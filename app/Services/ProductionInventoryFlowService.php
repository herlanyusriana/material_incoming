<?php

namespace App\Services;

use App\Models\NewSchema\Core\Department;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Inventory\InventoryReturn;
use App\Models\NewSchema\Inventory\InventoryStockMovement;
use App\Models\NewSchema\Inventory\InventorySupply;
use App\Models\NewSchema\Production\ProductionOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductionInventoryFlowService
{
    public function recordSupply(ProductionOrder $order, array $issueLine): InventorySupply
    {
        $department = $this->resolveDepartment($order);

        $tagNumber = strtoupper(trim((string) ($issueLine['tag_number'] ?? '')));
        $qtySupply = round((float) ($issueLine['qty'] ?? 0), 4);
        $qtyConsumed = 0.0;
        $status = 'supplied';
        $policy = (string) ($issueLine['consumption_policy'] ?? 'backflush_return');

        if ($policy === 'direct_issue') {
            $qtyConsumed = $qtySupply;
            $status = 'consumed';
        }

        $gciPartId = (int) ($issueLine['gci_part_id'] ?? 0) ?: null;

        $supply = InventorySupply::updateOrCreate(
            [
                'production_order_id' => $order->id,
                'tag_number' => $tagNumber,
            ],
            [
                'gci_part_id' => $gciPartId,
                'qty_supplied' => $qtySupply,
                'qty_consumed' => $qtyConsumed,
                'qty_returned' => (float) ($issueLine['returned_qty'] ?? 0),
                'unit' => (string) ($issueLine['uom'] ?? ''),
                'to_location_code' => (string) ($issueLine['location_code'] ?? ''),
                'supplied_at' => $issueLine['posted_at'] ?? now(),
                'supplied_by' => Auth::id(),
                'notes' => [
                    'policy' => $policy,
                    'department_id' => $department->id,
                    'department_code' => $department->code ?? $department->department_code ?? null,
                    'source_location_code' => (string) ($issueLine['source_location_code'] ?? ''),
                    'traceability' => is_array($issueLine['traceability'] ?? null) ? $issueLine['traceability'] : [],
                ],
            ]
        );

        $this->recordMovement($order, $supply, null, [
            'movement_type' => 'supply_to_department',
            'qty' => $qtySupply,
            'from_location_code' => $supply->source_location_code ?? (string) ($issueLine['source_location_code'] ?? ''),
            'to_location_code' => $supply->to_location_code ?? (string) ($issueLine['location_code'] ?? ''),
            'notes' => [
                'policy' => $policy,
                'department_id' => $department->id,
                'department_code' => $department->code ?? $department->department_code ?? null,
            ],
            'moved_at' => $supply->supplied_at,
        ]);

        if ($policy === 'direct_issue' && $qtySupply > 0 && $gciPartId) {
            $this->recordMovement($order, $supply, null, [
                'movement_type' => 'consume_direct_issue',
                'qty' => $qtySupply,
                'from_location_code' => $supply->to_location_code ?? (string) ($issueLine['location_code'] ?? ''),
                'to_location_code' => null,
                'notes' => [
                    'policy' => $policy,
                ],
                'moved_at' => $supply->supplied_at,
            ]);
        }

        return $supply->fresh();
    }

    public function recordBackflushConsumption(ProductionOrder $order, array $issueLine, float $qty): ?InventorySupply
    {
        $qty = round($qty, 4);
        if ($qty <= 0) {
            return null;
        }

        $tagNumber = strtoupper(trim((string) ($issueLine['tag_number'] ?? '')));
        if ($tagNumber === '') {
            return null;
        }

        /** @var InventorySupply|null $supply */
        $supply = InventorySupply::query()
            ->where('production_order_id', $order->id)
            ->where('tag_number', $tagNumber)
            ->first();

        if (!$supply) {
            return null;
        }

        $available = max(0, round((float) $supply->qty_supply - (float) $supply->qty_consumed - (float) $supply->qty_returned, 4));
        $consumeQty = min($qty, $available);
        if ($consumeQty <= 0) {
            return $supply;
        }

        $supply->qty_consumed = round((float) $supply->qty_consumed + $consumeQty, 4);
        $supply->status = $this->resolveStatus($supply);
        $supply->save();

        $this->recordMovement($order, $supply, null, [
            'movement_type' => 'consume_production',
            'qty' => $consumeQty,
            'from_location_code' => $supply->target_location_code,
            'to_location_code' => null,
            'notes' => [
                'policy' => $supply->consumption_policy,
                'backflush' => true,
            ],
        ]);

        return $supply->fresh();
    }

    public function returnSupply(ProductionOrder $order, string $tagNumber, ?float $qtyReturn = null, array $notes = []): InventoryReturn
    {
        $tagNumber = strtoupper(trim($tagNumber));

        /** @var InventorySupply $supply */
        $supply = InventorySupply::query()
            ->where('production_order_id', $order->id)
            ->where('tag_number', $tagNumber)
            ->firstOrFail();

        $remaining = max(0, round((float) $supply->qty_supply - (float) $supply->qty_consumed - (float) $supply->qty_returned, 4));
        if ($remaining <= 0) {
            throw new \RuntimeException("Tag {$tagNumber} sudah tidak punya sisa untuk dibalikkan.");
        }

        $qtyReturn = $qtyReturn !== null ? round($qtyReturn, 4) : $remaining;
        if ($qtyReturn <= 0 || $qtyReturn > $remaining) {
            throw new \RuntimeException("Qty return {$qtyReturn} tidak valid. Sisa tag {$remaining}.");
        }

        $fromLocation = (string) ($supply->to_location_code ?: 'AA-BULK');
        $toLocation = (string) ($supply->notes['source_location_code'] ?? '');

        if ($toLocation === '') {
            throw new \RuntimeException("Lokasi asal tag {$tagNumber} kosong, return tidak bisa diproses.");
        }

        if (!$supply->gci_part_id) {
            throw new \RuntimeException("Supply tag {$tagNumber} tidak punya gci_part_id, return stok tidak bisa diproses.");
        }

        $gciPartId = (int) $supply->gci_part_id;
        $sourceReference = 'PROD#' . $supply->production_order_id;

        DB::transaction(function () use ($gciPartId, $qtyReturn, $fromLocation, $toLocation, $supply, $sourceReference) {
            InventoryLocationStock::consumeStock(
                gciPartId: $gciPartId,
                locationCode: $fromLocation,
                qty: $qtyReturn,
                batchNo: $supply->tag_number,
                transactionType: 'PRODUCTION_RETURN_OUT',
                sourceReference: $sourceReference,
                createdBy: Auth::id()
            );

            InventoryLocationStock::updateStock(
                gciPartId: $gciPartId,
                locationCode: $toLocation,
                qtyChange: $qtyReturn,
                batchNo: $supply->tag_number,
                tag: null,
                transactionType: 'PRODUCTION_RETURN_IN',
                sourceReference: $sourceReference,
                sourceReceiveId: null,
                sourceArrivalId: null,
                sourceInvoiceNo: null,
                sourceDeliveryNoteNo: null,
                weightKgm: null,
                createdBy: Auth::id()
            );
        });

        $return = InventoryReturn::create([
            'inventory_supply_id' => $supply->id,
            'production_order_id' => $order->id,
            'gci_part_id' => $supply->gci_part_id,
            'tag_number' => $supply->tag_number,
            'unit' => $supply->unit,
            'from_location_code' => $fromLocation,
            'to_location_code' => $toLocation,
            'qty_returned' => $qtyReturn,
            'reason' => $notes['reason'] ?? (is_string($notes) ? $notes : null),
            'notes' => $notes,
            'returned_at' => now(),
            'returned_by' => Auth::id(),
        ]);

        $supply->qty_returned = round((float) $supply->qty_returned + $qtyReturn, 4);
        $supply->status = $this->resolveStatus($supply);
        $supply->save();

        $this->recordMovement($order, $supply, $return, [
            'movement_type' => 'return_to_wh',
            'qty' => $qtyReturn,
            'from_location_code' => $fromLocation,
            'to_location_code' => $toLocation,
            'notes' => $notes,
            'moved_at' => $return->returned_at,
        ]);

        return $return->fresh();
    }

    public function summarizeOrderFlow(ProductionOrder $order): array
    {
        $supplies = InventorySupply::query()
            ->where('production_order_id', $order->id)
            ->orderBy('supplied_at')
            ->get()
            ->map(fn (InventorySupply $supply) => [
                'id' => (int) $supply->id,
                'tag_number' => (string) $supply->tag_number,
                'part_no' => (string) ($supply->gciPart?->part_no ?? ''),
                'part_name' => (string) ($supply->gciPart?->part_name ?? ''),
                'uom' => (string) ($supply->unit ?? ''),
                'status' => (string) $supply->status,
                'source_location_code' => (string) ($supply->notes['source_location_code'] ?? ''),
                'target_location_code' => (string) ($supply->to_location_code ?? ''),
                'qty_supply' => (float) $supply->qty_supplied,
                'qty_consumed' => (float) $supply->qty_consumed,
                'qty_returned' => (float) $supply->qty_returned,
                'qty_remaining' => max(0, round((float) $supply->qty_supplied - (float) $supply->qty_consumed - (float) $supply->qty_returned, 4)),
                'supplied_at' => optional($supply->supplied_at)->toDateTimeString(),
                'notes' => $supply->notes ?? [],
            ])
            ->values()
            ->all();

        $returns = InventoryReturn::query()
            ->where('production_order_id', $order->id)
            ->orderBy('returned_at')
            ->get()
            ->map(fn (InventoryReturn $return) => [
                'id' => (int) $return->id,
                'inventory_supply_id' => (int) $return->inventory_supply_id,
                'tag_number' => (string) $return->tag_number,
                'qty_return' => (float) $return->qty_returned,
                'uom' => (string) ($return->unit ?? ''),
                'from_location_code' => (string) ($return->from_location_code ?? ''),
                'to_location_code' => (string) ($return->to_location_code ?? ''),
                'returned_at' => optional($return->returned_at)->toDateTimeString(),
                'notes' => $return->notes ?? [],
            ])
            ->values()
            ->all();

        return [
            'supplies' => $supplies,
            'returns' => $returns,
        ];
    }

    private function resolveDepartment(ProductionOrder $order): Department
    {
        $order->loadMissing('machine');

        $departmentName = trim((string) ($order->process_name ?: optional($order->machine)->name ?: 'Production'));
        $departmentCode = strtoupper(substr(Str::slug($departmentName, '-'), 0, 50));
        if ($departmentCode === '') {
            $departmentCode = 'PRODUCTION';
        }

        return Department::firstOrCreate(
            ['code' => $departmentCode],
            [
                'name' => $departmentName,
            ]
        );
    }

    private function resolveStatus(InventorySupply $supply): string
    {
        $remaining = max(0, round((float) $supply->qty_supply - (float) $supply->qty_consumed - (float) $supply->qty_returned, 4));

        if ($remaining <= 0) {
            if ((float) $supply->qty_returned > 0 && (float) $supply->qty_consumed <= 0) {
                return 'returned';
            }

            if ((float) $supply->qty_returned > 0 && (float) $supply->qty_consumed > 0) {
                return 'closed';
            }

            return 'consumed';
        }

        if ((float) $supply->qty_consumed > 0 || (float) $supply->qty_returned > 0) {
            return 'partial';
        }

        return 'supplied';
    }

    private function recordMovement(ProductionOrder $order, InventorySupply $supply, ?InventoryReturn $return, array $payload): void
    {
        InventoryStockMovement::create([
            'production_order_id' => $order->id,
            'inventory_supply_id' => $supply->id,
            'inventory_return_id' => $return?->id,
            'department_id' => $supply->department_id,
            'production_inventory_id' => $supply->production_inventory_id,
            'gci_part_id' => $supply->gci_part_id,
            'part_id' => $supply->part_id,
            'tag_number' => $supply->tag_number,
            'part_no' => $supply->part_no,
            'part_name' => $supply->part_name,
            'movement_type' => (string) ($payload['movement_type'] ?? 'movement'),
            'uom' => $supply->uom,
            'from_location_code' => $payload['from_location_code'] ?? null,
            'to_location_code' => $payload['to_location_code'] ?? null,
            'qty' => round((float) ($payload['qty'] ?? 0), 4),
            'notes' => $payload['notes'] ?? null,
            'moved_at' => $payload['moved_at'] ?? now(),
            'created_by' => Auth::id(),
        ]);
    }
}
