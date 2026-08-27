<?php

namespace App\Http\Controllers;

use App\Exports\GciInventoryExport;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\WarehouseLocation;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
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

        $summary = InventoryLocationStock::query()
            ->selectRaw('gci_part_id, SUM(qty_on_hand) as on_hand, COUNT(DISTINCT location_code) as location_count')
            ->whereNotNull('gci_part_id')
            ->groupBy('gci_part_id');

        $query = GciPart::query()
            ->with('customers')
            ->leftJoinSub($summary, 'stock_summary', fn ($join) => $join->on('stock_summary.gci_part_id', '=', 'gci_parts.id'))
            ->when($classification !== '', fn($q) => $q->where('classification', $classification))
            ->when(in_array($status, ['active', 'inactive'], true), fn($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $s = strtoupper($search);
                $q->where(function ($qp) use ($s) {
                    $qp->where('part_no', 'like', '%' . $s . '%')
                        ->orWhere('part_name', 'like', '%' . $s . '%')
                        ->orWhere('model', 'like', '%' . $s . '%');
                });
            })
            ->select([
                'gci_parts.*',
                DB::raw('COALESCE(stock_summary.on_hand, 0) as on_hand'),
                DB::raw('COALESCE(stock_summary.location_count, 0) as location_count'),
            ])
            ->orderByDesc('on_hand')
            ->orderBy('gci_parts.part_no');

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
