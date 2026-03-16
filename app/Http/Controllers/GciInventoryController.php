<?php

namespace App\Http\Controllers;

use App\Exports\GciInventoryExport;
use App\Models\GciInventory;
use App\Models\GciPart;
use App\Models\LocationInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class GciInventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $classification = strtoupper(trim((string) $request->query('classification', '')));
        $status = strtolower(trim((string) $request->query('status', '')));
        $perPage = (int) $request->query('per_page', 50);
        if ($perPage < 10) {
            $perPage = 10;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }

        $query = GciInventory::query()
            ->with('part.customers')
            ->when($classification !== '', fn($q) => $q->whereHas('part', fn($qp) => $qp->where('classification', $classification)))
            ->when(in_array($status, ['active', 'inactive'], true), fn($q) => $q->whereHas('part', fn($qp) => $qp->where('status', $status)))
            ->when($search !== '', function ($q) use ($search) {
                $s = strtoupper($search);
                $q->whereHas('part', function ($qp) use ($s) {
                    $qp->where('part_no', 'like', '%' . $s . '%')
                        ->orWhere('part_name', 'like', '%' . $s . '%')
                        ->orWhere('model', 'like', '%' . $s . '%');
                });
            })
            ->orderByDesc('on_hand')
            ->orderBy('gci_part_id');

        $rows = $query->paginate($perPage)->withQueryString();

        return view('inventory.gci_inventory', compact('rows', 'search', 'classification', 'status', 'perPage'));
    }

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
        $oldLocation = $part->default_location;

        $part->update(['default_location' => $newLocation]);

        // Sync existing FG stock to LocationInventory when location is set
        $synced = 0;
        if ($newLocation) {
            $synced = $this->syncFgStockToLocation($part->id, $newLocation, $oldLocation);
        }

        return response()->json([
            'success' => true,
            'default_location' => $part->default_location,
            'synced_qty' => $synced,
        ]);
    }

    /**
     * Update FG stock on_hand manually (for testing outgoing flow).
     * Adjusts both gci_inventories and location_inventory.
     */
    public function updateStock(Request $request)
    {
        $request->validate([
            'gci_inventory_id' => 'required|integer|exists:gci_inventories,id',
            'on_hand' => 'required|numeric|min:0',
        ]);

        $inv = GciInventory::findOrFail($request->gci_inventory_id);
        $part = GciPart::find($inv->gci_part_id);

        if (!$part || $part->classification !== 'FG') {
            return response()->json(['success' => false, 'message' => 'Hanya FG yang bisa diedit manual.'], 422);
        }

        $oldQty = (float) $inv->on_hand;
        $newQty = (float) $request->on_hand;
        $diff = $newQty - $oldQty;

        DB::transaction(function () use ($inv, $newQty, $diff, $part) {
            $inv->update([
                'on_hand' => $newQty,
                'as_of_date' => now()->toDateString(),
            ]);

            // Sync to location_inventory if default_location is set
            $loc = $part->default_location;
            if ($loc && abs($diff) > 0.0001) {
                if ($diff > 0) {
                    LocationInventory::updateStock(
                        null,
                        strtoupper(trim($loc)),
                        $diff,
                        null,
                        now()->toDateString(),
                        $part->id,
                        'ADJUSTMENT',
                        'Manual FG edit'
                    );
                } else {
                    LocationInventory::consumeStock(
                        null,
                        strtoupper(trim($loc)),
                        abs($diff),
                        null,
                        $part->id,
                        'ADJUSTMENT',
                        'Manual FG edit'
                    );
                }
            }
        });

        return response()->json([
            'success' => true,
            'on_hand' => $newQty,
        ]);
    }

    /**
     * Sync GCI inventory stock that exists in gci_inventories but not yet in location_inventory
     * into the specified location.
     */
    private function syncFgStockToLocation(int $gciPartId, string $locationCode, ?string $oldLocation): float
    {
        $gciInv = GciInventory::where('gci_part_id', $gciPartId)->first();
        if (!$gciInv || $gciInv->on_hand <= 0) {
            return 0;
        }

        $summaryQty = (float) $gciInv->on_hand;

        // Sum current LocationInventory for this part (all locations)
        $locTotal = (float) LocationInventory::where('gci_part_id', $gciPartId)->sum('qty_on_hand');

        // Only sync the gap (stock not yet in any location)
        $gap = $summaryQty - $locTotal;
        if ($gap <= 0) {
            return 0;
        }

        LocationInventory::updateStock(
            null,
            $locationCode,
            $gap,
            null,
            null,
            $gciPartId,
            'SYNC',
            'Location assignment'
        );

        return $gap;
    }
}

