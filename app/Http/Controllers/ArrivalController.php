<?php

namespace App\Http\Controllers;

use App\Models\Arrival;
use App\Models\Part;
use App\Models\Vendor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ArrivalController extends Controller
{
    public function index()
    {
        $departures = Arrival::with(['vendor', 'creator', 'items.receives'])
            ->latest()
            ->paginate(10);

        return view('arrivals.index', ['departures' => $departures]);
    }

    public function create()
    {
        $vendors = Vendor::orderBy('vendor_name')->get();
        $parts = Part::with('vendor')->where('status', 'active')->get();
        $truckings = \App\Models\Trucking::where('status', 'active')->orderBy('company_name')->get();

        return view('arrivals.create', compact('vendors', 'parts', 'truckings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_no' => ['required', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'vendor_name' => ['nullable', 'string'], // Allow vendor_name but not required
            'vessel' => ['nullable', 'string', 'max:255'],
            'trucking_company_id' => ['nullable', 'exists:trucking_companies,id'],
            'etd' => ['nullable', 'date'],
            'eta' => ['nullable', 'date'],
            'bl_no' => ['nullable', 'string', 'max:255'],
            'hs_code' => ['nullable', 'string', 'max:255'],
            'port_of_loading' => ['nullable', 'string', 'max:255'],
            'container_numbers' => ['nullable', 'string'],
            'currency' => ['required', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_group' => ['nullable', 'string', 'max:255'],
            'items.*.part_id' => ['required', 'exists:parts,id'],
            'items.*.size' => ['nullable', 'string', 'max:100', 'regex:/^\d{1,4}(\.\d{1,2})?\s*x\s*\d{1,4}(\.\d)?\s*x\s*[A-Z]$/i'],
            'items.*.qty_bundle' => ['nullable', 'integer', 'min:0'],
            'items.*.unit_bundle' => ['nullable', 'string', 'max:20'],
            'items.*.qty_goods' => ['required', 'integer', 'min:1'],
            'items.*.weight_nett' => ['required', 'numeric', 'min:0'],
            'items.*.unit_weight' => ['nullable', 'string', 'max:20'],
            'items.*.weight_gross' => ['required', 'numeric', 'min:0'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $vendorId = $validated['vendor_id'];

        $arrival = DB::transaction(function () use ($validated, $vendorId) {
            $arrival = Arrival::create([
                'invoice_no' => $validated['invoice_no'],
                'invoice_date' => $validated['invoice_date'],
                'vendor_id' => $vendorId,
                'vessel' => $validated['vessel'] ?? null,
                'trucking_company_id' => $validated['trucking_company_id'] ?? null,
                'ETD' => $validated['etd'] ?? null,
                'ETA' => $validated['eta'] ?? null,
                'bill_of_lading' => $validated['bl_no'] ?? null,
                'hs_code' => $validated['hs_code'] ?? null,
                'port_of_loading' => $validated['port_of_loading'] ?? null,
                'country' => $validated['port_of_loading'] ?? 'SOUTH KOREA',
                'container_numbers' => $validated['container_numbers'] ?? null,
                'currency' => $validated['currency'] ?? 'USD',
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $index => $item) {
                // Skip items with qty_goods = 0
                if ($item['qty_goods'] <= 0) {
                    continue;
                }

                $part = Part::find($item['part_id']);
                if ($part && $part->vendor_id != $vendorId) {
                    throw ValidationException::withMessages([
                        "items.{$index}.part_id" => "Part {$part->part_no} does not belong to the selected vendor.",
                    ]);
                }

                $arrival->items()->create([
                    'part_id' => $item['part_id'],
                    'material_group' => $item['material_group'] ?? null,
                    'size' => $item['size'] ?? null,
                    'qty_bundle' => $item['qty_bundle'] ?? 0,
                    'unit_bundle' => $item['unit_bundle'] ?? null,
                    'qty_goods' => $item['qty_goods'],
                    'weight_nett' => $item['weight_nett'],
                    'unit_weight' => $item['unit_weight'] ?? null,
                    'weight_gross' => $item['weight_gross'],
                    'price' => $item['price'],
                    'total_price' => $item['price'] * $item['qty_goods'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $arrival;
        });

        return redirect()->route('departures.show', $arrival)->with('success', 'Departure created successfully. Click \"Receive\" on each item to process incoming goods.');
    }

    public function show(Arrival $departure)
    {
        // Keep using $arrival internally for existing views/logic
        $arrival = $departure;
        $arrival->load(['vendor', 'creator', 'inspection', 'items.part.vendor', 'items.receives']);

        return view('arrivals.show', compact('arrival'));
    }

    public function destroy(Arrival $departure)
    {
        $arrival = $departure;

        DB::transaction(function () use ($arrival) {
            foreach ($arrival->items as $item) {
                $item->receives()->delete();
            }
            $arrival->items()->delete();
            $arrival->delete();
        });

        return redirect()->route('departures.index')->with('success', 'Departure berhasil dihapus.');
    }

    public function edit(Arrival $departure)
    {
        return view('arrivals.edit', ['arrival' => $departure]);
    }

    public function update(Request $request, Arrival $departure)
    {
        $data = $request->validate([
            'invoice_date' => ['required', 'date'],
            'etd' => ['nullable', 'date'],
            'eta' => ['nullable', 'date'],
        ]);

        $departure->update([
            'invoice_date' => $data['invoice_date'],
            'ETD' => $data['etd'] ?? null,
            'ETA' => $data['eta'] ?? null,
        ]);

        return redirect()->route('departures.index')->with('status', 'Departure dates updated.');
    }

    public function printInvoice(Arrival $departure)
    {
        // Keep using $arrival internally for existing view/logic
        $arrival = $departure;
        $arrival->load(['vendor', 'trucking', 'items.part']);

        $pdf = Pdf::loadView('arrivals.invoice', compact('arrival'))
            ->setPaper('a3', 'portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 15)
            ->setOption('margin-right', 15);

        // Clean filename - remove / and \ characters
        $filename = 'Commercial-Invoice-' . str_replace(['/', '\\'], '-', $arrival->invoice_no) . '.pdf';
        
        return $pdf->stream($filename);
    }

    public function printInspectionReport(Arrival $departure)
    {
        $arrival = $departure;
        $arrival->load(['vendor', 'inspection.inspector']);

        if (!$arrival->inspection) {
            abort(404);
        }

        $inspection = $arrival->inspection;

        $toDataUri = function (?string $publicPath): ?string {
            if (!$publicPath) {
                return null;
            }
            if (!Storage::disk('public')->exists($publicPath)) {
                return null;
            }
            $bytes = Storage::disk('public')->get($publicPath);
            $mime = Storage::disk('public')->mimeType($publicPath) ?: 'image/jpeg';
            return 'data:' . $mime . ';base64,' . base64_encode($bytes);
        };

        $photos = [
            'left' => $toDataUri($inspection->photo_left),
            'right' => $toDataUri($inspection->photo_right),
            'front' => $toDataUri($inspection->photo_front),
            'back' => $toDataUri($inspection->photo_back),
        ];

        $pdf = Pdf::loadView('arrivals.inspection_report', compact('arrival', 'inspection', 'photos'))
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);

        $filename = 'Inspection-' . str_replace(['/', '\\'], '-', $arrival->invoice_no) . '.pdf';

        return $pdf->stream($filename);
    }
}
