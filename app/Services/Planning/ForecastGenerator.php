<?php

namespace App\Services\Planning;

use App\Models\Forecast;
use Illuminate\Support\Facades\DB;

class ForecastGenerator
{
    public function generateForWeek(string $minggu): void
    {
        // Forecast is system-generated (no manual input), so regenerate cleanly per week.
        Forecast::query()->where('minggu', $minggu)->delete();

        $planningRows = DB::table('customer_planning_rows as r')
            ->join('customer_planning_imports as i', 'i.id', '=', 'r.import_id')
            ->join('customer_parts as cp', function ($join) {
                $join->on('cp.customer_id', '=', 'i.customer_id')
                    ->on('cp.customer_part_no', '=', 'r.customer_part_no');
            })
            ->join('customer_part_components as cpc', 'cpc.customer_part_id', '=', 'cp.id')
            ->join('gci_parts as gp', 'gp.id', '=', 'cpc.part_id')
            ->where('r.row_status', 'accepted')
            ->where('r.minggu', $minggu)
            ->select('cpc.part_id', DB::raw('SUM(r.qty * cpc.usage_qty) as qty'))
            ->groupBy('cpc.part_id')
            ->get();

        $planningByPart = $planningRows->pluck('qty', 'part_id')->map(fn ($v) => (float) $v)->all();

        $poDirect = DB::table('customer_pos as po')
            ->join('gci_parts as gp', 'gp.id', '=', 'po.part_id')
            ->whereNotNull('po.part_id')
            ->where('po.minggu', $minggu)
            ->where('po.status', 'open')
            ->select('po.part_id', DB::raw('SUM(po.qty) as qty'))
            ->groupBy('po.part_id')
            ->get();

        $poByPart = [];
        foreach ($poDirect as $row) {
            $poByPart[(int) $row->part_id] = ((float) $row->qty) + ($poByPart[(int) $row->part_id] ?? 0);
        }

        $partIds = collect(array_keys($planningByPart))
            ->merge(array_keys($poByPart))
            ->unique()
            ->values();

        foreach ($partIds as $partId) {
            $planningQty = (float) ($planningByPart[$partId] ?? 0);
            $poQty = (float) ($poByPart[$partId] ?? 0);
            $forecastQty = max($planningQty, $poQty);

            $source = 'planning';
            if ($planningQty <= 0 && $poQty > 0) {
                $source = 'po';
            } elseif ($planningQty > 0 && $poQty > 0) {
                $source = 'mixed';
            }

            Forecast::updateOrCreate(
                ['part_id' => $partId, 'minggu' => $minggu],
                [
                    'qty' => $forecastQty,
                    'planning_qty' => $planningQty,
                    'po_qty' => $poQty,
                    'source' => $source,
                ],
            );
        }
    }

    /**
     * Generate forecast from selected POs and Planning rows only
     */
    public function generateFromSelected(string $minggu, array $selectedPoIds, array $selectedPlanningIds): void
    {
        // Clear existing forecasts for this week
        Forecast::query()->where('minggu', $minggu)->delete();

        $planningByPart = [];
        $poByPart = [];

        // Process selected planning rows
        if (!empty($selectedPlanningIds)) {
            $planningRows = DB::table('customer_planning_rows as r')
                ->join('customer_planning_imports as i', 'i.id', '=', 'r.import_id')
                ->join('customer_parts as cp', function ($join) {
                    $join->on('cp.customer_id', '=', 'i.customer_id')
                        ->on('cp.customer_part_no', '=', 'r.customer_part_no');
                })
                ->join('customer_part_components as cpc', 'cpc.customer_part_id', '=', 'cp.id')
                ->whereIn('r.id', $selectedPlanningIds)
                ->where('r.row_status', 'accepted')
                ->where('r.minggu', $minggu)
                ->select('cpc.part_id', DB::raw('SUM(r.qty * cpc.usage_qty) as qty'))
                ->groupBy('cpc.part_id')
                ->get();

            $planningByPart = $planningRows->pluck('qty', 'part_id')->map(fn ($v) => (float) $v)->all();
        }

        // Process selected POs
        if (!empty($selectedPoIds)) {
            $poDirect = DB::table('customer_pos as po')
                ->whereIn('po.id', $selectedPoIds)
                ->whereNotNull('po.part_id')
                ->where('po.minggu', $minggu)
                ->where('po.status', 'open')
                ->select('po.part_id', DB::raw('SUM(po.qty) as qty'))
                ->groupBy('po.part_id')
                ->get();

            foreach ($poDirect as $row) {
                $poByPart[(int) $row->part_id] = ((float) $row->qty) + ($poByPart[(int) $row->part_id] ?? 0);
            }
        }

        // Merge and create forecasts
        $partIds = collect(array_keys($planningByPart))
            ->merge(array_keys($poByPart))
            ->unique()
            ->values();

        foreach ($partIds as $partId) {
            $planningQty = (float) ($planningByPart[$partId] ?? 0);
            $poQty = (float) ($poByPart[$partId] ?? 0);
            $forecastQty = max($planningQty, $poQty);

            $source = 'planning';
            if ($planningQty <= 0 && $poQty > 0) {
                $source = 'po';
            } elseif ($planningQty > 0 && $poQty > 0) {
                $source = 'mixed';
            }

            Forecast::updateOrCreate(
                ['part_id' => $partId, 'minggu' => $minggu],
                [
                    'qty' => $forecastQty,
                    'planning_qty' => $planningQty,
                    'po_qty' => $poQty,
                    'source' => $source,
                ],
            );
        }
    }
}
