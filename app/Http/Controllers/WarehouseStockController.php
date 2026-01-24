<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\LocationInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseStockController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $location = strtoupper(trim((string) $request->query('location', '')));
        $onlyPositive = (bool) $request->boolean('only_positive', true);
        $perPage = (int) $request->query('per_page', 50);
        if ($perPage < 10) {
            $perPage = 10;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }

        $query = LocationInventory::query()
            ->with(['part', 'location'])
            ->when($onlyPositive, fn ($q) => $q->where('qty_on_hand', '>', 0))
            ->when($location !== '', fn ($q) => $q->where('location_code', $location))
            ->when($search !== '', function ($q) use ($search) {
                $s = strtoupper($search);
                $q->whereHas('part', function ($qp) use ($s) {
                    $qp->where('part_no', 'like', '%' . $s . '%')
                        ->orWhere('part_name_gci', 'like', '%' . $s . '%')
                        ->orWhere('part_name_vendor', 'like', '%' . $s . '%');
                })->orWhere('location_code', 'like', '%' . $s . '%');
            })
            ->orderBy('location_code')
            ->orderBy('part_id');

        $records = $query->paginate($perPage)->withQueryString();

        $totalsByLocation = LocationInventory::query()
            ->selectRaw('location_code, SUM(qty_on_hand) as total_qty')
            ->when($onlyPositive, fn ($q) => $q->where('qty_on_hand', '>', 0))
            ->when($location !== '', fn ($q) => $q->where('location_code', $location))
            ->groupBy('location_code')
            ->orderBy('location_code')
            ->pluck('total_qty', 'location_code')
            ->all();

        $grandTotal = array_sum(array_map('floatval', $totalsByLocation));

        return view('warehouse.stock.index', compact(
            'records',
            'search',
            'location',
            'onlyPositive',
            'perPage',
            'totalsByLocation',
            'grandTotal',
        ));
    }

    public function reconcile(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $onlyDiff = (bool) $request->boolean('only_diff', true);
        $perPage = (int) $request->query('per_page', 50);
        if ($perPage < 10) {
            $perPage = 10;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }

        $locationSums = LocationInventory::query()
            ->selectRaw('part_id, SUM(qty_on_hand) as loc_qty')
            ->groupBy('part_id');

        $query = Inventory::query()
            ->select([
                'inventories.part_id',
                'inventories.on_hand',
                DB::raw('COALESCE(ls.loc_qty, 0) as loc_qty'),
                DB::raw('(COALESCE(inventories.on_hand, 0) - COALESCE(ls.loc_qty, 0)) as diff_qty'),
            ])
            ->leftJoinSub($locationSums, 'ls', function ($join) {
                $join->on('ls.part_id', '=', 'inventories.part_id');
            })
            ->with('part')
            ->when($onlyDiff, fn ($q) => $q->whereRaw('(COALESCE(inventories.on_hand, 0) - COALESCE(ls.loc_qty, 0)) != 0'))
            ->when($search !== '', function ($q) use ($search) {
                $s = strtoupper($search);
                $q->whereHas('part', function ($qp) use ($s) {
                    $qp->where('part_no', 'like', '%' . $s . '%')
                        ->orWhere('part_name_gci', 'like', '%' . $s . '%')
                        ->orWhere('part_name_vendor', 'like', '%' . $s . '%');
                });
            })
            ->orderByRaw('ABS(COALESCE(inventories.on_hand, 0) - COALESCE(ls.loc_qty, 0)) DESC')
            ->orderBy('inventories.part_id');

        $rows = $query->paginate($perPage)->withQueryString();

        return view('warehouse.stock.reconcile', compact('rows', 'search', 'onlyDiff', 'perPage'));
    }
}

