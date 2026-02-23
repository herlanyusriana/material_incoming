<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\DeliveryNote;
use App\Models\DeliveryItem;
use App\Models\Customer;
use App\Models\Trucking;
use App\Models\GciPart;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeliveryOutgoingService
{
    public function getReadyForDeliveryOrders(?int $customerId = null, ?string $status = null): \Illuminate\Support\Collection
    {
        $query = DeliveryOrder::with(['customer', 'items.part'])
            ->where('status', 'completed')
            ->whereDoesntHave('deliveryItems', function ($q) {
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

    public function createDeliveryNote(array $deliveryOrderIds, int $customerId, ?int $truckId = null, ?int $driverId = null, array $options = []): DeliveryNote
    {
        $deliveryOrders = DeliveryOrder::with(['items.part', 'customer'])
            ->whereIn('id', $deliveryOrderIds)
            ->where('customer_id', $customerId)
            ->where('status', 'completed')
            ->get();

        if ($deliveryOrders->isEmpty()) {
            throw new \Exception('No valid delivery orders found for delivery');
        }

        $customerIds = $deliveryOrders->pluck('customer_id')->unique();
        if ($customerIds->count() > 1) {
            throw new \Exception('All selected orders must belong to the same customer');
        }

        DB::beginTransaction();

        try {
            $deliveryNo = $this->generateDeliveryNoteNumber();

            $deliveryNote = DeliveryNote::create([
                'delivery_no' => $deliveryNo,
                'customer_id' => $customerId,
                'truck_id' => $truckId,
                'driver_id' => $driverId,
                'status' => 'prepared',
                'notes' => $options['notes'] ?? null,
                'delivery_date' => $options['delivery_date'] ?? null,
                'created_by' => $options['created_by'] ?? null,
            ]);

            foreach ($deliveryOrders as $do) {
                foreach ($do->items as $item) {
                    DeliveryItem::create([
                        'delivery_note_id' => $deliveryNote->id,
                        'delivery_order_id' => $do->id,
                        'part_id' => $item->part_id,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'notes' => $item->notes ?? null,
                    ]);
                }
            }

            DB::commit();

            return $deliveryNote->fresh();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function getDeliveriesGroupedByCustomer(?string $status = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = DeliveryNote::with(['customer', 'items.deliveryOrder', 'items.part', 'truck'])
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

    public function assignToTruck(int $deliveryNoteId, int $truckId, array $options = []): DeliveryNote
    {
        $deliveryNote = DeliveryNote::findOrFail($deliveryNoteId);
        $truck = Trucking::findOrFail($truckId);

        $deliveryNote->update([
            'truck_id' => $truckId,
            'assigned_at' => now(),
            'status' => $options['status'] ?? 'assigned',
        ]);

        return $deliveryNote->fresh();
    }

    public function assignToDriver(int $deliveryNoteId, int $driverId, array $options = []): DeliveryNote
    {
        $deliveryNote = DeliveryNote::findOrFail($deliveryNoteId);

        $deliveryNote->update([
            'driver_id' => $driverId,
            'status' => $options['status'] ?? $deliveryNote->status,
        ]);

        return $deliveryNote->fresh();
    }

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
