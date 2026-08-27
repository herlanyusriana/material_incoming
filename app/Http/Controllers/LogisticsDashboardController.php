<?php

namespace App\Http\Controllers;

use App\Models\NewSchema\Incoming\IncomingArrival;
use App\Models\NewSchema\Incoming\IncomingReceive;
use App\Models\NewSchema\Inventory\InventoryBinTransfer;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogisticsDashboardController extends Controller
{
    public function index(Request $request)
    {
        $pendingArrivals = IncomingArrival::with(['vendor', 'items.receives'])
            ->latest()
            ->limit(20)
            ->get()
            ->map(function ($arrival) {
                $remaining = $arrival->items->sum(function ($item) {
                    $received = $item->receives->sum('qty');
                    return max(0, (float) $item->qty_goods - (float) $received);
                });
                $arrival->remaining_qty = $remaining;
                return $arrival;
            })
            ->filter(fn ($arrival) => (float) ($arrival->remaining_qty ?? 0) > 0)
            ->values();

        $qcCounts = IncomingReceive::query()
            ->select('qc_status', DB::raw('COUNT(*) as total'))
            ->groupBy('qc_status')
            ->pluck('total', 'qc_status')
            ->all();

        $recentReceives = IncomingReceive::with(['arrivalItem.gciPart', 'arrivalItem.arrival.vendor'])
            ->latest()
            ->limit(10)
            ->get();

        $topLocations = InventoryLocationStock::query()
            ->selectRaw('location_code, SUM(qty_on_hand) as total_qty')
            ->where('qty_on_hand', '>', 0)
            ->groupBy('location_code')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        $recentBinTransfers = InventoryBinTransfer::with(['gciPart', 'createdBy'])
            ->latest()
            ->limit(10)
            ->get();

        return view('logistics.dashboard', compact(
            'pendingArrivals',
            'qcCounts',
            'recentReceives',
            'topLocations',
            'recentBinTransfers',
        ));
    }
}
