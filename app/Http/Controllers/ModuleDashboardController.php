<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Support\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ModuleDashboardController extends Controller
{
    public function show(Request $request, string $module): View
    {
        $user = $request->user();

        $definition = Menu::get($module);

        if (! $definition) {
            throw new NotFoundHttpException;
        }

        abort_unless(Menu::visibleItems($user, $module) !== [], 403);

        $stats = $this->stats($module);

        return view('module.dashboard', [
            'module' => $definition + ['key' => $module],
            'items' => Menu::visibleItems($user, $module),
            'stats' => $stats['stats'] ?? [],
            'chart' => $stats['chart'] ?? null,
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
            'warehouse' => $this->warehouseStats(),
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

    protected function masterDataStats(): array
    {
        $startOfMonth = now()->startOfMonth();

        return [
            'stats' => [
                ['label' => __('kpi.parts'), 'value' => $this->count('parts')],
                ['label' => __('kpi.customers'), 'value' => $this->count('customers')],
                ['label' => __('kpi.vendors'), 'value' => $this->count('vendors')],
                ['label' => __('kpi.machines'), 'value' => $this->count('machines')],
            ],
            'chart' => null,
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

        return [
            'stats' => [
                ['label' => __('kpi.po_customer_month'), 'value' => $poCount],
                ['label' => __('kpi.active_prices'), 'value' => $this->table('pricing_master')
                    ? $this->count('pricing_master')
                    : $this->count('prices')],
                ['label' => __('kpi.mapped_customers'), 'value' => $this->count('customer_parts')],
                ['label' => __('kpi.customers'), 'value' => $this->count('customers')],
            ],
            'chart' => null,
        ];
    }

    protected function planningStats(): array
    {
        return [
            'stats' => [
                ['label' => __('kpi.active_forecasts'), 'value' => $this->count('forecasts')],
                ['label' => __('kpi.planning_docs'), 'value' => $this->count('forecasts') + $this->count('customer_parts')],
                ['label' => __('kpi.parts'), 'value' => $this->count('parts')],
            ],
            'chart' => null,
        ];
    }

    protected function purchasingStats(): array
    {
        $month = now()->startOfMonth();

        $poThisMonth = $this->table('purchase_orders')
            ? (int) DB::table('purchase_orders')->where('created_at', '>=', $month)->count()
            : 0;

        $poOpen = $this->table('purchase_orders')
            ? (int) DB::table('purchase_orders')->whereNull('closed_at')->where('status', '!=', 'closed')->count()
            : 0;

        return [
            'stats' => [
                ['label' => __('kpi.po_month'), 'value' => $poThisMonth],
                ['label' => __('kpi.po_open'), 'value' => $poOpen],
                ['label' => __('kpi.vendors'), 'value' => $this->count('vendors')],
            ],
            'chart' => null,
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
        $month = now()->startOfMonth();

        $receivesToday = $this->table('receive_material')
            ? (int) DB::table('receive_material')->where('received_at', '>=', $today)->count()
            : $this->count('receives', DB::table('receives')->whereDate('created_at', today()));

        return [
            'stats' => [
                ['label' => __('kpi.incoming_today'), 'value' => $receivesToday],
                ['label' => __('kpi.stock_items'), 'value' => $this->count('stock_cards')],
                ['label' => __('kpi.locations'), 'value' => $this->count('warehouse_locations')],
            ],
            'chart' => null,
        ];
    }

    protected function productionStats(): array
    {
        $total = $this->count('work_orders');
        $inProgress = $this->table('work_orders')
            ? (int) DB::table('work_orders')->where('status', 'like', '%progress%')->count()
            : 0;

        return [
            'stats' => [
                ['label' => __('kpi.wo_total'), 'value' => $total],
                ['label' => __('kpi.wo_progress'), 'value' => $inProgress],
                ['label' => __('kpi.machines'), 'value' => $this->count('machines')],
            ],
            'chart' => null,
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
        return [
            'stats' => [
                ['label' => __('kpi.subcon_orders'), 'value' => $this->count('subcon_orders') + $this->count('subcounts')],
                ['label' => __('kpi.vendors'), 'value' => $this->count('vendors')],
            ],
            'chart' => null,
        ];
    }

    protected function adminStats(): array
    {
        $newWeek = User::where('created_at', '>=', now()->subDays(7))->count();

        return [
            'stats' => [
                ['label' => __('kpi.users_total'), 'value' => User::count()],
                ['label' => __('kpi.roles'), 'value' => Role::count()],
                ['label' => __('kpi.users_active_week'), 'value' => $newWeek],
            ],
            'chart' => null,
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
