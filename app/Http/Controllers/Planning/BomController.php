<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Exports\BomExport;
use App\Imports\BomImport;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\BomItemSubstitute;
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
            ->with(['part', 'items.wipPart', 'items.componentPart', 'items.substitutes.part'])
            ->when($gciPartId, fn ($q) => $q->where('part_id', $gciPartId))
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
            $customerProducts = \App\Models\CustomerPartComponent::query()
                ->with(['customerPart.customer'])
                ->where('part_id', $bom->part_id)
                ->get()
                ->map(fn ($comp) => [
                    'customer_part_no' => $comp->customerPart->customer_part_no,
                    'customer_part_name' => $comp->customerPart->customer_part_name,
                    'customer_name' => $comp->customerPart->customer->name ?? '-',
                    'usage_qty' => $comp->usage_qty,
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
        $recentParts = \App\Models\GciPart::query()
            ->where('classification', 'RM')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        return view('planning.boms.where_used', compact('recentParts'));
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
                    ->map(fn ($f) => "Row {$f->row()}: " . implode(' | ', $f->errors()))
                    ->implode(' ; ');

                return back()->with('error', "Import selesai tapi ada {$failures->count()} baris gagal. {$preview}");
            }

            return back()->with('success', 'BOM imported.');
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
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $bom->update($validated);

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
            'component_part_id' => ['required', Rule::exists('gci_parts', 'id')],
            'make_or_buy' => ['nullable', Rule::in(['make', 'buy'])],
            'usage_qty' => ['required', 'numeric', 'min:0.0001'],
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
            'component_part_no' => ['nullable', 'string', 'max:100'],
            'scrap_factor' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'yield_factor' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'consumption_uom_id' => ['nullable', Rule::exists('uoms', 'id')],
            'wip_uom_id' => ['nullable', Rule::exists('uoms', 'id')],
        ]);

        $payload = [
            'usage_qty' => $validated['usage_qty'],
            'consumption_uom' => $validated['consumption_uom'] ?? null,
            'line_no' => $validated['line_no'] ?? null,
            'process_name' => $validated['process_name'] ?? null,
            'machine_name' => $validated['machine_name'] ?? null,
            'wip_part_id' => $validated['wip_part_id'] ?? null,
            'wip_part_no' => $validated['wip_part_no'] ?? null,
            'wip_qty' => $validated['wip_qty'] ?? null,
            'wip_uom' => $validated['wip_uom'] ?? null,
            'wip_part_name' => $validated['wip_part_name'] ?? null,
            'material_size' => $validated['material_size'] ?? null,
            'material_spec' => $validated['material_spec'] ?? null,
            'material_name' => $validated['material_name'] ?? null,
            'special' => $validated['special'] ?? null,
            'make_or_buy' => $validated['make_or_buy'] ?? 'buy',
            'component_part_no' => $validated['component_part_no'] ?? null,
            'scrap_factor' => $validated['scrap_factor'] ?? 0,
            'yield_factor' => $validated['yield_factor'] ?? 1,
            'consumption_uom_id' => $validated['consumption_uom_id'] ?? null,
            'wip_uom_id' => $validated['wip_uom_id'] ?? null,
        ];

        $bomItemId = isset($validated['bom_item_id']) ? (int) $validated['bom_item_id'] : null;
        if ($bomItemId) {
            $item = BomItem::query()
                ->where('bom_id', $bom->id)
                ->where('id', $bomItemId)
                ->firstOrFail();

            $item->update(array_merge($payload, [
                'component_part_id' => $validated['component_part_id'] ? (int) $validated['component_part_id'] : null,
            ]));

            return back()->with('success', 'BOM line updated.');
        }

        if ($payload['line_no'] === null) {
            $next = (int) (BomItem::query()->where('bom_id', $bom->id)->max('line_no') ?? 0) + 1;
            $payload['line_no'] = $next > 0 ? $next : 1;
        }

        BomItem::create(array_merge($payload, [
            'bom_id' => $bom->id,
            'component_part_id' => $validated['component_part_id'] ? (int) $validated['component_part_id'] : null,
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
            $customerPart = \App\Models\CustomerPart::query()
                ->with(['customer', 'components.part.bom'])
                ->where('customer_part_no', 'like', '%' . $searchQuery . '%')
                ->orWhere('customer_part_name', 'like', '%' . $searchQuery . '%')
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

        // If no BOM found yet, return to search
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
