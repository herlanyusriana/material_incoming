<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Exports\GciPartsExport;
use App\Imports\GciPartsImport;
use App\Models\GciPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class GciPartController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        
        // Get classification from route default or query param
        $classification = $request->route('classification') ?? $request->query('classification');

        $qParam = trim((string) $request->query('q', ''));

        $parts = GciPart::query()
            ->with('customer')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($classification, fn ($q) => $q->where('classification', strtoupper($classification)))
            ->when($qParam, function ($query) use ($qParam) {
                $query->where(function ($sub) use ($qParam) {
                    $sub->where('part_no', 'like', "%{$qParam}%")
                        ->orWhere('part_name', 'like', "%{$qParam}%")
                        ->orWhere('model', 'like', "%{$qParam}%");
                });
            })
            ->orderBy('part_no')
            ->paginate(25)
            ->withQueryString();

        $customers = \App\Models\Customer::where('status', 'active')->orderBy('name')->get();

        return view('planning.gci_parts.index', compact('parts', 'status', 'classification', 'customers', 'qParam'));
    }

    public function export()
    {
        return Excel::download(new GciPartsExport(), 'gci_parts_' . date('Y-m-d_His') . '.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048'],
        ]);

        try {
            $import = new GciPartsImport();
            DB::transaction(function () use ($request, $import) {
                Excel::import($import, $request->file('file'));
            });

            $failures = collect($import->failures());
            if ($failures->isNotEmpty()) {
                $preview = $failures
                    ->take(5)
                    ->map(fn ($f) => "Row {$f->row()}: " . implode(' | ', $f->errors()))
                    ->implode(' ; ');
                return back()->with('error', "Import selesai tapi ada {$failures->count()} baris gagal. {$preview}");
            }

            return back()->with('success', 'Part GCI imported.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'part_no' => ['required', 'string', 'max:100'],
            'classification' => ['required', Rule::in(['FG', 'WIP', 'RM'])],
            'part_name' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validated['part_no'] = strtoupper(trim($validated['part_no']));
        $validated['classification'] = strtoupper(trim($validated['classification']));
        $validated['part_name'] = $validated['part_name'] ? trim($validated['part_name']) : null;
        $validated['model'] = $validated['model'] ? trim($validated['model']) : null;

        if (!$request->boolean('confirm_duplicate')) {
            if (GciPart::where('part_no', $validated['part_no'])->exists()) {
                return back()
                    ->withInput()
                    ->with('duplicate_warning_data', $request->all())
                    ->with('error', "Part number '{$validated['part_no']}' already exists. Please confirm to proceed.");
            }
        }

        GciPart::create($validated);

        return back()->with('success', 'Part GCI created.');
    }

    public function update(Request $request, GciPart $gciPart)
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'part_no' => ['required', 'string', 'max:100'],
            'classification' => ['required', Rule::in(['FG', 'WIP', 'RM'])],
            'part_name' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validated['part_no'] = strtoupper(trim($validated['part_no']));
        $validated['classification'] = strtoupper(trim($validated['classification']));
        $validated['part_name'] = $validated['part_name'] ? trim($validated['part_name']) : null;
        $validated['model'] = $validated['model'] ? trim($validated['model']) : null;

        $gciPart->update($validated);

        return back()->with('success', 'Part GCI updated.');
    }

    public function destroy(GciPart $gciPart)
    {
        try {
            $gciPart->delete();
            return back()->with('success', 'Part GCI deleted.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Check if it's a foreign key constraint error
            if ($e->getCode() === '23000') {
                $references = [];
                
                // Check common tables that might reference this part
                if (DB::table('boms')->where('part_id', $gciPart->id)->exists()) {
                    $references[] = 'BOM (Bill of Materials)';
                }
                if (DB::table('bom_items')->where('component_part_id', $gciPart->id)->exists()) {
                    $references[] = 'BOM Items (used as component)';
                }
                if (DB::table('bom_items')->where('wip_part_id', $gciPart->id)->exists()) {
                    $references[] = 'BOM Items (used as WIP)';
                }
                if (DB::table('customer_part_components')->where('part_id', $gciPart->id)->exists()) {
                    $references[] = 'Customer Part Mapping';
                }
                if (DB::table('mps')->where('part_id', $gciPart->id)->exists()) {
                    $references[] = 'MPS (Master Production Schedule)';
                }
                if (DB::table('forecasts')->where('part_id', $gciPart->id)->exists()) {
                    $references[] = 'Forecasts';
                }
                if (DB::table('mrp_production_plans')->where('part_id', $gciPart->id)->exists()) {
                    $references[] = 'MRP Production Plans';
                }
                if (DB::table('mrp_purchase_plans')->where('part_id', $gciPart->id)->exists()) {
                    $references[] = 'MRP Purchase Plans';
                }
                if (DB::table('gci_inventories')->where('gci_part_id', $gciPart->id)->exists()) {
                    $references[] = 'Inventory Records';
                }
                if (DB::table('bom_item_substitutes')->where('substitute_part_id', $gciPart->id)->exists()) {
                    $references[] = 'BOM Item Substitutes';
                }
                
                $msg = 'Cannot delete part "' . $gciPart->part_no . '" because it is still referenced by: ' 
                    . implode(', ', $references) 
                    . '. Please remove these references first or set the part status to inactive instead.';
                
                return back()->with('error', $msg);
            }
            
            // For other errors, show generic message
            return back()->with('error', 'Failed to delete part: ' . $e->getMessage());
        }
    }
}
