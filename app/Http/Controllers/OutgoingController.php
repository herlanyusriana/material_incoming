<?php

namespace App\Http\Controllers;

use App\Exports\OutgoingDailyPlanningExport;
use App\Exports\OutgoingDailyPlanningTemplateExport;
use App\Exports\StockAtCustomersExport;
use App\Imports\StockAtCustomersImport;
use App\Models\OutgoingDeliveryPlanningLine;
use App\Models\OutgoingPickingFg;
use App\Models\OutgoingJigSetting;
use App\Models\StandardPacking;
use App\Imports\OutgoingDailyPlanningImport;
use App\Models\StockAtCustomer;
use App\Models\OutgoingDailyPlan;
use App\Models\OutgoingDailyPlanCell;
use App\Models\OutgoingDailyPlanRow;
use App\Models\CustomerPart;
use App\Models\CustomerPartComponent;
use App\Models\DeliveryRequirementFulfillment;
use App\Models\DeliveryPlanRequirementAssignment;
use App\Models\GciPart;
use App\Models\Bom;
use App\Models\DeliveryPlan;
use App\Models\Truck;
use App\Models\Driver;
use App\Models\DeliveryOrderItem;
use App\Models\DeliveryOrder;
use App\Models\Customer;
use Carbon\CarbonImmutable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Pagination\LengthAwarePaginator;

class OutgoingController extends Controller
{
    public function dailyPlanning()
    {
        /** @var \Carbon\Carbon $dateFrom */
        $dateFrom = $this->parseDate(request('date_from')) ?? now()->startOfDay();
        /** @var \Carbon\Carbon $dateTo */
        $dateTo = $this->parseDate(request('date_to')) ?? now()->addDays(4)->startOfDay();
        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $search = trim((string) request('search', ''));
        $perPage = (int) request('per_page', 100);
        if ($perPage < 10) {
            $perPage = 10;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }

        $planId = request('plan_id');
        $plan = null;
        if ($planId) {
            $plan = OutgoingDailyPlan::query()->whereKey($planId)->first();
        }
        if (!$plan) {
            // Find an overlapping plan
            $plan = OutgoingDailyPlan::query()
                ->whereDate('date_from', '<=', $dateTo->toDateString())
                ->whereDate('date_to', '>=', $dateFrom->toDateString())
                ->latest('id')
                ->first();
        }

        if (!$plan) {
            // Fallback: search any plan at all to show SOMETHING
            $plan = OutgoingDailyPlan::query()->latest('id')->first();
        }

        // SYNC LOGIC: Ensure gci_part_id stays in sync with CustomerPartComponent mappings
        if ($plan) {
            $this->syncDailyPlanRowMappings($plan->id, $dateFrom, $dateTo);
        }

        $days = $this->daysBetween($dateFrom, $dateTo);

        // Fetch rows based on the plan
        if ($plan) {
            $rows = $plan->rows()
                ->with([
                    'gciPart.standardPacking',
                    'customerPart',
                    'cells' => function (\Illuminate\Database\Eloquent\Relations\HasMany $query) use ($dateFrom, $dateTo) {
                        $query->whereBetween('plan_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);
                    }
                ])
                ->when($search !== '', function (\Illuminate\Database\Eloquent\Builder $query) use ($search) {
                    $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($search) {
                        $q->where('part_no', 'like', '%' . $search . '%')
                            ->orWhereHas('gciPart', function (\Illuminate\Database\Eloquent\Builder $sq) use ($search) {
                                $sq->where('part_name', 'like', '%' . $search . '%');
                            });
                    });
                })
                ->whereHas('cells', function (\Illuminate\Database\Eloquent\Builder $query) use ($dateFrom, $dateTo) {
                    $query->whereBetween('plan_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                        ->where('qty', '>', 0);
                })
                ->paginate($perPage)
                ->withQueryString();
        } else {
            // Empty paginator if no plan
            $rows = new LengthAwarePaginator([], 0, $perPage);
        }

        $totalsByDate = [];
        foreach ($days as $d) {
            $totalsByDate[$d->format('Y-m-d')] = 0;
        }

        if ($plan) {
            $planTotals = OutgoingDailyPlanCell::query()
                ->whereIn('row_id', $plan->rows()->pluck('id'))
                ->whereBetween('plan_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->selectRaw('plan_date, SUM(qty) as total')
                ->groupBy('plan_date')
                ->get()
                ->pluck('total', 'plan_date');

            foreach ($planTotals as $dateStr => $total) {
                $totalsByDate[$dateStr] = (int) $total;
            }
        }

        $unmappedCount = 0;
        if ($plan) {
            // Count only rows with NULL gci_part_id that have demand in the selected date range
            $unmappedCount = $plan->rows()
                ->whereNull('gci_part_id')
                ->whereHas('cells', function (\Illuminate\Database\Eloquent\Builder $q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('plan_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                        ->where('qty', '>', 0);
                })
                ->count();
        }

        return view('outgoing.daily_planning', compact(
            'plan',
            'rows',
            'days',
            'dateFrom',
            'dateTo',
            'planId',
            'search',
            'totalsByDate',
            'unmappedCount'
        ));
    }

    public function dailyPlanningTemplate(Request $request)
    {
        $dateFrom = $this->parseDate($request->query('date_from')) ?? now()->startOfDay();
        $dateTo = $this->parseDate($request->query('date_to')) ?? now()->addDays(4)->startOfDay();
        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $filename = 'daily_planning_template_' . $dateFrom->format('Ymd') . '_' . $dateTo->format('Ymd') . '.xlsx';
        return Excel::download(new OutgoingDailyPlanningTemplateExport($dateFrom, $dateTo), $filename);
    }

    public function dailyPlanningExport(OutgoingDailyPlan $plan)
    {
        /** @var \Carbon\Carbon $dateFrom */
        $dateFrom = $plan->date_from;
        /** @var \Carbon\Carbon $dateTo */
        $dateTo = $plan->date_to;
        $filename = 'daily_planning_' . $dateFrom->format('Ymd') . '_' . $dateTo->format('Ymd') . '.xlsx';
        return Excel::download(new OutgoingDailyPlanningExport($plan->loadMissing('rows.cells')), $filename);
    }

    public function dailyPlanningImport(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new OutgoingDailyPlanningImport();
        Excel::import($import, $request->file('file'));

        if (!empty($import->failures)) {
            $msg = implode('<br>', array_slice($import->failures, 0, 10));
            if (count($import->failures) > 10) {
                $msg .= '<br>... and ' . (count($import->failures) - 10) . ' more errors.';
            }
            return back()->with('error', 'Import errors found:<br>' . $msg);
        }

        $dateFrom = $import->dateFrom();
        $dateTo = $import->dateTo();
        if (!$dateFrom || !$dateTo) {
            return back()->withErrors(['file' => 'Format Excel tidak dikenali. Pastikan ada kolom tanggal seperti "2024-01-14 Seq" dan "2024-01-14 Qty".']);
        }

        $plan = null;
        DB::transaction(function () use ($import, $dateFrom, $dateTo, &$plan) {
            $plan = OutgoingDailyPlan::create([
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'created_by' => auth()->id(),
            ]);

            foreach ($import->rows as $idx => $row) {
                $rowNo = $row['row_no'] ?? null;
                $rowModel = OutgoingDailyPlanRow::create([
                    'plan_id' => $plan->id,
                    'row_no' => $rowNo ?: ($idx + 1),
                    'production_line' => $row['production_line'],
                    'part_no' => $row['part_no'],
                    'gci_part_id' => $row['gci_part_id'],
                    'customer_part_id' => $row['customer_part_id'] ?? null,
                ]);

                foreach ($row['cells'] as $date => $cell) {
                    OutgoingDailyPlanCell::create([
                        'row_id' => $rowModel->id,
                        'plan_date' => $date,
                        'seq' => $cell['seq'],
                        'qty' => $cell['qty'],
                    ]);
                }
            }
        });

        $msg = 'Daily planning berhasil diimport.';
        if (!empty($import->createdParts)) {
            $msg .= '<br><br><strong>Info Unmapped Parts:</strong> ' . count($import->createdParts) . ' part tidak ter-mapping:<br>' . implode('<br>', array_slice($import->createdParts, 0, 10)) . (count($import->createdParts) > 10 ? '<br>...' : '');
        }

        return redirect()->route('outgoing.daily-planning', ['plan_id' => $plan?->id])->with('success', $msg);
    }

    public function customerPo()
    {
        return view('outgoing.customer_po');
    }

    public function productMapping()
    {
        $recentParts = GciPart::query()
            ->where('classification', 'FG')
            ->orderBy('updated_at', 'desc')
            ->paginate(50);

        return view('outgoing.product_mapping', compact('recentParts'));
    }

    public function whereUsed(Request $request)
    {
        $validated = $request->validate([
            'part_no' => ['required', 'string'],
        ]);

        $partNo = $this->normalizePartNo((string) $validated['part_no']);
        if ($partNo === '') {
            return response()->json([
                'part_no' => '',
                'mode' => 'invalid',
                'used_in' => [],
            ]);
        }

        // If the input is an FG (GCI) part number, show the FG BOM(s) directly (so user can see customer mappings),
        // instead of treating it as a "component" for implosion.
        $mode = 'component';
        $boms = null;

        $fgPartId = null;
        $fg = GciPart::query()->where('part_no', $partNo)->where('classification', 'FG')->first();
        if ($fg) {
            $fgPartId = (int) $fg->id;
        } else {
            // Also allow customer part numbers: map -> first FG component.
            $mappedFgId = $this->resolveFgPartIdFromPartNo($partNo);
            if ($mappedFgId) {
                $fgPartId = (int) $mappedFgId;
            }
        }

        if ($fgPartId) {
            $mode = 'fg';
            $boms = Bom::query()
                ->with(['part', 'items.componentPart'])
                ->where('part_id', $fgPartId)
                ->where('status', 'active')
                ->get();
        }

        if ($boms === null) {
            $boms = Bom::whereUsed($partNo);
        }

        $results = $boms->map(function ($bom) {
            $customerProducts = CustomerPartComponent::query()
                ->with(['customerPart.customer'])
                ->where('gci_part_id', $bom->part_id)
                ->get()
                ->map(fn($comp) => [
                    'customer_part_no' => $comp->customerPart->customer_part_no,
                    'customer_part_name' => $comp->customerPart->customer_part_name,
                    'customer_name' => $comp->customerPart->customer->name ?? '-',
                    'usage_qty' => $comp->qty_per_unit,
                    'line' => $comp->customerPart->line,
                    'case_name' => $comp->customerPart->case_name,
                ]);

            return [
                'id' => $bom->id,
                'part_id' => $bom->part_id,
                'fg_part_no' => $bom->part->part_no,
                'fg_part_name' => $bom->part->part_name,
                'revision' => $bom->revision,
                'status' => $bom->status,
                'customer_products' => $customerProducts,
            ];
        });

        return response()->json([
            'part_no' => $partNo,
            'mode' => $mode,
            'used_in' => $results,
        ]);
    }

    public function deliveryRequirements(Request $request)
    {
        $dateFrom = $this->parseDate($request->query('date_from')) ?? now()->startOfDay();
        $dateTo = $this->parseDate($request->query('date_to')) ?? now()->startOfDay();
        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        // Sorting parameters
        $sortBy = $request->query('sort_by', 'date'); // date, customer, part, sequence
        $sortDir = $request->query('sort_dir', 'asc'); // asc, desc
        if (!in_array($sortBy, ['date', 'customer', 'part', 'sequence'], true)) {
            $sortBy = 'date';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        // SYNC LOGIC: Re-sync gci_part_id from CustomerPartComponent mapping for ALL rows in this date range.
        // This ensures that if a user updates the mapping in Planning module, the Daily Plan rows are updated.
        $this->syncDailyPlanRowMappings(null, $dateFrom, $dateTo);

        // Fetch planned cells within range (we will subtract fulfillments below)
        /** @var \Illuminate\Support\Collection $cells */
        $cells = OutgoingDailyPlanCell::query()
            ->with(['row.gciPart.customer', 'row.gciPart.standardPacking'])
            ->whereDate('plan_date', '>=', $dateFrom->format('Y-m-d'))
            ->whereDate('plan_date', '<=', $dateTo->format('Y-m-d'))
            ->get();

        $fulfilledMap = DeliveryRequirementFulfillment::query()
            ->selectRaw('plan_date, row_id, SUM(qty) as fulfilled_qty')
            ->whereDate('plan_date', '>=', $dateFrom->format('Y-m-d'))
            ->whereDate('plan_date', '<=', $dateTo->format('Y-m-d'))
            ->groupBy('plan_date', 'row_id')
            ->get()
            ->mapWithKeys(function ($r) {
                $k = (string) $r->plan_date . '|' . (int) $r->row_id;
                return [$k => (float) $r->fulfilled_qty];
            })
            ->all();

        /** @var \Illuminate\Support\Collection $cells */
        $cells = $cells
            ->map(function ($cell) use ($fulfilledMap) {
                $key = $cell->plan_date->format('Y-m-d') . '|' . (int) $cell->row_id;
                $fulfilled = (float) ($fulfilledMap[$key] ?? 0);
                $remaining = max(0, (float) $cell->qty - $fulfilled);
                $cell->remaining_qty = $remaining;
                return $cell;
            })
            ->filter(fn($cell) => (float) ($cell->remaining_qty ?? 0) > 0)
            ->values();

        // Stock at Customers (consignment) map: period|customer_id|gci_part_id => record.
        // We'll subtract available stock from requirements, allocating stock to the latest sequence first
        // so earlier sequences remain prioritized.
        $periods = $cells
            ->pluck('plan_date')
            ->filter()
            ->map(fn($d) => $d->format('Y-m'))
            ->unique()
            ->values();

        $customerIds = $cells
            ->map(fn($c) => $c->row?->gciPart?->customer_id)
            ->filter(fn($v) => $v !== null)
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values();

        $partIds = $cells
            ->map(fn($c) => $c->row?->gci_part_id)
            ->filter(fn($v) => $v !== null)
            ->map(fn($v) => (int) $v)
            ->unique()
            ->values();

        $stockMap = [];
        if ($periods->isNotEmpty() && $customerIds->isNotEmpty() && $partIds->isNotEmpty()) {
            $stockMap = StockAtCustomer::query()
                ->whereIn('period', $periods->all())
                ->whereIn('customer_id', $customerIds->all())
                ->whereIn('gci_part_id', $partIds->all())
                ->get()
                ->mapWithKeys(function ($rec) {
                    $k = (string) $rec->period . '|' . (int) $rec->customer_id . '|' . (int) $rec->gci_part_id;
                    return [$k => $rec];
                })
                ->all();
        }

        $getStockAtCustomer = function (Carbon $date, int $customerId, int $gciPartId) use ($stockMap): float {
            $period = $date->format('Y-m');
            $day = (int) $date->format('j');
            if ($day < 1 || $day > 31) {
                return 0.0;
            }

            $k = $period . '|' . $customerId . '|' . $gciPartId;
            $rec = $stockMap[$k] ?? null;
            if (!$rec) {
                return 0.0;
            }

            return (float) ($rec->{'day_' . $day} ?? 0);
        };

        // Group cells by row_id (each row = unique customer part entry)
        // This ensures each customer part is shown separately even if they map to same GCI Part
        $days = $this->daysBetween($dateFrom, $dateTo);
        $lines = collect();

        foreach ($days as $day) {
            $dateStr = $day->toDateString();
            $dayCells = $cells->filter(fn($c) => $c->plan_date->toDateString() === $dateStr);

            // Group by row_id to show each customer part separately
            $dayCellsByRow = $dayCells->groupBy(fn($c) => (int) ($c->row_id ?? 0));

            foreach ($dayCellsByRow as $rowId => $rowCells) {
                /** @var \Illuminate\Support\Collection $rowCells */
                $firstCell = $rowCells->first();
                $row = $firstCell?->row;

                if (!$row) {
                    continue;
                }

                $gciPart = $row->gciPart;

                // Skip if GCI Part is not FG or not active
                if (!$gciPart || $gciPart->classification !== 'FG' || $gciPart->status !== 'active') {
                    // Handle as unmapped
                    $grossQty = $rowCells->sum(fn($c) => (float) ($c->remaining_qty ?? 0));
                    if ($grossQty <= 0.0001)
                        continue;

                    $sequences = $rowCells->pluck('seq')->filter()->unique()->sort()->values()->all();
                    $primarySequence = !empty($sequences) ? min($sequences) : 9999;

                    $lines->push((object) [
                        'date' => $day->copy(),
                        'customer' => null,
                        'gci_part' => $gciPart,
                        'customer_part_no' => $row->part_no ?? 'UNKNOWN',
                        'customer_part_name' => 'UNMAPPED / INACTIVE',
                        'unmapped' => true,
                        'gross_qty' => $grossQty,
                        'sequence' => $primarySequence,
                        'sequences_consolidated' => $sequences,
                        'packing_std' => 1,
                        'uom' => 'PCS',
                        'source_row_ids' => [$rowId],
                    ]);
                    continue;
                }

                $grossQty = $rowCells->sum(fn($c) => (float) ($c->remaining_qty ?? 0));

                $packingQty = (float) ($gciPart->standardPacking?->packing_qty ?? 1) ?: 1;
                $sequences = $rowCells
                    ->map(fn($c) => $c->seq !== null && $c->seq !== '' ? (int) $c->seq : null)
                    ->filter(fn($s) => $s !== null)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
                $primarySequence = !empty($sequences) ? min($sequences) : 9999;

                $lines->push((object) [
                    'date' => $day->copy(),
                    'customer' => $gciPart->customer,
                    'gci_part' => $gciPart,
                    'customer_part_no' => $row->part_no ?? $gciPart->part_no,  // Show row's part_no (customer part)
                    'customer_part_name' => $gciPart->part_name,
                    'unmapped' => false,
                    'gross_qty' => $grossQty,
                    'sequence' => $primarySequence,
                    'sequences_consolidated' => $sequences,
                    'packing_std' => $packingQty,
                    'uom' => $gciPart->standardPacking?->uom ?? 'PCS',
                    'source_row_ids' => [$rowId],
                ]);
            }

            // Handle cells with no row (orphaned - shouldn't happen but safety check)
            $orphanedCells = $dayCells->filter(fn($c) => !$c->row);
            if ($orphanedCells->isNotEmpty()) {
                $grossQty = $orphanedCells->sum(fn($c) => (float) ($c->remaining_qty ?? 0));
                if ($grossQty > 0.0001) {
                    $lines->push((object) [
                        'date' => $day->copy(),
                        'customer' => null,
                        'gci_part' => null,
                        'customer_part_no' => 'ORPHANED',
                        'customer_part_name' => 'DATA ERROR',
                        'unmapped' => true,
                        'gross_qty' => $grossQty,
                        'sequence' => 9999,
                        'sequences_consolidated' => [],
                        'packing_std' => 1,
                        'uom' => 'PCS',
                        'source_row_ids' => $orphanedCells->pluck('row_id')->unique()->values()->all(),
                    ]);
                }
            }
        }

        // --- PO Non-LG items ---
        $poItems = \App\Models\OutgoingPoItem::query()
            ->whereHas('outgoingPo', fn($q) => $q->where('status', 'confirmed'))
            ->whereNotNull('gci_part_id')
            ->whereColumn('fulfilled_qty', '<', 'qty')
            ->whereNotNull('delivery_date')
            ->whereDate('delivery_date', '>=', $dateFrom->format('Y-m-d'))
            ->whereDate('delivery_date', '<=', $dateTo->format('Y-m-d'))
            ->with(['part', 'part.customer', 'part.standardPacking', 'outgoingPo'])
            ->get();

        foreach ($poItems as $poItem) {
            $gciPart = $poItem->part;
            if (!$gciPart)
                continue;

            $remainingQty = max(0, $poItem->qty - $poItem->fulfilled_qty);
            if ($remainingQty <= 0)
                continue;

            $packingQty = (float) ($gciPart->standardPacking?->packing_qty ?? 1) ?: 1;

            $lines->push((object) [
                'date' => $poItem->delivery_date->copy(),
                'customer' => $gciPart->customer,
                'gci_part' => $gciPart,
                'customer_part_no' => $poItem->vendor_part_name ?? $gciPart->part_no,
                'customer_part_name' => $gciPart->part_name,
                'unmapped' => false,
                'gross_qty' => $remainingQty,
                'sequence' => 9999,
                'sequences_consolidated' => [],
                'packing_std' => $packingQty,
                'uom' => $gciPart->standardPacking?->uom ?? 'PCS',
                'source_row_ids' => [],
                'source' => 'po',
                'outgoing_po_item_id' => $poItem->id,
                'po_no' => $poItem->outgoingPo?->po_no,
            ]);
        }

        // Also add PO part IDs to stock calculation collections
        $poPartIds = $poItems->pluck('gci_part_id')->filter()->unique();
        $poCustomerIds = $poItems->map(fn($i) => $i->part?->customer_id)->filter()->unique();
        $poPeriods = $poItems->pluck('delivery_date')->filter()->map(fn($d) => $d->format('Y-m'))->unique();

        if ($poPeriods->isNotEmpty() && $poCustomerIds->isNotEmpty() && $poPartIds->isNotEmpty()) {
            /** @var \Illuminate\Support\Collection $poStockRecords */
            $poStockRecords = StockAtCustomer::query()
                ->whereIn('period', $poPeriods->all())
                ->whereIn('customer_id', $poCustomerIds->all())
                ->whereIn('gci_part_id', $poPartIds->all())
                ->get();
            foreach ($poStockRecords as $rec) {
                $k = (string) $rec->period . '|' . (int) $rec->customer_id . '|' . (int) $rec->gci_part_id;
                if (!isset($stockMap[$k])) {
                    $stockMap[$k] = $rec;
                }
            }
        }

        // Allocate StockAtCustomer per date+customer+part across sequences (reduce later sequences first).
        /** @var \Illuminate\Support\Collection $requirements */
        $requirements = $lines
            ->map(function (object $r) use ($getStockAtCustomer): object {
                // Calculate quantities for each line (GCI Part / Unmapped) individually
                $date = $r->date;
                $custId = (int) ($r->customer?->id ?? 0);
                $partId = (int) ($r->gci_part?->id ?? 0);

                $stockTotal = 0.0;
                if ($date && $custId > 0 && $partId > 0) {
                    $stockTotal = $getStockAtCustomer($date, $custId, $partId);
                }

                $gross = (float) ($r->gross_qty ?? 0);
                $used = 0.0;
                if ($stockTotal > 0 && $gross > 0) {
                    $used = min($gross, $stockTotal);
                }

                $r->stock_at_customer = $stockTotal;
                $r->stock_used = $used;
                $r->total_qty = max(0, $gross - $used);

                // Recalculate packing
                $packQty = (float) ($r->packing_std ?? 1);
                $packQty = $packQty > 0 ? $packQty : 1;
                $r->packing_load = (int) ceil(((float) $r->total_qty) / $packQty);
                $r->delivery_pack_qty = $r->packing_load * $packQty;

                return $r;
            })
            ->values()
            ->sort(function (object $a, object $b) use ($sortBy, $sortDir): int {
                // PRIORITY 1: PO items go to bottom
                $aIsPo = ($a->source ?? '') === 'po' ? 1 : 0;
                $bIsPo = ($b->source ?? '') === 'po' ? 1 : 0;
                if ($aIsPo !== $bIsPo) {
                    return $aIsPo <=> $bIsPo;
                }

                // PRIORITY 2: Items with value (total_qty > 0) come first
                $aHasValue = ((float) ($a->total_qty ?? 0)) > 0 ? 0 : 1;
                $bHasValue = ((float) ($b->total_qty ?? 0)) > 0 ? 0 : 1;
                if ($aHasValue !== $bHasValue) {
                    return $aHasValue <=> $bHasValue;
                }

                $result = 0;

                switch ($sortBy) {
                    case 'customer':
                        $result = strcmp(
                            (string) ($a->customer?->name ?? ''),
                            (string) ($b->customer?->name ?? '')
                        );
                        break;
                    case 'part':
                        $result = strcmp(
                            (string) ($a->gci_part?->part_no ?? ''),
                            (string) ($b->gci_part?->part_no ?? '')
                        );
                        break;
                    case 'sequence':
                        $result = ($a->sequence ?? 9999) <=> ($b->sequence ?? 9999);
                        break;
                    case 'date':
                    default:
                        if ($a->date->ne($b->date)) {
                            $result = $a->date->gt($b->date) ? 1 : -1;
                        }
                        break;
                }

                // Apply sort direction
                if ($sortDir === 'desc') {
                    $result = -$result;
                }

                // Secondary sort by date if primary sort is not date
                if ($result === 0 && $sortBy !== 'date') {
                    if ($a->date->ne($b->date)) {
                        return $a->date->gt($b->date) ? 1 : -1;
                    }
                }

                // Tertiary sort by sequence as default
                if ($result === 0) {
                    $seqCmp = ($a->sequence ?? 9999) <=> ($b->sequence ?? 9999);
                    if ($seqCmp !== 0) {
                        return $seqCmp;
                    }
                }

                return $result;
            })
            ->values();

        // Pagination Logic
        $page = $request->query('page', 1);
        $perPage = 50;
        $total = $requirements->count();

        $paginatedItems = $requirements->forPage($page, $perPage);

        $requirements = new LengthAwarePaginator(
            $paginatedItems,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('outgoing.delivery_requirements', compact('requirements', 'dateFrom', 'dateTo', 'sortBy', 'sortDir'));
    }

    public function deliveryRequirementsExport(Request $request)
    {
        $dateFrom = $this->parseDate($request->query('date_from')) ?? now()->startOfDay();
        $dateTo = $this->parseDate($request->query('date_to')) ?? now()->startOfDay();
        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        // Re-sync mappings
        $this->syncDailyPlanRowMappings(null, $dateFrom, $dateTo);

        // Build requirements using same logic as deliveryRequirements (without pagination)
        /** @var \Illuminate\Support\Collection $cells */
        $cells = OutgoingDailyPlanCell::query()
            ->with(['row.gciPart.customer', 'row.gciPart.standardPacking'])
            ->whereDate('plan_date', '>=', $dateFrom->format('Y-m-d'))
            ->whereDate('plan_date', '<=', $dateTo->format('Y-m-d'))
            ->get();

        $fulfilledMap = DeliveryRequirementFulfillment::query()
            ->selectRaw('plan_date, row_id, SUM(qty) as fulfilled_qty')
            ->whereDate('plan_date', '>=', $dateFrom->format('Y-m-d'))
            ->whereDate('plan_date', '<=', $dateTo->format('Y-m-d'))
            ->groupBy('plan_date', 'row_id')
            ->get()
            ->mapWithKeys(function ($r) {
                return [(string) $r->plan_date . '|' . (int) $r->row_id => (float) $r->fulfilled_qty];
            })
            ->all();

        /** @var \Illuminate\Support\Collection $cells */
        $cells = $cells
            ->map(function ($cell) use ($fulfilledMap) {
                $key = $cell->plan_date->format('Y-m-d') . '|' . (int) $cell->row_id;
                $cell->remaining_qty = max(0, (float) $cell->qty - (float) ($fulfilledMap[$key] ?? 0));
                return $cell;
            })
            ->filter(fn($cell) => (float) ($cell->remaining_qty ?? 0) > 0)
            ->values();

        // Stock at customer map
        $periods = $cells->pluck('plan_date')->filter()->map(fn($d) => $d->format('Y-m'))->unique()->values();
        $customerIds = $cells->map(fn($c) => $c->row?->gciPart?->customer_id)->filter()->map(fn($v) => (int) $v)->unique()->values();
        $partIds = $cells->map(fn($c) => $c->row?->gci_part_id)->filter()->map(fn($v) => (int) $v)->unique()->values();

        $stockMap = [];
        if ($periods->isNotEmpty() && $customerIds->isNotEmpty() && $partIds->isNotEmpty()) {
            $stockMap = StockAtCustomer::query()
                ->whereIn('period', $periods->all())
                ->whereIn('customer_id', $customerIds->all())
                ->whereIn('gci_part_id', $partIds->all())
                ->get()
                ->mapWithKeys(fn($rec) => [(string) $rec->period . '|' . (int) $rec->customer_id . '|' . (int) $rec->gci_part_id => $rec])
                ->all();
        }

        $getStock = function (Carbon $date, int $custId, int $partId) use ($stockMap): float {
            $k = $date->format('Y-m') . '|' . $custId . '|' . $partId;
            $rec = $stockMap[$k] ?? null;
            if (!$rec)
                return 0.0;
            $day = (int) $date->format('j');
            return ($day >= 1 && $day <= 31) ? (float) ($rec->{'day_' . $day} ?? 0) : 0.0;
        };

        $days = $this->daysBetween($dateFrom, $dateTo);
        $lines = collect();

        foreach ($days as $day) {
            $dateStr = $day->toDateString();
            $dayCells = $cells->filter(fn($c) => $c->plan_date->toDateString() === $dateStr);
            $dayCellsByRow = $dayCells->groupBy(fn($c) => (int) ($c->row_id ?? 0));

            foreach ($dayCellsByRow as $rowId => $rowCells) {
                /** @var \Illuminate\Support\Collection $rowCells */
                $row = $rowCells->first()?->row;
                if (!$row)
                    continue;

                $gciPart = $row->gciPart;
                $grossQty = $rowCells->sum(fn($c) => (float) ($c->remaining_qty ?? 0));
                if ($grossQty <= 0.0001)
                    continue;

                if (!$gciPart || $gciPart->classification !== 'FG' || $gciPart->status !== 'active') {
                    $lines->push((object) [
                        'date' => $day->copy(),
                        'customer' => null,
                        'gci_part' => $gciPart,
                        'customer_part_name' => 'UNMAPPED / INACTIVE',
                        'unmapped' => true,
                        'gross_qty' => $grossQty,
                        'source_row_ids' => [$rowId],
                    ]);
                    continue;
                }

                $lines->push((object) [
                    'date' => $day->copy(),
                    'customer' => $gciPart->customer,
                    'gci_part' => $gciPart,
                    'customer_part_name' => $gciPart->part_name,
                    'unmapped' => false,
                    'gross_qty' => $grossQty,
                    'packing_std' => (float) ($gciPart->standardPacking?->packing_qty ?? 1) ?: 1,
                    'source_row_ids' => [$rowId],
                ]);
            }
        }

        // --- PO Non-LG items (export) ---
        $poItems = \App\Models\OutgoingPoItem::query()
            ->whereHas('outgoingPo', fn($q) => $q->where('status', 'confirmed'))
            ->whereNotNull('gci_part_id')
            ->whereColumn('fulfilled_qty', '<', 'qty')
            ->whereNotNull('delivery_date')
            ->whereDate('delivery_date', '>=', $dateFrom->format('Y-m-d'))
            ->whereDate('delivery_date', '<=', $dateTo->format('Y-m-d'))
            ->with(['part', 'part.customer', 'part.standardPacking', 'outgoingPo'])
            ->get();

        foreach ($poItems as $poItem) {
            $gciPart = $poItem->part;
            if (!$gciPart)
                continue;
            $remainingQty = max(0, $poItem->qty - $poItem->fulfilled_qty);
            if ($remainingQty <= 0)
                continue;

            $lines->push((object) [
                'date' => $poItem->delivery_date->copy(),
                'customer' => $gciPart->customer,
                'gci_part' => $gciPart,
                'customer_part_name' => $gciPart->part_name,
                'unmapped' => false,
                'gross_qty' => $remainingQty,
                'packing_std' => (float) ($gciPart->standardPacking?->packing_qty ?? 1) ?: 1,
                'source_row_ids' => [],
                'source' => 'po',
                'po_no' => $poItem->outgoingPo?->po_no,
            ]);
        }

        /** @var \Illuminate\Support\Collection $requirements */
        $requirements = $lines->map(function (object $r) use ($getStock): object {
            $custId = (int) ($r->customer?->id ?? 0);
            $partId = (int) ($r->gci_part?->id ?? 0);
            $stockTotal = ($r->date && $custId > 0 && $partId > 0) ? $getStock($r->date, $custId, $partId) : 0.0;
            $gross = (float) ($r->gross_qty ?? 0);
            $used = ($stockTotal > 0 && $gross > 0) ? min($gross, $stockTotal) : 0.0;
            $r->stock_at_customer = $stockTotal;
            $r->total_qty = max(0, $gross - $used);
            return $r;
        })->sortBy(fn(object $r): array => [
                (($r->source ?? '') === 'po' ? 1 : 0), // PO items at bottom
                $r->date?->toDateString(),
                $r->gci_part?->part_no ?? ''
            ])->values();

        $dateLabel = $dateFrom->format('d_M_Y');
        $filename = 'delivery_requirements_' . $dateFrom->format('Ymd') . '.xlsx';

        return Excel::download(
            new \App\Exports\DeliveryRequirementsExport($requirements, $dateLabel),
            $filename
        );
    }


    public function deliveryPlan(Request $request)
    {
        $selectedDate = $this->parseDate($request->query('date')) ?? now()->startOfDay();
        $dateStr = $selectedDate->toDateString();

        // Next day for H+1 columns
        $nextDate = $selectedDate->copy()->addDay();
        $nextDateStr = $nextDate->toDateString();

        // SYNC LOGIC: Re-sync gci_part_id from CustomerPartComponent mapping for ALL rows in these dates.
        $this->syncDailyPlanRowMappings(null, $selectedDate, $nextDate);

        // ── 1. Daily Plan data for today (H) ──
        /** @var \Illuminate\Support\Collection $planCells */
        $planCells = OutgoingDailyPlanCell::query()
            ->with(['row'])
            ->whereDate('plan_date', $dateStr)
            ->where('qty', '>', 0)
            ->get();

        // Group by gci_part_id → sum qty
        $dailyPlanMap = []; // gci_part_id => total qty
        foreach ($planCells as $cell) {
            $gciPartId = (int) ($cell->row->gci_part_id ?? 0);
            if ($gciPartId <= 0)
                continue;
            $dailyPlanMap[$gciPartId] = ($dailyPlanMap[$gciPartId] ?? 0) + (int) $cell->qty;
        }

        // ── 2. Daily Plan data for H+1 ──
        /** @var \Illuminate\Support\Collection $planCellsH1 */
        $planCellsH1 = OutgoingDailyPlanCell::query()
            ->with(['row'])
            ->whereDate('plan_date', $nextDateStr)
            ->where('qty', '>', 0)
            ->get();

        $dailyPlanH1Map = [];
        foreach ($planCellsH1 as $cell) {
            $gciPartId = (int) ($cell->row->gci_part_id ?? 0);
            if ($gciPartId <= 0)
                continue;
            $dailyPlanH1Map[$gciPartId] = ($dailyPlanH1Map[$gciPartId] ?? 0) + (int) $cell->qty;
        }

        // ── 3. Collect all relevant GCI Part IDs ──
        $allPartIds = collect(array_keys($dailyPlanMap))
            ->merge(array_keys($dailyPlanH1Map))
            ->unique()
            ->values();

        // Also include parts that already have delivery planning lines for this date (daily_plan only)
        $existingLines = OutgoingDeliveryPlanningLine::where('delivery_date', $dateStr)
            ->where(function ($q) {
                $q->where('source', 'daily_plan')->orWhereNull('source');
            })
            ->get();
        $allPartIds = $allPartIds->merge($existingLines->pluck('gci_part_id'))->unique()->values();

        // PO Non-LG items for this date
        $poItems = \App\Models\OutgoingPoItem::query()
            ->whereHas('outgoingPo', fn($q) => $q->where('status', 'confirmed'))
            ->whereNotNull('gci_part_id')
            ->whereColumn('fulfilled_qty', '<', 'qty')
            ->whereDate('delivery_date', $dateStr)
            ->with(['part', 'part.customer', 'part.standardPacking', 'outgoingPo'])
            ->get();

        $poPartIds = $poItems->pluck('gci_part_id')->filter()->unique();

        // PO delivery planning lines
        $poExistingLines = OutgoingDeliveryPlanningLine::where('delivery_date', $dateStr)
            ->where('source', 'po')
            ->get()
            ->keyBy('gci_part_id');

        // Merge PO part IDs but keep them separate for row building
        $allPartIds = $allPartIds->merge($poPartIds)->unique()->values();

        if ($allPartIds->isEmpty()) {
            return view('outgoing.delivery_plan', [
                'selectedDate' => $selectedDate,
                'rows' => collect(),
            ]);
        }

        // ── 4. Load FG Part master data (only active FG parts) ──
        $parts = GciPart::with('customer')
            ->whereIn('id', $allPartIds)
            ->where('classification', 'FG')
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        // Filter to only FG parts
        $allPartIds = $allPartIds->filter(fn($id) => $parts->has($id))->values();

        // ── 5. Stock at Customer ──
        $period = $selectedDate->format('Y-m');
        $dayCol = 'day_' . (int) $selectedDate->format('j');

        $stockMap = StockAtCustomer::where('period', $period)
            ->whereIn('gci_part_id', $allPartIds)
            ->get()
            ->groupBy('gci_part_id')
            ->map(fn($recs) => $recs->sum(fn($r) => (float) ($r->{$dayCol} ?? 0)));

        // ── 6. Standard Packing ──
        $stdPackMap = StandardPacking::whereIn('gci_part_id', $allPartIds)
            ->where('status', 'active')
            ->get()
            ->keyBy('gci_part_id');

        // ── 7. JIG Settings (grouped by gci_part_id via customer part mapping) ──
        $jigSettings = OutgoingJigSetting::with([
            'customerPart.components.part',
            'plans' => function ($q) use ($dateStr, $nextDateStr) {
                $q->whereIn('plan_date', [$dateStr, $nextDateStr]);
            }
        ])->get();

        // Map jig to gci_part_id through customer part components
        $jigsByPart = []; // gci_part_id => [jig settings]
        foreach ($jigSettings as $jig) {
            $cp = $jig->customerPart;
            if (!$cp)
                continue;
            foreach ($cp->components ?? [] as $comp) {
                if ($comp->part && $comp->part->classification === 'FG') {
                    $partId = (int) $comp->gci_part_id;
                    $jigsByPart[$partId][] = $jig;
                }
            }
        }

        // ── 8. Existing delivery planning lines (trip data) ──
        $deliveryLines = $existingLines->keyBy('gci_part_id');

        // ── 9. Build display rows ──
        $rows = collect();
        foreach ($allPartIds as $partId) {
            $part = $parts->get($partId);
            if (!$part)
                continue;

            $stockAtCust = (int) ($stockMap->get($partId) ?? 0);
            $dailyPlanQty = (int) ($dailyPlanMap[$partId] ?? 0);
            $dailyPlanH1Qty = (int) ($dailyPlanH1Map[$partId] ?? 0);
            $stdPack = $stdPackMap->get($partId);
            $stdPackQty = $stdPack ? (int) $stdPack->packing_qty : 0;

            $deliveryReq = max(0, $dailyPlanQty - $stockAtCust);

            // Trip data
            $line = $deliveryLines->get($partId);
            $trips = [];
            $totalTrips = 0;
            for ($t = 1; $t <= 14; $t++) {
                $val = $line ? (int) $line->{"trip_{$t}"} : 0;
                $trips[$t] = $val;
                $totalTrips += $val;
            }

            // Jig info for this part
            $partJigs = $jigsByPart[$partId] ?? [];
            $jigRows = [];
            $totalJigQtyH1 = 0;
            foreach ($partJigs as $jig) {
                $plans = $jig->plans->keyBy(fn($p) => $p->plan_date->format('Y-m-d'));
                $jigQtyH = (int) ($plans->get($dateStr)?->jig_qty ?? 0);
                $jigQtyH1 = (int) ($plans->get($nextDateStr)?->jig_qty ?? 0);
                $totalJigQtyH1 += $jigQtyH1;
                $jigRows[] = (object) [
                    'jig_name' => $jig->customerPart?->customer_part_name ?? $jig->customerPart?->customer_part_no ?? '-',
                    'jig_qty' => $jigQtyH,
                    'uph' => (int) $jig->uph,
                    'jig_qty_h1' => $jigQtyH1,
                ];
            }

            // OSP Status check
            $isOsp = \App\Models\BomItem::where('special', 'OSP')
                ->whereHas('bom', fn(\Illuminate\Database\Eloquent\Builder $q) => $q->where('part_id', $partId))
                ->exists();
            $ospOrder = null;
            if ($isOsp) {
                $ospOrder = \App\Models\OspOrder::where('gci_part_id', $partId)
                    ->where('status', '!=', 'shipped')
                    ->latest()
                    ->first();
            }

            // Production rate = sum(jig_qty × UPH) for today
            $productionRate = collect($jigRows)->sum(fn($j) => $j->jig_qty * $j->uph);

            // Finish time = 07:00 + delivery_requirement / production_rate (format HH:MM)
            if ($productionRate > 0 && $deliveryReq > 0) {
                $decimalHours = 7.0 + $deliveryReq / $productionRate;
                $hours = (int) floor($decimalHours);
                $minutes = (int) round(($decimalHours - $hours) * 60);
                if ($minutes === 60) {
                    $hours++;
                    $minutes = 0;
                }
                $finishTime = sprintf('%02d:%02d', $hours, $minutes);
            } else {
                $finishTime = null;
            }

            // End stock at customer = stock + totalTrips - dailyPlanQty
            $endStock = $stockAtCust + $totalTrips - $dailyPlanQty;

            // Production rate H+1 = sum(jig_qty_h1 × UPH)
            $productionRateH1 = collect($jigRows)->sum(fn($j) => $j->jig_qty_h1 * $j->uph);

            // Est. finish time H+1 = 07:00 + end_stock_customer / total_uph_h1 (format HH:MM)
            if ($productionRateH1 > 0 && $endStock > 0) {
                $decimalHours = 7.0 + $endStock / $productionRateH1;
                $hours = (int) floor($decimalHours);
                $minutes = (int) round(($decimalHours - $hours) * 60);
                if ($minutes === 60) {
                    $hours++;
                    $minutes = 0;
                }
                $estFinishTime = sprintf('%02d:%02d', $hours, $minutes);
            } else {
                $estFinishTime = null;
            }

            // Determine product group from part_name
            $partName = strtolower($part->part_name ?? '');
            if (str_contains($partName, 'base') || str_contains($partName, 'compressor base')) {
                $category = 'Base Comp';
            } elseif (str_contains($partName, 'plate')) {
                $category = 'Plate Rear';
            } elseif (str_contains($partName, 'reinforce')) {
                $category = 'Reinforce';
            } elseif (str_contains($partName, 'tray')) {
                $category = 'Tray Drip';
            } else {
                $category = 'Small Part';
            }

            $rows->push((object) [
                'gci_part_id' => $partId,
                'customer_id' => $part->customer_id ?? 0,
                'category' => $category,
                'fg_part_name' => $part->part_name ?? '-',
                'fg_part_no' => $part->part_no ?? '-',
                'model' => $part->model ?? '-',
                'stock_at_customer' => $stockAtCust,
                'daily_plan_qty' => $dailyPlanQty,
                'delivery_requirement' => $deliveryReq,
                'std_packing' => $stdPackQty,
                'production_rate' => $productionRate,
                'production_rate_h1' => $productionRateH1,
                'jigs' => $jigRows,
                'trips' => $trips,
                'total_trips' => $totalTrips,
                'finish_time' => $finishTime,
                'end_stock' => $endStock,
                'daily_plan_h1' => $dailyPlanH1Qty,
                'jig_qty_h1' => $totalJigQtyH1,
                'delivery_req_h1' => max(0, $dailyPlanH1Qty - max(0, $endStock)),
                'est_finish_time' => $estFinishTime,
                'is_osp' => $isOsp,
                'osp_order' => $ospOrder,
                'has_line' => $line !== null,
                'line_id' => $line?->id,
                'source' => 'daily_plan',
                'outgoing_po_item_id' => null,
                'po_no' => null,
            ]);
        }

        // ── 10. PO Non-LG rows ──
        foreach ($poItems as $poItem) {
            $part = $poItem->part;
            if (!$part || $part->classification !== 'FG' || $part->status !== 'active')
                continue;

            $partId = (int) $poItem->gci_part_id;

            // Skip if already in daily plan rows (same part)
            if (isset($dailyPlanMap[$partId]))
                continue;

            $remainingQty = max(0, $poItem->qty - $poItem->fulfilled_qty);
            if ($remainingQty <= 0)
                continue;

            $stockAtCust = (int) ($stockMap->get($partId) ?? 0);
            $stdPack = $stdPackMap->get($partId);
            $stdPackQty = $stdPack ? (int) $stdPack->packing_qty : 0;

            $deliveryReq = $remainingQty;

            // Trip data from PO-sourced lines
            $line = $poExistingLines->get($partId);
            $trips = [];
            $totalTrips = 0;
            for ($t = 1; $t <= 14; $t++) {
                $val = $line ? (int) $line->{"trip_{$t}"} : 0;
                $trips[$t] = $val;
                $totalTrips += $val;
            }

            $rows->push((object) [
                'gci_part_id' => $partId,
                'customer_id' => $part->customer_id ?? 0,
                'category' => 'NON LG',
                'fg_part_name' => $part->part_name ?? '-',
                'fg_part_no' => $part->part_no ?? '-',
                'model' => $part->model ?? '-',
                'stock_at_customer' => $stockAtCust,
                'daily_plan_qty' => $remainingQty,
                'delivery_requirement' => $deliveryReq,
                'std_packing' => $stdPackQty,
                'production_rate' => null,
                'production_rate_h1' => null,
                'jigs' => [],
                'trips' => $trips,
                'total_trips' => $totalTrips,
                'finish_time' => null,
                'end_stock' => $stockAtCust + $totalTrips - $remainingQty,
                'daily_plan_h1' => 0,
                'jig_qty_h1' => 0,
                'delivery_req_h1' => 0,
                'est_finish_time' => null,
                'is_osp' => false,
                'osp_order' => null,
                'has_line' => $line !== null,
                'line_id' => $line?->id,
                'source' => 'po',
                'outgoing_po_item_id' => $poItem->id,
                'po_no' => $poItem->outgoingPo?->po_no,
            ]);
        }

        // ── 11. Check existing DOs for each part on this date ──
        $existingDos = DeliveryOrder::where('do_date', $dateStr)
            ->with('items')
            ->get();
        $doByPart = [];
        foreach ($existingDos as $do) {
            foreach ($do->items as $item) {
                $doByPart[$item->gci_part_id][] = [
                    'do_no' => $do->do_no,
                    'status' => $do->status,
                    'trip_no' => $do->trip_no,
                ];
            }
        }

        // Attach DO info to rows
        foreach ($rows as $row) {
            $row->existing_dos = $doByPart[$row->gci_part_id] ?? [];
            $row->has_do = !empty($row->existing_dos);
        }

        // Sort by fixed category order, then part name
        $categoryOrder = ['Base Comp' => 1, 'Plate Rear' => 2, 'Reinforce' => 3, 'Tray Drip' => 4, 'Small Part' => 5, 'NON LG' => 6];
        $rows = $rows->sort(function (object $a, object $b) use ($categoryOrder): int {
            $catCmp = ($categoryOrder[$a->category ?? ''] ?? 99) <=> ($categoryOrder[$b->category ?? ''] ?? 99);
            if ($catCmp !== 0) {
                return $catCmp;
            }
            return strcmp((string) ($a->fg_part_name ?? ''), (string) ($b->fg_part_name ?? ''));
        })->values();

        return view('outgoing.delivery_plan', [
            'selectedDate' => $selectedDate,
            'rows' => $rows,
        ]);
    }

    /**
     * AJAX: Update a single trip cell value.
     */
    public function updateDeliveryPlanTrip(Request $request)
    {
        $request->validate([
            'delivery_date' => 'required|date',
            'gci_part_id' => 'required|integer|exists:gci_parts,id',
            'trip_no' => 'required|integer|min:1|max:14',
            'qty' => 'required|integer|min:0',
            'source' => 'nullable|string|in:daily_plan,po',
            'outgoing_po_item_id' => 'nullable|integer|exists:outgoing_po_items,id',
        ]);

        $source = $request->input('source', 'daily_plan');

        $line = OutgoingDeliveryPlanningLine::updateOrCreate(
            [
                'delivery_date' => $request->delivery_date,
                'gci_part_id' => $request->gci_part_id,
                'source' => $source,
            ],
            array_filter([
                'trip_' . $request->trip_no => $request->qty,
                'outgoing_po_item_id' => $request->outgoing_po_item_id,
            ], fn($v) => $v !== null)
        );

        return response()->json([
            'success' => true,
            'total' => $line->total_trips,
        ]);
    }

    /**
     * AJAX: Bulk update trip values.
     */
    public function updateDeliveryPlanTrips(Request $request)
    {
        $request->validate([
            'delivery_date' => 'required|date',
            'data' => 'required|array',
            'data.*.gci_part_id' => 'required|integer',
            'data.*.trip_no' => 'required|integer|min:1|max:14',
            'data.*.qty' => 'required|integer|min:0',
            'data.*.source' => 'nullable|string',
            'data.*.outgoing_po_item_id' => 'nullable|integer',
        ]);

        $deliveryDate = $request->delivery_date;
        $data = $request->input('data', []);

        DB::transaction(function () use ($deliveryDate, $data) {
            foreach ($data as $item) {
                $source = $item['source'] ?? 'daily_plan';

                OutgoingDeliveryPlanningLine::updateOrCreate(
                    [
                        'delivery_date' => $deliveryDate,
                        'gci_part_id' => $item['gci_part_id'],
                        'source' => $source,
                    ],
                    array_filter([
                        'trip_' . $item['trip_no'] => $item['qty'],
                        'outgoing_po_item_id' => $item['outgoing_po_item_id'] ?? null,
                    ], fn($v) => $v !== null)
                );
            }
        });

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────
    // Stock at Customers
    // ──────────────────────────────────────────────

    public function stockAtCustomers(Request $request)
    {
        $period = $request->input('period', now()->format('Y-m'));
        $date = CarbonImmutable::parse($period . '-01');
        $days = range(1, $date->daysInMonth);

        $records = StockAtCustomer::query()
            ->with(['customer', 'part'])
            ->where('period', $period)
            ->orderBy('customer_id')
            ->orderBy('part_no')
            ->paginate(50)
            ->appends(['period' => $period]);

        return view('outgoing.stock_at_customers', compact('period', 'days', 'records'));
    }

    public function stockAtCustomersTemplate(Request $request)
    {
        $period = $request->input('period', now()->format('Y-m'));
        return Excel::download(
            new StockAtCustomersExport($period),
            "stock_at_customers_template_{$period}.xlsx"
        );
    }

    public function stockAtCustomersExport(Request $request)
    {
        $period = $request->input('period', now()->format('Y-m'));
        return Excel::download(
            new StockAtCustomersExport($period),
            "stock_at_customers_{$period}.xlsx"
        );
    }

    public function stockAtCustomersImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'period' => 'required|date_format:Y-m',
        ]);

        $import = new StockAtCustomersImport($request->period);
        Excel::import($import, $request->file('file'));

        $msg = "Imported {$import->rowCount} rows.";
        if ($import->skippedRows > 0) {
            $msg .= " Skipped {$import->skippedRows} empty rows.";
        }

        if (!empty($import->failures)) {
            $msg .= '<br>' . implode('<br>', array_slice($import->failures, 0, 10));
            return back()->with('error', $msg);
        }

        return back()->with('success', $msg);
    }

    private function parseDate($val)
    {
        if (!$val)
            return null;
        try {
            return Carbon::parse($val)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function daysBetween($start, $end)
    {
        $out = [];
        $curr = $start->copy();
        while ($curr->lte($end)) {
            $out[] = $curr->copy();
            $curr->addDay();
        }
        return $out;
    }

    /**
     * Sync gci_part_id from CustomerPartComponent mappings for rows in the given plan and date range.
     * This ensures that when customer part mappings are updated in Planning module,
     * the Daily Plan rows are updated accordingly.
     * 
     * @param int|null $planId If null, sync all plans
     */
    private function syncDailyPlanRowMappings(?int $planId, Carbon $dateFrom, Carbon $dateTo): void
    {
        // Step 1: Rows that have a customer_part_id BUT NO gci_part_id - sync from CustomerPartComponent
        // IMPORTANT: We now skip rows that ALREADY have gci_part_id set, because the import
        // correctly explodes each CustomerPart's components into separate rows with distinct gci_part_ids.
        $rowsWithCustomerPart = OutgoingDailyPlanCell::query()
            ->join('outgoing_daily_plan_rows as r', 'r.id', '=', 'outgoing_daily_plan_cells.row_id')
            ->whereDate('outgoing_daily_plan_cells.plan_date', '>=', $dateFrom->format('Y-m-d'))
            ->whereDate('outgoing_daily_plan_cells.plan_date', '<=', $dateTo->format('Y-m-d'))
            ->where('outgoing_daily_plan_cells.qty', '>', 0)
            ->when($planId !== null, fn($q) => $q->where('r.plan_id', $planId))
            ->whereNotNull('r.customer_part_id')
            ->whereNull('r.gci_part_id')  // ONLY sync rows that DON'T have gci_part_id yet
            ->distinct()
            ->select('r.id', 'r.customer_part_id', 'r.gci_part_id')
            ->get();

        if ($rowsWithCustomerPart->isNotEmpty()) {
            $customerPartIds = $rowsWithCustomerPart->pluck('customer_part_id')->unique()->filter()->values()->all();

            // Get current FG mappings from CustomerPartComponent (first one only for legacy compatibility)
            $currentMappings = CustomerPartComponent::query()
                ->whereIn('customer_part_id', $customerPartIds)
                ->whereHas('part', fn($q) => $q->where('classification', 'FG'))
                ->get()
                ->keyBy('customer_part_id');

            foreach ($rowsWithCustomerPart as $row) {
                $cpId = (int) $row->customer_part_id;
                $currentMapping = $currentMappings->get($cpId);
                $expectedGciPartId = $currentMapping ? (int) $currentMapping->gci_part_id : null;

                if ($expectedGciPartId) {
                    OutgoingDailyPlanRow::query()
                        ->whereKey($row->id)
                        ->update(['gci_part_id' => $expectedGciPartId]);
                }
            }
        }

        // Step 2: Rows without customer_part_id but missing gci_part_id - try to resolve from part_no
        $rowIdsNeedingFix = OutgoingDailyPlanCell::query()
            ->join('outgoing_daily_plan_rows as r', 'r.id', '=', 'outgoing_daily_plan_cells.row_id')
            ->whereDate('outgoing_daily_plan_cells.plan_date', '>=', $dateFrom->format('Y-m-d'))
            ->whereDate('outgoing_daily_plan_cells.plan_date', '<=', $dateTo->format('Y-m-d'))
            ->where('outgoing_daily_plan_cells.qty', '>', 0)
            ->when($planId !== null, fn($q) => $q->where('r.plan_id', $planId))
            ->whereNull('r.gci_part_id')
            ->whereNull('r.customer_part_id')
            ->distinct()
            ->pluck('r.id');

        if ($rowIdsNeedingFix->isNotEmpty()) {
            OutgoingDailyPlanRow::query()
                ->whereIn('id', $rowIdsNeedingFix)
                ->select(['id', 'part_no'])
                ->chunk(200, function ($rows) {
                    foreach ($rows as $row) {
                        $partNo = $this->normalizePartNo((string) ($row->part_no ?? ''));
                        if ($partNo === '') {
                            continue;
                        }
                        $resolvedId = $this->resolveFgPartIdFromPartNo($partNo);
                        if ($resolvedId) {
                            OutgoingDailyPlanRow::query()->whereKey($row->id)->update(['gci_part_id' => $resolvedId]);
                        }
                    }
                });
        }
    }

    public function normalizePartNo(string $partNo): string
    {
        $partNo = trim($partNo);
        // Remove spaces inside too? Usually standard parts like 'A B C' -> 'ABC' or 'A-B-C' -> 'ABC'
        // For now, strict trim.
        // Some logic might remove dashes if consistent standard is needed.
        return strtoupper($partNo);
    }

    public function resolveFgPartIdFromPartNo(string $partNo): ?int
    {
        $partNo = $this->normalizePartNo($partNo);
        if ($partNo === '') {
            return null;
        }

        // 1. Check Customer Part mapping (Prioritize Mapping as requested)
        $cp = CustomerPart::query()
            ->where('customer_part_no', $partNo)
            ->first();

        if ($cp) {
            // Get first component that is FG
            foreach ($cp->components as $comp) {
                if ($comp->part && $comp->part->classification === 'FG') {
                    return (int) $comp->part->id;
                }
            }
        }

        // 2. Direct match in GciPart (FG)
        $fg = GciPart::where('classification', 'FG')
            ->where(function ($q) use ($partNo) {
                $q->where('part_no', $partNo)
                    ->orWhere('part_no', str_replace(['-', ' ', '/', '.', '_'], '', $partNo)); // loose match
            })
            ->first();
        if ($fg) {
            return (int) $fg->id;
        }

        return null;
    }

    public function generateDoFromDeliveryPlan(Request $request)
    {
        try {
            $validated = $request->validate([
                'date' => ['required', 'date'],
                'selected' => ['required', 'array', 'min:1'],
                'selected.*' => ['integer', 'min:0'],
                'lines' => ['required', 'array'],
                'lines.*.gci_part_id' => ['nullable', 'integer'],
                'lines.*.customer_id' => ['nullable', 'integer'],
                'lines.*.qty' => ['nullable', 'numeric'],
                'lines.*.part_no' => ['nullable', 'string'],
                'lines.*.part_name' => ['nullable', 'string'],
                'lines.*.source' => ['nullable', 'string'],
            ]);

            $planDate = $validated['date'];
            $selectedIdx = collect($validated['selected'])->map(fn($v) => (int) $v)->unique()->values();
            $lines = collect($validated['lines']);

            $selectedLines = $selectedIdx
                ->map(fn(int $i) => is_array($lines->get($i)) ? array_merge(['_idx' => $i], $lines->get($i)) : null)
                ->filter()
                ->values();

            if ($selectedLines->isEmpty()) {
                \Log::warning('Generate DO: No lines selected', ['date' => $planDate]);
                return back()->with('error', 'Pilih minimal 1 part untuk generate DO.');
            }

            // Validate selected lines have required data
            foreach ($selectedLines as $idx => $line) {
                if (empty($line['gci_part_id'])) {
                    return back()->with('error', "Baris #" . ($idx + 1) . ": GCI Part ID tidak valid.");
                }
                if (empty($line['customer_id'])) {
                    return back()->with('error', "Baris #" . ($idx + 1) . ": Customer ID tidak valid.");
                }
                if (!isset($line['qty']) || $line['qty'] <= 0) {
                    return back()->with('error', "Baris #" . ($idx + 1) . ": Qty harus lebih dari 0.");
                }
            }

            // Validate all customers exist first
            $customerIds = $selectedLines->pluck('customer_id')->unique()->values();
            $existingCustomers = Customer::whereIn('id', $customerIds)->pluck('id');
            $invalidCustomers = $customerIds->diff($existingCustomers);

            if ($invalidCustomers->isNotEmpty()) {
                \Log::error('Generate DO: Invalid customers', [
                    'invalid_ids' => $invalidCustomers->toArray(),
                    'plan_date' => $planDate
                ]);
                return back()->with('error', 'Ada customer yang tidak valid: ' . $invalidCustomers->implode(', '));
            }

            // Validate all GCI parts exist
            $partIds = $selectedLines->pluck('gci_part_id')->unique()->values();
            $existingParts = GciPart::whereIn('id', $partIds)->pluck('id');
            $invalidParts = $partIds->diff($existingParts);

            if ($invalidParts->isNotEmpty()) {
                \Log::error('Generate DO: Invalid GCI parts', [
                    'invalid_ids' => $invalidParts->toArray(),
                    'plan_date' => $planDate
                ]);
                return back()->with('error', 'Ada GCI part yang tidak valid: ' . $invalidParts->implode(', '));
            }

            $created = [];
            $updated = [];
            $skipped = [];

            DB::transaction(function () use ($selectedLines, $planDate, &$created, &$updated, &$skipped) {
                \Log::info('Generate DO Transaction Start', [
                    'plan_date' => $planDate,
                    'lines_count' => $selectedLines->count()
                ]);

                foreach ($selectedLines as $line) {
                    $customerId = (int) $line['customer_id'];
                    $partId = (int) $line['gci_part_id'];
                    $source = $line['source'] ?? 'daily_plan';

                    $planningLine = OutgoingDeliveryPlanningLine::where('delivery_date', $planDate)
                        ->where('gci_part_id', $partId)
                        ->where('source', $source)
                        ->first();

                    if (!$planningLine) {
                        \Log::warning('Generate DO: Planning line not found', [
                            'plan_date' => $planDate,
                            'gci_part_id' => $partId,
                            'source' => $source
                        ]);
                        continue;
                    }

                    for ($t = 1; $t <= 14; $t++) {
                        $tripQty = (int) $planningLine->{"trip_{$t}"};
                        if ($tripQty <= 0)
                            continue;

                        // Check for existing DO (any status) for this customer+date+trip
                        $do = DeliveryOrder::where('customer_id', $customerId)
                            ->where('do_date', $planDate)
                            ->where('trip_no', $t)
                            ->first();

                        if ($do && !in_array($do->status, ['draft', 'pending'])) {
                            // DO already shipped/completed → skip
                            $skipped[$do->do_no] = $do->status;
                            continue;
                        }

                        $isNew = !$do;

                        if (!$do) {
                            $doNo = $this->generateDoNumber($planDate, $t);
                            \Log::info('Creating new DO', [
                                'do_no' => $doNo,
                                'customer_id' => $customerId,
                                'trip_no' => $t,
                                'plan_date' => $planDate
                            ]);

                            $do = DeliveryOrder::create([
                                'do_no' => $doNo,
                                'customer_id' => $customerId,
                                'do_date' => $planDate,
                                'trip_no' => $t,
                                'status' => 'draft',
                                'notes' => "Generated from Delivery Planning (Trip {$t})",
                                'created_by' => auth()->id(),
                            ]);
                            $created[] = $do->do_no;
                        } else {
                            \Log::info('Updating existing DO', [
                                'do_no' => $do->do_no,
                                'do_id' => $do->id
                            ]);
                            $updated[] = $do->do_no;
                        }

                        // Upsert DO item - set qty (not accumulate) to allow regenerate
                        $doItem = DeliveryOrderItem::where('delivery_order_id', $do->id)
                            ->where('gci_part_id', $partId)
                            ->first();

                        if ($doItem) {
                            $doItem->update(['qty_ordered' => $tripQty]);
                        } else {
                            DeliveryOrderItem::create([
                                'delivery_order_id' => $do->id,
                                'gci_part_id' => $partId,
                                'qty_ordered' => $tripQty,
                                'qty_shipped' => 0,
                            ]);
                        }

                        // Create/Update Picking record linked to this DO
                        $pickingFg = OutgoingPickingFg::firstOrNew([
                            'delivery_date' => $planDate,
                            'gci_part_id' => $partId,
                            'delivery_order_id' => $do->id,
                        ]);

                        $isNewPicking = !$pickingFg->exists;
                        $pickingFg->qty_plan = $tripQty;
                        if (!$pickingFg->exists) {
                            $pickingFg->status = 'pending';
                            $pickingFg->qty_picked = 0;
                        } elseif ($pickingFg->qty_picked > 0 && $pickingFg->qty_picked < $pickingFg->qty_plan) {
                            $pickingFg->status = 'picking';
                        } elseif ($pickingFg->qty_picked >= $pickingFg->qty_plan) {
                            $pickingFg->status = 'completed';
                        }
                        $pickingFg->source = $planningLine->source ?: 'daily_plan';
                        $pickingFg->created_by = auth()->id();

                        try {
                            $pickingFg->save();
                            \Log::info($isNewPicking ? 'Created Picking FG' : 'Updated Picking FG', [
                                'picking_id' => $pickingFg->id,
                                'do_id' => $do->id,
                                'gci_part_id' => $partId,
                                'qty_plan' => $tripQty,
                                'status' => $pickingFg->status
                            ]);
                        } catch (\Exception $e) {
                            \Log::error('Failed to save Picking FG', [
                                'error' => $e->getMessage(),
                                'do_id' => $do->id,
                                'gci_part_id' => $partId,
                                'delivery_date' => $planDate
                            ]);
                            throw $e;
                        }
                    }
                }
            });

            // Build feedback message
            $messages = [];
            $created = array_unique($created);
            $updated = array_unique($updated);
            if (count($created) > 0) {
                $messages[] = count($created) . ' DO created: ' . implode(', ', $created);
            }
            if (count($updated) > 0) {
                $messages[] = count($updated) . ' DO updated: ' . implode(', ', $updated);
            }
            if (count($skipped) > 0) {
                $skippedList = collect($skipped)->map(fn($status, $no) => "{$no} ({$status})")->implode(', ');
                $messages[] = count($skipped) . ' DO skipped (non-draft): ' . $skippedList;
            }

            if (empty($messages)) {
                return back()->with('info', 'Tidak ada DO yang di-generate. Pastikan trip qty sudah diisi.');
            }

            $type = count($skipped) > 0 && count($created) === 0 && count($updated) === 0 ? 'warning' : 'success';
            return back()->with($type, implode(' | ', $messages));

        } catch (\Exception $e) {
            \Log::error('Generate DO Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'plan_date' => $request->input('date'),
                'selected_count' => count($request->input('selected', [])),
            ]);

            return back()->with('error', 'Gagal generate DO: ' . $e->getMessage() . ' (Line: ' . $e->getLine() . ')');
        }
    }

    private function generateDoNumber(string $planDate, int $tripNo): string
    {
        $dateStr = Carbon::parse($planDate)->format('Ymd');
        $prefix = "DO-{$dateStr}-T{$tripNo}-";

        $lastDo = DeliveryOrder::where('do_no', 'like', $prefix . '%')
            ->orderByRaw('LENGTH(do_no) DESC, do_no DESC')
            ->first();

        $nextSeq = 1;
        if ($lastDo) {
            $lastSeqStr = str_replace($prefix, '', $lastDo->do_no);
            $nextSeq = ((int) $lastSeqStr) + 1;
        }

        return $prefix . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);
    }
}
