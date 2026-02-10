<?php

namespace App\Services\Planning;

use App\Models\Forecast;
use Illuminate\Support\Facades\DB;

class ForecastGenerator
{
    public function generateForWeek(string $period): void
    {
        // Forecast is system-generated (no manual input), so regenerate cleanly per period.
        Forecast::query()->where('period', $period)->delete();

        $planningRows = DB::table('customer_planning_rows as r')
            ->join('customer_planning_imports as i', 'i.id', '=', 'r.import_id')
            ->leftJoin('customer_parts as cp', function ($join) {
                $join->on('cp.customer_id', '=', 'i.customer_id')
                    ->on('cp.customer_part_no', '=', 'r.customer_part_no');
            })
            ->leftJoin('customer_part_components as cpc', 'cpc.customer_part_id', '=', 'cp.id')
            ->where('r.row_status', 'accepted')
            ->where('r.period', $period)
            ->where(function ($q) {
                $q->whereNotNull('cpc.gci_part_id')
                    ->orWhereNotNull('r.part_id');
            })
            ->select([
                DB::raw('COALESCE(cpc.gci_part_id, r.part_id) as part_id'),
                DB::raw('SUM(r.qty * COALESCE(cpc.qty_per_unit, 1.0)) as qty')
            ])
            ->groupBy(DB::raw('COALESCE(cpc.gci_part_id, r.part_id)'))
            ->get();

        $planningByPart = $planningRows->pluck('qty', 'part_id')->map(fn($v) => (float) $v)->all();

        $poDirect = DB::table('customer_pos as po')
            ->join('gci_parts as gp', 'gp.id', '=', 'po.part_id')
            ->whereNotNull('po.part_id')
            ->where('po.period', $period)
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
                ['part_id' => $partId, 'period' => $period],
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
    public function generateFromSelected(?string $period, array $selectedPoIds, array $selectedPlanningIds, array $selectedImportIds = []): void
    {
        // If period is null, find all periods present in selection
        $periods = [];
        if ($period) {
            $periods[] = $period;
        } else {
            $periodsFromPos = DB::table('customer_pos')->whereIn('id', $selectedPoIds)->pluck('period')->toArray();
            $periodsFromPlanning = DB::table('customer_planning_rows')->whereIn('id', $selectedPlanningIds)->pluck('period')->toArray();

            $periodsFromImports = [];
            if (!empty($selectedImportIds)) {
                $periodsFromImports = DB::table('customer_planning_rows')
                    ->whereIn('import_id', $selectedImportIds)
                    ->pluck('period')
                    ->toArray();
            }

            $periods = array_unique(array_merge($periodsFromPos, $periodsFromPlanning, $periodsFromImports));
        }

        foreach ($periods as $w) {
            // Clear existing forecasts for this specific period before regenerating
            Forecast::query()->where('period', $w)->delete();

            $planningByPart = [];
            $poByPart = [];

            // Process selected planning rows/imports for THIS week
            if (!empty($selectedPlanningIds) || !empty($selectedImportIds)) {
                $query = DB::table('customer_planning_rows as r')
                    ->join('customer_planning_imports as i', 'i.id', '=', 'r.import_id')
                    ->leftJoin('customer_parts as cp', function ($join) {
                        $join->on('cp.customer_id', '=', 'i.customer_id')
                            ->on('cp.customer_part_no', '=', 'r.customer_part_no');
                    })
                    ->leftJoin('customer_part_components as cpc', 'cpc.customer_part_id', '=', 'cp.id')
                    ->where('r.period', $w)
                    ->where('r.row_status', 'accepted')
                    ->where(function ($q) {
                        $q->whereNotNull('cpc.gci_part_id')
                            ->orWhereNotNull('r.part_id');
                    });

                if (!empty($selectedPlanningIds) && !empty($selectedImportIds)) {
                    $query->where(function ($q) use ($selectedPlanningIds, $selectedImportIds) {
                        $q->whereIn('r.id', $selectedPlanningIds)
                            ->orWhereIn('r.import_id', $selectedImportIds);
                    });
                } elseif (!empty($selectedPlanningIds)) {
                    $query->whereIn('r.id', $selectedPlanningIds);
                } else {
                    $query->whereIn('r.import_id', $selectedImportIds);
                }

                $planningRows = $query->select([
                    DB::raw('COALESCE(cpc.gci_part_id, r.part_id) as part_id'),
                    DB::raw('SUM(r.qty * COALESCE(cpc.qty_per_unit, 1.0)) as qty')
                ])
                    ->groupBy(DB::raw('COALESCE(cpc.gci_part_id, r.part_id)'))
                    ->get();

                $planningByPart = $planningRows->pluck('qty', 'part_id')->map(fn($v) => (float) $v)->all();
            }

            // Process selected POs for THIS week
            if (!empty($selectedPoIds)) {
                $poDirect = DB::table('customer_pos as po')
                    ->whereIn('po.id', $selectedPoIds)
                    ->where('po.period', $w)
                    ->whereNotNull('po.part_id')
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
                    ['part_id' => $partId, 'period' => $w],
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
}
