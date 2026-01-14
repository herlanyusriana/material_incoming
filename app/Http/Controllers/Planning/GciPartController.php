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
        $classification = $request->query('classification');

        $parts = GciPart::query()
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($classification, fn ($q) => $q->where('classification', strtoupper($classification)))
            ->orderBy('part_no')
            ->paginate(25)
            ->withQueryString();

        return view('planning.gci_parts.index', compact('parts', 'status', 'classification'));
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
            'part_no' => ['required', 'string', 'max:100', Rule::unique('gci_parts', 'part_no')],
            'classification' => ['nullable', Rule::in(['FG'])],
            'part_name' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validated['part_no'] = strtoupper(trim($validated['part_no']));
        $validated['classification'] = 'FG';
        $validated['part_name'] = $validated['part_name'] ? trim($validated['part_name']) : null;
        $validated['model'] = $validated['model'] ? trim($validated['model']) : null;

        GciPart::create($validated);

        return back()->with('success', 'Part GCI created.');
    }

    public function update(Request $request, GciPart $gciPart)
    {
        $validated = $request->validate([
            'part_no' => ['required', 'string', 'max:100', Rule::unique('gci_parts', 'part_no')->ignore($gciPart->id)],
            'classification' => ['nullable', Rule::in(['FG'])],
            'part_name' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validated['part_no'] = strtoupper(trim($validated['part_no']));
        $validated['classification'] = 'FG';
        $validated['part_name'] = $validated['part_name'] ? trim($validated['part_name']) : null;
        $validated['model'] = $validated['model'] ? trim($validated['model']) : null;

        $gciPart->update($validated);

        return back()->with('success', 'Part GCI updated.');
    }

    public function destroy(GciPart $gciPart)
    {
        $gciPart->delete();

        return back()->with('success', 'Part GCI deleted.');
    }
}
