<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\CustomerPartComponent;
use App\Models\Forecast;
use App\Models\GciPartVendor;
use App\Models\NewSchema\Core\GciPart;
use App\Models\NewSchema\Inventory\InventoryLocationStock;
use App\Models\MrpProductionPlan;
use App\Models\MrpPurchasePlan;
use App\Models\MrpRun;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\MrpIncomingIntegrationService;
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
        $purchaseSelect = ['id', 'part_id', 'plan_date'];
        if (Schema::hasColumn('mrp_purchase_plans', 'required_qty')) {
            $purchaseSelect[] = 'required_qty';
        }
        if (Schema::hasColumn('mrp_purchase_plans', 'net_required')) {
            $purchaseSelect[] = 'net_required';
        }
        if (Schema::hasColumn('mrp_purchase_plans', 'planned_order_rec')) {
            $purchaseSelect[] = 'planned_order_rec';
        }
        if (Schema::hasColumn('mrp_purchase_plans', 'eta_week')) {
            $purchaseSelect[] = 'eta_week';
        }
        if (Schema::hasColumn('mrp_purchase_plans', 'order_week')) {
            $purchaseSelect[] = 'order_week';
        }
        if (Schema::hasColumn('mrp_purchase_plans', 'status')) {
            $purchaseSelect[] = 'status';
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
        $purchasePlanInfo = [];      // [part_id] => list of {id, eta_week, status, qty}
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

            $purchasePlanInfo[$pp->part_id][] = [
                'id' => (int) $pp->id,
                'plan_date' => $dateKey,
                'eta_week' => $pp->eta_week ?? null,
                'order_week' => $pp->order_week ?? null,
                'status' => $pp->status ?? 'pending',
                'qty' => $ppPlanned,
            ];

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

        $parts = GciPart::whereIn('id', $partIds)->get()->keyBy('id');
        $inventories = InventoryLocationStock::query()
            ->whereIn('gci_part_id', $partIds)
            ->groupBy('gci_part_id')
            ->pluck(DB::raw('SUM(qty_on_hand)'), 'gci_part_id');

        // Vendor-bridge policy (MOQ / lead time) per part — local active vendor wins.
        $vendorPolicyByPart = [];
        if ($partIds->isNotEmpty()) {
            $vendorPolicies = GciPartVendor::query()
                ->whereIn('gci_part_id', $partIds->all())
                ->where('status', 'active')
                ->orderBy('min_order_qty', 'desc')
                ->get(['gci_part_id', 'vendor_id', 'min_order_qty', 'lead_time_days'])
                ->each(fn ($v) => $vendorPolicyByPart[(int) $v->gci_part_id] = $v);
        }

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

        // Instantiate service once before loop to avoid N+1 performance issue
        $incomingService = new MrpIncomingIntegrationService();

        foreach ($partIds as $partId) {
            $part = $parts[$partId] ?? null;
            if (!$part)
                continue;

            $hasPurchase = isset($hasPurchaseParts[(int) $partId]);
            $hasProduction = isset($hasProductionParts[(int) $partId]);

            $startStock = (float) ($inventories[$partId] ?? 0);

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

            // Calculate incoming quantities from actual received materials
            $incomingTotal = $incomingService->getTotalIncomingForPart($partId, $startKey, $endKey);

            $endStock = (float) $startStock + $incomingTotal - (float) $demandTotal;
            $netRequired = $endStock < 0 ? abs($endStock) : 0.0;

            // Build daily view row for the selected month.
            $days = [];
            $runningStock = (float) $startStock;
            
            // Get daily incoming quantities
            $dailyIncoming = $incomingService->getIncomingQuantities([$partId], $monthStartKey, $monthEndKey)[$partId] ?? [];
            
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

                $incoming = (float) ($dailyIncoming[$dateKey] ?? 0);
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

            // Buy-plan details for the summarize view (PO selection / approval). Keep the
            // highest-impact (highest qty) non-rejected plan as the row's representative.
            $buyPlans = $purchasePlanInfo[(int) $partId] ?? [];
            $representative = null;
            $bestQty = -1.0;
            foreach ($buyPlans as $bp) {
                if (($bp['status'] ?? 'pending') === 'rejected') {
                    continue;
                }
                if ((float) $bp['qty'] > $bestQty) {
                    $bestQty = (float) $bp['qty'];
                    $representative = $bp;
                }
            }
            $planIdsCsv = implode(',', array_column($buyPlans, 'id'));

            $vendorPolicy = $vendorPolicyByPart[(int) $partId] ?? null;
            $safetyStock = (float) ($part->safety_stock ?? 0);
            $orderMultiple = (float) ($part->order_multiple ?? 0);
            $minOrderQty = (float) ($vendorPolicy->min_order_qty ?? 0);
            $leadTimeDays = $vendorPolicy->lead_time_days ?? null;

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
                'buy_plans' => $buyPlans,
                'plan_ids' => $planIdsCsv,
                'plan_id' => $representative['id'] ?? null,
                'eta_week' => $representative['eta_week'] ?? null,
                'order_week' => $representative['order_week'] ?? null,
                'plan_status' => $representative['status'] ?? null,
                'safety_stock' => $safetyStock,
                'order_multiple' => $orderMultiple,
                'min_order_qty' => $minOrderQty,
                'lead_time_days' => $leadTimeDays,
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
            $machineId = null;
            
            if ($bom) {
                // Try to get from first WIP item, otherwise from first item
                $bomItems = $bom->items()->orderBy('line_no')->get();
                $targetItem = $bomItems->firstWhere('wip_part_id', '!=', null) ?? $bomItems->first();
                
                if ($targetItem) {
                    $processName = $targetItem->process_name;
                    $machineId = $targetItem->machine_id;
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
                        'machine_id' => $machineId,
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
                'machine_id' => $machineId,
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
        // Demand input: monthly Forecast (period = YYYY-MM) only. The month's demand
        // is prorated across the workdays that fall inside this ISO week.

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

        $mrpProductionPlanHasPlannedQty = Schema::hasColumn('mrp_production_plans', 'planned_qty');
        $mrpProductionPlanHasNetRequired = Schema::hasColumn('mrp_production_plans', 'net_required');

        // Monthly demand (period = YYYY-MM). Only months overlapping this week's workloads.
        $demandPeriods = $targetMonth ? [$targetMonth] : array_values(array_unique(array_map(fn (string $d) => substr($d, 0, 7), $dates)));
        $weeklyPlannedQtyByPart = []; // [part_id => qty] used for BOM explode

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

            // Only create a production (make) plan when this part actually has an active BOM.
            // A top-level part with no BOM is a buy/resale item — it gets a purchase plan,
            // NOT a production order. This avoids a no-BOM buy part getting both a production
            // plan (here) and a purchase plan (fix #3) simultaneously.
            if (Bom::query()
                ->where('part_id', (int) $row->part_id)
                ->where('status', 'active')
                ->exists()) {
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
        }

        if (empty($weeklyPlannedQtyByPart)) {
            $run->delete();
            return null;
        }

        // Calculate Requirements
        $requirements = [];
        $componentMode = [];
        $bomCache = [];
        $partNoCache = [];

        // Pre-resolve which top-level parts have an active BOM. `explodeBomRequirements`
        // only adds COMPONENTS to $requirements — never the parent — so a top-level part
        // with NO active BOM would be dropped entirely. Such a part is a buy/resale item
        // (drop-ship) or a raw-material bought directly, so route it to the buy section.
        // Cache the Bom model itself so the explode below reuses it (not a raw bool).
        foreach ($weeklyPlannedQtyByPart as $partId => $plannedQty) {
            if (!array_key_exists($partId, $bomCache)) {
                $bomCache[$partId] = Bom::query()
                    ->with('items')
                    ->where('part_id', (int) $partId)
                    ->where('status', 'active')
                    ->first() ?: false;
            }
        }

        foreach ($weeklyPlannedQtyByPart as $partId => $plannedQty) {
            // Explode BOM (multi-level) using BOM make/buy.
            $path = [];

            if ($bomCache[$partId] === false) {
                $componentMode[(int) $partId] = 'buy';
                $requirements[(int) $partId] = ($requirements[(int) $partId] ?? 0) + $plannedQty;
                continue;
            }

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

        // Instantiate service once before loop to avoid N+1 performance issue
        $incomingService = new MrpIncomingIntegrationService();

        foreach ($requirements as $partId => $requiredQty) {
            // ── Phase 1: Safety stock (per-part manual column on gci_parts) ──
            $part = GciPart::query()->find($partId);
            $safetyStock = $part ? (float) ($part->safety_stock ?? 0) : 0;

            // ── Phase 2: MOQ per vendor-part link (min_order_qty) + order_multiple round-up ──
            $orderMultiple = $part ? (float) ($part->order_multiple ?? 0) : 0;
            $minOrderQty = (float) GciPartVendor::query()
                ->where('gci_part_id', $partId)
                ->where('status', 'active')
                ->orderBy('min_order_qty', 'desc')
                ->value('min_order_qty') ?? 0;

            $onHand = (float) InventoryLocationStock::where('gci_part_id', $partId)->sum('qty_on_hand');

            // Fix #1: on_order is committed quantity already on open vendor POs that has
            // NOT yet arrived/received. `incoming_stock` (via $incomingService) already
            // adds scheduled arrivals + receipts to available stock, so only subtract the
            // PO balance that is still outstanding (status Pending/Approved → not Released,
            // and qty − qty_received), excluding that which is already counted as incoming
            // to avoid double-counting the same goods.
            $onOrder = (float) PurchaseOrderItem::query()
                ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
                ->where('purchase_order_items.part_id', $partId)
                ->whereIn('purchase_orders.status', ['Pending', 'Approved'])
                ->selectRaw('SUM(purchase_order_items.qty - purchase_order_items.qty_received) as balance')
                ->value('balance') ?? 0;

            // Calculate incoming quantities for this period
            $incomingTotal = $incomingService->getTotalIncomingForPart($partId, $startDate->format('Y-m-d'), $dates[count($dates) - 1]);

            // Adjust on-hand with incoming stock
            $adjustedOnHand = $onHand + $incomingTotal;

            // Net requirement = demand + safety stock − available stock − on-order.
            $netRequired = max(0, $requiredQty + $safetyStock - $adjustedOnHand - $onOrder);

            // Apply MOQ: never order less than the vendor minimum, and round up to order_multiple.
            if ($netRequired > 0) {
                if ($minOrderQty > 0 && $netRequired < $minOrderQty) {
                    $netRequired = $minOrderQty;
                }
                if ($orderMultiple > 0) {
                    $netRequired = ceil($netRequired / $orderMultiple) * $orderMultiple;
                }
            }

            // Weekly ETA bucket (phase 3): the planning week itself, in YYYY-Www.
            // $minggu is already in o-WW format (e.g. "2026-W36"), so pass it through.
            $etaWeek = $minggu;

            // Fix #2: lead-time offset. Order week = ETA week − vendor lead_time_days so the
            // PO is placed early enough to arrive by eta_week. Uses the same vendor link that
            // supplies MOS/min_order_qty (active, highest min_order_qty) so the report matches.
            $leadTimeDays = (int) (GciPartVendor::query()
                ->where('gci_part_id', $partId)
                ->where('status', 'active')
                ->orderBy('min_order_qty', 'desc')
                ->value('lead_time_days') ?? 0);
            $orderWeek = $etaWeek;
            if ($leadTimeDays > 0) {
                $orderWeek = \Carbon\Carbon::createFromFormat('o-W', $etaWeek)
                    ->subDays($leadTimeDays)
                    ->format('o-W');
            }

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
                        $payload['status'] = 'pending'; // awaiting approval (phase 4)
                        MrpProductionPlan::create($payload);
                    }
                } else {
                    if ($requiredQty > 0) {
                        MrpPurchasePlan::create([
                            'mrp_run_id' => $run->id,
                            'part_id' => $partId,
                            'plan_date' => $date,
                            'eta_week' => $etaWeek,
                            'order_week' => $orderWeek,
                            'required_qty' => $dailyRequired,
                            'on_hand' => $onHand,
                            'on_order' => $onOrder,
                            'incoming_stock' => $incomingTotal,
                            'net_required' => $dailyNetRequired,
                            'planned_order_rec' => $dailyNetRequired,
                            'status' => 'pending', // awaiting approval (phase 4)
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
        // Only APPROVED purchase plans may be converted into vendor POs (phase 4 → create PO).
        // Accepted inputs: mrp_run_id (batch of approved plans) and/or plan_ids[].
        $runId = (int) $request->input('mrp_run_id', 0);
        $planIds = array_values(array_filter(array_map('intval', (array) $request->input('plan_ids', []))));

        if ($runId <= 0 && empty($planIds)) {
            return back()->with('error', 'Select a run or at least one approved plan to create a PO.');
        }

        // Resolve the approved buy plans.
        $plansQuery = MrpPurchasePlan::query()
            ->where('status', 'approved')
            ->with('part:id,part_no');

        if ($runId > 0) {
            $plansQuery->where('mrp_run_id', $runId);
        }
        if (!empty($planIds)) {
            $plansQuery->orWhereIn('id', array_unique($planIds));
        }

        $plans = $plansQuery->get();
        if ($plans->isEmpty()) {
            return back()->with('error', 'No approved MRP plans found for PO generation.');
        }

        $gciPartIds = $plans->pluck('part_id')->unique()->map(fn ($id) => (int) $id)->all();

        // Bridge to the Incoming `parts` view (read-only over gci_part_vendor) to get vendor_id + price.
        $parts = \App\Models\Part::with('vendor')
            ->whereIn('gci_part_id', $gciPartIds)
            ->get();

        $partsByGci = $parts->keyBy(fn ($p) => (int) $p->gci_part_id);

        $missing = $plans->pluck('part_id')
            ->filter(fn ($gciId) => !isset($partsByGci[(int) $gciId]))
            ->map(fn ($gciId) => (string) ($plans->firstWhere('part_id', $gciId)->part->part_no ?? ('ID:' . $gciId)))
            ->unique()
            ->values()
            ->all();

        if (!empty($missing)) {
            $preview = implode(', ', array_slice($missing, 0, 10));
            $more = count($missing) > 10 ? (' … +' . (count($missing) - 10) . ' more') : '';
            return back()->with('error', "Selected parts are not registered in Incoming Part master (parts): {$preview}{$more}. Create Part master first (matching part_no).");
        }

        $nonLocalParts = $parts->filter(fn ($p) => strtolower($p->vendor->vendor_type ?? '') !== 'local');
        if ($nonLocalParts->isNotEmpty()) {
            return back()->with('error', 'Some selected parts are not from LOCAL vendors. Only Local POs are supported currently.');
        }

        // Group approved plans by vendor for one PO per vendor.
        $grouped = $plans->groupBy(fn ($plan) => (int) ($partsByGci[(int) $plan->part_id]->vendor_id ?? 0));

        $created = 0;
        DB::transaction(function () use ($grouped, $partsByGci, &$created) {
            foreach ($grouped as $vendorId => $vendorPlans) {
                if ($vendorId <= 0) {
                    continue;
                }

                $prevPo = \App\Models\PurchaseOrder::query()
                    ->where('vendor_id', $vendorId)
                    ->whereIn('status', ['Pending', 'Approved'])
                    ->latest()
                    ->first();

                $po = \App\Models\PurchaseOrder::create([
                    'po_number' => $prevPo?->po_number ?? ('PO-MRP-' . now()->format('ymdHis') . '-' . $vendorId),
                    'vendor_id' => $vendorId,
                    'total_amount' => 0,
                    'status' => 'Pending', // approval is the next step
                    'notes' => 'Generated from MRP (approved plans)',
                ]);

                // Deduplicate plans that share a part across the group.
                $seenParts = [];
                $total = 0;
                foreach ($vendorPlans as $plan) {
                    $gciId = (int) $plan->part_id;
                    if (isset($seenParts[$gciId])) {
                        continue;
                    }
                    $seenParts[$gciId] = true;

                    $partRow = $partsByGci[$gciId] ?? null;
                    if (!$partRow) {
                        continue;
                    }

                    $qty = (float) ($plan->planned_order_rec > 0 ? $plan->planned_order_rec : $plan->net_required);
                    if ($qty <= 0) {
                        continue;
                    }

                    $price = (float) ($partRow->price ?? 0);
                    $subtotal = $qty * $price;
                    $total += $subtotal;

                    $po->items()->create([
                        'part_id' => $gciId,
                        'vendor_part_id' => $partRow->id,
                        'gci_part_vendor_id' => $partRow->id,
                        'qty' => $qty,
                        'unit_price' => $price,
                        'subtotal' => $subtotal,
                    ]);
                }

                $po->update(['total_amount' => $total]);
                $created++;
            }
        });

        return redirect()->route('planning.mrp.index')
            ->with('success', "{$created} vendor PO(s) created from approved MRP plans. Approve then release to send.");
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
     * Approve MRP plans (phase 4). Batch by mrp_run_id or a set of plan_ids[].
     */
    public function approvePlans(Request $request)
    {
        $this->assertCanApprove($request);

        $runId = (int) $request->input('mrp_run_id', 0);
        $planIds = array_values(array_filter(array_map('intval', (array) $request->input('plan_ids', []))));

        if ($runId <= 0 && empty($planIds)) {
            return back()->with('error', 'Select a run or at least one plan to approve.');
        }

        $status = $request->input('status', 'approved');
        $newStatus = $status === 'rejected' ? 'rejected' : 'approved';
        $isApprove = $newStatus === 'approved';

        $count = 0;
        DB::transaction(function () use ($runId, $planIds, $newStatus, $isApprove, &$count) {
            $apply = fn ($builder) => $builder->where('status', 'pending')->update([
                'status' => $newStatus,
                'approved_by' => auth()->id(),
                'approved_at' => $isApprove ? now() : null,
            ]);

            if ($runId > 0) {
                $count += $apply(MrpPurchasePlan::query()->where('mrp_run_id', $runId));
                $count += $apply(MrpProductionPlan::query()->where('mrp_run_id', $runId));
            }

            $ids = array_values(array_unique($planIds));
            if (!empty($ids)) {
                $count += $apply(MrpPurchasePlan::query()->whereIn('id', $ids));
                $count += $apply(MrpProductionPlan::query()->whereIn('id', $ids));
            }
        });

        $verb = $isApprove ? 'approved' : 'rejected';
        return redirect()->route('planning.mrp.index')
            ->with('success', "{$count} plan(s) {$verb}.");
    }

    /**
     * Reject MRP plans. Convenience wrapper around approvePlans().
     */
    public function rejectPlans(Request $request)
    {
        return $this->approvePlans($request->merge(['status' => 'rejected']));
    }

    private function assertCanApprove(Request $request): void
    {
        if (!auth()->user()?->can('approve_mrp')) {
            abort(403, 'You are not authorized to approve MRP plans.');
        }
    }

    /**
     * Release a vendor PO (phase 5).
     */
    public function releasePo(Request $request, int $purchaseOrderId)
    {
        if (!auth()->user()?->can('release_po')) {
            abort(403, 'You are not authorized to release purchase orders.');
        }

        $po = \App\Models\PurchaseOrder::query()->findOrFail($purchaseOrderId);

        // Only a PO that has been approved may be released.
        if (in_array($po->status, ['Released', 'Cancelled', 'Closed'])) {
            return back()->with('error', "PO {$po->po_number} is already {$po->status}.");
        }
        if (!in_array($po->status, ['Approved', 'Pending'])) {
            return back()->with('error', "PO {$po->po_number} cannot be released from '{$po->status}'.");
        }

        $po->update([
            'status' => 'Released',
            'released_by' => auth()->id(),
            'released_at' => now(),
        ]);

        return back()->with('success', "PO {$po->po_number} released.");
    }

    /**
     * Actualize a vendor PO: reconcile received qty vs ordered (phase 6).
     */
    public function actualizePo(Request $request, int $purchaseOrderId)
    {
        if (!auth()->user()?->can('manage_purchasing')) {
            abort(403, 'You are not authorized to actualize purchase orders.');
        }

        $po = \App\Models\PurchaseOrder::query()->with('items')->findOrFail($purchaseOrderId);

        $totalShort = 0;
        foreach ($po->items as $item) {
            $ordered = (float) ($item->qty ?? 0);
            $received = (float) ($item->qty_received ?? 0);
            $short = $ordered - $received;
            $totalShort += max(0, $short);
        }

        $allFulfilled = $totalShort <= 0;
        $po->update(['status' => $allFulfilled ? 'Closed' : 'Partially Received']);

        return back()->with('success', $allFulfilled
            ? "PO {$po->po_number} closed. All items received."
            : "PO {$po->po_number} has {$totalShort} qty outstanding (partially received).");
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

    /**
     * Show MRP and Incoming Integration Dashboard
     */
    public function integrationDashboard(Request $request)
    {
        $period = $request->query('month') ?: now()->format('Y-m');
        $incomingService = new MrpIncomingIntegrationService();
        
        // Get all GCI parts that have MRP data
        $mrpRuns = MrpRun::query()
            ->where('period', 'LIKE', substr($period, 0, 7) . '%')
            ->get();
        
        $mrpRunIds = $mrpRuns->pluck('id');
        
        // Get parts involved in MRP runs
        $mrpPartIds = [];
        if ($mrpRunIds->isNotEmpty()) {
            $mrpPartIds = MrpPurchasePlan::whereIn('mrp_run_id', $mrpRunIds)
                ->pluck('part_id')
                ->merge(
                    MrpProductionPlan::whereIn('mrp_run_id', $mrpRunIds)
                        ->pluck('part_id')
                )
                ->unique()
                ->values()
                ->toArray();
        }
        
        // Get incoming data for these parts
        $startOfMonth = \Carbon\Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $endOfMonth = \Carbon\Carbon::createFromFormat('Y-m', $period)->endOfMonth();
        
        $incomingData = $incomingService->getIncomingQuantities($mrpPartIds, 
            $startOfMonth->format('Y-m-d'), 
            $endOfMonth->format('Y-m-d')
        );
        
        // Get parts details
        $parts = \App\Models\GciPart::whereIn('id', $mrpPartIds)->get()->keyBy('id');
        
        // Calculate summary
        $totalIncoming = 0;
        $incomingByPart = [];
        
        foreach ($incomingData as $partId => $dailyIncoming) {
            $partIncoming = array_sum($dailyIncoming);
            $incomingByPart[$partId] = [
                'part' => $parts[$partId] ?? null,
                'total' => $partIncoming,
                'daily' => $dailyIncoming
            ];
            $totalIncoming += $partIncoming;
        }
        
        // Get MRP demand data for comparison
        $mrpDemandByPart = [];
        if ($mrpRunIds->isNotEmpty()) {
            $mrpPurchasePlans = MrpPurchasePlan::whereIn('mrp_run_id', $mrpRunIds)
                ->whereBetween('plan_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->get();
                
            foreach ($mrpPurchasePlans as $plan) {
                $partId = $plan->part_id;
                if (!isset($mrpDemandByPart[$partId])) {
                    $mrpDemandByPart[$partId] = 0;
                }
                $mrpDemandByPart[$partId] += $plan->required_qty ?? 0;
            }
        }
        
        return view('planning.mrp.integration-dashboard', compact(
            'period',
            'incomingByPart',
            'mrpDemandByPart',
            'totalIncoming'
        ));
    }
}
