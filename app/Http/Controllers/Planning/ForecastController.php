<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Forecast;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ForecastController extends Controller
{
    private function validatePeriod(string $field = 'period'): array
    {
        return [$field => ['required', 'string', 'regex:/^\\d{4}-(0[1-9]|1[0-2])$/']];
    }

    public function index(Request $request)
    {
        $period = $request->query('period') ?: now()->format('Y-m');
        $productId = $request->query('product_id');

        $products = Product::query()->orderBy('code')->get();

        $forecasts = Forecast::query()
            ->with('product')
            ->where('period', $period)
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->orderBy(Product::select('code')->whereColumn('products.id', 'forecasts.product_id'))
            ->paginate(25)
            ->withQueryString();

        return view('planning.forecasts.index', compact('products', 'forecasts', 'period', 'productId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(array_merge(
            $this->validatePeriod(),
            [
                'product_id' => ['required', Rule::exists('products', 'id')],
                'qty' => ['required', 'numeric', 'min:0'],
            ],
        ));

        Forecast::updateOrCreate(
            ['product_id' => (int) $validated['product_id'], 'period' => $validated['period']],
            ['qty' => $validated['qty']],
        );

        return back()->with('success', 'Forecast saved.');
    }

    public function update(Request $request, Forecast $forecast)
    {
        $validated = $request->validate([
            'qty' => ['required', 'numeric', 'min:0'],
        ]);

        $forecast->update(['qty' => $validated['qty']]);

        return back()->with('success', 'Forecast updated.');
    }

    public function destroy(Forecast $forecast)
    {
        $forecast->delete();

        return back()->with('success', 'Forecast deleted.');
    }
}

