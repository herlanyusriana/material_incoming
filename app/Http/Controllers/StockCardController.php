<?php

namespace App\Http\Controllers;

use App\Exports\StockCardExport;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Core\WarehouseLocation;
use App\Models\NewSchema\Incoming\IncomingReceive as Receive;
use App\Models\NewSchema\Inventory\InventoryFgStock;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\NewSchema\Inventory\InventoryStockMovement;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

        // RM + WIP dari inventory_location_stock, FG dari inventory_fg_stock.
        if (!in_array($classification, ['RM', 'WIP', 'FG'], true)) {
            $classification = '';
        }

        $searchClause = function ($query) use ($search) {
            $s = strtoupper($search);
            $query->where('part_no', 'like', '%' . $s . '%')
                ->orWhere('part_name', 'like', '%' . $s . '%')
                ->orWhere('model', 'like', '%' . $s . '%');
        };

        // ── RM + WIP: aggregated on-hand per part dari inventory_location_stock ──
        // WIP lives in the SAME table as RM, disaring gci_parts.classification = 'WIP'.
        // Kolom Receipt (batch/invoice) dihitung SETELAH pagination, bukan sebagai
        // subquery di SELECT — biar `only_full_group_by` & binding union tidak jebol.
        $rmQuery = InventoryLocationStock::query()
            ->whereNotNull('inventory_location_stock.gci_part_id')
            ->join('gci_parts as gp', 'gp.id', '=', 'inventory_location_stock.gci_part_id')
            ->leftJoin('customer_gci_part as cgp', 'cgp.gci_part_id', '=', 'gp.id')
            ->when($classification === '', fn ($q) => $q->whereIn('gp.classification', ['RM', 'WIP']))
            ->when($classification === 'RM', fn ($q) => $q->where('gp.classification', 'RM'))
            ->when($classification === 'WIP', fn ($q) => $q->where('gp.classification', 'WIP'))
            ->when($classification === 'FG', fn ($q) => $q->whereRaw('1 = 0'))
            ->when($search !== '', fn ($q) => $q->where($searchClause))
            ->addSelect([
                'inventory_location_stock.gci_part_id',
                'gp.part_no',
                'gp.part_name',
                'gp.model',
                'gp.subcount_uom as uom',
                'gp.default_location',
                'gp.status as part_status',
                DB::raw('SUM(inventory_location_stock.qty_on_hand) as total_qty'),
                DB::raw('COUNT(DISTINCT inventory_location_stock.location_code) as location_count'),
                DB::raw('GROUP_CONCAT(DISTINCT customers.name ORDER BY customers.name SEPARATOR ", ") as customer_names'),
            ])
            ->leftJoin('customers', 'customers.id', '=', 'cgp.customer_id')
            ->groupBy('inventory_location_stock.gci_part_id', 'gp.part_no', 'gp.part_name', 'gp.model', 'gp.subcount_uom', 'gp.default_location', 'gp.status');

        // ── FG: on-hand dari inventory_fg_stock ──
        $fgQuery = InventoryFgStock::query()
            ->whereNotNull('inventory_fg_stock.gci_part_id')
            ->join('gci_parts as gp', 'gp.id', '=', 'inventory_fg_stock.gci_part_id')
            ->leftJoin('customer_gci_part as cgp', 'cgp.gci_part_id', '=', 'gp.id')
            ->when(in_array($classification, ['RM', 'WIP'], true), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($search !== '', fn ($q) => $q->where($searchClause))
            ->addSelect([
                'inventory_fg_stock.gci_part_id',
                'gp.part_no',
                'gp.part_name',
                'gp.model',
                'gp.subcount_uom as uom',
                'gp.default_location',
                'gp.status as part_status',
                DB::raw('SUM(inventory_fg_stock.qty_on_hand) as total_qty'),
                DB::raw('COUNT(DISTINCT inventory_fg_stock.location_code) as location_count'),
                DB::raw('GROUP_CONCAT(DISTINCT customers.name ORDER BY customers.name SEPARATOR ", ") as customer_names'),
            ])
            ->leftJoin('customers', 'customers.id', '=', 'cgp.customer_id')
            ->groupBy('inventory_fg_stock.gci_part_id', 'gp.part_no', 'gp.part_name', 'gp.model', 'gp.subcount_uom', 'gp.default_location', 'gp.status');

        $union = $rmQuery->union($fgQuery);
        $rows = DB::table(DB::raw("({$union->toSql()}) as stock_union"))
            ->mergeBindings($union->getQuery())
            ->orderByDesc('total_qty')
            ->orderBy('part_no')
            ->paginate($perPage)
            ->withQueryString();

        // Augment each row: classification + lokasi ringkas + receipt terakhir per part.
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

        $receiptByPart = empty($partIds) ? [] : $this->receiptSummary($partIds);

        $partsById = GciPart::whereIn('id', $partIds)->get()->keyBy('id');

        $rows->getCollection()->transform(function ($row) use ($partsById, $locationByPart, $receiptByPart) {
            $part = $partsById[(int) $row->gci_part_id] ?? null;
            $row->classification = $part?->classification ?? '';
            $row->locations = $locationByPart[(int) $row->gci_part_id] ?? '';
            $receipt = $receiptByPart[(int) $row->gci_part_id] ?? [];
            $row->latest_batch_received = $receipt['batch'] ?? null;
            $row->latest_receive_invoice_no = $receipt['invoice'] ?? null;
            $row->latest_source_invoice_no = $receipt['source'] ?? null;
            return $row;
        });

        $summary = [
            'total_parts' => $rows->total(),
            'total_qty' => collect($rows->items())->sum(fn ($r) => (float) ($r->total_qty ?? 0)),
        ];

        // ── KPI cards (Items / On Hand / On Order / Available) ──
        // Dihitung dari seluruh hasil union (bukan satu halaman). On Order belum
        // dihitung (0) sehingga Available = On Hand, konsisten dengan Master Inventory.
        $kpiRows = DB::table(DB::raw("({$union->toSql()}) as stock_union"))
            ->mergeBindings($union->getQuery())
            ->selectRaw('COUNT(*) as item_count, COALESCE(SUM(total_qty), 0) as on_hand')
            ->first();

        $kpi = [
            'item_count' => (int) ($kpiRows->item_count ?? 0),
            'total_on_hand' => (float) ($kpiRows->on_hand ?? 0),
            'total_on_order' => 0.0,
            'total_available' => (float) ($kpiRows->on_hand ?? 0),
        ];

        $locationOptions = WarehouseLocation::query()
            ->where('status', 'ACTIVE')
            ->orderBy('location_code')
            ->pluck('location_code')
            ->all();

        return view('stock-card.index', compact('rows', 'search', 'classification', 'perPage', 'summary', 'kpi', 'locationOptions'));
    }

    /**
     * Ringkasan penerimaan terakhir per part (batch + invoice), untuk kolom "Receipt".
     * Dipanggil sekali per halaman setelah pagination, bukan sebagai subquery di SELECT.
     *
     * @param  int[]  $partIds
     * @return array<int, array{batch: ?string, invoice: ?string, source: ?string}>
     */
    protected function receiptSummary(array $partIds): array
    {
        // Batch tag terakhir per part (dari incoming_receives via arrival_item).
        $batchRows = Receive::query()
            ->join('incoming_arrival_items', 'incoming_arrival_items.id', '=', 'incoming_receives.arrival_item_id')
            ->whereIn('incoming_arrival_items.gci_part_id', $partIds)
            ->whereNotNull('incoming_receives.tag')
            ->whereNull('incoming_receives.deleted_at')
            ->orderByDesc('incoming_receives.created_at')
            ->get(['incoming_arrival_items.gci_part_id', 'incoming_receives.tag']);

        // Invoice terakhir per part (invoice receive, fallback ke invoice arrival).
        $invoiceRows = Receive::query()
            ->join('incoming_arrival_items', 'incoming_arrival_items.id', '=', 'incoming_receives.arrival_item_id')
            ->leftJoin('incoming_arrivals', 'incoming_arrivals.id', '=', 'incoming_arrival_items.arrival_id')
            ->whereIn('incoming_arrival_items.gci_part_id', $partIds)
            ->where(function ($q) {
                $q->whereNotNull('incoming_receives.invoice_no')
                    ->where('incoming_receives.invoice_no', '!=', '')
                    ->orWhere(function ($qq) {
                        $qq->whereNotNull('incoming_arrivals.invoice_no')
                            ->where('incoming_arrivals.invoice_no', '!=', '');
                    });
            })
            ->whereNull('incoming_receives.deleted_at')
            ->orderByDesc('incoming_receives.created_at')
            ->get([
                'incoming_arrival_items.gci_part_id',
                DB::raw("COALESCE(NULLIF(incoming_receives.invoice_no, ''), NULLIF(incoming_arrivals.invoice_no, '')) as invoice_no"),
            ]);

        // Source invoice terakhir per part (dari mutasi RECEIVE yang terhubung ke receive).
        $sourceRows = InventoryStockMovement::query()
            ->join('incoming_receives', function ($join) {
                $join->on('incoming_receives.gci_part_id', '=', 'inventory_stock_movements.gci_part_id')
                     ->on('incoming_receives.tag', '=', 'inventory_stock_movements.tag_number');
            })
            ->whereIn('inventory_stock_movements.gci_part_id', $partIds)
            ->where('inventory_stock_movements.movement_type', 'RECEIVE')
            ->where('inventory_stock_movements.qty', '>', 0)
            ->whereNull('incoming_receives.deleted_at')
            ->whereNotNull('incoming_receives.invoice_no')
            ->where('incoming_receives.invoice_no', '!=', '')
            ->whereNull('inventory_stock_movements.deleted_at')
            ->orderByDesc('inventory_stock_movements.moved_at')
            ->get(['inventory_stock_movements.gci_part_id', 'incoming_receives.invoice_no']);

        $result = [];
        foreach ($partIds as $id) {
            $result[(int) $id] = ['batch' => null, 'invoice' => null, 'source' => null];
        }
        // Hasil diurutkan created_at/moved_at desc → "first wins" = yang terbaru.
        foreach ($batchRows as $r) {
            if ($result[(int) $r->gci_part_id]['batch'] === null) {
                $result[(int) $r->gci_part_id]['batch'] = $r->tag;
            }
        }
        foreach ($invoiceRows as $r) {
            if ($result[(int) $r->gci_part_id]['invoice'] === null) {
                $result[(int) $r->gci_part_id]['invoice'] = $r->invoice_no;
            }
        }
        foreach ($sourceRows as $r) {
            if ($result[(int) $r->gci_part_id]['source'] === null) {
                $result[(int) $r->gci_part_id]['source'] = $r->invoice_no;
            }
        }

        return $result;
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
