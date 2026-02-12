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

        $picks = OutgoingPickingFg::with(['part', 'outgoingPoItem.outgoingPo'])
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
        ]);

        $part = GciPart::where('part_no', $request->part_no)->first();
        if (!$part) {
            return response()->json(['success' => false, 'message' => 'Part not found'], 404);
        }

        // Try to find existing pick (check all sources)
        $pick = OutgoingPickingFg::where('delivery_date', $request->date)
            ->where('gci_part_id', $part->id)
            ->first();

        if (!$pick) {
            // Maybe try to create it if it exists in delivery plan but not synced yet
            $plan = OutgoingDeliveryPlanningLine::where('delivery_date', $request->date)
                ->where('gci_part_id', $part->id)
                ->first();

            if ($plan && $plan->total_trips > 0) {
                $pick = OutgoingPickingFg::create([
                    'delivery_date' => $request->date,
                    'gci_part_id' => $part->id,
                    'source' => $plan->source ?? 'daily_plan',
                    'outgoing_po_item_id' => $plan->outgoing_po_item_id,
                    'qty_plan' => $plan->total_trips,
                    'status' => 'pending',
                    'created_by' => Auth::id(),
                ]);
            } else {
                return response()->json(['success' => false, 'message' => 'Part not in delivery plan for this date'], 404);
            }
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
