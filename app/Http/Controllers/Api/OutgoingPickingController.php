<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;
use App\Models\DnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OutgoingPickingController extends Controller
{
    public function show(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load(['customer', 'items.part']);

        return response()->json([
            'delivery_note' => [
                'id' => $deliveryNote->id,
                'dn_no' => $deliveryNote->dn_no,
                'status' => $deliveryNote->status,
                'delivery_date' => $deliveryNote->delivery_date?->format('Y-m-d'),
                'customer' => [
                    'id' => $deliveryNote->customer?->id,
                    'code' => $deliveryNote->customer?->code,
                    'name' => $deliveryNote->customer?->name,
                ],
            ],
            'items' => $deliveryNote->items->map(function ($item) {
                $required = (float) $item->qty;
                $picked = (float) ($item->picked_qty ?? 0);
                return [
                    'id' => $item->id,
                    'part_no' => $item->part?->part_no,
                    'part_name' => $item->part?->part_name,
                    'kitting_location_code' => $item->kitting_location_code,
                    'required_qty' => $required,
                    'picked_qty' => $picked,
                    'remaining_qty' => max(0, $required - $picked),
                ];
            })->values(),
        ]);
    }

    public function startPicking(Request $request, DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->status !== 'ready_to_pick') {
            return response()->json([
                'ok' => false,
                'message' => 'Delivery Note must be ready_to_pick to start picking.',
            ], 422);
        }

        $deliveryNote->update(['status' => 'picking']);

        return response()->json(['ok' => true, 'status' => $deliveryNote->status]);
    }

    public function pick(Request $request, DeliveryNote $deliveryNote)
    {
        if ($deliveryNote->status !== 'picking') {
            return response()->json([
                'ok' => false,
                'message' => 'Picking scan only available when status is picking.',
            ], 422);
        }

        $validated = $request->validate([
            'location_code' => ['required', 'string', 'max:50'],
            'part_no' => ['required', 'string', 'max:100'],
            'qty' => ['nullable', 'numeric', 'min:0.0001'],
        ]);

        $locationCode = strtoupper(trim((string) $validated['location_code']));
        $partNo = strtoupper(trim((string) $validated['part_no']));
        $qty = (float) ($validated['qty'] ?? 1);

        $deliveryNote->loadMissing(['items.part']);

        $item = $deliveryNote->items
            ->first(function ($i) use ($partNo, $locationCode) {
                $pno = strtoupper(trim((string) ($i->part?->part_no ?? '')));
                $loc = strtoupper(trim((string) ($i->kitting_location_code ?? '')));

                $remaining = (float) $i->qty - (float) ($i->picked_qty ?? 0);
                return $pno === $partNo && $loc === $locationCode && $remaining > 0;
            });

        if (!$item) {
            return response()->json([
                'ok' => false,
                'message' => "No matching DN item remaining for part {$partNo} at location {$locationCode}.",
            ], 422);
        }

        $remaining = (float) $item->qty - (float) ($item->picked_qty ?? 0);
        if ($qty > $remaining) {
            return response()->json([
                'ok' => false,
                'message' => "Pick qty too large. Remaining {$remaining} for {$partNo}.",
            ], 422);
        }

        $pickedQtyAfter = 0.0;

        DB::transaction(function () use ($item, $qty, $request, &$pickedQtyAfter) {
            $locked = DnItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $current = (float) ($locked->picked_qty ?? 0);
            $new = $current + $qty;

            if ($new > (float) $locked->qty) {
                throw new \RuntimeException('Pick qty exceeds required qty.');
            }

            $payload = [
                'picked_qty' => $new,
            ];

            if ($new >= (float) $locked->qty) {
                $payload['picked_at'] = now();
                $payload['picked_by'] = (int) ($request->user()?->id ?? 0) ?: null;
            }

            $locked->update($payload);
            $pickedQtyAfter = $new;
        });

        return response()->json([
            'ok' => true,
            'item_id' => $item->id,
            'part_no' => $partNo,
            'location_code' => $locationCode,
            'picked_qty' => $pickedQtyAfter,
            'required_qty' => (float) $item->qty,
            'remaining_qty' => max(0, (float) $item->qty - $pickedQtyAfter),
        ]);
    }
}

