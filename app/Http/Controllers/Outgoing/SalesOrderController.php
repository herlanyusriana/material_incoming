<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\DnItem;
use App\Models\FgInventory;
use App\Models\GciPart;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $customerId = $request->query('customer_id');

        $orders = SalesOrder::with(['customer', 'items'])
            ->when($q, function ($query) use ($q) {
                $query->where('so_no', 'like', "%{$q}%");
            })
            ->when($customerId, function ($query) use ($customerId) {
                $query->where('customer_id', $customerId);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $customers = Customer::orderBy('name')->get();

        return view('outgoing.sales_orders.index', compact('orders', 'customers', 'q', 'customerId'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $parts = GciPart::where('classification', 'FG')->orderBy('part_no')->get();

        return view('outgoing.sales_orders.create', compact('customers', 'parts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'so_no' => 'required|string|max:100|unique:sales_orders,so_no',
            'customer_id' => 'required|exists:customers,id',
            'so_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.part_id' => 'required|exists:gci_parts,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
        ]);

        try {
            DB::beginTransaction();

            $so = SalesOrder::create([
                'so_no' => $validated['so_no'],
                'customer_id' => $validated['customer_id'],
                'so_date' => $validated['so_date'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'gci_part_id' => $item['part_id'],
                    'qty_ordered' => $item['qty'],
                    'qty_shipped' => 0,
                ]);
            }

            DB::commit();
            return redirect()->route('outgoing.sales-orders.index')->with('success', 'Sales Order created.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->loadMissing(['customer', 'items.part.standardPacking', 'plan', 'stop', 'deliveryNotes']);

        return view('outgoing.sales_orders.show', compact('salesOrder'));
    }

    public function edit(SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft') {
            return back()->with('error', 'Only draft SO can be edited.');
        }

        $salesOrder->load('items');
        $customers = Customer::orderBy('name')->get();
        $parts = GciPart::where('classification', 'FG')->orderBy('part_no')->get();

        return view('outgoing.sales_orders.edit', compact('salesOrder', 'customers', 'parts'));
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft') {
            return back()->with('error', 'Only draft SO can be updated.');
        }

        $validated = $request->validate([
            'so_no' => 'required|string|max:100|unique:sales_orders,so_no,' . $salesOrder->id,
            'customer_id' => 'required|exists:customers,id',
            'so_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.part_id' => 'required|exists:gci_parts,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
        ]);

        try {
            DB::beginTransaction();

            $salesOrder->update([
                'so_no' => $validated['so_no'],
                'customer_id' => $validated['customer_id'],
                'so_date' => $validated['so_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $salesOrder->items()->delete();

            foreach ($validated['items'] as $item) {
                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'gci_part_id' => $item['part_id'],
                    'qty_ordered' => $item['qty'],
                    'qty_shipped' => 0,
                ]);
            }

            DB::commit();
            return redirect()->route('outgoing.sales-orders.index')->with('success', 'Sales Order updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy(SalesOrder $salesOrder)
    {
        if ($salesOrder->status !== 'draft') {
            return back()->with('error', 'Only draft SO can be deleted.');
        }

        $salesOrder->delete();
        return redirect()->route('outgoing.sales-orders.index')->with('success', 'Sales Order deleted.');
    }

    public function ship(Request $request, SalesOrder $salesOrder)
    {
        if ($salesOrder->status === 'shipped') {
            return back()->with('error', 'SO already fully shipped.');
        }

        $salesOrder->loadMissing(['customer', 'items']);

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

        $dn = null;

        DB::transaction(function () use ($salesOrder, $qtyByItemId, $request, &$dn) {
            /** @var SalesOrder $so */
            $so = SalesOrder::query()->whereKey($salesOrder->id)->lockForUpdate()->firstOrFail();
            $so->loadMissing(['items']);

            $itemsById = $so->items->keyBy('id');

            // Validate remaining qty for each selected item
            foreach ($qtyByItemId as $itemId => $qtyToShip) {
                /** @var SalesOrderItem|null $item */
                $item = $itemsById->get((int) $itemId);
                if (!$item) {
                    throw new \RuntimeException("Invalid SO item: {$itemId}");
                }

                $ordered = (float) $item->qty_ordered;
                $shipped = (float) ($item->qty_shipped ?? 0);
                $remaining = max(0, $ordered - $shipped);
                if ($qtyToShip > $remaining + 1e-9) {
                    throw new \RuntimeException("Ship qty exceeds remaining for item {$itemId}. Remaining {$remaining}.");
                }
            }

            // Create DN (generated at ship)
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
                'sales_order_id' => $so->id,
                'dn_no' => $dnNo,
                'customer_id' => $so->customer_id,
                'delivery_date' => $so->so_date->toDateString(),
                'status' => 'shipped',
                'delivery_plan_id' => $so->delivery_plan_id,
                'delivery_stop_id' => $so->delivery_stop_id,
                'notes' => 'Shipped from SO ' . $so->so_no,
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

                $inventory = FgInventory::firstOrCreate(
                    ['gci_part_id' => (int) $item->gci_part_id],
                    ['qty_on_hand' => 0]
                );
                $inventory->decrement('qty_on_hand', $qtyToShip);
            }

            $so->refresh();
            $totalRemaining = $so->items->sum(function ($i) {
                $ordered = (float) $i->qty_ordered;
                $shipped = (float) ($i->qty_shipped ?? 0);
                return max(0, $ordered - $shipped);
            });

            $so->update([
                'status' => $totalRemaining > 0 ? 'partial_shipped' : 'shipped',
            ]);
        });

        return redirect()
            ->route('outgoing.sales-orders.show', $salesOrder)
            ->with('success', 'DN created: ' . ($dn?->dn_no ?? ''));
    }
}

