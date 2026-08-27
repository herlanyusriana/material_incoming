<?php

namespace App\Http\Controllers;

use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Inventory\InventoryStockMovement;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\WarehouseLocation;
use App\Models\NewSchema\Incoming\IncomingReceive as Receive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Exports\InventoryExport;
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

        $locationSummary = InventoryLocationStock::query()
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
            // ->leftJoin('gci_inventories', 'gci_inventories.gci_part_id', '=', 'gci_parts.id') // Deprecated in new schema
            ->addSelect([
                'gci_parts.*',
                DB::raw('COALESCE(location_summary.on_hand, 0) as on_hand'),
                DB::raw('0 as on_order'), // TODO: compute from inventory_stock_movements
                DB::raw('COALESCE(location_summary.on_hand, 0) - 0 as available_qty'), // on_order currently 0
                DB::raw('COALESCE(location_summary.location_count, 0) as location_count'),
                'latest_batch_received' => Receive::query()
                    ->select('incoming_receives.tag')
                    ->join('incoming_arrival_items', 'incoming_arrival_items.id', '=', 'incoming_receives.arrival_item_id')
                    ->whereColumn('incoming_arrival_items.gci_part_id', 'gci_parts.id')
                    ->whereNotNull('incoming_receives.tag')
                    ->orderByDesc('incoming_receives.created_at')
                    ->limit(1),
                'latest_receive_invoice_no' => Receive::query()
                    ->selectRaw("COALESCE(NULLIF(incoming_receives.invoice_no, ''), NULLIF(incoming_arrivals.invoice_no, ''))")
                    ->join('incoming_arrival_items', 'incoming_arrival_items.id', '=', 'incoming_receives.arrival_item_id')
                    ->leftJoin('incoming_arrivals', 'incoming_arrivals.id', '=', 'incoming_arrival_items.arrival_id')
                    ->whereColumn('incoming_arrival_items.gci_part_id', 'gci_parts.id')
                    ->where(function ($q) {
                        $q->whereNotNull('incoming_receives.invoice_no')
                            ->where('incoming_receives.invoice_no', '!=', '')
                            ->orWhere(function ($qq) {
                                $qq->whereNotNull('incoming_arrivals.invoice_no')
                                    ->where('incoming_arrivals.invoice_no', '!=', '');
                            });
                    })
                    ->orderByDesc('incoming_receives.created_at')
                    ->limit(1),
                'latest_source_invoice_no' => InventoryStockMovement::query()
                    ->select('incoming_receives.invoice_no')
                    ->join('incoming_receives', function ($join) {
                        $join->on('incoming_receives.gci_part_id', '=', 'inventory_stock_movements.gci_part_id')
                             ->on('incoming_receives.tag', '=', 'inventory_stock_movements.tag_number');
                    })
                    ->whereColumn('inventory_stock_movements.gci_part_id', 'gci_parts.id')
                    ->where('inventory_stock_movements.movement_type', 'RECEIVE')
                    ->where('inventory_stock_movements.qty', '>', 0)
                    ->whereNull('incoming_receives.deleted_at')
                    ->whereNotNull('incoming_receives.invoice_no')
                    ->where('incoming_receives.invoice_no', '!=', '')
                    ->orderByDesc('inventory_stock_movements.moved_at')
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
            'total_on_order' => 0.0,
            'total_available' => (float) (clone $query)->sum(DB::raw('COALESCE(location_summary.on_hand, 0)')),
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

        $parts = GciPart::query()->orderBy('part_no')->get();

        $receives = Receive::query()
            ->with(['arrivalItem.gciPart', 'arrivalItem.vendorPart', 'arrivalItem.arrival'])
            ->when($partId, fn($q) => $q->whereHas('arrivalItem', fn($qq) => $qq->where('gci_part_id', $partId)))
            ->when($qcStatus, fn($q) => $q->where('qc_status', $qcStatus))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('tag', 'like', '%' . $search . '%')
                        ->orWhereHas('arrivalItem', function ($qqq) use ($search) {
                            $qqq->whereHas('arrival', fn ($a) => $a->where('invoice_no', 'like', '%' . $search . '%'))
                                ->orWhereHas('gciPart', function ($qqqq) use ($search) {
                                    $qqqq->where('part_no', 'like', '%' . $search . '%')
                                        ->orWhere('part_name', 'like', '%' . $search . '%');
                                })
                                ->orWhereHas('vendorPart', function ($qqqq) use ($search) {
                                    $qqqq->where('vendor_part_no', 'like', '%' . $search . '%')
                                        ->orWhere('vendor_part_name', 'like', '%' . $search . '%');
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

    // public function store(Request $request)
    // {
    //     // Deprecated: use InventoryLocationStock adjustments via movements.
    //     return back()->with('error', 'Manual inventory creation is deprecated. Use stock movements.');
    // }

    // public function update(Request $request, Inventory $inventory)
    // {
    //     // Deprecated: use InventoryLocationStock adjustments via movements.
    //     return back()->with('error', 'Manual inventory update is deprecated. Use stock movements.');
    // }

    // public function destroy(Inventory $inventory)
    // {
    //     // Deprecated: inventory records are not deleted in new schema.
    //     return back()->with('error', 'Inventory deletion is deprecated.');
    // }

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
            ->with(['arrivalItem.vendorPart', 'arrivalItem.gciPart', 'arrivalItem.arrival'])
            ->where(function ($q) use ($query) {
                $q->where('tag', 'like', '%' . $query . '%')
                    ->orWhereHas('arrivalItem', function ($qq) use ($query) {
                        $qq->whereHas('arrival', fn ($a) => $a->where('invoice_no', 'like', '%' . $query . '%'))
                            ->orWhereHas('gciPart', function ($qqq) use ($query) {
                                $qqq->where('part_no', 'like', '%' . $query . '%')
                                    ->orWhere('part_name', 'like', '%' . $query . '%');
                            })
                            ->orWhereHas('vendorPart', function ($qqq) use ($query) {
                                $qqq->where('vendor_part_no', 'like', '%' . $query . '%')
                                    ->orWhere('vendor_part_name', 'like', '%' . $query . '%');
                            });
                    });
            })
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($receive) {
                $part = $receive->arrivalItem?->gciPart;
                $vendorPart = $receive->arrivalItem?->vendorPart;
                $arrival = $receive->arrivalItem?->arrival;
                return [
                    'id' => $receive->id,
                    'tag' => $receive->tag,
                    'part_no' => $part?->part_no ?? $vendorPart?->vendor_part_no ?? '-',
                    'part_name' => $part?->part_name ?? $vendorPart?->vendor_part_name ?? '-',
                    'invoice_no' => $arrival?->invoice_no ?? '-',
                    'location_code' => $receive->location_code ?? '-',
                ];
            });

        return response()->json($receives);
    }
}
