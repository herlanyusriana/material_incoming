<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\GciPart;
use App\Models\PricingMaster;
use App\Models\Vendor;
use Illuminate\Http\Request;

/**
 * Material Price — purchasing-side view of the pricing masters.
 *
 * Single source of truth stays in `pricing_masters` (same table used by the
 * Price Master page in Marketing); this screen only exposes the buy-side
 * price types. Selling / OSP / subcon prices remain managed in Price Master.
 */
class MaterialPriceController extends Controller
{
    public const PRICE_TYPES = [
        'purchase_price' => 'material_price.types.purchase_price',
        'material_cost' => 'material_price.types.material_cost',
    ];

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $vendorId = $request->filled('vendor_id') ? (int) $request->query('vendor_id') : null;
        $status = trim((string) $request->query('status', ''));

        $prices = $this->baseQuery()
            ->with(['gciPart', 'vendor'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->whereHas('gciPart', function ($partQ) use ($search) {
                        $partQ->where('part_no', 'like', "%{$search}%")
                            ->orWhere('part_name', 'like', "%{$search}%");
                    })->orWhereHas('vendor', function ($vendorQ) use ($search) {
                        $vendorQ->where('vendor_name', 'like', "%{$search}%");
                    });
                });
            })
            ->when($vendorId, fn ($q) => $q->where('vendor_id', $vendorId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('purchasing.material-price.index', [
            'prices' => $prices,
            'filters' => compact('search', 'vendorId', 'status'),
            'vendors' => Vendor::where('status', 'active')->orderBy('vendor_name')->get(['id', 'vendor_name']),
            'parts' => GciPart::where('status', 'active')
                ->orderBy('classification')
                ->orderBy('part_no')
                ->get(['id', 'part_no', 'part_name', 'classification']),
            'priceTypes' => self::PRICE_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        PricingMaster::create($data);

        return redirect()
            ->route('purchasing.material-prices.index')
            ->with('success', __('material_price.created'));
    }

    public function update(Request $request, PricingMaster $pricing)
    {
        $this->assertPurchaseRow($pricing);

        $data = $this->validatedData($request);
        $data['updated_by'] = $request->user()->id;

        $pricing->update($data);

        return redirect()
            ->route('purchasing.material-prices.index')
            ->with('success', __('material_price.updated'));
    }

    public function destroy(PricingMaster $pricing)
    {
        $this->assertPurchaseRow($pricing);

        $pricing->delete();

        return redirect()
            ->route('purchasing.material-prices.index')
            ->with('success', __('material_price.deleted'));
    }

    private function baseQuery()
    {
        return PricingMaster::query()->whereIn('price_type', array_keys(self::PRICE_TYPES));
    }

    /**
     * Guard so this screen can never touch rows owned by other
     * price types (e.g. selling prices managed in Price Master).
     */
    private function assertPurchaseRow(PricingMaster $pricing): void
    {
        abort_unless(array_key_exists($pricing->price_type, self::PRICE_TYPES), 404);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'gci_part_id' => ['required', 'exists:gci_parts,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'price_type' => ['required', 'in:'.implode(',', array_keys(self::PRICE_TYPES))],
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
}
