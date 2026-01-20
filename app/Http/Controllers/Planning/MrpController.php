<?php

namespace App\Http\Controllers\Planning;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\GciInventory;
use App\Models\MrpProductionPlan;
use App\Models\MrpPurchasePlan;
use App\Models\MrpRun;
use App\Models\Mps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MrpController extends Controller
{
    private function validateMinggu(string $field = 'minggu'): array
    {
        return [$field => ['required', 'string', 'regex:/^\d{4}-W(0[1-9]|[1-4][0-9]|5[0-3])$/']];
    }

    public function index(Request $request)
    {
        $month = $request->query('month') ?: now()->format('Y-m');
        $startOfMonth = \Carbon\Carbon::parse($month)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Generate Dates 1..End
        $dates = [];
        $temp = $startOfMonth->copy();
        while ($temp->lte($endOfMonth)) {
            $dates[] = $temp->format('Y-m-d');
            $temp->addDay();
        }

        // Fetch Latest Run in this month? Or just latest generally?
        // MRP Run is usually weekly. For Monthly view, we might need to aggregate runs or just pick the latest one active for this period.
        // For simplicity: Pick latest run that overlaps or just the very latest run in DB.
        // Let's assume user wants to see the latest plan state.
        
        // Get weeks for this month
        $weeks = $this->getWeeksForMonth($month);
        
        // Fetch Latest Run for EACH week in this month
        // We want the latest run for Week X, latest for Week Y...
        $latestRunIds = MrpRun::whereIn('minggu', $weeks)
            ->selectRaw('MAX(id) as id')
            ->groupBy('minggu')
            ->pluck('id');
            
        $runs = MrpRun::with(['purchasePlans.part', 'productionPlans.part'])
            ->whereIn('id', $latestRunIds)
            ->get();

        // Prepare Data Structure: Part -> [Info, Stock, Days => [Plan, Incoming, Projected, Net]]
        $mrpData = [];
        
        // 1. Get all active Parts (or just those in BOM/Stock? Let's take parts from Inventory + MRP Plans)
        $partIds = collect([]);
        // 1. Get all active Parts
        $partIds = collect([]);
        foreach ($runs as $run) {
             $partIds = $partIds->merge($run->purchasePlans->pluck('part_id'))
                                ->merge($run->productionPlans->pluck('part_id'));
        }
        $partIds = $partIds->unique();
        
        $parts = \App\Models\Part::whereIn('id', $partIds)->get()->keyBy('id');
        $inventories = GciInventory::whereIn('gci_part_id', $partIds)->get()->keyBy('gci_part_id');
        
        // Existing Arrivals (Incoming)
        // Check Arrivals where ETD or ATA is within range
        // Note: 'receives' table is what we actually received. 'arrival_items' is what is expected.
        // We look at 'arrival_items' that are NOT fully received yet (remaining).
        // Simplification: just get ArrivalItems with ETA in this month.
        $arrivals = \App\Models\ArrivalItem::with('arrival')
            ->whereHas('arrival', function($q) use ($startOfMonth, $endOfMonth) {
                 // Logic for date: Use arrival.etd (estimated time arrival)
                 $q->whereBetween('eta', [$startOfMonth, $endOfMonth]); // Assuming 'eta' column exists or 'invoice_date'? 
                 // Actually Upgrade: Let's assume we use invoice_date or created_at if eta missing for now, 
                 // or properly add ETA to arrival. For now, use 'invoice_date' as proxy or CreatedAt.
                 // Better: check migrations. 'arrivals' has 'etd' (Departure time) and 'eta' (Arrival time)?
                 // Checked migrations: 'arrivals' has 'etd' (date) and 'eta' (date).
                 $q->whereBetween('eta', [$startOfMonth, $endOfMonth]);
            })
            ->get();
            
        // Map Arrivals to Dates
        $incomingMap = []; // part_id -> date -> qty
        foreach ($arrivals as $item) {
             $date = $item->arrival->eta?->format('Y-m-d');
             if ($date && in_array($date, $dates)) {
                 $incomingMap[$item->part_id][$date] = ($incomingMap[$item->part_id][$date] ?? 0) + $item->qty_goods; // Or remaining?
             }
        }

        foreach ($partIds as $partId) {
            $part = $parts[$partId] ?? null;
            if (!$part) continue;

            $inv = $inventories[$partId] ?? null;
            $startStock = $inv ? $inv->on_hand : 0;
            
            $rowData = [
                'part' => $part,
                'initial_stock' => $startStock,
                'days' => []
            ];
            
            $runningStock = $startStock;
            
            foreach ($dates as $date) {
                // Demand/Plan (From MRP Run) -> negative stock
                // ProductionPlan for FG is Supply for FG, BUT Demand for Components?
                // Wait, MRP View usually shows:
                // For FG: Demand = Forecast/Order, Supply = Production Plan.
                // For RM: Demand = Production Plan of Parent, Supply = Purchase Plan/Arrival.
                
                // Simplified View: Just show what MRP Run computed.
                // Run->productionPlans = Calculated Requirement to Make? Or Resulting Supply?
                // In generate(): ProductionPlan is created based on MPS (Demand). So it is the "Plan to Make". 
                // For RM: NetRequired is "Plan to Buy".
                
                // Let's rely on standard MRP display:
                // Gross Requirement (Demand)
                // Scheduled Receipts (Incoming)
                // Projected On Hand
                // Net Requirement (Shortage planning)
                // Planned Order Release (New POs/Jobs)
                
                // Mapping our DB to this:
                // Demand: Not stored explicitly in MRP Run for RM? Yes, we calculated $requirements.
                // But we didn't save "Gross Requirement" in DB for RM. We only saved "MrpPurchasePlan" (Net Req).
                // ISSUE: We need Gross Requirement to show proper calculation.
                // Workaround: We will use PurchasePlan->required_qty which we saved! (See generate method: 'required_qty' => $dailyRequired).
                
                $demand = 0;
                $supply = 0; // Existing Incoming
                $plannedOrder = 0; // New Recommendation
                
                // Check Purchase Plans for this Date (Aggregate from all relevant runs)
                // Since runs are unique per week, and plan_date is unique day, there should be only one plan record per day across these runs.
                // We use $runs collection.
                
                $pPlan = null;
                $prodPlan = null;
                
                foreach ($runs as $runItem) {
                    // Purchase Plan
                    $pp = $runItem->purchasePlans->where('part_id', $partId)->where('plan_date', $date)->first();
                    if ($pp) {
                        $pPlan = $pp;
                    }
                    // Production Plan
                    $prp = $runItem->productionPlans->where('part_id', $partId)->where('plan_date', $date)->first();
                    if ($prp) {
                         $prodPlan = $prp;
                    }
                }

                if ($pPlan) {
                    $demand = $pPlan->required_qty;
                    $plannedOrder = $pPlan->net_required;
                }
                
                // Check Production Plans (If it's an FG/WIP, this is the "Plan to make" -> Supply? No, MPS is demand, ProdPlan is supply to meet MPS?
                // In generate(): MPS -> MrpProductionPlan.
                // So MrpProductionPlan IS the supply to meet external demand.
                // Let's treat it as "Plan" row.
                
                // ProdPlan already fetched in loop above
                 if ($prodPlan) {
                     // For FG/WIP
                     // If this part is FG, ProdPlan is basically "Production Order Recommendation".
                     $plannedOrder += $prodPlan->planned_qty;
                 }
                
                $incoming = $incomingMap[$partId][$date] ?? 0;
                
                // Projected Stock Calculation for End of Day
                // Proj = Start + Incoming + PlannedOrder(if we do it) - Demand
                // Usually "Projected Stock" excludes "Planned Order" to show the shortage (Net Req).
                // If we include Planned Order, stock stays >= 0 (ideally).
                // Let's show Projected Stock assuming ONLY Existing Incoming. 
                // Then Net Req shows what is needed.
                
                // Logic:
                // Stock[i] = Stock[i-1] + Incoming[i] - Demand[i]
                $endStock = $runningStock + $incoming - $demand;
                
                $rowData['days'][$date] = [
                    'demand' => $demand,
                    'incoming' => $incoming,
                    'projected_stock' => $endStock,
                    'net_required' => $endStock < 0 ? abs($endStock) : 0, 
                    'planned_order_rec' => $plannedOrder // What MRP suggests to add
                ];
                
                 // Update running stock
                 // If we assume we fulfill the net req:
                 // $runningStock = $endStock + ($endStock < 0 ? abs($endStock) : 0);
                 // But strictly, Projected Stock should show the Drop.
                 // However, for the next day, does the shortage carry over? Yes.
                 $runningStock = $endStock; 
            }
            
            $mrpData[] = $rowData;
        }

        return view('planning.mrp.index', compact('month', 'dates', 'mrpData', 'runs'));
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
            $date = \Carbon\Carbon::now()->setISODate((int)$m[1], (int)$m[2], 1);
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
        $approvedMps = Mps::query()
            ->where('minggu', $minggu)
            ->where('status', 'approved')
            ->with('part')
            ->get();

        // If no approved MPS, we just skip? Or create empty run?
        // Ideally skip to avoid clutter, but for MRP view consistency maybe we need it?
        // Let's skip if empty but maybe user wants to see "No Data".
        if ($approvedMps->isEmpty()) {
            return; 
        }

        $run = MrpRun::create([
            'minggu' => $minggu,
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

        foreach ($approvedMps as $row) {
            if ($row->planned_qty <= 0) continue;
            
            $dailyQty = $row->planned_qty / $workDays;
            
            foreach ($dates as $date) {
                if ($dailyQty > 0) {
                    MrpProductionPlan::create([
                        'mrp_run_id' => $run->id,
                        'part_id' => $row->part_id,
                        'plan_date' => $date,
                        'planned_qty' => $dailyQty,
                    ]);
                }
            }
        }
        
        // Calculate Requirements
        $requirements = [];
        $componentMode = [];

        foreach ($approvedMps as $row) {
            $bom = Bom::query()->with('items')->where('part_id', $row->part_id)->first();
            if (!$bom) continue;

            foreach ($bom->items as $item) {
                $componentId = (int) $item->component_part_id;
                $requirements[$componentId] = ($requirements[$componentId] ?? 0)
                    + ((float) $row->planned_qty * (float) $item->usage_qty);

                $mob = strtolower((string) ($item->make_or_buy ?? 'buy'));
                if ($mob === 'make') {
                    $componentMode[$componentId] = 'make';
                } elseif (!isset($componentMode[$componentId])) {
                    $componentMode[$componentId] = 'buy';
                }
            }
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
                        ]);
                    }
                }
            }
        }
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
                    if ($qty <= 0) continue;
                    
                    $price = $part->price ?? 0;
                    
                    $arrival->items()->create([
                        'part_id' => $part->id,
                        'qty_goods' => $qty,
                        'unit_goods' => $part->uom && in_array($part->uom, ['PCS','COIL','SHEET','SET','EA','KGM','ROLL','UOM']) ? $part->uom : 'PCS',
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
