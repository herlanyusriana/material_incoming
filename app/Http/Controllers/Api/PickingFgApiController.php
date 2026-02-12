<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OutgoingPickingFg;
use App\Models\OutgoingDeliveryPlanningLine;
use App\Models\GciPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PickingFgApiController extends Controller
{
    /**
     * Get picking list for a date
     */
    public function index(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        $picks = OutgoingPickingFg::with(['part', 'outgoingPoItem.outgoingPo', 'salesOrder'])
            ->where('delivery_date', $date)
            ->get();

        return response()->json([
            'success' => true,
            'date' => $date,
            'data' => $picks->map(function ($p) {
                return [
                    'id' => $p->id,
                    'part_id' => $p->gci_part_id,
                    'part_no' => $p->part->part_no ?? 'N/A',
                    'part_name' => $p->part->part_name ?? 'N/A',
                    'model' => $p->part->model ?? '-',
                    'qty_plan' => (int) $p->qty_plan,
                    'qty_picked' => (int) $p->qty_picked,
                    'qty_remaining' => $p->qty_remaining,
                    'status' => $p->status,
                    'location' => $p->pick_location,
                    'progress' => $p->progress_percent,
                    'source' => $p->source ?? 'daily_plan',
                    'po_no' => $p->outgoingPoItem?->outgoingPo?->po_no,
                    'sales_order_id' => $p->sales_order_id,
                    'so_no' => $p->salesOrder?->so_no,
                    'trip_no' => $p->salesOrder?->trip_no,
                ];
            })
        ]);
    }

    /**
     * Update pick quantity (Scanning part)
     */
    public function updatePick(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'part_no' => 'required|string',
            'qty' => 'required|integer|min:1',
            'location' => 'nullable|string',
            'sales_order_id' => 'nullable|integer|exists:sales_orders,id',
        ]);

        $part = GciPart::where('part_no', $request->part_no)->first();
        if (!$part) {
            return response()->json(['success' => false, 'message' => 'Part not found'], 404);
        }

        // Try to find existing pick
        $query = OutgoingPickingFg::where('delivery_date', $request->date)
            ->where('gci_part_id', $part->id);

        if ($request->sales_order_id) {
            $query->where('sales_order_id', $request->sales_order_id);
        }

        $pick = $query->first();

        if (!$pick) {
            // If sales_order_id was specifically requested but not found for that part
            if ($request->sales_order_id) {
                return response()->json(['success' => false, 'message' => 'Part not found in this Sales Order'], 404);
            }

            // Fallback: maybe try to create it if it exists in delivery plan but not synced yet (deprecated approach now)
            // But we already generate picks during SO generation, so this shouldn't happen much.
            return response()->json(['success' => false, 'message' => 'Picking record not found'], 404);
        }

        $newQty = $pick->qty_picked + $request->qty;

        $pick->update([
            'qty_picked' => $newQty,
            'status' => ($newQty >= $pick->qty_plan) ? 'completed' : 'picking',
            'pick_location' => $request->location ?: $pick->pick_location,
            'picked_by' => Auth::id(),
            'picked_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pick updated successfully',
            'data' => [
                'part_no' => $part->part_no,
                'qty_picked' => $pick->qty_picked,
                'qty_remaining' => $pick->qty_remaining,
                'status' => $pick->status,
                'source' => $pick->source ?? 'daily_plan',
            ]
        ]);
    }
}
