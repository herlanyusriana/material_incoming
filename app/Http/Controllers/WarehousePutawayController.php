<?php

namespace App\Http\Controllers;

use App\Models\LocationInventory;
use App\Models\Receive;
use App\Models\WarehouseLocation;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Traits\LogsActivity;

class WarehousePutawayController extends Controller
{
    use LogsActivity;

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
            ->with(['arrivalItem.part', 'arrivalItem.arrival.vendor'])
            ->where('qc_status', 'pass')
            ->where(function ($q) {
                $q->whereNull('location_code')->orWhere('location_code', '');
            })
            ->when($search !== '', function ($q) use ($search) {
                $s = strtoupper($search);
                $q->where(function ($qq) use ($s) {
                    $qq->where('tag', 'like', '%' . $s . '%')
                        ->orWhereHas('arrivalItem.arrival', fn ($qa) => $qa->where('arrival_no', 'like', '%' . $s . '%'))
                        ->orWhereHas('arrivalItem.part', fn ($qp) => $qp->where('part_no', 'like', '%' . $s . '%'));
                });
            })
            ->latest();

        $rows = $query->paginate($perPage)->withQueryString();

        $locationCodes = [];
        if (Schema::hasTable('warehouse_locations')) {
            $locationCodes = WarehouseLocation::query()
                ->where('status', 'ACTIVE')
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
            $locationCodeRule[] = Rule::exists('warehouse_locations', 'location_code')->where(fn ($q) => $q->where('status', 'ACTIVE'));
        }

        $validated = $request->validate([
            'location_code' => $locationCodeRule,
            'putaway_date' => ['nullable', 'date'],
        ]);

        if ($receive->qc_status !== 'pass') {
            return back()->with('error', 'Putaway hanya untuk QC status PASS.');
        }

        $receive->loadMissing(['arrivalItem']);
        $partId = (int) ($receive->arrivalItem?->part_id ?? 0);
        if ($partId <= 0) {
            return back()->with('error', 'Part belum terisi untuk receive ini.');
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

        DB::transaction(function () use ($receive, $partId, $oldLocationCode, $newLocationCode, $qtyContribution, $putawayDate) {
            $receive = Receive::query()->whereKey($receive->id)->lockForUpdate()->firstOrFail();

            $existingLoc = strtoupper(trim((string) ($receive->location_code ?? '')));
            if ($existingLoc !== '' && $existingLoc !== $oldLocationCode) {
                $oldLocationCode = $existingLoc;
            }

            if ($oldLocationCode !== '' && $oldLocationCode !== $newLocationCode) {
                LocationInventory::updateStock($partId, $oldLocationCode, -$qtyContribution, null, null, null, 'PUTAWAY', "RCV#{$receive->id}", [], $putawayDate);
            }

            if ($oldLocationCode === '' || $oldLocationCode !== $newLocationCode) {
                LocationInventory::updateStock($partId, $newLocationCode, $qtyContribution, null, null, null, 'PUTAWAY', "RCV#{$receive->id}", [], $putawayDate);
            }

            $receive->update(['location_code' => $newLocationCode]);
        });

        $this->logActivity('STORE Putaway', "receive_id:{$receive->id} location:{$newLocationCode}", [
            'part_id' => $partId,
            'qty' => $qtyContribution,
            'old_location' => $oldLocationCode,
        ]);

        return back()->with('success', 'Putaway berhasil. Lokasi tersimpan.');
    }

    public function bulk(Request $request)
    {
        $locationCodeRule = ['required', 'string', 'max:50'];
        if (Schema::hasTable('warehouse_locations')) {
            $locationCodeRule[] = Rule::exists('warehouse_locations', 'location_code')->where(fn ($q) => $q->where('status', 'ACTIVE'));
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

                $existingLoc = strtoupper(trim((string) ($receive->location_code ?? '')));
                if ($existingLoc !== '') {
                    $skipped++;
                    continue;
                }

                $receive->loadMissing(['arrivalItem']);
                $partId = (int) ($receive->arrivalItem?->part_id ?? 0);
                if ($partId <= 0) {
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

                LocationInventory::updateStock($partId, $newLocationCode, $qtyContribution, null, null, null, 'PUTAWAY', "RCV#{$receive->id}", [], $putawayDate);
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
        if (!empty($receive->location_code)) {
            return back()->with('error', 'Receive ini sudah di-putaway (punya lokasi), tidak bisa dihapus dari antrean.');
        }

        $receive->loadMissing(['arrivalItem']);
        $partId = (int) ($receive->arrivalItem?->part_id ?? ($receive->arrivalItem?->gci_part_vendor_id ?? 0));
        
        $qtyUnit = strtoupper(trim((string) ($receive->qty_unit ?? '')));
        $qtyContribution = $qtyUnit === 'COIL'
            ? (float) ($receive->net_weight ?? 0)
            : (float) ($receive->qty ?? 0);

        DB::transaction(function () use ($receive, $partId, $qtyContribution) {
            if ($partId > 0 && $receive->qc_status === 'pass' && $qtyContribution > 0) {
                // Deduct the inventory on_hand because it was added during Receive QC Pass
                $inventory = Inventory::query()->where('part_id', $partId)->lockForUpdate()->first();
                if ($inventory) {
                    $inventory->update([
                        'on_hand' => max(0, (float) $inventory->on_hand - $qtyContribution),
                    ]);
                }
            }

            $receive->delete();
        });

        $this->logActivity('DELETE from Putaway', "receive_id:{$receive->id}", [
            'part_id' => $partId,
            'qty_deducted' => $qtyContribution,
        ]);

        return back()->with('success', 'Baris antrean berhasil dihapus beserta saldo inventorinya.');
    }
}
