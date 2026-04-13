<?php

namespace App\Http\Controllers\Api;

use App\Events\Production\MonitoringUpdated;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Machine;
use App\Models\ProductionOrder;
use App\Models\ProductionInspection;
use App\Models\ProductionGciWorkOrder;
use App\Models\ProductionGciHourlyReport;
use App\Models\ProductionGciDowntime;
use App\Models\ProductionGciMaterialLot;
use App\Models\Bom;
use App\Models\GciInventory;
use Illuminate\Support\Str;

class ProductionGciApiController extends Controller
{
    private const CLOSED_EXECUTION_STAGES = [
        'final_inspection',
        'kanban_update',
        'warehouse_supply',
        'finished',
    ];

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
        return (int) now()->format('YmdHis') . random_int(10, 99);
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

        $materialReady = !$requiresIssue
            ? true
            : ($shortageCount === 0 && $issuePosted && $handoverDone && $issueLines->isNotEmpty());

        return [
            'requires_issue' => $requiresIssue,
            'request_line_count' => $requestLines->count(),
            'shortage_count' => $shortageCount,
            'issue_posted' => $issuePosted,
            'issued_at' => $order->material_issued_at ? $order->material_issued_at->toDateTimeString() : null,
            'handover_done' => $handoverDone,
            'handed_over_at' => $order->material_handed_over_at ? $order->material_handed_over_at->toDateTimeString() : null,
            'material_ready' => $materialReady,
            'issued_tag_count' => count($issuedTags),
            'issued_tags' => $issuedTags,
            'start_block_reason' => $materialReady
                ? null
                : (!$requiresIssue
                    ? null
                    : ($shortageCount > 0
                        ? 'Material request masih shortage.'
                        : (!$issuePosted
                            ? 'WH supply ke production belum diposting.'
                            : (!$handoverDone
                                ? 'Material sudah diissue, tapi serah terima ke production belum dicatat.'
                                : 'Material issue belum lengkap.')))),
        ];
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
                'uom' => (string) ($issueLine['uom'] ?? '-'),
                'required_qty' => (float) ($requestLine['required_qty'] ?? $issueLine['required_qty'] ?? 0),
                'available_qty' => (float) ($requestLine['available_qty'] ?? 0),
                'shortage_qty' => (float) ($requestLine['shortage_qty'] ?? 0),
                'issued_qty' => (float) ($issueLine['issued_qty'] ?? 0),
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
                    ProductionGciHourlyReport::updateOrCreate(
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
                            'operator_name' => $hrParams['operatorName'] ?? null,
                            'shift' => $hrParams['shift'] ?? null,
                        ]
                    );

                        // Update production order actual totals
                        $po = ProductionOrder::find($hrParams['productionOrderId']);
                        if ($po) {
                            $totalActual = ProductionGciHourlyReport::where('production_order_id', $po->id)->sum('actual');
                            $totalNg = ProductionGciHourlyReport::where('production_order_id', $po->id)->sum('ng');
                            $po->update([
                                'qty_actual' => $totalActual,
                                'qty_ng' => $totalNg,
                            ]);
                            $affectedDates[] = $po->plan_date;
                            $affectedMachineIds[] = (int) $po->machine_id;
                            $affectedOrderIds[] = (int) $po->id;
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
        $machineId = $request->query('machine_id');
        $date = $request->query('date', now()->toDateString());

        $machineIdInt = (int) $machineId;
        $query = ProductionOrder::with('part:id,part_no,part_name,model')
            ->where('machine_id', $machineIdInt)
            ->where(function($q) use ($date) {
                // 1. Show all WOs for the selected date for this machine
                $q->whereDate('plan_date', $date);
                
                // 2. OR show backlog for this machine: WOs that are released, in production, or kanban_released
                $q->orWhereIn('status', ['kanban_released', 'released', 'in_production', 'paused']);
            })
            ->whereNotIn('workflow_stage', self::CLOSED_EXECUTION_STAGES)
            ->whereNotIn('status', ['material_hold', 'resource_hold', 'cancelled', 'completed'])
            ->orderBy('plan_date', 'asc')
            ->orderBy('production_sequence', 'asc');

        $orders = $query->get()->map(function ($o) {
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
                'shift' => (string) $o->shift,
                'production_sequence' => $o->production_sequence !== null ? (int) $o->production_sequence : null,
                'start_time' => $o->start_time ? (string) $o->start_time : null,
                'end_time' => $o->end_time ? (string) $o->end_time : null,
                'material_status' => $materialStatus,
                'can_start' => $materialStatus['material_ready']
                    && in_array((string) $o->status, ['released', 'kanban_released'], true),
            ];
        });

        return response()->json(['data' => $orders]);
    }

    public function materialStatus($id)
    {
        $order = ProductionOrder::with('part:id,part_no,part_name,model')->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => (int) $order->id,
                'wo_number' => (string) ($order->production_order_number ?? $order->transaction_no ?? '-'),
                'status' => (string) $order->status,
                'workflow_stage' => (string) $order->workflow_stage,
                'part_no' => (string) ($order->part?->part_no ?? '-'),
                'part_name' => (string) ($order->part?->part_name ?? '-'),
                'material_status' => $this->buildMaterialStatus($order),
            ],
        ]);
    }

    public function materialIssueHistory($id)
    {
        $order = ProductionOrder::with('part:id,part_no,part_name,model')->findOrFail($id);
        $materialStatus = $this->buildMaterialStatus($order);
        $issueHistory = $this->buildMaterialIssueHistory($order);

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
            ],
        ]);
    }

    /**
     * Start a WO from Android app (operator starts production)
     */
    public function startWo(Request $request, $id)
    {
        $order = ProductionOrder::findOrFail($id);

        $isMachineBusy = ProductionOrder::where('machine_id', $order->machine_id)
            ->whereIn('status', ['in_production', 'paused'])
            ->where('id', '!=', $order->id)
            ->exists();

        if ($isMachineBusy) {
            return response()->json([
                'message' => 'Pekerjaan ditolak. Masih ada Work Order lain yang sedang aktif (Running/Paused) pada mesin ini.'
            ], 422);
        }

        // Block PLANNED status
        if ($order->status === 'planned') {
            return response()->json([
                'message' => 'WO masih dalam status PLANNED. Silakan hubungi admin untuk melakukan RELEASE WO terlebih dahulu.'
            ], 422);
        }

        $materialStatus = $this->buildMaterialStatus($order);
        if (!$materialStatus['material_ready']) {
            return response()->json([
                'message' => $materialStatus['start_block_reason'] ?? 'Material untuk WO ini belum siap.',
                'material_status' => $materialStatus,
            ], 422);
        }

        // Allow starting from kanban_released or released status
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json(['message' => 'WO sudah selesai atau dibatalkan'], 422);
        }

        if ($order->status === 'in_production') {
            return response()->json(['message' => 'WO sudah berjalan', 'data' => $order], 200);
        }

        $order->update([
            'status' => 'in_production',
            'workflow_stage' => 'mass_production',
            'start_time' => $order->start_time ?? now(),
        ]);

        $this->broadcastMonitoringUpdate('wo_started', $order, meta: [
            'status' => 'in_production',
            'workflow_stage' => 'mass_production',
        ]);

        return response()->json(['status' => 'success', 'data' => $order->fresh()]);
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
        $hourlyActual = (float) ProductionGciHourlyReport::where('production_order_id', $id)->sum('actual');
        $hourlyNg = (float) ProductionGciHourlyReport::where('production_order_id', $id)->sum('ng');

        $finalActual = isset($validated['qty_actual'])
            ? (float) $validated['qty_actual']
            : ($hourlyActual > 0 ? $hourlyActual : (float) ($order->qty_actual ?? 0));

        $finalNg = isset($validated['qty_ng'])
            ? (float) $validated['qty_ng']
            : ($hourlyNg > 0 ? $hourlyNg : (float) ($order->qty_ng ?? 0));

        // Auto-Backflush
        if (($finalActual + $finalNg) > 0) {
            $bom = Bom::activeVersion($order->gci_part_id, $order->plan_date);
            if ($bom) {
                $requirements = $bom->getTotalMaterialRequirements($finalActual + $finalNg);
                foreach ($requirements as $req) {
                    if ($this->isRmBuyRequirement($req)) {
                        $part = $req['part'] ?? null;
                        if ($part) {
                            $needed = round((float) ($req['total_qty'] ?? 0), 4);
                            $needed = round((float) ($req['total_qty'] ?? 0), 4);
                            $tags = $order->material_issue_lines ?? [];
                            $deducted = false;

                            foreach ($tags as $tag) {
                                $tagNo = $tag['tag_number'] ?? '';
                                if (!empty($tagNo)) {
                                    // Try to deduct from the exact tag/batch to maintain ISO traceability
                                    $inventory = GciInventory::where('gci_part_id', $part->id)
                                        ->where('batch_no', $tagNo)
                                        ->first();
                                    if ($inventory && $needed > 0) {
                                        $inventory->on_hand -= $needed;
                                        $inventory->save();
                                        $deducted = true;
                                        break;
                                    }
                                }
                            }

                            // Fallback to random FIFO if no matching tag found
                            if (!$deducted) {
                                $inventory = GciInventory::where('gci_part_id', $part->id)->orderByDesc('id')->first();
                                if ($inventory) {
                                    $inventory->on_hand -= $needed;
                                    $inventory->save();
                                }
                            }
                        }
                    }
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
            ->get()
            ->map(fn($r) => [
                'time_range' => $r->time_range,
                'target' => $r->target,
                'actual' => $r->actual,
                'ng' => $r->ng,
                'operator_name' => $r->operator_name,
                'shift' => $r->shift,
            ]);

        return response()->json(['data' => $reports]);
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
            'operator_name' => 'nullable|string|max:255',
            'shift' => 'nullable|string|max:50',
        ]);

        $report = ProductionGciHourlyReport::query()
            ->where('production_order_id', $order->id)
            ->where('time_range', $validated['time_range'])
            ->first();

        // 110% Limit Validation
        $oldActual = $report ? (float) $report->actual : 0;
        $oldNg = $report ? (float) $report->ng : 0;
        
        $totalCurrentActual = (float) ProductionGciHourlyReport::where('production_order_id', $order->id)->sum('actual');
        $totalCurrentNg = (float) ProductionGciHourlyReport::where('production_order_id', $order->id)->sum('ng');
        
        $newTotalActual = ($totalCurrentActual - $oldActual) + (float) $validated['actual'];
        $newTotalNg = ($totalCurrentNg - $oldNg) + (float) $validated['ng'];
        
        $maxAllowed = ceil((float)$order->qty_planned * 1.1);
        
        if (($newTotalActual + $newTotalNg) > $maxAllowed) {
            return response()->json([
                'message' => "Akumulasi total (" . $newTotalActual . " OK + " . $newTotalNg . " NG) melampaui toleransi 110% dari target plan (" . $maxAllowed . "). Harap periksa kembali input Anda!"
            ], 422);
        }

        if ($report) {
            $report->fill([
                'target' => $validated['target'] ?? (int) $report->target,
                'actual' => $validated['actual'],
                'ng' => $validated['ng'],
                'ng_reason' => $validated['ng_reason'] ?? $report->ng_reason,
                'operator_name' => $validated['operator_name'] ?? $report->operator_name,
                'shift' => $validated['shift'] ?? $report->shift,
            ])->save();
        } else {
            $legacyWorkOrder = $this->resolveLegacyGciWorkOrder($order);
            $report = ProductionGciHourlyReport::create([
                'production_gci_work_order_id' => $legacyWorkOrder->id,
                'production_order_id' => $order->id,
                'time_range' => $validated['time_range'],
                'target' => $validated['target'] ?? 0,
                'actual' => $validated['actual'],
                'ng' => $validated['ng'],
                'ng_reason' => $validated['ng_reason'] ?? null,
                'offline_id' => $this->generateCloudOfflineId(),
                'operator_name' => $validated['operator_name'] ?? null,
                'shift' => $validated['shift'] ?? null,
            ]);
        }

        $totalActual = (float) ProductionGciHourlyReport::where('production_order_id', $order->id)->sum('actual');
        $totalNg = (float) ProductionGciHourlyReport::where('production_order_id', $order->id)->sum('ng');

        $order->update([
            'qty_actual' => $totalActual,
            'qty_ng' => $totalNg,
        ]);

        $this->broadcastMonitoringUpdate('hourly_saved', $order, meta: [
            'time_range' => $report->time_range,
            'actual' => (int) $report->actual,
            'ng' => (int) $report->ng,
            'ng_reason' => $report->ng_reason,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'time_range' => $report->time_range,
                'target' => (int) $report->target,
                'actual' => (int) $report->actual,
                'ng' => (int) $report->ng,
                'ng_reason' => $report->ng_reason,
                'operator_name' => $report->operator_name,
                'shift' => $report->shift,
                'qty_actual_total' => $totalActual,
                'qty_ng_total' => $totalNg,
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
                ->where('machine_id', $machine->id)
                ->whereDate('plan_date', $date)
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
            $hourlyReports = ProductionGciHourlyReport::whereIn('production_order_id', $orderIds)->get();

            $result[] = [
                'machine' => [
                    'id' => $machine->id,
                    'name' => $machine->name,
                    'code' => $machine->code,
                ],
                'orders' => $orders->map(function ($o) use ($hourlyReports, $qdcByOrder) {
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
