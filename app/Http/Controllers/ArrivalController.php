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
        $arrivals = Arrival::with(['vendor', 'creator'])
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
            'items.*.qty_bundle' => ['required', 'integer', 'min:0'],
            'items.*.qty_goods' => ['required', 'integer', 'min:0'],
            'items.*.weight_nett' => ['required', 'numeric', 'min:0'],
            'items.*.weight_gross' => ['required', 'numeric', 'min:0'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $vendorId = $validated['vendor_id'];

        DB::transaction(function () use ($validated, $vendorId) {
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

            foreach ($validated['items'] as $item) {
                $part = Part::find($item['part_id']);
                if ($part && $part->vendor_id !== $vendorId) {
                    throw ValidationException::withMessages([
                        'items' => ['Selected part does not belong to this vendor.'],
                    ]);
                }

                $arrival->items()->create([
                    'part_id' => $item['part_id'],
                    'qty_bundle' => $item['qty_bundle'],
                    'qty_goods' => $item['qty_goods'],
                    'weight_nett' => $item['weight_nett'],
                    'weight_gross' => $item['weight_gross'],
                    'price' => $item['price'],
                    'total_price' => $item['price'] * $item['qty_goods'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('arrivals.index')->with('status', 'Arrival created.');
    }

    public function show(Arrival $arrival)
    {
        $arrival->load(['vendor', 'creator', 'items.part']);

        return view('arrivals.show', compact('arrival'));
    }
}
