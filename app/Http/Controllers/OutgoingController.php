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

        return view('outgoing.daily_planning', compact(
            'plan',
            'rows',
            'days',
            'dateFrom',
            'dateTo',
            'planId',
            'search',
            'totalsByDate'
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
            ->where('classification', 'RM')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

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
        $dateTo = $this->parseDate($request->query('date_to')) ?? now()->addDays(6)->startOfDay();
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
                $grossQty = $partDayCells->sum(fn($c) => (float) ($c->remaining_qty ?? 0));

                if ($grossQty <= 0) {
                    continue; // Skip part with no demand on this day (No value hide)
                }

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
                    'unmapped' => false,
                    'gross_qty' => $grossQty,
                    'sequence' => $primarySequence,
                    'sequences_consolidated' => $sequences,
                    'packing_std' => $packingQty,
                    'uom' => $gciPart->standardPacking?->uom ?? 'PCS',
                    'source_row_ids' => $partDayCells->pluck('row_id')->unique()->values()->all(),
                ]);
            }

            // Handle unmapped parts that have demand on this day
            $fgPartIdsInDay = $fgParts->pluck('id')->all();
            $unmappedDayCells = $dayCells->filter(function ($c) use ($fgPartIdsInDay) {
                return !in_array($c->row?->gci_part_id, $fgPartIdsInDay);
            });
            $unmappedDayGroups = $unmappedDayCells->groupBy(fn($c) => "{$c->row?->part_no}");

            foreach ($unmappedDayGroups as $pNo => $group) {
                $grossQty = $group->sum(fn($c) => (float) ($c->remaining_qty ?? 0));
                if ($grossQty <= 0)
                    continue;

                $lines->push((object) [
                    'date' => $day->copy(),
                    'customer' => null,
                    'gci_part' => null,
                    'customer_part_no' => $pNo,
                    'unmapped' => true,
                    'gross_qty' => $grossQty,
                    'sequence' => 9999,
                    'sequences_consolidated' => [],
                    'packing_std' => 1,
                    'uom' => 'PCS',
                    'source_row_ids' => $group->pluck('row_id')->unique()->values()->all(),
                ]);
            }
        }

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

                $sorted = $group->sortByDesc(fn($r) => (int) ($r->sequence ?? 9999))->values();
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

                    // Recalculate delivery pack qty after stock deduction
                    $packQty = (float) ($r->packing_std ?? 1);
                    $packQty = $packQty > 0 ? $packQty : 1;
                    $r->packing_load = (int) ceil(((float) $r->total_qty) / $packQty);
                    $r->delivery_pack_qty = $r->packing_load * $packQty;
                }

                return $sorted;
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
                ->pluck('fulfilled_qty', 'row_id')
                ->map(fn($v) => (float) $v)
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

        return Excel::download(
            new class ($period, $daysInMonth) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
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
            },
            $filename
        );
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
        $uphNr1 = (float) request('uph_nr1', 0);
        $uphNr2 = (float) request('uph_nr2', 0);
        $uphNr1 = $uphNr1 > 0 ? $uphNr1 : 60.0;
        $uphNr2 = $uphNr2 > 0 ? $uphNr2 : 60.0;

        // Best-effort: backfill missing gci_part_id for rows that have demand on this date,
        // using customer part mapping / FG mapping. This makes Delivery Plan show more rows automatically.
        $autoMappedRowIds = [];
        $rowIdsNeedingFix = OutgoingDailyPlanCell::query()
            ->join('outgoing_daily_plan_rows as r', 'r.id', '=', 'outgoing_daily_plan_cells.row_id')
            ->whereDate('outgoing_daily_plan_cells.plan_date', $date->toDateString())
            ->where('outgoing_daily_plan_cells.qty', '>', 0)
            ->whereNull('r.gci_part_id')
            ->distinct()
            ->pluck('r.id');

        if ($rowIdsNeedingFix->isNotEmpty()) {
            OutgoingDailyPlanRow::query()
                ->whereIn('id', $rowIdsNeedingFix)
                ->select(['id', 'part_no'])
                ->chunk(200, function ($rows) use (&$autoMappedRowIds) {
                    foreach ($rows as $row) {
                        $partNo = $this->normalizePartNo((string) ($row->part_no ?? ''));
                        if ($partNo === '') {
                            continue;
                        }
                        $resolvedId = $this->resolveFgPartIdFromPartNo($partNo);
                        if ($resolvedId) {
                            OutgoingDailyPlanRow::query()->whereKey($row->id)->update(['gci_part_id' => $resolvedId]);
                            $autoMappedRowIds[] = (int) $row->id;
                        }
                    }
                });
        }

        $plans = DeliveryPlan::query()
            ->with(['truck', 'driver', 'salesOrders.customer', 'salesOrders.items.part'])
            ->whereDate('plan_date', $date)
            ->orderBy('sequence')
            ->get();

        $unassignedSalesOrders = SalesOrder::query()
            ->with(['customer', 'items.part'])
            ->whereDate('so_date', $date)
            ->whereNull('delivery_plan_id')
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
            ->filter(fn($cell) => (float) ($cell->remaining_qty ?? 0) > 0)
            ->values();

        $autoMappedRowIdSet = array_fill_keys(array_map('intval', $autoMappedRowIds), true);

        $period = $date->format('Y-m');
        $day = (int) $date->format('j');

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
            ->filter(fn($c) => $c->row?->gciPart)
            ->groupBy(fn($c) => (int) $c->row->gci_part_id)
            ->map(function ($group) use ($stockMap, $period, $day, $uphNr1, $uphNr2) {
                $first = $group->first();
                $gciPart = $first->row->gciPart;

                $deliveryClass = (string) ($gciPart?->standardPacking?->delivery_class ?? 'unknown');
                $customerId = (int) ($gciPart?->customer_id ?? 0);
                $partId = (int) ($gciPart?->id ?? 0);
                $stdPackQty = $gciPart?->standardPacking?->packing_qty;
                $stdPackUom = $gciPart?->standardPacking?->uom;
                $trolleyType = $gciPart?->standardPacking?->trolley_type;
                $partModel = $gciPart?->model;

                $planTotal = (float) $group->sum(fn($c) => (float) ($c->remaining_qty ?? 0));

                $stock = 0.0;
                if ($customerId > 0 && $partId > 0 && $day >= 1 && $day <= 31) {
                    $k = $period . '|' . $customerId . '|' . $partId;
                    $rec = $stockMap[$k] ?? null;
                    if ($rec) {
                        $stock = (float) ($rec->{'day_' . $day} ?? 0);
                    }
                }

                $balance = max(0, $planTotal - $stock);

                $productionLines = $group
                    ->map(fn($c) => (string) ($c->row?->production_line ?? ''))
                    ->map(fn($v) => trim($v))
                    ->filter(fn($v) => $v !== '')
                    ->unique()
                    ->values()
                    ->implode(', ');

                $linesUpper = strtoupper($productionLines);
                $hasNr1 = str_contains($linesUpper, 'NR1');
                $hasNr2 = str_contains($linesUpper, 'NR2');
                $lineType = $hasNr1 && !$hasNr2 ? 'NR1' : ($hasNr2 && !$hasNr1 ? 'NR2' : ($hasNr1 && $hasNr2 ? 'MIX' : 'UNKNOWN'));

                $jigNr1 = $balance > 0 ? (int) ceil($balance / 10) : 0; // NR1: 10 pcs per jig
                $jigNr2 = $balance > 0 ? (int) ceil($balance / 9) : 0;  // NR2: 9 pcs per jig
    
                $uph = $lineType === 'NR2' ? $uphNr2 : $uphNr1;
                $hours = $uph > 0 ? ($balance / $uph) : 0.0;
                $jigs = $lineType === 'NR2' ? $jigNr2 : $jigNr1;
                if ($lineType === 'MIX' || $lineType === 'UNKNOWN') {
                    // Keep both visible in UI; default to NR1 for computed totals.
                    $uph = $uphNr1;
                    $hours = $uph > 0 ? ($balance / $uph) : 0.0;
                    $jigs = $jigNr1;
                }

                $sourceRowIds = $group
                    ->pluck('row_id')
                    ->map(fn($v) => (int) $v)
                    ->filter(fn($v) => $v > 0)
                    ->unique()
                    ->values()
                    ->all();

                $autoMapped = false;
                foreach ($sourceRowIds as $rid) {
                    if (isset($autoMappedRowIdSet[$rid])) {
                        $autoMapped = true;
                        break;
                    }
                }

                return (object) [
                    'gci_part_id' => $partId,
                    'customer_id' => $customerId,
                    'delivery_class' => $deliveryClass,
                    'part_name' => $gciPart?->part_name ?? '-',
                    'part_no' => $gciPart?->part_no ?? '-',
                    'production_lines' => $productionLines !== '' ? $productionLines : '-',
                    'line_type' => $lineType,
                    'std_pack_qty' => $stdPackQty,
                    'std_pack_uom' => $stdPackUom,
                    'trolley_type' => $trolleyType,
                    'part_model' => $partModel,
                    'plan_total' => $planTotal,
                    'stock_at_customer' => $stock,
                    'balance' => $balance,
                    'due_date' => $first->plan_date,
                    'jig_nr1' => $jigNr1,
                    'jig_nr2' => $jigNr2,
                    'uph_nr1' => $uphNr1,
                    'uph_nr2' => $uphNr2,
                    'uph' => $uph,
                    'hours' => $hours,
                    'jigs' => $jigs,
                    'auto_mapped' => $autoMapped,
                    'source_row_ids' => $sourceRowIds,
                ];
            })
            ->values()
            ->sortBy(fn($r) => $r->delivery_class)
            ->values();

        $unmappedRows = $cells
            ->filter(fn($c) => !$c->row?->gciPart)
            ->groupBy(fn($c) => (int) $c->row_id)
            ->map(function ($group) {
                $first = $group->first();
                $row = $first?->row;

                return (object) [
                    'row_id' => (int) ($row?->id ?? 0),
                    'production_line' => (string) ($row?->production_line ?? '-'),
                    'part_no' => (string) ($row?->part_no ?? '-'),
                    'total_qty' => (float) $group->sum(fn($c) => (float) ($c->remaining_qty ?? 0)),
                ];
            })
            ->values();

        $assignmentMap = [];
        $assignedPartIds = $rowsByPart
            ->map(fn($r) => (int) ($r->gci_part_id ?? 0))
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (!empty($assignedPartIds)) {
            $assignmentMap = DeliveryPlanRequirementAssignment::query()
                ->with(['plan.truck', 'plan.driver'])
                ->whereDate('plan_date', $date->toDateString())
                ->whereIn('gci_part_id', $assignedPartIds)
                ->get()
                ->keyBy(fn($a) => (int) $a->gci_part_id)
                ->all();
        }

        foreach ($rowsByPart as $r) {
            $r->assignment = $assignmentMap[(int) ($r->gci_part_id ?? 0)] ?? null;

            $assignment = $r->assignment;
            $hasOverrides = false;
            if ($assignment) {
                $hasOverrides = ($assignment->line_type_override !== null)
                    || ($assignment->jig_capacity_nr1_override !== null)
                    || ($assignment->jig_capacity_nr2_override !== null)
                    || ($assignment->uph_nr1_override !== null)
                    || ($assignment->uph_nr2_override !== null)
                    || ($assignment->notes !== null && trim((string) $assignment->notes) !== '');
            }
            $r->has_overrides = $hasOverrides;

            $lineType = (string) ($assignment?->line_type_override ?? $r->line_type ?? 'UNKNOWN');
            $capNr1 = (int) ($assignment?->jig_capacity_nr1_override ?? 10);
            $capNr2 = (int) ($assignment?->jig_capacity_nr2_override ?? 9);
            $rowUphNr1 = (float) ($assignment?->uph_nr1_override ?? $r->uph_nr1 ?? $uphNr1);
            $rowUphNr2 = (float) ($assignment?->uph_nr2_override ?? $r->uph_nr2 ?? $uphNr2);

            $balance = (float) ($r->balance ?? 0);
            $jigNr1 = ($balance > 0 && $capNr1 > 0) ? (int) ceil($balance / $capNr1) : 0;
            $jigNr2 = ($balance > 0 && $capNr2 > 0) ? (int) ceil($balance / $capNr2) : 0;

            $uph = $lineType === 'NR2' ? $rowUphNr2 : $rowUphNr1;
            $jigs = $lineType === 'NR2' ? $jigNr2 : $jigNr1;
            if ($lineType === 'MIX' || $lineType === 'UNKNOWN') {
                $uph = $rowUphNr1;
                $jigs = $jigNr1;
            }
            $hours = $uph > 0 ? ($balance / $uph) : 0.0;

            $r->line_type = $lineType;
            $r->jig_capacity_nr1 = $capNr1;
            $r->jig_capacity_nr2 = $capNr2;
            $r->uph_nr1 = $rowUphNr1;
            $r->uph_nr2 = $rowUphNr2;
            $r->jig_nr1 = $jigNr1;
            $r->jig_nr2 = $jigNr2;
            $r->uph = $uph;
            $r->hours = $hours;
            $r->jigs = $jigs;
        }

        $totalsByLine = $rowsByPart
            ->groupBy(fn($r) => (string) ($r->line_type ?? 'UNKNOWN'))
            ->map(function ($group) {
                return (object) [
                    'line_type' => (string) ($group->first()?->line_type ?? 'UNKNOWN'),
                    'balance' => (float) $group->sum(fn($r) => (float) ($r->balance ?? 0)),
                    'uph' => (float) ($group->first()?->uph ?? 0),
                    'hours' => (float) $group->sum(fn($r) => (float) ($r->hours ?? 0)),
                    'jig_nr1' => (int) $group->sum(fn($r) => (int) ($r->jig_nr1 ?? 0)),
                    'jig_nr2' => (int) $group->sum(fn($r) => (int) ($r->jig_nr2 ?? 0)),
                    'jigs' => (int) $group->sum(fn($r) => (int) ($r->jigs ?? 0)),
                ];
            })
            ->values();

        $groups = $rowsByPart->groupBy('delivery_class')->all();

        return view('outgoing.delivery_plan', [
            'selectedDate' => $date->toDateString(),
            'groups' => $groups,
            'plans' => $plans,
            'unassignedSalesOrders' => $unassignedSalesOrders,
            'trucks' => $trucks,
            'drivers' => $drivers,
            'autoMappedRowCount' => count($autoMappedRowIds),
            'unmappedRows' => $unmappedRows,
            'uphNr1' => $uphNr1,
            'uphNr2' => $uphNr2,
            'totalsByLine' => $totalsByLine,
        ]);
    }

    public function assignDeliveryPlanItems(Request $request)
    {
        $validated = $request->validate([
            'plan_date' => ['required', 'date'],
            'gci_part_ids' => ['required', 'array', 'min:1'],
            'gci_part_ids.*' => ['integer', 'exists:gci_parts,id'],
            'delivery_plan_id' => ['nullable', 'integer', 'exists:delivery_plans,id'],
            'truck_id' => ['nullable', 'exists:trucks,id'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
        ]);

        $date = Carbon::parse($validated['plan_date'])->startOfDay();
        $gciPartIds = array_values(array_unique(array_map('intval', $validated['gci_part_ids'])));

        DB::transaction(function () use ($date, $validated, $gciPartIds) {
            $plan = null;
            if (!empty($validated['delivery_plan_id'])) {
                $plan = DeliveryPlan::query()->whereKey((int) $validated['delivery_plan_id'])->first();
                if ($plan) {
                    /** @var \Carbon\Carbon $planDate */
                    $planDate = $plan->plan_date;
                    if ($planDate->toDateString() !== $date->toDateString()) {
                        abort(422, 'Trip date mismatch.');
                    }
                }
            }

            if (!$plan) {
                $maxSeq = DeliveryPlan::query()->whereDate('plan_date', $date->toDateString())->max('sequence') ?? 0;
                $plan = DeliveryPlan::create([
                    'plan_date' => $date->toDateString(),
                    'sequence' => $maxSeq + 1,
                    'truck_id' => $validated['truck_id'] ?? null,
                    'driver_id' => $validated['driver_id'] ?? null,
                    'status' => 'scheduled',
                ]);
            } else {
                $plan->update([
                    'truck_id' => $validated['truck_id'] ?? $plan->truck_id,
                    'driver_id' => $validated['driver_id'] ?? $plan->driver_id,
                ]);
            }

            foreach ($gciPartIds as $partId) {
                DeliveryPlanRequirementAssignment::updateOrCreate(
                    [
                        'plan_date' => $date->toDateString(),
                        'gci_part_id' => $partId,
                    ],
                    [
                        'delivery_plan_id' => $plan->id,
                        'status' => 'assigned',
                    ]
                );
            }
        });

        return back()->with('success', 'Items assigned to trip.');
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

    public function updateDeliveryPlanOverrides(Request $request)
    {
        $validated = $request->validate([
            'plan_date' => ['required', 'date'],
            'gci_part_id' => ['required', 'integer', 'exists:gci_parts,id'],
            'line_type_override' => ['nullable', 'string', 'in:NR1,NR2,MIX,UNKNOWN'],
            'jig_capacity_nr1_override' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'jig_capacity_nr2_override' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'uph_nr1_override' => ['nullable', 'numeric', 'min:0.01', 'max:100000'],
            'uph_nr2_override' => ['nullable', 'numeric', 'min:0.01', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $date = Carbon::parse($validated['plan_date'])->startOfDay()->toDateString();
        $partId = (int) $validated['gci_part_id'];

        $toNullIfBlank = fn($v) => ($v === '' || $v === null) ? null : $v;

        DB::transaction(function () use ($date, $partId, $validated, $toNullIfBlank) {
            $assignment = DeliveryPlanRequirementAssignment::query()->firstOrNew([
                'plan_date' => $date,
                'gci_part_id' => $partId,
            ]);

            if (!$assignment->exists) {
                $assignment->status = 'pending';
            }

            $assignment->line_type_override = $toNullIfBlank($validated['line_type_override'] ?? null);
            $assignment->jig_capacity_nr1_override = $toNullIfBlank($validated['jig_capacity_nr1_override'] ?? null);
            $assignment->jig_capacity_nr2_override = $toNullIfBlank($validated['jig_capacity_nr2_override'] ?? null);
            $assignment->uph_nr1_override = $toNullIfBlank($validated['uph_nr1_override'] ?? null);
            $assignment->uph_nr2_override = $toNullIfBlank($validated['uph_nr2_override'] ?? null);
            $assignment->notes = $toNullIfBlank($validated['notes'] ?? null);

            $assignment->save();
        });

        return back()->with('success', 'Overrides updated.');
    }

    public function assignSoToPlan(Request $request)
    {
        $validated = $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'delivery_plan_id' => 'nullable', // allow unassign
        ]);

        $so = SalesOrder::findOrFail($validated['sales_order_id']);

        DB::transaction(function () use ($so, $validated) {
            if (empty($validated['delivery_plan_id'])) {
                $so->update([
                    'delivery_plan_id' => null,
                    'delivery_stop_id' => null,
                    'status' => $so->status === 'assigned' ? 'draft' : $so->status,
                ]);
                return;
            }

            $plan = DeliveryPlan::findOrFail($validated['delivery_plan_id']);

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
        $maxSeq = DeliveryPlan::whereDate('plan_date', $validated['plan_date'])->max('sequence') ?? 0;

        $plan = DeliveryPlan::create([
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
            'row_id' => 'required|string', // Changed to string to support vpart_ prefix
            'date' => 'required|date',
            'field' => 'required|in:seq,qty',
            'value' => 'nullable',
        ]);

        $rowId = $validated['row_id'];
        $date = Carbon::parse($validated['date']);
        $field = $validated['field'];
        $value = $validated['value'];

        $actualRowId = null;

        if (str_starts_with($rowId, 'vpart_')) {
            $partId = (int) str_replace('vpart_', '', $rowId);

            // Find or create a plan that covers this date
            // We'll create weekly plans by default if none exist
            $startOfWeek = $date->copy()->startOfWeek();
            $endOfWeek = $date->copy()->endOfWeek();

            $plan = OutgoingDailyPlan::firstOrCreate(
                [
                    'date_from' => $startOfWeek->toDateString(),
                    'date_to' => $endOfWeek->toDateString(),
                ],
                [
                    'created_by' => auth()->id(),
                ]
            );

            // Find or create the row
            $part = GciPart::findOrFail($partId);
            $row = OutgoingDailyPlanRow::firstOrCreate(
                [
                    'plan_id' => $plan->id,
                    'gci_part_id' => $part->id,
                ],
                [
                    'row_no' => (int) ($plan->rows()->max('row_no') ?? 0) + 1,
                    'production_line' => '-',
                    'part_no' => $part->part_no,
                ]
            );
            $actualRowId = $row->id;
        } else {
            $actualRowId = (int) $rowId;
        }

        $cell = OutgoingDailyPlanCell::updateOrCreate(
            ['row_id' => $actualRowId, 'plan_date' => $date->toDateString()],
            [$field => $value]
        );

        // Touch the plan so last update time is visible
        $planId = OutgoingDailyPlanRow::where('id', $actualRowId)->value('plan_id');
        if ($planId) {
            OutgoingDailyPlan::where('id', $planId)->update(['updated_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'cell' => $cell,
            'new_row_id' => $actualRowId // Returning this so frontend can update if needed
        ]);
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

        // Second: try customer part mapping (removed status filter to read all mappings)
        $customerPart = CustomerPart::query()
            ->where('customer_part_no', $partNo)
            // BUGFIX: Removed ->where('status', 'active') to allow all customer parts
            ->with([
                'components.part' => function ($q) {
                    $q->where('classification', 'FG');
                }
            ])
            ->first();

        if ($customerPart) {
            $fgComponents = $customerPart->components
                ->filter(fn($c) => $c->part && $c->part->classification === 'FG' && $c->gci_part_id)
                ->sortByDesc(fn($c) => (float) ($c->qty_per_unit ?? 0))
                ->values();

            $first = $fgComponents->first();
            if ($first && $first->gci_part_id) {
                return (int) $first->gci_part_id;
            }
        }

        $created = GciPart::create([
            'part_no' => $partNo,
            'part_name' => $partNo,
            'classification' => 'FG',
            'status' => 'active',
        ]);

        return (int) $created->id;
    }
}
