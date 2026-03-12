<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionGciWorkOrder;
use App\Models\ProductionGciDowntime;
use App\Models\ProductionGciHourlyReport;
use App\Models\ProductionOrder;
use Illuminate\Support\Facades\DB;

class ProductionGciWebController extends Controller
{
    public function index()
    {
        $workOrders = ProductionGciWorkOrder::with(['hourlyReports', 'downtimes', 'materialLots'])
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('production.gci-dashboard.index', compact('workOrders'));
    }

    public function show($id)
    {
        $workOrder = ProductionGciWorkOrder::with(['hourlyReports', 'downtimes', 'materialLots'])->findOrFail($id);
        return view('production.gci-dashboard.show', compact('workOrder'));
    }

    public function woMonitoring(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        return view('production.wo-monitoring.index', compact('date'));
    }

    public function operatorKpi(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));
        return view('production.operator-kpi.index', compact('from', 'to'));
    }

    public function operatorKpiData(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->format('Y-m-d'));
        $to = $request->get('to', now()->format('Y-m-d'));

        // 1. Get all downtimes in period, grouped by operator
        $downtimes = ProductionGciDowntime::whereBetween(
                DB::raw('DATE(start_time)'), [$from, $to]
            )
            ->whereNotNull('operator_name')
            ->where('operator_name', '!=', '')
            ->get();

        // 2. Get hourly reports linked to production orders
        $hourlyReports = ProductionGciHourlyReport::whereNotNull('production_order_id')
            ->whereHas('productionOrder', function ($q) use ($from, $to) {
                $q->whereBetween('plan_date', [$from, $to]);
            })
            ->with('productionOrder:id,machine_id,plan_date,qty_planned,status')
            ->get();

        // 3. Get production orders in period
        $orders = ProductionOrder::with('part:id,part_no,part_name')
            ->whereBetween('plan_date', [$from, $to])
            ->get();

        // ─── Aggregate per operator ───
        $operatorMap = [];

        // From downtimes
        foreach ($downtimes as $dt) {
            $name = $dt->operator_name;
            if (!isset($operatorMap[$name])) {
                $operatorMap[$name] = [
                    'name' => $name,
                    'total_output' => 0,
                    'total_ng' => 0,
                    'total_target' => 0,
                    'total_downtime_minutes' => 0,
                    'downtime_count' => 0,
                    'qdc_count' => 0,
                    'qdc_total_seconds' => 0,
                    'wo_count' => 0,
                    'days_worked' => [],
                    'machines_used' => [],
                    'downtime_reasons' => [],
                ];
            }

            $op = &$operatorMap[$name];
            $op['total_downtime_minutes'] += $dt->duration_minutes ?? 0;
            $op['downtime_count']++;

            // Track downtime reasons
            $reason = $dt->reason ?? 'Lainnya';
            if (!isset($op['downtime_reasons'][$reason])) {
                $op['downtime_reasons'][$reason] = 0;
            }
            $op['downtime_reasons'][$reason] += $dt->duration_minutes ?? 0;

            // Track QDC sessions
            if ($dt->reason === 'Ganti Tipe/Setting') {
                $op['qdc_count']++;
                $notes = json_decode($dt->notes, true);
                if ($notes && isset($notes['duration_seconds'])) {
                    $op['qdc_total_seconds'] += $notes['duration_seconds'];
                }
            }

            // Track machines
            if ($dt->machine_name && !in_array($dt->machine_name, $op['machines_used'])) {
                $op['machines_used'][] = $dt->machine_name;
            }

            // Track days
            $day = substr($dt->start_time, 0, 10);
            if (!in_array($day, $op['days_worked'])) {
                $op['days_worked'][] = $day;
            }
        }

        // From production orders (match operator from downtime records for now)
        // Link hourly reports to operators via production_order -> downtimes
        $orderOperatorMap = [];
        foreach ($downtimes as $dt) {
            if ($dt->production_gci_work_order_id || isset($dt->notes)) {
                // Try to find the production_order_id from notes (QDC) or link
                $notes = json_decode($dt->notes ?? '{}', true);
            }
        }

        // Map hourly reports to operators via machine + date match from downtimes
        foreach ($hourlyReports as $hr) {
            $po = $hr->productionOrder;
            if (!$po) continue;

            // Find operator who worked on this machine on this date
            $matchingDt = $downtimes->first(function ($dt) use ($po) {
                return $dt->machine_id == $po->machine_id
                    && substr($dt->start_time, 0, 10) === $po->plan_date;
            });

            if (!$matchingDt) continue;

            $name = $matchingDt->operator_name;
            if (!isset($operatorMap[$name])) continue;

            $op = &$operatorMap[$name];
            $op['total_output'] += $hr->actual ?? 0;
            $op['total_ng'] += $hr->ng ?? 0;
            $op['total_target'] += $hr->target ?? 0;
        }

        // Also count from production orders directly
        foreach ($orders as $order) {
            // Find operator from matching downtimes
            $matchingDt = $downtimes->first(function ($dt) use ($order) {
                return $dt->machine_id == $order->machine_id
                    && substr($dt->start_time, 0, 10) === $order->plan_date;
            });

            if (!$matchingDt) continue;

            $name = $matchingDt->operator_name;
            if (!isset($operatorMap[$name])) continue;

            $operatorMap[$name]['wo_count']++;
        }

        // ─── Compute KPIs ───
        $results = [];
        foreach ($operatorMap as $name => &$op) {
            $efficiency = $op['total_target'] > 0
                ? round(($op['total_output'] / $op['total_target']) * 100, 1)
                : 0;

            $ngRate = ($op['total_output'] + $op['total_ng']) > 0
                ? round(($op['total_ng'] / ($op['total_output'] + $op['total_ng'])) * 100, 2)
                : 0;

            $avgQdc = $op['qdc_count'] > 0
                ? round($op['qdc_total_seconds'] / $op['qdc_count'])
                : 0;

            $daysWorked = count($op['days_worked']);
            $avgOutputPerDay = $daysWorked > 0
                ? round($op['total_output'] / $daysWorked)
                : 0;

            $avgDowntimePerDay = $daysWorked > 0
                ? round($op['total_downtime_minutes'] / $daysWorked, 1)
                : 0;

            // Score: composite KPI (higher = better)
            // efficiency contributes positively, NG rate & downtime contribute negatively
            $score = max(0, $efficiency - ($ngRate * 2) - ($avgDowntimePerDay * 0.5));

            $results[] = [
                'name' => $name,
                'days_worked' => $daysWorked,
                'wo_count' => $op['wo_count'],
                'total_output' => $op['total_output'],
                'total_ng' => $op['total_ng'],
                'total_target' => $op['total_target'],
                'efficiency' => $efficiency,
                'ng_rate' => $ngRate,
                'total_downtime_minutes' => $op['total_downtime_minutes'],
                'downtime_count' => $op['downtime_count'],
                'avg_downtime_per_day' => $avgDowntimePerDay,
                'avg_output_per_day' => $avgOutputPerDay,
                'qdc_count' => $op['qdc_count'],
                'avg_qdc_seconds' => $avgQdc,
                'machines_used' => $op['machines_used'],
                'downtime_reasons' => $op['downtime_reasons'],
                'score' => round($score, 1),
            ];
        }

        // Sort by score descending
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        // Add rank
        foreach ($results as $i => &$r) {
            $r['rank'] = $i + 1;
        }

        // Summary totals
        $summary = [
            'total_operators' => count($results),
            'total_output' => array_sum(array_column($results, 'total_output')),
            'total_ng' => array_sum(array_column($results, 'total_ng')),
            'total_downtime' => array_sum(array_column($results, 'total_downtime_minutes')),
            'avg_efficiency' => count($results) > 0
                ? round(array_sum(array_column($results, 'efficiency')) / count($results), 1)
                : 0,
        ];

        return response()->json([
            'data' => $results,
            'summary' => $summary,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
