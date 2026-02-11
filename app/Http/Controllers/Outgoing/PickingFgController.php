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
        $picks = OutgoingPickingFg::with(['part', 'picker'])
            ->where('delivery_date', $dateStr)
            ->get()
            ->keyBy('gci_part_id');

        // Get delivery plan lines for this date (to show what needs picking)
        $planLines = OutgoingDeliveryPlanningLine::with('part')
            ->where('delivery_date', $dateStr)
            ->get();

        // Get FG inventory for available stock
        $partIds = $planLines->pluck('gci_part_id')->merge($picks->pluck('gci_part_id'))->unique();
        $inventoryMap = GciInventory::whereIn('gci_part_id', $partIds)
            ->get()
            ->keyBy('gci_part_id');

        // Build display rows
        $rows = collect();
        foreach ($planLines as $line) {
            $partId = (int) $line->gci_part_id;
            $part = $line->part;
            if (!$part || $part->classification !== 'FG')
                continue;

            $totalTrips = $line->total_trips;
            if ($totalTrips <= 0)
                continue;

            $pick = $picks->get($partId);
            $inventory = $inventoryMap->get($partId);

            $rows->push((object) [
                'gci_part_id' => $partId,
                'part_no' => $part->part_no ?? '-',
                'part_name' => $part->part_name ?? '-',
                'model' => $part->model ?? '-',
                'qty_plan' => $totalTrips,
                'qty_picked' => $pick ? (int) $pick->qty_picked : 0,
                'qty_remaining' => $pick ? $pick->qty_remaining : $totalTrips,
                'status' => $pick ? $pick->status : 'pending',
                'pick_location' => $pick->pick_location ?? null,
                'picked_by_name' => $pick && $pick->picker ? $pick->picker->name : null,
                'picked_at' => $pick ? $pick->picked_at : null,
                'notes' => $pick->notes ?? null,
                'stock_on_hand' => $inventory ? (int) $inventory->on_hand : 0,
                'pick_id' => $pick?->id,
                'progress_percent' => $pick ? $pick->progress_percent : 0,
            ]);
        }

        // Sort: pending first, then picking, then completed
        $statusOrder = ['pending' => 1, 'picking' => 2, 'completed' => 3];
        $rows = $rows->sortBy(fn($r) => [$statusOrder[$r->status] ?? 4, $r->part_name])
            ->values();

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

        // Get all delivery plan lines for this date
        $planLines = OutgoingDeliveryPlanningLine::where('delivery_date', $dateStr)->get();

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($planLines, $dateStr, &$created, &$updated) {
            foreach ($planLines as $line) {
                $totalTrips = $line->total_trips;
                if ($totalTrips <= 0)
                    continue;

                $pick = OutgoingPickingFg::updateOrCreate(
                    [
                        'delivery_date' => $dateStr,
                        'gci_part_id' => $line->gci_part_id,
                    ],
                    [
                        'qty_plan' => $totalTrips,
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
        ]);

        $dateStr = Carbon::parse($request->delivery_date)->toDateString();

        $pick = OutgoingPickingFg::firstOrCreate(
            [
                'delivery_date' => $dateStr,
                'gci_part_id' => $request->gci_part_id,
            ],
            [
                'qty_plan' => 0,
                'created_by' => Auth::id(),
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
