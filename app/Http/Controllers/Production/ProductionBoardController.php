<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProductionBoardController extends Controller
{
    public function index(Request $request)
    {
        $date = Carbon::parse($request->query('date', now()->toDateString()))->startOfDay();

        $orders = ProductionOrder::query()
            ->with(['part:id,part_no,part_name,model', 'machine:id,name,code', 'hourlyReports.machine:id,name,code'])
            ->whereDate('plan_date', $date->toDateString())
            ->orderByRaw("FIELD(status, 'in_production', 'paused', 'released', 'kanban_released', 'planned', 'completed', 'cancelled')")
            ->orderBy('shift')
            ->orderBy('production_sequence')
            ->orderBy('id')
            ->get();

        $rows = $orders->map(function (ProductionOrder $order) {
            $reports = $order->hourlyReports;
            $targetQty = (float) ($order->qty_planned ?? $order->target_qty ?? 0);
            $okQty = (float) $reports->sum(fn ($report) => (float) ($report->actual ?? 0));
            $ngQty = (float) $reports->sum(fn ($report) => (float) ($report->ng ?? 0));
            $fgQty = (float) $reports
                ->where('output_type', 'fg')
                ->sum(fn ($report) => (float) ($report->actual ?? 0));
            $wipQty = (float) $reports
                ->where('output_type', 'wip')
                ->sum(fn ($report) => (float) ($report->actual ?? 0));

            $shiftCells = collect([1, 2, 3])->mapWithKeys(function (int $shiftNo) use ($reports) {
                $shiftReports = $reports->filter(function ($report) use ($shiftNo) {
                    $shift = preg_replace('/[^0-9]/', '', (string) ($report->shift ?? ''));
                    return (int) $shift === $shiftNo;
                });

                $processes = $shiftReports
                    ->pluck('process_name')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $machines = $shiftReports
                    ->map(fn ($report) => $report->machine_name ?: optional($report->machine)->name)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                return [
                    $shiftNo => [
                        'ok' => (float) $shiftReports->sum(fn ($report) => (float) ($report->actual ?? 0)),
                        'ng' => (float) $shiftReports->sum(fn ($report) => (float) ($report->ng ?? 0)),
                        'wip' => (float) $shiftReports->where('output_type', 'wip')->sum(fn ($report) => (float) ($report->actual ?? 0)),
                        'fg' => (float) $shiftReports->where('output_type', 'fg')->sum(fn ($report) => (float) ($report->actual ?? 0)),
                        'processes' => $processes,
                        'machines' => $machines,
                    ],
                ];
            })->all();

            $currentProcess = trim((string) ($order->process_name ?? ''));
            if ($currentProcess === '') {
                $currentProcess = $reports->sortByDesc('created_at')->first()?->process_name ?? '-';
            }

            $actualMachines = $reports
                ->map(fn ($report) => $report->machine_name ?: optional($report->machine)->name)
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (empty($actualMachines) && $order->machine) {
                $actualMachines[] = $order->machine->name;
            }

            $progressBase = $fgQty > 0 ? $fgQty : $okQty;

            return [
                'order' => $order,
                'wo_number' => $order->production_order_number ?? $order->transaction_no ?? '-',
                'part_no' => $order->part?->part_no ?? '-',
                'part_name' => $order->part?->part_name ?? '-',
                'model' => $order->part?->model ?? '-',
                'target_qty' => $targetQty,
                'ok_qty' => $okQty,
                'ng_qty' => $ngQty,
                'fg_qty' => $fgQty,
                'wip_qty' => $wipQty,
                'progress_percent' => $targetQty > 0 ? min(100, round(($progressBase / $targetQty) * 100)) : 0,
                'current_process' => $currentProcess,
                'actual_machines' => $actualMachines,
                'shift_cells' => $shiftCells,
                'status' => (string) ($order->status ?? '-'),
            ];
        });

        $summary = [
            'wo_count' => $orders->count(),
            'running' => $orders->where('status', 'in_production')->count(),
            'paused' => $orders->where('status', 'paused')->count(),
            'completed' => $orders->where('status', 'completed')->count(),
            'target_qty' => (float) $rows->sum('target_qty'),
            'ok_qty' => (float) $rows->sum('ok_qty'),
            'ng_qty' => (float) $rows->sum('ng_qty'),
        ];

        return view('production.board.index', compact('date', 'rows', 'summary'));
    }
}
