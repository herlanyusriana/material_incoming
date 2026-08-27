<?php

namespace App\Services;

use App\Models\Bom;
use App\Models\NewSchema\Incoming\IncomingReceive;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Production\ProductionOrder;
use App\Models\NewSchema\Production\ProductionOrderReservedMaterial;
use Illuminate\Support\Facades\Auth;

class ProductionMaterialRequestService
{
    private function cleanText(...$values): string
    {
        foreach ($values as $value) {
            $text = trim((string) ($value ?? ''));
            if ($text !== '' && $text !== '-') {
                return $text;
            }
        }

        return '';
    }

    private function normalizeConsumptionPolicy(?string $policy, ?bool $legacyBackflush = null): string
    {
        $policy = strtolower(trim((string) $policy));
        if (in_array($policy, ['direct_issue', 'backflush_return', 'backflush_line_stock'], true)) {
            return $policy;
        }

        if ($legacyBackflush === false) {
            return 'direct_issue';
        }

        return 'backflush_return';
    }

    private function resolveConsumptionPolicy($item): array
    {
        $override = $this->normalizeConsumptionPolicy($item->consumption_policy_override ?? null);
        if (!empty($item->consumption_policy_override)) {
            return [$override, 'bom_override'];
        }

        $component = $item->componentPart;
        if ($component && !empty($component->consumption_policy)) {
            return [
                $this->normalizeConsumptionPolicy($component->consumption_policy, $component->is_backflush ?? true),
                'master_part',
            ];
        }

        return [
            $this->normalizeConsumptionPolicy(null, $component?->is_backflush ?? true),
            'legacy_default',
        ];
    }

    public function buildLines(ProductionOrder $order): array
    {
        $order->loadMissing(['gciPart']);

        $bom = Bom::activeVersion($order->gci_part_id, $order->plan_date);
        if (!$bom) {
            return [];
        }

        $bomItems = $bom->items()
            ->with([
                'componentPart',
                'incomingPart.gciPart',
                'substitutes.gciPart',
                'substitutes.incomingPart.gciPart',
            ])
            ->get();

        $lines = [];

        foreach ($bomItems as $item) {
            $makeOrBuy = strtoupper(trim((string) ($item->make_or_buy ?? '')));
            if (!$this->isWarehouseScannableRm($makeOrBuy)) {
                continue;
            }

            [$consumptionPolicy, $policySource] = $this->resolveConsumptionPolicy($item);

            $requiredQty = round((float) ($item->net_required ?? $item->usage_qty ?? 0) * (float) $order->qty_planned, 4);
            if ($requiredQty <= 0) {
                continue;
            }

            $candidateParts = collect();
            if ($item->incomingPart) {
                $gciPartId = (int) ($item->incomingPart->gci_part_id
                    ?? $item->incomingPart->gciPart?->id
                    ?? $item->component_part_id
                    ?? 0);
                $partNo = $this->cleanText(
                    $item->incomingPart->vendor_part_no,
                    $item->incomingPart->gciPart?->part_no,
                    $item->componentPart?->part_no,
                    $item->component_part_no
                );
                $candidateParts->push([
                    'type' => 'primary',
                    'vendor_part_id' => (int) $item->incomingPart->id,
                    'gci_part_id' => $gciPartId,
                    'part_no' => $partNo,
                    'part_name' => $this->cleanText(
                        $item->incomingPart->vendor_part_name,
                        $item->incomingPart->gciPart?->part_name,
                        $item->componentPart?->part_name,
                        $partNo
                    ),
                ]);
            }

            foreach (($item->substitutes ?? collect()) as $substitute) {
                if (!$substitute->incomingPart && !$substitute->gciPart) {
                    continue;
                }

                $gciPartId = (int) ($substitute->incomingPart?->gci_part_id
                    ?? $substitute->incomingPart?->gciPart?->id
                    ?? $substitute->substitute_part_id
                    ?? $substitute->gciPart?->id
                    ?? 0);
                $partNo = $this->cleanText(
                    $substitute->incomingPart?->vendor_part_no,
                    $substitute->incomingPart?->gciPart?->part_no,
                    $substitute->gciPart?->part_no,
                    $substitute->substitute_part_no
                );
                $candidateParts->push([
                    'type' => 'substitute',
                    'vendor_part_id' => (int) ($substitute->incomingPart?->id ?? 0),
                    'gci_part_id' => $gciPartId,
                    'part_no' => $partNo,
                    'part_name' => $this->cleanText(
                        $substitute->incomingPart?->vendor_part_name,
                        $substitute->incomingPart?->gciPart?->part_name,
                        $substitute->gciPart?->part_name,
                        $partNo
                    ),
                ]);
            }

            $candidateParts = $candidateParts
                ->filter(fn($part) => !empty($part['gci_part_id']) || !empty($part['vendor_part_id']) || trim((string) ($part['part_no'] ?? '')) !== '')
                ->unique(fn($part) => ($part['vendor_part_id'] ?? 0) . '|' . ($part['gci_part_id'] ?? 0) . '|' . strtoupper((string) ($part['part_no'] ?? '')))
                ->values();
            $scanOptions = $candidateParts->map(fn($part) => [
                'source_type' => $part['type'],
                'vendor_part_id' => (int) ($part['vendor_part_id'] ?? 0),
                'gci_part_id' => (int) ($part['gci_part_id'] ?? 0),
                'part_no' => $this->cleanText($part['part_no'] ?? ''),
                'part_name' => $this->cleanText($part['part_name'] ?? '', $part['part_no'] ?? ''),
            ])->values()->all();

            if ($candidateParts->isEmpty()) {
                $lines[] = [
                    'component_gci_part_id' => (int) ($item->component_part_id ?? 0),
                    'component_part_no' => $this->cleanText($item->componentPart?->part_no, $item->component_part_no),
                    'component_part_name' => $this->cleanText($item->componentPart?->part_name, $item->material_name, $item->componentPart?->part_no, $item->component_part_no),
                    'uom' => (string) ($item->consumption_uom ?? $item->componentPart?->uom ?? 'PCS'),
                    'make_or_buy' => $makeOrBuy,
                    'consumption_policy' => $consumptionPolicy,
                    'policy_source' => $policySource,
                    'is_backflush' => $consumptionPolicy !== 'direct_issue',
                    'required_qty' => $requiredQty,
                    'available_qty' => 0,
                    'shortage_qty' => $requiredQty,
                    'allocations' => [],
                    'scan_options' => [],
                    'notes' => 'Incoming RM part belum dipetakan pada BOM.',
                ];
                continue;
            }

            $remaining = $requiredQty;
            $allocations = [];

            foreach ($candidateParts as $candidate) {
                if ($remaining <= 0 || empty($candidate['gci_part_id'])) {
                    break;
                }

                $stocks = InventoryLocationStock::query()
                    ->where('gci_part_id', $candidate['gci_part_id'])
                    ->where('qty_on_hand', '>', 0)
                    ->orderBy('production_date')
                    ->orderBy('created_at')
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

                    $allocation = [
                        'source_type' => $candidate['type'],
                        'vendor_part_id' => (int) ($candidate['vendor_part_id'] ?? 0),
                        'gci_part_id' => (int) $candidate['gci_part_id'],
                        'part_no' => $candidate['part_no'],
                        'part_name' => $candidate['part_name'],
                        'location_code' => (string) $stock->location_code,
                        'batch_no' => (string) ($stock->batch_no ?? ''),
                        'qty_on_hand' => $available,
                        'request_qty' => $pickedQty,
                    ];

                    $traceability = $this->resolveIncomingTraceability(
                        (int) $candidate['gci_part_id'],
                        (string) $stock->location_code,
                        (string) ($stock->batch_no ?? '')
                    );

                    $allocations[] = array_merge($allocation, $traceability);
                }
            }

            $availableQty = collect($allocations)->sum('request_qty');
            $lines[] = [
                'component_gci_part_id' => (int) ($item->component_part_id ?? 0),
                'component_part_no' => $this->cleanText($item->componentPart?->part_no, $item->component_part_no),
                'component_part_name' => $this->cleanText($item->componentPart?->part_name, $item->material_name, $item->componentPart?->part_no, $item->component_part_no),
                'uom' => (string) ($item->consumption_uom ?? $item->componentPart?->uom ?? 'PCS'),
                'make_or_buy' => $makeOrBuy,
                'consumption_policy' => $consumptionPolicy,
                'policy_source' => $policySource,
                'is_backflush' => $consumptionPolicy !== 'direct_issue',
                'required_qty' => $requiredQty,
                'available_qty' => $availableQty,
                'shortage_qty' => max(0, round($requiredQty - $availableQty, 4)),
                'allocations' => $allocations,
                'scan_options' => $scanOptions,
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
        ProductionOrderReservedMaterial::query()
            ->where('production_order_id', $order->id)
            ->whereNull('consumed_at')
            ->get()
            ->each(function (ProductionOrderReservedMaterial $reserved) {
                $reserved->consumed_at = now();
                $reserved->saveQuietly();
            });
    }

    private function syncReservedMaterialsFromRequestLines(ProductionOrder $order, array $requestLines): void
    {
        $this->releaseReservedMaterials($order);

        $hasShortage = collect($requestLines)->contains(fn($line) => (float) ($line['shortage_qty'] ?? 0) > 0);
        if ($hasShortage) {
            return;
        }

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

            ProductionOrderReservedMaterial::create([
                'production_order_id' => $order->id,
                'gci_part_id' => $partId,
                'qty_reserved' => $requiredQty,
                'qty_consumed' => 0,
                'reserved_at' => now(),
                'reserved_by' => Auth::check() ? Auth::id() : null,
            ]);
        }
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

    private function resolveIncomingTraceability(int $gciPartId, string $locationCode, string $batchNo): array
    {
        $locationCode = strtoupper(trim($locationCode));
        $batchNo = strtoupper(trim($batchNo));

        if ($gciPartId <= 0 || $locationCode === '' || $batchNo === '') {
            return [];
        }

        $receive = IncomingReceive::query()
            ->with('arrivalItem:id,arrival_id,gci_part_id')
            ->where('tag', $batchNo)
            ->where('location_code', $locationCode)
            ->whereHas('arrivalItem', function ($query) use ($gciPartId) {
                $query->where('gci_part_id', $gciPartId);
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
            'received_qty' => (float) ($receive->qty ?? 0),
            'received_at' => optional($receive->ata_date)->toDateTimeString(),
        ];
    }
}
