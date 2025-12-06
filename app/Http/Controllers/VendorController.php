<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Exports\VendorsExport;
use App\Imports\VendorsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('q');
        $status = $request->query('status');

        $vendors = Vendor::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('vendor_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('vendors.index', compact('vendors', 'search', 'status'));
    }

    public function export()
    {
        return Excel::download(new VendorsExport, 'vendors_' . date('Y-m-d_His') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
        ]);

        try {
            Excel::import(new VendorsImport, $request->file('file'));
            return back()->with('status', 'Vendors imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('vendors.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vendor_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'bank_account' => ['nullable', 'string', 'max:255'],
        ]);

        Vendor::create($data + ['status' => 'active']);

        return redirect()->route('vendors.index')->with('status', 'Vendor created.');
    }

    public function edit(Vendor $vendor)
    {
        return view('vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'vendor_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'bank_account' => ['nullable', 'string', 'max:255'],
        ]);

        $vendor->update($data);

        return redirect()->route('vendors.index')->with('status', 'Vendor updated.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return redirect()->route('vendors.index')->with('status', 'Vendor archived.');
    }
}
