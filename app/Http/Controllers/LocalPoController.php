<?php

namespace App\Http\Controllers;

use App\Models\Arrival;
use App\Models\Part;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LocalPoController extends Controller
{
    public function index(Request $request)
    {
        $q = strtoupper(trim((string) $request->query('q', '')));
        $vendorId = $request->query('vendor_id');

        $vendors = Vendor::query()
            ->where('vendor_type', 'local')
            ->orderBy('vendor_name')
            ->get();

        $localPos = Arrival::query()
            ->with(['vendor', 'items.receives'])
            ->whereHas('vendor', fn ($qv) => $qv->where('vendor_type', 'local'))
            ->when($vendorId, fn ($qa) => $qa->where('vendor_id', $vendorId))
            ->when($q !== '', function ($qa) use ($q) {
                $qa->where(function ($inner) use ($q) {
                    $inner->where('invoice_no', 'like', "%{$q}%")
                        ->orWhere('arrival_no', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $localPos->getCollection()->transform(function (Arrival $arrival) {
            $remaining = $arrival->items->sum(function ($item) {
                $received = $item->receives->sum('qty');
                return max(0, (float) $item->qty_goods - (float) $received);
            });
            $arrival->remaining_qty = $remaining;
            $arrival->items_count = $arrival->items->count();
            return $arrival;
        });

        return view('local_pos.index', compact('localPos', 'vendors', 'q', 'vendorId'));
    }

    public function create()
    {
        $vendors = Vendor::query()
            ->where('vendor_type', 'local')
            ->orderBy('vendor_name')
            ->get();

        $localVendorIds = $vendors->pluck('id')->all();

        $parts = Part::with('vendor')
            ->where('status', 'active')
            ->whereIn('vendor_id', $localVendorIds)
            ->get();

        return view('local_pos.create', compact('vendors', 'parts'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'po_no' => strtoupper(trim((string) $request->input('po_no', ''))),
        ]);

        $validated = $request->validate([
            'po_no' => ['required', 'string', 'max:255', Rule::unique('arrivals', 'invoice_no')],
            'po_date' => ['required', 'date'],
            'vendor_id' => ['required', Rule::exists('vendors', 'id')],
            'currency' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
            'delivery_note_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'invoice_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'packing_list_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.part_id' => ['required', Rule::exists('parts', 'id')],
            'items.*.material_group' => ['nullable', 'string', 'max:255'],
            'items.*.size' => ['nullable', 'string', 'max:100'],
            'items.*.qty_bundle' => ['required', 'integer', 'min:0'],
            'items.*.unit_bundle' => ['required', 'in:PALLET,BUNDLE,BOX'],
            'items.*.qty_goods' => ['required', 'numeric', 'min:0'],
            'items.*.unit_goods' => ['required', 'in:PCS,COIL,SHEET,SET,EA,KGM'],
            'items.*.weight_nett' => ['nullable', 'numeric', 'min:0'],
            'items.*.weight_gross' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $vendor = Vendor::findOrFail((int) $validated['vendor_id']);
        if ($vendor->vendor_type !== 'local') {
            return back()->withInput()->withErrors([
                'vendor_id' => 'Vendor harus bertipe LOCAL untuk Local PO.',
            ]);
        }

        $vendorPartIds = Part::query()
            ->where('vendor_id', $vendor->id)
            ->pluck('id')
            ->all();

        $invalidPartIds = collect($validated['items'] ?? [])
            ->pluck('part_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id !== 0 && !in_array($id, $vendorPartIds, true))
            ->unique()
            ->values();

        if ($invalidPartIds->isNotEmpty()) {
            return back()->withInput()->withErrors([
                'items' => 'Ada part yang tidak sesuai vendor: part_id=' . $invalidPartIds->implode(', '),
            ]);
        }

        $poNo = $validated['po_no'];
        $currency = strtoupper(trim((string) ($validated['currency'] ?? 'IDR')));
        if ($currency === '') {
            $currency = 'IDR';
        }

        $arrival = DB::transaction(function () use ($request, $validated, $poNo, $currency) {
            /** @var Arrival $arrival */
            $arrival = Arrival::create([
                'invoice_no' => $poNo,
                'invoice_date' => $validated['po_date'],
                'vendor_id' => $validated['vendor_id'],
                'currency' => $currency,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $dir = "local_pos/arrival-{$arrival->id}";

            if ($request->hasFile('delivery_note_file')) {
                $file = $request->file('delivery_note_file');
                $path = $file->storePubliclyAs($dir, "surat_jalan." . $file->getClientOriginalExtension(), 'public');
                $arrival->delivery_note_file = $path;
                $arrival->save();
            }

            if ($request->hasFile('invoice_file')) {
                $file = $request->file('invoice_file');
                $path = $file->storePubliclyAs($dir, "invoice." . $file->getClientOriginalExtension(), 'public');
                $arrival->invoice_file = $path;
                $arrival->save();
            }

            if ($request->hasFile('packing_list_file')) {
                $file = $request->file('packing_list_file');
                $path = $file->storePubliclyAs($dir, "packing_list." . $file->getClientOriginalExtension(), 'public');
                $arrival->packing_list_file = $path;
                $arrival->save();
            }

            foreach (($validated['items'] ?? []) as $item) {
                $arrival->items()->create([
                    'part_id' => (int) $item['part_id'],
                    'material_group' => isset($item['material_group']) && trim((string) $item['material_group']) !== '' ? strtoupper(trim((string) $item['material_group'])) : null,
                    'size' => isset($item['size']) && trim((string) $item['size']) !== '' ? strtoupper(trim((string) $item['size'])) : null,
                    'qty_bundle' => (int) $item['qty_bundle'],
                    'unit_bundle' => strtoupper((string) $item['unit_bundle']),
                    'qty_goods' => (float) $item['qty_goods'],
                    'unit_goods' => strtoupper((string) $item['unit_goods']),
                    'weight_nett' => (float) ($item['weight_nett'] ?? 0),
                    'unit_weight' => 'KGM',
                    'weight_gross' => (float) ($item['weight_gross'] ?? 0),
                    'price' => 0,
                    'total_price' => 0,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $arrival;
        });

        return redirect()->route('receives.invoice.create', $arrival)->with('success', 'Local PO created. Silakan lakukan receive.');
    }
}
