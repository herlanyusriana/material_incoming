<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Part;
use App\Models\Receive;
use App\Models\WarehouseLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Exports\InventoryExport;
use App\Imports\InventoryImport;
use Maatwebsite\Excel\Facades\Excel;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $partId = $request->query('part_id');

        $parts = Part::query()->orderBy('part_no')->get();

        $inventories = Inventory::query()
            ->with('part')
            ->when($partId, fn ($q) => $q->where('part_id', $partId))
            ->orderBy(Part::select('part_no')->whereColumn('parts.id', 'inventories.part_id'))
            ->paginate(25)
            ->withQueryString();

        return view('inventory.index', compact('inventories', 'parts', 'partId'));
    }

    public function receives(Request $request)
    {
        $partId = $request->query('part_id');
        $qcStatus = $request->query('qc_status');

        $parts = Part::query()->orderBy('part_no')->get();

        $receives = Receive::query()
            ->with(['arrivalItem.part', 'arrivalItem.arrival'])
            ->when($partId, fn ($q) => $q->whereHas('arrivalItem', fn ($qq) => $qq->where('part_id', $partId)))
            ->when($qcStatus, fn ($q) => $q->where('qc_status', $qcStatus))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $locationCodes = $receives->getCollection()
            ->pluck('location_code')
            ->filter(fn ($code) => is_string($code) && trim($code) !== '')
            ->map(fn ($code) => strtoupper(trim($code)))
            ->unique()
            ->values();

        $locationMap = $locationCodes->isEmpty()
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
}
