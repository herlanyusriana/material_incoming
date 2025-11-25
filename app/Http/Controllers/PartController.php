<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\Vendor;
use Illuminate\Http\Request;

class PartController extends Controller
{
    public function index(Request $request)
    {
        $vendorId = $request->query('vendor_id');
        $search = $request->query('q');

        $parts = Part::with('vendor')
            ->when($vendorId, fn ($query) => $query->where('vendor_id', $vendorId))
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('part_no', 'like', "%{$search}%")
                        ->orWhere('register_no', 'like', "%{$search}%")
                        ->orWhere('part_name_vendor', 'like', "%{$search}%")
                        ->orWhere('part_name_gci', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $vendors = Vendor::orderBy('vendor_name')->get();

        return view('parts.index', compact('parts', 'vendors', 'vendorId', 'search'));
    }

    public function create()
    {
        $vendors = Vendor::orderBy('vendor_name')->get();

        return view('parts.create', compact('vendors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'register_no' => ['required', 'string', 'max:255'],
            'part_no' => ['required', 'string', 'max:255'],
            'part_name_vendor' => ['required', 'string', 'max:255'],
            'part_name_gci' => ['required', 'string', 'max:255'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'trucking_company' => ['nullable', 'string', 'max:255'],
            'storage_reg' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        Part::create($data);

        return redirect()->route('parts.index')->with('status', 'Part created.');
    }

    public function edit(Part $part)
    {
        $vendors = Vendor::orderBy('vendor_name')->get();

        return view('parts.edit', compact('part', 'vendors'));
    }

    public function update(Request $request, Part $part)
    {
        $data = $request->validate([
            'register_no' => ['required', 'string', 'max:255'],
            'part_no' => ['required', 'string', 'max:255'],
            'part_name_vendor' => ['required', 'string', 'max:255'],
            'part_name_gci' => ['required', 'string', 'max:255'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'trucking_company' => ['nullable', 'string', 'max:255'],
            'storage_reg' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $part->update($data);

        return redirect()->route('parts.index')->with('status', 'Part updated.');
    }

    public function destroy(Part $part)
    {
        $part->delete();

        return redirect()->route('parts.index')->with('status', 'Part deleted.');
    }

    public function byVendor(Vendor $vendor)
    {
        $parts = Part::where('vendor_id', $vendor->id)
            ->where('status', 'active')
            ->orderBy('part_no')
            ->get(['id', 'part_no', 'part_name_vendor']);

        return response()->json($parts);
    }
}
