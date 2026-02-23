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
    public function index(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        $picks = OutgoingPickingFg::with(['part', 'outgoingPoItem.outgoingPo', 'deliveryOrder'])
            ->where('delivery_date', $date)
            ->get();

        $lastUpdated = $picks->max('updated_at');

        return response()->json([
            'success' => true,
            'date' => $date,
            'last_updated' => $lastUpdated?->toIso8601String(),
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
                    'delivery_order_id' => $p->delivery_order_id,
                    'do_no' => $p->deliveryOrder?->do_no,
                    'trip_no' => $p->deliveryOrder?->trip_no,
                    'updated_at' => $p->updated_at?->toIso8601String(),
                ];
            })
        ]);
    }

    public function status(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        $picks = OutgoingPickingFg::where('delivery_date', $date);

        return response()->json([
            'success' => true,
            'date' => $date,
            'last_updated' => (clone $picks)->max('updated_at'),
            'total' => (clone $picks)->count(),
            'pending' => (clone $picks)->where('status', 'pending')->count(),
            'picking' => (clone $picks)->where('status', 'picking')->count(),
            'completed' => (clone $picks)->where('status', 'completed')->count(),
            'qty_plan' => (clone $picks)->sum('qty_plan'),
            'qty_picked' => (clone $picks)->sum('qty_picked'),
        ]);
    }

    public function lookupPart(Request $request)
    {
        $request->validate(['part_no' => 'required|string', 'date' => 'required|date']);

        $part = GciPart::where('part_no', $request->part_no)->first();
        if (!$part) {
            return response()->json(['success' => false, 'message' => 'Part not found'], 404);
        }

        $picks = OutgoingPickingFg::with('deliveryOrder')
            ->where('delivery_date', $request->date)
            ->where('gci_part_id', $part->id)
            ->where('status', '!=', 'completed')
            ->get();

        if ($picks->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No pending picking for this part on this date',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'part' => [
                'id' => $part->id,
                'part_no' => $part->part_no,
                'part_name' => $part->part_name,
                'model' => $part->model ?? '-',
            ],
            'picks' => $picks->map(fn($p) => [
                'id' => $p->id,
                'delivery_order_id' => $p->delivery_order_id,
                'do_no' => $p->deliveryOrder?->do_no ?? 'N/A',
                'trip_no' => $p->deliveryOrder?->trip_no,
                'qty_plan' => (int) $p->qty_plan,
                'qty_picked' => (int) $p->qty_picked,
                'qty_remaining' => $p->qty_remaining,
                'status' => $p->status,
            ]),
        ]);
    }

    public function updatePick(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'part_no' => 'required|string',
            'qty' => 'required|integer|min:1',
            'location' => 'nullable|string',
            'delivery_order_id' => 'nullable|integer|exists:delivery_orders,id',
        ]);

        $part = GciPart::where('part_no', $request->part_no)->first();
        if (!$part) {
            return response()->json(['success' => false, 'message' => 'Part not found'], 404);
        }

        $pickCount = OutgoingPickingFg::where('delivery_date', $request->date)
            ->where('gci_part_id', $part->id)
            ->count();

        if ($pickCount > 1 && !$request->delivery_order_id) {
            return response()->json([
                'success' => false,
                'message' => 'Part exists in multiple DOs. Please specify delivery_order_id.',
                'require_do_selection' => true,
            ], 422);
        }

        $query = OutgoingPickingFg::where('delivery_date', $request->date)
            ->where('gci_part_id', $part->id);

        if ($request->delivery_order_id) {
            $query->where('delivery_order_id', $request->delivery_order_id);
        }

        $pick = $query->first();

        if (!$pick) {
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
            'message' => 'Pick updated',
            'data' => [
                'id' => $pick->id,
                'part_no' => $part->part_no,
                'part_name' => $part->part_name,
                'qty_picked' => (int) $pick->qty_picked,
                'qty_plan' => (int) $pick->qty_plan,
                'qty_remaining' => $pick->qty_remaining,
                'status' => $pick->status,
                'progress' => $pick->progress_percent,
                'do_no' => $pick->deliveryOrder?->do_no,
            ]
        ]);
    }
}
