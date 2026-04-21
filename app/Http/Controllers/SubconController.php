<?php

namespace App\Http\Controllers;

use App\Models\BomItem;
use App\Models\ContractNumber;
use App\Models\ContractNumberItem;
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
                $remainingQty = max(0, $targetQty - $sentQty);
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
                    'remaining_qty' => max(0, (float)$item->target_qty - (float)$item->sent_qty),
                ]),
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
            'items.*.qty_sent' => 'required|numeric|min:0',
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
                    if ((float)$item['qty_sent'] <= 0) {
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
                        
                        if ((float)$item['qty_sent'] > $matchedItem->remaining_qty) {
                            throw new \RuntimeException("Qty dikirim (" . $item['qty_sent'] . ") untuk RM " . ($rmPart->part_no ?? '-') . " melebihi sisa kontrak (" . $matchedItem->remaining_qty . ").");
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
                        'qty_sent' => $item['qty_sent'],
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
                        (float) $item['qty_sent'],
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

    public function receive(Request $request, SubconOrder $subconOrder)
    {
        if (in_array($subconOrder->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'Cannot receive on a completed/cancelled order.');
        }

        $validated = $request->validate([
            'qty_good' => 'required|numeric|min:0',
            'qty_rejected' => 'nullable|numeric|min:0',
            'weight_kgm' => 'nullable|numeric|min:0',
            'weight_rejected_kgm' => 'nullable|numeric|min:0',
            'received_date' => 'required|date',
            'receive_location_code' => ['nullable', 'string', 'max:50'],
            'reject_location_code' => ['nullable', 'string', 'max:50'],
            'sj_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'invoice_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['qty_good'] = (float) ($validated['qty_good'] ?? 0);
        $validated['qty_rejected'] = (float) ($validated['qty_rejected'] ?? 0);

        if ($validated['qty_good'] <= 0 && $validated['qty_rejected'] <= 0) {
            return back()->withInput()->with('error', 'Isi minimal Qty Good atau Qty Rejected lebih dari nol.');
        }

        // Bypass Location validation (biarkan ambil dari mana aja)
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
                    $subconOrder->order_no,
                    [],
                    null,
                    (float) ($validated['weight_kgm'] ?? 0)
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
                    $subconOrder->order_no,
                    [],
                    null,
                    (float) ($validated['weight_rejected_kgm'] ?? 0)
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
        $subconOrder->loadMissing(['vendor', 'rmPart', 'gciPart', 'creator', 'bomItem.consumptionUom', 'bomItem.wipUom']);

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
        
        // Use stored manual weight if available, otherwise fallback to theoretical calculation
        $weightKgm = (float)($subconOrder->weight_kgm ?? 0) > 0 
            ? (float)$subconOrder->weight_kgm 
            : round($qty * (float) ($subconOrder->rmPart->net_weight ?? 0), 4);
        
        $weightKgm = round($weightKgm, 4);

        $lines = collect([[
            'no' => 1,
            'part_no' => (string) ($subconOrder->rmPart->part_no ?? '-'),
            'part_name' => (string) ($subconOrder->rmPart->part_name ?? '-'),
            'description' => 'RM part sent to subcon vendor. Return as WIP: '
                . (string) ($subconOrder->gciPart->part_no ?? '-')
                . ' - '
                . (string) ($subconOrder->gciPart->part_name ?? '-'),
            'uom' => $this->resolveSubconUom($subconOrder->bomItem, $subconOrder->rmPart, $subconOrder->gciPart),
            'qty' => $qty,
            'weight_kgm' => $weightKgm,
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
            'totalWeight' => round((float) $lines->sum('weight_kgm'), 4),
            'totalAmount' => round((float) $lines->sum('amount'), 2),
        ];
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
