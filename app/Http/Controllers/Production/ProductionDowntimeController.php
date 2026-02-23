<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\ProductionDowntime;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProductionDowntimeController extends Controller
{
    public function store(Request $request, ProductionOrder $productionOrder)
    {
        $validated = $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after_or_equal:start_time',
            'category' => 'required|string|max:50',
            'notes' => 'nullable|string|max:255',
        ]);

        $duration = null;
        if (!empty($validated['end_time'])) {
            $start = Carbon::createFromFormat('H:i', $validated['start_time']);
            $end = Carbon::createFromFormat('H:i', $validated['end_time']);

            // Handle cross-midnight by adding 1 day if end < start
            if ($end->lessThan($start)) {
                $end->addDay();
            }
            $duration = $start->diffInMinutes($end);
        }

        ProductionDowntime::create([
            'production_order_id' => $productionOrder->id,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'] ?? null,
            'duration_minutes' => $duration,
            'category' => $validated['category'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Downtime record added.');
    }

    public function update(Request $request, ProductionOrder $productionOrder, ProductionDowntime $downtime)
    {
        // This is typically used to "stop" an ongoing downtime.
        $validated = $request->validate([
            'end_time' => 'required|date_format:H:i',
        ]);

        $start = Carbon::createFromTimeString($downtime->start_time);
        $end = Carbon::createFromFormat('H:i', $validated['end_time']);

        if ($end->lessThan($start)) {
            $end->addDay();
        }
        $duration = $start->diffInMinutes($end);

        $downtime->update([
            'end_time' => $validated['end_time'],
            'duration_minutes' => $duration,
        ]);

        return back()->with('success', 'Downtime stopped.');
    }

    public function destroy(ProductionOrder $productionOrder, ProductionDowntime $downtime)
    {
        $downtime->delete();
        return back()->with('success', 'Downtime record deleted.');
    }
}
