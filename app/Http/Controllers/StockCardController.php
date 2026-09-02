<?php

namespace App\Http\Controllers;

use App\Exports\StockCardExport;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\WarehouseLocation;
use App\Models\NewSchema\Inventory\InventoryFgStock;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Inventory\InventoryStockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StockCardController extends Controller
{
    /**
     * Stock Card — saldo stok real-time per part (RM + FG) dengan drill-down
     * riwayat mutasi. Fokus: bikin tim gudang percaya data sistem ≥ Excel.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $classification = strtoupper(trim((string) $request->query('classification', '')));
        $perPage = max(10, min(200, (int) $request->query('per_page', 50)));

        // Only RM + FG for now (WIP menyusul).
        if (!in_array($classification, ['RM', 'FG'], true)) {
            $classification = '';
        }

        $searchClause = function ($query) use ($search) {
            $s = strtoupper($search);
            $query->where('part_no', 'like', '%' . $s . '%')
                ->orWhere('part_name', 'like', '%' . $s . '%')
                ->orWhere('model', 'like', '%' . $s . '%');
        };

        // ── RM: aggregated on-hand per part dari inventory_location_stock ──
        $rmQuery = InventoryLocationStock::query()
            ->whereNotNull('gci_part_id')
            ->join('gci_parts as gp', 'gp.id', '=', 'inventory_location_stock.gci_part_id')
            ->where('gp.classification', 'RM')
            ->when($classification === 'FG', fn ($q) => $q->whereRaw('1 = 0'))
            ->when($search !== '', fn ($q) => $q->where($searchClause))
            ->selectRaw('inventory_location_stock.gci_part_id, gp.part_no, gp.part_name, gp.model, gp.subcount_uom as uom, SUM(inventory_location_stock.qty_on_hand) as total_qty')
            ->groupBy('inventory_location_stock.gci_part_id', 'gp.part_no', 'gp.part_name', 'gp.model', 'gp.subcount_uom')
            ->orderByDesc('total_qty')
            ->orderBy('gp.part_no');

        // ── FG: on-hand dari inventory_fg_stock ──
        $fgQuery = InventoryFgStock::query()
            ->whereNotNull('gci_part_id')
            ->join('gci_parts as gp', 'gp.id', '=', 'inventory_fg_stock.gci_part_id')
            ->when($classification === 'RM', fn ($q) => $q->whereRaw('1 = 0'))
            ->when($search !== '', fn ($q) => $q->where($searchClause))
            ->selectRaw('inventory_fg_stock.gci_part_id, gp.part_no, gp.part_name, gp.model, gp.subcount_uom as uom, SUM(inventory_fg_stock.qty_on_hand) as total_qty')
            ->groupBy('inventory_fg_stock.gci_part_id', 'gp.part_no', 'gp.part_name', 'gp.model', 'gp.subcount_uom')
            ->orderByDesc('total_qty')
            ->orderBy('gp.part_no');

        $union = $rmQuery->union($fgQuery);
        $rows = DB::table(DB::raw("({$union->toSql()}) as stock_union"))
            ->mergeBindings($union->getQuery())
            ->orderByDesc('total_qty')
            ->orderBy('part_no')
            ->paginate($perPage)
            ->withQueryString();

        // Augment each row: classification + lokasi ringkas per part.
        $items = $rows->items();
        $partIds = collect($items)->pluck('gci_part_id')->filter()->unique()->values()->all();

        $locationByPart = [];
        if (!empty($partIds)) {
            $locationByPart = InventoryLocationStock::query()
                ->whereIn('gci_part_id', $partIds)
                ->selectRaw('gci_part_id, GROUP_CONCAT(DISTINCT location_code ORDER BY location_code SEPARATOR ", ") as locations')
                ->groupBy('gci_part_id')
                ->pluck('locations', 'gci_part_id')
                ->all();
        }

        $partsById = GciPart::whereIn('id', $partIds)->get()->keyBy('id');

        $rows->getCollection()->transform(function ($row) use ($partsById, $locationByPart) {
            $part = $partsById[(int) $row->gci_part_id] ?? null;
            $row->classification = $part?->classification ?? '';
            $row->locations = $locationByPart[(int) $row->gci_part_id] ?? '';
            return $row;
        });

        $summary = [
            'total_parts' => $rows->total(),
            'total_qty' => collect($rows->items())->sum(fn ($r) => (float) ($r->total_qty ?? 0)),
        ];

        $locationOptions = WarehouseLocation::query()
            ->where('status', 'ACTIVE')
            ->orderBy('location_code')
            ->pluck('location_code')
            ->all();

        return view('stock-card.index', compact('rows', 'search', 'classification', 'perPage', 'summary', 'locationOptions'));
    }

    /**
     * Riwayat mutasi untuk satu part (drill-down), dirender via fetch.
     */
    public function mutations(Request $request, int $gciPartId)
    {
        $part = GciPart::find($gciPartId);
        if (!$part) {
            abort(404);
        }

        $movements = InventoryStockMovement::query()
            ->where('gci_part_id', $gciPartId)
            ->orderByDesc('moved_at')
            ->limit(100)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'html' => view('stock-card.partials.mutations', [
                    'part' => $part,
                    'movements' => $movements,
                ])->render(),
            ]);
        }

        return view('stock-card.partials.mutations', compact('part', 'movements'));
    }

    /**
     * Export saldo stok (RM + FG) ke Excel.
     */
    public function export(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $classification = strtoupper(trim((string) $request->query('classification', '')));

        $filename = 'stock_card_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new StockCardExport($search, $classification), $filename);
    }
}
