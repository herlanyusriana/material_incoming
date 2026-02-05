<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LocationInventory;
use App\Models\WarehouseLocation;
use App\Models\GciPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryApiController extends Controller
{
    /**
     * Search inventory across locations
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        $location = $request->input('location');

        $results = LocationInventory::with(['gciPart', 'part'])
            ->when($query, function ($q) use ($query) {
                $q->whereHas('gciPart', function ($sq) use ($query) {
                    $sq->where('part_no', 'like', "%{$query}%")
                        ->orWhere('part_name', 'like', "%{$query}%");
                })->orWhereHas('part', function ($sq) use ($query) {
                    $sq->where('part_no', 'like', "%{$query}%");
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
            'part_id' => 'nullable|exists:parts,id',
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
            $sourceQuery = LocationInventory::where('location_code', $from)
                ->where('gci_part_id', $request->gci_part_id);

            if ($request->part_id) {
                $sourceQuery->where('part_id', $request->part_id);
            }
            if ($request->batch_no) {
                $sourceQuery->where('batch_no', $request->batch_no);
            }

            $sourceStock = $sourceQuery->first();

            if (!$sourceStock || $sourceStock->qty_on_hand < $request->qty) {
                throw new \Exception("Insufficient stock in source bin ({$from}). Available: " . ($sourceStock->qty_on_hand ?? 0));
            }

            // 2. Deduct from source
            $sourceStock->decrement('qty_on_hand', $request->qty);
            if ($sourceStock->qty_on_hand <= 0) {
                $sourceStock->delete(); // Or keep at 0, but deleting keeps index clean
            }

            // 3. Add to destination
            LocationInventory::updateStock(
                $request->part_id,
                $to,
                $request->qty,
                $request->batch_no,
                $request->gci_part_id
            );

            // TODO: Log the transfer in an activity log table if needed

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
