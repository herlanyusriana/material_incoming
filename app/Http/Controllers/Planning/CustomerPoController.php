<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPart;
use App\Models\CustomerPo;
use App\Models\GciPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerPoController extends Controller
{
    private function validateMinggu(string $field = 'minggu'): array
    {
        return [$field => ['required', 'string', 'regex:/^\d{4}-W(0[1-9]|[1-4][0-9]|5[0-3])$/']];
    }

    public function index(Request $request)
    {
        $minggu = trim((string) $request->query('minggu', ''));
        $minggu = $minggu !== '' ? $minggu : null;
        $customerId = $request->query('customer_id');
        $status = $request->query('status');
        $defaultMinggu = now()->format('o-\\WW');

        $customers = Customer::query()->orderBy('code')->get();
        // Default for Customer PO: show FG only to prevent mixing RM/WIP in PO selection.
        $gciParts = GciPart::query()
            ->where('status', 'active')
            ->where('classification', 'FG')
            ->orderBy('part_no')
            ->get();

        $orders = CustomerPo::query()
            ->with(['customer', 'part'])
            ->when($minggu, fn ($q) => $q->where('minggu', $minggu))
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('minggu')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('planning.customer_pos.index', compact('orders', 'customers', 'gciParts', 'minggu', 'defaultMinggu', 'customerId', 'status'));
    }

    public function store(Request $request)
    {
        $hasItems = is_array($request->input('items')) && count((array) $request->input('items')) > 0;

        if ($hasItems) {
            $validated = $request->validate(array_merge(
                $this->validateMinggu(),
                [
                    'customer_id' => ['required', Rule::exists('customers', 'id')],
                    'po_no' => ['nullable', 'string', 'max:100'],
                    'status' => ['required', Rule::in(['open', 'closed'])],
                    'notes' => ['nullable', 'string'],
                    'items' => ['required', 'array', 'min:1'],
                    'items.*.part_id' => ['required', Rule::exists('gci_parts', 'id')],
                    'items.*.qty' => ['required', 'numeric', 'min:0'],
                ],
            ));

            $customerId = (int) $validated['customer_id'];
            $poNo = $validated['po_no'] ? trim($validated['po_no']) : null;
            $notes = $validated['notes'] ? trim($validated['notes']) : null;

            foreach ($validated['items'] as $item) {
                CustomerPo::create([
                    'po_no' => $poNo,
                    'customer_id' => $customerId,
                    'part_id' => (int) $item['part_id'],
                    'minggu' => $validated['minggu'],
                    'qty' => $item['qty'],
                    'status' => $validated['status'],
                    'notes' => $notes,
                ]);
            }

            return back()->with('success', 'Customer PO created.');
        }

        $validated = $request->validate(array_merge(
            $this->validateMinggu(),
            [
                'customer_id' => ['required', Rule::exists('customers', 'id')],
                'po_no' => ['nullable', 'string', 'max:100'],
                'part_id' => ['required', Rule::exists('gci_parts', 'id')],
                'qty' => ['required', 'numeric', 'min:0'],
                'status' => ['required', Rule::in(['open', 'closed'])],
                'notes' => ['nullable', 'string'],
            ],
        ));

        CustomerPo::create([
            'po_no' => $validated['po_no'] ? trim($validated['po_no']) : null,
            'customer_id' => (int) $validated['customer_id'],
            'part_id' => (int) $validated['part_id'],
            'minggu' => $validated['minggu'],
            'qty' => $validated['qty'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ? trim($validated['notes']) : null,
        ]);

        return back()->with('success', 'Customer PO created.');
    }

    public function update(Request $request, CustomerPo $customerPo)
    {
        $validated = $request->validate([
            'po_no' => ['nullable', 'string', 'max:100'],
            'qty' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['open', 'closed'])],
            'notes' => ['nullable', 'string'],
        ]);

        $customerPo->update([
            'po_no' => $validated['po_no'] ? trim($validated['po_no']) : null,
            'qty' => $validated['qty'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ? trim($validated['notes']) : null,
        ]);

        return back()->with('success', 'Customer PO updated.');
    }

    public function destroy(CustomerPo $customerPo)
    {
        $customerPo->delete();

        return back()->with('success', 'Customer PO deleted.');
    }
}
