<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Exports\CustomerPartMappingExport;
use App\Imports\CustomerPartMappingImport;
use App\Models\Customer;
use App\Models\CustomerPart;
use App\Models\CustomerPartComponent;
use App\Models\GciPart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class CustomerPartController extends Controller
{
    public function index(Request $request)
    {
        $customerId = $request->query('customer_id');
        $search = $request->query('search');

        $customers = Customer::query()->orderBy('code')->get();
        $parts = GciPart::query()->orderBy('part_no')->get();

        $customerParts = CustomerPart::query()
            ->with(['customer', 'components.part'])
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('customer_part_no', 'like', "%{$search}%")
                        ->orWhere('customer_part_name', 'like', "%{$search}%");
                });
            })
            ->orderBy(Customer::select('code')->whereColumn('customers.id', 'customer_parts.customer_id'))
            ->orderBy('customer_part_no')
            ->paginate(20)
            ->withQueryString();

        return view('planning.customer_parts.index', compact('customers', 'parts', 'customerParts', 'customerId', 'search'));
    }

    public function export(Request $request)
    {
        $customerId = $request->query('customer_id');
        $filename = 'customer_part_mapping_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new CustomerPartMappingExport($customerId), $filename);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            $import = new CustomerPartMappingImport();
            Excel::import($import, $validated['file']);

            $failures = collect($import->failures());
            if ($failures->isNotEmpty()) {
                $preview = $failures
                    ->take(5)
                    ->map(fn ($f) => "Row {$f->row()}: " . implode(' | ', $f->errors()))
                    ->implode(' ; ');

                return back()->with('error', "Import selesai tapi ada {$failures->count()} baris gagal. {$preview}");
            }

            $dupCount = count($import->duplicates);
            $msg = 'Customer part mapping imported.';
            if ($dupCount > 0) {
                $msg .= " Detected {$dupCount} duplicate mapping rows (same customer + customer part + GCI part); qty summed.";
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                $failures = collect($e->failures());
                $preview = $failures
                    ->take(5)
                    ->map(fn ($f) => "Row {$f->row()}: " . implode(' | ', $f->errors()))
                    ->implode(' ; ');
                return back()->with('error', "Import failed: {$preview}");
            }
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')],
            'customer_part_no' => ['required', 'string', 'max:100'],
            'customer_part_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $partNoRaw = str_replace("\u{00A0}", ' ', (string) $validated['customer_part_no']);
        $partNoRaw = preg_replace('/\s+/', ' ', $partNoRaw) ?? $partNoRaw;
        $validated['customer_part_no'] = strtoupper(trim($partNoRaw));
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

        $partNoRaw = str_replace("\u{00A0}", ' ', (string) $validated['customer_part_no']);
        $partNoRaw = preg_replace('/\s+/', ' ', $partNoRaw) ?? $partNoRaw;
        $validated['customer_part_no'] = strtoupper(trim($partNoRaw));
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

        $gciPartId = (int) $validated['part_id'];
        $hasGciPartId = Schema::hasColumn('customer_part_components', 'gci_part_id');
        $hasLegacyPartId = Schema::hasColumn('customer_part_components', 'part_id');

        $componentQuery = CustomerPartComponent::query()->where('customer_part_id', $customerPart->id);
        if ($hasGciPartId && $hasLegacyPartId) {
            $componentQuery->where(function ($q) use ($gciPartId) {
                $q->where('gci_part_id', $gciPartId)->orWhere('part_id', $gciPartId);
            });
        } elseif ($hasGciPartId) {
            $componentQuery->where('gci_part_id', $gciPartId);
        } elseif ($hasLegacyPartId) {
            $componentQuery->where('part_id', $gciPartId);
        }

        $component = $componentQuery->first();
        $payload = ['qty_per_unit' => $validated['usage_qty']];
        if ($hasGciPartId) {
            $payload['gci_part_id'] = $gciPartId;
        }
        if ($hasLegacyPartId) {
            $payload['part_id'] = $gciPartId;
        }

        if ($component) {
            $component->update($payload);
        } else {
            $payload['customer_part_id'] = $customerPart->id;
            CustomerPartComponent::create($payload);
        }

        return back()->with('success', 'Mapping saved.');
    }

    public function destroyComponent(CustomerPartComponent $component)
    {
        $component->delete();

        return back()->with('success', 'Component removed.');
    }
}
