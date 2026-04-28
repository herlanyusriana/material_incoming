<?php

namespace App\Http\Controllers\Api;

use App\Events\Production\MonitoringUpdated;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\Machine;
use App\Models\ProductionOrder;
use App\Models\ProductionInspection;
use App\Models\ProductionOrderActivity;
use App\Models\ProductionGciWorkOrder;
use App\Models\ProductionGciHourlyReport;
use App\Models\ProductionGciDowntime;
use App\Models\ProductionGciMaterialLot;
use App\Models\Bom;
use App\Models\GciPart;
use App\Models\GciInventory;
use App\Models\LocationInventory;
use App\Services\ProductionInventoryFlowService;
use Illuminate\Support\Str;

class ProductionGciApiController extends Controller
{
    /**
     * Temporary management decision: production may start WO without waiting
     * for WH RM supply while operators are training / WH discipline is being fixed.
     * Set to false to restore the normal material gate.
     */
    private const TEMP_ALLOW_START_WITHOUT_WH_SUPPLY = true;

    private const CLOSED_EXECUTION_STAGES = [
        'final_inspection',
        'kanban_update',
        'warehouse_supply',
        'finished',
    ];

    private function inventoryFlowService(): ProductionInventoryFlowService
    {
        return app(ProductionInventoryFlowService::class);
    }

    private function bypassMaterialGateForWoStart(): bool
    {
        return self::TEMP_ALLOW_START_WITHOUT_WH_SUPPLY;
    }

    private function recordProductionActivity(ProductionOrder $order, string $type, array $data = []): void
    {
        try {
            ProductionOrderActivity::create([
                'production_order_id' => $order->id,
                'activity_type' => $type,
                'process_name' => $data['process_name'] ?? $order->process_name,
                'machine_id' => $data['machine_id'] ?? $order->machine_id,
                'machine_name' => $data['machine_name'] ?? ($order->machine?->name ?? null),
                'shift' => $data['shift'] ?? $order->shift,
                'operator_name' => $data['operator_name'] ?? null,
                'output_type' => $data['output_type'] ?? null,
                'output_part_no' => $data['output_part_no'] ?? null,
                'output_part_name' => $data['output_part_name'] ?? null,
                'qty_ok' => (float) ($data['qty_ok'] ?? 0),
                'qty_ng' => (float) ($data['qty_ng'] ?? 0),
                'notes' => $data['notes'] ?? null,
                'meta' => $data['meta'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record production activity', [
                'production_order_id' => $order->id,
                'activity_type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isRmBuyRequirement(array $req): bool
    {
        $makeOrBuy = strtoupper(trim((string) ($req['make_or_buy'] ?? 'BUY')));
        $classification = strtoupper(trim((string) ($req['part']?->classification ?? '')));

        return in_array($makeOrBuy, ['BUY', 'B', 'PURCHASE'], true)
            && $classification === 'RM';
    }

    private function findActivePauseDowntime(ProductionOrder $order): ?ProductionGciDowntime
    {
        return ProductionGciDowntime::query()
            ->where('machine_id', $order->machine_id)
            ->whereNull('end_time')
            ->latest('id')
            ->get()
            ->first(function (ProductionGciDowntime $downtime) use ($order) {
                $meta = json_decode((string) $downtime->notes, true);

                return is_array($meta)
                    && ($meta['type'] ?? null) === 'wo_pause'
                    && (int) ($meta['production_order_id'] ?? 0) === (int) $order->id;
            });
    }

    private function decodeDowntimeNotes(?string $notes): array
    {
        $decoded = json_decode((string) $notes, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function generateCloudOfflineId(): int
    {
        // Keep generated IDs safely inside signed INT range used by offline_id columns.
        return random_int(100000000, 2147483647);
    }

    private function formatDowntime(ProductionGciDowntime $downtime): array
    {
        $meta = $this->decodeDowntimeNotes($downtime->notes);

        return [
            'id' => (int) $downtime->id,
            'machine_id' => (int) ($downtime->machine_id ?? 0),
            'machine_name' => (string) ($downtime->machine_name ?? '-'),
            'shift' => (string) ($downtime->shift ?? '-'),
            'operator_name' => (string) ($downtime->operator_name ?? ''),
            'start_time' => (string) $downtime->start_time,
            'end_time' => $downtime->end_time ? (string) $downtime->end_time : null,
            'duration_minutes' => (int) ($downtime->duration_minutes ?? 0),
            'reason' => (string) ($downtime->reason ?? '-'),
            'notes' => is_array($meta) && array_key_exists('notes', $meta)
                ? (string) ($meta['notes'] ?? '')
                : (string) ($downtime->notes ?? ''),
            'refill_part_no' => $downtime->refill_part_no,
            'refill_part_name' => $downtime->refill_part_name,
            'refill_qty' => $downtime->refill_qty !== null ? (float) $downtime->refill_qty : null,
            'production_order_id' => (int) ($meta['production_order_id'] ?? 0) ?: null,
            'production_order_number' => $meta['production_order_number'] ?? null,
            'type' => $meta['type'] ?? 'downtime',
            'is_running' => $downtime->end_time === null,
        ];
    }

    private function resolveLegacyGciWorkOrder(ProductionOrder $order): ProductionGciWorkOrder
    {
        return ProductionGciWorkOrder::firstOrCreate(
            ['order_no' => (string) ($order->production_order_number ?? $order->transaction_no ?? ('PO-' . $order->id))],
            [
                'type_model' => (string) (optional($order->part)->model ?: optional($order->part)->part_no ?: 'UNKNOWN'),
                'tact_time' => 0,
                'target_uph' => 0,
                'date' => $order->plan_date ? Carbon::parse($order->plan_date)->toDateString() : now()->toDateString(),
                'shift' => (string) ($order->shift ?: '-'),
                'foreman' => '-',
                'operator_name' => '-',
                'offline_id' => $this->generateCloudOfflineId(),
            ]
        );
    }

    private function resolveMachineDowntimeWorkOrder(int $machineId, ?string $shift = null, ?string $operatorName = null): ProductionGciWorkOrder
    {
        $machine = Machine::find($machineId);
        $date = now()->toDateString();
        $orderNo = 'DT-' . ($machine?->code ?: ('M' . $machineId)) . '-' . str_replace('-', '', $date);

        return ProductionGciWorkOrder::firstOrCreate(
            ['order_no' => $orderNo],
            [
                'type_model' => (string) ($machine?->name ?? 'Machine Downtime'),
                'tact_time' => 0,
                'target_uph' => 0,
                'date' => $date,
                'shift' => (string) ($shift ?: '-'),
                'foreman' => '-',
                'operator_name' => (string) ($operatorName ?: '-'),
                'offline_id' => $this->generateCloudOfflineId(),
            ]
        );
    }

    private function normalizeMonitoringDates(array $dates): array
    {
        return collect($dates)
            ->filter()
            ->map(function ($date) {
                if ($date instanceof Carbon) {
                    return $date->toDateString();
                }

                if ($date instanceof \DateTimeInterface) {
                    return Carbon::instance($date)->toDateString();
                }

                try {
                    return Carbon::parse((string) $date)->toDateString();
                } catch (\Throwable) {
                    return null;
                }
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function broadcastMonitoringUpdate(
        string $type,
        ?ProductionOrder $order = null,
        array $dates = [],
        array $machineIds = [],
        array $orderIds = [],
        array $meta = []
    ): void {
        if ($order) {
            $dates[] = $order->plan_date;
            $machineIds[] = (int) $order->machine_id;
            $orderIds[] = (int) $order->id;
        }

        $dates = $this->normalizeMonitoringDates($dates);
        if (empty($dates)) {
            $dates = [now()->toDateString()];
        }

        try {
            event(new MonitoringUpdated(
                type: $type,
                dates: $dates,
                machine_ids: $machineIds,
                order_ids: $orderIds,
                meta: $meta,
            ));
        } catch (\Throwable $e) {
            Log::warning('Production monitoring broadcast failed', [
                'type' => $type,
                'order_id' => $order?->id,
                'machine_ids' => $machineIds,
                'dates' => $dates,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizeIssuedTags(ProductionOrder $order): array
    {
        $issueLines = collect($order->material_issue_lines ?? []);

        return $issueLines
            ->flatMap(function ($line) {
                return collect($line['allocations'] ?? [])->map(function ($allocation) use ($line) {
                    $tag = (string) ($allocation['source_tag'] ?? $allocation['batch_no'] ?? '');

                    return [
                        'component_part_no' => (string) ($line['component_part_no'] ?? '-'),
                        'component_part_name' => (string) ($line['component_part_name'] ?? '-'),
                        'consumption_policy' => (string) ($line['consumption_policy'] ?? (($line['is_backflush'] ?? true) ? 'backflush_return' : 'direct_issue')),
                        'location_code' => (string) ($allocation['location_code'] ?? '-'),
                        'batch_no' => (string) ($allocation['batch_no'] ?? ''),
                        'source_tag' => $tag,
                        'source_invoice_no' => (string) ($allocation['source_invoice_no'] ?? ''),
                        'source_arrival_id' => $allocation['source_arrival_id'] ?? null,
                        'source_receive_id' => $allocation['source_receive_id'] ?? null,
                        'issued_qty' => (float) ($allocation['issued_qty'] ?? 0),
                        'uom' => (string) ($line['uom'] ?? '-'),
                    ];
                });
            })
            ->values()
            ->all();
    }

    private function buildMaterialStatus(ProductionOrder $order): array
    {
        $requestLines = collect($order->material_request_lines ?? []);
        $issueLines = collect($order->material_issue_lines ?? []);
        $requiresIssue = $requestLines->isNotEmpty();
        $shortageCount = $requestLines->filter(fn ($line) => (float) ($line['shortage_qty'] ?? 0) > 0)->count();
        $issuePosted = !is_null($order->material_issued_at);
        $handoverDone = !is_null($order->material_handed_over_at);
        $issuedTags = $this->normalizeIssuedTags($order);

        $materialReadyWithoutBypass = !$requiresIssue
            ? true
            : ($shortageCount === 0 && $issuePosted && $handoverDone && $issueLines->isNotEmpty());
        $materialReady = $materialReadyWithoutBypass || $this->bypassMaterialGateForWoStart();

        return [
            'requires_issue' => $requiresIssue,
            'request_line_count' => $requestLines->count(),
            'shortage_count' => $shortageCount,
            'issue_posted' => $issuePosted,
            'issued_at' => $order->material_issued_at ? $order->material_issued_at->toDateTimeString() : null,
            'handover_done' => $handoverDone,
            'handed_over_at' => $order->material_handed_over_at ? $order->material_handed_over_at->toDateTimeString() : null,
            'material_ready' => $materialReady,
            'material_ready_without_bypass' => $materialReadyWithoutBypass,
            'material_bypass_active' => $this->bypassMaterialGateForWoStart(),
            'issued_tag_count' => count($issuedTags),
            'issued_tags' => $issuedTags,
            'start_block_reason' => $materialReady
                ? ($materialReadyWithoutBypass ? null : 'Bypass sementara aktif: WO boleh start tanpa menunggu WH supply RM.')
                : (!$requiresIssue
                    ? null
                    : ($shortageCount > 0
                        ? 'Material request masih shortage.'
                        : (!$issuePosted
                            ? 'Supply material dari WH ke produksi belum diposting.'
                            : (!$handoverDone
                                ? 'Material sudah disupply dari WH, tapi penerimaan line produksi belum dicatat.'
                                : 'Status supply material belum lengkap.')))),
        ];
    }

    private function materialKeys(?int $gciPartId, ?int $partId, ?string $partNo): array
    {
        $keys = [];
        $normalizedPartNo = strtoupper(trim((string) $partNo));

        if ((int) $gciPartId > 0) {
            $keys[] = 'gci:' . (int) $gciPartId;
        }

        if ((int) $partId > 0) {
            $keys[] = 'incoming:' . (int) $partId;
        }

        if ($normalizedPartNo !== '') {
            $keys[] = 'part:' . $normalizedPartNo;
        }

        return array_values(array_unique($keys));
    }

    private function requestLineKeys(array $line): array
    {
        $keys = $this->materialKeys(
            (int) ($line['component_gci_part_id'] ?? 0),
            null,
            (string) ($line['component_part_no'] ?? '')
        );

        foreach (($line['allocations'] ?? []) as $allocation) {
            $keys = array_merge($keys, $this->materialKeys(
                null,
                (int) ($allocation['part_id'] ?? 0),
                (string) ($allocation['part_no'] ?? '')
            ));
        }

        return array_values(array_unique($keys));
    }

    private function backflushIssuedMaterialsForOutput(ProductionOrder $order, float $outputQty): array
    {
        if ($this->bypassMaterialGateForWoStart()) {
            return [];
        }

        if ($outputQty <= 0) {
            return [];
        }

        $order->refresh();
        $plannedQty = (float) ($order->qty_planned ?? 0);
        $requestLines = collect($order->material_request_lines ?? [])
            ->filter(fn (array $line) => (bool) ($line['is_backflush'] ?? true));

        if ($plannedQty <= 0 || $requestLines->isEmpty()) {
            return [];
        }

        $issueLines = array_values($order->material_issue_lines ?? []);
        $events = [];
        $sourceReference = 'PROD#' . ($order->production_order_number ?: $order->id);
        $inventoryFlowService = $this->inventoryFlowService();

        foreach ($requestLines as $requestLine) {
            $requiredQty = (float) ($requestLine['required_qty'] ?? 0);
            if ($requiredQty <= 0) {
                continue;
            }

            $usagePerUnit = $requiredQty / $plannedQty;
            $needed = round($usagePerUnit * $outputQty, 4);
            if ($needed <= 0) {
                continue;
            }

            $requestKeys = $this->requestLineKeys($requestLine);

            foreach ($issueLines as $index => $issueLine) {
                if ($needed <= 0) {
                    break;
                }

                if (!($issueLine['is_backflush'] ?? true)) {
                    continue;
                }

                $issueKeys = $this->materialKeys(
                    (int) ($issueLine['gci_part_id'] ?? 0),
                    (int) ($issueLine['part_id'] ?? 0),
                    (string) ($issueLine['part_no'] ?? '')
                );

                if (empty(array_intersect($requestKeys, $issueKeys))) {
                    continue;
                }

                $issuedQty = (float) ($issueLine['qty'] ?? 0);
                $alreadyBackflushed = (float) ($issueLine['backflushed_qty'] ?? 0);
                $availableToBackflush = max(0, round($issuedQty - $alreadyBackflushed, 4));
                if ($availableToBackflush <= 0) {
                    continue;
                }

                $consumeQty = min($availableToBackflush, $needed);
                $tagNo = (string) ($issueLine['tag_number'] ?? '');
                $locationCode = (string) ($issueLine['location_code'] ?? '');
                $partId = (int) ($issueLine['part_id'] ?? 0);
                $gciPartId = (int) ($issueLine['gci_part_id'] ?? 0);
                $traceability = is_array($issueLine['traceability'] ?? null) ? $issueLine['traceability'] : [];

                if ($consumeQty > 0 && $tagNo !== '' && $locationCode !== '' && ($partId > 0 || $gciPartId > 0)) {
                    LocationInventory::consumeStock(
                        $partId > 0 ? $partId : null,
                        $locationCode,
                        $consumeQty,
                        $tagNo,
                        $gciPartId > 0 ? $gciPartId : null,
                        'PRODUCTION_BACKFLUSH',
                        $sourceReference,
                        array_merge(['source_tag' => $tagNo], $traceability)
                    );
                }

                if ($consumeQty > 0 && $gciPartId > 0) {
                    $inventory = GciInventory::firstOrCreate(
                        ['gci_part_id' => $gciPartId],
                        ['on_hand' => 0, 'on_order' => 0, 'as_of_date' => now()->toDateString()]
                    );
                    $inventory->consume($consumeQty);
                }

                $issueLines[$index]['backflushed_qty'] = round($alreadyBackflushed + $consumeQty, 4);
                $issueLines[$index]['backflushed_at'] = now()->toDateTimeString();
                $issueLines[$index]['backflush_events'] = array_values(array_merge(
                    $issueLine['backflush_events'] ?? [],
                    [[
                        'qty' => $consumeQty,
                        'output_qty' => $outputQty,
                        'posted_at' => now()->toDateTimeString(),
                    ]]
                ));

                $needed = round($needed - $consumeQty, 4);
                $supply = $inventoryFlowService->recordBackflushConsumption($order, $issueLines[$index], $consumeQty);
                if ($supply) {
                    $issueLines[$index]['inventory_supply_id'] = (int) $supply->id;
                    $issueLines[$index]['consumed_qty'] = (float) $supply->qty_consumed;
                    $issueLines[$index]['returned_qty'] = (float) $supply->qty_returned;
                    $issueLines[$index]['supply_status'] = (string) $supply->status;
                }
                $events[] = [
                    'part_no' => (string) ($issueLine['part_no'] ?? $requestLine['component_part_no'] ?? '-'),
                    'tag_number' => $tagNo,
                    'qty' => $consumeQty,
                    'remaining_need' => $needed,
                ];
            }

            if ($needed > 0) {
                throw new \RuntimeException("Material backflush {$requestLine['component_part_no']} kurang {$needed}. Scan/supply material tambahan dulu.");
            }
        }

        $order->update(['material_issue_lines' => $issueLines]);

        return $events;
    }

    private function buildMaterialIssueHistory(ProductionOrder $order): array
    {
        $requestLines = collect($order->material_request_lines ?? []);
        $issueLines = collect($order->material_issue_lines ?? []);

        return $issueLines->map(function ($issueLine) use ($requestLines) {
            $componentPartNo = (string) ($issueLine['component_part_no'] ?? '-');
            $requestLine = $requestLines->first(function ($line) use ($issueLine, $componentPartNo) {
                $samePartId = (int) ($line['component_gci_part_id'] ?? 0) > 0
                    && (int) ($line['component_gci_part_id'] ?? 0) === (int) ($issueLine['component_gci_part_id'] ?? 0);

                return $samePartId || (string) ($line['component_part_no'] ?? '-') === $componentPartNo;
            });

            $allocations = collect($issueLine['allocations'] ?? [])->map(function ($allocation) {
                $sourceTag = (string) ($allocation['source_tag'] ?? $allocation['batch_no'] ?? '');

                return [
                    'part_no' => (string) ($allocation['part_no'] ?? '-'),
                    'part_name' => (string) ($allocation['part_name'] ?? '-'),
                    'location_code' => (string) ($allocation['location_code'] ?? '-'),
                    'batch_no' => (string) ($allocation['batch_no'] ?? ''),
                    'source_tag' => $sourceTag,
                    'source_invoice_no' => (string) ($allocation['source_invoice_no'] ?? ''),
                    'source_delivery_note_no' => (string) ($allocation['source_delivery_note_no'] ?? ''),
                    'source_receive_id' => $allocation['source_receive_id'] ?? null,
                    'source_arrival_id' => $allocation['source_arrival_id'] ?? null,
                    'qty_on_hand' => (float) ($allocation['qty_on_hand'] ?? 0),
                    'request_qty' => (float) ($allocation['request_qty'] ?? 0),
                    'issued_qty' => (float) ($allocation['issued_qty'] ?? 0),
                    'source_type' => (string) ($allocation['source_type'] ?? 'primary'),
                ];
            })->values()->all();

            return [
                'component_gci_part_id' => (int) ($issueLine['component_gci_part_id'] ?? 0),
                'component_part_no' => $componentPartNo,
                'component_part_name' => (string) ($issueLine['component_part_name'] ?? '-'),
                'consumption_policy' => (string) ($issueLine['consumption_policy'] ?? ($requestLine['consumption_policy'] ?? (($issueLine['is_backflush'] ?? true) ? 'backflush_return' : 'direct_issue'))),
                'uom' => (string) ($issueLine['uom'] ?? '-'),
                'required_qty' => (float) ($requestLine['required_qty'] ?? $issueLine['required_qty'] ?? 0),
                'available_qty' => (float) ($requestLine['available_qty'] ?? 0),
                'shortage_qty' => (float) ($requestLine['shortage_qty'] ?? 0),
                'issued_qty' => (float) ($issueLine['issued_qty'] ?? 0),
                'backflushed_qty' => (float) ($issueLine['backflushed_qty'] ?? 0),
                'allocations_count' => count($allocations),
                'allocations' => $allocations,
            ];
        })->values()->all();
    }

    private function resolveMonitoringStatus(ProductionOrder $order): string
    {
        if (
            $order->kanban_updated_at
            || in_array((string) $order->workflow_stage, self::CLOSED_EXECUTION_STAGES, true)
            || ($order->end_time && (float) ($order->qty_actual ?? 0) > 0)
        ) {
            return 'completed';
        }

        if ($order->status !== 'material_hold') {
            return (string) $order->status;
        }

        $requestLines = collect($order->material_request_lines ?? []);
        if ($requestLines->isNotEmpty()) {
            $hasShortage = $requestLines->contains(function ($line) {
                return (float) ($line['shortage_qty'] ?? 0) > 0;
            });

            if ($hasShortage) {
                return 'material_hold';
            }

            return (!$order->process_name || !$order->machine_id) ? 'resource_hold' : 'released';
        }

        $bom = Bom::activeVersion($order->gci_part_id, $order->plan_date);
        if (!$bom) {
            return 'material_hold';
        }

        $requirements = $bom->getTotalMaterialRequirements($order->qty_planned);
        if (empty($requirements)) {
            return (!$order->process_name || !$order->machine_id) ? 'resource_hold' : 'released';
        }

        foreach ($requirements as $req) {
            if (!$this->isRmBuyRequirement($req)) {
                continue;
            }

            $part = $req['part'] ?? null;
            $needed = round((float) ($req['total_qty'] ?? 0), 4);
            $onHand = (float) optional(GciInventory::query()->where('gci_part_id', $part?->id)->first())->on_hand;

            if ($onHand < $needed) {
                return 'material_hold';
            }
        }

        return (!$order->process_name || !$order->machine_id) ? 'resource_hold' : 'released';
    }

    public function sync(Request $request)
    {
        $data = $request->validate([
            'work_orders' => 'array',
            'hourly_reports' => 'array',
            'downtimes' => 'array',
            'material_lots' => 'array',
        ]);

        DB::beginTransaction();
        try {
            // Track mapping of offline SQLite ID to online Postgres/MySQL ID
            $woMap = [];
            $affectedDates = [];
            $affectedMachineIds = [];
            $affectedOrderIds = [];

            if (!empty($data['work_orders'])) {
                foreach ($data['work_orders'] as $woParams) {
                    $wo = ProductionGciWorkOrder::updateOrCreate(
                        ['offline_id' => $woParams['id']],
                        [
                            'order_no' => $woParams['orderNo'],
                            'type_model' => $woParams['typeModel'],
                            'tact_time' => $woParams['tactTime'],
                            'target_uph' => $woParams['targetUph'],
                            'date' => $woParams['date'],
                            'shift' => $woParams['shift'],
                            'foreman' => $woParams['foreman'],
                            'operator_name' => $woParams['operatorName']
                        ]
                    );
                    $woMap[$woParams['id']] = $wo->id;
                }
            }

            if (!empty($data['hourly_reports'])) {
                foreach ($data['hourly_reports'] as $hrParams) {
                    // New format: direct production_order_id from Android app
                    if (isset($hrParams['productionOrderId'])) {
                        $existingReport = ProductionGciHourlyReport::query()
                            ->where('offline_id', $hrParams['id'])
                            ->where('production_order_id', $hrParams['productionOrderId'])
                            ->first();
                        $previousActual = (float) ($existingReport?->actual ?? 0);
                        $outputType = $this->normalizeHourlyOutputType($hrParams['outputType'] ?? null);

                        $report = ProductionGciHourlyReport::updateOrCreate(
                            [
                                'offline_id' => $hrParams['id'],
                                'production_order_id' => $hrParams['productionOrderId'],
                            ],
                            [
                                'time_range' => $hrParams['timeRange'],
                                'target' => $hrParams['target'],
                                'actual' => $hrParams['actual'],
                                'ng' => $hrParams['ng'],
                                'ng_reason' => $hrParams['ngReason'] ?? null,
                                'ng_scrap' => $this->normalizeNgBreakdown($hrParams)['ng_scrap'],
                                'ng_rework' => $this->normalizeNgBreakdown($hrParams)['ng_rework'],
                                'ng_hold' => $this->normalizeNgBreakdown($hrParams)['ng_hold'],
                                'operator_name' => $hrParams['operatorName'] ?? null,
                                'shift' => $hrParams['shift'] ?? null,
                                'machine_id' => $hrParams['machineId'] ?? null,
                                'machine_name' => $hrParams['machineName'] ?? null,
                                'output_type' => $outputType,
                                'process_name' => $hrParams['processName'] ?? null,
                                'output_part_no' => $hrParams['outputPartNo'] ?? null,
                                'output_part_name' => $hrParams['outputPartName'] ?? null,
                            ]
                        );

                        // Update production order actual totals
                        $po = ProductionOrder::find($hrParams['productionOrderId']);
                        if ($po) {
                            $totalActual = ProductionGciHourlyReport::where('production_order_id', $po->id)
                                ->where(function ($query) {
                                    $query->where('output_type', 'fg')
                                        ->orWhereNull('output_type');
                                })
                                ->sum('actual');
                            $totalNg = ProductionGciHourlyReport::where('production_order_id', $po->id)
                                ->where(function ($query) {
                                    $query->where('output_type', 'fg')
                                        ->orWhereNull('output_type');
                                })
                                ->sum('ng');
                            $po->update([
                                'qty_actual' => $totalActual,
                                'qty_ng' => $totalNg,
                                'machine_id' => $hrParams['machineId'] ?? $po->machine_id,
                                'process_name' => $hrParams['processName'] ?? $po->process_name,
                            ]);
                            $affectedDates[] = $po->plan_date;
                            $affectedMachineIds[] = (int) $po->machine_id;
                            $affectedOrderIds[] = (int) $po->id;

                            $wipDelta = (float) ($hrParams['actual'] ?? 0) - $previousActual;
                            if ($outputType === 'wip' && $wipDelta > 0) {
                                $this->recordWipInventoryOutput(
                                    $po,
                                    [
                                        'output_type' => 'wip',
                                        'process_name' => $hrParams['processName'] ?? $po->process_name,
                                        'output_part_no' => $hrParams['outputPartNo'] ?? null,
                                        'output_part_name' => $hrParams['outputPartName'] ?? null,
                                    ],
                                    $wipDelta,
                                    $report
                                );
                            }
                        }
                        continue;
                    }

                    // Legacy format: work-order-based hourly reports
                    $woId = $woMap[$hrParams['workOrderId']] ?? ProductionGciWorkOrder::where('offline_id', $hrParams['workOrderId'])->value('id');

                    if ($woId) {
                        ProductionGciHourlyReport::updateOrCreate(
                            ['offline_id' => $hrParams['id']],
                            [
                                'production_gci_work_order_id' => $woId,
                                'time_range' => $hrParams['timeRange'],
                                'target' => $hrParams['target'],
                                'actual' => $hrParams['actual'],
                                'ng' => $hrParams['ng'],
                                'ng_reason' => $hrParams['ngReason'] ?? null,
                                'ng_scrap' => $this->normalizeNgBreakdown($hrParams)['ng_scrap'],
                                'ng_rework' => $this->normalizeNgBreakdown($hrParams)['ng_rework'],
                                'ng_hold' => $this->normalizeNgBreakdown($hrParams)['ng_hold'],
                            ]
                        );
                    }
                }
            }

            if (!empty($data['downtimes'])) {
                foreach ($data['downtimes'] as $dtParams) {
                    // New format: machine-based downtimes (from Flutter downtime-only app)
                    if (isset($dtParams['machineId'])) {
                        ProductionGciDowntime::updateOrCreate(
                            [
                                'offline_id' => $dtParams['id'],
                                'machine_id' => $dtParams['machineId'],
                            ],
                            [
                                'production_gci_work_order_id' => null,
                                'machine_name' => $dtParams['machineName'] ?? null,
                                'shift' => $dtParams['shift'] ?? null,
                                'start_time' => $dtParams['startTime'],
                                'end_time' => $dtParams['endTime'],
                                'duration_minutes' => $dtParams['durationMinutes'],
                                'reason' => $dtParams['reason'],
                                'operator_name' => $dtParams['operatorName'] ?? null,
                                'notes' => $dtParams['notes'] ?? null,
                                'refill_part_no' => $dtParams['refillPartNo'] ?? null,
                                'refill_part_name' => $dtParams['refillPartName'] ?? null,
                                'refill_qty' => $dtParams['refillQty'] ?? null,
                            ]
                        );
                        continue;
                    }

                    // Legacy format: work-order-based downtimes
                    $woId = $woMap[$dtParams['workOrderId']] ?? ProductionGciWorkOrder::where('offline_id', $dtParams['workOrderId'])->value('id');

                    if ($woId) {
                        ProductionGciDowntime::updateOrCreate(
                            ['offline_id' => $dtParams['id']],
                            [
                                'production_gci_work_order_id' => $woId,
                                'start_time' => $dtParams['startTime'],
                                'end_time' => $dtParams['endTime'],
                                'duration_minutes' => $dtParams['durationMinutes'],
                                'reason' => $dtParams['reason'],
                                'notes' => $dtParams['notes'] ?? null,
                            ]
                        );
                    }
                }
            }

            if (!empty($data['material_lots'])) {
                foreach ($data['material_lots'] as $mlParams) {
                    $woId = $woMap[$mlParams['workOrderId']] ?? ProductionGciWorkOrder::where('offline_id', $mlParams['workOrderId'])->value('id');

                    if ($woId) {
                        ProductionGciMaterialLot::updateOrCreate(
                            ['offline_id' => $mlParams['id']],
                            [
                                'production_gci_work_order_id' => $woId,
                                'invoice_or_tag' => $mlParams['invoiceOrTag'],
                                'qty' => $mlParams['qty'],
                                'actual' => $mlParams['actual'],
                            ]
                        );
                    }
                }
            }

            DB::commit();

            if (!empty($affectedOrderIds) || !empty($data['downtimes']) || !empty($data['hourly_reports'])) {
                $this->broadcastMonitoringUpdate(
                    type: 'sync',
                    dates: $affectedDates,
                    machineIds: $affectedMachineIds,
                    orderIds: $affectedOrderIds,
                    meta: [
                        'hourly_reports' => count($data['hourly_reports'] ?? []),
                        'downtimes' => count($data['downtimes'] ?? []),
                    ],
                );
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function machines()
    {
        $machines = Machine::active()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'group_name', 'cycle_time', 'cycle_time_unit']);

        return response()->json(['data' => $machines]);
    }

    public function parts(Request $request)
    {
        $search = $request->query('search', '');
        $classification = $request->query('classification', 'RM');

        $query = \App\Models\GciPart::where('status', 'active')
            ->where('classification', $classification);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('part_no', 'like', "%{$search}%")
                  ->orWhere('part_name', 'like', "%{$search}%");
            });
        }

        $parts = $query->orderBy('part_no')
            ->limit(50)
            ->get(['id', 'part_no', 'part_name', 'size', 'model']);

        return response()->json(['data' => $parts]);
    }

    public function workOrders(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        $blockedStatuses = $this->bypassMaterialGateForWoStart()
            ? ['cancelled', 'completed']
            : ['material_hold', 'resource_hold', 'cancelled', 'completed'];

        $query = ProductionOrder::with(['part:id,part_no,part_name,model', 'machine:id,name,code'])
            ->whereDate('plan_date', $date)
            ->whereNotIn('workflow_stage', self::CLOSED_EXECUTION_STAGES)
            ->whereNotIn('status', $blockedStatuses);

        if ($request->has('machine_id')) {
            $query->where('machine_id', $request->query('machine_id'));
        }

        if ($request->has('shift')) {
            $query->where('shift', $request->query('shift'));
        }

        $query->orderBy('plan_date', 'asc')
            ->orderBy('production_sequence', 'asc');

        $orders = $query->get()->map(function (ProductionOrder $o) {
            $materialStatus = $this->buildMaterialStatus($o);

            return [
                'id' => (int) $o->id,
                'wo_number' => (string) ($o->production_order_number ?? $o->transaction_no ?? '-'),
                'transaction_no' => (string) $o->transaction_no,
                'plan_date' => $o->plan_date ? Carbon::parse($o->plan_date)->toDateString() : null,
                'part_no' => (string) ($o->part?->part_no ?? '-'),
                'part_name' => (string) ($o->part?->part_name ?? '-'),
                'model' => (string) ($o->part?->model ?? '-'),
                'qty_planned' => (float) $o->qty_planned,
                'qty_actual' => (float) ($o->qty_actual ?? 0),
                'qty_ng' => (float) ($o->qty_ng ?? 0),
                'status' => (string) $o->status,
                'workflow_stage' => (string) $o->workflow_stage,
                'process_name' => (string) ($o->process_name ?? ''),
                'machine_id' => $o->machine_id ? (int) $o->machine_id : null,
                'machine_name' => (string) ($o->machine?->name ?? ($o->machine_name ?? '')),
                'last_handover_from_process' => (string) ($o->last_handover_from_process ?? ''),
                'last_handover_from_machine_name' => (string) ($o->last_handover_from_machine_name ?? ''),
                'last_handover_at' => $o->last_handover_at ? (string) $o->last_handover_at : null,
                'shift' => (string) $o->shift,
                'production_sequence' => $o->production_sequence !== null ? (int) $o->production_sequence : null,
                'start_time' => $o->start_time ? (string) $o->start_time : null,
                'end_time' => $o->end_time ? (string) $o->end_time : null,
                'material_status' => $materialStatus,
                'can_start' => $this->bypassMaterialGateForWoStart()
                    ? in_array((string) $o->status, ['released', 'kanban_released', 'material_hold', 'resource_hold'], true)
                    : ($materialStatus['material_ready']
                        && in_array((string) $o->status, ['released', 'kanban_released'], true)),
            ];
        });

        return response()->json(['data' => $orders]);
    }

    public function materialStatus($id)
    {
        $order = ProductionOrder::with('part:id,part_no,part_name,model')->findOrFail($id);
        $inventoryFlow = $this->inventoryFlowService()->summarizeOrderFlow($order);

        return response()->json([
            'data' => [
                'id' => (int) $order->id,
                'wo_number' => (string) ($order->production_order_number ?? $order->transaction_no ?? '-'),
                'status' => (string) $order->status,
                'workflow_stage' => (string) $order->workflow_stage,
                'part_no' => (string) ($order->part?->part_no ?? '-'),
                'part_name' => (string) ($order->part?->part_name ?? '-'),
                'process_name' => (string) ($order->process_name ?? ''),
                'last_handover_from_process' => (string) ($order->last_handover_from_process ?? ''),
                'last_handover_from_machine_name' => (string) ($order->last_handover_from_machine_name ?? ''),
                'last_handover_at' => $order->last_handover_at ? (string) $order->last_handover_at : null,
                'material_status' => $this->buildMaterialStatus($order),
                'inventory_flow' => $inventoryFlow,
            ],
        ]);
    }

    public function materialIssueHistory($id)
    {
        $order = ProductionOrder::with('part:id,part_no,part_name,model')->findOrFail($id);
        $materialStatus = $this->buildMaterialStatus($order);
        $issueHistory = $this->buildMaterialIssueHistory($order);
        $inventoryFlow = $this->inventoryFlowService()->summarizeOrderFlow($order);

        return response()->json([
            'data' => [
                'id' => (int) $order->id,
                'wo_number' => (string) ($order->production_order_number ?? $order->transaction_no ?? '-'),
                'status' => (string) $order->status,
                'workflow_stage' => (string) $order->workflow_stage,
                'part_no' => (string) ($order->part?->part_no ?? '-'),
                'part_name' => (string) ($order->part?->part_name ?? '-'),
                'material_status' => $materialStatus,
                'issue_summary' => [
                    'issued_at' => $materialStatus['issued_at'],
                    'handed_over_at' => $materialStatus['handed_over_at'],
                    'issue_posted' => $materialStatus['issue_posted'],
                    'handover_done' => $materialStatus['handover_done'],
                    'request_line_count' => $materialStatus['request_line_count'],
                    'issued_tag_count' => $materialStatus['issued_tag_count'],
                ],
                'issue_lines' => $issueHistory,
                'inventory_flow' => $inventoryFlow,
            ],
        ]);
    }

    private function buildRoutingStepsForOrder(ProductionOrder $order): array
    {
        $bom = Bom::activeVersion($order->gci_part_id, $order->plan_date);
        if (!$bom) {
            return [];
        }

        $bom->loadMissing('items.machine', 'items.wipPart', 'items.componentPart', 'part');

        $rawSteps = $bom->items
            ->sortBy('line_no')
            ->values()
            ->map(function ($item, $index) use ($order) {
                $processName = trim((string) ($item->process_name ?? ''));
                $wipPartNo = trim((string) ($item->wipPart?->part_no ?? $item->wip_part_no ?? ''));
                $wipPartName = trim((string) ($item->wipPart?->part_name ?? $item->wip_part_name ?? ''));
                $outputPartNo = $wipPartNo !== '' ? $wipPartNo : (string) ($order->part?->part_no ?? '-');
                $outputPartName = $wipPartName !== '' ? $wipPartName : (string) ($order->part?->part_name ?? '-');
                $isFinal = $wipPartNo === '';
                $isCurrent = $processName !== '' && strcasecmp($processName, (string) $order->process_name) === 0;

                $recommendedMachineId = $item->machine?->id ? (int) $item->machine->id : null;
                $recommendedMachineName = (string) ($item->machine?->name ?? '');

                return [
                    'step_no' => $index + 1,
                    'line_no' => (int) ($item->line_no ?? ($index + 1)),
                    'process_name' => $processName !== '' ? $processName : 'Process',
                    'machine_id' => $recommendedMachineId,
                    'machine_name' => $recommendedMachineName,
                    'recommended_machine_id' => $recommendedMachineId,
                    'recommended_machine_name' => $recommendedMachineName,
                    'input_part_no' => (string) ($item->componentPart?->part_no ?? $item->component_part_no ?? '-'),
                    'input_part_name' => (string) ($item->componentPart?->part_name ?? ''),
                    'output_part_no' => $outputPartNo,
                    'output_part_name' => $outputPartName,
                    'output_type' => $isFinal ? 'fg' : 'wip',
                    'is_final' => $isFinal,
                    'is_current' => $isCurrent,
                ];
            })
            ->all();

        $mergedSteps = [];
        foreach ($rawSteps as $step) {
            $lastIndex = count($mergedSteps) - 1;
            
            // Check if it's the same machine (and machine is not null)
            $isSameMachine = $lastIndex >= 0 
                && $mergedSteps[$lastIndex]['machine_id'] !== null 
                && $mergedSteps[$lastIndex]['machine_id'] === $step['machine_id'];

            if ($isSameMachine) {
                // Merge the current step into the last one
                $mergedSteps[$lastIndex]['process_name'] .= ' + ' . $step['process_name'];
                $mergedSteps[$lastIndex]['output_part_no'] = $step['output_part_no'];
                $mergedSteps[$lastIndex]['output_part_name'] = $step['output_part_name'];
                $mergedSteps[$lastIndex]['output_type'] = $step['output_type'];
                $mergedSteps[$lastIndex]['is_final'] = $step['is_final'];
                $mergedSteps[$lastIndex]['is_current'] = $mergedSteps[$lastIndex]['is_current'] || $step['is_current'];
            } else {
                $mergedSteps[] = $step;
            }
        }

        // Re-assign step numbers sequentially after merge
        $steps = [];
        foreach ($mergedSteps as $index => $mStep) {
            $mStep['step_no'] = $index + 1;
            $steps[] = $mStep;
        }

        foreach ($steps as $index => &$step) {
            $previousStep = $steps[$index - 1] ?? null;
            $previousOutputType = (string) ($previousStep['output_type'] ?? '');
            $previousPartNo = strtoupper(trim((string) ($previousStep['output_part_no'] ?? '')));
            $outputPartNo = strtoupper(trim((string) ($step['output_part_no'] ?? '')));

            $step['input_available_qty'] = $previousOutputType === 'wip' && $previousPartNo !== ''
                ? $this->availableQtyForPartNo($previousPartNo)
                : null;
            $step['output_available_qty'] = $outputPartNo !== '' && $outputPartNo !== '-'
                ? $this->availableQtyForPartNo($outputPartNo)
                : 0;
            $step['input_from_part_no'] = $previousOutputType === 'wip' ? ($previousStep['output_part_no'] ?? null) : null;
            $step['input_from_part_name'] = $previousOutputType === 'wip' ? ($previousStep['output_part_name'] ?? null) : null;
            $step['next_process_name'] = $steps[$index + 1]['process_name'] ?? null;
            $step['next_machine_name'] = $steps[$index + 1]['recommended_machine_name']
                ?? ($steps[$index + 1]['machine_name'] ?? null);
        }
        unset($step);

        return $steps;
    }

    private function availableQtyForPartNo(?string $partNo): float
    {
        $normalized = strtoupper(trim((string) $partNo));
        if ($normalized === '' || $normalized === '-') {
            return 0;
        }

        $part = GciPart::query()->where('part_no', $normalized)->first();
        if (!$part) {
            return 0;
        }

        return (float) LocationInventory::query()
            ->where('gci_part_id', $part->id)
            ->sum('qty_on_hand');
    }

    private function findNextRoutingStep(ProductionOrder $order, ?string $currentProcessName): ?array
    {
        $steps = $this->buildRoutingStepsForOrder($order);
        if (empty($steps)) {
            return null;
        }

        $currentIndex = null;
        foreach ($steps as $index => $step) {
            if ($currentProcessName && strcasecmp((string) $step['process_name'], $currentProcessName) === 0) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex === null) {
            $currentIndex = 0;
        }

        return $steps[$currentIndex + 1] ?? null;
    }

    public function workOrderRouting($id)
    {
        $order = ProductionOrder::with('part:id,part_no,part_name,model')->findOrFail($id);
        $steps = $this->buildRoutingStepsForOrder($order);

        return response()->json([
            'data' => [
                'wo_id' => (int) $order->id,
                'wo_number' => (string) ($order->production_order_number ?? $order->transaction_no ?? '-'),
                'current_process_name' => (string) ($order->process_name ?? ''),
                'steps' => $steps,
            ],
        ]);
    }

    /**
     * Start a WO from Android app (operator starts production)
     */
    public function startWo(Request $request, $id)
    {
        $order = ProductionOrder::findOrFail($id);
        $validated = $request->validate([
            'machine_id' => 'nullable|integer|exists:machines,id',
            'actual_machine_id' => 'nullable|integer|exists:machines,id',
            'machine_name' => 'nullable|string|max:255',
            'process_name' => 'nullable|string|max:255',
            'operator_name' => 'nullable|string|max:255',
            'shift' => 'nullable|string|max:50',
            'start_source' => 'nullable|string|in:rm,wip',
            'source_wip_part_no' => 'nullable|string|max:255',
            'source_wip_part_name' => 'nullable|string|max:255',
            'source_wip_process_name' => 'nullable|string|max:255',
        ]);

        $actualMachineId = (int) ($validated['actual_machine_id'] ?? $validated['machine_id'] ?? $order->machine_id ?? 0);
        if ($actualMachineId <= 0) {
            return response()->json([
                'message' => 'Pilih mesin aktual terlebih dahulu sebelum start WO.'
            ], 422);
        }

        $actualMachine = Machine::find($actualMachineId);

        if (!$this->bypassMaterialGateForWoStart()) {
            $isMachineBusy = ProductionOrder::where('machine_id', $actualMachineId)
                ->whereIn('status', ['in_production', 'paused'])
                ->where('id', '!=', $order->id)
                ->exists();

            if ($isMachineBusy) {
                return response()->json([
                    'message' => 'Pekerjaan ditolak. Masih ada Work Order lain yang sedang aktif (Running/Paused) pada mesin ini.'
                ], 422);
            }
        }

        // Block PLANNED status
        if ($order->status === 'planned') {
            return response()->json([
                'message' => 'WO masih dalam status PLANNED. Silakan hubungi admin untuk melakukan RELEASE WO terlebih dahulu.'
            ], 422);
        }

        $materialStatus = $this->buildMaterialStatus($order);
        if (!$this->bypassMaterialGateForWoStart() && !$materialStatus['material_ready']) {
            return response()->json([
                'message' => $materialStatus['start_block_reason'] ?? 'Material untuk WO ini belum siap.',
                'material_status' => $materialStatus,
            ], 422);
        }

        // Allow starting from kanban_released or released status
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json(['message' => 'WO sudah selesai atau dibatalkan'], 422);
        }

        $processName = trim((string) ($validated['process_name'] ?? $order->process_name ?? ''));
        if ($processName === '') {
            $firstStep = $this->buildRoutingStepsForOrder($order)[0] ?? null;
            $processName = (string) ($firstStep['process_name'] ?? '');
        }

        $startSource = strtolower(trim((string) ($validated['start_source'] ?? 'rm')));
        if (!in_array($startSource, ['rm', 'wip'], true)) {
            $startSource = 'rm';
        }

        $startSourceMeta = [
            'start_source' => $startSource,
            'source_wip_part_no' => $validated['source_wip_part_no'] ?? null,
            'source_wip_part_name' => $validated['source_wip_part_name'] ?? null,
            'source_wip_process_name' => $validated['source_wip_process_name'] ?? null,
        ];
        $normalizedOrderShift = $this->normalizeShiftNumber($validated['shift'] ?? null);

        $updatePayload = [
            'status' => 'in_production',
            'workflow_stage' => 'mass_production',
            'start_time' => $order->start_time ?? now(),
            'machine_id' => $actualMachineId,
            'process_name' => $processName !== '' ? $processName : $order->process_name,
        ];

        if (Schema::hasColumn('production_orders', 'machine_name')) {
            $updatePayload['machine_name'] = $actualMachine?->name ?? ($validated['machine_name'] ?? null);
        }

        if ($normalizedOrderShift !== null) {
            $updatePayload['shift'] = $normalizedOrderShift;
        }

        if ($order->status === 'in_production') {
            $order->update($updatePayload);
            $this->recordProductionActivity($order->fresh(), 'activity_switched', [
                'process_name' => $processName,
                'machine_id' => $actualMachineId,
                'machine_name' => $actualMachine?->name ?? ($validated['machine_name'] ?? null),
                'shift' => $validated['shift'] ?? $order->shift,
                'operator_name' => $validated['operator_name'] ?? null,
                'meta' => array_merge(['source' => 'apk_start_while_running'], $startSourceMeta),
            ]);

            $this->broadcastMonitoringUpdate('wo_activity_switched', $order, meta: [
                'status' => 'in_production',
                'workflow_stage' => 'mass_production',
                'machine_id' => $actualMachineId,
                'machine_name' => $actualMachine?->name ?? ($validated['machine_name'] ?? null),
                'process_name' => $processName,
                ...$startSourceMeta,
            ]);

            return response()->json([
                'message' => 'Aktivitas WO diperbarui',
                'status' => 'success',
                'data' => $order->fresh(),
            ], 200);
        }

        $order->update($updatePayload);
        $this->recordProductionActivity($order->fresh(), 'started', [
            'process_name' => $processName,
            'machine_id' => $actualMachineId,
            'machine_name' => $actualMachine?->name ?? ($validated['machine_name'] ?? null),
            'shift' => $validated['shift'] ?? $order->shift,
            'operator_name' => $validated['operator_name'] ?? null,
            'meta' => array_merge(['source' => 'apk_start'], $startSourceMeta),
        ]);

        $this->broadcastMonitoringUpdate('wo_started', $order, meta: [
            'status' => 'in_production',
            'workflow_stage' => 'mass_production',
            'machine_id' => $actualMachineId,
            'machine_name' => $actualMachine?->name ?? ($validated['machine_name'] ?? null),
            'process_name' => $processName,
            ...$startSourceMeta,
        ]);

        return response()->json(['status' => 'success', 'data' => $order->fresh()]);
    }

    private function normalizeShiftNumber($rawShift): ?int
    {
        if ($rawShift === null) {
            return null;
        }

        $text = trim((string) $rawShift);
        if ($text === '') {
            return null;
        }

        if (preg_match('/\d+/', $text, $matches)) {
            $value = (int) $matches[0];
            return $value > 0 ? $value : null;
        }

        return null;
    }

    /**
     * Pause a WO from Android app
     */
    public function pauseWo(Request $request, $id)
    {
        $order = ProductionOrder::findOrFail($id);

        if ($order->status === 'paused') {
            return response()->json(['message' => 'WO sudah dalam status pause', 'data' => $order], 200);
        }

        if ($order->status !== 'in_production') {
            return response()->json(['message' => 'WO harus running untuk di-pause'], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'operator_name' => 'nullable|string|max:255',
            'shift' => 'nullable|string|max:50',
        ]);

        $pausedAt = now();

        $order->update([
            'status' => 'paused',
            'workflow_stage' => 'mass_production',
        ]);

        ProductionGciDowntime::create([
            'machine_id' => $order->machine_id,
            'machine_name' => optional($order->machine)->name,
            'shift' => $validated['shift'] ?? $order->shift,
            'operator_name' => $validated['operator_name'] ?? null,
            'start_time' => $pausedAt->toDateTimeString(),
            'end_time' => null,
            'duration_minutes' => 0,
            'reason' => $validated['reason'],
            'notes' => json_encode([
                'type' => 'wo_pause',
                'production_order_id' => $order->id,
                'production_order_number' => $order->production_order_number,
                'notes' => $validated['notes'] ?? '',
            ]),
        ]);
        $this->recordProductionActivity($order->fresh(), 'paused', [
            'shift' => $validated['shift'] ?? $order->shift,
            'operator_name' => $validated['operator_name'] ?? null,
            'notes' => $validated['reason'],
            'meta' => [
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? '',
            ],
        ]);

        $this->broadcastMonitoringUpdate('wo_paused', $order, meta: [
            'reason' => $validated['reason'],
            'status' => 'paused',
        ]);

        return response()->json(['status' => 'success', 'data' => $order->fresh()]);
    }

    /**
     * Resume a paused WO from Android app
     */
    public function resumeWo(Request $request, $id)
    {
        $order = ProductionOrder::findOrFail($id);

        if ($order->status === 'in_production') {
            return response()->json(['message' => 'WO sudah running', 'data' => $order], 200);
        }

        if ($order->status !== 'paused') {
            return response()->json(['message' => 'WO harus pause dulu untuk di-resume'], 422);
        }

        $resumedAt = now();

        if ($activePause = $this->findActivePauseDowntime($order)) {
            $startedAt = strtotime((string) $activePause->start_time);
            $duration = $startedAt ? max(0, (int) ceil(($resumedAt->timestamp - $startedAt) / 60)) : 0;

            $activePause->update([
                'end_time' => $resumedAt->toDateTimeString(),
                'duration_minutes' => $duration,
            ]);
        }

        $order->update([
            'status' => 'in_production',
            'workflow_stage' => 'mass_production',
        ]);
        $this->recordProductionActivity($order->fresh(), 'resumed', [
            'meta' => ['source' => 'apk_resume'],
        ]);

        $this->broadcastMonitoringUpdate('wo_resumed', $order, meta: [
            'status' => 'in_production',
        ]);

        return response()->json(['status' => 'success', 'data' => $order->fresh()]);
    }

    /**
     * Finish a WO from Android app
     */
    public function finishWo(Request $request, $id)
    {
        $order = ProductionOrder::findOrFail($id);

        if (!in_array((string) $order->status, ['in_production', 'paused'], true)) {
            return response()->json(['message' => 'WO belum dimulai'], 422);
        }

        $validated = $request->validate([
            'qty_actual' => 'nullable|numeric|min:0',
            'qty_ng' => 'nullable|numeric|min:0',
        ]);

        if ($order->status === 'paused' && $activePause = $this->findActivePauseDowntime($order)) {
            $finishedAt = now();
            $startedAt = strtotime((string) $activePause->start_time);
            $duration = $startedAt ? max(0, (int) ceil(($finishedAt->timestamp - $startedAt) / 60)) : 0;

            $activePause->update([
                'end_time' => $finishedAt->toDateTimeString(),
                'duration_minutes' => $duration,
            ]);
        }

        // Priority: request body > hourly sum > existing order value
        $hourlyActual = (float) ProductionGciHourlyReport::where('production_order_id', $id)
            ->where(function ($query) {
                $query->where('output_type', 'fg')
                    ->orWhereNull('output_type');
            })
            ->sum('actual');
        $hourlyNg = (float) ProductionGciHourlyReport::where('production_order_id', $id)
            ->where(function ($query) {
                $query->where('output_type', 'fg')
                    ->orWhereNull('output_type');
            })
            ->sum('ng');

        $finalActual = isset($validated['qty_actual'])
            ? (float) $validated['qty_actual']
            : ($hourlyActual > 0 ? $hourlyActual : (float) ($order->qty_actual ?? 0));

        $finalNg = isset($validated['qty_ng'])
            ? (float) $validated['qty_ng']
            : ($hourlyNg > 0 ? $hourlyNg : (float) ($order->qty_ng ?? 0));

        // Auto-backflush only when this WO has no previous hourly backflush.
        // Hourly FG output is the primary trigger so material is consumed incrementally.
        $backflushEvents = [];
        if (($finalActual + $finalNg) > 0) {
            $hasExistingBackflush = collect($order->material_issue_lines ?? [])
                ->contains(fn (array $line) => (float) ($line['backflushed_qty'] ?? 0) > 0);

            if (!$hasExistingBackflush) {
                try {
                    $backflushEvents = $this->backflushIssuedMaterialsForOutput($order, $finalActual + $finalNg);
                } catch (\Throwable $e) {
                    Log::warning('Production backflush failed on finish WO', [
                        'production_order_id' => $order->id,
                        'output_qty' => $finalActual + $finalNg,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'message' => $e->getMessage(),
                    ], 422);
                }
            }

            // Create or add to FG inventory
            $fgInventory = GciInventory::where('gci_part_id', $order->gci_part_id)->orderByDesc('id')->first();
            if ($fgInventory) {
                $fgInventory->on_hand += $finalActual;
                $fgInventory->save();
            }
        }

        $order->update([
            'status' => 'finished',
            'workflow_stage' => 'final_inspection',
            'end_time' => now(),
            'qty_actual' => $finalActual,
            'qty_ng' => $finalNg,
        ]);
        $this->recordProductionActivity($order->fresh(), 'finished', [
            'qty_ok' => $finalActual,
            'qty_ng' => $finalNg,
            'output_type' => 'fg',
            'output_part_no' => (string) ($order->part?->part_no ?? ''),
            'output_part_name' => (string) ($order->part?->part_name ?? ''),
            'meta' => ['source' => 'apk_finish'],
        ]);

        // Create final inspection if not exists
        if (!$order->inspections()->where('type', 'final')->exists()) {
            ProductionInspection::create([
                'production_order_id' => $order->id,
                'type' => 'final',
                'status' => 'pending',
            ]);
        }

        $this->broadcastMonitoringUpdate('wo_finished', $order, meta: [
            'status' => 'finished',
            'workflow_stage' => 'final_inspection',
            'qty_actual' => $finalActual,
            'qty_ng' => $finalNg,
        ]);

        return response()->json(['status' => 'success', 'data' => $order->fresh()]);
    }

    /**
     * Get hourly reports for a specific WO
     */
    public function getHourlyReports($id)
    {
        $reports = ProductionGciHourlyReport::where('production_order_id', $id)
            ->orderBy('time_range')
            ->orderBy('output_type')
            ->orderBy('process_name')
            ->get()
            ->map(fn($r) => [
                'time_range' => $r->time_range,
                'target' => $r->target,
                'actual' => $r->actual,
                'ng' => $r->ng,
                'ng_reason' => $r->ng_reason,
                'ng_scrap' => (int) ($r->ng_scrap ?? 0),
                'ng_rework' => (int) ($r->ng_rework ?? 0),
                'ng_hold' => (int) ($r->ng_hold ?? 0),
                'operator_name' => $r->operator_name,
                'shift' => $r->shift,
                'machine_id' => $r->machine_id ? (int) $r->machine_id : null,
                'machine_name' => $r->machine_name,
                'output_type' => $r->output_type ?: 'fg',
                'process_name' => $r->process_name,
                'output_part_no' => $r->output_part_no,
                'output_part_name' => $r->output_part_name,
            ]);

        return response()->json(['data' => $reports]);
    }

    private function normalizeHourlyOutputType(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['wip', 'fg'], true) ? $normalized : 'fg';
    }

    private function wipLocationCode(?string $processName): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', trim((string) $processName)));
        $normalized = trim($normalized, '-');

        return $normalized !== ''
            ? 'WIP-' . substr($normalized, 0, 36)
            : 'WIP-PROD';
    }

    private function recordWipInventoryOutput(ProductionOrder $order, array $processContext, float $qty, ?ProductionGciHourlyReport $report = null): ?array
    {
        if (($processContext['output_type'] ?? 'fg') !== 'wip' || $qty <= 0) {
            return null;
        }

        $outputPartNo = strtoupper(trim((string) ($processContext['output_part_no'] ?? '')));
        if ($outputPartNo === '' || $outputPartNo === '-') {
            Log::warning('WIP inventory output skipped: output part is empty', [
                'production_order_id' => $order->id,
                'process_name' => $processContext['process_name'] ?? null,
            ]);

            return [
                'status' => 'skipped',
                'reason' => 'output_part_empty',
            ];
        }

        $wipPart = GciPart::firstOrCreate(
            ['part_no' => $outputPartNo],
            [
                'part_name' => $processContext['output_part_name'] ?: $outputPartNo,
                'classification' => 'WIP',
                'status' => 'active',
            ]
        );

        if (strtoupper((string) ($wipPart->classification ?? '')) !== 'WIP') {
            $wipPart->update(['classification' => 'WIP']);
        }

        $locationCode = $this->wipLocationCode($processContext['process_name'] ?? null);
        $sourceReference = sprintf(
            'WO:%s|HR:%s|PROC:%s',
            $order->production_order_number ?? $order->transaction_no ?? $order->id,
            $report?->id ?? '-',
            $processContext['process_name'] ?? '-'
        );

        LocationInventory::updateStock(
            null,
            $locationCode,
            $qty,
            null,
            now()->toDateString(),
            (int) $wipPart->id,
            'WIP_OUTPUT',
            $sourceReference,
            [
                'production_order_id' => $order->id,
                'production_order_number' => $order->production_order_number,
                'process_name' => $processContext['process_name'] ?? null,
                'hourly_report_id' => $report?->id,
            ]
        );

        return [
            'status' => 'posted',
            'gci_part_id' => (int) $wipPart->id,
            'part_no' => (string) $wipPart->part_no,
            'location_code' => $locationCode,
            'qty' => $qty,
        ];
    }

    private function consumePreviousWipInventoryInput(ProductionOrder $order, array $processContext, float $qty, ?ProductionGciHourlyReport $report = null): ?array
    {
        if ($qty <= 0) {
            return null;
        }

        $processName = trim((string) ($processContext['process_name'] ?? ''));
        if ($processName === '') {
            return null;
        }

        $steps = $this->buildRoutingStepsForOrder($order);
        $currentIndex = null;
        foreach ($steps as $index => $step) {
            if (strcasecmp((string) ($step['process_name'] ?? ''), $processName) === 0) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex === null || $currentIndex <= 0) {
            return null;
        }

        $previousStep = $steps[$currentIndex - 1] ?? null;
        if (!$previousStep || ($previousStep['output_type'] ?? '') !== 'wip') {
            return null;
        }

        $previousPartNo = strtoupper(trim((string) ($previousStep['output_part_no'] ?? '')));
        if ($previousPartNo === '' || $previousPartNo === '-') {
            return null;
        }

        $previousPart = GciPart::where('part_no', $previousPartNo)->first();
        if (!$previousPart) {
            return [
                'status' => 'skipped',
                'reason' => 'previous_wip_part_not_found',
                'part_no' => $previousPartNo,
            ];
        }

        $sourceReference = sprintf(
            'WO:%s|HR:%s|PROC:%s',
            $order->production_order_number ?? $order->transaction_no ?? $order->id,
            $report?->id ?? '-',
            $processName
        );

        try {
            LocationInventory::consumeStock(
                null,
                'WIP-BYPASS',
                $qty,
                null,
                (int) $previousPart->id,
                'WIP_CONSUME',
                $sourceReference,
                [
                    'production_order_id' => $order->id,
                    'production_order_number' => $order->production_order_number,
                    'from_wip_part_no' => $previousPartNo,
                    'process_name' => $processName,
                    'hourly_report_id' => $report?->id,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Previous WIP consume skipped', [
                'production_order_id' => $order->id,
                'process_name' => $processName,
                'part_no' => $previousPartNo,
                'qty' => $qty,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'skipped',
                'reason' => 'not_enough_previous_wip',
                'part_no' => $previousPartNo,
                'qty' => $qty,
                'message' => $e->getMessage(),
            ];
        }

        return [
            'status' => 'consumed',
            'gci_part_id' => (int) $previousPart->id,
            'part_no' => $previousPartNo,
            'qty' => $qty,
        ];
    }

    private function normalizeNgBreakdown(array $params): array
    {
        $totalNg = max(0, (int) ($params['ng'] ?? 0));
        $scrap = max(0, (int) ($params['ng_scrap'] ?? $params['ngScrap'] ?? 0));
        $rework = max(0, (int) ($params['ng_rework'] ?? $params['ngRework'] ?? 0));
        $hold = max(0, (int) ($params['ng_hold'] ?? $params['ngHold'] ?? 0));
        $classified = $scrap + $rework + $hold;

        if ($classified === 0 && $totalNg > 0) {
            // APK lama hanya kirim total NG. Simpan sebagai QC hold dulu agar tidak otomatis dianggap scrap.
            $hold = $totalNg;
            $classified = $totalNg;
        }

        if ($classified > $totalNg) {
            throw new \InvalidArgumentException('Total NG kategori tidak boleh lebih besar dari total NG.');
        }

        if ($classified < $totalNg) {
            $hold += $totalNg - $classified;
        }

        return [
            'ng_scrap' => $scrap,
            'ng_rework' => $rework,
            'ng_hold' => $hold,
        ];
    }

    private function resolveHourlyProcessContext(ProductionOrder $order, array $validated): array
    {
        $outputType = $this->normalizeHourlyOutputType($validated['output_type'] ?? null);
        $processName = trim((string) ($validated['process_name'] ?? $order->process_name ?? ''));
        $defaultPartNo = trim((string) ($order->part?->part_no ?? '-'));
        $defaultPartName = trim((string) ($order->part?->part_name ?? '-'));

        $outputPartNo = trim((string) ($validated['output_part_no'] ?? ''));
        $outputPartName = trim((string) ($validated['output_part_name'] ?? ''));

        if ($outputType === 'wip') {
            $bom = Bom::activeVersion($order->gci_part_id, $order->plan_date);
            $bomItem = null;

            if ($bom) {
                $bom->loadMissing('items.wipPart');
                $bomItem = $bom->items->first(function ($item) use ($processName) {
                    return strcasecmp(trim((string) $item->process_name), $processName) === 0;
                });
            }

            $outputPartNo = $outputPartNo !== ''
                ? $outputPartNo
                : trim((string) ($bomItem?->wipPart?->part_no ?? $bomItem?->wip_part_no ?? ''));
            $outputPartName = $outputPartName !== ''
                ? $outputPartName
                : trim((string) ($bomItem?->wipPart?->part_name ?? $bomItem?->wip_part_name ?? ''));
        }

        if ($outputPartNo === '') {
            $outputPartNo = $defaultPartNo;
        }

        if ($outputPartName === '') {
            $outputPartName = $outputType === 'wip'
                ? ($processName !== '' ? 'WIP ' . $processName : 'WIP Process')
                : $defaultPartName;
        }

        return [
            'output_type' => $outputType,
            'process_name' => $processName !== '' ? $processName : null,
            'output_part_no' => $outputPartNo,
            'output_part_name' => $outputPartName,
        ];
    }

    public function saveHourlyReport(Request $request, $id)
    {
        $order = ProductionOrder::findOrFail($id);
        $validated = $request->validate([
            'time_range' => 'required|string|max:50',
            'target' => 'nullable|integer|min:0',
            'actual' => 'required|integer|min:0',
            'ng' => 'required|integer|min:0',
            'ng_reason' => 'nullable|string|max:255',
            'ng_scrap' => 'nullable|integer|min:0',
            'ng_rework' => 'nullable|integer|min:0',
            'ng_hold' => 'nullable|integer|min:0',
            'operator_name' => 'nullable|string|max:255',
            'shift' => 'nullable|string|max:50',
            'machine_id' => 'nullable|integer|exists:machines,id',
            'actual_machine_id' => 'nullable|integer|exists:machines,id',
            'machine_name' => 'nullable|string|max:255',
            'output_type' => 'nullable|string|in:fg,wip',
            'process_name' => 'nullable|string|max:255',
            'output_part_no' => 'nullable|string|max:255',
            'output_part_name' => 'nullable|string|max:255',
        ]);

        $actualMachineId = (int) ($validated['actual_machine_id'] ?? $validated['machine_id'] ?? $order->machine_id ?? 0);
        if ($actualMachineId <= 0) {
            return response()->json([
                'message' => 'Pilih mesin aktual terlebih dahulu sebelum catat hourly.'
            ], 422);
        }

        $actualMachine = Machine::find($actualMachineId);
        $actualMachineName = $actualMachine?->name ?? ($validated['machine_name'] ?? null);
        $processContext = $this->resolveHourlyProcessContext($order, $validated);
        try {
            $ngBreakdown = $this->normalizeNgBreakdown($request->all());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $report = ProductionGciHourlyReport::query()
            ->where('production_order_id', $order->id)
            ->where('time_range', $validated['time_range'])
            ->where('machine_id', $actualMachineId)
            ->where(function ($query) use ($processContext) {
                if ($processContext['output_type'] === 'fg') {
                    $query->where('output_type', 'fg')
                        ->orWhereNull('output_type');
                    return;
                }

                $query->where('output_type', $processContext['output_type']);
            })
            ->where(function ($query) use ($processContext) {
                if ($processContext['process_name'] === null) {
                    $query->whereNull('process_name');
                    return;
                }

                $query->where('process_name', $processContext['process_name']);
            })
            ->first();

        // 110% limit only guards FG accumulation; WIP is tracked separately.
        $totalCurrentActual = (float) ProductionGciHourlyReport::query()
            ->where('production_order_id', $order->id)
            ->where(function ($query) {
                $query->where('output_type', 'fg')
                    ->orWhereNull('output_type');
            })
            ->sum('actual');
        $totalCurrentNg = (float) ProductionGciHourlyReport::query()
            ->where('production_order_id', $order->id)
            ->where(function ($query) {
                $query->where('output_type', 'fg')
                    ->orWhereNull('output_type');
            })
            ->sum('ng');

        $incrementActual = (float) $validated['actual'];
        $incrementNg = (float) $validated['ng'];
        $newTotalActual = $totalCurrentActual + ($processContext['output_type'] === 'fg' ? $incrementActual : 0);
        $newTotalNg = $totalCurrentNg + ($processContext['output_type'] === 'fg' ? $incrementNg : 0);

        $maxAllowed = ceil((float)$order->qty_planned * 1.1);

        if ($processContext['output_type'] === 'fg' && ($newTotalActual + $newTotalNg) > $maxAllowed) {
            return response()->json([
                'message' => "Akumulasi total (" . $newTotalActual . " OK + " . $newTotalNg . " NG) melampaui toleransi 110% dari target plan (" . $maxAllowed . "). Harap periksa kembali input Anda!"
            ], 422);
        }

        if ($report) {
            $report->fill([
                'target' => $validated['target'] ?? (int) $report->target,
                'actual' => (int) $report->actual + (int) $validated['actual'],
                'ng' => (int) $report->ng + (int) $validated['ng'],
                'ng_reason' => $validated['ng_reason'] ?? $report->ng_reason,
                'ng_scrap' => (int) ($report->ng_scrap ?? 0) + $ngBreakdown['ng_scrap'],
                'ng_rework' => (int) ($report->ng_rework ?? 0) + $ngBreakdown['ng_rework'],
                'ng_hold' => (int) ($report->ng_hold ?? 0) + $ngBreakdown['ng_hold'],
                'operator_name' => $validated['operator_name'] ?? $report->operator_name,
                'shift' => $validated['shift'] ?? $report->shift,
                'machine_id' => $actualMachineId,
                'machine_name' => $actualMachineName ?? $report->machine_name,
                'output_type' => $processContext['output_type'],
                'process_name' => $processContext['process_name'],
                'output_part_no' => $processContext['output_part_no'],
                'output_part_name' => $processContext['output_part_name'],
            ])->save();
        } else {
            $legacyWorkOrder = $this->resolveLegacyGciWorkOrder($order);
            $report = ProductionGciHourlyReport::create([
                'production_gci_work_order_id' => $legacyWorkOrder->id,
                'production_order_id' => $order->id,
                'machine_id' => $actualMachineId,
                'machine_name' => $actualMachineName,
                'time_range' => $validated['time_range'],
                'target' => $validated['target'] ?? 0,
                'actual' => $validated['actual'],
                'ng' => $validated['ng'],
                'ng_reason' => $validated['ng_reason'] ?? null,
                'ng_scrap' => $ngBreakdown['ng_scrap'],
                'ng_rework' => $ngBreakdown['ng_rework'],
                'ng_hold' => $ngBreakdown['ng_hold'],
                'offline_id' => $this->generateCloudOfflineId(),
                'operator_name' => $validated['operator_name'] ?? null,
                'shift' => $validated['shift'] ?? null,
                'output_type' => $processContext['output_type'],
                'process_name' => $processContext['process_name'],
                'output_part_no' => $processContext['output_part_no'],
                'output_part_name' => $processContext['output_part_name'],
            ]);
        }

        $totalActual = (float) ProductionGciHourlyReport::query()
            ->where('production_order_id', $order->id)
            ->where(function ($query) {
                $query->where('output_type', 'fg')
                    ->orWhereNull('output_type');
            })
            ->sum('actual');
        $totalNg = (float) ProductionGciHourlyReport::query()
            ->where('production_order_id', $order->id)
            ->where(function ($query) {
                $query->where('output_type', 'fg')
                    ->orWhereNull('output_type');
            })
            ->sum('ng');

        $orderUpdatePayload = [
            'qty_actual' => $totalActual,
            'qty_ng' => $totalNg,
            'machine_id' => $actualMachineId,
            'process_name' => $processContext['process_name'] ?? $order->process_name,
        ];

        if (Schema::hasColumn('production_orders', 'machine_name')) {
            $orderUpdatePayload['machine_name'] = $actualMachineName;
        }

        $order->update($orderUpdatePayload);

        $backflushEvents = [];
        if ($processContext['output_type'] === 'fg' && ($incrementActual + $incrementNg) > 0) {
            try {
                $backflushEvents = $this->backflushIssuedMaterialsForOutput($order, $incrementActual + $incrementNg);
            } catch (\Throwable $e) {
                Log::warning('Production backflush failed on hourly save', [
                    'production_order_id' => $order->id,
                    'output_qty' => $incrementActual + $incrementNg,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }
        }

        $previousWipConsumeEvent = null;
        $processedQty = $incrementActual + $incrementNg;
        if ($processedQty > 0) {
            $previousWipConsumeEvent = $this->consumePreviousWipInventoryInput(
                $order,
                $processContext,
                $processedQty,
                $report
            );
        }

        $wipInventoryEvent = null;
        if ($processContext['output_type'] === 'wip' && $incrementActual > 0) {
            try {
                $wipInventoryEvent = $this->recordWipInventoryOutput(
                    $order,
                    $processContext,
                    $incrementActual,
                    $report
                );
            } catch (\Throwable $e) {
                Log::warning('WIP inventory output failed on hourly save', [
                    'production_order_id' => $order->id,
                    'output_qty' => $incrementActual,
                    'output_part_no' => $processContext['output_part_no'] ?? null,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }
        }

        $this->recordProductionActivity($order->fresh(), 'hourly_saved', [
            'process_name' => $processContext['process_name'],
            'machine_id' => $report->machine_id,
            'machine_name' => $report->machine_name,
            'shift' => $report->shift,
            'operator_name' => $report->operator_name,
            'output_type' => $report->output_type ?: 'fg',
            'output_part_no' => $report->output_part_no,
            'output_part_name' => $report->output_part_name,
            'qty_ok' => $incrementActual,
            'qty_ng' => $incrementNg,
            'notes' => $report->ng_reason,
            'meta' => [
                'time_range' => $report->time_range,
                'ng_scrap' => (int) ($report->ng_scrap ?? 0),
                'ng_rework' => (int) ($report->ng_rework ?? 0),
                'ng_hold' => (int) ($report->ng_hold ?? 0),
                'backflush_events' => $backflushEvents,
                'wip_inventory_event' => $wipInventoryEvent,
                'previous_wip_consume_event' => $previousWipConsumeEvent,
            ],
        ]);

        $handoverMeta = null;
        if ($processContext['output_type'] === 'wip' && $incrementActual > 0) {
            $nextStep = $this->findNextRoutingStep($order->fresh(), $processContext['process_name']);
            if ($nextStep) {
                $currentMachineId = $actualMachineId;

                $order->update([
                    'process_name' => $nextStep['process_name'] ?? $order->process_name,
                    'machine_id' => $actualMachineId,
                    'status' => 'in_production',
                    'workflow_stage' => 'mass_production',
                    'last_handover_from_process' => $processContext['process_name'],
                    'last_handover_from_machine_id' => $currentMachineId > 0 ? $currentMachineId : null,
                    'last_handover_from_machine_name' => $actualMachineName,
                    'last_handover_at' => now(),
                ]);

                $handoverMeta = [
                    'from_process_name' => $processContext['process_name'],
                    'to_process_name' => $nextStep['process_name'] ?? null,
                    'from_machine_id' => $currentMachineId > 0 ? $currentMachineId : null,
                    'to_machine_id' => null,
                    'to_machine_name' => $nextStep['recommended_machine_name'] ?? ($nextStep['machine_name'] ?? null),
                    'status' => 'in_production',
                ];
            }
        }

        $this->broadcastMonitoringUpdate('hourly_saved', $order, meta: [
            'time_range' => $report->time_range,
            'output_type' => $report->output_type ?: 'fg',
            'process_name' => $report->process_name,
            'output_part_no' => $report->output_part_no,
            'output_part_name' => $report->output_part_name,
            'machine_id' => $report->machine_id ? (int) $report->machine_id : null,
            'machine_name' => $report->machine_name,
            'actual' => (int) $report->actual,
            'ng' => (int) $report->ng,
            'ng_reason' => $report->ng_reason,
            'ng_scrap' => (int) ($report->ng_scrap ?? 0),
            'ng_rework' => (int) ($report->ng_rework ?? 0),
            'ng_hold' => (int) ($report->ng_hold ?? 0),
            'backflush_events' => $backflushEvents,
            'wip_inventory_event' => $wipInventoryEvent,
            'previous_wip_consume_event' => $previousWipConsumeEvent,
            'handover' => $handoverMeta,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'time_range' => $report->time_range,
                'target' => (int) $report->target,
                'actual' => (int) $report->actual,
                'ng' => (int) $report->ng,
                'ng_reason' => $report->ng_reason,
                'ng_scrap' => (int) ($report->ng_scrap ?? 0),
                'ng_rework' => (int) ($report->ng_rework ?? 0),
                'ng_hold' => (int) ($report->ng_hold ?? 0),
                'output_type' => $report->output_type ?: 'fg',
                'process_name' => $report->process_name,
                'output_part_no' => $report->output_part_no,
                'output_part_name' => $report->output_part_name,
                'machine_id' => $report->machine_id ? (int) $report->machine_id : null,
                'machine_name' => $report->machine_name,
                'input_actual' => (int) $incrementActual,
                'input_ng' => (int) $incrementNg,
                'operator_name' => $report->operator_name,
                'shift' => $report->shift,
                'qty_actual_total' => $totalActual,
                'qty_ng_total' => $totalNg,
                'backflush_events' => $backflushEvents,
                'wip_inventory_event' => $wipInventoryEvent,
                'previous_wip_consume_event' => $previousWipConsumeEvent,
                'handover' => $handoverMeta,
            ],
        ]);
    }

    public function machineDowntimes(Request $request, $id)
    {
        $date = $request->query('date', now()->toDateString());

        $items = ProductionGciDowntime::query()
            ->where('machine_id', (int) $id)
            ->whereDate('start_time', $date)
            ->orderByDesc('start_time')
            ->get();

        $active = $items->first(fn (ProductionGciDowntime $item) => $item->end_time === null);

        return response()->json([
            'data' => [
                'active' => $active ? $this->formatDowntime($active) : null,
                'items' => $items->map(fn (ProductionGciDowntime $item) => $this->formatDowntime($item))->values(),
            ],
        ]);
    }

    public function startMachineDowntime(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'machine_name' => 'nullable|string|max:255',
                'shift' => 'nullable|string|max:50',
                'operator_name' => 'nullable|string|max:255',
                'reason' => 'required|string|max:255',
                'notes' => 'nullable|string',
                'refill_part_no' => 'nullable|string|max:255',
                'refill_part_name' => 'nullable|string|max:255',
                'refill_qty' => 'nullable|numeric|min:0',
                'production_order_id' => 'nullable|integer|exists:production_orders,id',
                'start_time' => 'nullable|date',
            ]);

            $order = !empty($validated['production_order_id'])
                ? ProductionOrder::find($validated['production_order_id'])
                : null;
            $legacyWorkOrder = $order
                ? $this->resolveLegacyGciWorkOrder($order)
                : $this->resolveMachineDowntimeWorkOrder(
                    (int) $id,
                    $validated['shift'] ?? null,
                    $validated['operator_name'] ?? null,
                );

            $meta = [
                'type' => in_array($validated['reason'], ['Ganti Type', 'Ganti Material / Reffil Material', 'Cleaning Machine', 'Briefing', 'Trial'], true)
                    ? 'qdc_reason'
                    : 'downtime',
                'production_order_id' => $order?->id,
                'production_order_number' => $order?->production_order_number,
                'notes' => $validated['notes'] ?? '',
            ];

            $downtime = ProductionGciDowntime::create([
                'production_gci_work_order_id' => $legacyWorkOrder->id,
                'machine_id' => (int) $id,
                'machine_name' => $validated['machine_name'] ?? optional(Machine::find($id))->name,
                'shift' => $validated['shift'] ?? $order?->shift,
                'operator_name' => $validated['operator_name'] ?? null,
                'start_time' => Carbon::parse($validated['start_time'] ?? now())->toDateTimeString(),
                'end_time' => null,
                'duration_minutes' => 0,
                'reason' => $validated['reason'],
                'notes' => json_encode($meta),
                'refill_part_no' => $validated['refill_part_no'] ?? null,
                'refill_part_name' => $validated['refill_part_name'] ?? null,
                'refill_qty' => $validated['refill_qty'] ?? null,
                'offline_id' => $this->generateCloudOfflineId(),
            ]);

            $this->broadcastMonitoringUpdate('downtime_started', $order, dates: [$downtime->start_time], machineIds: [(int) $id], meta: [
                'reason' => $downtime->reason,
                'downtime_id' => (int) $downtime->id,
            ]);

            return response()->json(['status' => 'success', 'data' => $this->formatDowntime($downtime)]);
        } catch (\Throwable $e) {
            Log::error('startMachineDowntime failed', [
                'machine_id' => (int) $id,
                'payload' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Downtime gagal disimpan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function stopMachineDowntime(Request $request, $id)
    {
        $validated = $request->validate([
            'end_time' => 'nullable|date',
        ]);

        $downtime = ProductionGciDowntime::findOrFail($id);
        $endTime = Carbon::parse($validated['end_time'] ?? now());
        $startTime = Carbon::parse($downtime->start_time);
        $duration = max(0, (int) ceil($startTime->diffInSeconds($endTime) / 60));
        $meta = $this->decodeDowntimeNotes($downtime->notes);
        $order = !empty($meta['production_order_id']) ? ProductionOrder::find((int) $meta['production_order_id']) : null;

        $downtime->update([
            'end_time' => $endTime->toDateTimeString(),
            'duration_minutes' => $duration,
        ]);

        $this->broadcastMonitoringUpdate('downtime_stopped', $order, dates: [$downtime->start_time, $downtime->end_time], machineIds: [(int) ($downtime->machine_id ?? 0)], meta: [
            'reason' => $downtime->reason,
            'downtime_id' => (int) $downtime->id,
            'duration_minutes' => $duration,
        ]);

        return response()->json(['status' => 'success', 'data' => $this->formatDowntime($downtime->fresh())]);
    }

    /**
     * Store QDC timer session from Android app
     */
    public function storeQdcSession(Request $request)
    {
        $data = $request->validate([
            'machine_id' => 'required|integer',
            'production_order_id' => 'nullable|integer|exists:production_orders,id',
            'machine_name' => 'nullable|string',
            'operator_name' => 'nullable|string',
            'shift' => 'nullable|string',
            'part_from' => 'nullable|string',
            'part_to' => 'nullable|string',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'duration_seconds' => 'required|integer',
            'internal_seconds' => 'nullable|integer',
            'external_seconds' => 'nullable|integer',
            'checklist' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        // Store as a downtime record with reason = 'QDC / Die Change'
        $durationMinutes = intval(ceil($data['duration_seconds'] / 60));
        $order = !empty($data['production_order_id']) ? ProductionOrder::with('part')->find($data['production_order_id']) : null;

        ProductionGciDowntime::create([
            'machine_id' => $data['machine_id'],
            'machine_name' => $data['machine_name'] ?? $order?->machine?->name,
            'shift' => $data['shift'] ?? $order?->shift,
            'operator_name' => $data['operator_name'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'duration_minutes' => $durationMinutes,
            'reason' => 'Ganti Tipe/Setting',
            'notes' => json_encode([
                'type' => 'qdc_session',
                'production_order_id' => $order?->id,
                'production_order_number' => $order?->production_order_number,
                'part_no' => $order?->part?->part_no,
                'part_name' => $order?->part?->part_name,
                'part_from' => $data['part_from'],
                'part_to' => $data['part_to'],
                'duration_seconds' => $data['duration_seconds'],
                'internal_seconds' => $data['internal_seconds'] ?? 0,
                'external_seconds' => $data['external_seconds'] ?? 0,
                'checklist' => $data['checklist'] ?? [],
                'notes' => $data['notes'] ?? '',
            ]),
        ]);

        $this->broadcastMonitoringUpdate(
            type: 'qdc_logged',
            order: $order,
            dates: [$data['start_time'], $data['end_time']],
            machineIds: [(int) $data['machine_id']],
            meta: [
                'duration_seconds' => (int) $data['duration_seconds'],
                'production_order_id' => $order?->id,
            ],
        );

        return response()->json(['status' => 'success']);
    }

    public function machineQdcSessions(Request $request, $id)
    {
        $date = $request->query('date', now()->toDateString());

        $items = ProductionGciDowntime::query()
            ->where('machine_id', (int) $id)
            ->whereDate('start_time', $date)
            ->orderByDesc('start_time')
            ->get()
            ->filter(function (ProductionGciDowntime $downtime) {
                $meta = $this->decodeDowntimeNotes($downtime->notes);

                return ($meta['type'] ?? null) === 'qdc_session';
            })
            ->map(function (ProductionGciDowntime $downtime) {
                $meta = $this->decodeDowntimeNotes($downtime->notes);

                return [
                    'id' => (int) $downtime->id,
                    'machine_id' => (int) ($downtime->machine_id ?? 0),
                    'machine_name' => (string) ($downtime->machine_name ?? '-'),
                    'shift' => (string) ($downtime->shift ?? '-'),
                    'operator_name' => (string) ($downtime->operator_name ?? ''),
                    'production_order_id' => (int) ($meta['production_order_id'] ?? 0) ?: null,
                    'production_order_number' => $meta['production_order_number'] ?? null,
                    'part_from' => $meta['part_from'] ?? null,
                    'part_to' => $meta['part_to'] ?? null,
                    'part_no' => $meta['part_no'] ?? null,
                    'part_name' => $meta['part_name'] ?? null,
                    'start_time' => (string) $downtime->start_time,
                    'end_time' => $downtime->end_time ? (string) $downtime->end_time : null,
                    'duration_seconds' => (int) ($meta['duration_seconds'] ?? ((int) ($downtime->duration_minutes ?? 0) * 60)),
                    'internal_seconds' => (int) ($meta['internal_seconds'] ?? 0),
                    'external_seconds' => (int) ($meta['external_seconds'] ?? 0),
                    'notes' => (string) ($meta['notes'] ?? ''),
                ];
            })
            ->values();

        return response()->json(['data' => $items]);
    }

    /**
     * WO Monitoring data for WEB dashboard (JSON endpoint)
     */
    public function woMonitoringData(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        $shift = $request->query('shift');

        $machines = Machine::active()->orderBy('name')->get();

        $result = [];
        foreach ($machines as $machine) {
            $query = ProductionOrder::with('part:id,part_no,part_name,model')
                ->whereDate('plan_date', $date)
                ->where(function ($query) use ($machine) {
                    $query->where('machine_id', $machine->id)
                        ->orWhereHas('hourlyReports', function ($hourlyQuery) use ($machine) {
                            $hourlyQuery->where('machine_id', $machine->id);
                        });
                })
                ->orderBy('production_sequence');

            $orders = $query->get();

            // Get downtimes for this machine today
            $downtimes = ProductionGciDowntime::where('machine_id', $machine->id)
                ->whereDate('start_time', $date)
                ->get();

            $qdcByOrder = [];
            $machineQdcSessions = [];
            $machineQdcTotalSeconds = 0;
            foreach ($downtimes as $dt) {
                $meta = $this->decodeDowntimeNotes($dt->notes);
                if (($meta['type'] ?? null) !== 'qdc_session') {
                    continue;
                }

                $durationSeconds = (int) ($meta['duration_seconds'] ?? ((int) ($dt->duration_minutes ?? 0) * 60));
                $poId = (int) ($meta['production_order_id'] ?? 0);
                $session = [
                    'id' => (int) $dt->id,
                    'production_order_id' => $poId > 0 ? $poId : null,
                    'production_order_number' => $meta['production_order_number'] ?? null,
                    'operator_name' => (string) ($dt->operator_name ?? ''),
                    'start_time' => (string) $dt->start_time,
                    'end_time' => $dt->end_time ? (string) $dt->end_time : null,
                    'duration_seconds' => $durationSeconds,
                    'internal_seconds' => (int) ($meta['internal_seconds'] ?? 0),
                    'external_seconds' => (int) ($meta['external_seconds'] ?? 0),
                    'part_from' => $meta['part_from'] ?? null,
                    'part_to' => $meta['part_to'] ?? null,
                    'part_no' => $meta['part_no'] ?? null,
                    'part_name' => $meta['part_name'] ?? null,
                    'notes' => (string) ($meta['notes'] ?? ''),
                ];

                $machineQdcSessions[] = $session;
                $machineQdcTotalSeconds += $durationSeconds;

                if ($poId <= 0) {
                    continue;
                }

                $currentLatest = $qdcByOrder[$poId]['latest_session'] ?? null;
                $qdcByOrder[$poId] = [
                    'count' => (int) (($qdcByOrder[$poId]['count'] ?? 0) + 1),
                    'duration_seconds' => (int) (($qdcByOrder[$poId]['duration_seconds'] ?? 0) + $durationSeconds),
                    'latest_session' => !$currentLatest || strtotime((string) $session['start_time']) >= strtotime((string) ($currentLatest['start_time'] ?? ''))
                        ? $session
                        : $currentLatest,
                ];
            }

            $qdcReasons = ['Ganti Type', 'Ganti Material / Reffil Material', 'Cleaning Machine', 'Briefing', 'Trial', 'Ganti Tipe/Setting'];
            $totalDowntimeMinutes = $downtimes->where('reason', '!=', 'Istirahat')
                ->reject(fn($dt) => in_array($dt->reason, $qdcReasons))
                ->sum('duration_minutes');

            // Get hourly reports for orders on this machine
            $orderIds = $orders->pluck('id');
            $hourlyReports = ProductionGciHourlyReport::whereIn('production_order_id', $orderIds)
                ->where(function ($query) use ($machine) {
                    $query->where('machine_id', $machine->id)
                        ->orWhereNull('machine_id');
                })
                ->get();

            $result[] = [
                'machine' => [
                    'id' => $machine->id,
                    'name' => $machine->name,
                    'code' => $machine->code,
                ],
                'orders' => $orders->map(function (ProductionOrder $o) use ($hourlyReports, $qdcByOrder) {
                    $displayStatus = $this->resolveMonitoringStatus($o);
                    $qdc = $qdcByOrder[$o->id] ?? ['count' => 0, 'duration_seconds' => 0, 'latest_session' => null];

                    return [
                        'id' => $o->id,
                        'wo_number' => $o->production_order_number,
                        'part_no' => $o->part?->part_no,
                        'part_name' => $o->part?->part_name,
                        'model' => $o->part?->model,
                        'qty_planned' => (float) $o->qty_planned,
                        'qty_actual' => (float) ($o->qty_actual ?? 0),
                        'qty_ng' => (float) ($o->qty_ng ?? 0),
                        'status' => $o->status,
                        'display_status' => $displayStatus,
                        'process_name' => (string) ($o->process_name ?? ''),
                        'last_handover_from_process' => (string) ($o->last_handover_from_process ?? ''),
                        'last_handover_from_machine_name' => (string) ($o->last_handover_from_machine_name ?? ''),
                        'last_handover_at' => $o->last_handover_at ? (string) $o->last_handover_at : null,
                        'start_time' => $o->start_time,
                        'end_time' => $o->end_time,
                        'shift' => $o->shift,
                        'qdc_count' => (int) $qdc['count'],
                        'qdc_duration_seconds' => (int) $qdc['duration_seconds'],
                        'latest_qdc' => $qdc['latest_session'],
                        'hourly' => $hourlyReports->where('production_order_id', $o->id)->map(fn($h) => [
                            'time_range' => $h->time_range,
                            'target' => (int) $h->target,
                            'actual' => (int) $h->actual,
                            'ng' => (int) $h->ng,
                            'ng_scrap' => (int) ($h->ng_scrap ?? 0),
                            'ng_rework' => (int) ($h->ng_rework ?? 0),
                            'ng_hold' => (int) ($h->ng_hold ?? 0),
                            'output_type' => (string) ($h->output_type ?: 'fg'),
                            'process_name' => (string) ($h->process_name ?? ''),
                            'output_part_no' => (string) ($h->output_part_no ?? ''),
                            'output_part_name' => (string) ($h->output_part_name ?? ''),
                            'machine_id' => $h->machine_id ? (int) $h->machine_id : null,
                            'machine_name' => (string) ($h->machine_name ?? ''),
                        ])->values(),
                        'handover_history' => $hourlyReports
                            ->where('production_order_id', $o->id)
                            ->filter(fn($h) => strtolower((string) ($h->output_type ?: 'fg')) === 'wip' && (int) ($h->actual ?? 0) > 0)
                            ->sortByDesc('created_at')
                            ->take(4)
                            ->map(fn($h) => [
                                'time_range' => $h->time_range,
                                'created_at' => $h->created_at ? (string) $h->created_at : null,
                                'process_name' => (string) ($h->process_name ?? ''),
                                'output_part_no' => (string) ($h->output_part_no ?? ''),
                                'output_part_name' => (string) ($h->output_part_name ?? ''),
                                'actual' => (int) ($h->actual ?? 0),
                                'operator_name' => (string) ($h->operator_name ?? ''),
                                'shift' => (string) ($h->shift ?? ''),
                                'machine_id' => $h->machine_id ? (int) $h->machine_id : null,
                                'machine_name' => (string) ($h->machine_name ?? ''),
                            ])->values(),
                    ];
                }),
                'total_downtime_minutes' => $totalDowntimeMinutes,
                'downtime_count' => $downtimes->count(),
                'qdc_count' => count($machineQdcSessions),
                'qdc_total_seconds' => $machineQdcTotalSeconds,
                'qdc_unassigned_count' => collect($machineQdcSessions)->whereNull('production_order_id')->count(),
                'qdc_sessions' => collect($machineQdcSessions)->sortByDesc('start_time')->values()->all(),
            ];
        }

        return response()->json(['data' => $result, 'date' => $date]);
    }
}
