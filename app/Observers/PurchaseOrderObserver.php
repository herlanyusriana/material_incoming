<?php

namespace App\Observers;

use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderIncomingService;
use Illuminate\Support\Facades\Log;

class PurchaseOrderObserver
{
    /**
     * Handle the PurchaseOrder "updated" event
     * Trigger: When PO status changes to 'confirmed'
     */
    public function updated(PurchaseOrder $po): void
    {
        // Only trigger if status changed TO 'confirmed'
        if ($po->isDirty('status') && $po->status === 'confirmed') {
            try {
                // Auto-create IncomingArrival
                $arrival = PurchaseOrderIncomingService::createIncomingArrivalFromPo($po);

                Log::info('IncomingArrival auto-created from PO', [
                    'po_id' => $po->id,
                    'po_number' => $po->po_number,
                    'arrival_no' => $arrival->arrival_no,
                ]);

            } catch (\Exception $e) {
                Log::warning('Failed to create IncomingArrival from PO', [
                    'po_id' => $po->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't throw - let PO confirm succeed even if arrival creation fails
                // User can manually create arrival if needed
            }
        }
    }
}
