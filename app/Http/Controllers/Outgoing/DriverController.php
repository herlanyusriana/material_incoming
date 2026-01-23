<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Exports\DriversExport;
use App\Imports\DriversImport;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $drivers = Driver::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('phone', 'like', '%' . $q . '%')
                    ->orWhere('license_type', 'like', '%' . $q . '%');
            })
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('outgoing.drivers.index', compact('drivers', 'q'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'license_type' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['available', 'on-delivery', 'off'])],
        ]);

        Driver::create($validated);

        return back()->with('success', 'Driver created.');
    }

    public function update(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'license_type' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['available', 'on-delivery', 'off'])],
        ]);

        $driver->update($validated);

        return back()->with('success', 'Driver updated.');
    }

    public function destroy(Driver $driver)
    {
        $driver->delete();

        return back()->with('success', 'Driver deleted.');
    }

    public function template()
    {
        return Excel::download(new DriversExport(template: true), 'drivers_template.xlsx');
    }

    public function export()
    {
        $filename = 'drivers_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new DriversExport(), $filename);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new DriversImport();
        Excel::import($import, $validated['file']);

        if (!empty($import->failures)) {
            $preview = array_slice($import->failures, 0, 10);
            $msg = implode(' ; ', $preview);
            if (count($import->failures) > 10) {
                $msg .= ' ; ... and ' . (count($import->failures) - 10) . ' more errors';
            }
            return back()->with('error', "Import selesai tapi ada error: {$msg}");
        }

        $msg = "Drivers imported. {$import->rowCount} rows processed.";
        if ($import->skippedRows > 0) {
            $msg .= " {$import->skippedRows} rows skipped.";
        }

        return back()->with('success', $msg);
    }
}

