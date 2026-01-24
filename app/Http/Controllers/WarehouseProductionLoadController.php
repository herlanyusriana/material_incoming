<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WarehouseProductionLoadController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->query('date_from') ?: now()->startOfDay()->format('Y-m-d');
        $dateTo = $request->query('date_to') ?: now()->addDays(6)->startOfDay()->format('Y-m-d');
        $search = trim((string) $request->query('search', ''));
        $status = strtolower(trim((string) $request->query('status', '')));

        try {
            $from = Carbon::parse($dateFrom)->startOfDay();
        } catch (\Throwable) {
            $from = now()->startOfDay();
        }
        try {
            $to = Carbon::parse($dateTo)->startOfDay();
        } catch (\Throwable) {
            $to = now()->addDays(6)->startOfDay();
        }

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        $query = ProductionOrder::query()
            ->with('part')
            ->whereBetween('plan_date', [$from->toDateString(), $to->toDateString()])
            ->when($search !== '', function ($q) use ($search) {
                $s = strtoupper($search);
                $q->where('production_order_number', 'like', '%' . $s . '%')
                    ->orWhereHas('part', function ($qp) use ($s) {
                        $qp->where('part_no', 'like', '%' . $s . '%')
                            ->orWhere('part_name', 'like', '%' . $s . '%')
                            ->orWhere('model', 'like', '%' . $s . '%');
                    });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderBy('plan_date')
            ->orderBy('production_order_number');

        $orders = $query->get();

        $totalsByDate = $orders
            ->groupBy(fn ($o) => (string) ($o->plan_date ?? ''))
            ->map(fn ($group) => (float) $group->sum('qty_planned'))
            ->all();

        return view('warehouse.production_load', [
            'orders' => $orders,
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
            'search' => $search,
            'status' => $status,
            'totalsByDate' => $totalsByDate,
        ]);
    }
}

