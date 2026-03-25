<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\DnItem;
use App\Models\GciPart;
use App\Models\GciInventory;
use App\Models\LocationInventory;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeliveryOrderController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $customerId = $request->query('customer_id');

        $orders = DeliveryOrder::with(['customer', 'items'])
            ->when($q, function ($query) use ($q) {
                $query->where('do_no', 'like', "%{$q}%");
            })
            ->when($customerId, function ($query) use ($customerId) {
                $query->where('customer_id', $customerId);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $customers = Customer::orderBy('name')->get();

        return view('outgoing.delivery_orders.index', compact('orders', 'customers', 'q', 'customerId'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $parts = GciPart::where('classification', 'FG')->orderBy('part_no')->get();

        return view('outgoing.delivery_orders.create', compact('customers', 'parts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'do_no' => 'required|string|max:100|unique:delivery_orders,do_no',
            'customer_id' => 'required|exists:customers,id',
            'do_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.part_id' => 'required|exists:gci_parts,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
        ]);

        try {
            DB::beginTransaction();

            $do = DeliveryOrder::create([
                'do_no' => $validated['do_no'],
                'customer_id' => $validated['customer_id'],
                'do_date' => $validated['do_date'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                DeliveryOrderItem::create([
                    'delivery_order_id' => $do->id,
                    'gci_part_id' => $item['part_id'],
                    'qty_ordered' => $item['qty'],
                    'qty_shipped' => 0,
                ]);

                $this->commitFgOrderQty((int) $item['part_id'], (float) $item['qty']);
            }

            DB::commit();
            return redirect()->route('outgoing.delivery-orders.index')->with('success', 'Delivery Order created.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function show(DeliveryOrder $deliveryOrder)
    {
        $deliveryOrder->loadMissing(['customer', 'items.part.standardPacking', 'plan', 'stop', 'deliveryNotes']);

        return view('outgoing.delivery_orders.show', compact('deliveryOrder'));
    }

    public function edit(DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'draft') {
            return back()->with('error', 'Only draft DO can be edited.');
        }

        $deliveryOrder->load('items');
        $customers = Customer::orderBy('name')->get();
        $parts = GciPart::where('classification', 'FG')->orderBy('part_no')->get();

        return view('outgoing.delivery_orders.edit', compact('deliveryOrder', 'customers', 'parts'));
    }

    public function update(Request $request, DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'draft') {
            return back()->with('error', 'Only draft DO can be updated.');
        }

        $validated = $request->validate([
            'do_no' => 'required|string|max:100|unique:delivery_orders,do_no,' . $deliveryOrder->id,
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
                'do_date' => $validated['do_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $deliveryOrder->loadMissing('items');
            foreach ($deliveryOrder->items as $existingItem) {
                $remainingReserved = max(0, (float) $existingItem->qty_ordered - (float) ($existingItem->qty_shipped ?? 0));
                $this->releaseFgOrderQty((int) $existingItem->gci_part_id, $remainingReserved);
            }

            $deliveryOrder->items()->delete();

            foreach ($validated['items'] as $item) {
                DeliveryOrderItem::create([
                    'delivery_order_id' => $deliveryOrder->id,
                    'gci_part_id' => $item['part_id'],
                    'qty_ordered' => $item['qty'],
                    'qty_shipped' => 0,
                ]);

                $this->commitFgOrderQty((int) $item['part_id'], (float) $item['qty']);
            }

            DB::commit();
            return redirect()->route('outgoing.delivery-orders.index')->with('success', 'Delivery Order updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy(DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status !== 'draft') {
            return back()->with('error', 'Only draft DO can be deleted.');
        }

        DB::transaction(function () use ($deliveryOrder) {
            $deliveryOrder->loadMissing('items');

            foreach ($deliveryOrder->items as $item) {
                $remainingReserved = max(0, (float) $item->qty_ordered - (float) ($item->qty_shipped ?? 0));
                $this->releaseFgOrderQty((int) $item->gci_part_id, $remainingReserved);
            }

            $deliveryOrder->delete();
        });

        return redirect()->route('outgoing.delivery-orders.index')->with('success', 'Delivery Order deleted.');
    }

    public function ship(Request $request, DeliveryOrder $deliveryOrder)
    {
        if ($deliveryOrder->status === 'shipped') {
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

        // Hard validation: block if default_location missing or stock insufficient
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
            $available = LocationInventory::getStockByLocation(0, strtoupper(trim($defaultLoc)), null, (int) $item->gci_part_id);
            if ($available + 1e-9 < $qtyToShip) {
                $stockErrors[] = ($gciPart->part_no ?? "ID:{$item->gci_part_id}") . " di {$defaultLoc} — need {$qtyToShip}, available {$available}";
            }
        }
        if (!empty($stockErrors)) {
            return back()->with('error', 'Stok tidak cukup untuk shipment: ' . implode('; ', $stockErrors));
        }

        $dn = null;

        DB::transaction(function () use ($deliveryOrder, $qtyByItemId, $request, &$dn) {
            $do = DeliveryOrder::query()->whereKey($deliveryOrder->id)->lockForUpdate()->firstOrFail();
            $do->loadMissing(['items']);

            $itemsById = $do->items->keyBy('id');

            foreach ($qtyByItemId as $itemId => $qtyToShip) {
                $item = $itemsById->get((int) $itemId);
                if (!$item) {
                    throw new \RuntimeException("Invalid DO item: {$itemId}");
                }

                $ordered = (float) $item->qty_ordered;
                $shipped = (float) ($item->qty_shipped ?? 0);
                $remaining = max(0, $ordered - $shipped);
                if ($qtyToShip > $remaining + 1e-9) {
                    throw new \RuntimeException("Ship qty exceeds remaining for item {$itemId}. Remaining {$remaining}.");
                }
            }

            $dnNo = null;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $candidate = 'DN-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
                if (!DeliveryNote::query()->where('dn_no', $candidate)->exists()) {
                    $dnNo = $candidate;
                    break;
                }
            }
            $dnNo ??= 'DN-' . now()->format('YmdHis') . '-' . (string) Str::uuid();

            $dn = DeliveryNote::create([
                'delivery_order_id' => $do->id,
                'dn_no' => $dnNo,
                'customer_id' => $do->customer_id,
                'delivery_date' => $do->do_date->toDateString(),
                'status' => 'shipped',
                'delivery_plan_id' => $do->delivery_plan_id,
                'delivery_stop_id' => $do->delivery_stop_id,
                'notes' => 'Shipped from DO ' . $do->do_no,
            ]);

            foreach ($qtyByItemId as $itemId => $qtyToShip) {
                $item = $itemsById->get((int) $itemId);
                if (!$item) {
                    continue;
                }

                DnItem::create([
                    'dn_id' => $dn->id,
                    'gci_part_id' => (int) $item->gci_part_id,
                    'qty' => $qtyToShip,
                ]);

                $item->update(['qty_shipped' => (float) $item->qty_shipped + $qtyToShip]);
                $this->consumeFgOrderQty((int) $item->gci_part_id, (float) $qtyToShip);

                // Deduct dari LocationInventory (source of truth) → auto-sync ke gci_inventories
                $gciPart = GciPart::find((int) $item->gci_part_id);
                $defaultLoc = $gciPart?->default_location;
                if ($defaultLoc && $qtyToShip > 0) {
                    LocationInventory::consumeStock(
                        null,
                        strtoupper(trim($defaultLoc)),
                        (float) $qtyToShip,
                        null,
                        (int) $item->gci_part_id,
                        'DELIVERY',
                        'DN#' . ($dn->dn_no ?? 'N/A')
                    );
                }
            }

            $do->refresh();
            $totalRemaining = $do->items->sum(function ($i) {
                $ordered = (float) $i->qty_ordered;
                $shipped = (float) ($i->qty_shipped ?? 0);
                return max(0, $ordered - $shipped);
            });

            $do->update([
                'status' => $totalRemaining > 0 ? 'partial_shipped' : 'shipped',
            ]);
        });

        return redirect()
            ->route('outgoing.delivery-orders.show', $deliveryOrder)
            ->with('success', 'DN created: ' . ($dn?->dn_no ?? ''));
    }

    private function commitFgOrderQty(int $gciPartId, float $qty): void
    {
        if ($gciPartId <= 0 || $qty <= 0) {
            return;
        }

        $inventory = GciInventory::firstOrCreate(
            ['gci_part_id' => $gciPartId],
            ['on_hand' => 0, 'on_order' => 0, 'as_of_date' => now()->toDateString()]
        );
        $inventory->commitOrder($qty);
    }

    private function releaseFgOrderQty(int $gciPartId, float $qty): void
    {
        if ($gciPartId <= 0 || $qty <= 0) {
            return;
        }

        $inventory = GciInventory::where('gci_part_id', $gciPartId)->first();
        if ($inventory) {
            $inventory->releaseOrder($qty);
        }
    }

    private function consumeFgOrderQty(int $gciPartId, float $qty): void
    {
        if ($gciPartId <= 0 || $qty <= 0) {
            return;
        }

        $inventory = GciInventory::firstOrCreate(
            ['gci_part_id' => $gciPartId],
            ['on_hand' => 0, 'on_order' => 0, 'as_of_date' => now()->toDateString()]
        );
        $inventory->consume($qty);
    }
}
