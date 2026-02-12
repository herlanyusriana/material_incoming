<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Models\OutgoingPickingFg;
use App\Models\OutgoingDeliveryPlanningLine;
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

        // Get picking records for this date
        $picks = OutgoingPickingFg::with(['part', 'picker', 'outgoingPoItem.outgoingPo', 'salesOrder'])
            ->where('delivery_date', $dateStr)
            ->get();

        // Group by Sales Order
        $grouped = $picks->groupBy(function ($p) {
            return $p->sales_order_id ?? 0;
        });

        $soList = $grouped->map(function ($items, $soId) {
            $first = $items->first();
            $soNo = $first->salesOrder?->so_no;
            $tripNo = $first->salesOrder?->trip_no;
            $source = $first->source;
            $poNo = $source === 'po' ? ($first->outgoingPoItem?->outgoingPo?->po_no ?? 'N/A') : null;

            return (object) [
                'so_id' => $soId ?: null,
                'so_no' => $soNo,
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
                        'sales_order_id' => $p->sales_order_id,
                    ];
                })
            ];
        })->sortByDesc('so_no')->values();

        // Stats
        $stats = (object) [
            'total_so' => $soList->count(),
            'total_parts' => $picks->count(),
            'pending' => $picks->where('status', 'pending')->count(),
            'picking' => $picks->where('status', 'picking')->count(),
            'completed' => $picks->where('status', 'completed')->count(),
            'total_qty' => $picks->sum('qty_plan'),
            'total_picked' => $picks->sum('qty_picked'),
        ];

        return view('outgoing.picking_fg', compact('selectedDate', 'soList', 'stats'));
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
        // Redirect to Delivery Plan with a message because SO generation is centralized there
        return redirect()->route('outgoing.index', ['date' => $request->date])
            ->with('info', 'Sales Order and Picking generation is now centralized in the Delivery Plan view. Please use the "Generate SO" button there.');
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
            'sales_order_id' => 'nullable|integer|exists:sales_orders,id',
        ]);

        $dateStr = Carbon::parse($request->delivery_date)->toDateString();
        $source = $request->input('source', 'daily_plan');

        $query = OutgoingPickingFg::where('delivery_date', $dateStr)
            ->where('gci_part_id', $request->gci_part_id);

        if ($request->sales_order_id) {
            $query->where('sales_order_id', $request->sales_order_id);
        } else {
            $query->where('source', $source);
        }

        $pick = $query->firstOrCreate(
            [], // Attributes already in query
            [
                'qty_plan' => 0,
                'created_by' => Auth::id(),
                'sales_order_id' => $request->sales_order_id,
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

        return response()->json([
            'success' => true,
            'status' => $status,
            'qty_picked' => $qtyPicked,
            'qty_remaining' => max(0, $pick->qty_plan - $qtyPicked),
            'progress_percent' => $pick->progress_percent,
        ]);
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

        return back()->with('success', 'All items marked as completed.');
    }
}
