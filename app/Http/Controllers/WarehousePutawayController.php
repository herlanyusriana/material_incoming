<?php

namespace App\Http\Controllers;

use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\WarehouseLocation;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Inventory\InventoryStockMovement;
use App\Models\NewSchema\Incoming\IncomingReceive as Receive;
use App\Models\NewSchema\Incoming\IncomingArrivalItem as ArrivalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Traits\LogsActivity;

class WarehousePutawayController extends Controller
{
    use LogsActivity;

    private function resolveGciPartId(Receive $receive): ?int
    {
        $receive->loadMissing(['arrivalItem.vendorPart', 'arrivalItem.gciPart']);
        $arrivalItem = $receive->arrivalItem;

        if (!empty($arrivalItem->gci_part_id)) {
            return (int) $arrivalItem->gci_part_id;
        }

        return $arrivalItem?->vendorPart?->gci_part_id ?: null;
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 50);
        if ($perPage < 10) {
            $perPage = 10;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }

        $query = Receive::query()
            ->with(['arrivalItem.vendorPart', 'arrivalItem.gciPart', 'arrivalItem.arrival.vendor'])
            ->where('qc_status', 'pass')
            ->where(function ($q) {
                // Antrean putaway = belum pindah rak: kosong atau masih di RECEIVING.
                $q->whereNull('location_code')
                    ->orWhere('location_code', '')
                    ->orWhere('location_code', 'RECEIVING');
            })
            ->when($search !== '', function ($q) use ($search) {
                $s = strtoupper($search);
                $q->where(function ($qq) use ($s) {
                    $qq->where('tag', 'like', '%' . $s . '%')
                        ->orWhereHas('arrivalItem.arrival', fn ($qa) => $qa->where('arrival_no', 'like', '%' . $s . '%'))
                        ->orWhereHas('arrivalItem.gciPart', fn ($qp) => $qp->where('part_no', 'like', '%' . $s . '%'))
                        ->orWhereHas('arrivalItem.vendorPart', fn ($qp) => $qp->where('vendor_part_no', 'like', '%' . $s . '%'));
                });
            })
            ->latest();

        $rows = $query->paginate($perPage)->withQueryString();

        $locationCodes = [];
        if (Schema::hasTable('warehouse_locations')) {
            $locationCodes = WarehouseLocation::query()
                ->where('status', 'active')
                ->orderBy('location_code')
                ->pluck('location_code')
                ->all();
        }

        return view('warehouse.putaway.index', compact('rows', 'search', 'perPage', 'locationCodes'));
    }

    public function store(Request $request, Receive $receive)
    {
        $locationCodeRule = ['required', 'string', 'max:50'];
        if (Schema::hasTable('warehouse_locations')) {
            $locationCodeRule[] = Rule::exists('warehouse_locations', 'location_code')->where(fn ($q) => $q->where('status', 'active'));
        }

        $validated = $request->validate([
            'location_code' => $locationCodeRule,
            'putaway_date' => ['nullable', 'date'],
        ]);

        if ($receive->qc_status !== 'pass') {
            return back()->with('error', 'Putaway hanya untuk QC status PASS.');
        }

        // Guard UOM: COIL = kemasan, bukan satuan stok. Yang masuk stok harus
        // net_weight dan part wajib ber-UOM KGM.
        $gciPartForGuard = GciPart::find($this->resolveGciPartId($receive) ?? 0);
        $receiveQtyUnit = \App\Support\Uom::canonical($receive->qty_unit);
        $putawayUom = \App\Support\Uom::canonical($gciPartForGuard?->uom);
        if ($receiveQtyUnit === 'COIL' && $putawayUom !== 'KGM') {
            return back()->with('error',
                "Putaway ditolak: receive dalam COIL hanya boleh untuk part ber-UOM KGM "
                . ($gciPartForGuard ? "(part ini: {$putawayUom}). Set UOM stok part di Master Part dulu." : '(part belum ter-link ke GCI Part Master).')
            );
        }

        $gciPartId = $this->resolveGciPartId($receive);
        if ($gciPartId === null) {
            return back()->with('error', 'Part belum terhubung ke GCI Part Master untuk receive ini.');
        }

        $newLocationCode = strtoupper(trim((string) $validated['location_code']));
        $oldLocationCode = strtoupper(trim((string) ($receive->location_code ?? '')));
        $putawayDate = !empty($validated['putaway_date'])
            ? Carbon::parse((string) $validated['putaway_date'])->endOfDay()
            : now();
        $qtyUnit = strtoupper(trim((string) ($receive->qty_unit ?? '')));
        $qtyContribution = $qtyUnit === 'COIL'
            ? (float) ($receive->net_weight ?? 0)
            : (float) ($receive->qty ?? 0);

        if ($qtyContribution <= 0) {
            return back()->with('error', 'Qty receive invalid untuk putaway.');
        }

        DB::transaction(function () use ($receive, $gciPartId, $oldLocationCode, $newLocationCode, $qtyContribution, $putawayDate) {
            $receive = Receive::query()->whereKey($receive->id)->lockForUpdate()->firstOrFail();

            $existingLoc = strtoupper(trim((string) ($receive->location_code ?? '')));
            if ($existingLoc !== '' && $existingLoc !== $oldLocationCode) {
                $oldLocationCode = $existingLoc;
            }

            if ($oldLocationCode !== '' && $oldLocationCode !== $newLocationCode) {
                InventoryLocationStock::updateStock(
                    $gciPartId,
                    $oldLocationCode,
                    -$qtyContribution,
                    $receive->tag,
                    $receive->tag,
                    'PUTAWAY',
                    "RCV#{$receive->id}",
                    $receive->id,
                    null,
                    null,
                    null,
                    null,
                    null
                );
            }

            if ($oldLocationCode === '' || $oldLocationCode !== $newLocationCode) {
                InventoryLocationStock::updateStock(
                    $gciPartId,
                    $newLocationCode,
                    $qtyContribution,
                    $receive->tag,
                    $receive->tag,
                    'PUTAWAY',
                    "RCV#{$receive->id}",
                    $receive->id,
                    null,
                    null,
                    null,
                    null,
                    null
                );
            }

            $receive->update(['location_code' => $newLocationCode]);
        });

        $this->logActivity('STORE Putaway', "receive_id:{$receive->id} location:{$newLocationCode}", [
            'gci_part_id' => $gciPartId,
            'qty' => $qtyContribution,
            'old_location' => $oldLocationCode,
        ]);

        return back()->with('success', 'Putaway berhasil. Lokasi tersimpan.');
    }

    public function bulk(Request $request)
    {
        $locationCodeRule = ['required', 'string', 'max:50'];
        if (Schema::hasTable('warehouse_locations')) {
            $locationCodeRule[] = Rule::exists('warehouse_locations', 'location_code')->where(fn ($q) => $q->where('status', 'active'));
        }

        $validated = $request->validate([
            'location_code' => $locationCodeRule,
            'putaway_date' => ['nullable', 'date'],
            'receive_ids' => ['required', 'array', 'min:1'],
            'receive_ids.*' => ['integer'],
        ]);

        $newLocationCode = strtoupper(trim((string) $validated['location_code']));
        $putawayDate = !empty($validated['putaway_date'])
            ? Carbon::parse((string) $validated['putaway_date'])->endOfDay()
            : now();
        $receiveIds = array_values(array_unique(array_map('intval', $validated['receive_ids'])));

        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($receiveIds, $newLocationCode, $putawayDate, &$updated, &$skipped) {
            $receives = Receive::query()
                ->whereIn('id', $receiveIds)
                ->lockForUpdate()
                ->get();

            foreach ($receives as $receive) {
                if ($receive->qc_status !== 'pass') {
                    $skipped++;
                    continue;
                }

                // Baris yang masih di lokasi RECEIVING (auto-post QC) boleh
                // diputaway ulang; baris yang sudah pindah rak dilewati.
                $existingLoc = strtoupper(trim((string) ($receive->location_code ?? '')));
                if ($existingLoc !== '' && $existingLoc !== 'RECEIVING') {
                    $skipped++;
                    continue;
                }

                $gciPartId = $this->resolveGciPartId($receive);
                if ($gciPartId === null) {
                    $skipped++;
                    continue;
                }

                $qtyUnit = strtoupper(trim((string) ($receive->qty_unit ?? '')));
                $qtyContribution = $qtyUnit === 'COIL'
                    ? (float) ($receive->net_weight ?? 0)
                    : (float) ($receive->qty ?? 0);
                if ($qtyContribution <= 0) {
                    $skipped++;
                    continue;
                }

                if ($qtyUnit === 'COIL' && \App\Support\Uom::canonical(GciPart::query()->whereKey($gciPartId)->value('uom')) !== 'KGM') {
                    $skipped++;
                    continue;
                }

                InventoryLocationStock::updateStock(
                    $gciPartId,
                    $newLocationCode,
                    $qtyContribution,
                    $receive->tag,
                    $receive->tag,
                    'PUTAWAY',
                    "RCV#{$receive->id}",
                    $receive->id,
                    null,
                    null,
                    null,
                    null,
                    null
                );
                $receive->update(['location_code' => $newLocationCode]);
                $updated++;
            }
        });

        $msg = "Putaway bulk selesai. {$updated} updated.";
        if ($skipped > 0) {
            $msg .= " {$skipped} skipped.";
        }

        $this->logActivity('BULK Putaway', "location:{$newLocationCode}", [
            'updated' => $updated,
            'skipped' => $skipped,
            'receive_ids' => $receiveIds,
        ]);

        return back()->with('success', $msg);
    }

    public function destroy(Receive $receive)
    {
        // RECEIVING = lokasi virtual menunggu putaway, boleh dihapus dari
        // antrean (stok di-void). Lokasi rak sungguhan tetap diblokir.
        $currentLocation = strtoupper(trim((string) ($receive->location_code ?? '')));
        if ($currentLocation !== '' && $currentLocation !== 'RECEIVING') {
            return back()->with('error', 'Receive ini sudah di-putaway ke rak, tidak bisa dihapus dari antrean. Gunakan Stock Adjustment.');
        }

        $gciPartId = $this->resolveGciPartId($receive);
        $qtyUnit = strtoupper(trim((string) ($receive->qty_unit ?? '')));
        $qtyContribution = $qtyUnit === 'COIL'
            ? (float) ($receive->net_weight ?? 0)
            : (float) ($receive->qty ?? 0);

        DB::transaction(function () use ($receive, $gciPartId, $qtyContribution) {
            if ($gciPartId !== null && $receive->qc_status === 'pass' && $qtyContribution > 0 && !empty($receive->location_code)) {
                InventoryLocationStock::updateStock(
                    $gciPartId,
                    strtoupper(trim($receive->location_code)),
                    -$qtyContribution,
                    $receive->tag,
                    $receive->tag,
                    'PUTAWAY_DELETE',
                    "RCV#{$receive->id}",
                    $receive->id,
                    null,
                    null,
                    null,
                    null,
                    null
                );
            }

            $receive->delete();
        });

        $this->logActivity('DELETE from Putaway', "receive_id:{$receive->id}", [
            'gci_part_id' => $gciPartId,
            'qty_deducted' => $qtyContribution,
        ]);

        return back()->with('success', 'Baris antrean berhasil dihapus beserta saldo inventorinya.');
    }
}
