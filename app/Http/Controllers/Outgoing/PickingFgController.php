<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Models\OutgoingPickingFg;
use App\Models\OutgoingDeliveryPlanningLine;
use App\Models\DeliveryOrder;
use App\Models\GciPart;
use App\Models\GciInventory;
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

        $doList = $grouped->map(function ($items, $doId) {
            $first = $items->first();
            $doNo = $first->deliveryOrder?->do_no;
            $tripNo = $first->deliveryOrder?->trip_no;
            $source = $first->source;
            $poNo = $source === 'po' ? ($first->outgoingPoItem?->outgoingPo?->po_no ?? 'N/A') : null;

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
                'rows' => $items->map(function ($p) {
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

        $query = OutgoingPickingFg::where('delivery_date', $dateStr)
            ->where('gci_part_id', $request->gci_part_id);

        if ($request->delivery_order_id) {
            $query->where('delivery_order_id', $request->delivery_order_id);
        } else {
            $query->where('source', $source);
        }

        $pick = $query->firstOrCreate(
            [],
            [
                'qty_plan' => 0,
                'created_by' => Auth::id(),
                'delivery_order_id' => $request->delivery_order_id,
                'source' => $source,
            ]
        );

        // Determine status based on qty
        $qtyPicked = (int) $request->qty_picked;
        if ($qtyPicked <= 0) {
            $status = 'pending';
        } elseif ($qtyPicked >= $pick->qty_plan) {
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

        // Check if all items for this DO are completed
        if ($pick->delivery_order_id) {
            $pendingCount = OutgoingPickingFg::where('delivery_order_id', $pick->delivery_order_id)
                ->where('status', '!=', 'completed')
                ->count();

            if ($pendingCount === 0) {
                DeliveryOrder::where('id', $pick->delivery_order_id)->update(['status' => 'completed']);
            } else {
                DeliveryOrder::where('id', $pick->delivery_order_id)
                    ->where('status', 'completed')
                    ->update(['status' => 'picking']);
            }
        }

        return response()->json([
            'success' => true,
            'status' => $status,
            'qty_picked' => $qtyPicked,
            'qty_remaining' => max(0, $pick->qty_plan - $qtyPicked),
            'progress_percent' => $pick->progress_percent,
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
