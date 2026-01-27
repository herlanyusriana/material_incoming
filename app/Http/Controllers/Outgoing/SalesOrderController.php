<?php

namespace App\Http\Controllers\Outgoing;

use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;
use App\Models\DnItem;
use App\Models\FgInventory;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesOrderController extends Controller
{
    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->loadMissing(['customer', 'items.part.standardPacking', 'plan', 'stop']);

        return view('outgoing.sales_orders.show', compact('salesOrder'));
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
            ->filter(fn ($qty, $id) => $id > 0 && $qty > 0);

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

