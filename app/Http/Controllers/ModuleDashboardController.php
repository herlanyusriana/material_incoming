<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ModuleDashboardController extends Controller
{
    public function show(Request $request, string $module)
    {
        // Bookmark lama /module/warehouse tetap jalan setelah rename ke inventory.
        if ($module === 'warehouse') {
            return redirect()->route('module.dashboard', ['module' => 'inventory']);
        }

        $user = $request->user();

        $definition = Menu::get($module);

        if (! $definition) {
            throw new NotFoundHttpException;
        }

        abort_unless(Menu::visibleItems($user, $module) !== [], 403);

        // Modul bertab (inventory): filter tile menu per kategori aktif (FG/RM/WIP).
        // Item tanpa key "categories" = tool lintas kategori, tampil di baris terpisah.
        $categories = $definition['categories'] ?? [];
        $tools = [];
        if ($categories !== []) {
            $activeCategory = $request->query('cat');
            if (! isset($categories[$activeCategory])) {
                $activeCategory = array_key_first($categories);
            }

            $allItems = Menu::visibleItems($user, $module);
            $items = array_values(array_filter(
                $allItems,
                fn (array $item) => in_array($activeCategory, $item['categories'] ?? [], true),
            ));
            $tools = array_values(array_filter(
                $allItems,
                fn (array $item) => ! array_key_exists('categories', $item),
            ));
        } else {
            $activeCategory = null;
            $items = Menu::visibleItems($user, $module);
        }

        $stats = $this->stats($module);
        $charts = $stats['charts'] ?? [];
        if ($charts === [] && ! empty($stats['chart'])) {
            $charts = [$stats['chart']];
        }

        return view('module.dashboard', [
            'module' => $definition + ['key' => $module],
            'items' => $items,
            'tools' => $tools,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'stats' => $stats['stats'] ?? [],
            'chart' => $stats['chart'] ?? null,
            'charts' => $charts,
            'attention' => $stats['attention'] ?? null,
        ]);
    }

    /**
     * Per-module KPI stats, chart config and "need attention" table.
     */
    protected function stats(string $module): array
    {
        return match ($module) {
            'master-data' => $this->masterDataStats(),
            'marketing' => $this->marketingStats(),
            'planning' => $this->planningStats(),
            'purchasing' => $this->purchasingStats(),
            'inventory' => $this->warehouseStats(),
            'production' => $this->productionStats(),
            'outside-process' => $this->outsideProcessStats(),
            'admin' => $this->adminStats(),
            default => [],
        };
    }

    protected function table(string $name): bool
    {
        static $cached = [];

        return $cached[$name] ??= DB::getSchemaBuilder()->hasTable($name);
    }

    protected function count(string $table, $query = null): int
    {
        if (! $this->table($table)) {
            return 0;
        }

        return $query
            ? (int) (clone $query)->count()
            : (int) DB::table($table)->count();
    }

    // ---------------------------------------------------------------
    // Chart helpers — aman: return null bila tabel/kolom tidak ada.
    // ---------------------------------------------------------------

    protected const CHART_COLORS = [
        'indigo' => '#4F46E5',
        'emerald' => '#10B981',
        'amber' => '#F59E0B',
        'red' => '#EF4444',
        'sky' => '#0EA5E9',
        'violet' => '#8B5CF6',
        'slate' => '#64748B',
        'orange' => '#F97316',
        'teal' => '#14B8A6',
    ];

    protected function hasColumn(string $table, string $column): bool
    {
        try {
            return $this->table($table) && DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array{labels: array, data: array} */
    protected function monthlySeries(string $table, int $months = 6, string $dateColumn = 'created_at'): array
    {
        $labels = [];
        $data = [];
        try {
            if (! $this->hasColumn($table, $dateColumn)) {
                return ['labels' => [], 'data' => []];
            }
            for ($i = $months - 1; $i >= 0; $i--) {
                $m = now()->subMonthsNoOverflow($i);
                $labels[] = $m->isoFormat('MMM');
                $data[] = (int) DB::table($table)
                    ->whereYear($dateColumn, $m->year)
                    ->whereMonth($dateColumn, $m->month)
                    ->count();
            }
        } catch (\Throwable) {
            return ['labels' => [], 'data' => []];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /** @return array{labels: array, data: array} */
    protected function dailySeries(string $table, int $days = 14, string $dateColumn = 'created_at'): array
    {
        $labels = [];
        $data = [];
        try {
            if (! $this->hasColumn($table, $dateColumn)) {
                return ['labels' => [], 'data' => []];
            }
            for ($i = $days - 1; $i >= 0; $i--) {
                $d = today()->subDays($i);
                $labels[] = $d->isoFormat('D MMM');
                $data[] = (int) DB::table($table)->whereDate($dateColumn, $d)->count();
            }
        } catch (\Throwable) {
            return ['labels' => [], 'data' => []];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /** @return array{labels: array, data: array} */
    protected function statusSeries(string $table, string $column, int $limit = 6): array
    {
        try {
            if (! $this->hasColumn($table, $column)) {
                return ['labels' => [], 'data' => []];
            }
            $rows = DB::table($table)
                ->select($column, DB::raw('count(*) as total'))
                ->groupBy($column)
                ->orderByDesc('total')
                ->limit($limit)
                ->get();
            $labels = [];
            $data = [];
            foreach ($rows as $r) {
                $labels[] = (string) ($r->{$column} ?? '-');
                $data[] = (int) $r->total;
            }

            return ['labels' => $labels, 'data' => $data];
        } catch (\Throwable) {
            return ['labels' => [], 'data' => []];
        }
    }

    protected function trendChart(string $title, array $series, string $color = 'indigo'): ?array
    {
        if ($series['labels'] === [] || array_sum($series['data']) === 0) {
            return null;
        }
        $hex = self::CHART_COLORS[$color] ?? self::CHART_COLORS['indigo'];

        return [
            'title' => $title,
            'subtitle' => __('module.chart_auto_range'),
            'switchable' => true,
            'height' => 'h-64',
            'config' => [
                'type' => 'bar',
                'data' => [
                    'labels' => $series['labels'],
                    'datasets' => [[
                        'label' => $title,
                        'data' => $series['data'],
                        'backgroundColor' => $hex,
                        'borderColor' => $hex,
                        'borderRadius' => 8,
                        'maxBarThickness' => 34,
                    ]],
                ],
                'options' => ['plugins' => ['legend' => ['display' => false]]],
            ],
        ];
    }

    protected function doughnutChart(string $title, array $series, ?string $subtitle = null): ?array
    {
        if ($series['labels'] === [] || array_sum($series['data']) === 0) {
            return null;
        }
        $palette = [
            self::CHART_COLORS['indigo'], self::CHART_COLORS['emerald'],
            self::CHART_COLORS['amber'], self::CHART_COLORS['sky'],
            self::CHART_COLORS['violet'], self::CHART_COLORS['slate'],
        ];

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'height' => 'h-64',
            'config' => [
                'type' => 'doughnut',
                'data' => [
                    'labels' => array_map(fn ($l) => ucfirst((string) $l), $series['labels']),
                    'datasets' => [[
                        'data' => $series['data'],
                        'backgroundColor' => array_slice(array_merge($palette, $palette), 0, count($series['data'])),
                        'borderWidth' => 2,
                        'borderColor' => '#FFFFFF',
                    ]],
                ],
                'options' => ['cutout' => '62%', 'plugins' => ['legend' => ['display' => true, 'position' => 'bottom']]],
            ],
        ];
    }

    // ---------------------------------------------------------------

    protected function masterDataStats(): array
    {
        $parts = $this->count('parts') + $this->count('gci_parts');
        $customers = $this->count('customers');
        $vendors = $this->count('vendors');
        $machines = $this->count('machines');
        $locations = $this->count('warehouse_locations');

        $composition = [
            'labels' => ['Parts', 'Customers', 'Vendors', 'Machines', 'Locations'],
            'data' => [$parts, $customers, $vendors, $machines, $locations],
        ];

        return [
            'stats' => [
                ['label' => __('kpi.parts'), 'value' => $parts, 'icon' => 'cube', 'color' => 'indigo', 'hint' => __('module.master_parts_hint')],
                ['label' => __('kpi.customers'), 'value' => $customers, 'icon' => 'users', 'color' => 'sky', 'hint' => __('module.master_customers_hint')],
                ['label' => __('kpi.vendors'), 'value' => $vendors, 'icon' => 'building-store', 'color' => 'emerald', 'hint' => __('module.master_vendors_hint')],
                ['label' => __('kpi.machines'), 'value' => $machines, 'icon' => 'chip', 'color' => 'amber', 'hint' => __('module.master_machines_hint')],
            ],
            'chart' => $this->doughnutChart(__('module.master_composition'), $composition, __('module.master_composition_hint')),
            'charts' => array_values(array_filter([
                $this->doughnutChart(__('module.master_composition'), $composition, __('module.master_composition_hint')),
            ])),
            'attention' => [
                'title' => __('attention.recent_vendors'),
                'columns' => [
                    ['key' => 'name', 'label' => 'Vendor'],
                    ['key' => 'created_at', 'label' => ''],
                ],
                'rows' => $this->table('vendors')
                    ? DB::table('vendors')->orderByDesc('created_at')->limit(5)->get()
                        ->map(fn ($v) => [
                            'name' => $v->vendor_name ?? $v->name ?? '-',
                            'created_at' => $v->created_at ? \Illuminate\Support\Carbon::parse($v->created_at)->isoFormat('LL') : '-',
                        ])->values()->all()
                    : [],
            ],
        ];
    }

    protected function marketingStats(): array
    {
        $month = now()->startOfMonth();

        $poCount = $this->table('customer_pos')
            ? (int) DB::table('customer_pos')->where('created_at', '>=', $month)->count()
            : 0;

        $trend = $this->trendChart(__('module.po_trend'), $this->monthlySeries('customer_pos', 6), 'violet');

        return [
            'stats' => [
                ['label' => __('kpi.po_customer_month'), 'value' => $poCount, 'icon' => 'clipboard-doc', 'color' => 'violet', 'hint' => __('module.this_month')],
                ['label' => __('kpi.active_prices'), 'value' => $this->table('pricing_master')
                    ? $this->count('pricing_master')
                    : $this->count('prices'), 'icon' => 'tag', 'color' => 'amber', 'hint' => __('module.price_books')],
                ['label' => __('kpi.mapped_customers'), 'value' => $this->count('customer_parts'), 'icon' => 'link', 'color' => 'sky', 'hint' => __('module.mapped_products')],
                ['label' => __('kpi.customers'), 'value' => $this->count('customers'), 'icon' => 'users', 'color' => 'indigo', 'hint' => __('module.total_customers')],
            ],
            'chart' => $trend,
            'charts' => array_values(array_filter([$trend])),
        ];
    }

    protected function planningStats(): array
    {
        $trend = $this->trendChart(__('module.forecast_trend'), $this->monthlySeries('forecasts', 6), 'sky');

        return [
            'stats' => [
                ['label' => __('kpi.active_forecasts'), 'value' => $this->count('forecasts'), 'icon' => 'chart-bar', 'color' => 'sky', 'hint' => __('module.total_forecasts')],
                ['label' => __('kpi.planning_docs'), 'value' => $this->count('forecasts') + $this->count('customer_parts'), 'icon' => 'list-check', 'color' => 'indigo', 'hint' => __('module.forecast_plus_mapping')],
                ['label' => __('kpi.parts'), 'value' => $this->count('parts') + $this->count('gci_parts'), 'icon' => 'cube', 'color' => 'violet', 'hint' => __('module.plannable_parts')],
            ],
            'chart' => $trend,
            'charts' => array_values(array_filter([$trend])),
        ];
    }

    protected function purchasingStats(): array
    {
        $month = now()->startOfMonth();

        $poThisMonth = $this->table('purchase_orders')
            ? (int) DB::table('purchase_orders')->where('created_at', '>=', $month)->count()
            : 0;

        $poOpen = $this->table('purchase_orders')
            ? (int) DB::table('purchase_orders')->where('status', '!=', 'closed')->count()
            : 0;

        $trend = $this->trendChart(__('module.po_trend'), $this->monthlySeries('purchase_orders', 6), 'emerald');
        $byStatus = $this->doughnutChart(__('module.po_by_status'), $this->statusSeries('purchase_orders', 'status'), __('module.po_status_hint'));

        return [
            'stats' => [
                ['label' => __('kpi.po_month'), 'value' => $poThisMonth, 'icon' => 'clipboard-doc', 'color' => 'emerald', 'hint' => __('module.this_month')],
                ['label' => __('kpi.po_open'), 'value' => $poOpen, 'icon' => 'clock', 'color' => 'amber', 'hint' => __('module.needs_followup')],
                ['label' => __('kpi.vendors'), 'value' => $this->count('vendors'), 'icon' => 'building-store', 'color' => 'indigo', 'hint' => __('module.active_suppliers')],
            ],
            'chart' => $trend ?? $byStatus,
            'charts' => array_values(array_filter([$trend, $byStatus])),
            'attention' => [
                'title' => __('attention.recent_po'),
                'columns' => [
                    ['key' => 'po_number', 'label' => 'No. PO'],
                    ['key' => 'vendor', 'label' => 'Vendor'],
                    ['key' => 'status', 'label' => '', 'type' => 'badge'],
                ],
                'rows' => $this->table('purchase_orders')
                    ? DB::table('purchase_orders')->orderByDesc('created_at')->limit(5)->get()
                        ->map(fn ($po) => [
                            'po_number' => $po->po_number ?? '-',
                            'vendor' => $po->vendor_name ?? '-',
                            'status' => ['label' => strtoupper($po->status ?? '-'), 'class' => 'bg-slate-100 text-slate-600'],
                        ])->values()->all()
                    : [],
            ],
        ];
    }

    protected function warehouseStats(): array
    {
        $today = now()->startOfDay();

        $receivesToday = $this->table('receive_material')
            ? (int) DB::table('receive_material')->where('received_at', '>=', $today)->count()
            : $this->count('receives', $this->table('receives') && $this->hasColumn('receives', 'created_at') ? DB::table('receives')->whereDate('created_at', today()) : null);

        $receiveTable = $this->table('incoming_receives') ? 'incoming_receives' : ($this->table('receives') ? 'receives' : 'receive_material');
        $trend = $this->trendChart(__('module.receiving_trend'), $this->dailySeries($receiveTable, 14, $this->hasColumn($receiveTable, 'received_at') ? 'received_at' : 'created_at'), 'amber');
        $qcTable = $this->table('incoming_receives') ? 'incoming_receives' : ($this->table('receives') ? 'receives' : null);
        $qc = $qcTable && $this->hasColumn($qcTable, 'qc_status')
            ? $this->doughnutChart(__('module.qc_composition'), $this->statusSeries($qcTable, 'qc_status'), __('module.qc_hint'))
            : null;

        return [
            'stats' => [
                ['label' => __('kpi.incoming_today'), 'value' => $receivesToday, 'icon' => 'arrow-down-tray', 'color' => 'amber', 'hint' => __('module.today')],
                ['label' => __('kpi.stock_items'), 'value' => $this->count('stock_cards') + $this->count('inventory_location_stock'), 'icon' => 'squares', 'color' => 'indigo', 'hint' => __('module.tracked_skus')],
                ['label' => __('kpi.locations'), 'value' => $this->count('warehouse_locations'), 'icon' => 'map-pin', 'color' => 'emerald', 'hint' => __('module.storage_bins')],
            ],
            'chart' => $trend ?? $qc,
            'charts' => array_values(array_filter([$trend, $qc])),
        ];
    }

    protected function productionStats(): array
    {
        $woTable = $this->table('production_orders') ? 'production_orders' : 'work_orders';
        $total = $this->count($woTable);
        $inProgress = $this->table($woTable) && $this->hasColumn($woTable, 'status')
            ? (int) DB::table($woTable)->where('status', 'like', '%progress%')->count()
            : 0;

        $trend = $this->trendChart(__('module.wo_trend'), $this->monthlySeries($woTable, 6), 'orange');
        $byStatus = $this->hasColumn($woTable, 'status')
            ? $this->doughnutChart(__('module.wo_by_status'), $this->statusSeries($woTable, 'status'), __('module.wo_status_hint'))
            : null;

        return [
            'stats' => [
                ['label' => __('kpi.wo_total'), 'value' => $total, 'icon' => 'cog', 'color' => 'orange', 'hint' => __('module.all_work_orders')],
                ['label' => __('kpi.wo_progress'), 'value' => $inProgress, 'icon' => 'play-pause', 'color' => 'sky', 'hint' => __('module.running_now')],
                ['label' => __('kpi.machines'), 'value' => $this->count('machines'), 'icon' => 'chip', 'color' => 'indigo', 'hint' => __('module.shopfloor_capacity')],
            ],
            'chart' => $trend ?? $byStatus,
            'charts' => array_values(array_filter([$trend, $byStatus])),
            'attention' => [
                'title' => __('attention.recent_wo'),
                'columns' => [
                    ['key' => 'wo_number', 'label' => 'No. WO'],
                    ['key' => 'part', 'label' => 'Part'],
                    ['key' => 'status', 'label' => '', 'type' => 'badge'],
                ],
                'rows' => $this->table('work_orders')
                    ? DB::table('work_orders')->orderByDesc('created_at')->limit(5)->get()
                        ->map(fn ($wo) => [
                            'wo_number' => $wo->wo_number ?? '-',
                            'part' => $wo->part_no ?? '-',
                            'status' => ['label' => strtoupper($wo->status ?? '-'), 'class' => 'bg-sky-100 text-sky-700'],
                        ])->values()->all()
                    : [],
            ],
        ];
    }

    protected function outsideProcessStats(): array
    {
        $subconTable = $this->table('subcon_orders') ? 'subcon_orders' : ($this->table('subcounts') ? 'subcounts' : null);
        $trend = $subconTable ? $this->trendChart(__('module.subcon_trend'), $this->monthlySeries($subconTable, 6), 'teal') : null;

        return [
            'stats' => [
                ['label' => __('kpi.subcon_orders'), 'value' => $this->count('subcon_orders') + $this->count('subcounts'), 'icon' => 'globe', 'color' => 'teal', 'hint' => __('module.active_jobs')],
                ['label' => __('kpi.vendors'), 'value' => $this->count('vendors'), 'icon' => 'building-store', 'color' => 'indigo', 'hint' => __('module.partner_vendors')],
            ],
            'chart' => $trend,
            'charts' => array_values(array_filter([$trend])),
        ];
    }

    protected function adminStats(): array
    {
        $newWeek = User::where('created_at', '>=', now()->subDays(7))->count();

        $byRole = ['labels' => [], 'data' => []];
        try {
            $rows = User::select('role', DB::raw('count(*) as total'))->groupBy('role')->orderByDesc('total')->limit(6)->get();
            foreach ($rows as $r) {
                $byRole['labels'][] = (string) ($r->role ?? '-');
                $byRole['data'][] = (int) $r->total;
            }
        } catch (\Throwable) {
            $byRole = ['labels' => [], 'data' => []];
        }
        $roleChart = $this->doughnutChart(__('module.users_by_role'), $byRole, __('module.access_distribution'));

        return [
            'stats' => [
                ['label' => __('kpi.users_total'), 'value' => User::count(), 'icon' => 'users', 'color' => 'rose', 'hint' => __('module.registered_accounts')],
                ['label' => __('kpi.roles'), 'value' => Role::count(), 'icon' => 'shield', 'color' => 'indigo', 'hint' => __('module.access_roles')],
                ['label' => __('kpi.users_active_week'), 'value' => $newWeek, 'icon' => 'user-cog', 'color' => 'emerald', 'hint' => __('module.last_7_days')],
            ],
            'chart' => $roleChart,
            'charts' => array_values(array_filter([$roleChart])),
            'attention' => [
                'title' => __('attention.recent_users'),
                'columns' => [
                    ['key' => 'name', 'label' => 'User'],
                    ['key' => 'role', 'label' => ''],
                ],
                'rows' => User::orderByDesc('created_at')->limit(5)->get()
                    ->map(fn (User $u) => [
                        'name' => $u->name,
                        'role' => $u->role,
                    ])->values()->all(),
            ],
        ];
    }
}
