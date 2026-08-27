<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryApiController extends Controller
{
    /**
     * Search inventory across locations
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        $location = $request->input('location');

        $results = InventoryLocationStock::with(['gciPart'])
            ->when($query, function ($q) use ($query) {
                $q->whereHas('gciPart', function ($sq) use ($query) {
                    $sq->where('part_no', 'like', "%{$query}%")
                        ->orWhere('part_name', 'like', "%{$query}%");
                });
            })
            ->when($location, function ($q) use ($location) {
                $q->where('location_code', strtoupper($location));
            })
            ->latest('updated_at')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * Transfer stock between bins (Bin to Bin)
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'from_location' => 'required|string',
            'to_location' => 'required|string|exists:warehouse_locations,location_code',
            'gci_part_id' => 'required|exists:gci_parts,id',
            'qty' => 'required|numeric|min:0.0001',
            'batch_no' => 'nullable|string',
        ]);

        $from = strtoupper($request->from_location);
        $to = strtoupper($request->to_location);

        if ($from === $to) {
            return response()->json(['success' => false, 'message' => 'Source and destination locations must be different'], 400);
        }

        try {
            DB::beginTransaction();

            // 1. Check source stock
            $sourceQuery = InventoryLocationStock::where('location_code', $from)
                ->where('gci_part_id', $request->gci_part_id);

            if ($request->batch_no) {
                $sourceQuery->where('batch_no', $request->batch_no);
            }

            $sourceStock = $sourceQuery->first();

            if (!$sourceStock || $sourceStock->qty_on_hand < $request->qty) {
                throw new \Exception("Insufficient stock in source bin ({$from}). Available: " . ($sourceStock->qty_on_hand ?? 0));
            }

            // 2. Deduct from source
            InventoryLocationStock::consumeStock(
                gciPartId: (int) $request->gci_part_id,
                locationCode: $from,
                qty: (float) $request->qty,
                batchNo: $request->batch_no,
                transactionType: 'TRANSFER',
                sourceReference: "API BIN:{$from}->{$to}"
            );

            // 3. Add to destination
            InventoryLocationStock::updateStock(
                gciPartId: (int) $request->gci_part_id,
                locationCode: $to,
                qty: (float) $request->qty,
                batchNo: $request->batch_no,
                transactionType: 'TRANSFER',
                sourceReference: "API BIN:{$from}->{$to}"
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfer successful from ' . $from . ' to ' . $to
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Transfer failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
