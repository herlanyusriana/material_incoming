<?php

namespace App\Services;

use App\Models\NewSchema\Incoming\IncomingArrival;
use App\Models\NewSchema\Incoming\IncomingArrivalItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderIncomingService
{
    /**
     * Auto-create IncomingArrival from confirmed PurchaseOrder
     * Triggered when PO status changes to 'confirmed'
     *
     * Flow:
     * 1. PO confirmed by procurement
     * 2. This service creates IncomingArrival record
     * 3. Tracks expected shipment from vendor
     * 4. Ready to receive items when they arrive
     */
    public static function createIncomingArrivalFromPo(PurchaseOrder $po): ?IncomingArrival
    {
        if ($po->status !== 'confirmed') {
            throw new \RuntimeException('PO must be in confirmed status to create IncomingArrival');
        }

        if ($po->items()->count() === 0) {
            throw new \RuntimeException('PO has no items');
        }

        if ($po->incoming_arrival_id) {
            throw new \RuntimeException('IncomingArrival already created for this PO');
        }

        return DB::transaction(function () use ($po) {
            // Generate Arrival number based on PO
            $arrivalNo = self::generateArrivalNo($po);

            // Create IncomingArrival record
            $arrival = IncomingArrival::create([
                'arrival_no' => $arrivalNo,
                'po_no' => $po->po_number ?? $po->id,
                'vendor_id' => $po->vendor_id,
                'invoice_no' => null, // Will be filled when invoice received
                'invoice_date' => null,
                'ata_date' => null,
                'eta_jkt' => $po->expected_delivery_date ?? now()->addDays(30),
                'eta_gci' => $po->expected_delivery_date ?? now()->addDays(30),
                'vessel' => null,
                'port_of_loading' => null,
                'bill_of_lading' => null,
                'bill_of_lading_status' => 'pending',
                'container_numbers' => null,
                'seal_code' => null,
                'hs_code' => null,
                'hs_codes' => null,
                'status' => 'draft', // Start as draft, changes to received when items arrive
                'notes' => "Auto-created from PO #{$po->po_number}",
                'created_by' => Auth::id(),
            ]);

            // Map PO items to IncomingArrivalItems
            foreach ($po->items as $poItem) {
                IncomingArrivalItem::create([
                    'incoming_arrival_id' => $arrival->id,
                    'gci_part_id' => $poItem->gci_part_id,
                    'vendor_part_id' => $poItem->vendor_part_id ?? null,
                    'vendor_part_no' => $poItem->part_no ?? '',
                    'vendor_part_name' => $poItem->part_name ?? '',
                    'qty_goods' => (float) $poItem->qty_ordered,
                    'unit_goods' => $poItem->unit ?? 'PCS',
                    'qty_bundle' => null,
                    'unit_bundle' => null,
                    'weight_nett' => null,
                    'weight_gross' => null,
                    'price' => $poItem->unit_price ?? 0,
                    'material_group' => null,
                    'size' => null,
                    'notes' => $poItem->notes ?? null,
                ]);
            }

            // Link PO to Arrival for traceability
            $po->update([
                'incoming_arrival_id' => $arrival->id,
            ]);

            return $arrival;
        });
    }

    /**
     * Validate if PO can be converted to IncomingArrival
     */
    public static function canCreateIncomingArrival(PurchaseOrder $po): array
    {
        $errors = [];

        if ($po->status !== 'confirmed') {
            $errors[] = 'PO must be confirmed status';
        }

        if ($po->items()->count() === 0) {
            $errors[] = 'PO has no items';
        }

        if ($po->incoming_arrival_id) {
            $errors[] = 'IncomingArrival already created for this PO';
        }

        return $errors;
    }

    /**
     * Generate unique Arrival number from PO
     */
    private static function generateArrivalNo(PurchaseOrder $po): string
    {
        $today = now()->format('Ymd');

        $lastArrival = IncomingArrival::where('arrival_no', 'like', "ARR-{$today}-%")
            ->orderByDesc('arrival_no')
            ->first();

        $seq = $lastArrival ? ((int) substr($lastArrival->arrival_no, -3)) + 1 : 1;

        return sprintf('ARR-%s-%03d', $today, $seq);
    }
}
