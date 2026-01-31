<?php

namespace App\Http\Controllers;

use App\Exports\WarehouseLocationsExport;
use App\Imports\WarehouseLocationsImport;
use App\Models\WarehouseLocation;
use App\Support\QrSvg;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseLocationController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $class = trim((string) $request->query('class', ''));
        $zone = trim((string) $request->query('zone', ''));
        $status = trim((string) $request->query('status', ''));

        $locations = WarehouseLocation::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('location_code', 'like', '%' . strtoupper($search) . '%')
                        ->orWhere('qr_payload', 'like', '%' . $search . '%');
                });
            })
            ->when($class !== '', fn($q) => $q->where('class', strtoupper($class)))
            ->when($zone !== '', fn($q) => $q->where('zone', strtoupper($zone)))
            ->when($status !== '', fn($q) => $q->where('status', strtoupper($status)))
            ->orderBy('location_code')
            ->paginate(25)
            ->withQueryString();

        return view('inventory.locations', compact('locations', 'search', 'class', 'zone', 'status'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_code' => ['required', 'string', 'max:50', Rule::unique('warehouse_locations', 'location_code')],
            'class' => ['nullable', 'string', 'max:50'],
            'zone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $locationCode = strtoupper(trim($validated['location_code']));
        $class = array_key_exists('class', $validated) ? strtoupper(trim((string) ($validated['class'] ?? ''))) : null;
        $zone = array_key_exists('zone', $validated) ? strtoupper(trim((string) ($validated['zone'] ?? ''))) : null;
        $status = array_key_exists('status', $validated) ? strtoupper(trim((string) ($validated['status'] ?? 'ACTIVE'))) : 'ACTIVE';

        $class = $class === '' ? null : $class;
        $zone = $zone === '' ? null : $zone;
        $status = $status === '' ? 'ACTIVE' : $status;

        WarehouseLocation::create([
            'location_code' => $locationCode,
            'class' => $class,
            'zone' => $zone,
            'status' => $status,
            'qr_payload' => WarehouseLocation::buildPayload($locationCode, $class, $zone),
        ]);

        return back()->with('success', 'Warehouse location created.');
    }

    public function update(Request $request, WarehouseLocation $location)
    {
        $validated = $request->validate([
            'location_code' => ['required', 'string', 'max:50', Rule::unique('warehouse_locations', 'location_code')->ignore($location->id)],
            'class' => ['nullable', 'string', 'max:50'],
            'zone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $locationCode = strtoupper(trim($validated['location_code']));
        $class = array_key_exists('class', $validated) ? strtoupper(trim((string) ($validated['class'] ?? ''))) : null;
        $zone = array_key_exists('zone', $validated) ? strtoupper(trim((string) ($validated['zone'] ?? ''))) : null;
        $status = array_key_exists('status', $validated) ? strtoupper(trim((string) ($validated['status'] ?? 'ACTIVE'))) : 'ACTIVE';

        $class = $class === '' ? null : $class;
        $zone = $zone === '' ? null : $zone;
        $status = $status === '' ? 'ACTIVE' : $status;

        $location->update([
            'location_code' => $locationCode,
            'class' => $class,
            'zone' => $zone,
            'status' => $status,
            'qr_payload' => WarehouseLocation::buildPayload($locationCode, $class, $zone),
        ]);

        return back()->with('success', 'Warehouse location updated.');
    }

    public function destroy(WarehouseLocation $location)
    {
        $location->delete();

        return back()->with('success', 'Warehouse location deleted.');
    }

    public function export()
    {
        $filename = 'warehouse_locations_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new WarehouseLocationsExport(), $filename);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new WarehouseLocationsImport(), $validated['file']);

        return back()->with('success', 'Warehouse locations imported.');
    }

    public function printQr(WarehouseLocation $location)
    {
        $payload = (string) ($location->qr_payload ?? '');
        if (trim($payload) === '') {
            $payload = WarehouseLocation::buildPayload($location->location_code, $location->class, $location->zone);
        }

        $qrSvg = QrSvg::make($payload, 260, 0);

        return view('inventory.location_qr', compact('location', 'qrSvg', 'payload'));
    }

    public function printMap()
    {
        return view('inventory.location_map_print');
    }

    public function printRange(Request $request)
    {
        $validated = $request->validate([
            'start' => ['nullable', 'string', 'max:50'],
            'end' => ['nullable', 'string', 'max:50'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $start = strtoupper(trim((string) ($validated['start'] ?? '')));
        $end = strtoupper(trim((string) ($validated['end'] ?? '')));
        $limit = max(1, min(50, (int) ($validated['limit'] ?? 20)));

        $query = WarehouseLocation::query();
        if ($start !== '' && $end !== '') {
            if ($start <= $end) {
                $query->whereBetween('location_code', [$start, $end]);
            } else {
                $query->whereBetween('location_code', [$end, $start]);
            }
        } elseif ($start !== '') {
            $query->where('location_code', '>=', $start);
        } elseif ($end !== '') {
            $query->where('location_code', '<=', $end);
        }

        $locations = $query->orderBy('location_code')->limit($limit)->get();

        $cards = $locations->map(function (WarehouseLocation $location) {
            $payload = trim((string) ($location->qr_payload ?? ''));
            if ($payload === '') {
                $payload = WarehouseLocation::buildPayload($location->location_code, $location->class, $location->zone);
            }
            return [
                'location' => $location,
                'payload' => $payload,
                'qrSvg' => QrSvg::make($payload, 260, 0),
            ];
        })->all();

        return view('inventory.location_qr_batch', [
            'cards' => $cards,
        ]);
    }
}
