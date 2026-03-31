<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\GciInventory;
use App\Models\GciPart;
use App\Models\LocationInventory;
use App\Models\LocationInventoryAdjustment;
use App\Models\Part;
use App\Models\Receive;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Exports\InventoryExport;
use App\Exports\GciInventoryExport;
use App\Imports\InventoryImport;
use App\Imports\LocationInventoryImport;
use Maatwebsite\Excel\Facades\Excel;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->query('tab', 'rm');
        if (!in_array($activeTab, ['rm', 'wip', 'fg'])) {
            $activeTab = 'rm';
        }

        $search = trim((string) $request->query('search', ''));
        $status = strtolower(trim((string) $request->query('status', '')));
        $perPage = max(10, min(200, (int) $request->query('per_page', 25)));

        // Map tab to classification
        $classificationMap = ['rm' => 'RM', 'wip' => 'WIP', 'fg' => 'FG'];
        $classification = $classificationMap[$activeTab];

        $locationSummary = LocationInventory::query()
            ->selectRaw('gci_part_id, SUM(qty_on_hand) as on_hand, COUNT(DISTINCT location_code) as location_count')
            ->whereNotNull('gci_part_id')
            ->groupBy('gci_part_id');

        $query = GciPart::query()
            ->with('customers')
            ->where('classification', $classification)
            ->when(in_array($status, ['active', 'inactive'], true), fn($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $s = strtoupper($search);
                $q->where(function ($qp) use ($s) {
                    $qp->where('part_no', 'like', '%' . $s . '%')
                        ->orWhere('part_name', 'like', '%' . $s . '%')
                        ->orWhere('model', 'like', '%' . $s . '%');
                });
            })
            ->leftJoinSub($locationSummary, 'location_summary', function ($join) {
                $join->on('location_summary.gci_part_id', '=', 'gci_parts.id');
            })
            ->leftJoin('gci_inventories', 'gci_inventories.gci_part_id', '=', 'gci_parts.id')
            ->addSelect([
                'gci_parts.*',
                DB::raw('COALESCE(location_summary.on_hand, 0) as on_hand'),
                DB::raw('COALESCE(gci_inventories.on_order, 0) as on_order'),
                DB::raw('COALESCE(location_summary.on_hand, 0) - COALESCE(gci_inventories.on_order, 0) as available_qty'),
                DB::raw('COALESCE(location_summary.location_count, 0) as location_count'),
                'latest_batch_received' => \App\Models\Receive::query()
                    ->select('receives.tag')
                    ->join('arrival_items', 'arrival_items.id', '=', 'receives.arrival_item_id')
                    ->whereColumn('arrival_items.gci_part_id', 'gci_parts.id')
                    ->whereNotNull('receives.tag')
                    ->orderByDesc('receives.created_at')
                    ->limit(1),
                'latest_source_invoice_no' => LocationInventoryAdjustment::query()
                    ->select('source_invoice_no')
                    ->whereColumn('location_inventory_adjustments.gci_part_id', 'gci_parts.id')
                    ->where('transaction_type', 'RECEIVE')
                    ->where('qty_change', '>', 0)
                    ->whereNotNull('source_invoice_no')
                    ->where('source_invoice_no', '!=', '')
                    ->orderByDesc('adjusted_at')
                    ->limit(1),
            ])
            ->orderByDesc('on_hand')
            ->orderBy('gci_parts.part_no');

        $rows = $query->paginate($perPage)->withQueryString();

        // Warehouse locations for default_location dropdown
        $warehouseLocations = Schema::hasTable('warehouse_locations')
            ? WarehouseLocation::where('status', 'ACTIVE')->orderBy('location_code')->pluck('location_code')->all()
            : [];

        // Summary counts per classification tabs
        $summary = GciPart::query()
            ->selectRaw("
                SUM(CASE WHEN classification = 'RM' THEN 1 ELSE 0 END) as rm_count,
                SUM(CASE WHEN classification = 'WIP' THEN 1 ELSE 0 END) as wip_count,
                SUM(CASE WHEN classification = 'FG' THEN 1 ELSE 0 END) as fg_count
            ")
            ->first();

        $kpi = [
            'item_count' => (clone $query)->toBase()->getCountForPagination(),
            'total_on_hand' => (float) (clone $query)->sum(DB::raw('COALESCE(location_summary.on_hand, 0)')),
            'total_on_order' => (float) (clone $query)->sum(DB::raw('COALESCE(gci_inventories.on_order, 0)')),
            'total_available' => (float) (clone $query)->sum(DB::raw('COALESCE(location_summary.on_hand, 0) - COALESCE(gci_inventories.on_order, 0)')),
        ];

        return view('inventory.index', compact(
            'activeTab',
            'rows',
            'search',
            'status',
            'perPage',
            'classification',
            'summary',
            'warehouseLocations',
            'kpi'
        ));
    }

    public function receives(Request $request)
    {
        $partId = $request->query('part_id');
        $qcStatus = $request->query('qc_status');
        $search = trim((string) $request->query('search', ''));

        $parts = Part::query()->orderBy('part_no')->get();

        $receives = Receive::query()
            ->with(['arrivalItem.part', 'arrivalItem.arrival'])
            ->when($partId, fn($q) => $q->whereHas('arrivalItem', fn($qq) => $qq->where('part_id', $partId)))
            ->when($qcStatus, fn($q) => $q->where('qc_status', $qcStatus))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('tag', 'like', '%' . $search . '%')
                        ->orWhereHas('arrivalItem', function ($qqq) use ($search) {
                            $qqq->where('invoice_no', 'like', '%' . $search . '%')
                                ->orWhereHas('part', function ($qqqq) use ($search) {
                                    $qqqq->where('part_no', 'like', '%' . $search . '%')
                                        ->orWhere('part_name_gci', 'like', '%' . $search . '%')
                                        ->orWhere('part_name_vendor', 'like', '%' . $search . '%');
                                });
                        });
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $locationCodes = $receives->getCollection()
            ->pluck('location_code')
            ->filter(fn($code) => is_string($code) && trim($code) !== '')
            ->map(fn($code) => strtoupper(trim($code)))
            ->unique()
            ->values();

        $locationMap = $locationCodes->isEmpty() || !Schema::hasTable('warehouse_locations')
            ? collect()
            : WarehouseLocation::query()
                ->whereIn('location_code', $locationCodes->all())
                ->get()
                ->keyBy('location_code');

        return view('inventory.receives', compact('receives', 'parts', 'partId', 'qcStatus', 'locationMap'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'part_id' => ['required', Rule::exists('parts', 'id'), Rule::unique('inventories', 'part_id')],
            'on_hand' => ['required', 'numeric', 'min:0'],
            'on_order' => ['required', 'numeric', 'min:0'],
            'as_of_date' => ['nullable', 'date'],
        ]);

        Inventory::create($validated);

        return back()->with('success', 'Inventory record created.');
    }

    public function update(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'on_hand' => ['required', 'numeric', 'min:0'],
            'on_order' => ['required', 'numeric', 'min:0'],
            'as_of_date' => ['nullable', 'date'],
        ]);

        $inventory->update($validated);

        return back()->with('success', 'Inventory updated.');
    }

    public function destroy(Inventory $inventory)
    {
        $inventory->delete();

        return back()->with('success', 'Inventory deleted.');
    }

    public function export()
    {
        $filename = 'inventory_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new InventoryExport(), $filename);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new LocationInventoryImport();
        Excel::import($import, $validated['file']);

        $msg = "Import selesai: {$import->imported} rows updated, {$import->skipped} skipped.";
        if ($import->created > 0) {
            $msg .= " {$import->created} new parts created.";
        }

        return back()->with('success', $msg);
    }

    public function searchReceives(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return response()->json([]);
        }

        $receives = Receive::query()
            ->with(['arrivalItem.part', 'arrivalItem.arrival'])
            ->where(function ($q) use ($query) {
                $q->where('tag', 'like', '%' . $query . '%')
                    ->orWhereHas('arrivalItem', function ($qq) use ($query) {
                        $qq->where('invoice_no', 'like', '%' . $query . '%')
                            ->orWhereHas('part', function ($qqq) use ($query) {
                                $qqq->where('part_no', 'like', '%' . $query . '%')
                                    ->orWhere('part_name_gci', 'like', '%' . $query . '%')
                                    ->orWhere('part_name_vendor', 'like', '%' . $query . '%');
                            });
                    });
            })
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($receive) {
                $part = $receive->arrivalItem?->part;
                $arrival = $receive->arrivalItem?->arrival;
                return [
                    'id' => $receive->id,
                    'tag' => $receive->tag,
                    'part_no' => $part?->part_no ?? '-',
                    'part_name' => $part?->part_name_gci ?? $part?->part_name_vendor ?? '-',
                    'invoice_no' => $arrival?->invoice_no ?? '-',
                    'location_code' => $receive->location_code ?? '-',
                ];
            });

        return response()->json($receives);
    }
}
