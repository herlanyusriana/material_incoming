<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StandardPackingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = \App\Models\StandardPacking::with(['part.customer']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('part', function($q) use ($search) {
                $q->where('part_no', 'like', "%{$search}%")
                  ->orWhere('part_name', 'like', "%{$search}%");
            });
        }
        
        // Filter by Customer
        if ($request->has('customer_id') && $request->customer_id != '') {
             $query->whereHas('part', function($q) use ($request) {
                $q->where('customer_id', $request->customer_id);
            });
        }

        $packings = $query->paginate(15);
        $customers = \App\Models\Customer::orderBy('name')->get();

        return view('outgoing.standard-packings.index', compact('packings', 'customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
       // return view('outgoing.standard-packings.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
             'gci_part_id' => 'required|exists:gci_parts,id',
             'delivery_class' => 'nullable|string',
             'packing_qty' => 'required|numeric|min:0',
             'uom' => 'nullable|string',
             'trolley_type' => 'nullable|string',
        ]);

        \App\Models\StandardPacking::updateOrCreate(
            ['gci_part_id' => $request->gci_part_id, 'delivery_class' => $request->delivery_class ?? 'Main'],
            [
                'packing_qty' => $request->packing_qty,
                'uom' => $request->uom ?? 'PCS',
                'trolley_type' => $request->trolley_type,
                'status' => 'active'
            ]
        );

        return back()->with('success', 'Standard Packing saved successfully.');
    }

    /**
     * Import Excel
     */
    public function import(Request $request) 
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\StandardPackingImport, $request->file('file'));
            return back()->with('success', 'Standard Packing imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
    
    public function export()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\StandardPackingExport, 'standard_packings.xlsx');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $item = \App\Models\StandardPacking::findOrFail($id);
        $item->delete();
        return back()->with('success', 'Item deleted.');
    }
}
