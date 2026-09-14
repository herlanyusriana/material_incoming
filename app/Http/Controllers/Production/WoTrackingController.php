<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Incoming\IncomingReceive;
use App\Models\NewSchema\Production\ProductionWorkOrder;
use App\Support\Uom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * WO Manual — mekanisme sederhana.
 *
 * Form create: FG Part, Tanggal, WO Quantity, Main RM Part, RM Invoice, RM Tag.
 * List       : FG Part | WO NO | Tanggal | WO QTY | Result QTY | Sisa WO QTY | Invoice Asal | Aksi.
 * Posting hasil mengakumulasi qty_actual; status otomatis CLOSED saat target tercapai.
 */
class WoTrackingController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $wos = ProductionWorkOrder::query()
            ->with(['gciPart:id,part_no,part_name,uom', 'mainRmPart:id,part_no,part_name'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('work_order_no', 'like', "%{$q}%")
                        ->orWhereHas('gciPart', function ($gq) use ($q) {
                            $gq->where('part_no', 'like', "%{$q}%")
                                ->orWhere('part_name', 'like', "%{$q}%");
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('production.wo-tracking.index', compact('wos', 'q'));
    }

    /**
     * Master data ringan untuk form WO manual (satu request, di-cache browser singkat):
     * daftar FG, daftar RM, pemetaan FG -> Main RM (BOM aktif), stok incoming per RM
     * (invoice -> tag).
     */
    public function masterData()
    {
        $mapPart = fn ($p) => [
            'id' => (int) $p->id,
            'part_no' => $p->part_no,
            'part_name' => $p->part_name,
            'uom' => Uom::canonical($p->uom) ?? 'PCE',
        ];

        $fgParts = GciPart::query()
            ->where('classification', 'FG')->where('status', 'active')
            ->orderBy('part_no')->get(['id', 'part_no', 'part_name', 'uom'])
            ->map($mapPart);

        $rmParts = GciPart::query()
            ->where('classification', 'RM')->where('status', 'active')
            ->orderBy('part_no')->get(['id', 'part_no', 'part_name', 'uom'])
            ->map($mapPart);

        // FG part id -> RM part id (komponen RM BUY/FREE_ISSUE pertama dari BOM aktif).
        $mainRm = [];
        $boms = Bom::query()
            ->where('status', 'active')
            ->with(['part:id,part_no,classification', 'items:id,bom_id,component_part_id,component_part_no,make_or_buy'])
            ->get();
        foreach ($boms as $bom) {
            if (($bom->part?->classification ?? '') !== 'FG' || isset($mainRm[$bom->part_id])) {
                continue;
            }
            $rm = $bom->items->first(function ($item) {
                $mb = strtoupper(trim((string) $item->make_or_buy));

                return in_array($mb, ['BUY', 'FREE_ISSUE'], true) && $item->component_part_id;
            });
            if ($rm) {
                $mainRm[$bom->part_id] = (int) $rm->component_part_id;
            }
        }

        // gci_part_id -> invoice_no -> [tag, ...] dari penerimaan barang.
        $stock = [];
        $receives = IncomingReceive::query()
            ->whereRaw("TRIM(COALESCE(invoice_no,'')) <> ''")
            ->whereRaw("TRIM(COALESCE(tag,'')) <> ''")
            ->orderBy('id')
            ->get(['gci_part_id', 'invoice_no', 'tag']);
        foreach ($receives as $r) {
            if (!$r->gci_part_id) {
                continue;
            }
            $stock[(int) $r->gci_part_id][$r->invoice_no][] = $r->tag;
        }
        foreach ($stock as $pid => $inv) {
            foreach ($inv as $ino => $tags) {
                $stock[$pid][$ino] = array_values(array_unique($tags));
            }
        }

        return response()->json([
            'fg_parts' => $fgParts,
            'rm_parts' => $rmParts,
            'main_rm' => (object) $mainRm,
            'stock' => (object) $stock,
        ]);
    }

    public function create()
    {
        return view('production.wo-tracking.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFields($request);

        $wo = ProductionWorkOrder::create($validated + [
            'work_order_no' => $this->nextWoNumber(),
            'status' => 'PLANNED',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('production.wo-tracking.index')
            ->with('success', "WO {$wo->work_order_no} dibuat untuk {$wo->gciPart?->part_no}.");
    }

    public function edit(ProductionWorkOrder $woTracking)
    {
        $woTracking->load(['gciPart:id,part_no,part_name', 'mainRmPart:id,part_no,part_name']);

        return view('production.wo-tracking.edit', ['wo' => $woTracking]);
    }

    public function update(Request $request, ProductionWorkOrder $woTracking)
    {
        if (in_array($woTracking->status, ['CLOSED', 'CANCELLED'], true)) {
            return back()->with('error', 'WO sudah ditutup — tidak bisa diubah.');
        }

        $validated = $this->validatedFields($request);
        $woTracking->update($validated + ['updated_by' => auth()->id()]);

        return redirect()->route('production.wo-tracking.index')
            ->with('success', "WO {$woTracking->work_order_no} diperbarui.");
    }

    /**
     * Posting hasil: akumulasi qty_actual. Sisa = qty_target - qty_actual;
     * otomatis CLOSED saat sisa <= 0.
     */
    public function postResult(Request $request, ProductionWorkOrder $woTracking)
    {
        $validated = $request->validate([
            'qty_result' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $wo = DB::transaction(function () use ($woTracking, $validated) {
            $wo = ProductionWorkOrder::query()->whereKey($woTracking->id)->lockForUpdate()->firstOrFail();

            if (in_array($wo->status, ['CLOSED', 'CANCELLED'], true)) {
                abort(422, 'WO sudah ditutup — tidak bisa posting hasil.');
            }

            $wo->qty_actual = round((float) $wo->qty_actual + (float) $validated['qty_result'], 4);
            $wo->updated_by = auth()->id();
            if ($wo->qty_actual + 0.0001 >= (float) $wo->qty_target) {
                $wo->qty_actual = min((float) $wo->qty_actual, (float) $wo->qty_target);
                $wo->status = 'CLOSED';
                $wo->end_date = now()->toDateString();
            } else {
                $wo->status = 'IN_PRODUCTION';
            }
            $wo->save();

            return $wo;
        });

        return redirect()->route('production.wo-tracking.index')
            ->with('success', "Hasil WO {$wo->work_order_no}: total {$wo->qty_actual} dari target {$wo->qty_target}.");
    }

    public function destroy(ProductionWorkOrder $woTracking)
    {
        $woTracking->delete();

        return redirect()->route('production.wo-tracking.index')
            ->with('success', "WO {$woTracking->work_order_no} dihapus.");
    }

    private function validatedFields(Request $request): array
    {
        $data = $request->validate([
            'gci_part_id' => ['required', 'integer', Rule::exists('gci_parts', 'id')->where('classification', 'FG')],
            'start_date' => ['required', 'date'],
            'qty_target' => ['required', 'numeric', 'min:0.0001'],
            'main_rm_part_id' => ['nullable', 'integer', Rule::exists('gci_parts', 'id')->where('classification', 'RM')],
            'rm_invoice_no' => ['nullable', 'string', 'max:255'],
            'rm_tag' => ['nullable', 'string', 'max:255'],
        ]);

        return [
            'gci_part_id' => (int) $data['gci_part_id'],
            'start_date' => $data['start_date'],
            'qty_target' => round((float) $data['qty_target'], 4),
            'main_rm_part_id' => isset($data['main_rm_part_id']) ? (int) $data['main_rm_part_id'] : null,
            'rm_invoice_no' => trim((string) ($data['rm_invoice_no'] ?? '')) ?: null,
            'rm_tag' => trim((string) ($data['rm_tag'] ?? '')) ?: null,
        ];
    }

    private function nextWoNumber(): string
    {
        $prefix = 'WO-' . now()->format('Ymd') . '-';
        $last = ProductionWorkOrder::query()->withTrashed()
            ->where('work_order_no', 'like', $prefix . '%')
            ->max('work_order_no');
        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
