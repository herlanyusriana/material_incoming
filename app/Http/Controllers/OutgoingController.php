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
use App\Models\DeliveryRequirementFulfillment;
use App\Models\GciPart;
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

class OutgoingController extends Controller
{
    public function dailyPlanning()
    {
        $dateFrom = $this->parseDate(request('date_from')) ?? now()->startOfDay();
        $dateTo = $this->parseDate(request('date_to')) ?? now()->addDays(4)->startOfDay();
        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $search = trim((string) request('search', ''));
        $perPage = (int) request('per_page', 50);
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
            $plan = OutgoingDailyPlan::query()
                ->whereDate('date_from', $dateFrom->toDateString())
                ->whereDate('date_to', $dateTo->toDateString())
                ->latest('id')
                ->first();
        }

        if ($plan) {
            $dateFrom = $plan->date_from->copy();
            $dateTo = $plan->date_to->copy();
        }

        $days = $this->daysBetween($dateFrom, $dateTo);

        $rows = collect();
        $totalsByDate = [];
        foreach ($days as $d) {
            $totalsByDate[$d->format('Y-m-d')] = 0;
        }

        if ($plan) {
            $totals = OutgoingDailyPlanCell::query()
                ->join('outgoing_daily_plan_rows as r', 'r.id', '=', 'outgoing_daily_plan_cells.row_id')
                ->where('r.plan_id', $plan->id)
                ->whereBetween('outgoing_daily_plan_cells.plan_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->selectRaw('outgoing_daily_plan_cells.plan_date as plan_date, SUM(COALESCE(outgoing_daily_plan_cells.qty, 0)) as total_qty')
                ->groupBy('outgoing_daily_plan_cells.plan_date')
                ->pluck('total_qty', 'plan_date')
                ->all();

            foreach ($totals as $k => $totalQty) {
                if (isset($totalsByDate[$k])) {
                    $totalsByDate[$k] = (int) $totalQty;
                }
            }

            $rows = $plan->rows()
                ->with([
                    'gciPart.standardPacking',
                    'cells' => function ($query) use ($dateFrom, $dateTo) {
                        $query
                            ->select(['id', 'row_id', 'plan_date', 'seq', 'qty'])
                            ->whereBetween('plan_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);
                    },
                ])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('production_line', 'like', '%' . $search . '%')
                            ->orWhere('part_no', 'like', '%' . $search . '%');
                    });
                })
                ->paginate($perPage)
                ->withQueryString();
        }

        return view('outgoing.daily_planning', [
            'plan' => $plan,
            'rows' => $rows,
            'days' => $days,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'totalsByDate' => $totalsByDate,
            'search' => $search,
            'perPage' => $perPage,
        ]);
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
        $filename = 'daily_planning_' . $plan->date_from->format('Ymd') . '_' . $plan->date_to->format('Ymd') . '.xlsx';
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
        return view('outgoing.product_mapping');
    }

    public function deliveryRequirements(Request $request)
    {
        $dateFrom = $this->parseDate($request->query('date_from')) ?? now()->startOfDay();
        $dateTo = $this->parseDate($request->query('date_to')) ?? now()->addDays(6)->startOfDay();
        if ($dateTo->lt($dateFrom)) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
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
            ->where('qty', '>', 0)
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
            ->filter(fn ($cell) => (float) ($cell->remaining_qty ?? 0) > 0)
            ->values();

        // Stock at Customers (consignment) map: period|customer_id|gci_part_id => record.
        // We'll subtract available stock from requirements, allocating stock to the latest sequence first
        // so earlier sequences remain prioritized.
        $periods = $cells
            ->pluck('plan_date')
            ->filter()
            ->map(fn ($d) => $d->format('Y-m'))
            ->unique()
            ->values();

        $customerIds = $cells
            ->map(fn ($c) => $c->row?->gciPart?->customer_id)
            ->filter(fn ($v) => $v !== null)
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        $partIds = $cells
            ->map(fn ($c) => $c->row?->gci_part_id)
            ->filter(fn ($v) => $v !== null)
            ->map(fn ($v) => (int) $v)
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

        $lines = $cells->groupBy(function ($cell) {
            // Group key: Date|Part|Seq
            // Keep seq in the key so aggregated requirements don't "inherit" the wrong sequence
            // when the same part appears under different seq in Daily Planning.
            $date = $cell->plan_date->format('Y-m-d');
            $partKey = $cell->row->gci_part_id
                ? ('gci:' . $cell->row->gci_part_id)
                : ('raw:' . (string) ($cell->row->part_no ?? ''));

            $seq = $cell->seq !== null && $cell->seq !== '' ? (int) $cell->seq : 9999;
            return "{$date}|{$partKey}|seq:{$seq}";
        })->map(function ($group) {
            $first = $group->first();
            
            $gciPart = $first->row->gciPart ?? null;

            $sequence = $first->seq !== null && $first->seq !== '' ? (int) $first->seq : 9999;
            
            return (object) [
                'date' => $first->plan_date,
                'customer' => $gciPart?->customer ?? null,
                'gci_part' => $gciPart,
                'customer_part_no' => $first->row->part_no,
                'unmapped' => $gciPart === null,
                'gross_qty' => $group->sum(fn ($c) => (float) ($c->remaining_qty ?? 0)),
                'sequence' => $sequence,
                'packing_std' => ($gciPart?->standardPacking?->packing_qty ?? 1) ?: 1,
                'uom' => $gciPart?->standardPacking?->uom ?? 'PCS',
                'source_row_ids' => $group->pluck('row_id')->unique()->values(),
            ];
        })->filter()->values();

        // Allocate StockAtCustomer per date+customer+part across sequences (reduce later sequences first).
        $requirements = $lines
            ->groupBy(function ($r) {
                $date = $r->date?->format('Y-m-d') ?? '';
                $custId = (int) ($r->customer?->id ?? 0);
                $partId = (int) ($r->gci_part?->id ?? 0);
                return "{$date}|cust:{$custId}|part:{$partId}";
            })
            ->flatMap(function ($group) use ($getStockAtCustomer) {
                /** @var \Illuminate\Support\Collection $group */
                $first = $group->first();
                $date = $first->date;
                $custId = (int) ($first->customer?->id ?? 0);
                $partId = (int) ($first->gci_part?->id ?? 0);

                $stockTotal = 0.0;
                if ($date && $custId > 0 && $partId > 0) {
                    $stockTotal = $getStockAtCustomer($date, $custId, $partId);
                }

                $remainingStock = $stockTotal;

                $sorted = $group->sortByDesc(fn ($r) => (int) ($r->sequence ?? 9999))->values();
                foreach ($sorted as $r) {
                    $gross = (float) ($r->gross_qty ?? 0);
                    $used = 0.0;
                    if ($remainingStock > 0 && $gross > 0) {
                        $used = min($gross, $remainingStock);
                        $remainingStock -= $used;
                    }

                    $r->stock_at_customer = $stockTotal;
                    $r->stock_used = $used;
                    $r->total_qty = max(0, $gross - $used);

                    $packQty = (float) ($r->packing_std ?? 1);
                    $packQty = $packQty > 0 ? $packQty : 1;
                    $r->packing_load = $packQty > 0 ? (int) ceil(((float) $r->total_qty) / $packQty) : 0;
                }

                return $sorted;
            })
            ->filter(fn ($r) => (float) ($r->total_qty ?? 0) > 0)
            ->sort(function ($a, $b) {
            // Sort by Date then Sequence
            if ($a->date->ne($b->date)) {
                return $a->date->gt($b->date) ? 1 : -1;
            }
            $seqCmp = ($a->sequence ?? 9999) <=> ($b->sequence ?? 9999);
            if ($seqCmp !== 0) {
                return $seqCmp;
            }

            return strcmp((string) ($a->gci_part?->part_no ?? ''), (string) ($b->gci_part?->part_no ?? ''));
        })
            ->values();

        return view('outgoing.delivery_requirements', compact('requirements', 'dateFrom', 'dateTo'));
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

        $selectedIdx = collect($validated['selected'])->map(fn ($v) => (int) $v)->unique()->values();
        $lines = collect($validated['lines']);

        $selectedLines = $selectedIdx
            ->map(fn (int $i) => is_array($lines->get($i)) ? array_merge(['_idx' => $i], $lines->get($i)) : null)
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
                    if (!\App\Models\SalesOrder::query()->where('so_no', $candidate)->exists()) {
                        $soNo = $candidate;
                        break;
                    }
                }
                $soNo ??= 'SO-' . now()->format('YmdHis') . '-' . (string) Str::uuid();

                $so = \App\Models\SalesOrder::create([
                    'so_no' => $soNo,
                    'customer_id' => (int) $customerId,
                    'so_date' => $planDate,
                    'status' => 'draft',
                    'notes' => 'Generated from Delivery Requirements',
                    'created_by' => auth()->id(),
                ]);

                $items = $customerLines
                    ->groupBy('gci_part_id')
                    ->map(fn ($rows) => (float) $rows->sum('qty'));

                foreach ($items as $partId => $qty) {
                    if ($qty <= 0) {
                        continue;
                    }
                    \App\Models\SalesOrderItem::create([
                        'sales_order_id' => $so->id,
                        'gci_part_id' => (int) $partId,
                        'qty_ordered' => $qty,
                    ]);
                }
            }

            // Mark selected requirements as fulfilled without mutating Daily Planning:
            // Create fulfillment records (best-effort, idempotent on remaining qty).
            $rowIds = $selectedLines
                ->flatMap(fn ($l) => $l['row_ids'] ?? [])
                ->map(fn ($v) => (int) $v)
                ->filter(fn ($v) => $v > 0)
                ->unique()
                ->values();

            if ($rowIds->isEmpty()) {
                return;
            }

            $cells = \App\Models\OutgoingDailyPlanCell::query()
                ->whereIn('row_id', $rowIds->all())
                ->whereDate('plan_date', $planDate)
                ->get(['row_id', 'qty', 'plan_date']);

            $alreadyFulfilled = DeliveryRequirementFulfillment::query()
                ->whereIn('row_id', $rowIds->all())
                ->whereDate('plan_date', $planDate)
                ->selectRaw('row_id, SUM(qty) as fulfilled_qty')
                ->groupBy('row_id')
                ->pluck('fulfilled_qty', 'row_id')
                ->map(fn ($v) => (float) $v)
                ->all();

            foreach ($cells as $cell) {
                $planned = (float) $cell->qty;
                if ($planned <= 0) {
                    continue;
                }

                $rowId = (int) $cell->row_id;
                $fulfilled = (float) ($alreadyFulfilled[$rowId] ?? 0);
                $remaining = max(0, $planned - $fulfilled);
                if ($remaining <= 0) {
                    continue;
                }

                DeliveryRequirementFulfillment::create([
                    'plan_date' => $planDate,
                    'row_id' => $rowId,
                    'qty' => $remaining,
                    'delivery_plan_id' => null,
                    'created_by' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('outgoing.delivery-plan', ['date' => $planDate])
            ->with('success', 'SO generated successfully. Assign truck/driver in Delivery Plan.');
    }

    public function generateSo(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array',
            'items.*.gci_part_id' => 'required|exists:gci_parts,id',
            'items.*.qty' => 'required|numeric|min:0.0001',
        ]);

        DB::transaction(function () use ($validated) {
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
                'customer_id' => $validated['customer_id'],
                'so_date' => $validated['date'],
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'gci_part_id' => $item['gci_part_id'],
                    'qty_ordered' => $item['qty'],
                ]);
            }
        });

        return redirect()->route('outgoing.delivery-plan', ['date' => $validated['date']])->with('success', 'SO generated successfully.');
    }

    public function stockAtCustomers()
    {
        $period = request('period') ?: now()->format('Y-m');
        $daysInMonth = CarbonImmutable::parse($period . '-01')->daysInMonth;
        $days = range(1, $daysInMonth);

        $records = StockAtCustomer::query()
            ->with(['customer', 'part'])
            ->where('period', $period)
            ->orderBy('customer_id')
            ->orderBy('part_no')
            ->paginate(50)
            ->withQueryString();

        return view('outgoing.stock_at_customers', compact('period', 'records', 'days'));
    }

    public function stockAtCustomersTemplate(Request $request)
    {
        $period = $request->query('period') ?: now()->format('Y-m');
        $daysInMonth = CarbonImmutable::parse($period . '-01')->daysInMonth;
        $filename = 'stock_at_customers_template_' . $period . '.xlsx';

        return Excel::download(new class($period, $daysInMonth) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function __construct(private readonly string $period, private readonly int $daysInMonth)
            {
            }

            public function array(): array
            {
                $row = ['CUSTOMER_NAME', 'PART-001', 'PART NAME', 'MODEL', 'active'];
                for ($d = 1; $d <= $this->daysInMonth; $d++) {
                    $row[] = 0;
                }
                return [$row];
            }

            public function headings(): array
            {
                $base = ['customer', 'part_no', 'part_name', 'model', 'status'];
                for ($d = 1; $d <= $this->daysInMonth; $d++) {
                    $base[] = (string) $d;
                }
                return $base;
            }
        }, $filename);
    }

    public function stockAtCustomersExport(Request $request)
    {
        $period = $request->query('period') ?: now()->format('Y-m');
        $filename = 'stock_at_customers_' . $period . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new StockAtCustomersExport($period), $filename);
    }

    public function stockAtCustomersImport(Request $request)
    {
        $validated = $request->validate([
            'period' => ['required', 'string', 'regex:/^\\d{4}-\\d{2}$/'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new StockAtCustomersImport($validated['period']);
        Excel::import($import, $validated['file']);

        if (!empty($import->failures)) {
            $preview = array_slice($import->failures, 0, 10);
            $msg = implode(' ; ', $preview);
            if (count($import->failures) > 10) {
                $msg .= ' ; ... and ' . (count($import->failures) - 10) . ' more errors';
            }
            return back()->with('error', "Import selesai tapi ada error: {$msg}");
        }

        $msg = "Stock at Customers imported. {$import->rowCount} rows processed.";
        if ($import->skippedRows > 0) {
            $msg .= " {$import->skippedRows} rows skipped.";
        }

        return redirect()->route('outgoing.stock-at-customers', ['period' => $validated['period']])->with('success', $msg);
    }

    public function deliveryPlan()
    {
        $date = $this->parseDate(request('date')) ?? now()->startOfDay();
        $sequences = range(1, 13);

        $plans = DeliveryPlan::query()
            ->with(['truck', 'driver'])
            ->whereDate('plan_date', $date)
            ->orderBy('sequence')
            ->get();

        $trucks = Truck::query()
            ->select(['id', 'plate_no', 'type', 'capacity', 'status'])
            ->orderBy('plate_no')
            ->get();

        $drivers = Driver::query()
            ->select(['id', 'name', 'phone', 'license_type', 'status'])
            ->orderBy('name')
            ->get();

        $cells = OutgoingDailyPlanCell::query()
            ->with(['row.gciPart.customer', 'row.gciPart.standardPacking'])
            ->whereDate('plan_date', $date->toDateString())
            ->where('qty', '>', 0)
            ->get();

        $fulfilledMap = DeliveryRequirementFulfillment::query()
            ->selectRaw('plan_date, row_id, SUM(qty) as fulfilled_qty')
            ->whereDate('plan_date', $date->toDateString())
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
            ->filter(fn ($cell) => (float) ($cell->remaining_qty ?? 0) > 0)
            ->values();

        $period = $date->format('Y-m');
        $day = (int) $date->format('j');

        $customerIds = $cells
            ->map(fn ($c) => $c->row?->gciPart?->customer_id)
            ->filter(fn ($v) => $v !== null)
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        $partIds = $cells
            ->map(fn ($c) => $c->row?->gci_part_id)
            ->filter(fn ($v) => $v !== null)
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        $stockMap = [];
        if ($customerIds->isNotEmpty() && $partIds->isNotEmpty()) {
            $stockMap = StockAtCustomer::query()
                ->where('period', $period)
                ->whereIn('customer_id', $customerIds->all())
                ->whereIn('gci_part_id', $partIds->all())
                ->get()
                ->mapWithKeys(function ($rec) use ($period) {
                    $k = $period . '|' . (int) $rec->customer_id . '|' . (int) $rec->gci_part_id;
                    return [$k => $rec];
                })
                ->all();
        }

        $rowsByPart = $cells
            ->filter(fn ($c) => $c->row?->gciPart)
            ->groupBy(fn ($c) => (int) $c->row->gci_part_id)
            ->map(function ($group) use ($sequences, $stockMap, $period, $day) {
                $first = $group->first();
                $gciPart = $first->row->gciPart;

                $deliveryClass = (string) ($gciPart?->standardPacking?->delivery_class ?? 'unknown');
                $customerId = (int) ($gciPart?->customer_id ?? 0);
                $partId = (int) ($gciPart?->id ?? 0);

                $perSeqGross = [];
                foreach ($sequences as $seq) {
                    $perSeqGross[$seq] = 0.0;
                }
                $extraGross = 0.0;

                foreach ($group as $cell) {
                    $seq = $cell->seq !== null && $cell->seq !== '' ? (int) $cell->seq : 9999;
                    $qty = (float) ($cell->remaining_qty ?? 0);
                    if (in_array($seq, $sequences, true)) {
                        $perSeqGross[$seq] += $qty;
                    } else {
                        $extraGross += $qty;
                    }
                }

                $planTotal = array_sum($perSeqGross) + $extraGross;

                $stock = 0.0;
                if ($customerId > 0 && $partId > 0 && $day >= 1 && $day <= 31) {
                    $k = $period . '|' . $customerId . '|' . $partId;
                    $rec = $stockMap[$k] ?? null;
                    if ($rec) {
                        $stock = (float) ($rec->{'day_' . $day} ?? 0);
                    }
                }

                // Allocate stock to latest sequence first, then extra.
                $remainingStock = $stock;
                $perSeqNet = $perSeqGross;
                foreach (array_reverse($sequences) as $seq) {
                    if ($remainingStock <= 0) {
                        break;
                    }
                    $take = min($perSeqNet[$seq], $remainingStock);
                    $perSeqNet[$seq] -= $take;
                    $remainingStock -= $take;
                }
                if ($remainingStock > 0 && $extraGross > 0) {
                    $take = min($extraGross, $remainingStock);
                    $extraGross -= $take;
                    $remainingStock -= $take;
                }

                $remain = $extraGross;
                $balance = max(0, (array_sum($perSeqNet) + $extraGross));

                $productionLines = $group
                    ->map(fn ($c) => (string) ($c->row?->production_line ?? ''))
                    ->map(fn ($v) => trim($v))
                    ->filter(fn ($v) => $v !== '')
                    ->unique()
                    ->values()
                    ->implode(', ');

                $jigNr1 = $balance > 0 ? (int) ceil($balance / 10) : 0; // NR1: 10 pcs per jig
                $jigNr2 = $balance > 0 ? (int) ceil($balance / 9) : 0;  // NR2: 9 pcs per jig

                return (object) [
                    'delivery_class' => $deliveryClass,
                    'part_name' => $gciPart?->part_name ?? '-',
                    'part_no' => $gciPart?->part_no ?? '-',
                    'production_lines' => $productionLines !== '' ? $productionLines : '-',
                    'plan_total' => $planTotal,
                    'stock_at_customer' => $stock,
                    'balance' => $balance,
                    'due_date' => $first->plan_date,
                    'per_seq' => $perSeqNet,
                    'remain' => $remain,
                    'jig_nr1' => $jigNr1,
                    'jig_nr2' => $jigNr2,
                ];
            })
            ->values()
            ->sortBy(fn ($r) => $r->delivery_class)
            ->values();

        $groups = $rowsByPart->groupBy('delivery_class')->all();

        return view('outgoing.delivery_plan', [
            'selectedDate' => $date->toDateString(),
            'sequences' => $sequences,
            'groups' => $groups,
            'plans' => $plans,
            'trucks' => $trucks,
            'drivers' => $drivers,
        ]);
    }

    public function assignDeliveryPlanResources(Request $request, DeliveryPlan $plan)
    {
        $validated = $request->validate([
            'truck_id' => ['nullable', 'exists:trucks,id'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
        ]);

        $plan->update([
            'truck_id' => $validated['truck_id'] ?? null,
            'driver_id' => $validated['driver_id'] ?? null,
        ]);

        return back()->with('success', 'Trip resources updated.');
    }

    public function assignSoToPlan(Request $request)
    {
        $validated = $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'delivery_plan_id' => 'required|exists:delivery_plans,id',
        ]);

        $so = SalesOrder::findOrFail($validated['sales_order_id']);
        $plan = \App\Models\DeliveryPlan::findOrFail($validated['delivery_plan_id']);

        DB::transaction(function () use ($so, $plan) {
            // Find or create a stop for this customer in this plan
            $stop = \App\Models\DeliveryStop::firstOrCreate(
                [
                    'plan_id' => $plan->id,
                    'customer_id' => $so->customer_id,
                ],
                [
                    'sequence' => ($plan->stops()->max('sequence') ?? 0) + 1,
                    'status' => 'pending',
                ]
            );

            $so->update([
                'delivery_plan_id' => $plan->id,
                'delivery_stop_id' => $stop->id,
                'status' => $so->status === 'draft' ? 'assigned' : $so->status,
            ]);
        });

        return response()->json(['success' => true]);
    }

    public function storeDeliveryPlan(Request $request)
    {
        $validated = $request->validate([
            'plan_date' => 'required|date',
            'truck_id' => 'nullable|exists:trucks,id',
            'driver_id' => 'nullable|exists:drivers,id',
        ]);

        // Auto-generate sequence: Get max sequence for the day + 1
        $maxSeq = \App\Models\DeliveryPlan::whereDate('plan_date', $validated['plan_date'])->max('sequence') ?? 0;

        $plan = \App\Models\DeliveryPlan::create([
            'plan_date' => $validated['plan_date'],
            'sequence' => $maxSeq + 1,
            'truck_id' => $validated['truck_id'] ?? null,
            'driver_id' => $validated['driver_id'] ?? null,
            'status' => 'scheduled',
        ]);

        return redirect()->route('outgoing.delivery-plan', ['date' => $validated['plan_date']])
            ->with('success', 'Delivery Plan created successfully.');
    }

    private function parseDate(?string $value): ?Carbon
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }
        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return list<Carbon> */
    private function daysBetween(Carbon $from, Carbon $to): array
    {
        $days = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $days[] = $cursor->copy();
            $cursor->addDay();
            if (count($days) > 31) {
                break;
            }
        }
        return $days;
    }
    public function updateCell(Request $request)
    {
        $validated = $request->validate([
            'row_id' => 'required|exists:outgoing_daily_plan_rows,id',
            'date' => 'required|date',
            'field' => 'required|in:seq,qty',
            'value' => 'nullable',
        ]);

        $rowId = $validated['row_id'];
        $date = $validated['date'];
        $field = $validated['field'];
        $value = $validated['value'];

        $cell = OutgoingDailyPlanCell::updateOrCreate(
            ['row_id' => $rowId, 'plan_date' => $date],
            [$field => $value]
        );

        return response()->json(['success' => true, 'cell' => $cell]);
    }

    public function createPlan(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $plan = OutgoingDailyPlan::create([
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('outgoing.daily-planning', ['plan_id' => $plan->id])
            ->with('success', 'New plan created. Please add rows.');
    }
    
    public function storeRow(Request $request, OutgoingDailyPlan $plan) 
    {
        $validated = $request->validate([
            'part_no' => 'required|string',
            'production_line' => 'required|string',
        ]);
        
        $partNo = $this->normalizePartNo($validated['part_no']);
        $gciPartId = $partNo !== '' ? $this->resolveFgPartIdFromPartNo($partNo) : null;
        
        $row = $plan->rows()->create([
            'row_no' => (int) ($plan->rows()->max('row_no') ?? 0) + 1,
            'production_line' => $validated['production_line'],
            'part_no' => $partNo !== '' ? $partNo : $validated['part_no'],
            'gci_part_id' => $gciPartId,
        ]);
        
        return back()->with('success', 'Row added.');
    }

    private function normalizePartNo(string $value): string
    {
        $str = str_replace("\u{00A0}", ' ', (string) ($value ?? ''));
        $str = preg_replace('/\s+/', ' ', $str) ?? $str;
        $str = strtoupper(trim($str));
        if ($str === '') {
            return '';
        }

        // Common import patterns append notes like "(REV A)" or extra tokens after spaces.
        // Canonicalize to reduce accidental duplicates during auto-mapping.
        $str = preg_replace('/\s*[\\(\\[].*$/', '', $str) ?? $str;
        $str = trim($str);
        if ($str === '') {
            return '';
        }

        if (str_contains($str, ' ')) {
            $str = (string) strtok($str, ' ');
        }

        return trim($str);
    }

    private function resolveFgPartIdFromPartNo(string $partNo): ?int
    {
        $partNo = $this->normalizePartNo($partNo);
        if ($partNo === '') {
            return null;
        }

        $gciPart = GciPart::query()
            ->where('part_no', $partNo)
            ->where('classification', 'FG')
            ->first();
        if ($gciPart) {
            return (int) $gciPart->id;
        }

        $customerPart = CustomerPart::query()
            ->where('customer_part_no', $partNo)
            ->where('status', 'active')
            ->with(['components.part' => function ($q) {
                $q->where('classification', 'FG');
            }])
            ->first();

        if ($customerPart) {
            $fgComponents = $customerPart->components
                ->filter(fn ($c) => $c->part && $c->part->classification === 'FG' && $c->gci_part_id)
                ->sortByDesc(fn ($c) => (float) ($c->qty_per_unit ?? 0))
                ->values();

            $first = $fgComponents->first();
            if ($first && $first->gci_part_id) {
                return (int) $first->gci_part_id;
            }
        }

        $created = GciPart::create([
            'part_no' => $partNo,
            'part_name' => 'AUTO-CREATED (DAILY PLAN)',
            'classification' => 'FG',
            'status' => 'active',
        ]);

        return (int) $created->id;
    }
}
