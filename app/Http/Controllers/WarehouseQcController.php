<?php

namespace App\Http\Controllers;

use App\Models\NewSchema\Incoming\IncomingReceive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WarehouseQcController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $status = strtolower(trim((string) $request->query('status', '')));
        $perPage = (int) $request->query('per_page', 50);
        if ($perPage < 10) {
            $perPage = 10;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }

        $allowed = ['hold', 'reject', 'fail'];
        if ($status !== '' && !in_array($status, $allowed, true)) {
            $status = '';
        }

        $query = IncomingReceive::query()
            ->with(['incomingArrivalItem.gciPart', 'incomingArrivalItem.incomingArrival.vendor', 'qcUpdater'])
            ->whereIn(DB::raw('LOWER(qc_status)'), $allowed)
            ->when($status !== '', fn ($q) => $q->whereRaw('LOWER(qc_status) = ?', [$status]))
            ->when($search !== '', function ($q) use ($search) {
                $s = strtoupper($search);
                $q->where(function ($qq) use ($s) {
                    $qq->where('tag', 'like', '%' . $s . '%')
                        ->orWhereHas('incomingArrivalItem.incomingArrival', fn ($qa) => $qa->where('arrival_no', 'like', '%' . $s . '%'))
                        ->orWhereHas('incomingArrivalItem.gciPart', fn ($qp) => $qp->where('part_no', 'like', '%' . $s . '%'));
                });
            })
            ->latest();

        $rows = $query->paginate($perPage)->withQueryString();

        return view('warehouse.qc.index', compact('rows', 'search', 'status', 'perPage'));
    }

    public function update(Request $request, IncomingReceive $receive)
    {
        $validated = $request->validate([
            'qc_status' => ['required', 'string', Rule::in(['pass', 'hold', 'reject'])],
            'qc_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $newStatus = strtolower(trim((string) $validated['qc_status']));
        $note = isset($validated['qc_note']) ? trim((string) $validated['qc_note']) : '';
        $note = $note !== '' ? $note : null;

        DB::transaction(function () use ($request, $receive, $newStatus, $note) {
            $receive->update([
                'qc_status' => $newStatus,
                'qc_note' => $note,
                'qc_updated_at' => now(),
                'qc_updated_by' => (int) ($request->user()?->id ?? 0) ?: null,
            ]);

            if ($newStatus === 'pass') {
                $this->postReceivingStock($receive);
            }
        });

        $msg = $newStatus === 'pass'
            ? 'QC updated to PASS. Stok tercatat di lokasi RECEIVING — lanjutkan Putaway.'
            : 'QC updated.';

        return back()->with('success', $msg);
    }

    /**
     * Fase 2 — stok QC-pass selalu terbentuk (tidak pernah "hilang").
     * Diposting ke lokasi virtual RECEIVING; putaway memindahkannya ke rak.
     * Idempoten: hanya posting jika baris stok tag ini belum ada di RECEIVING.
     */
    public static function postReceivingStock(IncomingReceive $receive): void
    {
        // Idempoten: satu receive hanya pernah diposting satu kali ke RECEIVING.
        $alreadyPosted = \App\Models\NewSchema\Inventory\InventoryStockMovement::query()
            ->where('source_reference', "RCV#{$receive->id}")
            ->where('movement_type', 'RECEIVE')
            ->where('to_location_code', 'RECEIVING')
            ->exists();
        if ($alreadyPosted) {
            return;
        }

        $arrivalItem = $receive->arrivalItem()->with('gciPart')->first();
        $gciPartId = (int) ($arrivalItem?->gci_part_id ?? 0);
        if ($gciPartId <= 0) {
            return; // part belum ter-link — tetap muncul di antrean putaway
        }

        $qtyUnit = \App\Support\Uom::canonical($receive->qty_unit);
        $qty = $qtyUnit === 'COIL'
            ? (float) ($receive->net_weight ?? 0)
            : (float) ($receive->qty ?? 0);
        if ($qty <= 0) {
            return;
        }

        if ($qtyUnit === 'COIL' && \App\Support\Uom::canonical($arrivalItem?->gciPart?->uom) !== 'KGM') {
            return; // guard UOM: tidak posting satuan salah, tunggu perbaikan part master
        }

        \App\Models\NewSchema\Inventory\InventoryLocationStock::updateStock(
            $gciPartId,
            'RECEIVING',
            $qty,
            $receive->tag,
            $receive->tag,
            'RECEIVE',
            "RCV#{$receive->id}",
            $receive->id,
            $arrivalItem?->arrival_id,
            $arrivalItem?->arrival?->invoice_no,
            null,
            null,
            null
        );

        // Tandai lokasi virtual supaya putaway melakukan PERPINDAHAN
        // (deduct RECEIVING + add rak tujuan), bukan menambah saldo dua kali.
        $receive->update(['location_code' => 'RECEIVING']);
    }
}

