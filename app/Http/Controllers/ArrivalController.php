<?php

namespace App\Http\Controllers;

use App\Models\Arrival;
use App\Models\Part;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ArrivalController extends Controller
{
    public function index()
    {
        $arrivals = Arrival::with(['vendor', 'creator', 'items.receives'])
            ->latest()
            ->paginate(10);

        return view('arrivals.index', compact('arrivals'));
    }

    public function create()
    {
        $vendors = Vendor::orderBy('vendor_name')->get();
        $parts = Part::with('vendor')->where('status', 'active')->get();

        return view('arrivals.create', compact('vendors', 'parts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_no' => ['required', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'vessel' => ['nullable', 'string', 'max:255'],
            'trucking_company' => ['nullable', 'string', 'max:255'],
            'ETD' => ['nullable', 'date'],
            'bill_of_lading' => ['nullable', 'string', 'max:255'],
            'hs_code' => ['nullable', 'string', 'max:255'],
            'port_of_loading' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.part_id' => ['required', 'exists:parts,id'],
            'items.*.size' => ['nullable', 'string', 'max:100', 'regex:/^\d{1,4}(\.\d{1,2})?\s*x\s*\d{1,4}(\.\d)?\s*x\s*[A-Z]$/i'],
            'items.*.qty_bundle' => ['required', 'integer', 'min:0'],
            'items.*.qty_goods' => ['required', 'integer', 'min:1'],
            'items.*.weight_nett' => ['required', 'numeric', 'min:0'],
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
                'trucking_company' => $validated['trucking_company'] ?? null,
                'ETD' => $validated['ETD'] ?? null,
                'bill_of_lading' => $validated['bill_of_lading'] ?? null,
                'hs_code' => $validated['hs_code'] ?? null,
                'port_of_loading' => $validated['port_of_loading'] ?? null,
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
                    'size' => $item['size'] ?? null,
                    'qty_bundle' => $item['qty_bundle'],
                    'qty_goods' => $item['qty_goods'],
                    'weight_nett' => $item['weight_nett'],
                    'weight_gross' => $item['weight_gross'],
                    'price' => $item['price'],
                    'total_price' => $item['price'] * $item['qty_goods'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $arrival;
        });

        return redirect()->route('arrivals.show', $arrival)->with('success', 'Arrival created successfully. Click "Receive" on each item to process incoming goods.');
    }

    public function show(Arrival $arrival)
    {
        $arrival->load(['vendor', 'creator', 'items.part', 'items.receives']);

        return view('arrivals.show', compact('arrival'));
    }
}
