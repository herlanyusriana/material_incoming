<?php

namespace App\Services;

use App\Models\Bom;
use App\Models\GciInventory;
use App\Models\LocationInventory;
use App\Models\ProductionOrder;
use App\Models\Receive;

class ProductionMaterialRequestService
{
    public function buildLines(ProductionOrder $order): array
    {
        $order->loadMissing(['part']);

        $bom = Bom::activeVersion($order->gci_part_id, $order->plan_date);
        if (!$bom) {
            return [];
        }

        $bomItems = $bom->items()
            ->with([
                'componentPart',
                'incomingPart',
                'substitutes.part',
                'substitutes.incomingPart',
            ])
            ->get();

        $lines = [];

        foreach ($bomItems as $item) {
            $makeOrBuy = strtoupper(trim((string) ($item->make_or_buy ?? '')));
            if (!$this->isWarehouseScannableRm($makeOrBuy)) {
                continue;
            }

            $requiredQty = round((float) ($item->net_required ?? $item->usage_qty ?? 0) * (float) $order->qty_planned, 4);
            if ($requiredQty <= 0) {
                continue;
            }

            $candidateParts = collect();
            if ($item->incomingPart) {
                $candidateParts->push([
                    'type' => 'primary',
                    'part_id' => (int) $item->incomingPart->id,
                    'part_no' => (string) ($item->incomingPart->part_no ?? '-'),
                    'part_name' => (string) ($item->incomingPart->part_name ?? '-'),
                ]);
            }

            foreach (($item->substitutes ?? collect()) as $substitute) {
                if (!$substitute->incomingPart) {
                    continue;
                }

                $candidateParts->push([
                    'type' => 'substitute',
                    'part_id' => (int) $substitute->incomingPart->id,
                    'part_no' => (string) ($substitute->incomingPart->part_no ?? $substitute->substitute_part_no ?? '-'),
                    'part_name' => (string) ($substitute->incomingPart->part_name ?? $substitute->part?->part_name ?? '-'),
                ]);
            }

            $candidateParts = $candidateParts
                ->filter(fn($part) => !empty($part['part_id']))
                ->unique('part_id')
                ->values();

            if ($candidateParts->isEmpty()) {
                $lines[] = [
                    'component_gci_part_id' => (int) ($item->component_part_id ?? 0),
                    'component_part_no' => (string) ($item->componentPart?->part_no ?? $item->component_part_no ?? '-'),
                    'component_part_name' => (string) ($item->componentPart?->part_name ?? '-'),
                    'uom' => (string) ($item->consumption_uom ?? $item->componentPart?->uom ?? 'PCS'),
                    'make_or_buy' => $makeOrBuy,
                    'is_backflush' => (bool) ($item->componentPart?->is_backflush ?? true),
                    'required_qty' => $requiredQty,
                    'available_qty' => 0,
                    'shortage_qty' => $requiredQty,
                    'allocations' => [],
                    'notes' => 'Incoming RM part belum dipetakan pada BOM.',
                ];
                continue;
            }

            $remaining = $requiredQty;
            $allocations = [];

            foreach ($candidateParts as $candidate) {
                if ($remaining <= 0) {
                    break;
                }

                $stocks = LocationInventory::query()
                    ->where('part_id', $candidate['part_id'])
                    ->where('qty_on_hand', '>', 0)
                    ->orderByRaw('production_date IS NULL')
                    ->orderBy('production_date')
                    ->orderBy('batch_no')
                    ->orderBy('location_code')
                    ->get();

                foreach ($stocks as $stock) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $available = (float) $stock->qty_on_hand;
                    if ($available <= 0) {
                        continue;
                    }

                    $pickedQty = min($available, $remaining);
                    $remaining = round($remaining - $pickedQty, 4);

                    $allocations[] = [
                        'source_type' => $candidate['type'],
                        'part_id' => $candidate['part_id'],
                        'part_no' => $candidate['part_no'],
                        'part_name' => $candidate['part_name'],
                        'location_code' => (string) $stock->location_code,
                        'batch_no' => (string) ($stock->batch_no ?? ''),
                        'qty_on_hand' => $available,
                        'request_qty' => $pickedQty,
                    ];

                    $traceability = $this->resolveIncomingTraceability(
                        $candidate['part_id'],
                        (string) $stock->location_code,
                        (string) ($stock->batch_no ?? '')
                    );

                    if (!empty($traceability)) {
                        $allocations[array_key_last($allocations)] = array_merge(
                            $allocations[array_key_last($allocations)],
                            $traceability
                        );
                    }
                }
            }

            $availableQty = collect($allocations)->sum('request_qty');
            $lines[] = [
                'component_gci_part_id' => (int) ($item->component_part_id ?? 0),
                'component_part_no' => (string) ($item->componentPart?->part_no ?? $item->component_part_no ?? '-'),
                'component_part_name' => (string) ($item->componentPart?->part_name ?? '-'),
                'uom' => (string) ($item->consumption_uom ?? $item->componentPart?->uom ?? 'PCS'),
                'make_or_buy' => $makeOrBuy,
                'is_backflush' => (bool) ($item->componentPart?->is_backflush ?? true),
                'required_qty' => $requiredQty,
                'available_qty' => $availableQty,
                'shortage_qty' => max(0, round($requiredQty - $availableQty, 4)),
                'allocations' => $allocations,
                'notes' => null,
            ];
        }

        return $lines;
    }

    public function syncToOrder(ProductionOrder $order, ?int $userId = null, bool $resetIssueState = false): array
    {
        $requestLines = $this->buildLines($order);
        if (empty($requestLines)) {
            return [];
        }

        $payload = [
            'material_request_lines' => $requestLines,
            'material_requested_at' => now(),
            'material_requested_by' => $userId,
        ];

        if ($resetIssueState) {
            $payload = array_merge($payload, [
                'material_issue_lines' => null,
                'material_handed_over_at' => null,
                'material_handed_over_by' => null,
                'material_handover_notes' => null,
            ]);
        }

        $order->update($payload);

        $this->syncReservedMaterialsFromRequestLines($order, $requestLines);
        $this->syncOrderStatusFromMaterialRequest($order, $requestLines);

        return $requestLines;
    }

    public function shortageCount(array $requestLines): int
    {
        return collect($requestLines)->where('shortage_qty', '>', 0)->count();
    }

    private function isWarehouseScannableRm(string $makeOrBuy): bool
    {
        return in_array(
            strtoupper(trim($makeOrBuy)),
            ['BUY', 'B', 'PURCHASE', 'FREE_ISSUE', 'FREE ISSUE', 'FI'],
            true
        );
    }

    private function releaseReservedMaterials(ProductionOrder $order): void
    {
        $reserved = collect($order->reserved_materials ?? []);
        if ($reserved->isEmpty()) {
            return;
        }

        foreach ($reserved as $mat) {
            $partId = (int) ($mat['gci_part_id'] ?? 0);
            $qty = (float) ($mat['qty'] ?? 0);
            if ($partId <= 0 || $qty <= 0) {
                continue;
            }

            $inventory = GciInventory::query()->where('gci_part_id', $partId)->first();
            if ($inventory) {
                $inventory->release($qty);
            }
        }

        $order->update(['reserved_materials' => null]);
    }

    private function syncReservedMaterialsFromRequestLines(ProductionOrder $order, array $requestLines): void
    {
        $this->releaseReservedMaterials($order);

        $hasShortage = collect($requestLines)->contains(fn($line) => (float) ($line['shortage_qty'] ?? 0) > 0);
        if ($hasShortage) {
            return;
        }

        $reservedMaterials = [];

        foreach ($requestLines as $line) {
            $makeOrBuy = strtoupper(trim((string) ($line['make_or_buy'] ?? 'BUY')));
            if (!in_array($makeOrBuy, ['BUY', 'B', 'PURCHASE'], true)) {
                continue;
            }

            $partId = (int) ($line['component_gci_part_id'] ?? 0);
            $requiredQty = round((float) ($line['required_qty'] ?? 0), 4);

            if ($partId <= 0 || $requiredQty <= 0) {
                continue;
            }

            $inventory = GciInventory::firstOrCreate(
                ['gci_part_id' => $partId],
                ['on_hand' => 0, 'on_order' => 0, 'as_of_date' => now()->toDateString()]
            );
            $inventory->reserve($requiredQty);

            $reservedMaterials[] = [
                'gci_part_id' => $partId,
                'part_no' => (string) ($line['component_part_no'] ?? '-'),
                'qty' => $requiredQty,
            ];
        }

        $order->update(['reserved_materials' => $reservedMaterials]);
    }

    private function syncOrderStatusFromMaterialRequest(ProductionOrder $order, array $requestLines): void
    {
        $shortageCount = $this->shortageCount($requestLines);

        if ($shortageCount > 0) {
            $order->update([
                'status' => 'material_hold',
                'workflow_stage' => 'material_check',
            ]);
            return;
        }

        $nextStatus = (!$order->process_name || !$order->machine_id) ? 'resource_hold' : 'released';
        $nextWorkflowStage = $nextStatus === 'resource_hold' ? 'resource_check' : 'material_ready';

        $order->update([
            'status' => $nextStatus,
            'workflow_stage' => $nextWorkflowStage,
        ]);
    }

    private function resolveIncomingTraceability(int $partId, string $locationCode, string $batchNo): array
    {
        $locationCode = strtoupper(trim($locationCode));
        $batchNo = strtoupper(trim($batchNo));

        if ($partId <= 0 || $locationCode === '' || $batchNo === '') {
            return [];
        }

        $receive = Receive::query()
            ->with('arrivalItem:id,arrival_id,part_id')
            ->where('tag', $batchNo)
            ->where('location_code', $locationCode)
            ->whereHas('arrivalItem', function ($query) use ($partId) {
                $query->where('part_id', $partId);
            })
            ->latest('id')
            ->first();

        if (!$receive) {
            return [];
        }

        return [
            'receive_id' => (int) $receive->id,
            'arrival_id' => (int) ($receive->arrivalItem->arrival_id ?? 0),
            'arrival_item_id' => (int) ($receive->arrival_item_id ?? 0),
            'received_qty' => (float) ($receive->qty_received ?? 0),
            'received_at' => optional($receive->received_at)->toDateTimeString(),
        ];
    }
}
