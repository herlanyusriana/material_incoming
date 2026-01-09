<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Exports\BomExport;
use App\Imports\BomImport;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\GciPart;
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

        $gciParts = GciPart::query()->orderBy('part_no')->get();
        $wipParts = $gciParts;
        $components = $gciParts;

        $boms = Bom::query()
            ->with(['part', 'items.wipPart', 'items.componentPart'])
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

        return view('planning.boms.index', compact('boms', 'gciParts', 'wipParts', 'components', 'gciPartId', 'q'));
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
            'usage_qty' => ['required', 'numeric', 'min:0.0001'],
            'consumption_uom' => ['nullable', 'string', 'max:20'],
            'line_no' => ['nullable', 'integer', 'min:1'],
            'process_name' => ['nullable', 'string', 'max:255'],
            'machine_name' => ['nullable', 'string', 'max:255'],
            'wip_part_id' => ['nullable', Rule::exists('gci_parts', 'id')],
            'wip_qty' => ['nullable', 'numeric', 'min:0'],
            'wip_uom' => ['nullable', 'string', 'max:20'],
            'wip_part_name' => ['nullable', 'string', 'max:255'],
            'material_size' => ['nullable', 'string', 'max:255'],
            'material_spec' => ['nullable', 'string', 'max:255'],
            'material_name' => ['nullable', 'string', 'max:255'],
            'special' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'usage_qty' => $validated['usage_qty'],
            'consumption_uom' => $validated['consumption_uom'] ?? null,
            'line_no' => $validated['line_no'] ?? null,
            'process_name' => $validated['process_name'] ?? null,
            'machine_name' => $validated['machine_name'] ?? null,
            'wip_part_id' => $validated['wip_part_id'] ?? null,
            'wip_qty' => $validated['wip_qty'] ?? null,
            'wip_uom' => $validated['wip_uom'] ?? null,
            'wip_part_name' => $validated['wip_part_name'] ?? null,
            'material_size' => $validated['material_size'] ?? null,
            'material_spec' => $validated['material_spec'] ?? null,
            'material_name' => $validated['material_name'] ?? null,
            'special' => $validated['special'] ?? null,
        ];

        $bomItemId = isset($validated['bom_item_id']) ? (int) $validated['bom_item_id'] : null;
        if ($bomItemId) {
            $item = BomItem::query()
                ->where('bom_id', $bom->id)
                ->where('id', $bomItemId)
                ->firstOrFail();

            $item->update(array_merge($payload, [
                'component_part_id' => (int) $validated['component_part_id'],
            ]));

            return back()->with('success', 'BOM line updated.');
        }

        if ($payload['line_no'] === null) {
            $next = (int) (BomItem::query()->where('bom_id', $bom->id)->max('line_no') ?? 0) + 1;
            $payload['line_no'] = $next > 0 ? $next : 1;
        }

        BomItem::create(array_merge($payload, [
            'bom_id' => $bom->id,
            'component_part_id' => (int) $validated['component_part_id'],
        ]));

        return back()->with('success', 'BOM line added.');
    }

    public function destroyItem(BomItem $bomItem)
    {
        $bomItem->delete();

        return back()->with('success', 'BOM item removed.');
    }
}
