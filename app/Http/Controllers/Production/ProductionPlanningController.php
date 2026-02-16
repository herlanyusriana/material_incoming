<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlanningSession;
use App\Models\ProductionPlanningLine;
use App\Models\ProductionOrder;
use App\Models\GciPart;
use App\Models\GciInventory;
use App\Models\FgInventory;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\OutgoingDailyPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductionPlanningController extends Controller
{
    /**
     * Main Production Planning page (GCI Planning Produksi)
     */
    public function index(Request $request)
    {
        $planDate = $request->get('date', now()->format('Y-m-d'));
        $planDate = Carbon::parse($planDate);

        // Get or create session
        $session = ProductionPlanningSession::where('plan_date', $planDate->format('Y-m-d'))->first();

        // Get planning lines grouped by machine_name (from BOM)
        $lines = collect();
        $machineGroups = [];
        if ($session) {
            $allLines = ProductionPlanningLine::where('session_id', $session->id)
                ->with(['gciPart.bom.items'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            // Group by machine_name
            foreach ($allLines as $line) {
                $machineName = $line->machine_name ?: 'Unassigned';
                if (!isset($machineGroups[$machineName])) {
                    $machineGroups[$machineName] = [
                        'machine_name' => $machineName,
                        'process_name' => $line->process_name ?: '-',
                        'lines' => [],
                        'subtotal_fg_lg' => 0,
                        'subtotal_fg_gci' => 0,
                        'subtotal_plan_qty' => 0,
                    ];
                }
                $machineGroups[$machineName]['lines'][] = $line;
                $machineGroups[$machineName]['subtotal_fg_lg'] += (float) $line->stock_fg_lg;
                $machineGroups[$machineName]['subtotal_fg_gci'] += (float) $line->stock_fg_gci;
                $machineGroups[$machineName]['subtotal_plan_qty'] += (float) $line->plan_qty;
            }
        }

        // Get daily planning data from outgoing
        $dailyPlanData = $this->getDailyPlanningData($planDate);

        // Get FG stock data
        $fgStockLg = $this->getFgStockLg();
        $fgStockGci = $this->getFgStockGci();

        // Date range for planning
        $planningDays = $session ? $session->planning_days : 7;
        $dateRange = [];
        for ($i = 0; $i < $planningDays; $i++) {
            $dateRange[] = $planDate->copy()->addDays($i);
        }

        // Get distinct machine names from BOMs for dropdown
        $bomMachineNames = BomItem::whereNotNull('machine_name')
            ->where('machine_name', '!=', '')
            ->distinct()
            ->pluck('machine_name')
            ->sort()
            ->values();

        // Get existing sessions for navigation
        $existingSessions = ProductionPlanningSession::orderBy('plan_date', 'desc')
            ->limit(30)
            ->get();

        // Grand totals
        $grandTotalFgLg = collect($machineGroups)->sum('subtotal_fg_lg');
        $grandTotalFgGci = collect($machineGroups)->sum('subtotal_fg_gci');
        $grandTotalPlanQty = collect($machineGroups)->sum('subtotal_plan_qty');
        $totalParts = collect($machineGroups)->sum(fn($g) => count($g['lines']));

        return view('production.planning.index', compact(
            'session',
            'machineGroups',
            'dailyPlanData',
            'fgStockLg',
            'fgStockGci',
            'planDate',
            'dateRange',
            'existingSessions',
            'planningDays',
            'bomMachineNames',
            'grandTotalFgLg',
            'grandTotalFgGci',
            'grandTotalPlanQty',
            'totalParts'
        ));
    }

    /**
     * Create or update a planning session
     */
    public function createSession(Request $request)
    {
        $request->validate([
            'plan_date' => 'required|date',
            'planning_days' => 'nullable|integer|min:1|max:14',
        ]);

        $session = ProductionPlanningSession::updateOrCreate(
            ['plan_date' => $request->plan_date],
            [
                'planning_days' => $request->planning_days ?? 7,
                'created_by' => auth()->id(),
            ]
        );

        return redirect()->route('production.planning.index', ['date' => $request->plan_date])
            ->with('success', 'Planning session created for ' . Carbon::parse($request->plan_date)->format('d M Y'));
    }

    /**
     * Auto-populate planning lines from GCI Parts + BOM machine data
     */
    public function autoPopulate(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:production_planning_sessions,id',
        ]);

        $session = ProductionPlanningSession::findOrFail($request->session_id);

        // Get FG stock data
        $fgStockLg = $this->getFgStockLg();
        $fgStockGci = $this->getFgStockGci();

        $sortOrder = ProductionPlanningLine::where('session_id', $session->id)->max('sort_order') ?? 0;

        DB::beginTransaction();
        try {
            // Get all FG GCI parts that have an active BOM
            $parts = GciPart::where('classification', 'FG')
                ->whereHas('bom', function ($q) {
                    $q->where('status', 'active');
                })
                ->with(['bom.items'])
                ->orderBy('part_name')
                ->get();

            foreach ($parts as $part) {
                // Check if line already exists for this part in session
                $exists = ProductionPlanningLine::where('session_id', $session->id)
                    ->where('gci_part_id', $part->id)
                    ->exists();

                if ($exists)
                    continue;

                // Get machine_name and process_name from BOM
                $machineName = null;
                $processName = null;
                $activeBom = $part->bom;
                if ($activeBom) {
                    foreach ($activeBom->items as $bomItem) {
                        if (!empty($bomItem->machine_name)) {
                            $machineName = $bomItem->machine_name;
                        }
                        if (!empty($bomItem->process_name)) {
                            $processName = $bomItem->process_name;
                        }
                        // Found both, stop
                        if ($machineName && $processName)
                            break;
                    }
                }

                $sortOrder++;

                ProductionPlanningLine::create([
                    'session_id' => $session->id,
                    'gci_part_id' => $part->id,
                    'machine_name' => $machineName,
                    'process_name' => $processName,
                    'stock_fg_lg' => $fgStockLg[$part->id] ?? 0,
                    'stock_fg_gci' => $fgStockGci[$part->id] ?? 0,
                    'sort_order' => $sortOrder,
                ]);
            }

            DB::commit();

            return redirect()->route('production.planning.index', ['date' => $session->plan_date->format('Y-m-d')])
                ->with('success', 'Planning lines auto-populated from BOM data. ' . $parts->count() . ' FG parts processed.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to auto-populate: ' . $e->getMessage());
        }
    }

    /**
     * Update a planning line (AJAX)
     */
    public function updateLine(Request $request, ProductionPlanningLine $line)
    {
        $request->validate([
            'field' => 'required|string',
            'value' => 'nullable',
        ]);

        $field = $request->field;
        $allowed = ['machine_name', 'process_name', 'production_sequence', 'plan_qty', 'shift', 'remark', 'stock_fg_lg', 'stock_fg_gci', 'sort_order'];

        if (!in_array($field, $allowed)) {
            return response()->json(['error' => 'Invalid field'], 422);
        }

        $line->update([$field => $request->value]);

        return response()->json(['success' => true, 'line' => $line->fresh()->load('gciPart')]);
    }

    /**
     * Add a new planning line
     */
    public function addLine(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:production_planning_sessions,id',
            'gci_part_id' => 'required|exists:gci_parts,id',
        ]);

        $sortOrder = ProductionPlanningLine::where('session_id', $request->session_id)->max('sort_order') + 1;

        $fgStockLg = $this->getFgStockLg();
        $fgStockGci = $this->getFgStockGci();

        // Get machine/process from BOM
        $part = GciPart::with('bom.items')->find($request->gci_part_id);
        $machineName = null;
        $processName = null;
        if ($part && $part->bom) {
            foreach ($part->bom->items as $bomItem) {
                if (!empty($bomItem->machine_name))
                    $machineName = $bomItem->machine_name;
                if (!empty($bomItem->process_name))
                    $processName = $bomItem->process_name;
                if ($machineName && $processName)
                    break;
            }
        }

        $line = ProductionPlanningLine::create([
            'session_id' => $request->session_id,
            'gci_part_id' => $request->gci_part_id,
            'machine_name' => $machineName,
            'process_name' => $processName,
            'stock_fg_lg' => $fgStockLg[$request->gci_part_id] ?? 0,
            'stock_fg_gci' => $fgStockGci[$request->gci_part_id] ?? 0,
            'sort_order' => $sortOrder,
        ]);

        return response()->json(['success' => true, 'line' => $line->load('gciPart')]);
    }

    /**
     * Delete a planning line
     */
    public function deleteLine(ProductionPlanningLine $line)
    {
        $line->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Generate MO/WO from planning session
     */
    public function generateMoWo(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:production_planning_sessions,id',
        ]);

        $session = ProductionPlanningSession::findOrFail($request->session_id);

        $lines = ProductionPlanningLine::where('session_id', $session->id)
            ->where('plan_qty', '>', 0)
            ->whereNotNull('production_sequence')
            ->with('gciPart')
            ->orderBy('production_sequence')
            ->get();

        if ($lines->isEmpty()) {
            return back()->with('error', 'No planning lines with production quantity and sequence found');
        }

        DB::beginTransaction();
        try {
            $generated = 0;
            foreach ($lines as $line) {
                // Check if MO already exists for this planning line
                $existingMo = ProductionOrder::where('planning_line_id', $line->id)->first();
                if ($existingMo)
                    continue;

                // Generate production order number
                $prefix = 'MO-' . now()->format('ymd');
                $lastOrder = ProductionOrder::where('production_order_number', 'like', $prefix . '%')
                    ->orderBy('production_order_number', 'desc')
                    ->first();
                $seq = $lastOrder ? intval(substr($lastOrder->production_order_number, -4)) + 1 : 1;
                $moNumber = $prefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

                ProductionOrder::create([
                    'production_order_number' => $moNumber,
                    'gci_part_id' => $line->gci_part_id,
                    'machine_name' => $line->machine_name,
                    'process_name' => $line->process_name,
                    'planning_line_id' => $line->id,
                    'plan_date' => $session->plan_date,
                    'qty_planned' => $line->plan_qty,
                    'shift' => $line->shift,
                    'production_sequence' => $line->production_sequence,
                    'status' => 'planned',
                    'created_by' => auth()->id(),
                ]);

                $generated++;
            }

            $session->update(['status' => 'confirmed', 'confirmed_by' => auth()->id(), 'confirmed_at' => now()]);

            DB::commit();

            return redirect()->route('production.planning.index', ['date' => $session->plan_date->format('Y-m-d')])
                ->with('success', "Successfully generated {$generated} production orders (MO/WO)");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to generate MO/WO: ' . $e->getMessage());
        }
    }

    /**
     * Get calculation data for a session (AJAX)
     */
    public function getCalculations(Request $request)
    {
        $sessionId = $request->get('session_id');
        $session = ProductionPlanningSession::findOrFail($sessionId);

        $lines = ProductionPlanningLine::where('session_id', $sessionId)
            ->with('gciPart')
            ->orderBy('sort_order')
            ->get();

        $planDate = $session->plan_date;
        $planningDays = $session->planning_days;
        $dateRange = [];
        for ($i = 0; $i < $planningDays; $i++) {
            $dateRange[] = $planDate->copy()->addDays($i)->format('Y-m-d');
        }

        $dailyRequirements = $this->getDailyRequirements($planDate, $planningDays);

        $result = [];
        foreach ($lines as $line) {
            $partId = $line->gci_part_id;
            $stockLg = (float) $line->stock_fg_lg;
            $stockGci = (float) $line->stock_fg_gci;
            $planQty = (float) $line->plan_qty;

            $dailyCalc = [];
            $runningStock = $stockLg + $planQty;

            foreach ($dateRange as $date) {
                $requirement = $dailyRequirements[$partId][$date] ?? 0;
                $runningStock -= $requirement;

                $dailyCalc[$date] = [
                    'stock' => $runningStock,
                    'requirement' => $requirement,
                    'difference' => $runningStock,
                ];
            }

            $result[$line->id] = [
                'daily' => $dailyCalc,
                'total_stock' => $stockLg + $stockGci,
            ];
        }

        return response()->json($result);
    }

    // ==========================================
    // PRIVATE HELPER METHODS
    // ==========================================

    /**
     * Get daily planning data from outgoing module
     */
    private function getDailyPlanningData(Carbon $planDate): array
    {
        $plans = OutgoingDailyPlan::where('date_from', '<=', $planDate)
            ->where('date_to', '>=', $planDate)
            ->with(['rows.cells', 'rows.gciPart'])
            ->get();

        $data = [];
        foreach ($plans as $plan) {
            foreach ($plan->rows as $row) {
                if (!$row->gci_part_id)
                    continue;
                $partId = $row->gci_part_id;

                if (!isset($data[$partId])) {
                    $data[$partId] = [
                        'part' => $row->gciPart,
                        'total_qty' => 0,
                        'production_line' => $row->production_line,
                    ];
                }

                foreach ($row->cells as $cell) {
                    $data[$partId]['total_qty'] += (int) $cell->qty;
                }
            }
        }

        return $data;
    }

    /**
     * Get daily requirements for a date range
     */
    private function getDailyRequirements(Carbon $startDate, int $days): array
    {
        $endDate = $startDate->copy()->addDays($days - 1);
        $requirements = [];

        $plans = OutgoingDailyPlan::where('date_from', '<=', $endDate)
            ->where('date_to', '>=', $startDate)
            ->with(['rows.cells', 'rows.gciPart'])
            ->get();

        foreach ($plans as $plan) {
            foreach ($plan->rows as $row) {
                if (!$row->gci_part_id)
                    continue;
                $partId = $row->gci_part_id;

                if (!isset($requirements[$partId])) {
                    $requirements[$partId] = [];
                }

                foreach ($row->cells as $cell) {
                    $cellDate = $cell->plan_date ?? null;
                    if ($cellDate) {
                        $dateKey = Carbon::parse($cellDate)->format('Y-m-d');
                        if (!isset($requirements[$partId][$dateKey])) {
                            $requirements[$partId][$dateKey] = 0;
                        }
                        $requirements[$partId][$dateKey] += (int) $cell->qty;
                    }
                }
            }
        }

        return $requirements;
    }

    /**
     * Get FG Stock LG (from FG Inventory)
     */
    private function getFgStockLg(): array
    {
        return FgInventory::pluck('qty_on_hand', 'gci_part_id')->toArray();
    }

    /**
     * Get FG Stock GCI (from GCI Inventory)
     */
    private function getFgStockGci(): array
    {
        return GciInventory::pluck('on_hand', 'gci_part_id')->toArray();
    }
}
