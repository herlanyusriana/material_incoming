<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\Vendor;
use App\Exports\PartsExport;
use App\Imports\PartsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class PartController extends Controller
{
    public function index(Request $request)
    {
        $vendorId = $request->query('vendor_id');
        $statusFilter = $request->query('status_filter');
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
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $vendors = Vendor::orderBy('vendor_name')->get();

        return view('parts.index', compact('parts', 'vendors', 'vendorId', 'search', 'statusFilter'));
    }

    public function export()
    {
        return Excel::download(new PartsExport, 'parts_' . date('Y-m-d_His') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:2048',
        ]);

        try {
            $import = new PartsImport();
            Excel::import($import, $request->file('file'));

            $failures = collect($import->failures());
            if ($failures->isNotEmpty()) {
                $preview = $failures
                    ->take(5)
                    ->map(fn ($f) => "Row {$f->row()}: " . implode(' | ', $f->errors()))
                    ->implode(' ; ');

                return back()->with('error', "Import selesai tapi ada {$failures->count()} baris gagal. {$preview}");
            }

            return back()->with('status', 'Parts imported successfully.');
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                $failures = collect($e->failures());
                $preview = $failures
                    ->take(5)
                    ->map(fn ($f) => "Row {$f->row()}: " . implode(' | ', $f->errors()))
                    ->implode(' ; ');
                return back()->with('error', "Import failed: {$preview}");
            }
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $vendors = Vendor::orderBy('vendor_name')->get();
        $part = new Part();

        return view('parts.create', compact('vendors', 'part'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'register_no' => ['required', 'string', 'max:255'],
            'part_no' => ['required', 'string', 'max:255', 'unique:parts,part_no'],
            'part_name_vendor' => ['required', 'string', 'max:255'],
            'part_name_gci' => ['required', 'string', 'max:255'],
            'hs_code' => ['nullable', 'string', 'max:50'],
            'quality_inspection' => ['nullable', 'in:YES'],
            'vendor_id' => ['required', 'exists:vendors,id'],
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
            'part_no' => ['required', 'string', 'max:255', 'unique:parts,part_no,' . $part->id],
            'part_name_vendor' => ['required', 'string', 'max:255'],
            'part_name_gci' => ['required', 'string', 'max:255'],
            'hs_code' => ['nullable', 'string', 'max:50'],
            'quality_inspection' => ['nullable', 'in:YES'],
            'vendor_id' => ['required', 'exists:vendors,id'],
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
            ->get(['id', 'part_no', 'register_no', 'part_name_vendor', 'part_name_gci']);

        return response()->json($parts);
    }
}
