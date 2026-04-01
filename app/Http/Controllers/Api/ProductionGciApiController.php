<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Machine;
use App\Models\ProductionOrder;
use App\Models\ProductionInspection;
use App\Models\ProductionGciWorkOrder;
use App\Models\ProductionGciHourlyReport;
use App\Models\ProductionGciDowntime;
use App\Models\ProductionGciMaterialLot;
use App\Models\Bom;
use App\Models\GciInventory;

class ProductionGciApiController extends Controller
{
    private function isRmBuyRequirement(array $req): bool
    {
        $makeOrBuy = strtoupper(trim((string) ($req['make_or_buy'] ?? 'BUY')));
        $classification = strtoupper(trim((string) ($req['part']?->classification ?? '')));

        return in_array($makeOrBuy, ['BUY', 'B', 'PURCHASE'], true)
            && $classification === 'RM';
    }

    private function resolveMonitoringStatus(ProductionOrder $order): string
    {
        if ($order->kanban_updated_at || in_array((string) $order->workflow_stage, ['warehouse_supply', 'finished'], true)) {
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
                $q->orWhereIn('status', ['kanban_released', 'released', 'in_production']);
            })
            ->whereNotIn('status', ['material_hold', 'resource_hold', 'cancelled', 'completed'])
            ->orderBy('plan_date', 'asc')
            ->orderBy('production_sequence', 'asc');

        $orders = $query->get()->map(fn($o) => [
            'id' => (int) $o->id,
            'wo_number' => (string) ($o->production_order_number ?? $o->transaction_no ?? '-'),
            'transaction_no' => (string) $o->transaction_no,
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
        ]);

        return response()->json(['data' => $orders]);
    }

    /**
     * Start a WO from Android app (operator starts production)
     */
    public function startWo(Request $request, $id)
    {
        $order = ProductionOrder::findOrFail($id);

        // Block PLANNED status
        if ($order->status === 'planned') {
            return response()->json([
                'message' => 'WO masih dalam status PLANNED. Silakan hubungi admin untuk melakukan RELEASE WO terlebih dahulu.'
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

        return response()->json(['status' => 'success', 'data' => $order->fresh()]);
    }

    /**
     * Finish a WO from Android app
     */
    public function finishWo(Request $request, $id)
    {
        $order = ProductionOrder::findOrFail($id);

        if ($order->status !== 'in_production') {
            return response()->json(['message' => 'WO belum dimulai'], 422);
        }

        // Sum actual from hourly reports
        $totalActual = ProductionGciHourlyReport::where('production_order_id', $id)->sum('actual');
        $totalNg = ProductionGciHourlyReport::where('production_order_id', $id)->sum('ng');

        $order->update([
            'status' => 'in_production',
            'workflow_stage' => 'final_inspection',
            'end_time' => now(),
            'qty_actual' => $totalActual > 0 ? $totalActual : $order->qty_actual,
            'qty_ng' => $totalNg > 0 ? $totalNg : ($order->qty_ng ?? 0),
        ]);

        // Create final inspection if not exists
        if (!$order->inspections()->where('type', 'final')->exists()) {
            ProductionInspection::create([
                'production_order_id' => $order->id,
                'type' => 'final',
                'status' => 'pending',
            ]);
        }

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

    /**
     * Store QDC timer session from Android app
     */
    public function storeQdcSession(Request $request)
    {
        $data = $request->validate([
            'machine_id' => 'required|integer',
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

        ProductionGciDowntime::create([
            'machine_id' => $data['machine_id'],
            'machine_name' => $data['machine_name'] ?? null,
            'shift' => $data['shift'] ?? null,
            'operator_name' => $data['operator_name'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'duration_minutes' => $durationMinutes,
            'reason' => 'Ganti Tipe/Setting',
            'notes' => json_encode([
                'type' => 'qdc_session',
                'part_from' => $data['part_from'],
                'part_to' => $data['part_to'],
                'duration_seconds' => $data['duration_seconds'],
                'internal_seconds' => $data['internal_seconds'] ?? 0,
                'external_seconds' => $data['external_seconds'] ?? 0,
                'checklist' => $data['checklist'] ?? [],
                'notes' => $data['notes'] ?? '',
            ]),
        ]);

        return response()->json(['status' => 'success']);
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
                'orders' => $orders->map(function ($o) use ($hourlyReports) {
                    $displayStatus = $this->resolveMonitoringStatus($o);

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
            ];
        }

        return response()->json(['data' => $result, 'date' => $date]);
    }
}
