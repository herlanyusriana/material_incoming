<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\BomItemSubstitute;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Production\ProductionWorkOrder;
use App\Models\NewSchema\Production\WoMaterialAllocation;
use App\Models\NewSchema\Production\WoRequirement;
use App\Services\WoTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * WO Manual + Material Tracking (scan-pick, terkunci sampai closed).
 * Alur: PLANNED -> (alokasi) RELEASED -> (hasil) CLOSED/CANCELLED.
 */
class WoTrackingController extends Controller
{
    public function __construct(private WoTrackingService $tracking)
    {
    }

    public function index(Request $request)
    {
        $status = strtoupper(trim((string) $request->query('status', '')));
        $q = trim((string) $request->query('q', ''));

        $wos = ProductionWorkOrder::query()
            ->with(['gciPart', 'requirements', 'allocations'])
            ->when(in_array($status, ['PLANNED', 'RELEASED', 'IN_PRODUCTION', 'CLOSED', 'CANCELLED'], true), fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $query->where('work_order_no', 'like', "%{$q}%"))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('production.wo-tracking.index', compact('wos', 'status', 'q'));
    }

    public function create()
    {
        $fgParts = GciPart::query()
            ->where('classification', 'FG')
            ->where('status', 'active')
            ->orderBy('part_no')
            ->get(['id', 'part_no', 'part_name', 'model', 'uom']);

        return view('production.wo-tracking.create', compact('fgParts'));
    }

    /** Preview demand tanpa menyimpan (AJAX: part + qty). */
    public function previewExplosion(Request $request)
    {
        $validated = $request->validate([
            'gci_part_id' => ['required', 'integer', 'exists:gci_parts,id'],
            'qty_target' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $part = GciPart::findOrFail((int) $validated['gci_part_id']);
        $bom = Bom::activeVersion($part->id, now());

        $lines = collect();
        if ($bom) {
            foreach ($bom->items()->with(['componentPart', 'incomingPart.gciPart', 'substitutes.part'])->get() as $item) {
                $componentPartId = (int) ($item->incomingPart?->gciPart?->id ?? $item->component_part_id ?? 0);
                if ($componentPartId <= 0 || in_array(strtoupper(trim((string) ($item->make_or_buy ?? ''))), ['BUY', 'FREE_ISSUE'], true)) {
                    continue;
                }
                $required = round((float) ($item->net_required ?? $item->usage_qty ?? 0) * (float) $validated['qty_target'], 4);
                if ($required <= 0) {
                    continue;
                }
                $substitutes = $item->substitutes
                    ->where('status', 'active')
                    ->filter(fn ($sub) => $sub->substitute_part_id)
                    ->filter(fn ($sub) => InventoryLocationStock::where('gci_part_id', $sub->substitute_part_id)->where('qty_on_hand', '>', 0)->exists())
                    ->sortBy('priority');
                foreach ($substitutes as $substitute) {
                    $sp = $substitute->part;
                    $lines->push([
                        'part_no' => $sp?->part_no ?? $substitute->substitute_part_no,
                        'part_name' => $sp?->part_name,
                        'required_qty' => round($required * (float) ($substitute->ratio ?: 1), 4),
                        'uom' => \App\Support\Uom::canonical($sp?->uom) ?? 'PCE',
                        'policy' => $item->consumption_policy_override ?: ($sp?->consumption_policy ?: (($sp?->is_backflush ?? true) ? 'backflush_return' : 'direct_issue')),
                    ]);
                }
            }
        }

        return response()->json([
            'bom_found' => $bom !== null,
            'fg_uom' => \App\Support\Uom::canonical($part->uom) ?? 'PCE',
            'lines' => $lines->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gci_part_id' => ['required', 'integer', 'exists:gci_parts,id'],
            'qty_target' => ['required', 'numeric', 'min:0.0001'],
            'start_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $wo = DB::transaction(function () use ($validated, $request) {
            $part = GciPart::findOrFail((int) $validated['gci_part_id']);

            $wo = ProductionWorkOrder::create([
                'work_order_no' => $this->nextWoNumber(),
                'gci_part_id' => $part->id,
                'qty_target' => $validated['qty_target'],
                'status' => 'PLANNED',
                'start_date' => $validated['start_date'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $this->tracking->buildRequirements($wo);

            return $wo;
        });

        return redirect()->route('production.wo-tracking.show', $wo)
            ->with('success', 'WO dibuat. Lanjutkan scan-pick material.');
    }

    public function show(ProductionWorkOrder $woTracking)
    {
        $woTracking->load(['gciPart', 'requirements.gciPart', 'allocations.gciPart']);

        $suggestions = [];
        foreach ($woTracking->requirements as $requirement) {
            $suggestions[$requirement->id] = $this->tracking->suggestPicks($woTracking, $requirement);
        }

        $allocatedByPart = $woTracking->allocations
            ->whereIn('status', [WoMaterialAllocation::STATUS_RESERVED, WoMaterialAllocation::STATUS_CONSUMED])
            ->groupBy('gci_part_id')
            ->map(fn ($group) => (float) $group->sum('qty_reserved'));

        return view('production.wo-tracking.show', compact('woTracking', 'suggestions', 'allocatedByPart'));
    }

    // ---- Fase B: scan & alokasi ----

    public function locateTag(Request $request, ProductionWorkOrder $woTracking)
    {
        $validated = $request->validate(['tag' => ['required', 'string', 'max:255']]);
        $info = $this->tracking->locateTag(trim($validated['tag']));

        // Kebutuhan WO ini untuk part dari tag tsb (kalau ada).
        $requirement = null;
        if ($info['gci_part_id'] > 0) {
            $requirement = $woTracking->requirements->first(function ($candidate) use ($info) {
                return BomItemSubstitute::query()
                    ->where('bom_item_id', $candidate->bom_item_id)
                    ->where('substitute_part_id', $info['gci_part_id'])
                    ->where('status', 'active')
                    ->exists();
            });
            if ($requirement) {
                $info['suggestion'] = $this->tracking->suggestPicks($woTracking, $requirement);
            }
        }
        $info['requirement_id'] = $requirement?->id;
        $info['requirement_qty'] = $requirement?->required_qty;

        return response()->json($info);
    }

    public function allocate(Request $request, ProductionWorkOrder $woTracking, WoRequirement $requirement)
    {
        $validated = $request->validate([
            'tag' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'min:0.0001'],
            'location_code' => ['nullable', 'string', 'max:50'],
        ]);

        if ($requirement->work_order_id !== $woTracking->id) {
            abort(422, 'Requirement bukan milik WO ini.');
        }

        $this->tracking->allocate(
            $woTracking,
            $requirement,
            trim($validated['tag']),
            (float) $validated['qty'],
            $validated['location_code'] ?? null
        );

        return back()->with('success', "Tag {$validated['tag']} dialokasikan ke WO.");
    }

    public function deallocate(Request $request, ProductionWorkOrder $woTracking, WoMaterialAllocation $allocation)
    {
        if ($allocation->work_order_id !== $woTracking->id) {
            abort(422, 'Alokasi bukan milik WO ini.');
        }
        if ($allocation->status !== WoMaterialAllocation::STATUS_RESERVED) {
            abort(422, 'Alokasi sudah dikonsumsi — tidak bisa dilepas. Gunakan Return.');
        }

        $this->tracking->returnAllocation($allocation);

        return back()->with('success', 'Alokasi dikembalikan ke gudang.');
    }

    public function release(ProductionWorkOrder $woTracking)
    {
        if (! in_array($woTracking->status, ['PLANNED', 'RELEASED'], true)) {
            return back()->with('error', 'WO tidak dalam status yang bisa di-release.');
        }

        $short = $woTracking->requirements->filter(function (WoRequirement $requirement) use ($woTracking) {
            $covered = (float) $woTracking->allocations()
                ->where('requirement_id', $requirement->id)
                ->whereIn('status', [WoMaterialAllocation::STATUS_RESERVED, WoMaterialAllocation::STATUS_CONSUMED])
                ->sum('qty_reserved');

            return $covered + 1e-9 < (float) $requirement->required_qty;
        });

        if ($short->isNotEmpty()) {
            return back()->with('error', 'Material belum lengkap: ' . $short->map(fn ($r) => ($r->gciPart?->part_no ?? $r->component_part_no))->implode(', '));
        }

        $woTracking->update(['status' => 'RELEASED', 'released_at' => now(), 'released_by' => auth()->id()]);

        return back()->with('success', 'WO released — material terkunci untuk WO ini.');
    }

    // ---- Fase C: hasil + closure ----

    public function postResult(Request $request, ProductionWorkOrder $woTracking)
    {
        $validated = $request->validate([
            'qty_good' => ['required', 'numeric', 'min:0'],
            'qty_ng' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($validated, $woTracking, $request) {
            $woTracking = ProductionWorkOrder::query()->whereKey($woTracking->id)->lockForUpdate()->firstOrFail();

            if (in_array($woTracking->status, ['CLOSED', 'CANCELLED'], true)) {
                return back()->with('error', 'WO sudah ditutup.');
            }

            $qtyGood = (float) $validated['qty_good'];
            $qtyNg = (float) ($validated['qty_ng'] ?? 0);

            // Backflush per tag: konsumsi = good × usage (dari alokasi RESERVED).
            $woTracking->load('requirements');
            foreach ($woTracking->requirements as $requirement) {
                if ($requirement->consumption_policy === 'direct_issue') {
                    continue; // sudah dikonsumsi saat alokasi
                }
                $consumption = round((float) $requirement->required_qty * ($qtyGood / max((float) $woTracking->qty_target, 0.0001)), 4);
                $this->tracking->consumeForWo($woTracking, $requirement, $consumption);
            }

            // FG masuk stok: lot = WO# (genealogi ke coil via WO).
            $fgPart = $woTracking->gciPart;
            if ($qtyGood > 0 && $fgPart?->default_location) {
                InventoryLocationStock::updateStock(
                    (int) $fgPart->id,
                    strtoupper(trim((string) $fgPart->default_location)),
                    $qtyGood,
                    $woTracking->work_order_no,
                    $woTracking->work_order_no,
                    'WO_FG_OUTPUT',
                    "WO#{$woTracking->id}",
                    null,
                    null,
                    null,
                    null,
                    null,
                    auth()->id()
                );
            }

            $woTracking->qty_actual = (float) $woTracking->qty_actual + $qtyGood;
            $woTracking->status = 'IN_PRODUCTION';
            $woTracking->save();

            // Sisa alokasi RESERVED setelah posting = over-alokasi → tampil di board;
            // closure tetap menuntut consume/return penuh (guard assertClosable).
            return back()->with('success', "Hasil diposting: good {$qtyGood}, NG {$qtyNg}. Sisa alokasi harus di-return sebelum close.");
        });
    }

    public function close(ProductionWorkOrder $woTracking)
    {
        try {
            $this->tracking->assertClosable($woTracking);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }

        $woTracking->update(['status' => 'CLOSED', 'end_date' => now()->toDateString()]);

        return back()->with('success', 'WO closed. Semua material telah reconcile.');
    }

    public function cancel(ProductionWorkOrder $woTracking)
    {
        $this->tracking->cancelWo($woTracking);

        return back()->with('success', 'WO dibatalkan, semua alokasi dikembalikan ke gudang.');
    }

    private function nextWoNumber(): string
    {
        $prefix = 'WO-' . now()->format('Ymd') . '-';
        $last = ProductionWorkOrder::query()->where('work_order_no', 'like', $prefix . '%')->max('work_order_no');
        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
