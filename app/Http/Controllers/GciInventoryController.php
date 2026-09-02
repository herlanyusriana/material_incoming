<?php

namespace App\Http\Controllers;

use App\Exports\GciInventoryExport;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class GciInventoryController extends Controller
{
    public function export(Request $request)
    {
        $classification = strtoupper(trim((string) $request->query('classification', '')));
        $status = strtolower(trim((string) $request->query('status', '')));
        $search = trim((string) $request->query('search', ''));

        $suffix = $classification !== '' ? '_' . strtolower($classification) : '';
        $filename = "gci_inventory{$suffix}_" . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new GciInventoryExport($classification, $status, $search),
            $filename
        );
    }

    public function updateLocation(Request $request)
    {
        $request->validate([
            'gci_part_id' => 'required|integer|exists:gci_parts,id',
            'default_location' => 'nullable|string|max:50',
        ]);

        $part = GciPart::findOrFail($request->gci_part_id);
        $newLocation = $request->default_location ? strtoupper(trim($request->default_location)) : null;

        $part->update(['default_location' => $newLocation]);

        return response()->json([
            'success' => true,
            'default_location' => $part->default_location,
        ]);
    }

    /**
     * Update FG stock on_hand manually (for testing outgoing flow).
     * Adjusts inventory_location_stock for the part's default location.
     */
    public function updateStock(Request $request)
    {
        $request->validate([
            'gci_part_id' => 'required|integer|exists:gci_parts,id',
            'on_hand' => 'required|numeric|min:0',
        ]);

        $part = GciPart::findOrFail($request->gci_part_id);

        if ($part->classification !== 'FG') {
            return response()->json(['success' => false, 'message' => 'Hanya FG yang bisa diedit manual.'], 422);
        }

        $locationCode = $part->default_location;
        if (!$locationCode) {
            return response()->json(['success' => false, 'message' => 'Part tidak punya default location.'], 422);
        }

        $locationCode = strtoupper(trim($locationCode));

        $current = InventoryLocationStock::where('gci_part_id', $part->id)
            ->where('location_code', $locationCode)
            ->first();
        $oldQty = $current ? (float) $current->qty_on_hand : 0.0;
        $newQty = (float) $request->on_hand;
        $diff = $newQty - $oldQty;

        if (abs($diff) < 0.0001) {
            return response()->json(['success' => true, 'on_hand' => $newQty]);
        }

        InventoryLocationStock::updateStock(
            $part->id,
            $locationCode,
            $diff,
            null,
            null,
            'ADJUSTMENT',
            'Manual FG edit',
            null,
            null,
            null,
            null,
            null,
            null
        );

        return response()->json([
            'success' => true,
            'on_hand' => $newQty,
        ]);
    }
}
