<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Models\ProductionOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MachineLoadController extends Controller
{
    public function index(Request $request)
    {
        $date = Carbon::parse($request->get('date', now()->format('Y-m-d')));

        // Get all active machines
        $machines = Machine::active()->orderBy('name')->get();

        // Get all WOs for this date, grouped by machine_id
        $orders = ProductionOrder::where('plan_date', $date->format('Y-m-d'))
            ->whereNotNull('machine_id')
            ->whereNotIn('status', ['completed'])
            ->with(['part', 'machine'])
            ->get()
            ->groupBy('machine_id');

        $machineLoads = [];
        $totalOverloaded = 0;
        $totalWarning = 0;
        $totalLoadPercents = [];

        foreach ($machines as $machine) {
            $machineOrders = $orders->get($machine->id, collect());
            $woCount = $machineOrders->count();

            // Calculate planned hours
            $plannedHours = 0;
            $maxShift = 1;
            foreach ($machineOrders as $wo) {
                $plannedHours += $machine->estimateHours((float) $wo->qty_planned);
                if ($wo->shift && $wo->shift > $maxShift) {
                    $maxShift = $wo->shift;
                }
            }

            $capacityHours = (float) $machine->available_hours_per_shift * $maxShift;
            $loadPercent = $capacityHours > 0 ? round(($plannedHours / $capacityHours) * 100, 1) : 0;

            if ($loadPercent > 100) {
                $status = 'overload';
                $totalOverloaded++;
            } elseif ($loadPercent >= 85) {
                $status = 'warning';
                $totalWarning++;
            } else {
                $status = 'normal';
            }

            if ($woCount > 0) {
                $totalLoadPercents[] = $loadPercent;
            }

            $machineLoads[] = [
                'machine' => $machine,
                'wo_count' => $woCount,
                'planned_hours' => round($plannedHours, 2),
                'capacity_hours' => round($capacityHours, 2),
                'max_shift' => $maxShift,
                'load_percent' => $loadPercent,
                'status' => $status,
            ];
        }

        // Sort: overloaded first, then warning, then by load% desc
        usort($machineLoads, function ($a, $b) {
            $statusOrder = ['overload' => 0, 'warning' => 1, 'normal' => 2];
            $sa = $statusOrder[$a['status']] ?? 3;
            $sb = $statusOrder[$b['status']] ?? 3;
            if ($sa !== $sb) return $sa - $sb;
            return $b['load_percent'] <=> $a['load_percent'];
        });

        $avgLoad = count($totalLoadPercents) > 0
            ? round(array_sum($totalLoadPercents) / count($totalLoadPercents), 1)
            : 0;

        $machinesWithWo = count($totalLoadPercents);

        return view('production.machine-load.index', compact(
            'machineLoads',
            'date',
            'totalOverloaded',
            'totalWarning',
            'avgLoad',
            'machinesWithWo'
        ));
    }

    public function show(Request $request, Machine $machine)
    {
        $date = Carbon::parse($request->get('date', now()->format('Y-m-d')));

        $orders = ProductionOrder::where('plan_date', $date->format('Y-m-d'))
            ->where('machine_id', $machine->id)
            ->whereNotIn('status', ['completed'])
            ->with('part')
            ->orderBy('production_sequence')
            ->orderBy('id')
            ->get();

        $totalPlannedHours = 0;
        $maxShift = 1;
        $orderDetails = [];

        foreach ($orders as $wo) {
            $estHours = $machine->estimateHours((float) $wo->qty_planned);
            $totalPlannedHours += $estHours;
            if ($wo->shift && $wo->shift > $maxShift) {
                $maxShift = $wo->shift;
            }

            $orderDetails[] = [
                'order' => $wo,
                'est_hours' => round($estHours, 2),
            ];
        }

        $capacityHours = (float) $machine->available_hours_per_shift * $maxShift;
        $loadPercent = $capacityHours > 0 ? round(($totalPlannedHours / $capacityHours) * 100, 1) : 0;

        if ($loadPercent > 100) {
            $status = 'overload';
        } elseif ($loadPercent >= 85) {
            $status = 'warning';
        } else {
            $status = 'normal';
        }

        return view('production.machine-load.show', compact(
            'machine',
            'date',
            'orderDetails',
            'totalPlannedHours',
            'capacityHours',
            'maxShift',
            'loadPercent',
            'status'
        ));
    }
}
