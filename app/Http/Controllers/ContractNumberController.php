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
            ->with(['vendor', 'creator', 'items.gciPart', 'items.rmPart'])
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

        $subconParts = $this->getSubconPartOptions();

        $subconPartsJson = collect($subconParts)->values()->map(function ($part, $idx) {
            return [
                'key' => 'wip-' . $idx,
                'id' => isset($part['id']) ? (string) $part['id'] : '',
                'part_no' => $part['part_no'] ?? '',
                'part_name' => $part['part_name'] ?? '',
                'rm_part_id' => isset($part['rm_part_id']) ? (string) $part['rm_part_id'] : '',
                'rm_part_no' => $part['rm_part_no'] ?? '',
                'rm_part_name' => $part['rm_part_name'] ?? '',
                'process_name' => $part['process_name'] ?? '',
                'bom_item_id' => isset($part['bom_item_id']) ? (string) $part['bom_item_id'] : '',
            ];
        })->all();

        return view('contract-numbers.index', [
            'contracts' => $contracts,
            'vendors' => $vendors,
            'filters' => compact('search', 'vendorId', 'status'),
            'subconPartsJson' => $subconPartsJson,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        \Illuminate\Support\Facades\DB::transaction(function() use ($data, $request) {
            $contractNumber = ContractNumber::create($data);
            $this->syncItems($contractNumber, $request->input('items', []));
        });

        return redirect()->route('contract-numbers.index')->with('success', 'Nomor kontrak berhasil dibuat.');
    }

    public function show(ContractNumber $contractNumber)
    {
        $contractNumber->load(['vendor', 'creator', 'updater', 'items.gciPart', 'items.rmPart', 'items.bomItem']);

        $vendors = Vendor::query()
            ->where('status', 'active')
            ->orderBy('vendor_name')
            ->get(['id', 'vendor_name']);

        $subconParts = $this->getSubconPartOptions();
        $subconPartsJson = collect($subconParts)->values()->map(function ($part, $idx) {
            return [
                'key' => 'wip-' . $idx,
                'id' => isset($part['id']) ? (string) $part['id'] : '',
                'part_no' => $part['part_no'] ?? '',
                'part_name' => $part['part_name'] ?? '',
                'rm_part_id' => isset($part['rm_part_id']) ? (string) $part['rm_part_id'] : '',
                'rm_part_no' => $part['rm_part_no'] ?? '',
                'rm_part_name' => $part['rm_part_name'] ?? '',
                'process_name' => $part['process_name'] ?? '',
                'bom_item_id' => isset($part['bom_item_id']) ? (string) $part['bom_item_id'] : '',
            ];
        })->all();

        $editItemsJson = $contractNumber->items->map(function ($item) use ($subconPartsJson) {
            $opt = collect($subconPartsJson)->firstWhere('bom_item_id', (string) $item->bom_item_id);

            return [
                'id' => $item->id,
                'selected_part_key' => $opt ? $opt['key'] : '',
                'gci_part_id' => $item->gci_part_id,
                'rm_gci_part_id' => $item->rm_gci_part_id,
                'process_type' => $item->process_type,
                'bom_item_id' => $item->bom_item_id,
                'target_qty' => (float) $item->target_qty,
                'warning_limit_qty' => $item->warning_limit_qty !== null ? (float) $item->warning_limit_qty : '',
            ];
        })->values()->all();

        return view('contract-numbers.show', [
            'contract' => $contractNumber,
            'vendors' => $vendors,
            'subconPartsJson' => $subconPartsJson,
            'editItemsJson' => $editItemsJson,
        ]);
    }

    public function update(Request $request, ContractNumber $contractNumber)
    {
        $data = $this->validatedData($request, $contractNumber);
        $data['updated_by'] = auth()->id();

        \Illuminate\Support\Facades\DB::transaction(function() use ($data, $request, $contractNumber) {
            $contractNumber->update($data);
            $this->syncItems($contractNumber, $request->input('items', []));
        });

        return redirect()->route('contract-numbers.show', $contractNumber)->with('success', 'Nomor kontrak berhasil diperbarui.');
    }

    public function destroy(ContractNumber $contractNumber)
    {
        $contractNumber->delete();

        return redirect()->route('contract-numbers.index')->with('success', 'Nomor kontrak berhasil dihapus.');
    }

    public function byVendor(Vendor $vendor)
    {
        $contracts = ContractNumber::query()
            ->with(['items.gciPart', 'items.rmPart'])
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
                'items' => $contract->items->map(fn($item) => [
                    'gci_part_id' => $item->gci_part_id,
                    'wip_part_no' => $item->gciPart->part_no ?? '-',
                    'wip_part_name' => $item->gciPart->part_name ?? '-',
                    'rm_gci_part_id' => $item->rm_gci_part_id,
                    'rm_part_no' => $item->rmPart->part_no ?? '-',
                    'rm_part_name' => $item->rmPart->part_name ?? '-',
                    'process_type' => $item->process_type,
                    'bom_item_id' => $item->bom_item_id,
                    'target_qty' => $item->target_qty,
                    'sent_qty' => $item->sent_qty,
                    'remaining_qty' => max(0, (float)$item->target_qty - (float)$item->sent_qty),
                    'warning_limit_qty' => $item->warning_limit_qty,
                ]),
            ])
            ->values();

        return response()->json([
            'contracts' => $contracts,
        ]);
    }

    private function syncItems(ContractNumber $contractNumber, array $items)
    {
        $contractNumber->items()->delete();
        foreach ($items as $item) {
            if (!empty($item['gci_part_id']) && !empty($item['rm_gci_part_id'])) {
                $contractNumber->items()->create([
                    'gci_part_id' => $item['gci_part_id'],
                    'rm_gci_part_id' => $item['rm_gci_part_id'],
                    'process_type' => $item['process_type'] ?? '',
                    'bom_item_id' => $item['bom_item_id'] ?? null,
                    'target_qty' => $item['target_qty'] ?? 0,
                    'warning_limit_qty' => $item['warning_limit_qty'] ?? null,
                ]);
            }
        }
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
            'items' => ['nullable', 'array'],
            'items.*.gci_part_id' => ['required_with:items', 'exists:gci_parts,id'],
            'items.*.rm_gci_part_id' => ['required_with:items', 'exists:gci_parts,id'],
            'items.*.target_qty' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.warning_limit_qty' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function getSubconPartOptions()
    {
        $today = now()->toDateString();
        return \App\Models\BomItem::query()
            ->where('special', 'T')
            ->whereNotNull('wip_part_id')
            ->whereNotNull('component_part_id')
            ->whereHas('bom', function ($query) use ($today) {
                $query->where('status', 'active')
                    ->whereDate('effective_date', '<=', $today)
                    ->where(function ($subQuery) use ($today) {
                        $subQuery->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', $today);
                    });
            })
            ->with(['wipPart', 'componentPart'])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->wip_part_id,
                    'part_no' => $item->wipPart->part_no ?? $item->wip_part_no,
                    'part_name' => $item->wipPart->part_name ?? $item->wip_part_name,
                    'rm_part_id' => $item->component_part_id,
                    'rm_part_no' => $item->componentPart->part_no ?? $item->component_part_no,
                    'rm_part_name' => $item->componentPart->part_name ?? $item->material_name,
                    'process_name' => $item->process_name,
                    'bom_item_id' => $item->id,
                ];
            })
            ->filter(fn ($item) => !empty($item['id']) && !empty($item['part_no']))
            ->unique(fn ($item) => implode('|', [
                $item['id'] ?? '',
                $item['rm_part_id'] ?? '',
                strtoupper(trim((string) ($item['process_name'] ?? ''))),
            ]))
            ->sortBy([
                ['part_no', 'asc'],
                ['process_name', 'asc'],
            ])
            ->values();
    }
}
