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

        $translatedByPoId = [];
        $poIds = $orders->getCollection()->pluck('id')->all();
        if (!empty($poIds)) {
            $translated = DB::table('customer_pos as po')
                ->join('customer_parts as cp', function ($join) {
                    $join->on('cp.customer_id', '=', 'po.customer_id')
                        ->on('cp.customer_part_no', '=', 'po.customer_part_no');
                })
                ->join('customer_part_components as cpc', 'cpc.customer_part_id', '=', 'cp.id')
                ->join('gci_parts as gp', 'gp.id', '=', 'cpc.part_id')
                ->whereIn('po.id', $poIds)
                ->whereNull('po.part_id')
                ->whereNotNull('po.customer_part_no')
                ->select([
                    'po.id as po_id',
                    'gp.part_no',
                    'gp.part_name',
                    'cpc.usage_qty',
                    'po.qty as customer_qty',
                    DB::raw('(po.qty * cpc.usage_qty) as demand_qty'),
                ])
                ->orderBy('po.id')
                ->orderBy('gp.part_no')
                ->get();

            foreach ($translated as $t) {
                $translatedByPoId[(int) $t->po_id][] = [
                    'part_no' => $t->part_no,
                    'part_name' => $t->part_name,
                    'usage_qty' => (float) $t->usage_qty,
                    'demand_qty' => (float) $t->demand_qty,
                ];
            }
        }

        return view('planning.customer_pos.index', compact('orders', 'customers', 'gciParts', 'minggu', 'defaultMinggu', 'customerId', 'status', 'translatedByPoId'));
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
                    'items.*.po_type' => ['required', Rule::in(['customer_part', 'gci_part'])],
                    'items.*.customer_part_no' => ['nullable', 'string', 'max:100', 'required_if:items.*.po_type,customer_part'],
                    'items.*.part_id' => ['nullable', Rule::exists('gci_parts', 'id'), 'required_if:items.*.po_type,gci_part'],
                    'items.*.qty' => ['required', 'numeric', 'min:0'],
                ],
            ));

            $customerId = (int) $validated['customer_id'];
            $poNo = $validated['po_no'] ? trim($validated['po_no']) : null;
            $notes = $validated['notes'] ? trim($validated['notes']) : null;

            foreach ($validated['items'] as $item) {
                $poType = $item['po_type'];
                $customerPartNo = null;
                $partId = null;

                if ($poType === 'customer_part') {
                    $customerPartNo = strtoupper(trim((string) ($item['customer_part_no'] ?? '')));
                    if ($customerPartNo === '') {
                        return back()->with('error', 'Customer part is required.');
                    }

                    $mapping = CustomerPart::query()
                        ->where('customer_id', $customerId)
                        ->where('customer_part_no', $customerPartNo)
                        ->withCount('components')
                        ->first();
                    if (!$mapping) {
                        return back()->with('error', "Customer part not mapped: {$customerPartNo}");
                    }
                    if (($mapping->status ?? 'active') !== 'active' || $mapping->components_count < 1) {
                        return back()->with('error', "Customer part mapping is incomplete/inactive: {$customerPartNo}");
                    }
                } else {
                    $partId = isset($item['part_id']) ? (int) $item['part_id'] : null;
                    if (!$partId) {
                        return back()->with('error', 'GCI part is required.');
                    }
                }

                CustomerPo::create([
                    'po_no' => $poNo,
                    'customer_id' => $customerId,
                    'customer_part_no' => $customerPartNo,
                    'part_id' => $partId,
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
                'po_type' => ['nullable', Rule::in(['customer_part', 'gci_part'])],
                'customer_part_no' => ['nullable', 'string', 'max:100', 'required_if:po_type,customer_part'],
                'part_id' => ['nullable', Rule::exists('gci_parts', 'id'), 'required_if:po_type,gci_part'],
                'qty' => ['required', 'numeric', 'min:0'],
                'status' => ['required', Rule::in(['open', 'closed'])],
                'notes' => ['nullable', 'string'],
            ],
        ));

        $poType = $validated['po_type'] ?? 'customer_part';
        $customerPartNo = null;
        $partId = null;

        if ($poType === 'customer_part') {
            $customerPartNo = $validated['customer_part_no'] ? strtoupper(trim($validated['customer_part_no'])) : null;
        }
        if ($poType === 'gci_part') {
            $partId = $validated['part_id'] ? (int) $validated['part_id'] : null;
        }

        if ($poType === 'customer_part' && !$customerPartNo) {
            return back()->with('error', 'Customer part is required.');
        }
        if ($poType === 'gci_part' && !$partId) {
            return back()->with('error', 'GCI part is required.');
        }

        if ($poType === 'customer_part' && $customerPartNo) {
            $mapping = CustomerPart::query()
                ->where('customer_id', $validated['customer_id'])
                ->where('customer_part_no', $customerPartNo)
                ->withCount('components')
                ->first();
            if (!$mapping) {
                return back()->with('error', 'Customer part not mapped.');
            }
            if (($mapping->status ?? 'active') !== 'active' || $mapping->components_count < 1) {
                return back()->with('error', 'Customer part mapping is incomplete/inactive.');
            }
        }

        CustomerPo::create([
            'po_no' => $validated['po_no'] ? trim($validated['po_no']) : null,
            'customer_id' => (int) $validated['customer_id'],
            'customer_part_no' => $customerPartNo,
            'part_id' => $partId,
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
