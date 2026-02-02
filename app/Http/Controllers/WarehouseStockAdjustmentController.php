<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\LocationInventory;
use App\Models\LocationInventoryAdjustment;
use App\Models\Part;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class WarehouseStockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $location = strtoupper(trim((string) $request->query('location', '')));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $perPage = (int) $request->query('per_page', 50);
        if ($perPage < 10) {
            $perPage = 10;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }

        $hasMoveFields = Schema::hasColumn('location_inventory_adjustments', 'from_location_code')
            && Schema::hasColumn('location_inventory_adjustments', 'to_location_code');

        $query = LocationInventoryAdjustment::query()
            ->with(['part', 'location', 'creator'])
            ->when($location !== '', function ($q) use ($location, $hasMoveFields) {
                if ($hasMoveFields) {
                    $q->where(function ($qq) use ($location) {
                        $qq->where('location_code', $location)
                            ->orWhere('from_location_code', $location)
                            ->orWhere('to_location_code', $location);
                    });
                    return;
                }
                $q->where('location_code', $location);
            })
            ->when($search !== '', function ($q) use ($search, $hasMoveFields) {
                $s = strtoupper($search);
                $q->whereHas('part', function ($qp) use ($s) {
                    $qp->where('part_no', 'like', '%' . $s . '%')
                        ->orWhere('part_name_gci', 'like', '%' . $s . '%')
                        ->orWhere('part_name_vendor', 'like', '%' . $s . '%');
                })->orWhere('location_code', 'like', '%' . $s . '%');

                if ($hasMoveFields) {
                    $q->orWhere('from_location_code', 'like', '%' . $s . '%')
                        ->orWhere('to_location_code', 'like', '%' . $s . '%');
                }
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('adjusted_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('adjusted_at', '<=', $dateTo))
            ->orderByDesc('adjusted_at')
            ->orderByDesc('id');

        $adjustments = $query->paginate($perPage)->withQueryString();

        return view('warehouse.stock.adjustments_index', compact('adjustments', 'search', 'location', 'dateFrom', 'dateTo', 'perPage', 'hasMoveFields'));
    }

    public function create()
    {
        $parts = Part::orderBy('part_no')->get();
        $locations = WarehouseLocation::query()->where('status', 'ACTIVE')->orderBy('location_code')->get();

        return view('warehouse.stock.adjustments_create', compact('parts', 'locations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'part_id' => ['required', 'exists:parts,id'],
            'location_code' => [
                'required',
                'string',
                'max:50',
                Rule::exists('warehouse_locations', 'location_code')->where(fn ($q) => $q->where('status', 'ACTIVE')),
            ],
            'batch_no' => ['nullable', 'string', 'max:255'],
            'qty_after' => ['required', 'numeric', 'min:0'],
            'adjusted_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $partId = (int) $validated['part_id'];
        $locationCode = strtoupper(trim((string) $validated['location_code']));
        $batchNo = isset($validated['batch_no']) && trim((string) $validated['batch_no']) !== '' 
            ? strtoupper(trim((string) $validated['batch_no'])) 
            : null;
        $qtyAfter = (float) $validated['qty_after'];
        $adjustedAt = isset($validated['adjusted_at']) && $validated['adjusted_at']
            ? Carbon::parse($validated['adjusted_at'])
            : now();

        DB::transaction(function () use ($partId, $locationCode, $batchNo, $qtyAfter, $adjustedAt, $validated) {
            // If batch specified, adjust only that batch
            if ($batchNo !== null) {
                $locInv = LocationInventory::query()
                    ->where('part_id', $partId)
                    ->where('location_code', $locationCode)
                    ->where('batch_no', $batchNo)
                    ->lockForUpdate()
                    ->first();

                $qtyBefore = $locInv ? (float) $locInv->qty_on_hand : 0.0;
                $qtyChange = $qtyAfter - $qtyBefore;

                if (!$locInv) {
                    $locInv = LocationInventory::query()->create([
                        'part_id' => $partId,
                        'location_code' => $locationCode,
                        'batch_no' => $batchNo,
                        'qty_on_hand' => 0,
                    ]);
                }

                $locInv->update([
                    'qty_on_hand' => $qtyAfter,
                    'last_counted_at' => $adjustedAt,
                ]);

                // Update global inventory
                $inv = Inventory::query()->where('part_id', $partId)->lockForUpdate()->first();
                if (!$inv) {
                    if ($qtyChange < 0) {
                        throw new \Exception('Inventory tidak ditemukan untuk part ini, tidak bisa mengurangi stok.');
                    }
                    Inventory::query()->create([
                        'part_id' => $partId,
                        'on_hand' => $qtyChange,
                        'on_order' => 0,
                        'as_of_date' => $adjustedAt->toDateString(),
                    ]);
                } else {
                    $newOnHand = (float) $inv->on_hand + $qtyChange;
                    if ($newOnHand < 0) {
                        throw new \Exception('Adjustment menyebabkan inventory menjadi minus.');
                    }
                    $inv->update([
                        'on_hand' => $newOnHand,
                        'as_of_date' => $adjustedAt->toDateString(),
                    ]);
                }

                LocationInventoryAdjustment::query()->create([
                    'part_id' => $partId,
                    'location_code' => $locationCode,
                    'batch_no' => $batchNo,
                    'from_location_code' => $locationCode,
                    'to_location_code' => $locationCode,
                    'from_batch_no' => $batchNo,
                    'to_batch_no' => $batchNo,
                    'action_type' => 'adjustment',
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                    'qty_change' => $qtyChange,
                    'reason' => $validated['reason'] ?? null,
                    'adjusted_at' => $adjustedAt,
                    'created_by' => auth()->id(),
                ]);
            } else {
                // No batch specified: adjust total qty at location (all batches combined)
                $locInv = LocationInventory::query()
                    ->where('part_id', $partId)
                    ->where('location_code', $locationCode)
                    ->lockForUpdate()
                    ->first();

                $qtyBefore = $locInv ? (float) $locInv->qty_on_hand : 0.0;
                $qtyChange = $qtyAfter - $qtyBefore;

                if (!$locInv) {
                    $locInv = LocationInventory::query()->create([
                        'part_id' => $partId,
                        'location_code' => $locationCode,
                        'qty_on_hand' => 0,
                    ]);
                }

                $locInv->update([
                    'qty_on_hand' => $qtyAfter,
                    'last_counted_at' => $adjustedAt,
                ]);

                // Mirror adjustment to overall inventory (best-effort).
                $inv = Inventory::query()->where('part_id', $partId)->lockForUpdate()->first();
                if (!$inv) {
                    if ($qtyChange < 0) {
                        throw new \Exception('Inventory tidak ditemukan untuk part ini, tidak bisa mengurangi stok.');
                    }
                    Inventory::query()->create([
                        'part_id' => $partId,
                        'on_hand' => $qtyChange,
                        'on_order' => 0,
                        'as_of_date' => $adjustedAt->toDateString(),
                    ]);
                } else {
                    $newOnHand = (float) $inv->on_hand + $qtyChange;
                    if ($newOnHand < 0) {
                        throw new \Exception('Adjustment menyebabkan inventory menjadi minus.');
                    }
                    $inv->update([
                        'on_hand' => $newOnHand,
                        'as_of_date' => $adjustedAt->toDateString(),
                    ]);
                }

                LocationInventoryAdjustment::query()->create([
                    'part_id' => $partId,
                    'location_code' => $locationCode,
                    'batch_no' => null,
                    'from_location_code' => $locationCode,
                    'to_location_code' => $locationCode,
                    'from_batch_no' => null,
                    'to_batch_no' => null,
                    'action_type' => 'adjustment',
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                    'qty_change' => $qtyChange,
                    'reason' => $validated['reason'] ?? null,
                    'adjusted_at' => $adjustedAt,
                    'created_by' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('warehouse.stock-adjustments.index')->with('success', 'Stock adjustment saved.');
    }

    /**
     * API endpoint to get batches for a specific part and location
     */
    public function getBatches(Request $request)
    {
        $partId = $request->query('part_id');
        $locationCode = $request->query('location_code');

        if (!$partId || !$locationCode) {
            return response()->json([]);
        }

        $batches = LocationInventory::query()
            ->where('part_id', $partId)
            ->where('location_code', strtoupper(trim((string) $locationCode)))
            ->where('qty_on_hand', '>', 0)
            ->orderBy('production_date')
            ->orderBy('batch_no')
            ->get(['batch_no', 'qty_on_hand', 'production_date']);

        return response()->json($batches);
    }
}
