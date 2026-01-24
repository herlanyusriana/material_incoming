<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\Forecast;
use App\Models\GciInventory;
use App\Models\MrpProductionPlan;
use App\Models\MrpPurchasePlan;
use App\Models\MrpRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MrpController extends Controller
{
    private function validatePeriod(string $field = 'period'): array
    {
        return [$field => ['required', 'string', 'regex:/^\d{4}-\d{2}$/']];
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
                    $demand = (float) ($p['demand'] ?? 0);
                    $planned = (float) ($p['planned'] ?? 0);
                } elseif ($pr) {
                    $demand = (float) ($pr['demand'] ?? 0);
                    $planned = (float) ($pr['planned'] ?? 0);
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

        if (!$startMinggu) {
            return back()->with('error', 'Start week is required.');
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

        DB::transaction(function () use ($weeks, $request) {
            foreach ($weeks as $minggu) {
                // Call generate logic per week
                // We construct a fake request or extract logic.
                // Extracting logic is cleaner.
                $this->runMrpForWeek($minggu, $request->user()?->id, $request->boolean('include_saturday'));
            }
        });

        return back()->with('success', 'MRP generated for ' . count($weeks) . ' weeks.');
    }

    // Extracted logic from generate()
    private function runMrpForWeek($minggu, $userId, $includeSaturday)
    {
        // Use Forecast as the demand input (period can be weekly like 2026-W02).
        // This keeps MPS (monthly) separate from MRP (weekly).
        $forecastRows = Forecast::query()
            ->where('period', $minggu)
            ->where('qty', '>', 0)
            ->whereNotNull('part_id')
            ->with('part')
            ->get();

        // If no approved MPS, we just skip? Or create empty run?
        // Ideally skip to avoid clutter, but for MRP view consistency maybe we need it?
        // Let's skip if empty but maybe user wants to see "No Data".
        if ($forecastRows->isEmpty()) {
            return;
        }

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

        foreach ($forecastRows as $row) {
            $plannedQty = (float) $row->qty;
            if ($plannedQty <= 0) {
                continue;
            }

            $dailyQty = $plannedQty / $workDays;

            foreach ($dates as $date) {
                if ($dailyQty > 0) {
                    MrpProductionPlan::create([
                        'mrp_run_id' => $run->id,
                        'part_id' => $row->part_id,
                        'plan_date' => $date,
                        'planned_qty' => $dailyQty,
                        'planned_order_rec' => $dailyQty,
                    ]);
                }
            }
        }

        // Calculate Requirements
        $requirements = [];
        $componentMode = [];
        $bomCache = [];
        $partNoCache = [];

        foreach ($forecastRows as $row) {
            $plannedQty = (float) $row->qty;
            if ($plannedQty <= 0) {
                continue;
            }

            // Explode BOM (multi-level) using BOM make/buy.
            $path = [];
            $this->explodeBomRequirements(
                (int) $row->part_id,
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

            $dailyNetRequired = $netRequired / $workDays;
            $dailyRequired = $requiredQty / $workDays;

            foreach ($dates as $date) {
                if (($componentMode[$partId] ?? 'buy') === 'make') {
                    if ($dailyNetRequired > 0) {
                        MrpProductionPlan::create([
                            'mrp_run_id' => $run->id,
                            'part_id' => $partId,
                            'plan_date' => $date,
                            'planned_qty' => $dailyNetRequired,
                            'planned_order_rec' => $dailyNetRequired,
                            'net_required' => $dailyNetRequired,
                        ]);
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
    }

    public function generate(Request $request)
    {
        $month = $request->input('month') ?: now()->format('Y-m');
        $weeks = $this->getWeeksForMonth($month);

        DB::transaction(function () use ($weeks, $request) {
            foreach ($weeks as $minggu) {
                $this->runMrpForWeek($minggu, $request->user()?->id, $request->boolean('include_saturday'));
            }
        });

        return back()->with('success', 'MRP generated for ' . count($weeks) . ' weeks.');
    }

    public function generatePo(Request $request)
    {
        // Expecting: items array [part_id => qty]
        $items = $request->input('items', []);

        if (empty($items)) {
            return back()->with('error', 'No items selected for PO.');
        }

        // Group by Vendor?
        // Logic: All selected items must belong to LOCAL vendors for this feature?
        // Or we create mixed?
        // Constraint: We only support Local PO creation for now.
        // We need to fetch parts to check vendors.

        $partIds = array_keys($items);
        $parts = \App\Models\Part::with('vendor')->whereIn('id', $partIds)->get();

        $nonLocalParts = $parts->filter(fn($p) => strtolower($p->vendor->vendor_type ?? '') !== 'local');

        if ($nonLocalParts->isNotEmpty()) {
            return back()->with('error', 'Some selected parts are not from LOCAL vendors. Only Local POs are supported currently.');
        }

        // Group by Vendor to create multiple POs if needed
        $grouped = $parts->groupBy('vendor_id');

        DB::transaction(function () use ($grouped, $items) {
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
                    $qty = (int) ($items[$part->id] ?? 0);
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
