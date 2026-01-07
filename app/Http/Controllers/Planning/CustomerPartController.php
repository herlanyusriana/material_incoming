<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPart;
use App\Models\CustomerPartComponent;
use App\Models\GciPart;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerPartController extends Controller
{
    public function index(Request $request)
    {
        $customerId = $request->query('customer_id');

        $customers = Customer::query()->orderBy('code')->get();
        $parts = GciPart::query()->orderBy('part_no')->get();

        $customerParts = CustomerPart::query()
            ->with(['customer', 'components.part'])
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->orderBy(Customer::select('code')->whereColumn('customers.id', 'customer_parts.customer_id'))
            ->orderBy('customer_part_no')
            ->paginate(20)
            ->withQueryString();

        return view('planning.customer_parts.index', compact('customers', 'parts', 'customerParts', 'customerId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')],
            'customer_part_no' => ['required', 'string', 'max:100'],
            'customer_part_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validated['customer_part_no'] = strtoupper(trim($validated['customer_part_no']));
        $validated['customer_part_name'] = $validated['customer_part_name'] ? trim($validated['customer_part_name']) : null;

        CustomerPart::create($validated);

        return back()->with('success', 'Customer part created.');
    }

    public function update(Request $request, CustomerPart $customerPart)
    {
        $validated = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')],
            'customer_part_no' => ['required', 'string', 'max:100'],
            'customer_part_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validated['customer_part_no'] = strtoupper(trim($validated['customer_part_no']));
        $validated['customer_part_name'] = $validated['customer_part_name'] ? trim($validated['customer_part_name']) : null;

        $customerPart->update($validated);

        return back()->with('success', 'Customer part updated.');
    }

    public function destroy(CustomerPart $customerPart)
    {
        $customerPart->delete();

        return back()->with('success', 'Customer part deleted.');
    }

    public function storeComponent(Request $request, CustomerPart $customerPart)
    {
        $validated = $request->validate([
            'part_id' => ['required', Rule::exists('gci_parts', 'id')],
            'usage_qty' => ['required', 'numeric', 'min:0.0001'],
        ]);

        CustomerPartComponent::updateOrCreate(
            ['customer_part_id' => $customerPart->id, 'part_id' => (int) $validated['part_id']],
            ['usage_qty' => $validated['usage_qty']],
        );

        return back()->with('success', 'Mapping saved.');
    }

    public function destroyComponent(CustomerPartComponent $component)
    {
        $component->delete();

        return back()->with('success', 'Component removed.');
    }
}
