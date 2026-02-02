<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockOpnameSession;
use App\Models\StockOpnameItem;
use App\Models\GciPart;
use App\Models\WarehouseLocation;
use App\Models\FgInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockOpnameApiController extends Controller
{
    /**
     * Get active SO sessions
     */
    public function activeSessions()
    {
        $sessions = StockOpnameSession::open()
            ->select('id', 'session_no', 'name', 'start_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Get Part info by QR scan
     */
    public function scanPart(Request $request)
    {
        $request->validate(['barcode' => 'required|string']);

        $barcode = trim($request->barcode);

        // Handle JSON payload from QR scans
        if (str_starts_with($barcode, '{') && str_ends_with($barcode, '}')) {
            $decoded = json_decode($barcode, true);
            if (isset($decoded['gci_part_id'])) {
                $part = GciPart::find($decoded['gci_part_id']);
            } elseif (isset($decoded['part_no'])) {
                $barcode = $decoded['part_no'];
            } elseif (isset($decoded['tag'])) {
                // If they scanned a tag, find the part associated with that tag
                $receive = \App\Models\Receive::with('arrivalItem.part')->where('tag', $decoded['tag'])->first();
                if ($receive && $receive->arrivalItem && $receive->arrivalItem->part) {
                    // Try to find matching GciPart by part_no
                    $part = GciPart::where('part_no', $receive->arrivalItem->part->part_no)->first();
                }
            } elseif (isset($decoded['barcode'])) {
                $barcode = $decoded['barcode'];
            }
        }

        if (!isset($part)) {
            $part = GciPart::where('barcode', $barcode)
                ->orWhere('part_no', $barcode)
                ->first();
        }

        if (!$part) {
            return response()->json([
                'success' => false,
                'message' => 'Part not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $part->id,
                'part_no' => $part->part_no,
                'part_name' => $part->part_name,
                'model' => $part->model,
                'classification' => $part->classification,
            ]
        ]);
    }

    /**
     * Get Location info by QR scan
     */
    public function scanLocation(Request $request)
    {
        $request->validate(['location_code' => 'required|string']);

        $locationCode = trim($request->location_code);

        // Handle JSON payload from QR scans
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

        $location = WarehouseLocation::where('location_code', $locationCode)->first();

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Location not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $location
        ]);
    }

    /**
     * Submit counting result
     */
    public function submitCount(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:stock_opname_sessions,id',
            'location_code' => 'required|string',
            'gci_part_id' => 'required|exists:gci_parts,id',
            'qty' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $session = StockOpnameSession::find($request->session_id);
        if ($session->status !== 'OPEN') {
            return response()->json(['success' => false, 'message' => 'Session is not OPEN'], 400);
        }

        // Get current system qty (from FgInventory for now)
        $systemQty = FgInventory::where('gci_part_id', $request->gci_part_id)
            ->value('qty_on_hand') ?? 0;

        // Use updateOrCreate if we want to overwrite existing count for the same item in the same location
        $item = StockOpnameItem::updateOrCreate(
            [
                'session_id' => $request->session_id,
                'location_code' => strtoupper($request->location_code),
                'gci_part_id' => $request->gci_part_id,
            ],
            [
                'system_qty' => $systemQty,
                'counted_qty' => $request->qty,
                'counted_by' => Auth::id(),
                'counted_at' => now(),
                'notes' => $request->notes,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Result saved',
            'data' => $item
        ]);
    }
}
