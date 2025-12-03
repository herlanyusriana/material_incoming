<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Receive;
use App\Models\ArrivalItem;

class ReceiveController extends Controller
{
    public function index()
    {
        // Show pending items that need to be received
        $pendingItems = ArrivalItem::with(['part', 'arrival.vendor', 'receives'])
            ->whereHas('arrival')
            ->where('qty_goods', '>', 0)
            ->get()
            ->map(function ($item) {
                $totalReceived = $item->receives->sum('qty');
                $remaining = $item->qty_goods - $totalReceived;
                $item->total_received = $totalReceived;
                $item->remaining_qty = $remaining;
                return $item;
            })
            ->filter(function ($item) {
                return $item->remaining_qty > 0;
            });

        return view('receives.index', compact('pendingItems'));
    }

    public function completed()
    {
        // Show completed receives (old index functionality)
        $receives = Receive::with(['arrivalItem.part', 'arrivalItem.arrival.vendor'])
            ->latest()
            ->paginate(10);

        $statusCounts = Receive::select('qc_status', DB::raw('count(*) as total'))
            ->groupBy('qc_status')
            ->pluck('total', 'qc_status');

        $topVendors = Receive::select(
                'vendors.vendor_name',
                DB::raw('COUNT(receives.id) as total_receives'),
                DB::raw('SUM(receives.qty) as total_qty')
            )
            ->join('arrival_items', 'receives.arrival_item_id', '=', 'arrival_items.id')
            ->join('arrivals', 'arrival_items.arrival_id', '=', 'arrivals.id')
            ->join('vendors', 'arrivals.vendor_id', '=', 'vendors.id')
            ->groupBy('vendors.vendor_name')
            ->orderByDesc('total_receives')
            ->limit(5)
            ->get();

        $summary = [
            'total_receives' => Receive::count(),
            'total_qty' => Receive::sum('qty'),
            'total_weight' => Receive::sum('weight'),
            'today' => Receive::whereDate('created_at', now())->count(),
        ];

        return view('receives.completed', compact('receives', 'statusCounts', 'topVendors', 'summary'));
    }

    public function create(ArrivalItem $arrivalItem)
    {
        $arrivalItem->load(['part.vendor', 'arrival.vendor']);

        return view('receives.create', compact('arrivalItem'));
    }

    public function store(Request $request, ArrivalItem $arrivalItem)
    {
        $validated = $request->validate([
            'tags' => 'required|array|min:1',
            'tags.*.tag' => 'required|string|max:255',
            'tags.*.qty' => 'required|integer|min:1',
            'tags.*.weight' => 'nullable|numeric',
        ]);

        // Create a receive record for each tag with default values
        foreach ($validated['tags'] as $tagData) {
            $arrivalItem->receives()->create([
                'tag' => $tagData['tag'],
                'qty' => $tagData['qty'],
                'weight' => $tagData['weight'] ?? null,
                'ata_date' => now(),
                'qc_status' => 'pass',
                'jo_po_number' => null,
                'location_code' => null,
            ]);
        }

        return redirect()->route('receives.index')->with('success', 'Items received successfully with ' . count($validated['tags']) . ' tag(s).');
    }

    public function printLabel(Receive $receive)
    {
        $receive->load(['arrivalItem.part', 'arrivalItem.arrival.vendor']);
        return view('receives.label', compact('receive'));
    }
}
