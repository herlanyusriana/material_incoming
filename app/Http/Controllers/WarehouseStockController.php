<?php

namespace App\Http\Controllers;

use App\Models\GciInventory;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\WarehouseLocation;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Inventory\InventoryStockMovement;
use App\Imports\LocationInventoryImport;
use App\Exports\LocationStockExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseStockController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $location = strtoupper(trim((string) $request->query('location', '')));
        $classification = strtoupper(trim((string) $request->query('classification', '')));
        $onlyPositive = (bool) $request->boolean('only_positive', true);
        $perPage = (int) $request->query('per_page', 50);
        if ($perPage < 10) {
            $perPage = 10;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }

        $query = InventoryLocationStock::query()
            ->with(['gciPart'])
            ->when($onlyPositive, fn ($q) => $q->where('qty_on_hand', '>', 0))
            ->when($location !== '', fn ($q) => $q->where('location_code', $location))
            ->when(in_array($classification, ['RM', 'WIP', 'FG'], true), fn ($q) => $q->whereHas('gciPart', fn ($qg) => $qg->where('classification', $classification)))
            ->when($search !== '', function ($q) use ($search) {
                $s = strtoupper($search);
                $q->where(function ($qq) use ($s) {
                    $qq->whereHas('gciPart', function ($qg) use ($s) {
                        $qg->where('part_no', 'like', '%' . $s . '%')
                            ->orWhere('part_name', 'like', '%' . $s . '%');
                    })->orWhere('location_code', 'like', '%' . $s . '%');
                });
            })
            ->orderBy('location_code')
            ->orderBy('gci_part_id');

        $records = $query->paginate($perPage)->withQueryString();

        $totalsByLocation = InventoryLocationStock::query()
            ->selectRaw('location_code, SUM(qty_on_hand) as total_qty')
            ->when($onlyPositive, fn ($q) => $q->where('qty_on_hand', '>', 0))
            ->when($location !== '', fn ($q) => $q->where('location_code', $location))
            ->groupBy('location_code')
            ->orderBy('location_code')
            ->pluck('total_qty', 'location_code')
            ->all();

        $grandTotal = array_sum(array_map('floatval', $totalsByLocation));

        return view('warehouse.stock.index', compact(
            'records',
            'search',
            'location',
            'classification',
            'onlyPositive',
            'perPage',
            'totalsByLocation',
            'grandTotal',
        ));
    }

    public function reconcile(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $onlyDiff = (bool) $request->boolean('only_diff', true);
        $perPage = (int) $request->query('per_page', 50);
        if ($perPage < 10) {
            $perPage = 10;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }

        // Aggregate stock per gci_part per location.
        $locationSums = InventoryLocationStock::query()
            ->whereNotNull('gci_part_id')
            ->selectRaw('gci_part_id, SUM(qty_on_hand) as loc_qty')
            ->groupBy('gci_part_id')
            ->pluck('loc_qty', 'gci_part_id')
            ->map(fn ($qty) => (float) $qty)
            ->all();

        // Aggregate on_hand from GciInventory per gci_part_id
        $gciInventorySums = GciInventory::query()
            ->selectRaw('gci_part_id, SUM(on_hand) as total_on_hand')
            ->groupBy('gci_part_id')
            ->pluck('total_on_hand', 'gci_part_id')
            ->map(fn ($qty) => (float) $qty)
            ->all();

        $rows = collect();

        foreach (GciPart::with(['customers'])->cursor() as $gciPart) {
            $locQty = (float) ($locationSums[$gciPart->id] ?? 0);
            $onHand = (float) ($gciInventorySums[$gciPart->id] ?? 0);
            $diffQty = $onHand - $locQty;

            $rows->push((object) [
                'reconcile_key' => 'gci:' . $gciPart->id,
                'summary_type' => 'gci_inventory',
                'gciPart' => $gciPart,
                'on_hand' => $onHand,
                'loc_qty' => $locQty,
                'diff_qty' => $diffQty,
            ]);
        }

        if ($search !== '') {
            $needle = strtoupper($search);
            $rows = $rows->filter(function ($row) use ($needle) {
                $partNo = strtoupper((string) ($row->gciPart?->part_no ?? ''));
                $partName = strtoupper((string) ($row->gciPart?->part_name ?? ''));
                return str_contains($partNo, $needle) || str_contains($partName, $needle);
            });
        }

        if ($onlyDiff) {
            $rows = $rows->filter(fn ($row) => round((float) $row->diff_qty, 4) !== 0.0);
        }

        $rows = $rows
            ->sortBy([
                fn ($row) => -abs((float) $row->diff_qty),
                fn ($row) => strtoupper((string) ($row->gciPart?->part_no ?? '')),
            ])
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $rows->forPage($page, $perPage)->values();
        $rows = new LengthAwarePaginator(
            $items,
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('warehouse.stock.reconcile', compact('rows', 'search', 'onlyDiff', 'perPage'));
    }

    public function importLocationStock(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new LocationInventoryImport();
        Excel::import($import, $request->file('file'));

        return back()->with('success', "Import selesai: {$import->imported} rows imported, {$import->skipped} skipped.");
    }

    public function export(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $location = strtoupper(trim((string) $request->query('location', '')));
        $classification = strtoupper(trim((string) $request->query('classification', '')));
        $onlyPositive = (bool) $request->boolean('only_positive', true);

        $filename = 'stock_location_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new LocationStockExport($search, $location, $classification, $onlyPositive),
            $filename
        );
    }
}
