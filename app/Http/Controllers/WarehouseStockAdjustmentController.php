<?php

namespace App\Http\Controllers;

use App\Models\GciPart;
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
    private const AUTHORITY_ROLES = ['admin', 'ppic'];
    private const EVENT_TYPES = [
        'stock_opname',
        'audit_correction',
        'system_posting_fix',
        'damage_loss_confirmation',
        'month_end_cutoff',
    ];

    private function ensureAdjustmentAuthority(): void
    {
        $role = strtolower((string) (auth()->user()?->role ?? ''));
        abort_unless(in_array($role, self::AUTHORITY_ROLES, true), 403, 'Stock adjustment hanya boleh dilakukan oleh authority tertentu.');
    }

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
            ->with(['part', 'gciPart', 'location', 'creator'])
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
                $q->where(function($qq) use ($s) {
                    $qq->whereHas('part', function ($qp) use ($s) {
                        $qp->where('part_no', 'like', '%' . $s . '%')
                            ->orWhere('part_name_gci', 'like', '%' . $s . '%')
                            ->orWhere('part_name_vendor', 'like', '%' . $s . '%');
                    })->orWhereHas('gciPart', function ($qg) use ($s) {
                        $qg->where('part_no', 'like', '%' . $s . '%')
                            ->orWhere('part_name', 'like', '%' . $s . '%');
                    })->orWhere('location_code', 'like', '%' . $s . '%');
                });

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

        $canCreateAdjustment = in_array(strtolower((string) (auth()->user()?->role ?? '')), self::AUTHORITY_ROLES, true);

        return view('warehouse.stock.adjustments_index', compact('adjustments', 'search', 'location', 'dateFrom', 'dateTo', 'perPage', 'hasMoveFields', 'canCreateAdjustment'));
    }

    public function create()
    {
        $this->ensureAdjustmentAuthority();

        $locations = WarehouseLocation::query()->where('status', 'ACTIVE')->orderBy('location_code')->get();
        $eventTypes = self::EVENT_TYPES;

        return view('warehouse.stock.adjustments_create', compact('locations', 'eventTypes'));
    }

    public function store(Request $request)
    {
        $this->ensureAdjustmentAuthority();

        $validated = $request->validate([
            'part_id' => ['required'], // Could be parts.id (vendor) or gci_parts.id (master)
            'event_type' => ['required', Rule::in(self::EVENT_TYPES)],

            'location_code' => [
                'required',
                'string',
                'max:50',
                Rule::exists('warehouse_locations', 'location_code')->where(fn ($q) => $q->where('status', 'ACTIVE')),
            ],
            'batch_no' => ['nullable', 'string', 'max:255'],
            'qty_after' => ['required', 'numeric', 'min:0'],
            'adjusted_at' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $partId = (int) $validated['part_id'];
        $hasMoveFields = Schema::hasColumn('location_inventory_adjustments', 'from_location_code')
            && Schema::hasColumn('location_inventory_adjustments', 'to_location_code');
        $hasActionType = Schema::hasColumn('location_inventory_adjustments', 'action_type');
        $hasEventType = Schema::hasColumn('location_inventory_adjustments', 'event_type');

        // Normalize date input
        $adjustedAt = isset($validated['adjusted_at']) && $validated['adjusted_at']
            ? Carbon::parse($validated['adjusted_at'])
            : now();

        $locationCode = strtoupper(trim((string) $validated['location_code']));
        $batchNo = isset($validated['batch_no']) && trim((string) $validated['batch_no']) !== ''
            ? strtoupper(trim((string) $validated['batch_no']))
            : null;
        $qtyAfter = (float) $validated['qty_after'];

        DB::transaction(function () use ($partId, $locationCode, $batchNo, $qtyAfter, $adjustedAt, $validated, $hasMoveFields, $hasActionType, $hasEventType) {
            // Resolve gci_part_id
            $gciPartId = null;
            $p = Part::find($partId);
            if ($p) {
                $gciPartId = $p->gci_part_id;
            } else {
                $gciPartId = GciPart::where('id', $partId)->value('id');
                if (!$gciPartId) {
                    throw new \Exception("Part ID {$partId} not found in master list.");
                }
            }

            // If batch specified, adjust only that batch
            if ($batchNo !== null) {
                $locInv = LocationInventory::query()
                    ->where('gci_part_id', $gciPartId)
                    ->where('location_code', $locationCode)
                    ->where('batch_no', $batchNo)
                    ->lockForUpdate()
                    ->first();

                $qtyBefore = $locInv ? (float) $locInv->qty_on_hand : 0.0;
                $qtyChange = $qtyAfter - $qtyBefore;

                if (!$locInv) {
                    $locInv = LocationInventory::query()->create([
                        'gci_part_id' => $gciPartId,
                        'part_id' => $p ? $partId : null,
                        'location_code' => $locationCode,
                        'batch_no' => $batchNo,
                        'qty_on_hand' => 0,
                    ]);
                }

                $locInv->update([
                    'qty_on_hand' => $qtyAfter,
                    'last_counted_at' => $adjustedAt,
                ]);

                // Global inventory summary is auto-synced by LocationInventory model.

                $adj = LocationInventoryAdjustment::query()->create([
                    'part_id' => $p ? $partId : null,
                    'gci_part_id' => $gciPartId,
                    'location_code' => $locationCode,
                    'batch_no' => $batchNo,
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                    'qty_change' => $qtyChange,
                    'reason' => $validated['reason'],
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
                        if ($hasEventType) {
                            $update['event_type'] = $validated['event_type'];
                        }
                        if ($update) {
                            $adj->update($update);
                        }
                    }
                }
            } else {
                // No batch specified: adjust total qty at location (all batches combined)
                $rows = LocationInventory::query()
                    ->where('location_code', $locationCode)
                    ->when($gciPartId, fn ($q) => $q->where('gci_part_id', $gciPartId))
                    ->when(!$gciPartId && $p, fn ($q) => $q->where('part_id', $partId))
                    ->lockForUpdate()
                    ->get();

                $qtyBefore = (float) $rows->sum('qty_on_hand');
                $qtyChange = $qtyAfter - $qtyBefore;

                if ($rows->isEmpty()) {
                    $locInv = LocationInventory::query()->create([
                        'gci_part_id' => $gciPartId,
                        'part_id' => $p ? $partId : null,
                        'location_code' => $locationCode,
                        'qty_on_hand' => 0,
                    ]);
                    $rows = collect([$locInv]);
                }

                $remainingQty = $qtyAfter;
                foreach ($rows->sortBy(fn ($row) => [$row->production_date ?? '9999-12-31', $row->batch_no ?? '']) as $row) {
                    $nextQty = max(min($remainingQty, (float) $row->qty_on_hand), 0);
                    if ($remainingQty > (float) $row->qty_on_hand) {
                        $nextQty = (float) $row->qty_on_hand;
                    }
                    $row->update([
                        'qty_on_hand' => $nextQty,
                        'last_counted_at' => $adjustedAt,
                    ]);
                    $remainingQty -= $nextQty;
                }
                if ($remainingQty > 0 && $rows->isNotEmpty()) {
                    $seed = $rows->first();
                    LocationInventory::query()->create([
                        'gci_part_id' => $gciPartId ?: $seed->gci_part_id,
                        'part_id' => $p ? $partId : $seed->part_id,
                        'location_code' => $locationCode,
                        'batch_no' => null,
                        'qty_on_hand' => $remainingQty,
                        'last_counted_at' => $adjustedAt,
                    ]);
                }

                // Summary is auto-synced by model.

                $adj = LocationInventoryAdjustment::query()->create([
                    'part_id' => $p ? $partId : null,
                    'gci_part_id' => $gciPartId,
                    'location_code' => $locationCode,
                    'batch_no' => null,
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                    'qty_change' => $qtyChange,
                    'reason' => $validated['reason'],
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
                        if ($hasEventType) {
                            $update['event_type'] = $validated['event_type'];
                        }
                        if ($update) {
                            $adj->update($update);
                        }
                    }
                }
            }
        });

        return redirect()->route('warehouse.stock-adjustments.index')->with('success', 'Special stock adjustment saved.');
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
            ->where(function($q) use ($partId) {
                $q->where('part_id', $partId)
                  ->orWhere('gci_part_id', $partId);
            })
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
