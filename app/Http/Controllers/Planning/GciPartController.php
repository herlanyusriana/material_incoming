<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\GciPart;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GciPartController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $parts = GciPart::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderBy('part_no')
            ->paginate(25)
            ->withQueryString();

        return view('planning.gci_parts.index', compact('parts', 'status'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'part_no' => ['required', 'string', 'max:100', Rule::unique('gci_parts', 'part_no')],
            'part_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validated['part_no'] = strtoupper(trim($validated['part_no']));
        $validated['part_name'] = $validated['part_name'] ? trim($validated['part_name']) : null;

        GciPart::create($validated);

        return back()->with('success', 'Part GCI created.');
    }

    public function update(Request $request, GciPart $gciPart)
    {
        $validated = $request->validate([
            'part_no' => ['required', 'string', 'max:100', Rule::unique('gci_parts', 'part_no')->ignore($gciPart->id)],
            'part_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validated['part_no'] = strtoupper(trim($validated['part_no']));
        $validated['part_name'] = $validated['part_name'] ? trim($validated['part_name']) : null;

        $gciPart->update($validated);

        return back()->with('success', 'Part GCI updated.');
    }

    public function destroy(GciPart $gciPart)
    {
        $gciPart->delete();

        return back()->with('success', 'Part GCI deleted.');
    }
}
