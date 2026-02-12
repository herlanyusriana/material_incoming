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

        // Get picking records for this date (all sources, include SO)
        $picks = OutgoingPickingFg::with(['part', 'picker', 'outgoingPoItem.outgoingPo', 'salesOrder'])
            ->where('delivery_date', $dateStr)
            ->get();

        // Build display rows from PICKS directly now, as we generate picks from Delivery Plan
        $rows = $picks->map(function ($p) {
            $part = $p->part;
            $source = $p->source ?? 'daily_plan';
            $poNo = null;
            if ($source === 'po') {
                $poNo = $p->outgoingPoItem?->outgoingPo?->po_no;
            }

            return (object) [
                'id' => $p->id,
                'gci_part_id' => $p->gci_part_id,
                'part_no' => $part->part_no ?? '-',
                'part_name' => $part->part_name ?? '-',
                'model' => $part->model ?? '-',
                'qty_plan' => (int) $p->qty_plan,
                'qty_picked' => (int) $p->qty_picked,
                'qty_remaining' => $p->qty_remaining,
                'status' => $p->status,
                'pick_location' => $p->pick_location,
                'picked_by_name' => $p->picker ? $p->picker->name : null,
                'picked_at' => $p->picked_at,
                'notes' => $p->notes,
                'stock_on_hand' => $p->part?->inventory?->on_hand ?? 0,
                'pick_id' => $p->id,
                'progress_percent' => $p->progress_percent,
                'source' => $source,
                'outgoing_po_item_id' => $p->outgoing_po_item_id,
                'po_no' => $poNo,
                'sales_order_id' => $p->sales_order_id,
                'so_no' => $p->salesOrder?->so_no,
                'trip_no' => $p->salesOrder?->trip_no,
            ];
        });

        // Sort: SO Number, سپس status, kemudian part name
        $statusOrder = ['pending' => 1, 'picking' => 2, 'completed' => 3];
        $rows = $rows->sortBy(fn($r) => [
            $r->so_no ?? 'ZZZ',
            $statusOrder[$r->status] ?? 4,
            $r->part_name
        ])->values();

        // Stats
        $stats = (object) [
            'total' => $rows->count(),
            'pending' => $rows->where('status', 'pending')->count(),
            'picking' => $rows->where('status', 'picking')->count(),
            'completed' => $rows->where('status', 'completed')->count(),
            'total_qty' => $rows->sum('qty_plan'),
            'total_picked' => $rows->sum('qty_picked'),
        ];

        return view('outgoing.picking_fg', compact('selectedDate', 'rows', 'stats'));
    }

    /**
     * Generate/sync picking list from delivery plan for a date.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $dateStr = Carbon::parse($request->date)->toDateString();

        // Get all delivery plan lines for this date (all sources)
        $planLines = OutgoingDeliveryPlanningLine::where('delivery_date', $dateStr)->get();

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($planLines, $dateStr, &$created, &$updated) {
            foreach ($planLines as $line) {
                $totalTrips = $line->total_trips;
                if ($totalTrips <= 0)
                    continue;

                $source = $line->source ?? 'daily_plan';

                $pick = OutgoingPickingFg::updateOrCreate(
                    [
                        'delivery_date' => $dateStr,
                        'gci_part_id' => $line->gci_part_id,
                        'source' => $source,
                    ],
                    [
                        'qty_plan' => $totalTrips,
                        'outgoing_po_item_id' => $line->outgoing_po_item_id,
                        'created_by' => Auth::id(),
                    ]
                );

                if ($pick->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            }
        });

        return back()->with('success', "Picking list generated: {$created} new, {$updated} updated.");
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
