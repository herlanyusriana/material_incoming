<?php

namespace App\Services;

use App\Models\NewSchema\Outgoing\OutgoingPo;
use App\Models\NewSchema\Outgoing\OutgoingDeliveryNote;
use App\Models\NewSchema\Outgoing\OutgoingDeliveryNoteItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OutgoingPoDeliveryService
{
    /**
     * Convert confirmed PO to DeliveryNote (Departure)
     * Triggered when PO status changes to 'confirmed'
     */
    public static function createDeliveryNoteFromPo(OutgoingPo $po): ?OutgoingDeliveryNote
    {
        if ($po->status !== 'confirmed') {
            throw new \RuntimeException('PO must be in confirmed status to create DeliveryNote');
        }

        if ($po->items()->count() === 0) {
            throw new \RuntimeException('PO has no items');
        }

        return DB::transaction(function () use ($po) {
            // Generate DN number based on PO
            $dnNo = self::generateDeliveryNoteNo($po);

            // Create DeliveryNote from PO
            $deliveryNote = OutgoingDeliveryNote::create([
                'dn_no' => $dnNo,
                'transaction_no' => $po->po_no, // Link to original PO
                'customer_id' => $po->customer_id,
                'delivery_date' => $po->delivery_date ?? now()->addDays(3),
                'planned_delivery_date' => $po->delivery_date ?? now()->addDays(3),
                'status' => 'draft',
                'notes' => $po->notes ? "From PO: {$po->po_no}\n{$po->notes}" : "From PO: {$po->po_no}",
                'created_by' => Auth::id(),
            ]);

            // Map PO items to DeliveryNote items
            foreach ($po->items as $poItem) {
                OutgoingDeliveryNoteItem::create([
                    'delivery_note_id' => $deliveryNote->id,
                    'gci_part_id' => $poItem->gci_part_id,
                    'qty_ordered' => (float) $poItem->qty_ordered,
                    'qty_picked' => 0,
                    'qty_shipped' => 0,
                    'unit_price' => $poItem->unit_price,
                    'unit' => $poItem->unit,
                    'notes' => $poItem->notes,
                ]);
            }

            // Update PO to link to DeliveryNote
            $po->update([
                'delivery_note_id' => $deliveryNote->id,
            ]);

            return $deliveryNote;
        });
    }

    /**
     * Generate unique DeliveryNote number from PO
     */
    private static function generateDeliveryNoteNo(OutgoingPo $po): string
    {
        $today = now()->format('Ymd');
        $poNo = substr($po->po_no, -6); // Last 6 chars of PO

        $lastDn = OutgoingDeliveryNote::where('dn_no', 'like', "DN-{$today}-%")
            ->orderByDesc('dn_no')
            ->first();

        $seq = $lastDn ? ((int) substr($lastDn->dn_no, -3)) + 1 : 1;

        return sprintf('DN-%s-%03d', $today, $seq);
    }

    /**
     * Validate if PO can be converted to DeliveryNote
     */
    public static function canCreateDeliveryNote(OutgoingPo $po): array
    {
        $errors = [];

        if ($po->status !== 'confirmed') {
            $errors[] = 'PO must be confirmed status';
        }

        if ($po->items()->count() === 0) {
            $errors[] = 'PO has no items';
        }

        if ($po->delivery_note_id) {
            $errors[] = 'DeliveryNote already created for this PO';
        }

        return $errors;
    }
}
