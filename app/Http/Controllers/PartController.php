<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\GciPart;
use App\Models\GciPartVendor;
use App\Models\Vendor;
use App\Exports\PartsExport;
use App\Imports\PartsImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\BomItem;
use App\Models\BomItemSubstitute;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

class PartController extends Controller
{
    /**
     * API: search parts (vendor-level) for autocomplete.
     * Kept as-is for backward compat with departure/arrival forms.
     */
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

    /**
     * Parts Master index: hierarchical GCI Parts with vendor parts.
     */
    public function index(Request $request)
    {
        $classification = $request->query('classification', 'RM');
        $status = $request->query('status', 'active');
        $search = $request->query('q');

        // Substitute tab: query BomItemSubstitute instead of GciPart
        if (strtoupper($classification) === 'SUB') {
            $substitutes = BomItemSubstitute::with(['bomItem.bom.part', 'bomItem.componentPart', 'part'])
                ->when($status, fn($q) => $q->where('status', $status))
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('substitute_part_no', 'like', "%{$search}%")
                            ->orWhereHas('part', fn($q) => $q->where('part_name', 'like', "%{$search}%"))
                            ->orWhereHas('bomItem.componentPart', fn($q) => $q->where('part_no', 'like', "%{$search}%"))
                            ->orWhereHas('bomItem.bom.part', fn($q) => $q->where('part_no', 'like', "%{$search}%"));
                    });
                })
                ->latest()
                ->paginate(25)
                ->withQueryString();

            $vendors = Vendor::orderBy('vendor_name')->get();
            $customers = \App\Models\Customer::where('status', 'active')->orderBy('name')->get();
            $rmParts = GciPart::where('classification', 'RM')
                ->where('status', 'active')
                ->orderBy('part_no')
                ->get(['id', 'part_no', 'part_name']);

            return view('parts.index', [
                'parts' => collect(), // empty for non-SUB sections
                'substitutes' => $substitutes,
                'vendors' => $vendors,
                'customers' => $customers,
                'rmParts' => $rmParts,
                'classification' => 'SUB',
                'status' => $status,
                'search' => $search,
            ]);
        }

        // Classification-specific eager loading
        $eagerLoads = ['customer'];
        if ($classification === 'RM') {
            $eagerLoads[] = 'vendorLinks.vendor';
        } elseif ($classification === 'FG') {
            $eagerLoads[] = 'customerPartUsages.customerPart.customer';
        }

        $parts = GciPart::with($eagerLoads)
            ->where('classification', strtoupper($classification))
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search, function ($query, $search) use ($classification) {
                $query->where(function ($inner) use ($search, $classification) {
                    $inner->where('part_no', 'like', "%{$search}%")
                        ->orWhere('part_name', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%");

                    if ($classification === 'RM') {
                        $inner->orWhereHas('vendorLinks', function ($vq) use ($search) {
                            $vq->where('vendor_part_no', 'like', "%{$search}%")
                                ->orWhere('vendor_part_name', 'like', "%{$search}%")
                                ->orWhere('register_no', 'like', "%{$search}%");
                        });
                    }
                });
            })
            ->orderBy('part_no')
            ->paginate(25)
            ->withQueryString();

        $vendors = Vendor::orderBy('vendor_name')->get();
        $customers = \App\Models\Customer::where('status', 'active')->orderBy('name')->get();

        // Part → linked Vendor IDs mapping (for edit pre-populate)
        $partVendorMap = [];
        $partIds = $parts->pluck('id')->toArray();
        if (!empty($partIds)) {
            $vendorLinks = DB::table('gci_part_vendor')
                ->whereIn('gci_part_id', $partIds)
                ->get(['gci_part_id', 'vendor_id']);
            foreach ($vendorLinks as $vl) {
                $partVendorMap[$vl->gci_part_id][] = $vl->vendor_id;
            }
        }

        // Substitute detail maps for RM modal
        $partSubstitutesMap = [];
        $partAsSubstituteMap = [];
        $rmParts = collect();
        $rmFgMap = [];
        $fgPartsWithBom = collect();

        $rmIds = ($classification === 'RM') ? $parts->pluck('id')->toArray() : [];
        if (!empty($rmIds)) {
            $subsForParts = BomItemSubstitute::query()
                ->whereHas('bomItem', fn($q) => $q->whereIn('component_part_id', $rmIds))
                ->with(['bomItem.bom.part:id,part_no,part_name', 'bomItem:id,bom_id,component_part_id', 'part:id,part_no,part_name'])
                ->get();

            foreach ($subsForParts as $sub) {
                $componentPartId = $sub->bomItem->component_part_id;
                $partSubstitutesMap[$componentPartId][] = [
                    'id' => $sub->id,
                    'fg_part_id' => $sub->bomItem->bom->part->id ?? null,
                    'fg_part_no' => $sub->bomItem->bom->part->part_no ?? '?',
                    'substitute_part_id' => $sub->substitute_part_id,
                    'substitute_part_no' => $sub->part->part_no ?? $sub->substitute_part_no,
                    'substitute_part_name' => $sub->part->part_name ?? '',
                    'ratio' => $sub->ratio,
                    'priority' => $sub->priority,
                    'status' => $sub->status,
                    'notes' => $sub->notes,
                ];
            }

            $asSubstitute = BomItemSubstitute::query()
                ->whereIn('substitute_part_id', $rmIds)
                ->with(['bomItem.bom.part:id,part_no', 'bomItem:id,bom_id,component_part_id,component_part_no', 'bomItem.componentPart:id,part_no,part_name'])
                ->get();

            foreach ($asSubstitute as $sub) {
                $partAsSubstituteMap[$sub->substitute_part_id][] = [
                    'id' => $sub->id,
                    'fg_part_no' => $sub->bomItem->bom->part->part_no ?? '?',
                    'original_rm_part_no' => $sub->bomItem->componentPart->part_no ?? $sub->bomItem->component_part_no,
                    'original_rm_part_name' => $sub->bomItem->componentPart->part_name ?? '',
                    'ratio' => $sub->ratio,
                    'priority' => $sub->priority,
                    'status' => $sub->status,
                ];
            }

            $links = BomItem::query()
                ->whereIn('component_part_id', $rmIds)
                ->with('bom.part:id,part_no')
                ->get(['id', 'bom_id', 'component_part_id']);
            foreach ($links as $link) {
                if ($link->bom?->part_id) {
                    $rmFgMap[$link->component_part_id][] = $link->bom->part_id;
                }
            }
            foreach ($rmFgMap as $pid => $fgIds) {
                $rmFgMap[$pid] = array_values(array_unique($fgIds));
            }

            $rmParts = GciPart::where('classification', 'RM')
                ->where('status', 'active')
                ->orderBy('part_no')
                ->get(['id', 'part_no', 'part_name']);

            $fgPartsWithBom = GciPart::where('classification', 'FG')
                ->whereHas('bom')
                ->orderBy('part_no')
                ->get(['id', 'part_no', 'part_name']);
        }

        return view('parts.index', compact('parts', 'vendors', 'customers', 'classification', 'status', 'search', 'partVendorMap', 'partSubstitutesMap', 'partAsSubstituteMap', 'rmParts', 'rmFgMap', 'fgPartsWithBom'));
    }

    public function export(Request $request)
    {
        $classification = $request->query('classification');
        return Excel::download(new PartsExport($classification), 'parts_' . ($classification ? strtolower($classification) . '_' : '') . date('Y-m-d_His') . '.xlsx');
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

            \Illuminate\Support\Facades\Storage::delete($filePath);

            $msg = "Parts imported successfully.{$dupMsg}";

            if (!empty($createdVendors)) {
                $preview = collect($createdVendors)->take(5)->implode(', ');
                $more = count($createdVendors) > 5 ? ' (+' . (count($createdVendors) - 5) . ' more)' : '';
                $msg .= " New vendors created: {$preview}{$more}";
            }

            if (!empty($import->skippedParts)) {
                $skippedCount = count($import->skippedParts);
                $skippedPreview = collect($import->skippedParts)->take(5)->implode(', ');
                $msg .= " ⚠ Skipped {$skippedCount} parts (not in master): {$skippedPreview}";
            }
            return back()->with('status', $msg);
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
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

    // ─── GCI Part CRUD ───

    public function store(Request $request)
    {
        $data = $request->validate([
            'part_no' => ['required', 'string', 'max:255', Rule::unique('gci_parts', 'part_no')],
            'part_name' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:255'],
            'classification' => ['required', 'in:FG,WIP,RM'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'status' => ['required', 'in:active,inactive'],
            'vendor_ids' => ['nullable', 'array'],
            'vendor_ids.*' => ['exists:vendors,id'],
        ]);

        $vendorIds = $data['vendor_ids'] ?? [];
        unset($data['vendor_ids']);

        try {
            $gciPart = GciPart::create($data);

            if ($data['classification'] === 'RM' && !empty($vendorIds)) {
                $gciPart->vendors()->syncWithoutDetaching($vendorIds);
            }
        } catch (QueryException $e) {
            $msg = strtolower((string) $e->getMessage());
            if (str_contains($msg, 'gci_parts_part_no_unique') || str_contains($msg, 'duplicate')) {
                return back()->withInput()->withErrors([
                    'part_no' => 'Part No sudah terdaftar. Gunakan Part No lain.',
                ]);
            }
            return back()->withInput()->withErrors([
                'part_no' => 'Gagal simpan part. Periksa data lalu coba lagi.',
            ]);
        }

        return redirect()->route('parts.index')->with('status', 'Part created.');
    }

    public function update(Request $request, GciPart $part)
    {
        $data = $request->validate([
            'part_no' => ['required', 'string', 'max:255', Rule::unique('gci_parts', 'part_no')->ignore($part->id)],
            'part_name' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:255'],
            'classification' => ['required', 'in:FG,WIP,RM'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'status' => ['required', 'in:active,inactive'],
            'vendor_ids' => ['nullable', 'array'],
            'vendor_ids.*' => ['exists:vendors,id'],
        ]);

        $vendorIds = $data['vendor_ids'] ?? [];
        unset($data['vendor_ids']);

        try {
            $part->update($data);

            if ($data['classification'] === 'RM') {
                $part->vendors()->sync($vendorIds);
            }
        } catch (QueryException $e) {
            $msg = strtolower((string) $e->getMessage());
            if (str_contains($msg, 'gci_parts_part_no_unique') || str_contains($msg, 'duplicate')) {
                return back()->withInput()->withErrors([
                    'part_no' => 'Part No sudah terdaftar. Gunakan Part No lain.',
                ]);
            }
            return back()->withInput()->withErrors([
                'part_no' => 'Gagal update part. Periksa data lalu coba lagi.',
            ]);
        }

        return redirect()->route('parts.index')->with('status', 'Part updated.');
    }

    public function destroy(GciPart $part)
    {
        if ($part->vendorLinks()->count() > 0) {
            return back()->with('error', 'Cannot delete part with vendor links. Remove vendor parts first.');
        }

        $part->delete();

        return redirect()->route('parts.index')->with('status', 'Part deleted.');
    }

    // ─── Vendor Part CRUD ───

    public function storeVendorPart(Request $request, GciPart $part)
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'vendor_part_no' => ['nullable', 'string', 'max:255'],
            'vendor_part_name' => ['nullable', 'string', 'max:255'],
            'register_no' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'uom' => ['nullable', 'string', 'max:20'],
            'hs_code' => ['nullable', 'string', 'max:50'],
            'quality_inspection' => ['nullable', 'in:YES'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $data['gci_part_id'] = $part->id;
        $data['quality_inspection'] = ($data['quality_inspection'] ?? null) === 'YES';

        GciPartVendor::create($data);

        return redirect()->route('parts.index')->with('status', 'Vendor part added.');
    }

    public function updateVendorPart(Request $request, GciPartVendor $vendorPart)
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'vendor_part_no' => ['nullable', 'string', 'max:255'],
            'vendor_part_name' => ['nullable', 'string', 'max:255'],
            'register_no' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'uom' => ['nullable', 'string', 'max:20'],
            'hs_code' => ['nullable', 'string', 'max:50'],
            'quality_inspection' => ['nullable', 'in:YES'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $data['quality_inspection'] = ($data['quality_inspection'] ?? null) === 'YES';

        $vendorPart->update($data);

        return redirect()->route('parts.index')->with('status', 'Vendor part updated.');
    }

    public function destroyVendorPart(GciPartVendor $vendorPart)
    {
        $vendorPart->delete();

        return redirect()->route('parts.index')->with('status', 'Vendor part deleted.');
    }

    // ─── API: vendor-scoped part lookup (backward compat) ───

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

        return response()->json($parts);
    }
}
