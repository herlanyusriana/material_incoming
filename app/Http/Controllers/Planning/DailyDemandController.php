<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\OutgoingDailyPlan;
use App\Models\OutgoingDailyPlanRow;
use Illuminate\Http\Request;

/**
 * Daily Demand by Customer — read-only report over the outgoing daily plan
 * cells (outgoing_daily_plan_cells). Demand entered in Daily Planning
 * (Outgoing) is aggregated per customer per day here; twins/components of
 * the same part are merged so nothing is double counted.
 */
class DailyDemandController extends Controller
{
    public function index(Request $request)
    {
        $plans = OutgoingDailyPlan::query()
            ->orderByDesc('date_from')
            ->orderByDesc('id')
            ->limit(30)
            ->get(['id', 'date_from', 'date_to']);

        $planId = $request->filled('plan_id') ? (int) $request->query('plan_id') : null;
        $plan = $planId ? $plans->firstWhere('id', $planId) ?? OutgoingDailyPlan::find($planId) : $plans->first();

        $days = collect();
        $buckets = [];
        $customers = collect();
        $grandTotal = 0.0;
        $truncated = false;

        if ($plan) {
            $from = $plan->date_from->copy();
            $to = $plan->date_to->copy();
            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }

            $days = collect();
            $cursor = $from->copy();
            while ($cursor->lte($to)) {
                $days->push($cursor->copy());
                $cursor->addDay();
            }

            // Keep wide grids sane; totals still cover all loaded cells.
            if ($days->count() > 31) {
                $days = $days->take(31);
                $truncated = true;
            }
            $dayKeys = $days->map(fn ($d) => $d->toDateString())->all();

            $rows = OutgoingDailyPlanRow::query()
                ->where('plan_id', $plan->id)
                ->with([
                    'gciPart:id,part_no,part_name',
                    'customerPart:id,customer_id,customer_part_no,customer_part_name',
                    'customerPart.customer:id,name',
                    'cells',
                ])
                ->orderBy('row_no')
                ->orderBy('id')
                ->get();

            // Merge twin/component rows that represent the same demand line.
            $groups = $rows->groupBy(fn ($r) => implode('|', [
                $r->production_line,
                $r->part_no,
                $r->customer_part_id,
            ]));

            foreach ($groups as $group) {
                $master = $group->first();
                $customerName = $master->customerPart?->customer?->name;
                $customerKey = $master->customerPart?->customer_id ?: 'unmapped';

                $qtyByDay = array_fill_keys($dayKeys, 0.0);
                $total = 0.0;
                foreach ($group as $row) {
                    foreach ($row->cells as $cell) {
                        $qty = (float) ($cell->qty ?? 0);
                        $total += $qty;
                        $dateKey = $cell->plan_date?->toDateString();
                        if ($dateKey && array_key_exists($dateKey, $qtyByDay)) {
                            $qtyByDay[$dateKey] += $qty;
                        }
                    }
                }

                if ($total == 0.0) {
                    continue;
                }

                if (! isset($buckets[$customerKey])) {
                    $buckets[$customerKey] = [
                        'name' => $customerName ?: __('daily_demand.unmapped'),
                        'total' => 0.0,
                        'qty_by_day' => array_fill_keys($dayKeys, 0.0),
                        'parts' => [],
                    ];
                }

                $buckets[$customerKey]['total'] += $total;
                foreach ($qtyByDay as $d => $q) {
                    $buckets[$customerKey]['qty_by_day'][$d] += $q;
                }
                $buckets[$customerKey]['parts'][] = [
                    'label' => $master->part_no ?: $master->customerPart?->customer_part_no ?: '—',
                    'name' => $master->gciPart?->part_name ?: $master->customerPart?->customer_part_name,
                    'line' => $master->production_line,
                    'qty_by_day' => $qtyByDay,
                    'total' => $total,
                ];

                $grandTotal += $total;
            }

            foreach ($buckets as &$bucket) {
                usort($bucket['parts'], fn ($a, $b) => $b['total'] <=> $a['total']);
            }
            unset($bucket);

            $customers = collect(array_values($buckets))
                ->sortByDesc('total')
                ->values();
        }

        return view('planning.daily-demand.index', [
            'plans' => $plans,
            'plan' => $plan,
            'days' => $days,
            'customers' => $customers,
            'grandTotal' => $grandTotal,
            'truncated' => $truncated,
        ]);
    }
}
