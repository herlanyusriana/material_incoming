<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\GciPart;
use App\Models\PricingMaster;
use App\Models\Vendor;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function create()
    {
        return view('pricing.create', $this->formData());
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $priceType = trim((string) $request->query('price_type', ''));
        $classification = trim((string) $request->query('classification', ''));
        $status = trim((string) $request->query('status', 'active'));

        $prices = PricingMaster::query()
            ->with(['gciPart', 'vendor', 'customer', 'creator'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->whereHas('gciPart', function ($partQ) use ($search) {
                        $partQ->where('part_no', 'like', "%{$search}%")
                            ->orWhere('part_name', 'like', "%{$search}%");
                    })->orWhereHas('vendor', function ($vendorQ) use ($search) {
                        $vendorQ->where('vendor_name', 'like', "%{$search}%");
                    })->orWhereHas('customer', function ($customerQ) use ($search) {
                        $customerQ->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->when($priceType !== '', fn($q) => $q->where('price_type', $priceType))
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->when($classification !== '', function ($q) use ($classification) {
                $q->whereHas('gciPart', fn($partQ) => $partQ->where('classification', $classification));
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('pricing.index', array_merge($this->formData(), [
            'prices' => $prices,
            'filters' => compact('search', 'priceType', 'classification', 'status'),
        ]));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        PricingMaster::create($data);

        return redirect()->route('pricing.index')->with('success', 'Pricing master created.');
    }

    public function update(Request $request, PricingMaster $pricing)
    {
        $data = $this->validatedData($request);
        $data['updated_by'] = auth()->id();

        $pricing->update($data);

        return redirect()->route('pricing.index')->with('success', 'Pricing master updated.');
    }

    public function destroy(PricingMaster $pricing)
    {
        $pricing->delete();

        return redirect()->route('pricing.index')->with('success', 'Pricing master deleted.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'gci_part_id' => ['required', 'exists:gci_parts,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'price_type' => ['required', 'in:' . implode(',', array_keys(PricingMaster::PRICE_TYPES))],
            'currency' => ['required', 'string', 'max:10'],
            'uom' => ['nullable', 'string', 'max:20'],
            'min_qty' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function formData(): array
    {
        return [
            'parts' => GciPart::where('status', 'active')->orderBy('classification')->orderBy('part_no')->get(['id', 'part_no', 'part_name', 'classification']),
            'vendors' => Vendor::where('status', 'active')->orderBy('vendor_name')->get(['id', 'vendor_name']),
            'customers' => Customer::where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'priceTypes' => PricingMaster::PRICE_TYPES,
        ];
    }
}
