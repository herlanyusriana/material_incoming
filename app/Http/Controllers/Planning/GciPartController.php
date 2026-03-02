<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Exports\GciPartsExport;
use App\Imports\GciPartsImport;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\GciPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class GciPartController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $classification = $request->query('classification');
        $limit = (int) $request->query('limit', 20);

        if (mb_strlen($q) < 2 && !$request->has('all')) {
            return response()->json([]);
        }

        $query = GciPart::query()
            ->select(['id', 'part_no', 'part_name', 'model', 'classification'])
            ->when($classification, function ($qr) use ($classification) {
                if (is_array($classification)) {
                    $qr->whereIn('classification', $classification);
                } else {
                    $qr->where('classification', $classification);
                }
            })
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($inner) use ($q) {
                    $inner->where('part_no', 'like', '%' . $q . '%')
                        ->orWhere('part_name', 'like', '%' . $q . '%')
                        ->orWhere('model', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('part_no')
            ->limit($limit);

        return response()->json($query->get());
    }
    
    public function getBomInfo(GciPart $gciPart)
    {
        $bom = \App\Models\Bom::where('part_id', $gciPart->id)->latest()->first();
        
        if (!$bom) {
            return response()->json(['success' => false, 'message' => 'No BOM found']);
        }
        
        // Try to get from first WIP item, otherwise from first item
        $bomItems = $bom->items()->orderBy('line_no')->get();
        $targetItem = $bomItems->firstWhere('wip_part_id', '!=', null) ?? $bomItems->first();
        
        if (!$targetItem) {
            return response()->json(['success' => false, 'message' => 'No BOM items found']);
        }
        
        return response()->json([
            'success' => true,
            'bom' => [
                'process_name' => $targetItem->process_name,
                'machine_id' => $targetItem->machine_id,
            ]
        ]);
    }

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

        // FG parts that have a BOM (for RM destination field)
        $fgPartsWithBom = GciPart::where('classification', 'FG')
            ->whereHas('bom')
            ->orderBy('part_no')
            ->get(['id', 'part_no', 'part_name']);

        // RM → linked FG IDs mapping (for edit pre-populate)
        $rmFgMap = [];
        if ($classification === 'RM') {
            $rmIds = $parts->pluck('id')->toArray();
            if (!empty($rmIds)) {
                $links = BomItem::whereIn('component_part_id', $rmIds)
                    ->whereHas('bom')
                    ->with('bom:id,part_id')
                    ->get(['id', 'bom_id', 'component_part_id']);
                foreach ($links as $link) {
                    $rmFgMap[$link->component_part_id][] = $link->bom->part_id;
                }
            }
        }

        return view('planning.gci_parts.index', compact('parts', 'status', 'classification', 'customers', 'qParam', 'fgPartsWithBom', 'rmFgMap'));
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

            DB::transaction(function () use ($import, $request) {
                Excel::import($import, $request->file('file'));
            });

            $parts = [];
            if ($import->createdCount > 0) {
                $parts[] = "{$import->createdCount} created";
            }
            if ($import->updatedCount > 0) {
                $parts[] = "{$import->updatedCount} updated";
            }
            $msg = 'Part GCI imported successfully. ' . implode(', ', $parts) . '.';

            if ($import->substituteCount > 0) {
                $msg .= " {$import->substituteCount} substitutes processed.";
            }

            $missingComp = array_keys($import->missingComponentParts);
            $missingSub = array_keys($import->missingSubstituteParts);
            if (!empty($missingComp) || !empty($missingSub)) {
                $allMissing = array_unique(array_merge($missingComp, $missingSub));
                $preview = implode(', ', array_slice($allMissing, 0, 10));
                $more = count($allMissing) > 10 ? (' … +' . (count($allMissing) - 10) . ' more') : '';
                $msg .= " Missing parts for substitutes: {$preview}{$more}.";
            }

            return back()->with('success', $msg);
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
            'destination_fg_ids' => ['nullable', 'array'],
            'destination_fg_ids.*' => ['exists:gci_parts,id'],
        ]);

        $destinationFgIds = $validated['destination_fg_ids'] ?? [];
        unset($validated['destination_fg_ids']);

        $validated['part_no'] = strtoupper(trim($validated['part_no']));
        $validated['classification'] = strtoupper(trim($validated['classification']));
        $validated['part_name'] = $validated['part_name'] ? trim($validated['part_name']) : null;
        $validated['model'] = $validated['model'] ? trim($validated['model']) : null;

        // Clear model for RM parts
        if ($validated['classification'] === 'RM') {
            $validated['model'] = null;
        }

        if (!$request->boolean('confirm_duplicate')) {
            if (GciPart::where('part_no', $validated['part_no'])->exists()) {
                return back()
                    ->withInput()
                    ->with('duplicate_warning_data', $request->all())
                    ->with('error', "Part number '{$validated['part_no']}' already exists. Please confirm to proceed.");
            }
        }

        $gciPart = GciPart::create($validated);

        // Auto-link RM to FG BOMs
        $bomLinked = 0;
        if ($validated['classification'] === 'RM' && !empty($destinationFgIds)) {
            foreach ($destinationFgIds as $fgId) {
                $bom = Bom::where('part_id', $fgId)->first();
                if ($bom) {
                    $nextLine = ($bom->items()->max('line_no') ?? 0) + 1;
                    BomItem::create([
                        'bom_id' => $bom->id,
                        'component_part_id' => $gciPart->id,
                        'component_part_no' => $gciPart->part_no,
                        'line_no' => $nextLine,
                        'usage_qty' => 1,
                        'make_or_buy' => 'buy',
                    ]);
                    $bomLinked++;
                }
            }
        }

        $msg = 'Part GCI created.';
        if ($bomLinked > 0) {
            $msg .= " Linked to {$bomLinked} FG BOM(s).";
        }

        return back()->with('success', $msg);
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
            'destination_fg_ids' => ['nullable', 'array'],
            'destination_fg_ids.*' => ['exists:gci_parts,id'],
        ]);

        $destinationFgIds = $validated['destination_fg_ids'] ?? [];
        unset($validated['destination_fg_ids']);

        $validated['part_no'] = strtoupper(trim($validated['part_no']));
        $validated['classification'] = strtoupper(trim($validated['classification']));
        $validated['part_name'] = $validated['part_name'] ? trim($validated['part_name']) : null;
        $validated['model'] = $validated['model'] ? trim($validated['model']) : null;

        if ($validated['classification'] === 'RM') {
            $validated['model'] = null;
        }

        $gciPart->update($validated);

        // Auto-link RM to new FG BOMs (skip already linked)
        $bomLinked = 0;
        if ($validated['classification'] === 'RM' && !empty($destinationFgIds)) {
            $existingBomIds = BomItem::where('component_part_id', $gciPart->id)
                ->pluck('bom_id')->toArray();

            foreach ($destinationFgIds as $fgId) {
                $bom = Bom::where('part_id', $fgId)->first();
                if ($bom && !in_array($bom->id, $existingBomIds)) {
                    $nextLine = ($bom->items()->max('line_no') ?? 0) + 1;
                    BomItem::create([
                        'bom_id' => $bom->id,
                        'component_part_id' => $gciPart->id,
                        'component_part_no' => $gciPart->part_no,
                        'line_no' => $nextLine,
                        'usage_qty' => 1,
                        'make_or_buy' => 'buy',
                    ]);
                    $bomLinked++;
                }
            }
        }

        $msg = 'Part GCI updated.';
        if ($bomLinked > 0) {
            $msg .= " Linked to {$bomLinked} new FG BOM(s).";
        }

        return back()->with('success', $msg);
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
                if (DB::table('customer_part_components')->where('gci_part_id', $gciPart->id)->exists()) {
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
