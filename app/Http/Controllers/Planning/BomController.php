<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Exports\BomExport;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\GciPart;
use App\Models\Part;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class BomController extends Controller
{
    public function index(Request $request)
    {
        $gciPartId = $request->query('gci_part_id');
        $q = trim((string) $request->query('q', ''));

        $gciParts = GciPart::query()->orderBy('part_no')->get();
        $components = Part::query()->orderBy('part_no')->get();

        $boms = Bom::query()
            ->with(['part', 'items.componentPart'])
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

        return view('planning.boms.index', compact('boms', 'gciParts', 'components', 'gciPartId', 'q'));
    }

    public function export(Request $request)
    {
        $gciPartId = $request->query('gci_part_id');
        $q = trim((string) $request->query('q', ''));

        $filename = 'boms_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new BomExport($gciPartId, $q), $filename);
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
            'component_part_id' => ['required', Rule::exists('parts', 'id')],
            'usage_qty' => ['required', 'numeric', 'min:0.0001'],
        ]);

        BomItem::updateOrCreate(
            ['bom_id' => $bom->id, 'component_part_id' => (int) $validated['component_part_id']],
            ['usage_qty' => $validated['usage_qty']],
        );

        return back()->with('success', 'BOM item saved.');
    }

    public function destroyItem(BomItem $bomItem)
    {
        $bomItem->delete();

        return back()->with('success', 'BOM item removed.');
    }
}
