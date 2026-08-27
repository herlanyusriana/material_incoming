<?php

namespace App\Services;

use App\Models\NewSchema\Core\Customer;
use App\Models\NewSchema\Outgoing\Driver;
use App\Models\NewSchema\Outgoing\OutgoingDeliveryNote;
use App\Models\NewSchema\Outgoing\OutgoingDeliveryNoteItem;
use App\Models\NewSchema\Outgoing\OutgoingDeliveryOrder;
use App\Models\NewSchema\Outgoing\Truck;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeliveryOutgoingService
{
    public function getReadyForDeliveryOrders(?int $customerId = null, ?string $status = null): \Illuminate\Support\Collection
    {
        $query = OutgoingDeliveryOrder::with(['customer', 'items.gciPart'])
            ->where('status', 'confirmed')
            ->whereDoesntHave('deliveryNoteItems', function ($q) {
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

    public function createDeliveryNote(array $deliveryOrderIds, int $customerId, ?int $truckId = null, ?int $driverId = null, array $options = []): OutgoingDeliveryNote
    {
        $deliveryOrders = OutgoingDeliveryOrder::with(['items.gciPart', 'customer'])
            ->whereIn('id', $deliveryOrderIds)
            ->where('customer_id', $customerId)
            ->where('status', 'confirmed')
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

            $deliveryNote = OutgoingDeliveryNote::create([
                'dn_no' => $deliveryNo,
                'transaction_no' => $deliveryNo,
                'customer_id' => $customerId,
                'truck_id' => $truckId,
                'driver_id' => $driverId,
                'status' => 'draft',
                'notes' => $options['notes'] ?? null,
                'delivery_date' => $options['delivery_date'] ?? now()->toDateString(),
                'planned_delivery_date' => $options['delivery_date'] ?? now()->toDateString(),
                'created_by' => $options['created_by'] ?? null,
            ]);

            foreach ($deliveryOrders as $do) {
                foreach ($do->items as $item) {
                    $remaining = round((float) $item->qty_ordered - (float) $item->qty_delivered, 4);
                    if ($remaining <= 0) {
                        continue;
                    }

                    OutgoingDeliveryNoteItem::create([
                        'delivery_note_id' => $deliveryNote->id,
                        'gci_part_id' => $item->gci_part_id,
                        'qty_delivered' => $remaining,
                        'unit' => $item->unit,
                        'sales_order_item_id' => null,
                        'picking_fg_id' => null,
                        'batch_no' => null,
                        'from_location_code' => null,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->unit_price !== null ? round((float) $item->unit_price * $remaining, 4) : null,
                        'notes' => $item->notes ?? null,
                    ]);

                    $item->qty_delivered = round((float) $item->qty_delivered + $remaining, 4);
                    $item->save();
                }

                $allDelivered = $do->items->every(fn ($item) => (float) $item->qty_delivered >= (float) $item->qty_ordered);
                if ($allDelivered) {
                    $do->status = 'delivered';
                    $do->save();
                }
            }

            DB::commit();

            return $deliveryNote->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getDeliveriesGroupedByCustomer(?string $status = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = OutgoingDeliveryNote::with(['customer', 'deliveryOrders', 'items.gciPart', 'truck'])
            ->join('customers', 'outgoing_delivery_notes.customer_id', '=', 'customers.id');

        if ($status) {
            $query->where('outgoing_delivery_notes.status', $status);
        }

        if ($dateFrom) {
            $query->where('outgoing_delivery_notes.delivery_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('outgoing_delivery_notes.delivery_date', '<=', $dateTo);
        }

        $deliveries = $query->select('outgoing_delivery_notes.*', 'customers.customer_name as customer_name')
            ->orderBy('customers.customer_name')
            ->orderBy('outgoing_delivery_notes.delivery_date', 'desc')
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

    public function assignToTruck(int $deliveryNoteId, int $truckId, array $options = []): OutgoingDeliveryNote
    {
        $deliveryNote = OutgoingDeliveryNote::findOrFail($deliveryNoteId);
        $truck = Truck::findOrFail($truckId);

        $deliveryNote->update([
            'truck_id' => $truckId,
            'status' => $options['status'] ?? 'loaded',
        ]);

        return $deliveryNote->fresh();
    }

    public function assignToDriver(int $deliveryNoteId, int $driverId, array $options = []): OutgoingDeliveryNote
    {
        $deliveryNote = OutgoingDeliveryNote::findOrFail($deliveryNoteId);
        $driver = Driver::findOrFail($driverId);

        $deliveryNote->update([
            'driver_id' => $driverId,
            'status' => $options['status'] ?? $deliveryNote->status,
        ]);

        return $deliveryNote->fresh();
    }

    private function generateDeliveryNoteNumber(): string
    {
        $year = Carbon::now()->year;
        $lastDelivery = OutgoingDeliveryNote::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $lastSequence = 0;
        if ($lastDelivery) {
            $parts = explode('-', $lastDelivery->dn_no);
            $lastSequence = (int) ($parts[2] ?? 0);
        }

        $next = str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);

        return 'DN-' . $year . '-' . $next;
    }

    public function updateDeliveryStatus(int $deliveryNoteId, string $status, array $options = []): OutgoingDeliveryNote
    {
        $validStatuses = ['draft', 'picked', 'loaded', 'in_transit', 'delivered', 'cancelled'];
        if (!in_array($status, $validStatuses, true)) {
            throw new \Exception('Invalid delivery status: ' . $status);
        }

        $deliveryNote = OutgoingDeliveryNote::findOrFail($deliveryNoteId);
        $deliveryNote->update([
            'status' => $status,
        ]);

        return $deliveryNote->fresh();
    }

    public function getDeliveryStats(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = OutgoingDeliveryNote::query()
            ->selectRaw('status, COUNT(*) as count')
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
        ];
    }
}
