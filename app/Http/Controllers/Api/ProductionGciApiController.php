<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Machine;
use App\Models\ProductionOrder;
use App\Models\ProductionGciWorkOrder;
use App\Models\ProductionGciHourlyReport;
use App\Models\ProductionGciDowntime;
use App\Models\ProductionGciMaterialLot;

class ProductionGciApiController extends Controller
{
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
                    // Attempt to resolve WO ID, fallback to finding by offline_id if not in array
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
                            ['offline_id' => $dtParams['id']],
                            [
                                'production_gci_work_order_id' => null,
                                'machine_id' => $dtParams['machineId'],
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

        $query = ProductionOrder::with('part:id,part_no,part_name,model')
            ->whereDate('planned_start_date', $date)
            ->orderBy('transaction_no');

        if ($machineId) {
            $query->where('machine_id', $machineId);
        }

        $orders = $query->get()->map(fn($o) => [
            'id' => $o->id,
            'transaction_no' => $o->transaction_no,
            'part_no' => $o->part?->part_no,
            'part_name' => $o->part?->part_name,
            'model' => $o->part?->model,
            'qty' => $o->qty,
            'status' => $o->status,
        ]);

        return response()->json(['data' => $orders]);
    }
}
