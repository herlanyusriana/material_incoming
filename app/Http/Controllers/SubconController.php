<?php

namespace App\Http\Controllers;

use App\Models\BomItem;
use App\Models\ContractNumber;
use App\Models\GciPart;
use App\Models\LocationInventory;
use App\Models\LocationInventoryAdjustment;
use App\Models\PricingMaster;
use App\Models\SubconOrder;
use App\Models\SubconOrderReceive;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SubconController extends Controller
{
    public function index(Request $request)
    {
        return $this->buildIndexResponse($request, 'receive');
    }

    public function receiveIndex(Request $request)
    {
        return $this->buildIndexResponse($request, 'receive');
    }

    public function traceabilityIndex(Request $request)
    {
        return $this->buildIndexResponse($request, 'traceability');
    }

    private function buildIndexResponse(Request $request, string $mode)
    {
        $query = SubconOrder::with(['vendor', 'rmPart', 'gciPart', 'creator'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } elseif ($mode === 'receive') {
            $query->whereIn('status', ['sent', 'partial']);
        }
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->filled('date_from')) {
            $query->where('sent_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('sent_date', '<=', $request->date_to);
        }

        if ($mode === 'receive') {
            $query->whereRaw('(qty_sent - qty_received - qty_rejected) > 0');
        }

        $orders = $query->paginate(25)->withQueryString();

        // Stats
        $stats = SubconOrder::selectRaw("
            count(*) as total,
            sum(case when status = 'sent' then 1 else 0 end) as sent,
            sum(case when status = 'partial' then 1 else 0 end) as partial,
            sum(case when status = 'completed' then 1 else 0 end) as completed,
            coalesce(sum(case when status in ('sent','partial') then qty_sent - qty_received - qty_rejected else 0 end), 0) as total_outstanding
        ")->first();

        $vendors = Vendor::query()
            ->where('vendor_type', 'tolling')
            ->orderBy('vendor_name')
            ->get(['id', 'vendor_name']);

        $pageTitle = $mode === 'traceability' ? 'Subcon Traceability' : 'WH Receive Subcon';
        $pageDescription = $mode === 'traceability'
            ? 'Histori dan audit trail send / receive / reject untuk order subcon.'
            : 'Daftar order subcon yang masih outstanding dan siap diterima kembali dari vendor.';

        return view('subcon.index', compact('orders', 'stats', 'vendors', 'mode', 'pageTitle', 'pageDescription'));
    }

    public function create()
    {
        $vendorColumns = ['id', 'vendor_name'];
        if (Schema::hasColumn('vendors', 'vendor_code')) {
            $vendorColumns[] = 'vendor_code';
        }
        $vendors = Vendor::query()
            ->where('vendor_type', 'tolling')
            ->orderBy('vendor_name')
            ->get($vendorColumns);

        $subconParts = $this->getSubconPartOptions();

        $rmParts = $subconParts
            ->filter(fn ($item) => !empty($item['rm_part_id']))
            ->unique('rm_part_id')
            ->sortBy([
                ['rm_part_no', 'asc'],
                ['rm_part_name', 'asc'],
            ])
            ->values()
            ->map(function ($item) {
                $part = !empty($item['rm_part_id']) ? GciPart::find($item['rm_part_id']) : null;
                $item['default_location'] = $part?->default_location;

                return $item;
            });

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
                'fg_part_no' => $part['fg_part_no'] ?? '',
                'fg_part_name' => $part['fg_part_name'] ?? '',
                'bom_item_id' => isset($part['bom_item_id']) ? (string) $part['bom_item_id'] : '',
            ];
        })->all();

        $rmPartsJson = collect($rmParts)->values()->map(function ($part, $idx) {
            return [
                'key' => 'rm-' . $idx,
                'rm_part_id' => isset($part['rm_part_id']) ? (string) $part['rm_part_id'] : '',
                'rm_part_no' => $part['rm_part_no'] ?? '',
                'rm_part_name' => $part['rm_part_name'] ?? '',
                'default_location' => $part['default_location'] ?? '',
            ];
        })->all();

        $oldRows = old('items', [[
            'gci_part_id' => '',
            'rm_gci_part_id' => '',
            'bom_item_id' => '',
            'process_type' => '',
            'qty_sent' => '',
            'send_location_code' => '',
        ]]);

        $contractsJson = ContractNumber::query()
            ->where('status', 'active')
            ->orderBy('contract_no')
            ->get(['id', 'vendor_id', 'contract_no', 'description'])
            ->map(fn (ContractNumber $contract) => [
                'id' => (string) $contract->id,
                'vendor_id' => (string) $contract->vendor_id,
                'contract_no' => $contract->contract_no,
                'description' => $contract->description ?? '',
            ])
            ->values()
            ->all();

        return view('subcon.create', compact('vendors', 'subconParts', 'rmParts', 'subconPartsJson', 'rmPartsJson', 'oldRows', 'contractsJson'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_no' => 'required|string|max:100',
            'vendor_id' => ['required', Rule::exists('vendors', 'id')->where(fn ($q) => $q->where('vendor_type', 'tolling'))],
            'sent_date' => 'required|date',
            'expected_return_date' => 'nullable|date|after_or_equal:sent_date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.rm_gci_part_id' => 'required|exists:gci_parts,id',
            'items.*.gci_part_id' => 'required|exists:gci_parts,id',
            'items.*.bom_item_id' => 'nullable|exists:bom_items,id',
            'items.*.process_type' => 'required|string|max:50',
            'items.*.qty_sent' => 'required|numeric|min:0.0001',
            'items.*.send_location_code' => ['nullable', 'string', 'max:50', Rule::exists('warehouse_locations', 'location_code')],
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $today = now()->format('Ymd');
                $lastOrder = SubconOrder::where('order_no', 'like', "SC-{$today}-%")
                    ->lockForUpdate()
                    ->orderByDesc('order_no')
                    ->first();
                $seq = $lastOrder
                    ? ((int) substr($lastOrder->order_no, -3)) + 1
                    : 1;
                $contractNo = strtoupper(trim((string) $validated['contract_no']));
                $createdOrders = [];

                foreach ($validated['items'] as $item) {
                    $rmPart = GciPart::query()->findOrFail((int) $item['rm_gci_part_id']);
                    $resolvedSendLocation = strtoupper(trim((string) ($item['send_location_code'] ?? '')));
                    if ($resolvedSendLocation === '') {
                        $resolvedSendLocation = strtoupper(trim((string) ($rmPart->default_location ?? '')));
                    }

                    if ($resolvedSendLocation === '') {
                        throw new \RuntimeException('Default location untuk RM part ' . ($rmPart->part_no ?? '-') . ' belum di-set. Mohon lengkapi default location part terlebih dahulu.');
                    }

                    $orderNo = sprintf('SC-%s-%03d', $today, $seq++);
                    $payload = [
                        'order_no' => $orderNo,
                        'contract_no' => $contractNo,
                        'vendor_id' => $validated['vendor_id'],
                        'rm_gci_part_id' => $item['rm_gci_part_id'],
                        'gci_part_id' => $item['gci_part_id'],
                        'bom_item_id' => $item['bom_item_id'] ?? null,
                        'process_type' => $item['process_type'],
                        'qty_sent' => $item['qty_sent'],
                        'sent_date' => $validated['sent_date'],
                        'expected_return_date' => $validated['expected_return_date'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'status' => 'sent',
                        'created_by' => Auth::id(),
                        'send_location_code' => $resolvedSendLocation,
                        'sent_posted_at' => now(),
                        'sent_posted_by' => Auth::id(),
                    ];

                    $order = SubconOrder::create($payload);

                    LocationInventory::consumeStock(
                        null,
                        $resolvedSendLocation,
                        (float) $item['qty_sent'],
                        null,
                        (int) $item['rm_gci_part_id'],
                        'SUBCON_SEND',
                        $order->order_no
                    );

                    $createdOrders[] = $order->order_no;
                }

                return redirect()->route('subcon.traceability-index')
                    ->with('success', 'Subcon order created: ' . implode(', ', $createdOrders));
            });
        } catch (\Throwable $e) {
            \Log::error('Subcon Order create failed', [
                'error' => $e->getMessage(),
                'input' => $request->except('_token'),
            ]);
            return back()->withInput()->with('error', 'Gagal membuat order: ' . $e->getMessage());
        }
    }

    public function show(SubconOrder $subconOrder)
    {
        $subconOrder->load(['vendor', 'rmPart', 'gciPart', 'bomItem', 'receives.creator', 'creator', 'sender']);
        $traceability = LocationInventoryAdjustment::with(['creator'])
            ->where('source_reference', $subconOrder->order_no)
            ->orderBy('adjusted_at')
            ->orderBy('id')
            ->get();

        return view('subcon.show', compact('subconOrder', 'traceability'));
    }

    public function printSuratJalan(SubconOrder $subconOrder)
    {
        $payload = $this->buildPrintPayload($subconOrder);

        return view('subcon.print_surat_jalan', $payload);
    }

    public function printPackingList(SubconOrder $subconOrder)
    {
        $payload = $this->buildPrintPayload($subconOrder);

        return view('subcon.print_packing_list', $payload);
    }

    public function printInvoice(SubconOrder $subconOrder)
    {
        $payload = $this->buildPrintPayload($subconOrder);

        return view('subcon.print_invoice', $payload);
    }

    public function receive(Request $request, SubconOrder $subconOrder)
    {
        if (in_array($subconOrder->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'Cannot receive on a completed/cancelled order.');
        }

        $validated = $request->validate([
            'qty_good' => 'required|numeric|min:0',
            'qty_rejected' => 'nullable|numeric|min:0',
            'received_date' => 'required|date',
            'receive_location_code' => ['nullable', 'string', 'max:50', Rule::exists('warehouse_locations', 'location_code')],
            'reject_location_code' => ['nullable', 'string', 'max:50', Rule::exists('warehouse_locations', 'location_code')],
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['qty_good'] = (float) ($validated['qty_good'] ?? 0);
        $validated['qty_rejected'] = (float) ($validated['qty_rejected'] ?? 0);

        if ($validated['qty_good'] <= 0 && $validated['qty_rejected'] <= 0) {
            return back()->withInput()->with('error', 'Isi minimal Qty Good atau Qty Rejected lebih dari nol.');
        }

        if ($validated['qty_good'] > 0 && empty($validated['receive_location_code'])) {
            return back()->withInput()->with('error', 'WH Receive Location wajib diisi jika Qty Good lebih dari nol.');
        }

        if ($validated['qty_rejected'] > 0 && empty($validated['reject_location_code'])) {
            return back()->withInput()->with('error', 'WH Reject Location wajib diisi jika Qty Rejected lebih dari nol.');
        }

        $validated['created_by'] = Auth::id();
        $validated['receive_location_code'] = !empty($validated['receive_location_code'])
            ? strtoupper(trim((string) $validated['receive_location_code']))
            : null;
        $validated['reject_location_code'] = !empty($validated['reject_location_code'])
            ? strtoupper(trim((string) $validated['reject_location_code']))
            : null;
        $validated['posted_to_wh_at'] = $validated['qty_good'] > 0 ? now() : null;
        $validated['reject_posted_to_wh_at'] = $validated['qty_rejected'] > 0 ? now() : null;

        DB::transaction(function () use ($subconOrder, $validated) {
            $receive = $subconOrder->receives()->create($validated);

            if ((float) $validated['qty_good'] > 0) {
                LocationInventory::updateStock(
                    null,
                    $validated['receive_location_code'],
                    (float) $validated['qty_good'],
                    null,
                    $validated['received_date'],
                    (int) $subconOrder->gci_part_id,
                    'SUBCON_RECEIVE',
                    $subconOrder->order_no
                );
            }

            if ((float) $validated['qty_rejected'] > 0) {
                LocationInventory::updateStock(
                    null,
                    $validated['reject_location_code'],
                    (float) $validated['qty_rejected'],
                    null,
                    $validated['received_date'],
                    (int) $subconOrder->gci_part_id,
                    'SUBCON_REJECT_RECEIVE',
                    $subconOrder->order_no
                );
            }

            $subconOrder->increment('qty_received', $validated['qty_good']);
            $subconOrder->increment('qty_rejected', $validated['qty_rejected']);

            $subconOrder->refresh();

            // Update status
            $outstanding = $subconOrder->qty_outstanding;
            if ($outstanding <= 0) {
                $subconOrder->update([
                    'status' => 'completed',
                    'received_date' => $validated['received_date'],
                ]);
            } else {
                $subconOrder->update(['status' => 'partial']);
            }
        });

        return back()->with('success', 'Receive recorded successfully.');
    }

    public function cancel(SubconOrder $subconOrder)
    {
        if ($subconOrder->status === 'completed') {
            return back()->with('error', 'Cannot cancel a completed order.');
        }

        $subconOrder->update(['status' => 'cancelled']);

        return back()->with('success', 'Order cancelled.');
    }

    public function parts()
    {
        $parts = $this->getSubconPartOptions()->values();

        return response()->json($parts);
    }

    private function getSubconPartOptions()
    {
        $today = now()->toDateString();

        return BomItem::query()
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
            ->with(['wipPart', 'componentPart', 'bom.part'])
            ->get()
            ->map(function (BomItem $item) {
                return [
                    'id' => $item->wip_part_id,
                    'part_no' => $item->wipPart->part_no ?? $item->wip_part_no,
                    'part_name' => $item->wipPart->part_name ?? $item->wip_part_name,
                    'rm_part_id' => $item->component_part_id,
                    'rm_part_no' => $item->componentPart->part_no ?? $item->component_part_no,
                    'rm_part_name' => $item->componentPart->part_name ?? $item->material_name,
                    'process_name' => $item->process_name,
                    'fg_part_no' => $item->bom?->part?->part_no ?? '',
                    'fg_part_name' => $item->bom?->part?->part_name ?? '',
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
                ['rm_part_no', 'asc'],
            ])
            ->values();
    }

    private function buildPrintPayload(SubconOrder $subconOrder): array
    {
        $subconOrder->loadMissing(['vendor', 'rmPart', 'gciPart', 'creator']);

        $pricing = PricingMaster::resolveCurrentPrice(
            (int) ($subconOrder->rm_gci_part_id ?? 0),
            'subcon_price',
            ['vendor_id' => $subconOrder->vendor_id],
            $subconOrder->sent_date
        ) ?? PricingMaster::resolveCurrentPrice(
            (int) ($subconOrder->rm_gci_part_id ?? 0),
            'purchase_price',
            ['vendor_id' => $subconOrder->vendor_id],
            $subconOrder->sent_date
        );

        $unitPrice = round((float) ($pricing?->price ?? 0), 3);
        $qty = round((float) $subconOrder->qty_sent, 4);

        $lines = collect([[
            'no' => 1,
            'part_no' => (string) ($subconOrder->rmPart->part_no ?? '-'),
            'part_name' => (string) ($subconOrder->rmPart->part_name ?? '-'),
            'description' => 'RM part sent to subcon vendor. Return as WIP: '
                . (string) ($subconOrder->gciPart->part_no ?? '-')
                . ' - '
                . (string) ($subconOrder->gciPart->part_name ?? '-'),
            'uom' => (string) ($subconOrder->rmPart->uom ?? 'PCS'),
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'amount' => round($qty * $unitPrice, 2),
        ]]);

        return [
            'subconOrder' => $subconOrder,
            'lines' => $lines,
            'sjNo' => 'SJ-SC-' . $subconOrder->order_no,
            'packingListNo' => 'PL-SC-' . $subconOrder->order_no,
            'invoiceNo' => 'INV-SC-' . $subconOrder->order_no,
            'currency' => $pricing?->currency ?? 'IDR',
            'totalQty' => round((float) $lines->sum('qty'), 4),
            'totalAmount' => round((float) $lines->sum('amount'), 2),
        ];
    }
}
