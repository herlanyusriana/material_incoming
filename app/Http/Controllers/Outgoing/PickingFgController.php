<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Models\OutgoingPickingFg;
use App\Models\DeliveryOrder;
use App\Models\DeliveryNote;
use App\Models\DnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PickingFgController extends Controller
{
    /**
     * Display the Picking FG list for a given delivery date.
     */
    public function index(Request $request)
    {
        $selectedDate = $request->query('date')
            ? Carbon::parse($request->query('date'))->startOfDay()
            : now()->startOfDay();
        $dateStr = $selectedDate->toDateString();

        // Filters
        $filterStatus = $request->query('status');
        $filterSearch = $request->query('search');

        // Get picking records for this date
        $query = OutgoingPickingFg::with(['part', 'picker', 'outgoingPoItem.outgoingPo', 'deliveryOrder'])
            ->where('delivery_date', $dateStr);

        if ($filterStatus && in_array($filterStatus, ['pending', 'picking', 'completed'])) {
            $query->where('status', $filterStatus);
        }

        if ($filterSearch) {
            $search = $filterSearch;
            $query->where(function ($q) use ($search) {
                $q->whereHas('part', function ($pq) use ($search) {
                    $pq->where('part_no', 'like', "%{$search}%")
                        ->orWhere('part_name', 'like', "%{$search}%");
                })->orWhereHas('deliveryOrder', function ($sq) use ($search) {
                    $sq->where('do_no', 'like', "%{$search}%");
                });
            });
        }

        $picks = $query->get();

        // Group by Delivery Order
        $grouped = $picks->groupBy(function ($p) {
            return $p->delivery_order_id ?? 0;
        });

        // Preload existing DNs for completed DOs
        $doIds = $grouped->keys()->filter()->toArray();
        $dnByDoId = [];
        if (!empty($doIds)) {
            $dns = DeliveryNote::whereHas('deliveryOrders', function ($q) use ($doIds) {
                $q->whereIn('delivery_order_id', $doIds);
            })->with('deliveryOrders')->get();

            foreach ($dns as $dn) {
                foreach ($dn->deliveryOrders as $linkedDo) {
                    $dnByDoId[$linkedDo->id] = $dn;
                }
            }
        }

        $doList = $grouped->map(function ($items, $doId) use ($dnByDoId) {
            $first = $items->first();
            $doNo = $first->deliveryOrder?->do_no;
            $tripNo = $first->deliveryOrder?->trip_no;
            $source = $first->source;
            $poNo = $source === 'po' ? ($first->outgoingPoItem?->outgoingPo?->po_no ?? 'N/A') : null;

            $linkedDn = $dnByDoId[$doId] ?? null;

            return (object) [
                'do_id' => $doId ?: null,
                'do_no' => $doNo,
                'trip_no' => $tripNo,
                'source' => $source,
                'po_no' => $poNo,
                'items_count' => $items->count(),
                'qty_plan_total' => $items->sum('qty_plan'),
                'qty_picked_total' => $items->sum('qty_picked'),
                'progress_percent' => $items->sum('qty_plan') > 0
                    ? round(($items->sum('qty_picked') / $items->sum('qty_plan')) * 100)
                    : 0,
                'status' => $this->identifyGroupStatus($items),
                'dn_id' => $linkedDn?->id,
                'dn_no' => $linkedDn?->dn_no,
                'dn_created_at' => $linkedDn?->created_at,
                'rows' => $items->map(function ($p) {
                    // Build expected location info
                    $expectedLocation = $p->part->default_location ?? null;
                    $stockLocations = \App\Models\LocationInventory::where('gci_part_id', $p->gci_part_id)
                        ->where('qty_on_hand', '>', 0)
                        ->orderByDesc('qty_on_hand')
                        ->limit(3)
                        ->get()
                        ->map(fn($loc) => [
                            'code' => $loc->location_code,
                            'qty' => (float) $loc->qty_on_hand,
                        ])->toArray();

                    return (object) [
                        'id' => $p->id,
                        'gci_part_id' => $p->gci_part_id,
                        'part_no' => $p->part->part_no ?? '-',
                        'part_name' => $p->part->part_name ?? '-',
                        'model' => $p->part->model ?? '-',
                        'qty_plan' => (int) $p->qty_plan,
                        'qty_picked' => (int) $p->qty_picked,
                        'qty_remaining' => $p->qty_remaining,
                        'status' => $p->status,
                        'pick_location' => $p->pick_location,
                        'picked_by_name' => $p->picker ? $p->picker->name : null,
                        'stock_on_hand' => $p->part?->inventory?->on_hand ?? 0,
                        'progress_percent' => $p->progress_percent,
                        'source' => $p->source,
                        'delivery_order_id' => $p->delivery_order_id,
                        'expected_location' => $expectedLocation,
                        'stock_locations' => $stockLocations,
                    ];
                })
            ];
        })->sortByDesc('do_no')->values();

        // Stats (always unfiltered for header)
        $allPicks = OutgoingPickingFg::where('delivery_date', $dateStr)->get();
        $stats = (object) [
            'total_do' => $allPicks->pluck('delivery_order_id')->unique()->count(),
            'total_parts' => $allPicks->count(),
            'pending' => $allPicks->where('status', 'pending')->count(),
            'picking' => $allPicks->where('status', 'picking')->count(),
            'completed' => $allPicks->where('status', 'completed')->count(),
            'total_qty' => $allPicks->sum('qty_plan'),
            'total_picked' => $allPicks->sum('qty_picked'),
        ];

        return view('outgoing.picking_fg', compact('selectedDate', 'doList', 'stats', 'filterStatus', 'filterSearch'));
    }

    /**
     * Lightweight JSON status for polling auto-refresh.
     */
    public function statusJson(Request $request)
    {
        $dateStr = $request->query('date', now()->toDateString());

        $picks = OutgoingPickingFg::with(['part', 'picker', 'deliveryOrder'])
            ->where('delivery_date', $dateStr)
            ->get();

        $lastUpdated = $picks->max('updated_at');

        $grouped = $picks->groupBy(fn($p) => $p->delivery_order_id ?? 0);

        $doList = $grouped->map(function ($items, $doId) {
            $first = $items->first();
            return [
                'do_id' => $doId ?: null,
                'do_no' => $first->deliveryOrder?->do_no,
                'trip_no' => $first->deliveryOrder?->trip_no,
                'status' => $this->identifyGroupStatus($items),
                'progress_percent' => $items->sum('qty_plan') > 0
                    ? round(($items->sum('qty_picked') / $items->sum('qty_plan')) * 100) : 0,
                'rows' => $items->map(fn($p) => [
                    'id' => $p->id,
                    'qty_picked' => (int) $p->qty_picked,
                    'qty_remaining' => $p->qty_remaining,
                    'status' => $p->status,
                    'progress_percent' => $p->progress_percent,
                    'picked_by_name' => $p->picker?->name,
                ]),
            ];
        })->values();

        return response()->json([
            'last_updated' => $lastUpdated?->toIso8601String(),
            'stats' => [
                'pending' => $picks->where('status', 'pending')->count(),
                'picking' => $picks->where('status', 'picking')->count(),
                'completed' => $picks->where('status', 'completed')->count(),
                'total_qty' => $picks->sum('qty_plan'),
                'total_picked' => $picks->sum('qty_picked'),
            ],
            'do_list' => $doList,
        ]);
    }

    /**
     * Auto-create a Delivery Note when all picking for a DO is completed.
     */
    private function autoCreateDeliveryNote(int $deliveryOrderId): void
    {
        // Skip if a DN already exists for this DO
        $alreadyExists = DeliveryNote::whereHas('deliveryOrders', function ($q) use ($deliveryOrderId) {
            $q->where('delivery_order_id', $deliveryOrderId);
        })->exists();

        if ($alreadyExists) {
            return;
        }

        $do = DeliveryOrder::find($deliveryOrderId);
        if (!$do) {
            return;
        }

        $completedPicks = OutgoingPickingFg::with('part')
            ->where('delivery_order_id', $deliveryOrderId)
            ->where('status', 'completed')
            ->get();

        if ($completedPicks->isEmpty()) {
            return;
        }

        $dn = DeliveryNote::create([
            'customer_id' => $do->customer_id,
            'delivery_date' => $do->do_date ?? now()->toDateString(),
            'status' => 'ready_to_ship',
            'notes' => 'Auto-created from completed Picking FG (DO: ' . $do->do_no . ') at ' . now()->format('d M Y H:i'),
            'created_by' => Auth::id(),
        ]);

        foreach ($completedPicks as $pick) {
            DnItem::create([
                'dn_id' => $dn->id,
                'gci_part_id' => $pick->gci_part_id,
                'qty' => $pick->qty_picked,
                'outgoing_po_item_id' => $pick->outgoing_po_item_id,
            ]);
        }

        $dn->deliveryOrders()->sync([$deliveryOrderId]);

        $dn->transaction_no = DeliveryNote::generateTransactionNo($dn->delivery_date->toDateString());
        $dn->save();

        // Auto-link production orders for traceability (FIFO)
        $gciPartIds = $completedPicks->pluck('gci_part_id')->unique()->toArray();
        if (!empty($gciPartIds)) {
            $woIds = \App\Models\ProductionOrder::whereIn('gci_part_id', $gciPartIds)
                ->whereNotNull('transaction_no')
                ->orderBy('created_at', 'asc')
                ->limit(20)
                ->pluck('id')
                ->toArray();

            if (!empty($woIds)) {
                $dn->productionOrders()->sync($woIds);
            }
        }
    }

    private function identifyGroupStatus($items)
    {
        $allCompleted = $items->every(fn($i) => $i->status === 'completed');
        if ($allCompleted)
            return 'completed';

        $anyPicking = $items->contains(fn($i) => $i->status === 'picking' || $i->status === 'completed');
        if ($anyPicking)
            return 'picking';

        return 'pending';
    }

    /**
     * Generate/sync picking list from delivery plan for a date.
     */
    public function generate(Request $request)
    {
        return redirect()->route('outgoing.delivery-plan', ['date' => $request->date])
            ->with('info', 'Delivery Order and Picking generation is now centralized in the Delivery Plan view. Please use the "Generate DO" button there.');
    }

    /**
     * Update pick quantity for a specific item.
     */
    public function updatePick(Request $request)
    {
        $request->validate([
            'delivery_date' => 'required|date',
            'gci_part_id' => 'required|integer|exists:gci_parts,id',
            'qty_picked' => 'required|integer|min:0',
            'pick_location' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'source' => 'nullable|string|in:daily_plan,po',
            'delivery_order_id' => 'nullable|integer|exists:delivery_orders,id',
        ]);

        $dateStr = Carbon::parse($request->delivery_date)->toDateString();
        $source = $request->input('source', 'daily_plan');

        $baseQuery = OutgoingPickingFg::where('delivery_date', $dateStr)
            ->where('gci_part_id', $request->gci_part_id);

        if ($request->delivery_order_id) {
            $baseQuery->where('delivery_order_id', $request->delivery_order_id);
        } else {
            $baseQuery->where('source', $source);
        }

        $pickId = (clone $baseQuery)->value('id');
        if (!$pickId) {
            return response()->json(['success' => false, 'message' => 'Picking record not found'], 404);
        }

        [$status, $qtyPicked, $qtyRemaining, $progressPercent] = DB::transaction(function () use ($pickId, $request) {
            $pick = OutgoingPickingFg::whereKey($pickId)->lockForUpdate()->firstOrFail();
            $qtyBefore = (int) $pick->qty_picked;

            $qtyPicked = (int) $request->qty_picked;
            if ($qtyPicked > (int) $pick->qty_plan) {
                $qtyPicked = (int) $pick->qty_plan;
            }

            if ($qtyPicked <= 0) {
                $status = 'pending';
            } elseif ($qtyPicked >= (int) $pick->qty_plan) {
                $status = 'completed';
            } else {
                $status = 'picking';
            }

            $pick->update([
                'qty_picked' => $qtyPicked,
                'status' => $status,
                'pick_location' => $request->pick_location,
                'notes' => $request->notes,
                'picked_by' => Auth::id(),
                'picked_at' => $qtyPicked > 0 ? now() : null,
            ]);

            // DECREMENT WAREHOUSE STOCK
            $delta = (float) $qtyPicked - (float) $qtyBefore;
            if ($delta != 0 && $request->pick_location) {
                \App\Models\LocationInventory::updateStock(
                    $pick->gci_part_id,
                    strtoupper(trim($request->pick_location)),
                    -$delta,
                    null,
                    'Picking FG (Web): ' . ($pick->deliveryOrder?->do_no ?? 'N/A')
                );
            }

            if ($pick->delivery_order_id) {
                $pendingCount = OutgoingPickingFg::where('delivery_order_id', $pick->delivery_order_id)
                    ->where('status', '!=', 'completed')
                    ->count();

                if ($pendingCount === 0) {
                    DeliveryOrder::where('id', $pick->delivery_order_id)->update(['status' => 'completed']);

                    // Auto-create Delivery Note when all picking for this DO is completed
                    $this->autoCreateDeliveryNote($pick->delivery_order_id);
                } else {
                    DeliveryOrder::where('id', $pick->delivery_order_id)
                        ->where('status', 'completed')
                        ->update(['status' => 'picking']);
                }
            }

            return [$status, $qtyPicked, max(0, (int) $pick->qty_plan - $qtyPicked), $pick->progress_percent];
        });

        return response()->json([
            'success' => true,
            'status' => $status,
            'qty_picked' => $qtyPicked,
            'qty_remaining' => $qtyRemaining,
            'progress_percent' => $progressPercent,
        ]);
    }

    /**
     * Clear generated data for a date (Undo Generate).
     */
    public function clear(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $dateStr = Carbon::parse($request->date)->toDateString();

        DB::transaction(function () use ($dateStr) {
            // 1. Delete Picking FGs that are pending
            OutgoingPickingFg::where('delivery_date', $dateStr)
                ->where('status', 'pending')
                ->delete();

            // 2. Delete Delivery Orders that are draft/pending
            $dos = DeliveryOrder::where('do_date', $dateStr)
                ->whereIn('status', ['draft', 'pending'])
                ->get();

            foreach ($dos as $do) {
                $do->items()->delete();
                $do->delete();
            }
        });

        return back()->with('success', 'Generated data cleared successfully.');
    }

    /**
     * Mark all items as completed for a date.
     */
    public function completeAll(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $dateStr = Carbon::parse($request->date)->toDateString();

        OutgoingPickingFg::where('delivery_date', $dateStr)
            ->where('status', '!=', 'completed')
            ->update([
                'status' => 'completed',
                'qty_picked' => DB::raw('qty_plan'),
                'picked_by' => Auth::id(),
                'picked_at' => now(),
            ]);

        // Update all related Delivery Orders to completed
        $doIds = OutgoingPickingFg::where('delivery_date', $dateStr)
            ->whereNotNull('delivery_order_id')
            ->pluck('delivery_order_id')
            ->unique();

        DeliveryOrder::whereIn('id', $doIds)->update(['status' => 'completed']);

        return back()->with('success', 'All items marked as completed.');
    }
}
