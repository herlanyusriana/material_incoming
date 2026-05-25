<?php

namespace App\Http\Controllers;

use App\Models\BomItem;
use App\Models\Bom;
use App\Models\ContractNumber;
use App\Models\ContractNumberItem;
use App\Models\GciPart;
use App\Models\LocationInventory;
use App\Models\LocationInventoryAdjustment;
use App\Models\PricingMaster;
use App\Models\ProductionInspection;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderActivity;
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
        $query = SubconOrder::with(['vendor', 'rmPart', 'gciPart', 'creator', 'receives', 'bomItem.consumptionUom', 'bomItem.wipUom'])
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
        $contractRemainRows = collect();
        $contractAlarmRows = collect();

        if ($mode === 'traceability') {
            $contractRemainRows = $this->buildContractRemainRows($request);
            $contractAlarmRows = $contractRemainRows
                ->filter(fn ($row) => $row['is_alarm'])
                ->values();
        }

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
            ? 'Monitor sisa kontrak/SKEP subcon dan alarm material yang perlu dibuatkan SKEP baru.'
            : 'Daftar order subcon yang masih outstanding dan siap diterima kembali dari vendor.';

        return view('subcon.index', compact(
            'orders',
            'stats',
            'vendors',
            'mode',
            'pageTitle',
            'pageDescription',
            'contractRemainRows',
            'contractAlarmRows'
        ));
    }

    private function buildContractRemainRows(Request $request)
    {
        return ContractNumberItem::query()
            ->with(['contractNumber.vendor', 'gciPart', 'rmPart', 'bomItem.consumptionUom', 'bomItem.wipUom'])
            ->whereHas('contractNumber', function ($query) use ($request) {
                $query->where('status', 'active');

                if ($request->filled('vendor_id')) {
                    $query->where('vendor_id', $request->vendor_id);
                }

                if ($request->filled('date_from')) {
                    $query->where(function ($dateQuery) use ($request) {
                        $dateQuery->whereNull('effective_to')
                            ->orWhereDate('effective_to', '>=', $request->date_from);
                    });
                }

                if ($request->filled('date_to')) {
                    $query->whereDate('effective_from', '<=', $request->date_to);
                }
            })
            ->get()
            ->map(function (ContractNumberItem $item) {
                $targetQty = (float) $item->target_qty;
                $sentQty = (float) $item->sent_qty;
                $rejectedQty = (float) $item->rejected_qty;
                $remainingQty = max(0, $targetQty - $sentQty - $rejectedQty);
                $warningLimit = $item->warning_limit_qty !== null ? (float) $item->warning_limit_qty : null;

                return [
                    'contract_no' => $item->contractNumber?->contract_no ?? '-',
                    'vendor_name' => $item->contractNumber?->vendor?->vendor_name ?? '-',
                    'rm_part_no' => $item->rmPart?->part_no ?? '-',
                    'rm_part_name' => $item->rmPart?->part_name ?? '-',
                    'wip_part_no' => $item->gciPart?->part_no ?? '-',
                    'wip_part_name' => $item->gciPart?->part_name ?? '-',
                    'process_type' => $item->process_type ?: '-',
                    'uom' => $this->resolveSubconUom($item->bomItem, $item->rmPart, $item->gciPart),
                    'rm_net_weight' => (float) ($item->rmPart->net_weight ?? 0),
                    'target_qty' => $targetQty,
                    'sent_qty' => $sentQty,
                    'rejected_qty' => $rejectedQty,
                    'remaining_qty' => $remainingQty,
                    'warning_limit_qty' => $warningLimit,
                    'is_alarm' => $remainingQty > 0 && $warningLimit !== null && $remainingQty <= $warningLimit,
                    'effective_to' => $item->contractNumber?->effective_to,
                    'remaining_kgm' => $remainingQty * (float) ($item->rmPart->net_weight ?? 0),
                ];
            })
            ->filter(fn ($row) => $row['remaining_qty'] > 0)
            ->sortBy([
                ['is_alarm', 'desc'],
                ['remaining_qty', 'asc'],
                ['contract_no', 'asc'],
                ['rm_part_no', 'asc'],
            ])
            ->values();
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
                'uom' => $part['uom'] ?? 'PCS',
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
            ->with(['items.gciPart', 'items.rmPart', 'items.bomItem.consumptionUom', 'items.bomItem.wipUom'])
            ->where('status', 'active')
            ->orderBy('contract_no')
            ->get()
            ->map(fn (ContractNumber $contract) => [
                'id' => (string) $contract->id,
                'vendor_id' => (string) $contract->vendor_id,
                'contract_no' => $contract->contract_no,
                'description' => $contract->description ?? '',
                'items' => $contract->items->map(fn($item) => [
                    'gci_part_id' => (string)$item->gci_part_id,
                    'rm_gci_part_id' => (string)$item->rm_gci_part_id,
                    'wip_part_no' => $item->gciPart->part_no ?? '-',
                    'wip_part_name' => $item->gciPart->part_name ?? '-',
                    'rm_part_no' => $item->rmPart->part_no ?? '-',
                    'rm_part_name' => $item->rmPart->part_name ?? '-',
                    'process_type' => $item->process_type,
                    'bom_item_id' => (string)$item->bom_item_id,
                    'uom' => $this->resolveSubconUom($item->bomItem, $item->rmPart, $item->gciPart),
                    'target_qty' => (float)$item->target_qty,
                    'sent_qty' => (float)$item->sent_qty,
                    'rejected_qty' => (float)$item->rejected_qty,
                    'remaining_qty' => (float)$item->remaining_qty,
                ]),
            ])
            ->values()
            ->all();

        $sendOrders = SubconOrder::with(['vendor', 'rmPart', 'gciPart', 'receives', 'bomItem.consumptionUom', 'bomItem.wipUom'])
            ->whereIn('status', ['sent', 'partial', 'completed'])
            ->orderByDesc('sent_date')
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        return view('subcon.create', compact('vendors', 'subconParts', 'rmParts', 'subconPartsJson', 'rmPartsJson', 'oldRows', 'contractsJson', 'sendOrders'));
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
            'items.*.qty_sent' => 'required|integer|min:0',
            'items.*.weight_kgm' => 'nullable|numeric|min:0',
            'items.*.send_location_code' => ['nullable', 'string', 'max:50', Rule::exists('warehouse_locations', 'location_code')],
        ]);

        // Guard: Ensure at least one item has qty > 0
        $allZero = true;
        foreach($validated['items'] as $it) {
            if ((float)($it['qty_sent'] ?? 0) > 0) {
                $allZero = false;
                break;
            }
        }
        if ($allZero) {
            return back()->withInput()->with('error', 'Minimal harus mengisi 1 part dengan Qty Sent lebih dari 0.');
        }

        try {
            return DB::transaction(function () use ($validated, $request) {
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

                // Validate if this contract is recorded in master contract numbers, enforce quantity limit
                $masterContract = \App\Models\ContractNumber::with('items')
                    ->where('contract_no', $contractNo)
                    ->where('vendor_id', $validated['vendor_id'])
                    ->first();

                foreach ($validated['items'] as $item) {
                    $qtySent = (int) ($item['qty_sent'] ?? 0);
                    if ($qtySent <= 0) {
                        continue;
                    }
                    $rmPart = GciPart::query()->findOrFail((int) $item['rm_gci_part_id']);
                    $resolvedSendLocation = strtoupper(trim((string) ($item['send_location_code'] ?? '')));
                    if ($resolvedSendLocation === '') {
                        // User left it blank -> "pull from anywhere" dummy code
                        $resolvedSendLocation = 'WIP-BYPASS';
                    }
                    
                    // Quantity Limit check against master contract
                    if ($masterContract && $masterContract->items->isNotEmpty()) {
                        $matchedItem = $masterContract->items->first(function($i) use ($item) {
                            return $i->gci_part_id == $item['gci_part_id'] && 
                                   $i->rm_gci_part_id == $item['rm_gci_part_id'] && 
                                   $i->process_type === $item['process_type'];
                        });
                        
                        if (!$matchedItem) {
                            throw new \RuntimeException('Part/Proses belum dipetakan (mapping) di dalam Kontrak No: ' . $contractNo);
                        }
                        
                        if ($qtySent > (int) floor((float) $matchedItem->remaining_qty)) {
                            throw new \RuntimeException("Qty dikirim (" . number_format($qtySent) . ") untuk RM " . ($rmPart->part_no ?? '-') . " melebihi sisa kontrak (" . number_format((float) $matchedItem->remaining_qty) . ").");
                        }
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
                        'qty_sent' => $qtySent,
                        'sent_date' => $validated['sent_date'],
                        'expected_return_date' => $validated['expected_return_date'] ?? null,
                        'notes' => $validated['notes'] ?? null,
                        'status' => 'sent',
                        'created_by' => Auth::id(),
                        'send_location_code' => $resolvedSendLocation,
                        'sent_posted_at' => now(),
                        'sent_posted_by' => Auth::id(),
                        'weight_kgm' => $item['weight_kgm'] ?? null,
                    ];

                    $order = SubconOrder::create($payload);

                    LocationInventory::consumeStock(
                        null,
                        $resolvedSendLocation,
                        (float) $qtySent,
                        null,
                        (int) $item['rm_gci_part_id'],
                        'SUBCON_SEND',
                        $order->order_no,
                        [],
                        (float) ($item['weight_kgm'] ?? 0)
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
        $subconOrder->load(['vendor', 'rmPart', 'gciPart', 'bomItem.consumptionUom', 'bomItem.wipUom', 'receives.creator', 'creator', 'sender']);
        $rejectReceives = $subconOrder->receives
            ->filter(fn (SubconOrderReceive $receive) => (float) $receive->qty_rejected > 0)
            ->values();
        $traceability = LocationInventoryAdjustment::with(['creator'])
            ->where('source_reference', $subconOrder->order_no)
            ->orderBy('adjusted_at')
            ->orderBy('id')
            ->get();

        return view('subcon.show', compact('subconOrder', 'rejectReceives', 'traceability'));
    }

    public function printSuratJalan(SubconOrder $subconOrder)
    {
        $orders = $this->suratJalanGroupQuery($subconOrder)->get();
        $payload = $this->buildPrintPayloadForOrders($orders, $subconOrder);

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

    public function printReceiveLabel(SubconOrderReceive $subconOrderReceive)
    {
        $subconOrderReceive->load(['subconOrder.gciPart', 'subconOrder.rmPart', 'subconOrder.bomItem.consumptionUom', 'subconOrder.bomItem.wipUom']);
        return view('subcon.print_receive_label', compact('subconOrderReceive'));
    }

    public function printReceivePL(SubconOrderReceive $subconOrderReceive)
    {
        $subconOrderReceive->load(['subconOrder.gciPart', 'subconOrder.rmPart', 'subconOrder.vendor', 'subconOrder.bomItem.consumptionUom', 'subconOrder.bomItem.wipUom']);
        return view('subcon.print_receive_pl', compact('subconOrderReceive'));
    }

    public function contractReceive(Request $request)
    {
        $validated = $request->validate([
            'contract_no' => ['required', 'string', 'max:100'],
            'vendor_id' => ['nullable', 'integer'],
        ]);

        $orders = $this->contractOutstandingQuery($validated['contract_no'], $validated['vendor_id'] ?? null)
            ->orderBy('sent_date')
            ->orderBy('order_no')
            ->get();

        if ($orders->isEmpty()) {
            return redirect()->route('subcon.receive-index')->with('error', 'Tidak ada item outstanding untuk kontrak tersebut.');
        }

        $contractNo = $validated['contract_no'];
        $vendorId = $validated['vendor_id'] ?? null;
        $vendor = $orders->first()->vendor;

        return view('subcon.contract_receive', compact('orders', 'contractNo', 'vendorId', 'vendor'));
    }

    public function storeContractReceive(Request $request)
    {
        $validated = $request->validate([
            'contract_no' => ['required', 'string', 'max:100'],
            'vendor_id' => ['nullable', 'integer'],
            'received_date' => ['required', 'date'],
            'receive_location_code' => ['nullable', 'string', 'max:50'],
            'reject_location_code' => ['nullable', 'string', 'max:50'],
            'sj_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'invoice_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array'],
            'items.*.subcon_order_id' => ['required', 'integer', 'exists:subcon_orders,id'],
            'items.*.qty_good' => ['nullable', 'integer', 'min:0'],
            'items.*.qty_rejected' => ['nullable', 'integer', 'min:0'],
            'items.*.weight_kgm' => ['nullable', 'numeric', 'min:0'],
            'items.*.weight_rejected_kgm' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = collect($validated['items'])
            ->map(function ($item) {
                $item['qty_good'] = (int) ($item['qty_good'] ?? 0);
                $item['qty_rejected'] = (int) ($item['qty_rejected'] ?? 0);
                return $item;
            })
            ->filter(fn ($item) => $item['qty_good'] > 0 || $item['qty_rejected'] > 0)
            ->values();

        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Isi minimal satu item dengan Qty Good atau Qty Rejected lebih dari nol.');
        }

        $allowedOrders = $this->contractOutstandingQuery($validated['contract_no'], $validated['vendor_id'] ?? null)
            ->whereIn('id', $items->pluck('subcon_order_id')->all())
            ->get()
            ->keyBy('id');

        if ($allowedOrders->count() !== $items->count()) {
            return back()->withInput()->with('error', 'Ada item yang tidak valid atau sudah tidak outstanding.');
        }

        foreach ($items as $item) {
            $order = $allowedOrders->get((int) $item['subcon_order_id']);
            $qtyTotal = (int) $item['qty_good'] + (int) $item['qty_rejected'];

            if ($qtyTotal > (int) $order->qty_outstanding) {
                return back()->withInput()->with('error', "Qty receive {$order->order_no} melebihi outstanding.");
            }
        }

        $common = [
            'received_date' => $validated['received_date'],
            'receive_location_code' => strtoupper(trim((string) ($validated['receive_location_code'] ?? ''))) ?: 'WIP-BYPASS',
            'reject_location_code' => strtoupper(trim((string) ($validated['reject_location_code'] ?? ''))) ?: 'WIP-REJECT',
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ];

        if ($request->hasFile('sj_file')) {
            $common['sj_file_path'] = $request->file('sj_file')->store('subcon_docs', 'public');
        }
        if ($request->hasFile('invoice_file')) {
            $common['invoice_file_path'] = $request->file('invoice_file')->store('subcon_docs', 'public');
        }

        $productionMessages = [];

        DB::transaction(function () use ($items, $allowedOrders, $common, &$productionMessages) {
            foreach ($items as $item) {
                $order = $allowedOrders->get((int) $item['subcon_order_id']);
                $netWeight = (float) ($order->rmPart?->net_weight ?? 0);
                $goodWeight = array_key_exists('weight_kgm', $item) && (float) ($item['weight_kgm'] ?? 0) > 0
                    ? (float) $item['weight_kgm']
                    : (float) $item['qty_good'] * $netWeight;
                $rejectedWeight = array_key_exists('weight_rejected_kgm', $item) && (float) ($item['weight_rejected_kgm'] ?? 0) > 0
                    ? (float) $item['weight_rejected_kgm']
                    : (float) $item['qty_rejected'] * $netWeight;
                $payload = array_merge($common, [
                    'qty_good' => (int) $item['qty_good'],
                    'qty_rejected' => (int) $item['qty_rejected'],
                    'weight_kgm' => $goodWeight,
                    'weight_rejected_kgm' => $rejectedWeight,
                ]);

                $message = $this->recordSubconReceive($order, $payload);
                if ($message) {
                    $productionMessages[] = $message;
                }
            }
        });

        $message = 'Receive kontrak berhasil disimpan untuk ' . $items->count() . ' item.';
        if ($productionMessages) {
            $message .= ' ' . implode(' ', array_unique($productionMessages));
        }

        return redirect()->route('subcon.receive-index')->with('success', $message);
    }

    public function receive(Request $request, SubconOrder $subconOrder)
    {
        if (in_array($subconOrder->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'Cannot receive on a completed/cancelled order.');
        }

        $validated = $request->validate([
            'qty_good' => 'required|integer|min:0',
            'qty_rejected' => 'nullable|integer|min:0',
            'weight_kgm' => 'nullable|numeric|min:0',
            'weight_rejected_kgm' => 'nullable|numeric|min:0',
            'received_date' => 'required|date',
            'receive_location_code' => ['nullable', 'string', 'max:50'],
            'reject_location_code' => ['nullable', 'string', 'max:50'],
            'sj_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'invoice_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['qty_good'] = (int) ($validated['qty_good'] ?? 0);
        $validated['qty_rejected'] = (int) ($validated['qty_rejected'] ?? 0);

        if ($validated['qty_good'] <= 0 && $validated['qty_rejected'] <= 0) {
            return back()->withInput()->with('error', 'Isi minimal Qty Good atau Qty Rejected lebih dari nol.');
        }

        if (empty($validated['receive_location_code'])) {
            $validated['receive_location_code'] = 'WIP-BYPASS';
        }
        if (empty($validated['reject_location_code'])) {
            $validated['reject_location_code'] = 'WIP-REJECT';
        }

        if ($request->hasFile('sj_file')) {
            $validated['sj_file_path'] = $request->file('sj_file')->store('subcon_docs', 'public');
        }
        if ($request->hasFile('invoice_file')) {
            $validated['invoice_file_path'] = $request->file('invoice_file')->store('subcon_docs', 'public');
        }

        $validated['created_by'] = Auth::id();
        $validated['receive_location_code'] = strtoupper(trim((string) $validated['receive_location_code']));
        $validated['reject_location_code'] = strtoupper(trim((string) $validated['reject_location_code']));
        $validated['posted_to_wh_at'] = $validated['qty_good'] > 0 ? now() : null;
        $validated['reject_posted_to_wh_at'] = $validated['qty_rejected'] > 0 ? now() : null;

        $productionMessage = null;

        DB::transaction(function () use ($subconOrder, $validated, &$productionMessage) {
            $productionMessage = $this->recordSubconReceive($subconOrder, $validated);
        });

        return back()->with('success', 'Receive recorded successfully.' . ($productionMessage ? ' ' . $productionMessage : ''));
    }

    private function contractOutstandingQuery(string $contractNo, ?int $vendorId = null)
    {
        return SubconOrder::with(['vendor', 'rmPart', 'gciPart', 'creator', 'receives', 'bomItem.consumptionUom', 'bomItem.wipUom'])
            ->where('contract_no', $contractNo)
            ->when($vendorId, fn ($query) => $query->where('vendor_id', $vendorId))
            ->whereIn('status', ['sent', 'partial'])
            ->whereRaw('(qty_sent - qty_received - qty_rejected) > 0');
    }

    private function recordSubconReceive(SubconOrder $subconOrder, array $payload): ?string
    {
        $payload['posted_to_wh_at'] = (int) ($payload['qty_good'] ?? 0) > 0 ? now() : null;
        $payload['reject_posted_to_wh_at'] = (int) ($payload['qty_rejected'] ?? 0) > 0 ? now() : null;

        $receive = $subconOrder->receives()->create($payload);

        if ((float) $payload['qty_good'] > 0) {
            LocationInventory::updateStock(
                null,
                $payload['receive_location_code'],
                (float) $payload['qty_good'],
                null,
                $payload['received_date'],
                (int) $subconOrder->gci_part_id,
                'SUBCON_RECEIVE',
                $subconOrder->order_no,
                [],
                null,
                (float) ($payload['weight_kgm'] ?? 0)
            );
        }

        if ((float) $payload['qty_rejected'] > 0) {
            LocationInventory::updateStock(
                null,
                $payload['reject_location_code'],
                (float) $payload['qty_rejected'],
                null,
                $payload['received_date'],
                (int) $subconOrder->gci_part_id,
                'SUBCON_REJECT_RECEIVE',
                $subconOrder->order_no,
                [],
                null,
                (float) ($payload['weight_rejected_kgm'] ?? 0)
            );
        }

        $subconOrder->increment('qty_received', (int) $payload['qty_good']);
        $subconOrder->increment('qty_rejected', (int) $payload['qty_rejected']);
        $subconOrder->refresh();

        if ($subconOrder->qty_outstanding <= 0) {
            $subconOrder->update([
                'status' => 'completed',
                'received_date' => $payload['received_date'],
            ]);

            return $this->releaseLinkedProductionOrderFromSubcon($subconOrder->fresh(), $receive);
        }

        $subconOrder->update(['status' => 'partial']);

        return null;
    }

    private function releaseLinkedProductionOrderFromSubcon(SubconOrder $subconOrder, SubconOrderReceive $receive): ?string
    {
        if (!Schema::hasColumn('subcon_orders', 'production_order_id') || !$subconOrder->production_order_id) {
            return null;
        }

        $order = ProductionOrder::with('part')->find($subconOrder->production_order_id);
        if (!$order) {
            return null;
        }

        if (in_array((string) $order->workflow_stage, ['final_inspection', 'kanban_update', 'warehouse_supply', 'finished'], true)
            || in_array((string) $order->status, ['finished', 'completed', 'cancelled'], true)
        ) {
            return null;
        }

        $nextItem = $this->nextBomItemAfterSubcon($order, $subconOrder);
        $qtyGood = (float) ($receive->qty_good ?? 0);
        $qtyRejected = (float) ($receive->qty_rejected ?? 0);
        $targetProcess = (string) ($subconOrder->target_process_name ?: $subconOrder->process_type);

        if ($nextItem) {
            $machineName = $nextItem->machine?->name;
            $payload = [
                'status' => 'released',
                'workflow_stage' => 'mass_production',
                'process_name' => trim((string) ($nextItem->process_name ?? '')) ?: 'Process',
                'machine_id' => $nextItem->machine_id,
                'last_handover_from_process' => $targetProcess,
                'last_handover_from_machine_id' => null,
                'last_handover_from_machine_name' => $subconOrder->vendor?->vendor_name,
                'last_handover_at' => now(),
            ];

            if (Schema::hasColumn('production_orders', 'machine_name')) {
                $payload['machine_name'] = $machineName;
            }

            $order->update($payload);

            $this->recordSubconProductionActivity($order->fresh(), 'subcon_received_release', $subconOrder, $receive, [
                'process_name' => $targetProcess,
                'output_type' => 'wip',
                'output_part_no' => $subconOrder->gciPart?->part_no,
                'output_part_name' => $subconOrder->gciPart?->part_name,
                'qty_ok' => $qtyGood,
                'qty_ng' => $qtyRejected,
                'meta' => [
                    'next_process_name' => $payload['process_name'],
                    'next_machine_id' => $nextItem->machine_id,
                    'next_machine_name' => $machineName,
                ],
            ]);

            return 'WO ' . ($order->production_order_number ?? $order->id) . ' lanjut ke proses ' . $payload['process_name'] . '.';
        }

        $finalActual = max((float) ($order->qty_actual ?? 0), (float) $subconOrder->qty_received);
        $finalNg = max((float) ($order->qty_ng ?? 0), (float) $subconOrder->qty_rejected);

        $order->update([
            'status' => 'finished',
            'workflow_stage' => 'final_inspection',
            'process_name' => null,
            'machine_id' => null,
            'end_time' => now(),
            'qty_actual' => $finalActual,
            'qty_ng' => $finalNg,
            'last_handover_from_process' => $targetProcess,
            'last_handover_from_machine_id' => null,
            'last_handover_from_machine_name' => $subconOrder->vendor?->vendor_name,
            'last_handover_at' => now(),
        ]);

        if (Schema::hasColumn('production_orders', 'machine_name')) {
            $order->update(['machine_name' => null]);
        }

        if (!$order->inspections()->where('type', 'final')->exists()) {
            ProductionInspection::create([
                'production_order_id' => $order->id,
                'type' => 'final',
                'status' => 'pending',
            ]);
        }

        $this->recordSubconProductionActivity($order->fresh(), 'subcon_received_finish', $subconOrder, $receive, [
            'process_name' => $targetProcess,
            'output_type' => 'fg',
            'output_part_no' => $order->part?->part_no,
            'output_part_name' => $order->part?->part_name,
            'qty_ok' => $qtyGood,
            'qty_ng' => $qtyRejected,
        ]);

        return 'WO ' . ($order->production_order_number ?? $order->id) . ' selesai dan masuk final inspection.';
    }

    private function nextBomItemAfterSubcon(ProductionOrder $order, SubconOrder $subconOrder): ?BomItem
    {
        $bom = Bom::activeVersion($order->gci_part_id, $order->plan_date);
        if (!$bom) {
            return null;
        }

        $items = $bom->items()
            ->with('machine')
            ->orderBy('line_no')
            ->orderBy('id')
            ->get()
            ->values();

        $currentIndex = $items->search(fn (BomItem $item) => (int) $item->id === (int) ($subconOrder->bom_item_id ?? 0));
        if ($currentIndex === false) {
            $targetProcess = strtolower(trim((string) ($subconOrder->target_process_name ?: $subconOrder->process_type)));
            $currentIndex = $items->search(function (BomItem $item) use ($targetProcess) {
                return strtolower(trim((string) $item->process_name)) === $targetProcess;
            });
        }

        if ($currentIndex === false) {
            return null;
        }

        return $items->get($currentIndex + 1);
    }

    private function recordSubconProductionActivity(
        ProductionOrder $order,
        string $type,
        SubconOrder $subconOrder,
        SubconOrderReceive $receive,
        array $data
    ): void {
        ProductionOrderActivity::create([
            'production_order_id' => $order->id,
            'activity_type' => $type,
            'process_name' => $data['process_name'] ?? $order->process_name,
            'machine_id' => null,
            'machine_name' => $subconOrder->vendor?->vendor_name,
            'shift' => $order->shift,
            'operator_name' => Auth::user()?->name,
            'output_type' => $data['output_type'] ?? null,
            'output_part_no' => $data['output_part_no'] ?? null,
            'output_part_name' => $data['output_part_name'] ?? null,
            'qty_ok' => (float) ($data['qty_ok'] ?? 0),
            'qty_ng' => (float) ($data['qty_ng'] ?? 0),
            'notes' => 'Receive Subcon ' . $subconOrder->order_no,
            'meta' => array_merge([
                'source' => 'web_subcon_receive',
                'subcon_order_id' => $subconOrder->id,
                'subcon_order_no' => $subconOrder->order_no,
                'subcon_receive_id' => $receive->id,
                'vendor_id' => $subconOrder->vendor_id,
                'vendor_name' => $subconOrder->vendor?->vendor_name,
                'contract_no' => $subconOrder->contract_no,
            ], $data['meta'] ?? []),
        ]);
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
            ->when(
                Schema::hasColumn('bom_items', 'special'),
                fn ($query) => $query->where('special', 'T'),
                fn ($query) => $query->whereRaw('1 = 0')
            )
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
            ->with(['wipPart', 'componentPart', 'bom.part', 'consumptionUom', 'wipUom'])
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
                    'uom' => $this->resolveSubconUom($item, $item->componentPart, $item->wipPart),
                    'rm_net_weight' => (float) ($item->componentPart->net_weight ?? 0),
                    'wip_net_weight' => (float) ($item->wipPart->net_weight ?? 0),
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
        return $this->buildPrintPayloadForOrders(collect([$subconOrder]), $subconOrder);
    }

    private function buildPrintPayloadForOrders($orders, ?SubconOrder $primaryOrder = null): array
    {
        $orders = collect($orders)->filter()->values();

        if ($orders->isEmpty() && $primaryOrder) {
            $orders = collect([$primaryOrder]);
        }

        $orders->each(fn (SubconOrder $order) => $order->loadMissing(['vendor', 'rmPart.standardPacking', 'gciPart', 'creator', 'bomItem.consumptionUom', 'bomItem.wipUom']));
        $subconOrder = $primaryOrder ?? $orders->first();

        $currency = 'IDR';
        $lines = $orders->map(function (SubconOrder $order, int $index) use (&$currency) {
            $pricing = PricingMaster::resolveCurrentPrice(
                (int) ($order->rm_gci_part_id ?? 0),
                'subcon_price',
                ['vendor_id' => $order->vendor_id],
                $order->sent_date
            ) ?? PricingMaster::resolveCurrentPrice(
                (int) ($order->rm_gci_part_id ?? 0),
                'purchase_price',
                ['vendor_id' => $order->vendor_id],
                $order->sent_date
            );

            $currency = $pricing?->currency ?? $currency;
            $unitPrice = round((float) ($pricing?->price ?? 0), 3);
            $qty = round((float) $order->qty_sent, 4);
            $weightKgm = (float)($order->weight_kgm ?? 0) > 0
                ? (float)$order->weight_kgm
                : round($qty * (float) ($order->rmPart->net_weight ?? 0), 4);
            $weightKgm = round($weightKgm, 4);
            $grossWeightKgm = round($qty * (float) ($order->rmPart->gross_weight ?? 0), 4);
            if ($grossWeightKgm <= 0) {
                $grossWeightKgm = $weightKgm;
            }

            return [
                'no' => $index + 1,
                'order_no' => (string) ($order->order_no ?? '-'),
                'part_no' => (string) ($order->rmPart->part_no ?? '-'),
                'part_name' => (string) ($order->rmPart->part_name ?? '-'),
                'description' => 'RM part sent to subcon vendor. Return as WIP: '
                    . (string) ($order->gciPart->part_no ?? '-')
                    . ' - '
                    . (string) ($order->gciPart->part_name ?? '-'),
                'uom' => $this->resolveSubconUom($order->bomItem, $order->rmPart, $order->gciPart),
                'qty' => $qty,
                'weight_kgm' => $weightKgm,
                'gross_weight_kgm' => $grossWeightKgm,
                'net_weight' => round((float) ($order->rmPart->net_weight ?? 0), 4),
                'box_qty' => $this->resolveBoxQty($qty, (float) ($order->rmPart?->standardPacking?->packing_qty ?? 0)),
                'packing_uom' => $order->rmPart?->standardPacking?->kemasan ?: 'Box',
                'unit_price' => $unitPrice,
                'amount' => round($qty * $unitPrice, 2),
            ];
        })->values();

        return [
            'subconOrder' => $subconOrder,
            'orders' => $orders,
            'lines' => $lines,
            'sjNo' => 'SJ-SC-' . $subconOrder->order_no,
            'packingListNo' => 'PL-SC-' . $subconOrder->order_no,
            'invoiceNo' => 'INV-SC-' . $subconOrder->order_no,
            'currency' => $currency,
            'totalQty' => round((float) $lines->sum('qty'), 4),
            'totalWeight' => round((float) $lines->sum('weight_kgm'), 4),
            'totalGrossWeight' => round((float) $lines->sum('gross_weight_kgm'), 4),
            'totalAmount' => round((float) $lines->sum('amount'), 2),
        ];
    }

    private function suratJalanGroupQuery(SubconOrder $subconOrder)
    {
        return SubconOrder::query()
            ->where('contract_no', $subconOrder->contract_no)
            ->where('vendor_id', $subconOrder->vendor_id)
            ->when($subconOrder->sent_date, fn ($query) => $query->whereDate('sent_date', $subconOrder->sent_date))
            ->whereIn('status', ['sent', 'partial', 'completed'])
            ->with(['vendor', 'rmPart.standardPacking', 'gciPart', 'creator', 'bomItem.consumptionUom', 'bomItem.wipUom'])
            ->orderBy('order_no');
    }

    private function resolveBoxQty(float $qty, float $packingQty): ?int
    {
        if ($qty <= 0 || $packingQty <= 0) {
            return null;
        }

        return (int) ceil($qty / $packingQty);
    }

    private function resolveSubconUom(?BomItem $bomItem, ?GciPart $rmPart = null, ?GciPart $wipPart = null): string
    {
        $uom = $bomItem?->consumptionUom?->code
            ?? $bomItem?->consumption_uom
            ?? $rmPart?->uom
            ?? $bomItem?->wipUom?->code
            ?? $bomItem?->wip_uom
            ?? $wipPart?->uom
            ?? 'PCS';

        $uom = strtoupper(trim((string) $uom));

        return $uom !== '' ? $uom : 'PCS';
    }
}
