<?php

namespace App\Http\Controllers;

use App\Exports\OutgoingDailyPlanningExport;
use App\Exports\OutgoingDailyPlanningTemplateExport;
use App\Exports\StockAtCustomersExport;
use App\Imports\StockAtCustomersImport;
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
use App\Models\SalesOrderItem;
use App\Models\SalesOrder;
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

        $days = $this->daysBetween($dateFrom, $dateTo);

        // Fetch rows based on the plan
        if ($plan) {
            $rows = $plan->rows()
                ->with([
                    'gciPart.standardPacking',
                    'customerPart',
                    'cells' => function ($query) use ($dateFrom, $dateTo) {
                        $query->whereBetween('plan_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);
                    }
                ])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('part_no', 'like', '%' . $search . '%')
                            ->orWhereHas('gciPart', function ($sq) use ($search) {
                                $sq->where('part_name', 'like', '%' . $search . '%');
                            });
                    });
                })
                ->whereHas('cells', function ($query) use ($dateFrom, $dateTo) {
                    $query->whereBetween('plan_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                        ->where('qty', '>', 0);
                })
                ->paginate($perPage)
                ->withQueryString();
        } else {
            // Empty paginator if no plan
            $rows = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
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
            $unmappedCount = $plan->rows()->whereNull('gci_part_id')->count();
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
            $msg .= ' (Info: ' . count($import->createdParts) . ' part baru otomatis didaftarkan: ' . implode(', ', array_slice($import->createdParts, 0, 5)) . (count($import->createdParts) > 5 ? '...' : '') . ')';
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
            $customerProducts = \App\Models\CustomerPartComponent::query()
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

        // Best-effort: backfill missing gci_part_id for rows that have demand in this date range.
        $rowIdsNeedingFix = OutgoingDailyPlanCell::query()
            ->join('outgoing_daily_plan_rows as r', 'r.id', '=', 'outgoing_daily_plan_cells.row_id')
            ->whereDate('outgoing_daily_plan_cells.plan_date', '>=', $dateFrom->format('Y-m-d'))
            ->whereDate('outgoing_daily_plan_cells.plan_date', '<=', $dateTo->format('Y-m-d'))
            ->where('outgoing_daily_plan_cells.qty', '>', 0)
            ->whereNull('r.gci_part_id')
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

        // Fetch planned cells within range (we will subtract fulfillments below)
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

        // NEW: Get all active FG Parts to ensure a static list
        $fgParts = GciPart::query()
            ->where('classification', 'FG')
            ->where('status', 'active')
            ->with(['customer', 'standardPacking'])
            ->get();

        // Map cells by Part ID for merging
        $cellsByPart = $cells->groupBy(fn($c) => (int) ($c->row?->gci_part_id ?? 0));

        $days = $this->daysBetween($dateFrom, $dateTo);
        $lines = collect();

        foreach ($days as $day) {
            $dateStr = $day->toDateString();
            $dayCells = $cells->filter(fn($c) => $c->plan_date->toDateString() === $dateStr);
            $dayCellsByPart = $dayCells->groupBy(fn($c) => (int) ($c->row?->gci_part_id ?? 0));

            foreach ($fgParts as $gciPart) {
                $partId = (int) $gciPart->id;
                $partDayCells = $dayCellsByPart->get($partId, collect());

                // Consolidated logic: One row per FG Part No, summing all demand.
                $grossQty = $partDayCells->sum(fn($c) => (float) ($c->remaining_qty ?? 0));

                $packingQty = (float) ($gciPart->standardPacking?->packing_qty ?? 1) ?: 1;
                $sequences = $partDayCells
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
                    'customer_part_no' => $gciPart->part_no,
                    'customer_part_name' => $gciPart->part_name,
                    'unmapped' => false,
                    'gross_qty' => $grossQty,
                    'sequence' => $primarySequence,
                    'sequences_consolidated' => $sequences,
                    'packing_std' => $packingQty,
                    'uom' => $gciPart->standardPacking?->uom ?? 'PCS',
                    'source_row_ids' => $partDayCells->pluck('row_id')->unique()->values()->all(),
                ]);
            }
        }

        // Allocate StockAtCustomer per date+customer+part across sequences (reduce later sequences first).
        $requirements = $lines
            ->map(function ($r) use ($getStockAtCustomer) {
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
            ->sort(function ($a, $b) use ($sortBy, $sortDir) {
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

    public function generateSoBulk(Request $request)
    {
        $validated = $request->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'min:0'],
            'lines' => ['required', 'array'],
            'lines.*.date' => ['required', 'date'],
            'lines.*.customer_id' => ['required', 'exists:customers,id'],
            'lines.*.gci_part_id' => ['required', 'exists:gci_parts,id'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.row_ids' => ['nullable', 'array'],
            'lines.*.row_ids.*' => ['integer', 'exists:outgoing_daily_plan_rows,id'],
        ]);

        $selectedIdx = collect($validated['selected'])->map(fn($v) => (int) $v)->unique()->values();
        $lines = collect($validated['lines']);

        $selectedLines = $selectedIdx
            ->map(fn(int $i) => is_array($lines->get($i)) ? array_merge(['_idx' => $i], $lines->get($i)) : null)
            ->filter()
            ->values();

        if ($selectedLines->isEmpty()) {
            return back()->with('error', 'No lines selected.');
        }

        $dates = $selectedLines->pluck('date')->unique()->values();
        if ($dates->count() !== 1) {
            return back()->with('error', 'Please select requirements for a single date only.');
        }
        $planDate = (string) $dates->first();

        DB::transaction(function () use ($validated, $selectedLines, $planDate) {
            $byCustomer = $selectedLines->groupBy('customer_id');

            foreach ($byCustomer as $customerId => $customerLines) {
                $soNo = null;
                for ($attempt = 0; $attempt < 5; $attempt++) {
                    $candidate = 'SO-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
                    if (!SalesOrder::query()->where('so_no', $candidate)->exists()) {
                        $soNo = $candidate;
                        break;
                    }
                }
                $soNo ??= 'SO-' . now()->format('YmdHis') . '-' . (string) Str::uuid();

                $so = SalesOrder::create([
                    'so_no' => $soNo,
                    'customer_id' => (int) $customerId,
                    'so_date' => $planDate,
                    'status' => 'draft',
                    'notes' => 'Generated from Delivery Requirements',
                    'created_by' => auth()->id(),
                ]);

                $items = $customerLines
                    ->groupBy('gci_part_id')
                    ->map(fn($rows) => (float) $rows->sum('qty'));

                foreach ($items as $partId => $qty) {
                    if ($qty <= 0) {
                        continue;
                    }
                    SalesOrderItem::create([
                        'sales_order_id' => $so->id,
                        'gci_part_id' => (int) $partId,
                        'qty_ordered' => $qty,
                    ]);
                }
            }

            // Mark selected requirements as fulfilled without mutating Daily Planning:
            // Create fulfillment records (best-effort, idempotent on remaining qty).
            $rowIds = $selectedLines
                ->flatMap(fn($l) => $l['row_ids'] ?? [])
                ->map(fn($v) => (int) $v)
                ->filter(fn($v) => $v > 0)
                ->unique()
                ->values();

            if ($rowIds->isEmpty()) {
                return;
            }

            $cells = OutgoingDailyPlanCell::query()
                ->whereIn('row_id', $rowIds->all())
                ->whereDate('plan_date', $planDate)
                ->get(['row_id', 'qty', 'plan_date']);

            $alreadyFulfilled = DeliveryRequirementFulfillment::query()
                ->whereIn('row_id', $rowIds->all())
                ->whereDate('plan_date', $planDate)
                ->selectRaw('row_id, SUM(qty) as fulfilled_qty')
                ->groupBy('row_id')
                ->get()
                ->mapWithKeys(fn($f) => [$f->row_id => $f->fulfilled_qty]);

            $newFulfillments = [];
            foreach ($cells as $cell) {
                $fulfilled = $alreadyFulfilled->get($cell->row_id, 0);
                $remaining = max(0, $cell->qty - $fulfilled);
                if ($remaining > 0) {
                    $newFulfillments[] = [
                        'row_id' => $cell->row_id,
                        'plan_date' => $planDate,
                        'qty' => $remaining,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($newFulfillments)) {
                DeliveryRequirementFulfillment::insert($newFulfillments);
            }
        });

        return back()->with('success', 'Sales Order(s) generated successfully.');
    }

    public function deliveryPlan()
    {
        $deliveryPlans = DeliveryPlan::query()->latest()->get();
        return view('outgoing.delivery_plan', compact('deliveryPlans'));
    }

    public function deliveryPlanCreate()
    {
        $trucks = Truck::query()->where('status', 'active')->get();
        $drivers = Driver::query()->where('status', 'active')->get();
        $date = request('date', now()->toDateString());

        return view('outgoing.delivery_plan_create', compact('trucks', 'drivers', 'date'));
    }

    public function deliveryPlanStore(Request $request)
    {
        $validated = $request->validate([
            'plan_date' => ['required', 'date'],
            'truck_id' => ['required', 'exists:trucks,id'],
            'driver_id' => ['required', 'exists:drivers,id'],
            'cycle' => ['required', 'integer', 'min:1'],
            'items' => ['required', 'array'],
            'items.*.so_id' => ['required', 'exists:sales_orders,id'],
            'items.*.part_id' => ['required', 'exists:gci_parts,id'],
            'items.*.qty' => ['required', 'numeric', 'min:1'],
        ]);

        DB::transaction(function () use ($validated) {
            $plan = DeliveryPlan::create([
                'plan_date' => $validated['plan_date'],
                'truck_id' => $validated['truck_id'],
                'driver_id' => $validated['driver_id'],
                'cycle' => $validated['cycle'],
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);
            // Logic to link SO items to delivery plan ...
        });

        return redirect()->route('outgoing.delivery-plan')->with('success', 'Delivery Plan created.');
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

        // 1. Direct match in GciPart (FG)
        $fg = GciPart::where('classification', 'FG')
            ->where(function ($q) use ($partNo) {
                $q->where('part_no', $partNo)
                    ->orWhere('part_no', Str::replace('-', '', $partNo)); // loose match
            })
            ->first();
        if ($fg) {
            return (int) $fg->id;
        }

        // 2. Check Customer Part mapping
        // CustomerPart -> hasMany components -> gci_part_id
        $cp = CustomerPart::query()
            ->where('customer_part_no', $partNo)
            ->first();

        if ($cp) {
            // Get first component that is FG
            // Actually components link to GciPart. Check if any component is FG.
            foreach ($cp->components as $comp) {
                if ($comp->part && $comp->part->classification === 'FG') {
                    return (int) $comp->part->id;
                }
            }
        }

        return null;
    }
}
