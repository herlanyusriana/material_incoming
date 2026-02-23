<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\GciInventory;
use App\Models\Part;
use App\Models\GciPart;
use App\Models\Receive;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Exports\InventoryExport;
use App\Imports\InventoryImport;
use Maatwebsite\Excel\Facades\Excel;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->query('tab', 'rm');

        // --- RM Data ---
        $rmPartId = $request->query('rm_part_id');
        $rmQ = trim((string) $request->query('rm_q', ''));
        $rmParts = Part::query()->orderBy('part_no')->get();

        $inventories = Inventory::query()
            ->with('part')
            ->when($rmPartId, fn($q) => $q->where('part_id', $rmPartId))
            ->when($rmQ !== '', function ($qr) use ($rmQ) {
                $qr->whereHas('part', function ($qp) use ($rmQ) {
                    $qp->where('part_no', 'like', '%' . $rmQ . '%')
                        ->orWhere('part_name_gci', 'like', '%' . $rmQ . '%')
                        ->orWhere('register_no', 'like', '%' . $rmQ . '%');
                });
            })
            ->orderBy(Part::select('part_no')->whereColumn('parts.id', 'inventories.part_id'))
            ->paginate(25, ['*'], 'rm_page')
            ->withQueryString();

        // --- FG/WIP Data ---
        $gciSearch = trim((string) $request->query('gci_search', ''));
        $gciClass = strtoupper(trim((string) $request->query('gci_class', '')));
        $gciStatus = strtolower(trim((string) $request->query('gci_status', '')));
        $gciPerPage = (int) $request->query('gci_per_page', 25);
        if ($gciPerPage < 10)
            $gciPerPage = 10;
        if ($gciPerPage > 200)
            $gciPerPage = 200;

        $gciQuery = GciInventory::query()
            ->with('part.customer')
            ->when($gciClass !== '', fn($q) => $q->whereHas('part', fn($qp) => $qp->where('classification', $gciClass)))
            ->when(in_array($gciStatus, ['active', 'inactive'], true), fn($q) => $q->whereHas('part', fn($qp) => $qp->where('status', $gciStatus)))
            ->when($gciSearch !== '', function ($q) use ($gciSearch) {
                $s = strtoupper($gciSearch);
                $q->whereHas('part', function ($qp) use ($s) {
                    $qp->where('part_no', 'like', '%' . $s . '%')
                        ->orWhere('part_name', 'like', '%' . $s . '%')
                        ->orWhere('model', 'like', '%' . $s . '%');
                });
            })
            ->orderByDesc('on_hand')
            ->orderBy('gci_part_id');

        $gciRows = $gciQuery->paginate($gciPerPage, ['*'], 'gci_page')->withQueryString();
        $gciPartsForModal = GciPart::where('status', 'active')->orderBy('part_no')->get();

        return view('inventory.index', compact(
            'activeTab',
            // RM
            'inventories',
            'rmParts',
            'rmPartId',
            'rmQ',
            // GCI
            'gciRows',
            'gciSearch',
            'gciClass',
            'gciStatus',
            'gciPerPage',
            'gciPartsForModal'
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

        Excel::import(new InventoryImport(), $validated['file']);

        return back()->with('success', 'Inventory imported.');
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
