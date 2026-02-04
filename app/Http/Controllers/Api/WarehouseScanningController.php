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

        $tagNo = trim($request->tag_no);

        // Handle JSON payload from QR scans
        if (str_starts_with($tagNo, '{') && str_ends_with($tagNo, '}')) {
            $decoded = json_decode($tagNo, true);
            if (isset($decoded['tag'])) {
                $tagNo = $decoded['tag'];
            } elseif (isset($decoded['barcode'])) {
                $tagNo = $decoded['barcode'];
            } elseif (isset($decoded['part_no'])) {
                $tagNo = $decoded['part_no'];
            }
        }

        $tagNo = strtoupper(trim($tagNo));

        // Handle composite key (Invoice|Tag)
        $invoiceNo = null;
        if (str_contains($tagNo, '|')) {
            $parts = explode('|', $tagNo, 2);
            $invoiceNo = trim($parts[0]);
            $tagNo = trim($parts[1]);
        }

        $query = Receive::with(['arrivalItem.part', 'arrivalItem.arrival.vendor'])
            ->where('tag', $tagNo);

        if ($invoiceNo && $invoiceNo !== '-') {
            $query->whereHas('arrivalItem.arrival', fn($q) => $q->where('invoice_no', $invoiceNo));
        }

        $receive = $query->latest()->first();

        if (!$receive) {
            return response()->json([
                'message' => 'Tag not found: ' . $tagNo,
            ], 404);
        }

        return response()->json([
            'tag_no' => $receive->tag,
            'part_no' => $receive->arrivalItem->part->part_no,
            'part_name' => $receive->arrivalItem->part->part_name_gci ?? $receive->arrivalItem->part->part_name_vendor,
            'qty' => (float) $receive->qty,
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
            'location_code' => 'required|string',
        ]);

        $tagNo = trim($request->tag_no);
        $locationCode = trim($request->location_code);

        // Handle JSON payload from QR scans for tag
        if (str_starts_with($tagNo, '{') && str_ends_with($tagNo, '}')) {
            $decoded = json_decode($tagNo, true);
            if (isset($decoded['tag'])) {
                $tagNo = $decoded['tag'];
            }
        }

        // Handle JSON payload from QR scans for location
        if (str_starts_with($locationCode, '{') && str_ends_with($locationCode, '}')) {
            $decoded = json_decode($locationCode, true);
            if (isset($decoded['location_code'])) {
                $locationCode = $decoded['location_code'];
            } elseif (isset($decoded['location'])) {
                $locationCode = $decoded['location'];
            } elseif (isset($decoded['code'])) {
                $locationCode = $decoded['code'];
            }
        }

        $tagNo = strtoupper(trim($tagNo));
        $locationCode = strtoupper(trim($locationCode));

        if (!WarehouseLocation::where('location_code', $locationCode)->exists()) {
            return response()->json(['message' => "Location invalid: $locationCode"], 422);
        }

        // Handle composite key (Invoice|Tag)
        $invoiceNo = null;
        if (str_contains($tagNo, '|')) {
            $parts = explode('|', $tagNo, 2);
            $invoiceNo = trim($parts[0]);
            $tagNo = trim($parts[1]);
        }

        $query = Receive::with('arrivalItem')->where('tag', $tagNo);

        if ($invoiceNo && $invoiceNo !== '-') {
            $query->whereHas('arrivalItem.arrival', fn($q) => $q->where('invoice_no', $invoiceNo));
        }

        $receive = $query->latest()->first();

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
            $qty = (float) $receive->qty;

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
