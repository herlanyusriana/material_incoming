<?php

namespace App\Http\Controllers;

use App\Models\LocationInventory;
use App\Models\Receive;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class WarehousePutawayController extends Controller
{
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
        $qtyUnit = strtoupper(trim((string) ($receive->qty_unit ?? '')));
        $qtyContribution = $qtyUnit === 'COIL'
            ? (float) ($receive->net_weight ?? 0)
            : (float) ($receive->qty ?? 0);

        if ($qtyContribution <= 0) {
            return back()->with('error', 'Qty receive invalid untuk putaway.');
        }

        DB::transaction(function () use ($receive, $partId, $oldLocationCode, $newLocationCode, $qtyContribution) {
            $receive = Receive::query()->whereKey($receive->id)->lockForUpdate()->firstOrFail();

            $existingLoc = strtoupper(trim((string) ($receive->location_code ?? '')));
            if ($existingLoc !== '' && $existingLoc !== $oldLocationCode) {
                $oldLocationCode = $existingLoc;
            }

            if ($oldLocationCode !== '' && $oldLocationCode !== $newLocationCode) {
                LocationInventory::updateStock($partId, $oldLocationCode, -$qtyContribution);
            }

            if ($oldLocationCode === '' || $oldLocationCode !== $newLocationCode) {
                LocationInventory::updateStock($partId, $newLocationCode, $qtyContribution);
            }

            $receive->update(['location_code' => $newLocationCode]);
        });

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
            'receive_ids' => ['required', 'array', 'min:1'],
            'receive_ids.*' => ['integer'],
        ]);

        $newLocationCode = strtoupper(trim((string) $validated['location_code']));
        $receiveIds = array_values(array_unique(array_map('intval', $validated['receive_ids'])));

        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($receiveIds, $newLocationCode, &$updated, &$skipped) {
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

                LocationInventory::updateStock($partId, $newLocationCode, $qtyContribution);
                $receive->update(['location_code' => $newLocationCode]);
                $updated++;
            }
        });

        $msg = "Putaway bulk selesai. {$updated} updated.";
        if ($skipped > 0) {
            $msg .= " {$skipped} skipped.";
        }

        return back()->with('success', $msg);
    }
}
