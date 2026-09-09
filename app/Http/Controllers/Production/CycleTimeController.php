<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\ProductionOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Cycle Time by Machine — actual (from work orders + downtime) vs
 * standard (machine master cycle_time). Performance below WARNING_THRESHOLD
 * is flagged, mirroring the "Attention" KPI from the launcher design.
 */
class CycleTimeController extends Controller
{
    public const WARNING_THRESHOLD = 80;

    public function index(Request $request)
    {
        $from = $this->parseDate($request->query('from'), now()->startOfMonth());
        $to = $this->parseDate($request->query('to'), now());
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        $machineId = $request->filled('machine_id') ? (int) $request->query('machine_id') : null;

        $orders = ProductionOrder::query()
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('machine_id')
            ->whereBetween('plan_date', [$from->toDateString(), $to->toDateString()])
            ->when($machineId, fn ($q) => $q->where('machine_id', $machineId))
            ->with([
                'machine:id,name,code,cycle_time,cycle_time_unit',
                'part:id,part_no,part_name',
                'downtimes:id,production_order_id,duration_minutes',
            ])
            ->orderBy('plan_date')
            ->get();

        $rows = $orders
            ->groupBy('machine_id')
            ->map(function ($machineOrders) {
                $machine = $machineOrders->first()->machine;
                $stdSec = $machine ? $machine->getCycleTimeInSeconds() : 0.0;

                $qty = 0.0;
                $runMinutes = 0.0;
                $stdMinutes = 0.0;
                $downtimeMinutes = 0.0;
                $byPart = [];

                foreach ($machineOrders as $order) {
                    $orderQty = (float) ($order->qty_actual ?? 0);
                    $orderDowntime = (float) $order->downtimes->sum(fn ($d) => (float) ($d->duration_minutes ?? 0));
                    $orderMinutes = max($this->spanMinutes($order) - $orderDowntime, 0.0);

                    $qty += $orderQty;
                    $runMinutes += $orderMinutes;
                    $downtimeMinutes += $orderDowntime;
                    if ($stdSec > 0 && $orderQty > 0) {
                        $stdMinutes += $stdSec * $orderQty / 60;
                    }

                    if ($orderMinutes > 0 && $orderQty > 0) {
                        $key = (int) $order->gci_part_id;
                        $byPart[$key] ??= [
                            'part_no' => $order->part?->part_no,
                            'part_name' => $order->part?->part_name,
                            'qty' => 0.0,
                            'minutes' => 0.0,
                        ];
                        $byPart[$key]['qty'] += $orderQty;
                        $byPart[$key]['minutes'] += $orderMinutes;
                    }
                }

                $actualSec = $qty > 0 && $runMinutes > 0 ? $runMinutes * 60 / $qty : null;
                $performance = $stdMinutes > 0 && $runMinutes > 0 ? $stdMinutes / $runMinutes * 100 : null;

                foreach ($byPart as &$partRow) {
                    $partRow['actual_sec'] = $partRow['minutes'] * 60 / $partRow['qty'];
                    $partRow['performance'] = $stdSec > 0 && $partRow['actual_sec'] > 0
                        ? $stdSec / $partRow['actual_sec'] * 100
                        : null;
                }
                unset($partRow);

                return (object) [
                    'machine_id' => $machine?->id,
                    'machine_name' => $machine?->name ?? (string) $machine?->code,
                    'machine_code' => $machine?->code,
                    'unit' => $machine?->cycle_time_unit === 'minute' ? 'min' : 'sec',
                    'std_sec_per_pc' => $stdSec,
                    'orders' => $machineOrders->count(),
                    'qty' => $qty,
                    'run_minutes' => $runMinutes,
                    'std_minutes' => $stdMinutes,
                    'downtime_minutes' => $downtimeMinutes,
                    'actual_sec_per_pc' => $actualSec,
                    'performance' => $performance,
                    'warning' => $performance !== null && $performance < self::WARNING_THRESHOLD,
                    'by_part' => collect($byPart)->sortByDesc('qty')->values(),
                ];
            })
            ->sortBy(fn ($row) => [$row->performance === null ? 1 : 0, $row->performance ?? 0])
            ->values();

        $measured = $rows->filter(fn ($row) => $row->performance !== null);

        return view('production.cycle-time.index', [
            'rows' => $rows,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'machineId' => $machineId,
            ],
            'machines' => Machine::orderBy('name')->get(['id', 'name', 'code']),
            'summary' => [
                'machines' => $rows->count(),
                'avg_performance' => $measured->avg('performance'),
                'flagged' => $rows->where('warning', true)->count(),
                'total_qty' => $rows->sum('qty'),
            ],
            'threshold' => self::WARNING_THRESHOLD,
        ]);
    }

    private function parseDate(?string $value, Carbon $fallback): Carbon
    {
        if (! $value) {
            return $fallback;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * Raw span between start/end (downtime is subtracted by the caller).
     */
    private function spanMinutes(ProductionOrder $order): float
    {
        if (! $order->start_time || ! $order->end_time) {
            return 0.0;
        }

        $start = Carbon::parse($order->start_time);
        $end = Carbon::parse($order->end_time);
        if ($end->lt($start)) {
            $end->addDay();
        }

        $minutes = $start->diffInSeconds($end) / 60;

        return max($minutes, 0.0);
    }
}
