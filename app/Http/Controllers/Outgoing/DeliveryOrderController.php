<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Models\NewSchema\Core\Customer;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Outgoing\OutgoingDeliveryNote;
use App\Models\NewSchema\Outgoing\OutgoingDeliveryNoteItem;
use App\Models\NewSchema\Outgoing\OutgoingDeliveryOrder;
use App\Models\NewSchema\Outgoing\OutgoingDeliveryOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeliveryOrderController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $customerId = $request->query('customer_id');

        $orders = OutgoingDeliveryOrder::with(['customer', 'items'])
            ->when($q, function ($query) use ($q) {
                $query->where('do_no', 'like', "%{$q}%");
            })
            ->when($customerId, function ($query) use ($customerId) {
                $query->where('customer_id', $customerId);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $customers = Customer::orderBy('customer_name')->get();

        return view('outgoing.delivery_orders.index', compact('orders', 'customers', 'q', 'customerId'));
    }

    public function create()
    {
        $customers = Customer::orderBy('customer_name')->get();
        $parts = GciPart::where('classification', 'FG')->orderBy('part_no')->get();

        return view('outgoing.delivery_orders.create', compact('customers', 'parts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'do_no' => 'required|string|max:100|unique:outgoing_delivery_orders,do_no',
            'customer_id' => 'required|exists:customers,id',
            'do_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.part_id' => 'required|exists:gci_parts,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
        ]);

        try {
            DB::beginTransaction();

            $do = OutgoingDeliveryOrder::create([
                'do_no' => $validated['do_no'],
                'customer_id' => $validated['customer_id'],
                'order_date' => $validated['do_date'],
                'delivery_date' => $validated['do_date'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                OutgoingDeliveryOrderItem::create([
                    'delivery_order_id' => $do->id,
                    'gci_part_id' => $item['part_id'],
                    'qty_ordered' => $item['qty'],
                    'qty_delivered' => 0,
                ]);
            }

            DB::commit();
            return redirect()->route('outgoing.delivery-orders.index')->with('success', 'Delivery Order created.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function show(OutgoingDeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->loadMissing(['customer', 'items.gciPart', 'deliveryNotes.items']);

        return view('outgoing.delivery_orders.show', compact('deliveryOrder'));
    }

    public function edit(OutgoingDeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'draft') {
            return back()->with('error', 'Only draft DO can be edited.');
        }

        $deliveryOrder->load('items');
        $customers = Customer::orderBy('customer_name')->get();
        $parts = GciPart::where('classification', 'FG')->orderBy('part_no')->get();

        return view('outgoing.delivery_orders.edit', compact('deliveryOrder', 'customers', 'parts'));
    }

    public function update(Request $request, OutgoingDeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'draft') {
            return back()->with('error', 'Only draft DO can be updated.');
        }

        $validated = $request->validate([
            'do_no' => 'required|string|max:100|unique:outgoing_delivery_orders,do_no,' . $deliveryOrder->id,
            'customer_id' => 'required|exists:customers,id',
            'do_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.part_id' => 'required|exists:gci_parts,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
        ]);

        try {
            DB::beginTransaction();

            $deliveryOrder->update([
                'do_no' => $validated['do_no'],
                'customer_id' => $validated['customer_id'],
                'order_date' => $validated['do_date'],
                'delivery_date' => $validated['do_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $deliveryOrder->items()->delete();

            foreach ($validated['items'] as $item) {
                OutgoingDeliveryOrderItem::create([
                    'delivery_order_id' => $deliveryOrder->id,
                    'gci_part_id' => $item['part_id'],
                    'qty_ordered' => $item['qty'],
                    'qty_delivered' => 0,
                ]);
            }

            DB::commit();
            return redirect()->route('outgoing.delivery-orders.index')->with('success', 'Delivery Order updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy(OutgoingDeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'draft') {
            return back()->with('error', 'Only draft DO can be deleted.');
        }

        DB::transaction(function () use ($deliveryOrder) {
            $deliveryOrder->items()->delete();
            $deliveryOrder->delete();
        });

        return redirect()->route('outgoing.delivery-orders.index')->with('success', 'Delivery Order deleted.');
    }

    public function ship(Request $request, OutgoingDeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status === 'delivered') {
            return back()->with('error', 'DO already fully shipped.');
        }

        $deliveryOrder->loadMissing(['customer', 'items']);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.qty' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $qtyByItemId = collect($validated['items'])
            ->mapWithKeys(function ($v, $k) {
                $qty = (float) ($v['qty'] ?? 0);
                return [(int) $k => $qty];
            })
            ->filter(fn($qty, $id) => $id > 0 && $qty > 0);

        if ($qtyByItemId->isEmpty()) {
            return back()->with('error', 'No quantities to ship.');
        }

        $stockErrors = [];
        $itemsById = $deliveryOrder->items->keyBy('id');
        foreach ($qtyByItemId as $itemId => $qtyToShip) {
            $item = $itemsById->get((int) $itemId);
            if (!$item) {
                continue;
            }
            $gciPart = GciPart::find((int) $item->gci_part_id);
            $defaultLoc = $gciPart?->default_location;
            if (!$defaultLoc) {
                $stockErrors[] = ($gciPart->part_no ?? "ID:{$item->gci_part_id}") . " — default_location belum diset.";
                continue;
            }
            $available = InventoryLocationStock::getStockByLocation((int) $item->gci_part_id, strtoupper(trim($defaultLoc)));
            if ($available + 1e-9 < $qtyToShip) {
                $stockErrors[] = ($gciPart->part_no ?? "ID:{$item->gci_part_id}") . " di {$defaultLoc} — need {$qtyToShip}, available {$available}";
            }
        }
        if (!empty($stockErrors)) {
            return back()->with('error', 'Stok tidak cukup untuk shipment: ' . implode('; ', $stockErrors));
        }

        $dn = null;

        DB::transaction(function () use ($deliveryOrder, $qtyByItemId, $request, &$dn) {
            $do = OutgoingDeliveryOrder::query()->whereKey($deliveryOrder->id)->lockForUpdate()->firstOrFail();
            $do->loadMissing(['items']);

            $itemsById = $do->items->keyBy('id');

            foreach ($qtyByItemId as $itemId => $qtyToShip) {
                $item = $itemsById->get((int) $itemId);
                if (!$item) {
                    throw new \RuntimeException("Invalid DO item: {$itemId}");
                }

                $ordered = (float) $item->qty_ordered;
                $delivered = (float) ($item->qty_delivered ?? 0);
                $remaining = max(0, $ordered - $delivered);
                if ($qtyToShip > $remaining + 1e-9) {
                    throw new \RuntimeException("Ship qty exceeds remaining for item {$itemId}. Remaining {$remaining}.");
                }
            }

            $dnNo = null;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $candidate = 'DN-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
                if (!OutgoingDeliveryNote::query()->where('dn_no', $candidate)->exists()) {
                    $dnNo = $candidate;
                    break;
                }
            }
            $dnNo ??= 'DN-' . now()->format('YmdHis') . '-' . (string) Str::uuid();

            $dn = OutgoingDeliveryNote::create([
                'dn_no' => $dnNo,
                'transaction_no' => $dnNo,
                'customer_id' => $do->customer_id,
                'delivery_date' => $do->delivery_date?->toDateString() ?? now()->toDateString(),
                'planned_delivery_date' => $do->delivery_date?->toDateString() ?? now()->toDateString(),
                'status' => 'loaded',
                'notes' => 'Shipped from DO ' . $do->do_no,
                'created_by' => auth()->id(),
            ]);

            foreach ($qtyByItemId as $itemId => $qtyToShip) {
                $item = $itemsById->get((int) $itemId);
                if (!$item) {
                    continue;
                }

                OutgoingDeliveryNoteItem::create([
                    'delivery_note_id' => $dn->id,
                    'gci_part_id' => (int) $item->gci_part_id,
                    'qty_delivered' => $qtyToShip,
                    'unit' => null,
                    'sales_order_item_id' => null,
                    'picking_fg_id' => null,
                    'batch_no' => null,
                    'from_location_code' => null,
                    'unit_price' => null,
                    'total_price' => null,
                    'notes' => null,
                ]);

                $item->update(['qty_delivered' => (float) $item->qty_delivered + $qtyToShip]);

                $gciPart = GciPart::find((int) $item->gci_part_id);
                $defaultLoc = $gciPart?->default_location;
                if ($defaultLoc && $qtyToShip > 0) {
                    InventoryLocationStock::consumeStock(
                        gciPartId: (int) $item->gci_part_id,
                        locationCode: strtoupper(trim($defaultLoc)),
                        qty: (float) $qtyToShip,
                        batchNo: null,
                        transactionType: 'DELIVERY',
                        sourceReference: 'DN#' . ($dn->dn_no ?? 'N/A'),
                        createdBy: auth()->id()
                    );
                }
            }

            $do->refresh();
            $totalRemaining = $do->items->sum(function ($i) {
                $ordered = (float) $i->qty_ordered;
                $delivered = (float) ($i->qty_delivered ?? 0);
                return max(0, $ordered - $delivered);
            });

            $do->update([
                'status' => $totalRemaining > 0 ? 'partial' : 'delivered',
            ]);
        });

        return redirect()
            ->route('outgoing.delivery-orders.show', $deliveryOrder)
            ->with('success', 'DN created: ' . ($dn?->dn_no ?? ''));
    }
}
