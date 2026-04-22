<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Customer;
use App\Models\LocationInventory;
use App\Models\OspOrder;
use App\Models\PricingMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OspController extends Controller
{
    public function index(Request $request)
    {
        $query = OspOrder::with(['customer', 'gciPart', 'creator'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $orders = $query->paginate(25)->withQueryString();

        $stats = OspOrder::selectRaw("
            count(*) as total,
            sum(case when status = 'received' then 1 else 0 end) as received,
            sum(case when status = 'in_progress' then 1 else 0 end) as in_progress,
            sum(case when status = 'ready' then 1 else 0 end) as ready,
            sum(case when status = 'shipped' then 1 else 0 end) as shipped
        ")->first();

        $customers = Customer::orderBy('name')->get(['id', 'name']);

        return view('outgoing.osp.index', compact('orders', 'stats', 'customers'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get(['id', 'name']);

        // OSP parts must use a dedicated outgoing document flow, separate from normal FG outgoing.
        $ospParts = BomItem::query()
            ->when(
                Schema::hasColumn('bom_items', 'special'),
                fn ($query) => $query->where('special', 'OSP'),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->where('make_or_buy', 'free_issue')
            ->whereNotNull('wip_part_id')
            ->with(['wipPart', 'bom.part'])
            ->get()
            ->map(fn($item) => [
                'gci_part_id' => $item->bom->part_id ?? null,
                'part_no' => $item->bom->part->part_no ?? '',
                'part_name' => $item->bom->part->part_name ?? '',
                'bom_item_id' => $item->id,
            ])
            ->filter(fn($item) => $item['gci_part_id'] !== null)
            ->unique('gci_part_id')
            ->values();

        return view('outgoing.osp.create', compact('customers', 'ospParts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'gci_part_id' => 'required|exists:gci_parts,id',
            'bom_item_id' => 'nullable|exists:bom_items,id',
            'qty_received_material' => 'required|numeric|min:0.0001',
            'received_date' => 'required|date',
            'target_ship_date' => 'nullable|date|after_or_equal:received_date',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $today = now()->format('Ymd');
                $lastOrder = OspOrder::where('order_no', 'like', "OSP-{$today}-%")
                    ->lockForUpdate()
                    ->orderByDesc('order_no')
                    ->first();
                $seq = $lastOrder
                    ? ((int) substr($lastOrder->order_no, -3)) + 1
                    : 1;
                $validated['order_no'] = sprintf('OSP-%s-%03d', $today, $seq);
                $validated['status'] = 'received';
                $validated['created_by'] = Auth::id();

                OspOrder::create($validated);

                return redirect()->route('outgoing.osp.index')
                    ->with('success', "OSP Order {$validated['order_no']} created.");
            });
        } catch (\Throwable $e) {
            \Log::error('OSP Order create failed', [
                'error' => $e->getMessage(),
                'input' => $request->except('_token'),
            ]);
            return back()->withInput()->with('error', 'Gagal membuat order: ' . $e->getMessage());
        }
    }

    public function show(OspOrder $ospOrder)
    {
        $ospOrder->load(['customer', 'gciPart', 'bomItem', 'creator']);

        return view('outgoing.osp.show', compact('ospOrder'));
    }

    public function printDeliveryNote(OspOrder $ospOrder)
    {
        $payload = $this->buildPrintPayload($ospOrder);

        return view('outgoing.osp.print_dn', $payload);
    }

    public function printPackingList(OspOrder $ospOrder)
    {
        $payload = $this->buildPrintPayload($ospOrder);

        return view('outgoing.osp.print_packing_list', $payload);
    }

    public function printInvoice(OspOrder $ospOrder)
    {
        $payload = $this->buildPrintPayload($ospOrder);

        return view('outgoing.osp.print_invoice', $payload);
    }

    public function updateProgress(Request $request, OspOrder $ospOrder)
    {
        if (in_array($ospOrder->status, ['shipped', 'cancelled'])) {
            return back()->with('error', 'Cannot update a shipped/cancelled order.');
        }

        $validated = $request->validate([
            'qty_assembled' => 'required|numeric|min:0',
        ]);

        $ospOrder->update([
            'qty_assembled' => $validated['qty_assembled'],
            'status' => $validated['qty_assembled'] >= $ospOrder->qty_received_material ? 'ready' : 'in_progress',
        ]);

        return back()->with('success', 'Progress updated.');
    }

    public function ship(Request $request, OspOrder $ospOrder)
    {
        if ($ospOrder->status === 'shipped') {
            return back()->with('error', 'Already shipped.');
        }

        $validated = $request->validate([
            'qty_shipped' => 'required|numeric|min:0.0001',
            'shipped_date' => 'required|date',
        ]);

        $qtyShipped = (float) $validated['qty_shipped'];
        $qtyAvailableForOsp = (float) $ospOrder->qty_received_material;
        if ($qtyShipped > $qtyAvailableForOsp) {
            return back()->with('error', 'Qty outgoing OSP melebihi qty dokumen OSP.');
        }

        $ospOrder->loadMissing([
            'bomItem.incomingPart',
            'bomItem.componentPart',
            'bomItem.substitutes.incomingPart',
            'gciPart',
        ]);

        $bomItem = $ospOrder->bomItem;
        if (!$bomItem) {
            return back()->with('error', 'BOM item OSP belum terhubung. Dokumen OSP tidak bisa memotong RM.');
        }

        $makeOrBuy = strtolower(trim((string) ($bomItem->make_or_buy ?? '')));
        if ($makeOrBuy !== 'free_issue') {
            return back()->with('error', 'BOM item OSP ini bukan FREE ISSUE. OSP outgoing hanya boleh memotong RM OSP/free issue.');
        }

        $requiredQty = round((float) ($bomItem->net_required ?? $bomItem->usage_qty ?? 0) * $qtyShipped, 4);
        if ($requiredQty <= 0) {
            return back()->with('error', 'Qty kebutuhan RM OSP dari BOM tidak valid.');
        }

        [$allocations, $shortageQty] = $this->buildOspMaterialAllocations($bomItem, $requiredQty);
        if ($shortageQty > 0) {
            return back()->with('error', 'Stok RM OSP/free issue tidak cukup. Shortage: ' . number_format($shortageQty, 4));
        }

        DB::transaction(function () use ($ospOrder, $qtyShipped, $validated, $allocations) {
            foreach ($allocations as $allocation) {
                LocationInventory::consumeStock(
                    (int) $allocation['part_id'],
                    (string) $allocation['location_code'],
                    (float) $allocation['request_qty'],
                    $allocation['batch_no'] !== '' ? (string) $allocation['batch_no'] : null,
                    null,
                    'OSP_OUTGOING',
                    $ospOrder->order_no
                );
            }

            $ospOrder->update([
                'qty_shipped' => $qtyShipped,
                'shipped_date' => $validated['shipped_date'],
                'status' => 'shipped',
            ]);
        });

        return back()->with('success', 'Order marked as shipped.');
    }

    private function buildOspMaterialAllocations(BomItem $bomItem, float $requiredQty): array
    {
        $candidateParts = collect();

        if ($bomItem->incomingPart) {
            $candidateParts->push([
                'type' => 'primary',
                'part_id' => (int) $bomItem->incomingPart->id,
                'part_no' => (string) ($bomItem->incomingPart->part_no ?? '-'),
                'part_name' => (string) ($bomItem->incomingPart->part_name ?? '-'),
            ]);
        }

        foreach (($bomItem->substitutes ?? collect()) as $substitute) {
            if (!$substitute->incomingPart) {
                continue;
            }

            $candidateParts->push([
                'type' => 'substitute',
                'part_id' => (int) $substitute->incomingPart->id,
                'part_no' => (string) ($substitute->incomingPart->part_no ?? $substitute->substitute_part_no ?? '-'),
                'part_name' => (string) ($substitute->incomingPart->part_name ?? $substitute->part?->part_name ?? '-'),
            ]);
        }

        $candidateParts = $candidateParts
            ->filter(fn ($part) => !empty($part['part_id']))
            ->unique('part_id')
            ->values();

        if ($candidateParts->isEmpty()) {
            return [[], $requiredQty];
        }

        $remaining = round($requiredQty, 4);
        $allocations = [];

        foreach ($candidateParts as $candidate) {
            if ($remaining <= 0) {
                break;
            }

            $stocks = LocationInventory::query()
                ->where('part_id', $candidate['part_id'])
                ->where('qty_on_hand', '>', 0)
                ->orderByRaw('production_date IS NULL')
                ->orderBy('production_date')
                ->orderBy('batch_no')
                ->orderBy('location_code')
                ->get();

            foreach ($stocks as $stock) {
                if ($remaining <= 0) {
                    break;
                }

                $available = (float) $stock->qty_on_hand;
                if ($available <= 0) {
                    continue;
                }

                $pickedQty = min($available, $remaining);
                $remaining = round($remaining - $pickedQty, 4);

                $allocations[] = [
                    'source_type' => $candidate['type'],
                    'part_id' => (int) $candidate['part_id'],
                    'part_no' => (string) $candidate['part_no'],
                    'part_name' => (string) $candidate['part_name'],
                    'location_code' => (string) $stock->location_code,
                    'batch_no' => (string) ($stock->batch_no ?? ''),
                    'qty_on_hand' => $available,
                    'request_qty' => $pickedQty,
                ];
            }
        }

        return [$allocations, max(0, round($remaining, 4))];
    }

    private function buildPrintPayload(OspOrder $ospOrder): array
    {
        $ospOrder->loadMissing([
            'customer',
            'gciPart',
            'creator',
            'bomItem.bom.items.incomingPart',
            'bomItem.bom.items.componentPart',
            'bomItem.bom.items.substitutes.incomingPart',
        ]);

        $printQty = (float) ($ospOrder->qty_shipped > 0 ? $ospOrder->qty_shipped : $ospOrder->qty_received_material);
        $bom = $ospOrder->bomItem?->bom ?: Bom::activeVersion($ospOrder->gci_part_id, $ospOrder->received_date);

        $bomItems = collect($bom?->items ?? [])
            ->filter(fn (BomItem $item) => $this->isOspMaterialItem($item))
            ->values();

        if ($bomItems->isEmpty() && $ospOrder->bomItem && $this->isOspMaterialItem($ospOrder->bomItem)) {
            $ospOrder->loadMissing([
                'bomItem.incomingPart',
                'bomItem.componentPart',
                'bomItem.substitutes.incomingPart',
            ]);
            $bomItems = collect([$ospOrder->bomItem]);
        }

        $resolvedCurrency = null;

        $lines = $bomItems->map(function (BomItem $item, int $index) use ($printQty, $ospOrder, &$resolvedCurrency) {
            $incomingPart = $item->incomingPart;
            $fallbackPart = $item->componentPart;
            $partNo = (string) ($incomingPart?->part_no ?? $fallbackPart?->part_no ?? $item->component_part_no ?? '-');
            $partName = (string) ($incomingPart?->part_name ?? $fallbackPart?->part_name ?? $item->material_name ?? '-');
            $uom = (string) ($item->consumption_uom ?? $incomingPart?->uom ?? $fallbackPart?->uom ?? 'PCS');
            $pricing = PricingMaster::resolveCurrentPrice(
                (int) ($incomingPart?->gci_part_id ?? $fallbackPart?->id ?? 0),
                'osp_price',
                ['customer_id' => $ospOrder->customer_id],
                $ospOrder->shipped_date ?? $ospOrder->received_date
            ) ?? PricingMaster::resolveCurrentPrice(
                (int) ($incomingPart?->gci_part_id ?? $fallbackPart?->id ?? 0),
                'purchase_price',
                ['customer_id' => $ospOrder->customer_id],
                $ospOrder->shipped_date ?? $ospOrder->received_date
            );

            $unitPrice = round((float) ($pricing?->price ?? $incomingPart?->price ?? 0), 3);
            $resolvedCurrency = $resolvedCurrency ?: ($pricing?->currency ?? 'IDR');
            $requiredQty = round((float) ($item->net_required ?? $item->usage_qty ?? 0) * $printQty, 4);

            return [
                'no' => $index + 1,
                'part_no' => $partNo,
                'part_name' => $partName,
                'description' => trim(implode(' | ', array_filter([
                    $partName,
                    $item->material_spec,
                    $item->material_size,
                ]))),
                'uom' => $uom,
                'material_type' => strtoupper(trim((string) ($item->make_or_buy ?? '')) ?: 'N/A'),
                'special' => strtoupper(trim((string) ($item->special ?? '')) ?: '-'),
                'qty' => $requiredQty,
                'unit_price' => $unitPrice,
                'amount' => round($requiredQty * $unitPrice, 2),
            ];
        })->values();

        return [
            'ospOrder' => $ospOrder,
            'lines' => $lines,
            'printQty' => $printQty,
            'deliveryNoteNo' => 'DN-OSP-' . $ospOrder->order_no,
            'packingListNo' => 'PL-OSP-' . $ospOrder->order_no,
            'invoiceNo' => 'INV-OSP-' . $ospOrder->order_no,
            'currency' => $resolvedCurrency ?: 'IDR',
            'totalQty' => round((float) $lines->sum('qty'), 4),
            'totalAmount' => round((float) $lines->sum('amount'), 2),
        ];
    }

    private function isOspMaterialItem(BomItem $item): bool
    {
        $makeOrBuy = strtolower(trim((string) ($item->make_or_buy ?? '')));
        $special = strtoupper(trim((string) ($item->special ?? '')));

        return $makeOrBuy === 'free_issue' || $special === 'OSP';
    }
}
