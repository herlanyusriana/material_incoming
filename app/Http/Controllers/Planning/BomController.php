<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Exports\BomExport;
use App\Imports\BomImport;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\BomItemSubstitute;
use App\Imports\BomSubstituteImport;
use App\Imports\BomSubstituteMappingImport;
use App\Models\CustomerPart;
use App\Models\CustomerPartComponent;
use App\Exports\BomSubstitutesExport;
use App\Models\GciPart;
use App\Models\Uom;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class BomController extends Controller
{
    public function index(Request $request)
    {
        $gciPartId = $request->query('gci_part_id');
        $q = trim((string) $request->query('q', ''));

        $fgParts = GciPart::query()
            ->where('classification', 'FG')
            ->orderBy('part_no')
            ->get();
        $wipParts = GciPart::query()
            ->where('classification', 'WIP')
            ->orderBy('part_no')
            ->get();
        $rmParts = GciPart::query()
            ->where('classification', 'RM')
            ->orderBy('part_no')
            ->get();
        $makeParts = GciPart::query()
            ->whereIn('classification', ['FG', 'WIP'])
            ->orderBy('part_no')
            ->get();

        $uoms = Uom::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('code')
            ->get();

        $boms = Bom::query()
            ->with(['part', 'items.wipPart', 'items.componentPart', 'items.wipUom', 'items.consumptionUom', 'items.substitutes.part'])
            ->when($gciPartId, fn($q) => $q->where('part_id', $gciPartId))
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('part', function ($sub) use ($q) {
                    $sub->where('part_no', 'like', '%' . $q . '%')
                        ->orWhere('part_name', 'like', '%' . $q . '%');
                });
            })
            ->orderBy(GciPart::select('part_no')->whereColumn('gci_parts.id', 'boms.part_id'))
            ->paginate(20)
            ->withQueryString();

        return view('planning.boms.index', compact('boms', 'fgParts', 'wipParts', 'rmParts', 'makeParts', 'uoms', 'gciPartId', 'q'));
    }

    public function whereUsed(Request $request)
    {
        $validated = $request->validate([
            'part_no' => ['required', 'string'],
        ]);

        $partNo = strtoupper(trim($validated['part_no']));
        $boms = Bom::whereUsed($partNo);

        // For each BOM (FG part), find customer products that use it
        $results = $boms->map(function ($bom) {
            $customerProducts = CustomerPartComponent::query()
                ->with(['customerPart.customer'])
                ->where('gci_part_id', $bom->part_id)
                ->get()
                ->map(fn($comp) => [
                    'customer_part_no' => $comp->customerPart->customer_part_no,
                    'customer_part_name' => $comp->customerPart->customer_part_name,
                    'customer_name' => $comp->customerPart->customer->name ?? '-',
                    'usage_qty' => $comp->qty_per_unit,
                ]);

            return [
                'id' => $bom->id,
                'part_id' => $bom->part_id,
                'fg_part_no' => $bom->part->part_no,
                'fg_part_name' => $bom->part->part_name,
                'revision' => $bom->revision,
                'status' => $bom->status,
                'customer_products' => $customerProducts,
            ];
        });

        return response()->json([
            'part_no' => $partNo,
            'used_in' => $results,
        ]);
    }

    public function showWhereUsed()
    {
        // Get some recent RM parts for quick search
        $recentParts = GciPart::query()
            ->where('classification', 'RM')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        return view('planning.boms.where_used', compact('recentParts'));
    }

    public function exportSubstitutes()
    {
        $filename = 'bom_substitutes_' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new BomSubstitutesExport(), $filename);
    }

    public function export(Request $request)
    {
        $gciPartId = $request->query('gci_part_id');
        $q = trim((string) $request->query('q', ''));

        $filename = 'boms_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new BomExport($gciPartId, $q), $filename);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            $import = new BomImport();
            Excel::import($import, $validated['file']);

            $failures = collect($import->failures());
            if ($failures->isNotEmpty()) {
                $preview = $failures
                    ->take(5)
                    ->map(fn($f) => "Row {$f->row()}: " . implode(' | ', $f->errors()))
                    ->implode(' ; ');

                return back()->with('error', "Import selesai tapi ada {$failures->count()} baris gagal. {$preview}");
            }

            $count = $import->rowCount;
            $skipped = $import->skippedRows;
            $msg = "BOM imported successfully. {$count} rows processed.";
            if ($skipped > 0) {
                $msg .= " {$skipped} rows skipped (empty or missing part numbers).";
            }

            $missingFg = array_keys($import->missingFgParts ?? []);
            $missingComp = array_keys($import->missingComponentParts ?? []);
            $missingWip = array_keys($import->missingWipParts ?? []);
            if (!empty($missingComp) || !empty($missingWip) || !empty($missingFg)) {
                $parts = array_values(array_unique(array_filter(array_merge($missingFg, $missingComp, $missingWip))));
                $preview = implode(', ', array_slice($parts, 0, 15));
                $more = count($parts) > 15 ? (' … +' . (count($parts) - 15) . ' more') : '';
                $msg .= " Missing parts in GCI master: {$preview}{$more}.";
            }

            if (!empty($missingFg)) {
                return back()->with('error', $msg);
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                $failures = collect($e->failures());
                $preview = $failures
                    ->take(5)
                    ->map(fn($f) => "Row {$f->row()}: " . implode(' | ', $f->errors()))
                    ->implode(' ; ');

                return back()->with('error', "Import failed: {$preview}");
            }

            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function importSubstitutes(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'auto_create_parts' => ['nullable', 'boolean'],
        ]);

        try {
            // Default OFF: avoid creating new parts from import by accident.
            $import = new BomSubstituteImport((bool) ($validated['auto_create_parts'] ?? false));
            Excel::import($import, $validated['file']);

            $failures = collect($import->failures());
            if ($failures->isNotEmpty()) {
                $preview = $failures
                    ->take(5)
                    ->map(fn($f) => "Row {$f->row()}: " . implode(' | ', $f->errors()))
                    ->implode(' ; ');

                return back()->with('error', "Import substitutes selesai tapi ada {$failures->count()} baris gagal. {$preview}");
            }

            $count = $import->rowCount;
            $msg = "Substitutes imported successfully. {$count} rows processed.";
            $missing = array_values(array_unique(array_filter(array_merge(
                array_keys($import->missingFgParts ?? []),
                array_keys($import->missingComponentParts ?? []),
                array_keys($import->missingSubstituteParts ?? []),
            ))));
            if (!empty($missing)) {
                $preview = implode(', ', array_slice($missing, 0, 15));
                $more = count($missing) > 15 ? (' … +' . (count($missing) - 15) . ' more') : '';
                $msg .= " Missing parts in GCI master: {$preview}{$more}.";
            }
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                $failures = collect($e->failures());
                $preview = $failures
                    ->take(5)
                    ->map(fn($f) => "Row {$f->row()}: " . implode(' | ', $f->errors()))
                    ->implode(' ; ');

                return back()->with('error', "Import failed: {$preview}");
            }
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function importSubstitutesMapping(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'auto_create_parts' => ['nullable', 'boolean'],
        ]);

        try {
            // Default OFF: avoid creating new parts from import by accident.
            $import = new BomSubstituteMappingImport((bool) ($validated['auto_create_parts'] ?? false));
            Excel::import($import, $validated['file']);

            $failures = collect($import->failures());
            if ($failures->isNotEmpty()) {
                $preview = $failures
                    ->take(10)
                    ->map(fn($f) => "Row {$f->row()}: " . implode(' | ', $f->errors()))
                    ->implode(' ; ');

                return back()->with('error', "Import mapping selesai tapi ada {$failures->count()} baris gagal. {$preview}");
            }

            $msg = "Substitute mapping imported successfully. {$import->rowCount} rows processed.";
            $missing = array_values(array_unique(array_filter(array_merge(
                array_keys($import->missingComponentParts ?? []),
                array_keys($import->missingSubstituteParts ?? []),
            ))));
            if (!empty($missing)) {
                $preview = implode(', ', array_slice($missing, 0, 15));
                $more = count($missing) > 15 ? (' … +' . (count($missing) - 15) . ' more') : '';
                $msg .= " Missing parts in GCI master: {$preview}{$more}.";
            }
            return back()->with('success', $msg);
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                $failures = collect($e->failures());
                $preview = $failures
                    ->take(10)
                    ->map(fn($f) => "Row {$f->row()}: " . implode(' | ', $f->errors()))
                    ->implode(' ; ');

                return back()->with('error', "Import failed: {$preview}");
            }
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function downloadSubstituteTemplate()
    {
        return Excel::download(new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function array(): array
            {
                return [
                    // Example row
                    ['FG-001', 'FG PART NAME', 'COMP-001', 'COMPONENT PART NAME', 'SUB-001', 'SUBSTITUTE PART NAME', 1, 1, 'active', 'Optional note'],
                ];
            }
            public function headings(): array
            {
                return [
                    'fg_part_no',
                    'fg_part_name',
                    'component_part_no',
                    'component_part_name',
                    'substitute_part_no',
                    'substitute_part_name',
                    'ratio',
                    'priority',
                    'status',
                    'notes',
                ];
            }
        }, 'template_substitutes.xlsx');
    }

    public function downloadSubstituteMappingTemplate()
    {
        return Excel::download(new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function array(): array
            {
                return [
                    // Example row: component (RM) -> substitute (RM), optional supplier + notes
                    ['5040JA3071C', 'COMPONENT NAME HERE', 'KJPGICBOMG65S', 'SUBSTITUTE NAME HERE', 'PT. POSCO - INDONESIA JAKARTA PROCESSING CENTER', 1, 1, 'active', 'from incoming mapping'],
                ];
            }
            public function headings(): array
            {
                return [
                    'component_part_no',
                    'component_part_name',
                    'substitute_part_no',
                    'substitute_part_name',
                    'supplier',
                    'ratio',
                    'priority',
                    'status',
                    'notes',
                ];
            }
        }, 'template_substitute_mapping.xlsx');
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'part_id' => ['required', Rule::exists('gci_parts', 'id'), Rule::unique('boms', 'part_id')],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        Bom::create($validated);

        return back()->with('success', 'BOM created.');
    }

    public function update(Request $request, Bom $bom)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'part_id' => [
                'nullable',
                Rule::exists('gci_parts', 'id')->where(fn($q) => $q->where('classification', 'FG')),
                Rule::unique('boms', 'part_id')->ignore($bom->id),
            ],
        ]);

        $payload = [];
        if (array_key_exists('status', $validated) && $validated['status'] !== null) {
            $payload['status'] = $validated['status'];
        }
        if (array_key_exists('part_id', $validated) && $validated['part_id'] !== null) {
            $payload['part_id'] = (int) $validated['part_id'];
        }

        if ($payload === []) {
            return back()->with('error', 'No changes provided.');
        }

        $bom->update($payload);

        return back()->with('success', 'BOM updated.');
    }

    public function destroy(Bom $bom)
    {
        $bom->delete();

        return back()->with('success', 'BOM deleted.');
    }

    public function storeItem(Request $request, Bom $bom)
    {
        $validated = $request->validate([
            'bom_item_id' => ['nullable', 'integer'],
            'component_part_id' => ['nullable', Rule::exists('gci_parts', 'id')],
            'component_part_no' => ['nullable', 'string', 'max:100'],
            'make_or_buy' => ['nullable', Rule::in(['make', 'buy', 'free_issue'])],
            'usage_qty' => ['required', 'numeric', 'min:0'],
            'consumption_uom' => ['nullable', 'string', 'max:20'],
            'line_no' => ['nullable', 'integer', 'min:1'],
            'process_name' => ['nullable', 'string', 'max:255'],
            'machine_name' => ['nullable', 'string', 'max:255'],
            'wip_part_id' => ['nullable', Rule::exists('gci_parts', 'id')],
            'wip_part_no' => ['nullable', 'string', 'max:100'],
            'wip_qty' => ['nullable', 'numeric', 'min:0'],
            'wip_uom' => ['nullable', 'string', 'max:20'],
            'wip_part_name' => ['nullable', 'string', 'max:255'],
            'material_size' => ['nullable', 'string', 'max:255'],
            'material_spec' => ['nullable', 'string', 'max:255'],
            'material_name' => ['nullable', 'string', 'max:255'],
            'special' => ['nullable', 'string', 'max:255'],
            'scrap_factor' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'yield_factor' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'consumption_uom_id' => ['nullable', Rule::exists('uoms', 'id')],
            'wip_uom_id' => ['nullable', Rule::exists('uoms', 'id')],
        ]);

        $consumptionUomId = isset($validated['consumption_uom_id']) ? (int) ($validated['consumption_uom_id'] ?? 0) : 0;
        $wipUomId = isset($validated['wip_uom_id']) ? (int) ($validated['wip_uom_id'] ?? 0) : 0;

        $consumptionUomCode = isset($validated['consumption_uom']) && is_string($validated['consumption_uom'])
            ? strtoupper(trim($validated['consumption_uom']))
            : null;
        if ($consumptionUomCode === '') {
            $consumptionUomCode = null;
        }

        $wipUomCode = isset($validated['wip_uom']) && is_string($validated['wip_uom'])
            ? strtoupper(trim($validated['wip_uom']))
            : null;
        if ($wipUomCode === '') {
            $wipUomCode = null;
        }

        if ($consumptionUomId > 0) {
            $uom = Uom::query()->find($consumptionUomId);
            if ($uom) {
                $consumptionUomCode = $uom->code;
            }
        } elseif ($consumptionUomCode !== null) {
            $uom = Uom::query()->where('code', $consumptionUomCode)->first();
            if ($uom) {
                $consumptionUomId = (int) $uom->id;
            }
        }

        if ($wipUomId > 0) {
            $uom = Uom::query()->find($wipUomId);
            if ($uom) {
                $wipUomCode = $uom->code;
            }
        } elseif ($wipUomCode !== null) {
            $uom = Uom::query()->where('code', $wipUomCode)->first();
            if ($uom) {
                $wipUomId = (int) $uom->id;
            }
        }

        $componentPartId = array_key_exists('component_part_id', $validated) ? (int) ($validated['component_part_id'] ?? 0) : 0;
        $componentPartNo = array_key_exists('component_part_no', $validated) && is_string($validated['component_part_no'])
            ? strtoupper(trim($validated['component_part_no']))
            : null;
        if ($componentPartNo === '') {
            $componentPartNo = null;
        }

        if ($componentPartId <= 0 && !$componentPartNo) {
            return back()->withInput()->withErrors([
                'component_part_id' => 'Component Part wajib diisi (pilih dari list atau isi Part No).',
            ]);
        }

        $payload = [
            'usage_qty' => $validated['usage_qty'],
            'consumption_uom' => $consumptionUomCode,
            'line_no' => $validated['line_no'] ?? null,
            'process_name' => $validated['process_name'] ?? null,
            'machine_name' => $validated['machine_name'] ?? null,
            'wip_part_id' => $validated['wip_part_id'] ?? null,
            'wip_part_no' => $validated['wip_part_no'] ?? null,
            'wip_qty' => $validated['wip_qty'] ?? null,
            'wip_uom' => $wipUomCode,
            'wip_part_name' => $validated['wip_part_name'] ?? null,
            'material_size' => $validated['material_size'] ?? null,
            'material_spec' => $validated['material_spec'] ?? null,
            'material_name' => $validated['material_name'] ?? null,
            'special' => $validated['special'] ?? null,
            'make_or_buy' => $validated['make_or_buy'] ?? 'buy',
            'component_part_no' => $componentPartNo,
            'scrap_factor' => $validated['scrap_factor'] ?? 0,
            'yield_factor' => $validated['yield_factor'] ?? 1,
            'consumption_uom_id' => $consumptionUomId > 0 ? $consumptionUomId : null,
            'wip_uom_id' => $wipUomId > 0 ? $wipUomId : null,
        ];

        $bomItemId = isset($validated['bom_item_id']) ? (int) $validated['bom_item_id'] : null;
        if ($bomItemId) {
            $item = BomItem::query()
                ->where('bom_id', $bom->id)
                ->where('id', $bomItemId)
                ->firstOrFail();

            // If the edit form didn't provide any UOM selection, preserve existing values.
            // This avoids accidentally clearing legacy string-based UOMs when *_uom_id is empty.
            if ($consumptionUomId <= 0 && $consumptionUomCode === null) {
                unset($payload['consumption_uom'], $payload['consumption_uom_id']);
            }
            if ($wipUomId <= 0 && $wipUomCode === null) {
                unset($payload['wip_uom'], $payload['wip_uom_id']);
            }

            $item->update(array_merge($payload, [
                'component_part_id' => $componentPartId > 0 ? $componentPartId : null,
            ]));

            return back()->with('success', 'BOM line updated.');
        }

        if ($payload['line_no'] === null) {
            $next = (int) (BomItem::query()->where('bom_id', $bom->id)->max('line_no') ?? 0) + 1;
            $payload['line_no'] = $next > 0 ? $next : 1;
        }

        BomItem::create(array_merge($payload, [
            'bom_id' => $bom->id,
            'component_part_id' => $componentPartId > 0 ? $componentPartId : null,
        ]));

        return back()->with('success', 'BOM line added.');
    }

    public function destroyItem(BomItem $bomItem)
    {
        $bomItem->delete();

        return back()->with('success', 'BOM item removed.');
    }

    public function storeSubstitute(Request $request, BomItem $bomItem)
    {
        $validated = $request->validate([
            'substitute_part_id' => ['required', Rule::exists('gci_parts', 'id')],
            'ratio' => ['nullable', 'numeric', 'min:0.0001'],
            'priority' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        BomItemSubstitute::updateOrCreate(
            [
                'bom_item_id' => $bomItem->id,
                'substitute_part_id' => (int) $validated['substitute_part_id'],
            ],
            [
                'substitute_part_no' => GciPart::find($validated['substitute_part_id'])->part_no,
                'ratio' => $validated['ratio'] ?? 1,
                'priority' => $validated['priority'] ?? 1,
                'status' => $validated['status'] ?? 'active',
                'notes' => $validated['notes'] ? trim((string) $validated['notes']) : null,
            ],
        );

        return back()->with('success', 'Substitute saved.');
    }

    public function destroySubstitute(BomItemSubstitute $substitute)
    {
        $substitute->delete();

        return back()->with('success', 'Substitute removed.');
    }

    public function explosion(Request $request, Bom $bom = null)
    {
        $searchMode = $request->query('mode', 'fg'); // 'fg' or 'customer'
        $searchQuery = $request->query('search');
        $quantity = (float) ($request->query('qty', 1));
        if ($quantity <= 0) {
            $quantity = 1;
        }

        $customerPart = null;
        $customerPartComponents = collect();

        // If searching by customer part
        if ($searchMode === 'customer' && $searchQuery) {
            $customerPart = CustomerPart::query()
                ->with(['customer', 'components.part.bom'])
                ->where(function ($query) use ($searchQuery) {
                    $query->where('customer_part_no', 'like', '%' . $searchQuery . '%')
                        ->orWhere('customer_part_name', 'like', '%' . $searchQuery . '%');
                })
                ->first();

            if (!$customerPart) {
                return back()->with('error', 'Customer part not found: ' . $searchQuery);
            }

            $customerPartComponents = $customerPart->components;

            // If customer part has only one FG component, auto-select it
            if ($customerPartComponents->count() === 1) {
                $component = $customerPartComponents->first();
                if ($component->part && $component->part->bom) {
                    $bom = $component->part->bom;
                }
            }
        }

        // If searching by FG part
        if ($searchMode === 'fg' && $searchQuery && !$bom) {
            $fgPart = GciPart::query()
                ->with('bom')
                ->where(function ($query) use ($searchQuery) {
                    $query->where('part_no', 'like', '%' . $searchQuery . '%')
                        ->orWhere('part_name', 'like', '%' . $searchQuery . '%');
                })
                ->first();

            if ($fgPart && $fgPart->bom) {
                $bom = $fgPart->bom;
            } elseif ($fgPart) {
                return back()->with('error', 'Part found but no BOM exists: ' . $fgPart->part_no);
            } else {
                return back()->with('error', 'Part not found: ' . $searchQuery);
            }
        }

        // If a specific BOM was found/passed, load its specific explosion
        if ($bom) {
            $bom->loadMissing(['part', 'items.componentPart', 'items.wipPart', 'items.consumptionUom', 'items.wipUom']);
            $explosion = $bom->explode($quantity);
            $materials = $bom->getTotalMaterialRequirements($quantity);
        }

        // If no BOM found yet, return to search/overview
        if (!$bom) {
            return view('planning.boms.explosion', [
                'bom' => null,
                'explosion' => [],
                'materials' => [],
                'quantity' => $quantity,
                'searchMode' => $searchMode,
                'searchQuery' => $searchQuery,
                'customerPart' => $customerPart,
                'customerPartComponents' => $customerPartComponents,
            ]);
        }

        $bom->loadMissing(['part', 'items.componentPart', 'items.wipPart', 'items.consumptionUom', 'items.wipUom']);

        $explosion = $bom->explode($quantity);
        $materials = $bom->getTotalMaterialRequirements($quantity);

        return view('planning.boms.explosion', compact(
            'bom',
            'explosion',
            'materials',
            'quantity',
            'searchMode',
            'searchQuery',
            'customerPart',
            'customerPartComponents'
        ));
    }
}
