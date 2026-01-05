<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\CustomerOrder;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerOrderController extends Controller
{
    private function validatePeriod(string $field = 'period'): array
    {
        return [$field => ['required', 'string', 'regex:/^\\d{4}-(0[1-9]|1[0-2])$/']];
    }

    public function index(Request $request)
    {
        $period = $request->query('period') ?: now()->format('Y-m');
        $productId = $request->query('product_id');
        $status = $request->query('status');

        $products = Product::query()->orderBy('code')->get();

        $orders = CustomerOrder::query()
            ->with('product')
            ->where('period', $period)
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('planning.customer_orders.index', compact('products', 'orders', 'period', 'productId', 'status'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(array_merge(
            $this->validatePeriod(),
            [
                'product_id' => ['required', Rule::exists('products', 'id')],
                'qty' => ['required', 'numeric', 'min:0'],
                'status' => ['required', Rule::in(['open', 'closed'])],
                'order_no' => ['nullable', 'string', 'max:100'],
                'customer_name' => ['nullable', 'string', 'max:150'],
                'notes' => ['nullable', 'string'],
            ],
        ));

        CustomerOrder::create([
            'product_id' => (int) $validated['product_id'],
            'period' => $validated['period'],
            'qty' => $validated['qty'],
            'status' => $validated['status'],
            'order_no' => $validated['order_no'] ? trim($validated['order_no']) : null,
            'customer_name' => $validated['customer_name'] ? trim($validated['customer_name']) : null,
            'notes' => $validated['notes'] ? trim($validated['notes']) : null,
        ]);

        return back()->with('success', 'Customer order created.');
    }

    public function update(Request $request, CustomerOrder $customerOrder)
    {
        $validated = $request->validate([
            'qty' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['open', 'closed'])],
            'order_no' => ['nullable', 'string', 'max:100'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
        ]);

        $customerOrder->update([
            'qty' => $validated['qty'],
            'status' => $validated['status'],
            'order_no' => $validated['order_no'] ? trim($validated['order_no']) : null,
            'customer_name' => $validated['customer_name'] ? trim($validated['customer_name']) : null,
            'notes' => $validated['notes'] ? trim($validated['notes']) : null,
        ]);

        return back()->with('success', 'Customer order updated.');
    }

    public function destroy(CustomerOrder $customerOrder)
    {
        $customerOrder->delete();

        return back()->with('success', 'Customer order deleted.');
    }
}

