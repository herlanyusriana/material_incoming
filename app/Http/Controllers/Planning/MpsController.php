<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\CustomerOrder;
use App\Models\Forecast;
use App\Models\Mps;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MpsController extends Controller
{
    private function validatePeriod(string $field = 'period'): array
    {
        return [$field => ['required', 'string', 'regex:/^\\d{4}-(0[1-9]|1[0-2])$/']];
    }

    public function index(Request $request)
    {
        $period = $request->query('period') ?: now()->format('Y-m');

        $rows = Mps::query()
            ->with('product')
            ->where('period', $period)
            ->orderBy(Product::select('code')->whereColumn('products.id', 'mps.product_id'))
            ->get();

        return view('planning.mps.index', compact('period', 'rows'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate($this->validatePeriod());
        $period = $validated['period'];

        DB::transaction(function () use ($period) {
            $products = Product::query()->where('status', 'active')->get(['id']);
            foreach ($products as $product) {
                $forecastQty = (float) (Forecast::query()
                    ->where('product_id', $product->id)
                    ->where('period', $period)
                    ->value('qty') ?? 0);

                $openOrderQty = (float) (CustomerOrder::query()
                    ->where('product_id', $product->id)
                    ->where('period', $period)
                    ->where('status', 'open')
                    ->sum('qty') ?? 0);

                $computed = max($forecastQty, $openOrderQty);

                $existing = Mps::query()
                    ->where('product_id', $product->id)
                    ->where('period', $period)
                    ->lockForUpdate()
                    ->first();

                if ($existing && $existing->status === 'approved') {
                    continue;
                }

                if (!$existing) {
                    Mps::create([
                        'product_id' => $product->id,
                        'period' => $period,
                        'forecast_qty' => $forecastQty,
                        'open_order_qty' => $openOrderQty,
                        'planned_qty' => $computed,
                        'status' => 'draft',
                    ]);
                    continue;
                }

                $existingPlanned = (float) $existing->planned_qty;
                $existing->update([
                    'forecast_qty' => $forecastQty,
                    'open_order_qty' => $openOrderQty,
                    'planned_qty' => max($existingPlanned, $computed),
                ]);
            }
        });

        return redirect()->route('planning.mps.index', ['period' => $period])->with('success', 'MPS generated (draft).');
    }

    public function update(Request $request, Mps $mps)
    {
        if ($mps->status === 'approved') {
            return back()->with('error', 'MPS already approved.');
        }

        $validated = $request->validate([
            'planned_qty' => ['required', 'numeric', 'min:0'],
        ]);

        $mps->update(['planned_qty' => $validated['planned_qty']]);

        return back()->with('success', 'MPS updated.');
    }

    public function approve(Request $request)
    {
        $validated = $request->validate($this->validatePeriod());
        $period = $validated['period'];

        $updated = Mps::query()
            ->where('period', $period)
            ->where('status', 'draft')
            ->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

        return redirect()->route('planning.mps.index', ['period' => $period])->with('success', "Approved {$updated} rows.");
    }
}

