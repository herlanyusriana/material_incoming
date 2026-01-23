<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Exports\TrucksExport;
use App\Imports\TrucksImport;
use App\Models\Truck;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class TruckController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $trucks = Truck::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('plate_no', 'like', '%' . strtoupper($q) . '%')
                    ->orWhere('type', 'like', '%' . $q . '%')
                    ->orWhere('capacity', 'like', '%' . $q . '%');
            })
            ->orderBy('plate_no')
            ->paginate(50)
            ->withQueryString();

        return view('outgoing.trucks.index', compact('trucks', 'q'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'plate_no' => ['required', 'string', 'max:255', Rule::unique('trucks', 'plate_no')],
            'type' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['available', 'in-use', 'maintenance'])],
        ]);

        $validated['plate_no'] = strtoupper(trim($validated['plate_no']));

        Truck::create($validated);

        return back()->with('success', 'Truck created.');
    }

    public function update(Request $request, Truck $truck)
    {
        $validated = $request->validate([
            'plate_no' => ['required', 'string', 'max:255', Rule::unique('trucks', 'plate_no')->ignore($truck->id)],
            'type' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['available', 'in-use', 'maintenance'])],
        ]);

        $validated['plate_no'] = strtoupper(trim($validated['plate_no']));

        $truck->update($validated);

        return back()->with('success', 'Truck updated.');
    }

    public function destroy(Truck $truck)
    {
        $truck->delete();

        return back()->with('success', 'Truck deleted.');
    }

    public function template()
    {
        return Excel::download(new TrucksExport(template: true), 'trucks_template.xlsx');
    }

    public function export()
    {
        $filename = 'trucks_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new TrucksExport(), $filename);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new TrucksImport();
        Excel::import($import, $validated['file']);

        if (!empty($import->failures)) {
            $preview = array_slice($import->failures, 0, 10);
            $msg = implode(' ; ', $preview);
            if (count($import->failures) > 10) {
                $msg .= ' ; ... and ' . (count($import->failures) - 10) . ' more errors';
            }
            return back()->with('error', "Import selesai tapi ada error: {$msg}");
        }

        $msg = "Trucks imported. {$import->rowCount} rows processed.";
        if ($import->skippedRows > 0) {
            $msg .= " {$import->skippedRows} rows skipped.";
        }

        return back()->with('success', $msg);
    }
}

