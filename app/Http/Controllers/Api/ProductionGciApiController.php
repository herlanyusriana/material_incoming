<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
}
