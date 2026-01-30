<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Receive;
use App\Models\WarehouseLocation;
use App\Models\LocationInventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseScanningController extends Controller
{
    /**
     * Get tag info by tag_no
     */
    public function getTagInfo(Request $request)
    {
        $request->validate([
            'tag_no' => 'required|string',
        ]);

        $tagNo = strtoupper(trim($request->tag_no));

        $receive = Receive::with(['arrivalItem.part', 'arrivalItem.arrival.vendor'])
            ->where('tag', $tagNo)
            ->first();

        if (!$receive) {
            return response()->json([
                'message' => 'Tag not found: ' . $tagNo,
            ], 404);
        }

        return response()->json([
            'tag_no' => $receive->tag,
            'part_no' => $receive->arrivalItem->part->part_no,
            'part_name' => $receive->arrivalItem->part->part_name_gci ?? $receive->arrivalItem->part->part_name_vendor,
            'qty' => (float)$receive->qty,
            'uom' => $receive->qty_unit,
            'current_location' => $receive->location_code,
            'vendor' => $receive->arrivalItem->arrival->vendor->vendor_name,
            'receive_date' => $receive->ata_date?->toDateString(),
        ]);
    }

    /**
     * Perform putaway (assign tag to location)
     */
    public function putaway(Request $request)
    {
        $request->validate([
            'tag_no' => 'required|string',
            'location_code' => 'required|string|exists:warehouse_locations,location_code',
        ]);

        $tagNo = strtoupper(trim($request->tag_no));
        $locationCode = strtoupper(trim($request->location_code));

        $receive = Receive::where('tag', $tagNo)->first();

        if (!$receive) {
            return response()->json(['message' => 'Tag not found'], 404);
        }

        if ($receive->qc_status !== 'pass') {
            return response()->json(['message' => 'Cannot putaway non-PASS tag'], 422);
        }

        $oldLocation = $receive->location_code;
        if ($oldLocation === $locationCode) {
            return response()->json(['message' => 'Tag is already at this location'], 200);
        }

        try {
            DB::beginTransaction();

            // 1. Update location in receives table
            $receive->update(['location_code' => $locationCode]);

            // 2. Adjust LocationInventory
            $partId = $receive->arrivalItem->part_id;
            $qty = (float)$receive->qty;
            
            // If it was already at another warehouse location, subtract from there
            if ($oldLocation) {
                try {
                    LocationInventory::updateStock($partId, $oldLocation, -$qty);
                } catch (\Exception $e) {
                    Log::warning("Could not subtract stock from old location {$oldLocation} for tag {$tagNo}: " . $e->getMessage());
                }
            }

            // Add to new location
            LocationInventory::updateStock($partId, $locationCode, $qty);

            DB::commit();

            return response()->json([
                'message' => 'Putaway successful',
                'tag_no' => $tagNo,
                'from' => $oldLocation,
                'to' => $locationCode,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Putaway failed for tag {$tagNo}: " . $e->getMessage());
            return response()->json(['message' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get list of active locations for dropdown/suggestions
     */
    public function getLocations()
    {
        $locations = WarehouseLocation::where('status', 'ACTIVE')
            ->orderBy('location_code')
            ->get(['location_code', 'class', 'zone']);

        return response()->json($locations);
    }
}
