<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionDowntime;
use App\Models\ProductionGciDowntime;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class QdcHistoryController extends Controller
{
    // Downtime = masalah mesin/teknis
    private const DOWNTIME_REASONS = [
        'Mesin Rusak',
        'Robot Trouble',
        'Dies Trouble',
        'Material NG Quality',
        'Tooling Trouble',
        'Listrik Trouble / Mati Lampu',
        'Maintenance',
        'Breakdown Mesin',
        'Perbaikan Coil',
        'Material Kendor/Jatuh',
        'Tunggu Material',
        'Quality Check',
    ];

    // QDC = aktivitas rutin/planned
    private const QDC_REASONS = [
        'Ganti Type',
        'Ganti Tipe/Setting',
        'Ganti Material / Reffil Material',
        'Cleaning Machine',
        'Cleaning',
        'Briefing',
        'Trial',
        'Istirahat',
    ];

    private function getDowntimes(Request $request, ?string $type = null)
    {
        $dateFrom = $request->query('date_from', now()->subDays(7)->toDateString());
        $dateTo = $request->query('date_to', now()->toDateString());
        $category = $request->query('category');
        $machine = $request->query('machine');
        $source = $request->query('source', 'all');

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
                'refill_part_no' => null,
                'refill_part_name' => null,
                'refill_qty' => null,
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
                'operator' => $dt->operator_name ?? '-',
                'shift' => $dt->shift,
                'refill_part_no' => $dt->refill_part_no,
                'refill_part_name' => $dt->refill_part_name,
                'refill_qty' => $dt->refill_qty,
            ]);
        }

        $allDowntimes = collect($woDowntimes->all())->merge($appDowntimes->all());

        // Filter by type (downtime vs qdc)
        if ($type === 'downtime') {
            $allDowntimes = $allDowntimes->filter(function ($dt) {
                $cat = $dt->category;
                return !in_array($cat, self::QDC_REASONS) && strtolower($cat) !== 'istirahat' && strtolower($cat) !== 'lainnya';
            });
        } elseif ($type === 'qdc') {
            $allDowntimes = $allDowntimes->filter(function ($dt) {
                $cat = strtolower($dt->category ?? '');
                return in_array($dt->category, self::QDC_REASONS) || $cat === 'istirahat';
            });
        }

        $allDowntimes = $allDowntimes->sortByDesc('date')->values();

        return [
            'allDowntimes' => $allDowntimes,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'category' => $category,
            'machine' => $machine,
            'source' => $source,
            'type' => $type,
        ];
    }

    public function downtimeIndex(Request $request)
    {
        return $this->buildIndex($request, 'downtime');
    }

    public function index(Request $request)
    {
        return $this->buildIndex($request, 'qdc');
    }

    private function buildIndex(Request $request, string $type)
    {
        $result = $this->getDowntimes($request, $type);
        $allDowntimes = $result['allDowntimes'];

        // Paginate manually
        $page = (int) $request->query('page', 1);
        $perPage = 50;
        $paginatedItems = $allDowntimes->forPage($page, $perPage)->values();
        $downtimes = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems->all(), $allDowntimes->count(), $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Categories based on type
        if ($type === 'downtime') {
            $categories = collect(self::DOWNTIME_REASONS);
        } elseif ($type === 'qdc') {
            $categories = collect(self::QDC_REASONS);
        } else {
            $categories = ProductionDowntime::select('category')->distinct()->pluck('category')
                ->merge(ProductionGciDowntime::select('reason')->distinct()->pluck('reason'))
                ->unique()
                ->sort()
                ->values();
        }

        $machines = \App\Models\Machine::orderBy('name')->get(['id', 'name']);

        $totalMinutes = $allDowntimes->sum('duration_minutes');
        $totalCount = $allDowntimes->count();

        $routePrefix = $type === 'downtime' ? 'production.downtime-history' : 'production.qdc-history';

        return view('production.qdc-history.index', compact(
            'downtimes', 'categories', 'machines', 'type', 'routePrefix', 'totalMinutes', 'totalCount',
        ) + $result);
    }

    public function downtimePdf(Request $request)
    {
        return $this->buildPdf($request, 'downtime');
    }

    public function pdf(Request $request)
    {
        return $this->buildPdf($request, 'qdc');
    }

    private function buildPdf(Request $request, string $type)
    {
        $result = $this->getDowntimes($request, $type);
        $allDowntimes = $result['allDowntimes'];

        $totalMinutes = $allDowntimes->sum('duration_minutes');
        $totalCount = $allDowntimes->count();

        // Group by machine
        $machineGroups = $allDowntimes->groupBy('machine_name')->map(function ($items, $machineName) {
            $byCategory = $items->groupBy('category')->map(fn($catItems, $cat) => (object) [
                'category' => $cat,
                'count' => $catItems->count(),
                'total_minutes' => $catItems->sum('duration_minutes'),
            ])->sortByDesc('total_minutes')->values();

            return (object) [
                'machine_name' => $machineName,
                'downtimes' => $items->sortByDesc('date')->values(),
                'count' => $items->count(),
                'total_minutes' => $items->sum('duration_minutes'),
                'by_category' => $byCategory,
            ];
        })->sortByDesc('total_minutes')->values();

        $machineName = null;
        if ($result['machine']) {
            $machineName = \App\Models\Machine::find($result['machine'])?->name;
        }

        $title = $type === 'downtime' ? 'Downtime_History' : 'QDC_History';

        $pdf = Pdf::loadView('production.qdc-history.pdf', compact(
            'machineGroups', 'totalMinutes', 'totalCount', 'machineName', 'type',
        ) + $result);

        $pdf->setPaper('a4', 'landscape');

        $filename = $title . '_' . $result['dateFrom'] . '_to_' . $result['dateTo'] . '.pdf';
        return $pdf->download($filename);
    }
}
