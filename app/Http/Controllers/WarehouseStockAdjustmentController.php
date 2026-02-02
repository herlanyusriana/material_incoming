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
        $locations = WarehouseLocation::query()->where('status', 'ACTIVE')->orderBy('location_code')->get();

        return view('warehouse.stock.adjustments_create', compact('locations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'action_type' => ['nullable', 'in:adjustment,move'],
            'part_id' => ['required', 'exists:parts,id'],

            // Adjustment fields
            'location_code' => [
                'required_if:action_type,adjustment',
                'nullable',
                'string',
                'max:50',
                Rule::exists('warehouse_locations', 'location_code')->where(fn ($q) => $q->where('status', 'ACTIVE')),
            ],
            'batch_no' => ['nullable', 'string', 'max:255'],
            'qty_after' => ['required_if:action_type,adjustment', 'nullable', 'numeric', 'min:0'],

            // Move fields (explicit from/to so history is readable)
            'from_location_code' => [
                'required_if:action_type,move',
                'nullable',
                'string',
                'max:50',
                Rule::exists('warehouse_locations', 'location_code')->where(fn ($q) => $q->where('status', 'ACTIVE')),
            ],
            'to_location_code' => [
                'required_if:action_type,move',
                'nullable',
                'string',
                'max:50',
                'different:from_location_code',
                Rule::exists('warehouse_locations', 'location_code')->where(fn ($q) => $q->where('status', 'ACTIVE')),
            ],
            'from_batch_no' => ['required_if:action_type,move', 'nullable', 'string', 'max:255'],
            'to_batch_no' => ['required_if:action_type,move', 'nullable', 'string', 'max:255'],
            'qty_move' => ['required_if:action_type,move', 'nullable', 'numeric', 'min:0.0001'],

            'adjusted_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $actionType = strtolower(trim((string) ($validated['action_type'] ?? 'adjustment')));
        $partId = (int) $validated['part_id'];
        $hasMoveFields = Schema::hasColumn('location_inventory_adjustments', 'from_location_code')
            && Schema::hasColumn('location_inventory_adjustments', 'to_location_code');
        $hasActionType = Schema::hasColumn('location_inventory_adjustments', 'action_type');

        // Normalize date input
        $adjustedAt = isset($validated['adjusted_at']) && $validated['adjusted_at']
            ? Carbon::parse($validated['adjusted_at'])
            : now();

        if ($actionType === 'move') {
            $fromLocation = strtoupper(trim((string) $validated['from_location_code']));
            $toLocation = strtoupper(trim((string) $validated['to_location_code']));
            $fromBatch = strtoupper(trim((string) $validated['from_batch_no']));
            $toBatch = strtoupper(trim((string) $validated['to_batch_no']));
            $qtyMove = (float) $validated['qty_move'];

            DB::transaction(function () use ($partId, $fromLocation, $toLocation, $fromBatch, $toBatch, $qtyMove, $adjustedAt, $validated, $hasMoveFields, $hasActionType) {
                $sourceBefore = LocationInventory::getStockByLocation($partId, $fromLocation, $fromBatch);
                if ($sourceBefore < $qtyMove) {
                    throw new \Exception("Insufficient stock at {$fromLocation} batch {$fromBatch}. Available: {$sourceBefore}, Requested: {$qtyMove}");
                }

                // Move stock between explicit batches
                LocationInventory::updateStock($partId, $fromLocation, -$qtyMove, $fromBatch);
                LocationInventory::updateStock($partId, $toLocation, $qtyMove, $toBatch);

                $payload = [
                    'part_id' => $partId,
                    // Keep legacy columns populated for compatibility with older UIs.
                    'location_code' => $fromLocation,
                    'batch_no' => $fromBatch,
                    'qty_before' => $sourceBefore,
                    'qty_after' => $sourceBefore - $qtyMove,
                    'qty_change' => 0 - $qtyMove,
                    'reason' => $validated['reason'] ?? null,
                    'adjusted_at' => $adjustedAt,
                    'created_by' => auth()->id(),
                ];
                if ($hasMoveFields) {
                    $payload['from_location_code'] = $fromLocation;
                    $payload['to_location_code'] = $toLocation;
                    $payload['from_batch_no'] = $fromBatch;
                    $payload['to_batch_no'] = $toBatch;
                }
                if ($hasActionType) {
                    $payload['action_type'] = 'move';
                }

                LocationInventoryAdjustment::query()->create($payload);
            });

            return redirect()->route('warehouse.stock-adjustments.index')->with('success', 'Stock move saved.');
        }

        $locationCode = strtoupper(trim((string) $validated['location_code']));
        $batchNo = isset($validated['batch_no']) && trim((string) $validated['batch_no']) !== ''
            ? strtoupper(trim((string) $validated['batch_no']))
            : null;
        $qtyAfter = (float) $validated['qty_after'];

        DB::transaction(function () use ($partId, $locationCode, $batchNo, $qtyAfter, $adjustedAt, $validated, $hasMoveFields, $hasActionType) {
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

                $adj = LocationInventoryAdjustment::query()->create([
                    'part_id' => $partId,
                    'location_code' => $locationCode,
                    'batch_no' => $batchNo,
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                    'qty_change' => $qtyChange,
                    'reason' => $validated['reason'] ?? null,
                    'adjusted_at' => $adjustedAt,
                    'created_by' => auth()->id(),
                ]);
                // Best-effort: enrich with move fields if columns exist.
                if ($hasMoveFields || $hasActionType) {
                    if ($adj) {
                        $update = [];
                        if ($hasMoveFields) {
                            $update['from_location_code'] = $locationCode;
                            $update['to_location_code'] = $locationCode;
                            $update['from_batch_no'] = $batchNo;
                            $update['to_batch_no'] = $batchNo;
                        }
                        if ($hasActionType) {
                            $update['action_type'] = 'adjustment';
                        }
                        if ($update) {
                            $adj->update($update);
                        }
                    }
                }
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

                $adj = LocationInventoryAdjustment::query()->create([
                    'part_id' => $partId,
                    'location_code' => $locationCode,
                    'batch_no' => null,
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                    'qty_change' => $qtyChange,
                    'reason' => $validated['reason'] ?? null,
                    'adjusted_at' => $adjustedAt,
                    'created_by' => auth()->id(),
                ]);
                if ($hasMoveFields || $hasActionType) {
                    if ($adj) {
                        $update = [];
                        if ($hasMoveFields) {
                            $update['from_location_code'] = $locationCode;
                            $update['to_location_code'] = $locationCode;
                            $update['from_batch_no'] = null;
                            $update['to_batch_no'] = null;
                        }
                        if ($hasActionType) {
                            $update['action_type'] = 'adjustment';
                        }
                        if ($update) {
                            $adj->update($update);
                        }
                    }
                }
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
        $limit = (int) $request->query('limit', 200);
        if ($limit < 10) {
            $limit = 10;
        }
        if ($limit > 500) {
            $limit = 500;
        }

        if (!$partId || !$locationCode) {
            return response()->json([]);
        }

        $base = LocationInventory::query()
            ->where('part_id', $partId)
            ->where('location_code', strtoupper(trim((string) $locationCode)))
            ->where('qty_on_hand', '>', 0)
            ->orderBy('production_date')
            ->orderBy('batch_no');

        $total = (clone $base)->count();

        $batches = $base
            ->limit($limit)
            ->get(['batch_no', 'qty_on_hand', 'production_date']);

        return response()->json([
            'batches' => $batches,
            'total' => $total,
            'limit' => $limit,
            'truncated' => $total > $limit,
        ]);
    }
}
