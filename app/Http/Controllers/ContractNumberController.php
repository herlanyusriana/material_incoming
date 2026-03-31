<?php

namespace App\Http\Controllers;

use App\Models\ContractNumber;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractNumberController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $vendorId = (int) $request->query('vendor_id', 0);
        $status = trim((string) $request->query('status', 'active'));

        $contracts = ContractNumber::query()
            ->with(['vendor', 'creator'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('contract_no', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhereHas('vendor', fn ($vendorQ) => $vendorQ->where('vendor_name', 'like', '%' . $search . '%'));
                });
            })
            ->when($vendorId > 0, fn ($q) => $q->where('vendor_id', $vendorId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $vendors = Vendor::query()
            ->where('status', 'active')
            ->orderBy('vendor_name')
            ->get(['id', 'vendor_name']);

        return view('contract-numbers.index', [
            'contracts' => $contracts,
            'vendors' => $vendors,
            'filters' => compact('search', 'vendorId', 'status'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        ContractNumber::create($data);

        return redirect()->route('contract-numbers.index')->with('success', 'Nomor kontrak berhasil dibuat.');
    }

    public function update(Request $request, ContractNumber $contractNumber)
    {
        $data = $this->validatedData($request, $contractNumber);
        $data['updated_by'] = auth()->id();

        $contractNumber->update($data);

        return redirect()->route('contract-numbers.index')->with('success', 'Nomor kontrak berhasil diperbarui.');
    }

    public function destroy(ContractNumber $contractNumber)
    {
        $contractNumber->delete();

        return redirect()->route('contract-numbers.index')->with('success', 'Nomor kontrak berhasil dihapus.');
    }

    public function byVendor(Vendor $vendor)
    {
        $contracts = ContractNumber::query()
            ->where('vendor_id', $vendor->id)
            ->where('status', 'active')
            ->orderByDesc('effective_from')
            ->orderBy('contract_no')
            ->get()
            ->map(fn (ContractNumber $contract) => [
                'id' => $contract->id,
                'contract_no' => $contract->contract_no,
                'description' => $contract->description,
                'effective_from' => optional($contract->effective_from)->format('Y-m-d'),
                'effective_to' => optional($contract->effective_to)->format('Y-m-d'),
            ])
            ->values();

        return response()->json([
            'contracts' => $contracts,
        ]);
    }

    private function validatedData(Request $request, ?ContractNumber $contractNumber = null): array
    {
        return $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'contract_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('contract_numbers', 'contract_no')->ignore($contractNumber?->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
