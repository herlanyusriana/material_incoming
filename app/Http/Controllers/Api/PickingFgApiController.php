<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\OutgoingPickingFg;
use App\Models\GciPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $partNo = trim((string) $request->part_no);
        $part = GciPart::where('part_no', $partNo)->first();
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

        $partNo = trim((string) $request->part_no);
        $part = GciPart::where('part_no', $partNo)->first();
        if (!$part) {
            return response()->json(['success' => false, 'message' => 'Part not found'], 404);
        }

        $openPicksQuery = OutgoingPickingFg::where('delivery_date', $request->date)
            ->where('gci_part_id', $part->id)
            ->where('status', '!=', 'completed');

        $pickCount = (clone $openPicksQuery)->count();

        if ($pickCount > 1 && !$request->delivery_order_id) {
            return response()->json([
                'success' => false,
                'message' => 'Part exists in multiple DOs. Please specify delivery_order_id.',
                'require_do_selection' => true,
                'options' => (clone $openPicksQuery)->with('deliveryOrder')->get()->map(fn($p) => [
                    'delivery_order_id' => $p->delivery_order_id,
                    'do_no' => $p->deliveryOrder?->do_no,
                    'trip_no' => $p->deliveryOrder?->trip_no,
                    'qty_remaining' => $p->qty_remaining,
                ])->values(),
            ], 422);
        }

        $query = OutgoingPickingFg::where('delivery_date', $request->date)
            ->where('gci_part_id', $part->id);

        if ($request->delivery_order_id) {
            $query->where('delivery_order_id', $request->delivery_order_id);
        } elseif ($pickCount === 1) {
            $singleOpen = (clone $openPicksQuery)->first();
            if ($singleOpen) {
                $query->where('id', $singleOpen->id);
            }
        }

        $result = DB::transaction(function () use ($query, $request, $part) {
            $pick = $query->lockForUpdate()->first();
            if (!$pick) {
                return response()->json(['success' => false, 'message' => 'Picking record not found'], 404);
            }

            $remainingBefore = max(0, (int) $pick->qty_plan - (int) $pick->qty_picked);
            if ($remainingBefore <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This picking task is already completed.',
                ], 422);
            }

            $requestedQty = (int) $request->qty;
            $appliedQty = min($requestedQty, $remainingBefore);
            $newQty = (int) $pick->qty_picked + $appliedQty;

            $pick->update([
                'qty_picked' => $newQty,
                'status' => ($newQty >= (int) $pick->qty_plan) ? 'completed' : 'picking',
                'pick_location' => $request->location ?: $pick->pick_location,
                'picked_by' => Auth::id(),
                'picked_at' => now(),
            ]);

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
                    'requested_qty' => $requestedQty,
                    'applied_qty' => $appliedQty,
                    'rejected_qty' => max(0, $requestedQty - $appliedQty),
                ]
            ]);
        });

        return $result;
    }
}
