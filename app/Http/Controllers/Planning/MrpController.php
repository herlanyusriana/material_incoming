<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\CustomerPartComponent;
use App\Models\Forecast;
use App\Models\GciInventory;
use App\Models\MrpProductionPlan;
use App\Models\MrpPurchasePlan;
use App\Models\MrpRun;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MrpController extends Controller
{
    private function validatePeriod(string $field = 'period'): array
    {
        return [$field => ['required', 'string', 'regex:/^\d{4}-\d{2}$/']];
    }

    private function countWorkdaysInMonth(string $ym, bool $includeSaturday): int
    {
        try {
            $cursor = \Carbon\Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
        } catch (\Throwable $e) {
            return 0;
        }

        $end = $cursor->copy()->endOfMonth();
        $count = 0;

        while ($cursor->lte($end)) {
            if ($cursor->isWeekday() || ($includeSaturday && $cursor->isSaturday())) {
                $count++;
            }
            $cursor->addDay();
        }

        return $count;
    }

    private function normalizePartNo(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtoupper(trim($value));
        return $value === '' ? null : $value;
    }

    private function resolveGciPartIdFromPartNo(?string $partNo, array &$cache): ?int
    {
        $partNo = $this->normalizePartNo($partNo);
        if ($partNo === null) {
            return null;
        }

        // Some upstream files append notes after a space; try the first token too.
        $candidates = [$partNo];
        if (str_contains($partNo, ' ')) {
            $candidates[] = $this->normalizePartNo(strtok($partNo, ' '));
        }

        foreach ($candidates as $candidate) {
            if ($candidate === null) {
                continue;
            }

            if (array_key_exists($candidate, $cache)) {
                return $cache[$candidate];
            }

            $id = (int) (\App\Models\GciPart::query()->where('part_no', $candidate)->value('id') ?? 0);
            $cache[$candidate] = $id > 0 ? $id : null;

            if ($cache[$candidate] !== null) {
                return $cache[$candidate];
            }
        }

        // Ensure all candidates are cached to avoid repeated queries.
        foreach ($candidates as $candidate) {
            if ($candidate !== null && !array_key_exists($candidate, $cache)) {
                $cache[$candidate] = null;
            }
        }

        return null;
    }

    private function resolveOrCreateGciPartIdFromBomItem(\App\Models\BomItem $item, array &$partNoCache): ?int
    {
        $partNo = $this->normalizePartNo($item->component_part_no);
        if ($partNo === null) {
            return null;
        }

        $id = $this->resolveGciPartIdFromPartNo($partNo, $partNoCache);
        if ($id !== null) {
            if ((int) ($item->component_part_id ?? 0) <= 0) {
                $item->component_part_id = $id;
                $item->component_part_no = $partNo;
                $item->save();
            }
            return $id;
        }

        $mob = strtolower((string) ($item->make_or_buy ?? 'buy'));
        $classification = $mob === 'make' ? 'WIP' : 'RM';
        $partName = $item->material_name ? trim((string) $item->material_name) : null;

        $part = \App\Models\GciPart::query()->firstOrCreate(
            ['part_no' => $partNo],
            ['part_name' => $partName, 'classification' => $classification, 'status' => 'active'],
        );

        $partNoCache[$partNo] = (int) $part->id;

        if ((int) ($item->component_part_id ?? 0) <= 0) {
            $item->component_part_id = (int) $part->id;
            $item->component_part_no = $partNo;
            $item->save();
        }

        return (int) $part->id;
    }

    private function explodeBomRequirements(
        int $parentPartId,
        float $parentQty,
        array &$requirements,
        array &$componentMode,
        array &$bomCache,
        array &$partNoCache,
        int $level = 0,
        int $maxLevels = 10,
        array &$path = [],
    ): void {
        if ($level >= $maxLevels) {
            return;
        }

        // Prevent cycles per branch.
        if (isset($path[$parentPartId])) {
            return;
        }
        $path[$parentPartId] = true;

        $bom = $bomCache[$parentPartId] ?? null;
        if ($bom === null) {
            $bom = Bom::query()
                ->with('items')
                ->where('part_id', $parentPartId)
                ->where('status', 'active')
                ->first();
            $bomCache[$parentPartId] = $bom ?: false;
        }

        if ($bom === false || !$bom) {
            unset($path[$parentPartId]);
            return;
        }

        foreach ($bom->items as $item) {
            $componentId = (int) ($item->component_part_id ?? 0);
            if ($componentId <= 0) {
                $componentId = (int) ($this->resolveOrCreateGciPartIdFromBomItem($item, $partNoCache) ?? 0);
            }

            if ($componentId <= 0) {
                continue;
            }

            $mob = strtolower((string) ($item->make_or_buy ?? 'buy'));
            if ($mob === 'free_issue') {
                continue;
            }

            $netUsage = (float) ($item->net_required ?? $item->usage_qty ?? 0);
            if ($netUsage <= 0) {
                continue;
            }

            $requiredQty = $parentQty * $netUsage;
            if ($requiredQty <= 0) {
                continue;
            }

            $requirements[$componentId] = ($requirements[$componentId] ?? 0) + $requiredQty;

            if ($mob === 'make') {
                $componentMode[$componentId] = 'make';
            } elseif (!isset($componentMode[$componentId])) {
                $componentMode[$componentId] = 'buy';
            }

            if ($mob === 'make') {
                $this->explodeBomRequirements(
                    $componentId,
                    $requiredQty,
                    $requirements,
                    $componentMode,
                    $bomCache,
                    $partNoCache,
                    $level + 1,
                    $maxLevels,
                    $path,
                );
            }
        }

        unset($path[$parentPartId]);
    }

    public function index(Request $request)
    {
        $period = $request->query('month') ?: $request->query('period') ?: now()->format('Y-m');
        $year = (int) substr($period, 0, 4);
        $selectedMonth = \Carbon\Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $selectedMonthEnd = $selectedMonth->copy()->endOfMonth();

        // Dates for daily view (1..end of selected month)
        $dates = [];
        $cursor = $selectedMonth->copy();
        while ($cursor->lte($selectedMonthEnd)) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        $startOfYear = \Carbon\Carbon::create($year, 1, 1)->startOfDay();
        $endOfYear = $startOfYear->copy()->endOfYear();
        $startKey = $startOfYear->format('Y-m-d');
        $endKey = $endOfYear->format('Y-m-d');

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = \Carbon\Carbon::create($year, $m, 1)->format('Y-m');
        }
        $monthLabels = collect($months)->mapWithKeys(function (string $ym) {
            $label = \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('M');
            return [$ym => $label];
        })->all();

        // MRP runs are generated per-week (period format: YYYY-Www). For monthly (month-columns) view,
        // load the latest run for each ISO week that touches the selected year.
        $weeks = $this->getWeeksForRange($startOfYear, $endOfYear);
        $latestRunIds = MrpRun::query()
            ->whereIn('period', $weeks)
            ->selectRaw('MAX(id) as id')
            ->groupBy('period')
            ->pluck('id')
            ->filter()
            ->values();

        if ($latestRunIds->isEmpty()) {
            return view('planning.mrp.index', [
                'period' => $period,
                'mrpData' => [],
                'dates' => $dates,
                'months' => $months,
                'monthLabels' => $monthLabels,
            ]);
        }

        // Fetch plans directly (avoid eager-loading each run with all relations).
        $purchaseSelect = ['part_id', 'plan_date'];
        if (Schema::hasColumn('mrp_purchase_plans', 'required_qty')) {
            $purchaseSelect[] = 'required_qty';
        }
        if (Schema::hasColumn('mrp_purchase_plans', 'net_required')) {
            $purchaseSelect[] = 'net_required';
        }
        if (Schema::hasColumn('mrp_purchase_plans', 'planned_order_rec')) {
            $purchaseSelect[] = 'planned_order_rec';
        }

        $purchasePlans = MrpPurchasePlan::query()
            ->whereIn('mrp_run_id', $latestRunIds)
            ->whereBetween('plan_date', [$startKey, $endKey])
            ->get($purchaseSelect);

        $productionSelect = ['part_id', 'plan_date'];
        if (Schema::hasColumn('mrp_production_plans', 'planned_qty')) {
            $productionSelect[] = 'planned_qty';
        }
        if (Schema::hasColumn('mrp_production_plans', 'net_required')) {
            $productionSelect[] = 'net_required';
        }
        if (Schema::hasColumn('mrp_production_plans', 'planned_order_rec')) {
            $productionSelect[] = 'planned_order_rec';
        }

        $productionPlans = MrpProductionPlan::query()
            ->whereIn('mrp_run_id', $latestRunIds)
            ->whereBetween('plan_date', [$startKey, $endKey])
            ->get($productionSelect);

        // Prepare Data Structure: Part -> [Info, Stock, Days => [Plan, Incoming, Projected, Net]]
        $mrpData = [];

        $purchaseByPartMonth = [];   // [part_id][Y-m] => [demand, planned]
        $productionByPartMonth = []; // [part_id][Y-m] => [demand, planned]
        $purchaseByPartDate = [];    // [part_id][Y-m-d] => [demand, planned] (selected month only)
        $productionByPartDate = [];  // [part_id][Y-m-d] => [demand, planned] (selected month only)
        $partIds = collect();

        $monthStartKey = $selectedMonth->format('Y-m-d');
        $monthEndKey = $selectedMonthEnd->format('Y-m-d');

        foreach ($purchasePlans as $pp) {
            $dateKey = $pp->plan_date instanceof \Carbon\CarbonInterface ? $pp->plan_date->format('Y-m-d') : (string) $pp->plan_date;
            $ym = substr($dateKey, 0, 7);
            $ppDemand = (float) (($pp->required_qty ?? null) !== null ? $pp->required_qty : ($pp->net_required ?? 0));
            $ppPlanned = (float) ($pp->planned_order_rec ?? $pp->net_required ?? 0);
            $purchaseByPartMonth[$pp->part_id][$ym]['demand'] = ($purchaseByPartMonth[$pp->part_id][$ym]['demand'] ?? 0) + $ppDemand;
            $purchaseByPartMonth[$pp->part_id][$ym]['planned'] = ($purchaseByPartMonth[$pp->part_id][$ym]['planned'] ?? 0) + $ppPlanned;

            if ($dateKey >= $monthStartKey && $dateKey <= $monthEndKey) {
                $purchaseByPartDate[$pp->part_id][$dateKey]['demand'] = ($purchaseByPartDate[$pp->part_id][$dateKey]['demand'] ?? 0) + $ppDemand;
                $purchaseByPartDate[$pp->part_id][$dateKey]['planned'] = ($purchaseByPartDate[$pp->part_id][$dateKey]['planned'] ?? 0) + $ppPlanned;
            }

            $partIds->push($pp->part_id);
        }

        foreach ($productionPlans as $pr) {
            $dateKey = $pr->plan_date instanceof \Carbon\CarbonInterface ? $pr->plan_date->format('Y-m-d') : (string) $pr->plan_date;
            $ym = substr($dateKey, 0, 7);
            $prDemand = (float) (($pr->planned_qty ?? null) !== null ? $pr->planned_qty : ($pr->net_required ?? 0));
            $prPlanned = (float) ($pr->planned_order_rec ?? $pr->planned_qty ?? $pr->net_required ?? 0);
            $productionByPartMonth[$pr->part_id][$ym]['demand'] = ($productionByPartMonth[$pr->part_id][$ym]['demand'] ?? 0) + $prDemand;
            $productionByPartMonth[$pr->part_id][$ym]['planned'] = ($productionByPartMonth[$pr->part_id][$ym]['planned'] ?? 0) + $prPlanned;

            if ($dateKey >= $monthStartKey && $dateKey <= $monthEndKey) {
                $productionByPartDate[$pr->part_id][$dateKey]['demand'] = ($productionByPartDate[$pr->part_id][$dateKey]['demand'] ?? 0) + $prDemand;
                $productionByPartDate[$pr->part_id][$dateKey]['planned'] = ($productionByPartDate[$pr->part_id][$dateKey]['planned'] ?? 0) + $prPlanned;
            }

            $partIds->push($pr->part_id);
        }

        $partIds = $partIds->unique()->values();
        $hasPurchaseParts = array_fill_keys(array_map('intval', array_keys($purchaseByPartMonth)), true);
        $hasProductionParts = array_fill_keys(array_map('intval', array_keys($productionByPartMonth)), true);

        $parts = \App\Models\GciPart::whereIn('id', $partIds)->get()->keyBy('id');
        $inventories = GciInventory::whereIn('gci_part_id', $partIds)->get()->keyBy('gci_part_id');

        // Customer Part Mapping (LINE / CASE) for each GCI part.
        $mappingByPartId = [];
        if ($partIds->isNotEmpty()) {
            $rawMappings = CustomerPartComponent::query()
                ->join('customer_parts as cp', 'cp.id', '=', 'customer_part_components.customer_part_id')
                ->whereIn('customer_part_components.gci_part_id', $partIds->all())
                ->where('cp.status', 'active')
                ->get([
                    'customer_part_components.gci_part_id as gci_part_id',
                    'cp.line as line',
                    'cp.case_name as case_name',
                ]);

            foreach ($rawMappings as $m) {
                $pid = (int) ($m->gci_part_id ?? 0);
                if ($pid <= 0) {
                    continue;
                }

                $line = trim((string) ($m->line ?? ''));
                $case = trim((string) ($m->case_name ?? ''));

                if (!isset($mappingByPartId[$pid])) {
                    $mappingByPartId[$pid] = ['lines' => [], 'cases' => []];
                }
                if ($line !== '') {
                    $mappingByPartId[$pid]['lines'][$line] = true;
                }
                if ($case !== '') {
                    $mappingByPartId[$pid]['cases'][$case] = true;
                }
            }
        }

        foreach ($partIds as $partId) {
            $part = $parts[$partId] ?? null;
            if (!$part)
                continue;

            $hasPurchase = isset($hasPurchaseParts[(int) $partId]);
            $hasProduction = isset($hasProductionParts[(int) $partId]);

            $inv = $inventories[$partId] ?? null;
            $startStock = $inv ? $inv->on_hand : 0;

            $monthlyDemand = [];
            $monthlyPlanned = [];

            foreach ($months as $ym) {
                $demand = (float) (($purchaseByPartMonth[$partId][$ym]['demand'] ?? 0) + ($productionByPartMonth[$partId][$ym]['demand'] ?? 0));
                $planned = (float) (($purchaseByPartMonth[$partId][$ym]['planned'] ?? 0) + ($productionByPartMonth[$partId][$ym]['planned'] ?? 0));
                $monthlyDemand[$ym] = $demand;
                $monthlyPlanned[$ym] = $planned;
            }

            $demandTotal = array_sum($monthlyDemand);
            $plannedOrderTotal = array_sum($monthlyPlanned);
            $incomingTotal = 0.0;
            $endStock = (float) $startStock + $incomingTotal - (float) $demandTotal;
            $netRequired = $endStock < 0 ? abs($endStock) : 0.0;

            // Build daily view row for the selected month.
            $days = [];
            $runningStock = (float) $startStock;
            foreach ($dates as $dateKey) {
                $p = $purchaseByPartDate[$partId][$dateKey] ?? null;
                $pr = $productionByPartDate[$partId][$dateKey] ?? null;

                $demand = 0.0;
                $planned = 0.0;

                if ($p) {
                    $demand += (float) ($p['demand'] ?? 0);
                    $planned += (float) ($p['planned'] ?? 0);
                }
                if ($pr) {
                    $demand += (float) ($pr['demand'] ?? 0);
                    $planned += (float) ($pr['planned'] ?? 0);
                }

                $incoming = 0.0;
                $endDayStock = $runningStock + $incoming - $demand;

                $days[$dateKey] = [
                    'demand' => $demand,
                    'incoming' => $incoming,
                    'projected_stock' => $endDayStock,
                    'net_required' => $endDayStock < 0 ? abs($endDayStock) : 0,
                    'planned_order_rec' => $planned,
                ];

                $runningStock = $endDayStock;
            }

            $mapping = $mappingByPartId[(int) $partId] ?? null;
            $mappedLines = $mapping ? implode(', ', array_keys($mapping['lines'] ?? [])) : '';
            $mappedCases = $mapping ? implode(', ', array_keys($mapping['cases'] ?? [])) : '';

            $rowData = [
                'part' => $part,
                'initial_stock' => $startStock,
                'has_purchase' => $hasPurchase,
                'has_production' => $hasProduction,
                'demand_total' => $demandTotal,
                'incoming_total' => $incomingTotal,
                'planned_order_total' => $plannedOrderTotal,
                'end_stock' => $endStock,
                'net_required' => $netRequired,
                'monthly_demand' => $monthlyDemand,
                'monthly_planned' => $monthlyPlanned,
                'days' => $days,
                'mapped_line' => $mappedLines,
                'mapped_case' => $mappedCases,
            ];

            $mrpData[] = $rowData;
        }

        $mrpDataBuy = array_values(array_filter($mrpData, fn ($r) => (bool) ($r['has_purchase'] ?? false)));
        $mrpDataMake = array_values(array_filter($mrpData, fn ($r) => (bool) ($r['has_production'] ?? false)));

        return view('planning.mrp.index', compact('period', 'dates', 'months', 'monthLabels', 'mrpData', 'mrpDataBuy', 'mrpDataMake'));
    }

    private function getWeeksForRange(\Carbon\Carbon $start, \Carbon\Carbon $end): array
    {
        $weeks = [];
        $current = $start->copy();
        while ($current->lte($end)) {
            $w = $current->format('o-\\WW');
            if (!in_array($w, $weeks, true)) {
                $weeks[] = $w;
            }
            $current->addDay();
        }
        return $weeks;
    }

    private function getWeeksForMonth(string $monthStr): array
    {
        $startOfMonth = \Carbon\Carbon::parse($monthStr . '-01')->startOfDay();
        // Use a simple date iteration to find all ISO weeks touching this month
        $weeks = [];
        $current = $startOfMonth->copy();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        while ($current->lte($endOfMonth)) {
            $w = $current->format('o-\\WW');
            if (!in_array($w, $weeks)) {
                $weeks[] = $w;
            }
            $current->addDay();
        }
        return $weeks;
    }

    public function generateRange(Request $request)
    {
        $startMinggu = $request->input('start_minggu');
        $weeksCount = (int) $request->input('weeks_count', 4);
        $generateProductionOrders = $request->boolean('generate_production_orders', true);
        $month = $request->input('month');

        if (!$startMinggu) {
            return back()->with('error', 'Start week is required.');
        }

        if ($month !== null && !preg_match('/^\\d{4}-\\d{2}$/', (string) $month)) {
            return back()->with('error', 'Invalid month format.');
        }

        $weeks = [];
        // Generate weeks array
        if (preg_match('/^(\d{4})-W(\d{2})$/', $startMinggu, $m)) {
            $date = \Carbon\Carbon::now()->setISODate((int) $m[1], (int) $m[2], 1);
            for ($i = 0; $i < $weeksCount; $i++) {
                $weeks[] = $date->copy()->addWeeks($i)->format('o-\\WW');
            }
        } else {
            return back()->with('error', 'Invalid start week format.');
        }

        $productionOrdersCreated = 0;
        $productionOrdersUpdated = 0;
        $runsCreated = 0;

        DB::transaction(function () use ($weeks, $request, $generateProductionOrders, $month, &$productionOrdersCreated, &$productionOrdersUpdated, &$runsCreated) {
            foreach ($weeks as $minggu) {
                // Call generate logic per week
                // We construct a fake request or extract logic.
                // Extracting logic is cleaner.
                $summary = $this->runMrpForWeek(
                    $minggu,
                    $request->user()?->id,
                    $request->boolean('include_saturday'),
                    $generateProductionOrders,
                    $month,
                );

                if (is_array($summary)) {
                    $runsCreated += (int) ($summary['mrp_runs_created'] ?? 0);
                    $productionOrdersCreated += (int) ($summary['production_orders_created'] ?? 0);
                    $productionOrdersUpdated += (int) ($summary['production_orders_updated'] ?? 0);
                }
            }
        });

        if ($runsCreated <= 0) {
            return back()->with('error', 'MRP skipped: no Forecast found for selected period.');
        }

        $msg = 'MRP generated for ' . $runsCreated . ' week(s).';
        if ($generateProductionOrders) {
            $msg .= ' Production Orders: ' . $productionOrdersCreated . ' created, ' . $productionOrdersUpdated . ' updated.';
        }

        return back()->with('success', $msg);
    }

    // Extracted logic from generate()
    private function syncProductionOrdersFromMrpRun(MrpRun $run, string $planDate, ?int $userId): array
    {
        $periodKey = preg_replace('/[^0-9A-Za-z]/', '', (string) $run->period);
        $hasQtyRejected = Schema::hasColumn('production_orders', 'qty_rejected');

        $qtyCols = [];
        if (Schema::hasColumn('mrp_production_plans', 'planned_order_rec')) {
            $qtyCols[] = 'planned_order_rec';
        }
        if (Schema::hasColumn('mrp_production_plans', 'planned_qty')) {
            $qtyCols[] = 'planned_qty';
        }
        if (Schema::hasColumn('mrp_production_plans', 'net_required')) {
            $qtyCols[] = 'net_required';
        }

        if (empty($qtyCols)) {
            return ['created' => 0, 'updated' => 0];
        }

        $qtyExpr = 'SUM(COALESCE(' . implode(', ', $qtyCols) . ', 0))';

        $planRows = MrpProductionPlan::query()
            ->where('mrp_run_id', $run->id)
            ->select([
                'part_id',
                DB::raw($qtyExpr . ' as qty'),
            ])
            ->groupBy('part_id')
            ->get();

        if ($planRows->isEmpty()) {
            return ['created' => 0, 'updated' => 0];
        }

        // Production orders generated from MRP should only be for finished goods (FG).
        $allowedPartIds = \App\Models\GciPart::query()
            ->whereIn('id', $planRows->pluck('part_id')->all())
            ->whereIn('classification', ['FG'])
            ->pluck('id')
            ->flip();

        $created = 0;
        $updated = 0;

        foreach ($planRows as $row) {
            $partId = (int) $row->part_id;
            if ($partId <= 0) {
                continue;
            }

            if (!isset($allowedPartIds[$partId])) {
                continue;
            }

            $qtyPlanned = (float) ($row->qty ?? 0);
            if ($qtyPlanned <= 0) {
                continue;
            }
            
            // Auto-populate process/machine from BOM
            $bom = \App\Models\Bom::where('part_id', $partId)->latest()->first();
            $processName = null;
            $machineName = null;
            
            if ($bom) {
                // Try to get from first WIP item, otherwise from first item
                $bomItems = $bom->items()->orderBy('line_no')->get();
                $targetItem = $bomItems->firstWhere('wip_part_id', '!=', null) ?? $bomItems->first();
                
                if ($targetItem) {
                    $processName = $targetItem->process_name;
                    $machineName = $targetItem->machine_name;
                }
            }

            $orderNo = 'MO-MRP-' . $periodKey . '-' . str_pad((string) $partId, 6, '0', STR_PAD_LEFT);

            $existing = ProductionOrder::query()
                ->where('production_order_number', $orderNo)
                ->first();

            if ($existing) {
                if (in_array((string) $existing->status, ['draft', 'planned'], true)) {
                    $updatePayload = [
                        'gci_part_id' => $partId,
                        'plan_date' => $planDate,
                        'qty_planned' => $qtyPlanned,
                        'workflow_stage' => $existing->workflow_stage ?: 'planned',
                        'mrp_run_id' => $run->id,
                        'mrp_period' => $run->period,
                        'mrp_generated' => true,
                        'process_name' => $processName,
                        'machine_name' => $machineName,
                    ];
                    // Some environments use qty_rejected instead of qty_ng and may not have a DB default.
                    if ($hasQtyRejected) {
                        $updatePayload['qty_rejected'] = (float) ($existing->qty_rejected ?? 0);
                    }
                    $existing->update($updatePayload);
                    $updated++;
                }
                continue;
            }

            $createPayload = [
                'production_order_number' => $orderNo,
                'gci_part_id' => $partId,
                'plan_date' => $planDate,
                'qty_planned' => $qtyPlanned,
                'status' => 'planned',
                'workflow_stage' => 'planned',
                'mrp_run_id' => $run->id,
                'mrp_period' => $run->period,
                'mrp_generated' => true,
                'qty_actual' => 0,
                'created_by' => $userId,
                'process_name' => $processName,
                'machine_name' => $machineName,
            ];
            // Some environments use qty_rejected instead of qty_ng and may not have a DB default.
            if ($hasQtyRejected) {
                $createPayload['qty_rejected'] = 0;
            }

            ProductionOrder::create($createPayload);

            $created++;
        }

        return ['created' => $created, 'updated' => $updated];
    }

    private function runMrpForWeek($minggu, $userId, $includeSaturday, bool $generateProductionOrders = false, ?string $targetMonth = null): ?array
    {
        // Demand input:
        // - Prefer Forecast period weekly (YYYY-Www) if present.
        // - Fallback to monthly (YYYY-MM) by prorating to workdays inside this week.

        $run = MrpRun::create([
            'period' => $minggu,
            'status' => 'completed',
            'run_by' => $userId,
            'run_at' => now(),
        ]);

        // ... Copy logic from old generate ...
        // Helper to get dates from Week
        $year = (int) substr($minggu, 0, 4);
        $week = (int) substr($minggu, 6, 2);
        $startDate = now()->setISODate($year, $week)->startOfDay();

        $workDays = $includeSaturday ? 6 : 5;

        $dates = [];
        for ($i = 0; $i < $workDays; $i++) {
            $dates[] = $startDate->copy()->addDays($i)->format('Y-m-d');
        }

        if ($targetMonth !== null) {
            $dates = array_values(array_filter($dates, fn (string $d) => substr($d, 0, 7) === $targetMonth));
        }

        if (empty($dates)) {
            $run->delete();
            return null;
        }

        $forecastWeeklyRows = Forecast::query()
            ->where('period', $minggu)
            ->where('qty', '>', 0)
            ->whereNotNull('part_id')
            ->select(['id', 'part_id', 'period', 'qty'])
            ->get();

        $mrpProductionPlanHasPlannedQty = Schema::hasColumn('mrp_production_plans', 'planned_qty');
        $mrpProductionPlanHasNetRequired = Schema::hasColumn('mrp_production_plans', 'net_required');

        $forecastRows = $forecastWeeklyRows;
        $weeklyPlannedQtyByPart = []; // [part_id => qty] used for BOM explode

        if ($forecastRows->isNotEmpty()) {
            foreach ($forecastRows as $row) {
                $plannedQty = (float) $row->qty;
                if ($plannedQty <= 0) {
                    continue;
                }

                $dailyQty = $plannedQty / count($dates);
                $weeklyPlannedQtyByPart[(int) $row->part_id] = ($weeklyPlannedQtyByPart[(int) $row->part_id] ?? 0) + $plannedQty;

                foreach ($dates as $date) {
                    if ($dailyQty <= 0) {
                        continue;
                    }

                    $payload = [
                        'mrp_run_id' => $run->id,
                        'part_id' => $row->part_id,
                        'plan_date' => $date,
                        'planned_order_rec' => $dailyQty,
                    ];
                    if ($mrpProductionPlanHasPlannedQty) {
                        $payload['planned_qty'] = $dailyQty;
                    }
                    if ($mrpProductionPlanHasNetRequired) {
                        $payload['net_required'] = 0;
                    }

                    MrpProductionPlan::create($payload);
                }
            }
        } else {
            $demandPeriods = $targetMonth ? [$targetMonth] : array_values(array_unique(array_map(fn (string $d) => substr($d, 0, 7), $dates)));

            $forecastMonthlyRows = Forecast::query()
                ->whereIn('period', $demandPeriods)
                ->where('qty', '>', 0)
                ->whereNotNull('part_id')
                ->select(['id', 'part_id', 'period', 'qty'])
                ->get();

            if ($forecastMonthlyRows->isEmpty()) {
                $run->delete();
                return null;
            }

            $workdaysInMonthCache = [];
            $datesByMonth = [];
            foreach ($dates as $date) {
                $ym = substr($date, 0, 7);
                $datesByMonth[$ym][] = $date;
            }

            foreach ($forecastMonthlyRows as $row) {
                $monthKey = (string) $row->period;
                $plannedQtyMonthly = (float) $row->qty;
                if ($plannedQtyMonthly <= 0) {
                    continue;
                }

                $datesInThisWeekForMonth = $datesByMonth[$monthKey] ?? [];
                if (empty($datesInThisWeekForMonth)) {
                    continue;
                }

                if (!array_key_exists($monthKey, $workdaysInMonthCache)) {
                    $workdaysInMonthCache[$monthKey] = $this->countWorkdaysInMonth($monthKey, (bool) $includeSaturday);
                }
                $workdaysInMonth = (int) ($workdaysInMonthCache[$monthKey] ?? 0);
                if ($workdaysInMonth <= 0) {
                    continue;
                }

                $dailyQty = $plannedQtyMonthly / $workdaysInMonth;
                $weeklyQty = $dailyQty * count($datesInThisWeekForMonth);
                if ($weeklyQty <= 0) {
                    continue;
                }

                $weeklyPlannedQtyByPart[(int) $row->part_id] = ($weeklyPlannedQtyByPart[(int) $row->part_id] ?? 0) + $weeklyQty;

                foreach ($datesInThisWeekForMonth as $date) {
                    $payload = [
                        'mrp_run_id' => $run->id,
                        'part_id' => $row->part_id,
                        'plan_date' => $date,
                        'planned_order_rec' => $dailyQty,
                    ];
                    if ($mrpProductionPlanHasPlannedQty) {
                        $payload['planned_qty'] = $dailyQty;
                    }
                    if ($mrpProductionPlanHasNetRequired) {
                        $payload['net_required'] = 0;
                    }

                    MrpProductionPlan::create($payload);
                }
            }

            if (empty($weeklyPlannedQtyByPart)) {
                $run->delete();
                return null;
            }
        }

        // Calculate Requirements
        $requirements = [];
        $componentMode = [];
        $bomCache = [];
        $partNoCache = [];

        foreach ($weeklyPlannedQtyByPart as $partId => $plannedQty) {
            // Explode BOM (multi-level) using BOM make/buy.
            $path = [];
            $this->explodeBomRequirements(
                (int) $partId,
                $plannedQty,
                $requirements,
                $componentMode,
                $bomCache,
                $partNoCache,
                0,
                10,
                $path,
            );
        }

        foreach ($requirements as $partId => $requiredQty) {
            $inventory = GciInventory::query()->where('gci_part_id', $partId)->first();
            $onHand = (float) ($inventory->on_hand ?? 0);
            $onOrder = (float) ($inventory->on_order ?? 0);

            $netRequired = max(0, $requiredQty - $onHand - $onOrder);

            $dailyNetRequired = $netRequired / count($dates);
            $dailyRequired = $requiredQty / count($dates);

            foreach ($dates as $date) {
                if (($componentMode[$partId] ?? 'buy') === 'make') {
                    if ($dailyNetRequired > 0) {
                        $payload = [
                            'mrp_run_id' => $run->id,
                            'part_id' => $partId,
                            'plan_date' => $date,
                            'planned_order_rec' => $dailyNetRequired,
                        ];
                        if ($mrpProductionPlanHasPlannedQty) {
                            $payload['planned_qty'] = $dailyNetRequired;
                        }
                        if ($mrpProductionPlanHasNetRequired) {
                            $payload['net_required'] = $dailyNetRequired;
                        }
                        MrpProductionPlan::create($payload);
                    }
                } else {
                    if ($requiredQty > 0) {
                        MrpPurchasePlan::create([
                            'mrp_run_id' => $run->id,
                            'part_id' => $partId,
                            'plan_date' => $date,
                            'required_qty' => $dailyRequired,
                            'on_hand' => $onHand,
                            'on_order' => $onOrder,
                            'net_required' => $dailyNetRequired,
                            'planned_order_rec' => $dailyNetRequired,
                        ]);
                    }
                }
            }
        }

        $summary = ['mrp_runs_created' => 1, 'production_orders_created' => 0, 'production_orders_updated' => 0];

        if ($generateProductionOrders) {
            $poSummary = $this->syncProductionOrdersFromMrpRun($run, $startDate->toDateString(), $userId);
            $summary['production_orders_created'] = (int) ($poSummary['created'] ?? 0);
            $summary['production_orders_updated'] = (int) ($poSummary['updated'] ?? 0);
        }

        return $summary;
    }

    public function generate(Request $request)
    {
        $month = $request->input('month') ?: now()->format('Y-m');
        $weeks = $this->getWeeksForMonth($month);
        $generateProductionOrders = $request->boolean('generate_production_orders', true);

        $productionOrdersCreated = 0;
        $productionOrdersUpdated = 0;
        $runsCreated = 0;

        DB::transaction(function () use ($weeks, $request, $generateProductionOrders, $month, &$productionOrdersCreated, &$productionOrdersUpdated, &$runsCreated) {
            foreach ($weeks as $minggu) {
                $summary = $this->runMrpForWeek(
                    $minggu,
                    $request->user()?->id,
                    $request->boolean('include_saturday'),
                    $generateProductionOrders,
                    $month,
                );

                if (is_array($summary)) {
                    $runsCreated += (int) ($summary['mrp_runs_created'] ?? 0);
                    $productionOrdersCreated += (int) ($summary['production_orders_created'] ?? 0);
                    $productionOrdersUpdated += (int) ($summary['production_orders_updated'] ?? 0);
                }
            }
        });

        if ($runsCreated <= 0) {
            return back()->with('error', 'MRP skipped: no Forecast found for selected period.');
        }

        $msg = 'MRP generated for ' . $runsCreated . ' week(s).';
        if ($generateProductionOrders) {
            $msg .= ' Production Orders: ' . $productionOrdersCreated . ' created, ' . $productionOrdersUpdated . ' updated.';
        }

        return back()->with('success', $msg);
    }

    public function generatePo(Request $request)
    {
        // Expecting: items array [gci_part_id => qty]
        $items = $request->input('items', []);

        if (empty($items)) {
            return back()->with('error', 'No items selected for PO.');
        }

        // Group by Vendor?
        // Logic: All selected items must belong to LOCAL vendors for this feature?
        // Or we create mixed?
        // Constraint: We only support Local PO creation for now.
        // We need to fetch parts to check vendors.

        $gciPartIds = array_map('intval', array_keys($items));
        $gciParts = \App\Models\GciPart::query()
            ->whereIn('id', $gciPartIds)
            ->get(['id', 'part_no']);

        $gciById = $gciParts->keyBy('id');
        $gciIdByNo = $gciParts
            ->filter(fn ($p) => (string) ($p->part_no ?? '') !== '')
            ->mapWithKeys(fn ($p) => [strtoupper(trim((string) $p->part_no)) => (int) $p->id])
            ->all();
        $partNos = $gciParts->pluck('part_no')->filter()->unique()->values()->all();

        // Bridge to Incoming `parts` by part_no (we intentionally keep BOM/Planning in gci_parts only).
        $parts = \App\Models\Part::with('vendor')
            ->whereIn('part_no', $partNos)
            ->get();

        $partsByNo = $parts->keyBy(fn ($p) => strtoupper(trim((string) $p->part_no)));

        $missing = [];
        foreach ($gciPartIds as $gciId) {
            $pno = strtoupper(trim((string) ($gciById[$gciId]?->part_no ?? '')));
            if ($pno === '' || !isset($partsByNo[$pno])) {
                $missing[] = $pno !== '' ? $pno : ('ID:' . $gciId);
            }
        }
        $missing = array_values(array_unique(array_filter($missing)));
        if (!empty($missing)) {
            $preview = implode(', ', array_slice($missing, 0, 10));
            $more = count($missing) > 10 ? (' … +' . (count($missing) - 10) . ' more') : '';
            return back()->with('error', "Selected items are not registered in Incoming Part master (parts): {$preview}{$more}. Create Part master first (matching part_no).");
        }

        $nonLocalParts = $parts->filter(fn($p) => strtolower($p->vendor->vendor_type ?? '') !== 'local');

        if ($nonLocalParts->isNotEmpty()) {
            return back()->with('error', 'Some selected parts are not from LOCAL vendors. Only Local POs are supported currently.');
        }

        // Group by Vendor to create multiple POs if needed
        // Only include selected parts, map from selected GCI ids -> incoming Part rows.
        $selectedIncomingParts = collect($gciPartIds)
            ->map(function (int $gciId) use ($gciById, $partsByNo) {
                $pno = strtoupper(trim((string) ($gciById[$gciId]?->part_no ?? '')));
                return $pno !== '' ? ($partsByNo[$pno] ?? null) : null;
            })
            ->filter()
            ->unique('id')
            ->values();

        $grouped = $selectedIncomingParts->groupBy('vendor_id');

        DB::transaction(function () use ($grouped, $items, $gciIdByNo) {
            foreach ($grouped as $vendorId => $vendorParts) {
                // Create Arrival (PO)
                $poNo = 'PO-MRP-' . now()->format('ymdHis') . '-' . $vendorId;

                $arrival = \App\Models\Arrival::create([
                    'invoice_no' => $poNo,
                    'invoice_date' => now(), // PO Date
                    'vendor_id' => $vendorId,
                    'currency' => 'IDR', // Default
                    'notes' => 'Generated from MRP',
                    'created_by' => auth()->id(),
                ]);

                foreach ($vendorParts as $part) {
                    $pno = strtoupper(trim((string) $part->part_no));
                    $gciId = (int) ($gciIdByNo[$pno] ?? 0);
                    $qty = (int) ($items[$gciId] ?? 0);
                    if ($qty <= 0)
                        continue;

                    $price = $part->price ?? 0;

                    $arrival->items()->create([
                        'part_id' => $part->id,
                        'qty_goods' => $qty,
                        'unit_goods' => $part->uom && in_array($part->uom, ['PCS', 'COIL', 'SHEET', 'SET', 'EA', 'KGM', 'ROLL', 'UOM']) ? $part->uom : 'PCS',
                        'price' => $price,
                        'total_price' => $qty * $price,
                        'qty_bundle' => 0,
                        'unit_bundle' => 'PALLET',
                        'unit_weight' => 'KGM',
                        'weight_nett' => 0,
                        'weight_gross' => 0,
                    ]);
                }
            }
        });

        return redirect()->route('local-pos.index')->with('success', 'Local PO(s) generated successfully from MRP Selection.');
    }

    /**
     * Clear all MRP data
     */
    public function clear(Request $request)
    {
        DB::transaction(function () {
            $runCount = \App\Models\MrpRun::count();
            $purchaseCount = \App\Models\MrpPurchasePlan::count();
            $productionCount = \App\Models\MrpProductionPlan::count();

            \App\Models\MrpPurchasePlan::query()->delete();
            \App\Models\MrpProductionPlan::query()->delete();
            \App\Models\MrpRun::query()->delete();

            // Log the clear action
            \App\Models\MrpHistory::create([
                'user_id' => auth()->id(),
                'action' => 'clear',
                'parts_count' => $purchaseCount + $productionCount,
                'notes' => "Cleared {$runCount} MRP runs, {$purchaseCount} purchase plans, {$productionCount} production plans",
            ]);
        });

        return redirect()->route('planning.mrp.index')->with('success', 'All MRP data has been cleared.');
    }

    /**
     * Show MRP history
     */
    public function history(Request $request)
    {
        $histories = \App\Models\MrpHistory::with('user', 'mrpRun')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('planning.mrp.history', compact('histories'));
    }
}
