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
        $minggu = $request->query('minggu') ?: now()->format('o-\\WW');

        $run = MrpRun::query()
            ->with(['purchasePlans.part', 'productionPlans.part'])
            ->where('minggu', $minggu)
            ->latest('id')
            ->first();

        // Helper to get dates from Week
        $year = (int) substr($minggu, 0, 4);
        $week = (int) substr($minggu, 6, 2);
        // Carbon setISODate logic
        $startDate = now();
        $startDate->setISODate($year, $week); 
        $startDate->startOfDay();
        
        $dates = [];
        for ($i = 0; $i < 5; $i++) {
            $dates[] = $startDate->copy()->addDays($i)->format('Y-m-d');
        }

        return view('planning.mrp.index', compact('minggu', 'run', 'dates'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate($this->validateMinggu());
        $minggu = $validated['minggu'];

        $approvedMps = Mps::query()
            ->where('minggu', $minggu)
            ->where('status', 'approved')
            ->with('part')
            ->get();

        if ($approvedMps->isEmpty()) {
            return back()->with('error', 'MRP requires approved MPS.');
        }

        DB::transaction(function () use ($minggu, $approvedMps, $request) {
            $run = MrpRun::create([
                'minggu' => $minggu,
                'status' => 'completed',
                'run_by' => $request->user()?->id,
                'run_at' => now(),
            ]);

            // Helper to get dates from Week
            $year = (int) substr($minggu, 0, 4);
            $week = (int) substr($minggu, 6, 2);
            $startDate = now()->setISODate($year, $week)->startOfDay();
            
            // Work Days Config
            $includeSaturday = $request->boolean('include_saturday');
            $workDays = $includeSaturday ? 6 : 5;
            
            $dates = [];
            for ($i = 0; $i < $workDays; $i++) {
                $dates[] = $startDate->copy()->addDays($i)->format('Y-m-d');
            }

            foreach ($approvedMps as $row) {
                // Production Plan for FG/WIP (From MPS)
                // Distributed evenly
                if ($row->planned_qty <= 0) continue;
                
                $dailyQty = $row->planned_qty / $workDays;
                
                foreach ($dates as $date) {
                    if ($dailyQty > 0) {
                        MrpProductionPlan::create([
                            'mrp_run_id' => $run->id,
                            'part_id' => $row->part_id,
                            'plan_date' => $date, // Daily Date
                            'planned_qty' => $dailyQty,
                        ]);
                    }
                }
            }

            $requirements = []; // component_id => total_required
            $componentMode = [];

            // Calculate Total Requirements first (still aggregated per week for simple calculation, 
            // but we can also breakdown. For now let's aggregate then split again or split at source?)
            // Better: Iterate BOM and add to specific dates? 
            // Complexity: A part might be used in different parents.
            // Simplified approach: Calculate Total Weekly Required -> Split Daily.
            
            foreach ($approvedMps as $row) {
                $bom = Bom::query()->with('items')->where('part_id', $row->part_id)->first();
                if (!$bom) {
                    continue;
                }

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
                
                // Net Required is calculated against Total Weekly Demand vs Total Stock
                // This is a simplified MRP. Real MRP would run day-by-day balance.
                // We will stick to Weekly Balance -> Daily Split for visualization.
                $netRequired = max(0, $requiredQty - $onHand - $onOrder);

                $dailyNetRequired = $netRequired / $workDays;
                $dailyRequired = $requiredQty / $workDays; // Gross required daily
                
                // We create entries for each day
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
                        // Purchase Plan
                        if ($requiredQty > 0) {
                             MrpPurchasePlan::create([
                                'mrp_run_id' => $run->id,
                                'part_id' => $partId,
                                'plan_date' => $date,
                                'required_qty' => $dailyRequired,
                                'on_hand' => $onHand, // Showing total stock (static)
                                'on_order' => $onOrder,
                                'net_required' => $dailyNetRequired,
                            ]);
                        }
                    }
                }
            }
        });

        return redirect()->route('planning.mrp.index', ['minggu' => $minggu])
            ->with('success', 'MRP generated.');
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
