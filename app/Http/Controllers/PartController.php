<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\GciPart;
use App\Models\Vendor;
use App\Exports\PartsExport;
use App\Imports\PartsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Illuminate\Support\Facades\DB;

class PartController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $limit = (int) $request->query('limit', 20);
        if ($limit < 5) {
            $limit = 5;
        }
        if ($limit > 50) {
            $limit = 50;
        }

        // Avoid returning huge datasets (and freezing the UI) when user hasn't typed a query.
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $inStock = $request->boolean('in_stock');

        $query = Part::query()
            ->select(['id', 'part_no', 'register_no', 'part_name_gci', 'part_name_vendor'])
            ->when($inStock, function ($qr) {
                $qr->whereHas('locationInventory', fn($q) => $q->where('qty_on_hand', '>', 0));
            })
            ->where(function ($qr) use ($q) {
                $qr->where('part_no', 'like', '%' . $q . '%')
                    ->orWhere('register_no', 'like', '%' . $q . '%')
                    ->orWhere('part_name_gci', 'like', '%' . $q . '%')
                    ->orWhere('part_name_vendor', 'like', '%' . $q . '%');
            })
            ->orderBy('part_no')
            ->limit($limit);

        return response()->json($query->get());
    }

    public function index(Request $request)
    {
        $vendorId = $request->query('vendor_id');
        $statusFilter = $request->query('status_filter');
        $search = $request->query('q');

        $parts = Part::with('vendor')
            ->when($vendorId, fn($query) => $query->where('vendor_id', $vendorId))
            ->when($search, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('part_no', 'like', "%{$search}%")
                        ->orWhere('register_no', 'like', "%{$search}%")
                        ->orWhere('part_name_vendor', 'like', "%{$search}%")
                        ->orWhere('part_name_gci', 'like', "%{$search}%");
                });
            })
            ->when($statusFilter, fn($query) => $query->where('status', $statusFilter))
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
            'file' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:2048'],
            'temp_file' => ['nullable', 'string'],
        ]);

        $file = $request->file('file');
        $tempPath = $request->input('temp_file');
        $confirm = $request->boolean('confirm_import');

        if (!$file && !$tempPath) {
            return back()->with('error', 'Please upload a file.');
        }

        try {
            $filePath = null;
            if ($file) {
                // Save original file to temp storage
                $filename = 'import_parts_' . auth()->id() . '_' . time() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('temp', $filename);
            } else {
                $filePath = $tempPath;
            }

            if (!$filePath || !\Illuminate\Support\Facades\Storage::exists($filePath)) {
                return back()->with('error', 'File not found or expired. Please upload again.');
            }

            $import = new PartsImport($confirm);
            Excel::import($import, \Illuminate\Support\Facades\Storage::path($filePath));

            // Check for skipped duplicates
            $dupMsg = '';
            if (!empty($import->duplicates)) {
                $dupCount = count($import->duplicates);
                $dupMsg = " Skipped {$dupCount} duplicate rows.";
            }

            $failures = collect($import->failures());
            if ($failures->isNotEmpty()) {
                \Illuminate\Support\Facades\Storage::delete($filePath);
                $preview = $failures
                    ->take(5)
                    ->map(fn($f) => "Row {$f->row()}: " . implode(' | ', $f->errors()))
                    ->implode(' ; ');
                return back()->with('error', "Import selesai tapi ada {$failures->count()} baris gagal.{$dupMsg} Details: {$preview}");
            }

            $createdVendors = $import->createdVendors();

            // Delete temp file on success
            \Illuminate\Support\Facades\Storage::delete($filePath);

            $msg = "Parts imported successfully.{$dupMsg}";

            if (!empty($createdVendors)) {
                $preview = collect($createdVendors)->take(5)->implode(', ');
                $more = count($createdVendors) > 5 ? ' (+' . (count($createdVendors) - 5) . ' more)' : '';
                $msg .= " New vendors created: {$preview}{$more}";
            }

            return back()->with('status', $msg);
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                // Keep file logic if reusable? Complexity. Let's delete for now on validation error.
                if (isset($filePath))
                    \Illuminate\Support\Facades\Storage::delete($filePath);

                $failures = collect($e->failures());
                $preview = $failures
                    ->take(5)
                    ->map(fn($f) => "Row {$f->row()}: " . implode(' | ', $f->errors()))
                    ->implode(' ; ');
                return back()->with('error', "Import failed: {$preview}");
            }
            if (isset($filePath))
                \Illuminate\Support\Facades\Storage::delete($filePath);
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
        $rules = [
            'register_no' => ['required', 'string', 'max:255'],
            'part_no' => ['required', 'string', 'max:255'],
            'part_name_vendor' => ['required', 'string', 'max:255'],
            'part_name_gci' => ['required', 'string', 'max:255'],
            'hs_code' => ['nullable', 'string', 'max:50'],
            'quality_inspection' => ['nullable', 'in:YES'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'status' => ['required', 'in:active,inactive'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'uom' => ['nullable', 'string', 'max:10'],
        ];

        $validated = $request->validate($rules);

        // Check for duplicates
        if (!$request->boolean('confirm_duplicate')) {
            if (Part::where('part_no', $validated['part_no'])->exists()) {
                return back()
                    ->withInput()
                    ->with('duplicate_warning', "Part number '{$validated['part_no']}' already exists. Do you want to proceed creating a duplicate?");
            }
        }

        // --- Logic to Link to GCI Part (The Bridge) ---
        $gciPart = GciPart::where('part_no', $validated['part_no'])->first();

        if (!$gciPart && !empty($validated['part_name_gci'])) {
            $gciPart = GciPart::where('part_name', $validated['part_name_gci'])->first();
        }

        if (!$gciPart) {
            $gciPart = GciPart::create([
                'part_no' => $validated['part_no'],
                'part_name' => $validated['part_name_gci'],
                'classification' => 'RM',
                'status' => 'active',
            ]);
        }

        $validated['gci_part_id'] = $gciPart->id;
        // ----------------------------------------------

        Part::create($validated);

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
            'hs_code' => ['nullable', 'string', 'max:50'],
            'quality_inspection' => ['nullable', 'in:YES'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'status' => ['required', 'in:active,inactive'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'uom' => ['nullable', 'string', 'max:10'],
        ]);

        $part->update($data);

        return redirect()->route('parts.index')->with('status', 'Part updated.');
    }

    public function destroy(Part $part)
    {
        $part->delete();

        return redirect()->route('parts.index')->with('status', 'Part deleted.');
    }

    public function byVendor(Request $request, Vendor $vendor)
    {
        $mode = strtolower(trim((string) $request->query('mode', 'parts')));
        $q = trim((string) $request->query('q', ''));
        $groupTitle = trim((string) $request->query('group_title', ''));
        $limit = (int) $request->query('limit', 200);
        if ($limit < 10) {
            $limit = 10;
        }
        if ($limit > 500) {
            $limit = 500;
        }

        if ($mode === 'names') {
            $base = Part::query()
                ->where('vendor_id', $vendor->id)
                ->whereNotNull('part_name_vendor')
                ->whereRaw("TRIM(part_name_vendor) <> ''")
                ->when($q !== '', function ($qr) use ($q) {
                    $qr->where('part_name_vendor', 'like', '%' . $q . '%');
                })
                ->select(DB::raw('TRIM(part_name_vendor) as part_name_vendor'))
                ->distinct()
                ->orderBy(DB::raw('TRIM(part_name_vendor)'));

            $total = (clone $base)->count();
            $names = (clone $base)->limit($limit)->pluck('part_name_vendor')->all();

            return response()->json([
                'names' => $names,
                'total' => $total,
                'limit' => $limit,
                'truncated' => $total > $limit,
            ]);
        }

        $base = Part::query()
            ->where('vendor_id', $vendor->id)
            ->where('status', 'active')
            ->when($groupTitle !== '', function ($qr) use ($groupTitle) {
                // Case-insensitive exact match by normalized title.
                $qr->whereRaw('UPPER(TRIM(part_name_vendor)) = UPPER(?)', [$groupTitle]);
            })
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($inner) use ($q) {
                    $inner->where('part_no', 'like', '%' . $q . '%')
                        ->orWhere('register_no', 'like', '%' . $q . '%')
                        ->orWhere('part_name_vendor', 'like', '%' . $q . '%')
                        ->orWhere('part_name_gci', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('part_no')
            ->select(['id', 'part_no', 'register_no', 'part_name_vendor', 'part_name_gci', 'uom']);

        $total = (clone $base)->count();
        $parts = (clone $base)->limit($limit)->get();

        if ($request->boolean('meta')) {
            return response()->json([
                'parts' => $parts,
                'total' => $total,
                'limit' => $limit,
                'truncated' => $total > $limit,
            ]);
        }

        // Backward-compat: keep returning a plain array for existing pages.
        return response()->json($parts);
    }
}
