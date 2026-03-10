<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionDowntime;
use App\Models\ProductionGciDowntime;
use Illuminate\Http\Request;

class QdcHistoryController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->query('date_from', now()->subDays(7)->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());
        $category = $request->query('category');
        $machine = $request->query('machine');
        $source = $request->query('source', 'all'); // 'all', 'wo', 'app'

        // Production Order downtimes
        $woDowntimes = collect();
        if ($source === 'all' || $source === 'wo') {
            $woQuery = ProductionDowntime::query()
                ->with(['productionOrder.part', 'productionOrder.machine', 'creator'])
                ->whereHas('productionOrder')
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo);

            if ($category) {
                $woQuery->where('category', $category);
            }
            if ($machine) {
                $woQuery->whereHas('productionOrder', fn($q) => $q->where('machine_id', $machine));
            }

            $woDowntimes = $woQuery->get()->map(fn($dt) => (object) [
                'source' => 'wo',
                'date' => $dt->created_at,
                'machine_name' => $dt->productionOrder?->machine?->name ?? '-',
                'wo_no' => $dt->productionOrder?->transaction_no,
                'wo_id' => $dt->productionOrder?->id,
                'part_no' => $dt->productionOrder?->part?->part_no ?? '-',
                'part_name' => $dt->productionOrder?->part?->part_name,
                'category' => $dt->category,
                'start_time' => $dt->start_time,
                'end_time' => $dt->end_time,
                'duration_minutes' => $dt->duration_minutes,
                'notes' => $dt->notes,
                'operator' => $dt->creator?->name ?? '-',
                'shift' => null,
            ]);
        }

        // Flutter app downtimes (machine-based)
        $appDowntimes = collect();
        if ($source === 'all' || $source === 'app') {
            $appQuery = ProductionGciDowntime::query()
                ->with('machine')
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo);

            if ($category) {
                $appQuery->where('reason', $category);
            }
            if ($machine) {
                $appQuery->where('machine_id', $machine);
            }

            $appDowntimes = $appQuery->get()->map(fn($dt) => (object) [
                'source' => 'app',
                'date' => $dt->created_at,
                'machine_name' => $dt->machine?->name ?? $dt->machine_name ?? '-',
                'wo_no' => null,
                'wo_id' => null,
                'part_no' => '-',
                'part_name' => null,
                'category' => $dt->reason,
                'start_time' => $dt->start_time,
                'end_time' => $dt->end_time,
                'duration_minutes' => $dt->duration_minutes,
                'notes' => $dt->notes,
                'operator' => '-',
                'shift' => $dt->shift,
            ]);
        }

        $allDowntimes = $woDowntimes->merge($appDowntimes)
            ->sortByDesc('date')
            ->values();

        // Paginate manually
        $page = (int) $request->query('page', 1);
        $perPage = 50;
        $paginatedItems = $allDowntimes->forPage($page, $perPage)->values();
        $downtimes = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems->all(), $allDowntimes->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Collect unique categories from both sources
        $categories = ProductionDowntime::select('category')->distinct()->pluck('category')
            ->merge(ProductionGciDowntime::select('reason')->distinct()->pluck('reason'))
            ->unique()
            ->sort()
            ->values();

        $machines = \App\Models\Machine::orderBy('name')->get(['id', 'name']);

        return view('production.qdc-history.index', compact(
            'downtimes', 'dateFrom', 'dateTo', 'category', 'machine', 'source',
            'categories', 'machines',
        ));
    }
}
