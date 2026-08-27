<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Incoming\IncomingArrival;
use App\Models\NewSchema\Incoming\IncomingArrivalItem;
use App\Models\NewSchema\Production\ProductionOrder;
use Illuminate\Http\JsonResponse;

class SuggestionController extends Controller
{
    public function arrivals(int $gciPartId): JsonResponse
    {
        // For new schema, we need to find arrivals based on incoming_arrival_items
        // that reference this gci_part_id
        $arrivalIds = IncomingArrivalItem::where('gci_part_id', $gciPartId)
            ->pluck('arrival_id')
            ->unique();

        if ($arrivalIds->isEmpty()) {
            return response()->json([]);
        }

        $arrivals = IncomingArrival::whereIn('id', $arrivalIds)
            ->whereNotNull('transaction_no')
            ->orderBy('created_at', 'asc')
            ->limit(20)
            ->get(['id', 'arrival_no', 'transaction_no', 'invoice_no', 'created_at']);

        return response()->json($arrivals);
    }

    public function productionOrders(int $gciPartId): JsonResponse
    {
        $orders = ProductionOrder::where('gci_part_id', $gciPartId)
            ->whereNotNull('transaction_no')
            ->orderBy('created_at', 'asc')
            ->limit(20)
            ->get(['id', 'production_order_number', 'transaction_no', 'plan_date', 'status']);

        return response()->json($orders);
    }
}


