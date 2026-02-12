<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\DeliveryNote;
use App\Models\DeliveryItem;
use App\Models\Customer;
use App\Models\Trucking;
use App\Models\GciPart;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeliveryOutgoingService
{
    /**
     * Get finished goods sales orders that are ready for delivery (picked/completed)
     *
     * @param int|null $customerId Optional customer ID to filter
     * @param string|null $status Optional status to filter
     * @return \Illuminate\Support\Collection
     */
    public function getReadyForDeliveryOrders(?int $customerId = null, ?string $status = null): \Illuminate\Support\Collection
    {
        $query = SalesOrder::with(['customer', 'items.part'])
            ->where('status', 'completed') // Assuming 'completed' means picked/ready
            ->whereDoesntHave('deliveryItems', function ($q) {
                // Exclude orders that are already assigned to a delivery note
                $q->whereHas('deliveryNote', function ($dnQuery) {
                    $dnQuery->where('status', '!=', 'cancelled');
                });
            });

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Create a delivery note from selected sales orders
     *
     * @param array $salesOrderIds Array of sales order IDs
     * @param int $customerId Customer ID
     * @param int|null $truckId Optional truck ID
     * @param int|null $driverId Optional driver ID
     * @param array $options Additional options
     * @return DeliveryNote
     */
    public function createDeliveryNote(array $salesOrderIds, int $customerId, ?int $truckId = null, ?int $driverId = null, array $options = []): DeliveryNote
    {
        $salesOrders = SalesOrder::with(['items.part', 'customer'])
            ->whereIn('id', $salesOrderIds)
            ->where('customer_id', $customerId)
            ->where('status', 'completed') // Only completed orders
            ->get();

        if ($salesOrders->isEmpty()) {
            throw new \Exception('No valid sales orders found for delivery');
        }

        // Validate that all orders belong to the same customer
        $customerIds = $salesOrders->pluck('customer_id')->unique();
        if ($customerIds->count() > 1) {
            throw new \Exception('All selected orders must belong to the same customer');
        }

        DB::beginTransaction();

        try {
            // Generate delivery note number
            $deliveryNo = $this->generateDeliveryNoteNumber();

            // Create delivery note
            $deliveryNote = DeliveryNote::create([
                'delivery_no' => $deliveryNo,
                'customer_id' => $customerId,
                'truck_id' => $truckId,
                'driver_id' => $driverId, // Add driver ID
                'status' => 'prepared', // Initially prepared
                'notes' => $options['notes'] ?? null,
                'delivery_date' => $options['delivery_date'] ?? null,
                'created_by' => $options['created_by'] ?? null,
            ]);

            // Add items from sales orders to delivery note
            foreach ($salesOrders as $so) {
                foreach ($so->items as $item) {
                    DeliveryItem::create([
                        'delivery_note_id' => $deliveryNote->id,
                        'sales_order_id' => $so->id,
                        'part_id' => $item->part_id,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'notes' => $item->notes ?? null,
                    ]);
                }
            }

            // Optionally update sales order status to indicate they're assigned to delivery
            $salesOrders->each(function ($so) {
                // You might want to update SO status to 'shipped' or similar
                // $so->update(['status' => 'shipped']);
            });

            DB::commit();

            return $deliveryNote->fresh();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get delivery notes grouped by customer
     *
     * @param string|null $status Optional status to filter
     * @param string|null $dateFrom Optional start date
     * @param string|null $dateTo Optional end date
     * @return array
     */
    public function getDeliveriesGroupedByCustomer(?string $status = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = DeliveryNote::with(['customer', 'items.salesOrder', 'items.part', 'truck'])
            ->join('customers', 'delivery_notes.customer_id', '=', 'customers.id');

        if ($status) {
            $query->where('delivery_notes.status', $status);
        }

        if ($dateFrom) {
            $query->where('delivery_notes.delivery_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('delivery_notes.delivery_date', '<=', $dateTo);
        }

        $deliveries = $query->select('delivery_notes.*', 'customers.name as customer_name')
            ->orderBy('customers.name')
            ->orderBy('delivery_notes.delivery_date', 'desc')
            ->get();

        // Group by customer
        $grouped = [];
        foreach ($deliveries as $delivery) {
            $customerId = $delivery->customer_id;
            if (!isset($grouped[$customerId])) {
                $grouped[$customerId] = [
                    'customer' => $delivery->customer,
                    'deliveries' => []
                ];
            }
            $grouped[$customerId]['deliveries'][] = $delivery;
        }

        return $grouped;
    }

    /**
     * Assign delivery note to a truck
     *
     * @param int $deliveryNoteId
     * @param int $truckId
     * @param array $options
     * @return DeliveryNote
     */
    public function assignToTruck(int $deliveryNoteId, int $truckId, array $options = []): DeliveryNote
    {
        $deliveryNote = DeliveryNote::findOrFail($deliveryNoteId);
        $truck = Trucking::findOrFail($truckId);

        $deliveryNote->update([
            'truck_id' => $truckId,
            'assigned_at' => now(),
            'status' => $options['status'] ?? 'assigned', // Could be 'assigned', 'in_transit', etc.
        ]);

        return $deliveryNote->fresh();
    }

    /**
     * Assign delivery note to a driver
     *
     * @param int $deliveryNoteId
     * @param int $driverId
     * @param array $options
     * @return DeliveryNote
     */
    public function assignToDriver(int $deliveryNoteId, int $driverId, array $options = []): DeliveryNote
    {
        $deliveryNote = DeliveryNote::findOrFail($deliveryNoteId);
        $driver = User::findOrFail($driverId);

        $deliveryNote->update([
            'driver_id' => $driverId,
            'status' => $options['status'] ?? $deliveryNote->status,
        ]);

        return $deliveryNote->fresh();
    }

    /**
     * Generate delivery note number
     *
     * @return string
     */
    private function generateDeliveryNoteNumber(): string
    {
        $year = Carbon::now()->year;
        $lastDelivery = DeliveryNote::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $lastSequence = 0;
        if ($lastDelivery) {
            $parts = explode('-', $lastDelivery->dn_no);
            $lastSequence = (int)($parts[2] ?? 0);
        }

        $next = str_pad((string)($lastSequence + 1), 4, '0', STR_PAD_LEFT);

        return 'DN-' . $year . '-' . $next;
    }

    /**
     * Update delivery note status
     *
     * @param int $deliveryNoteId
     * @param string $status
     * @param array $options
     * @return DeliveryNote
     */
    public function updateDeliveryStatus(int $deliveryNoteId, string $status, array $options = []): DeliveryNote
    {
        $validStatuses = ['prepared', 'assigned', 'in_transit', 'delivered', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid delivery status: ' . $status);
        }

        $deliveryNote = DeliveryNote::findOrFail($deliveryNoteId);
        $deliveryNote->update([
            'status' => $status,
            'delivered_at' => $status === 'delivered' ? now() : $deliveryNote->delivered_at,
        ]);

        return $deliveryNote->fresh();
    }

    /**
     * Get delivery statistics
     *
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function getDeliveryStats(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = DeliveryNote::selectRaw('status, COUNT(*) as count, SUM(total_value) as total_value')
            ->groupBy('status');

        if ($dateFrom) {
            $query->where('delivery_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('delivery_date', '<=', $dateTo);
        }

        $stats = $query->get()->keyBy('status');

        return [
            'total_deliveries' => $stats->sum('count'),
            'by_status' => $stats,
            'total_value' => $stats->sum('total_value'),
        ];
    }
}