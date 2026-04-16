<?php

namespace App\Services;

use App\Models\Department;
use App\Models\InventoryReturn;
use App\Models\InventoryStockMovement;
use App\Models\InventorySupply;
use App\Models\LocationInventory;
use App\Models\ProductionInventory;
use App\Models\ProductionOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductionInventoryFlowService
{
    public function recordSupply(ProductionOrder $order, array $issueLine): InventorySupply
    {
        [$department, $inventory] = $this->resolveTargets($order);

        $tagNumber = strtoupper(trim((string) ($issueLine['tag_number'] ?? '')));
        $qtySupply = round((float) ($issueLine['qty'] ?? 0), 4);
        $qtyConsumed = 0.0;
        $status = 'supplied';
        $policy = (string) ($issueLine['consumption_policy'] ?? 'backflush_return');

        if ($policy === 'direct_issue') {
            $qtyConsumed = $qtySupply;
            $status = 'consumed';
        }

        $supply = InventorySupply::updateOrCreate(
            [
                'production_order_id' => $order->id,
                'tag_number' => $tagNumber,
            ],
            [
                'department_id' => $department->id,
                'production_inventory_id' => $inventory->id,
                'gci_part_id' => (int) ($issueLine['gci_part_id'] ?? 0) ?: null,
                'part_id' => (int) ($issueLine['part_id'] ?? 0) ?: null,
                'part_no' => (string) ($issueLine['part_no'] ?? ''),
                'part_name' => (string) ($issueLine['part_name'] ?? ''),
                'uom' => (string) ($issueLine['uom'] ?? ''),
                'consumption_policy' => $policy,
                'status' => $status,
                'source_location_code' => (string) ($issueLine['source_location_code'] ?? ''),
                'target_location_code' => (string) ($issueLine['location_code'] ?? $inventory->location_code ?? ''),
                'qty_supply' => $qtySupply,
                'qty_consumed' => $qtyConsumed,
                'qty_returned' => (float) ($issueLine['returned_qty'] ?? 0),
                'traceability' => is_array($issueLine['traceability'] ?? null) ? $issueLine['traceability'] : [],
                'supplied_at' => $issueLine['posted_at'] ?? now(),
                'supplied_by' => Auth::id(),
            ]
        );

        $this->recordMovement($order, $supply, null, [
            'movement_type' => 'supply_to_department',
            'qty' => $qtySupply,
            'from_location_code' => $supply->source_location_code,
            'to_location_code' => $supply->target_location_code,
            'notes' => [
                'policy' => $policy,
                'department_code' => $department->code,
                'inventory_code' => $inventory->code,
            ],
            'moved_at' => $supply->supplied_at,
        ]);

        if ($policy === 'direct_issue' && $qtySupply > 0) {
            $this->recordMovement($order, $supply, null, [
                'movement_type' => 'consume_direct_issue',
                'qty' => $qtySupply,
                'from_location_code' => $supply->target_location_code,
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

        $fromLocation = (string) ($supply->target_location_code ?: 'AA-BULK');
        $toLocation = (string) ($supply->source_location_code ?: '');

        if ($toLocation === '') {
            throw new \RuntimeException("Lokasi asal tag {$tagNumber} kosong, return tidak bisa diproses.");
        }

        DB::transaction(function () use ($supply, $qtyReturn, $fromLocation, $toLocation, $notes) {
            LocationInventory::consumeStock(
                $supply->part_id ? (int) $supply->part_id : null,
                $fromLocation,
                $qtyReturn,
                $supply->tag_number,
                $supply->gci_part_id ? (int) $supply->gci_part_id : null,
                'PRODUCTION_RETURN_OUT',
                'PROD#' . $supply->production_order_id,
                array_merge(['source_tag' => $supply->tag_number], $supply->traceability ?? [])
            );

            LocationInventory::updateStock(
                $supply->part_id ? (int) $supply->part_id : null,
                $toLocation,
                $qtyReturn,
                $supply->tag_number,
                null,
                $supply->gci_part_id ? (int) $supply->gci_part_id : null,
                'PRODUCTION_RETURN_IN',
                'PROD#' . $supply->production_order_id,
                array_merge(['source_tag' => $supply->tag_number], $supply->traceability ?? [])
            );
        });

        $return = InventoryReturn::create([
            'inventory_supply_id' => $supply->id,
            'production_order_id' => $order->id,
            'department_id' => $supply->department_id,
            'production_inventory_id' => $supply->production_inventory_id,
            'gci_part_id' => $supply->gci_part_id,
            'part_id' => $supply->part_id,
            'tag_number' => $supply->tag_number,
            'uom' => $supply->uom,
            'from_location_code' => $fromLocation,
            'to_location_code' => $toLocation,
            'qty_return' => $qtyReturn,
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
                'department_id' => (int) $supply->department_id,
                'production_inventory_id' => (int) $supply->production_inventory_id,
                'tag_number' => (string) $supply->tag_number,
                'part_no' => (string) ($supply->part_no ?? ''),
                'part_name' => (string) ($supply->part_name ?? ''),
                'uom' => (string) ($supply->uom ?? ''),
                'policy' => (string) $supply->consumption_policy,
                'status' => (string) $supply->status,
                'source_location_code' => (string) ($supply->source_location_code ?? ''),
                'target_location_code' => (string) ($supply->target_location_code ?? ''),
                'qty_supply' => (float) $supply->qty_supply,
                'qty_consumed' => (float) $supply->qty_consumed,
                'qty_returned' => (float) $supply->qty_returned,
                'qty_remaining' => (float) $supply->qty_remaining,
                'supplied_at' => optional($supply->supplied_at)->toDateTimeString(),
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
                'qty_return' => (float) $return->qty_return,
                'uom' => (string) ($return->uom ?? ''),
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

    private function resolveTargets(ProductionOrder $order): array
    {
        $order->loadMissing('machine');

        $departmentName = trim((string) ($order->process_name ?: optional($order->machine)->name ?: 'Production'));
        $departmentCode = strtoupper(substr(Str::slug($departmentName, '-'), 0, 50));
        if ($departmentCode === '') {
            $departmentCode = 'PRODUCTION';
        }

        $department = Department::firstOrCreate(
            ['code' => $departmentCode],
            [
                'name' => $departmentName,
                'type' => 'production',
                'status' => 'active',
            ]
        );

        $inventoryCodeBase = optional($order->machine)->code ?: ($departmentCode . '-LINE');
        $inventoryCode = strtoupper(substr(Str::slug((string) $inventoryCodeBase, '-'), 0, 80));

        $inventory = ProductionInventory::firstOrCreate(
            ['code' => $inventoryCode],
            [
                'department_id' => $department->id,
                'machine_id' => $order->machine_id,
                'name' => trim((string) (optional($order->machine)->name ?: ($departmentName . ' Line'))),
                'inventory_type' => $order->machine_id ? 'machine_line' : 'department_line',
                'location_code' => 'AA-BULK',
                'status' => 'active',
            ]
        );

        return [$department, $inventory];
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
