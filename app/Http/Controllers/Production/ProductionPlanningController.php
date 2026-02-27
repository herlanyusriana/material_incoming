<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlanningSession;
use App\Models\ProductionPlanningLine;
use App\Models\ProductionOrder;
use App\Models\GciPart;
use App\Models\GciInventory;
use App\Models\FgInventory;
use App\Models\StockAtCustomer;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Machine;
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

        // Get planning lines grouped by machine (from BOM)
        $lines = collect();
        $machineGroups = [];
        if ($session) {
            $allLines = ProductionPlanningLine::where('session_id', $session->id)
                ->with(['gciPart.bom.items', 'productionOrders', 'machine'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            // Group by machine_id
            foreach ($allLines as $line) {
                $machineKey = $line->machine_id ?: 'unassigned';
                $machineName = $line->machine?->name ?: 'Unassigned';
                if (!isset($machineGroups[$machineKey])) {
                    $machineGroups[$machineKey] = [
                        'machine_id' => $line->machine_id,
                        'machine_name' => $machineName,
                        'process_name' => $line->process_name ?: '-',
                        'lines' => [],
                        'subtotal_fg_lg' => 0,
                        'subtotal_fg_gci' => 0,
                        'subtotal_plan_qty' => 0,
                    ];
                }
                $machineGroups[$machineKey]['lines'][] = $line;
                $machineGroups[$machineKey]['subtotal_fg_lg'] += (float) $line->stock_fg_lg;
                $machineGroups[$machineKey]['subtotal_fg_gci'] += (float) $line->stock_fg_gci;
                $machineGroups[$machineKey]['subtotal_plan_qty'] += (float) $line->plan_qty;
            }
        }

        // Get daily planning data from outgoing
        $dailyPlanData = $this->getDailyPlanningData($planDate);

        // Get FG stock data
        $fgStockLg = $this->getFgStockLg($planDate);
        $fgStockGci = $this->getFgStockGci();

        // Date range for planning
        $planningDays = $session ? $session->planning_days : 7;
        $dateRange = [];
        for ($i = 0; $i < $planningDays; $i++) {
            $dateRange[] = $planDate->copy()->addDays($i);
        }

        // Get active machines for dropdown
        $machines = Machine::where('is_active', true)->orderBy('name')->get();

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
            'machines',
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
        $fgStockLg = $this->getFgStockLg(Carbon::parse($session->plan_date));
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

                // Get machine_id and process_name from BOM
                $machineId = null;
                $processName = null;
                $activeBom = $part->bom;
                if ($activeBom) {
                    foreach ($activeBom->items as $bomItem) {
                        if (!empty($bomItem->machine_id)) {
                            $machineId = $bomItem->machine_id;
                        }
                        if (!empty($bomItem->process_name)) {
                            $processName = $bomItem->process_name;
                        }
                        // Found both, stop
                        if ($machineId && $processName)
                            break;
                    }
                }

                $sortOrder++;

                ProductionPlanningLine::create([
                    'session_id' => $session->id,
                    'gci_part_id' => $part->id,
                    'machine_id' => $machineId,
                    'process_name' => $processName,
                    'stock_fg_lg' => $fgStockLg[$part->id] ?? 0,
                    'stock_fg_gci' => $fgStockGci[$part->id] ?? 0,
                    'sort_order' => $sortOrder,
                ]);
            }

            DB::commit();

            return redirect()->route('production.planning.index', ['date' => Carbon::parse($session->plan_date)->format('Y-m-d')])
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
        $allowed = ['machine_id', 'process_name', 'production_sequence', 'plan_qty', 'shift', 'remark', 'sort_order'];

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

        $session = ProductionPlanningSession::findOrFail($request->session_id);
        $fgStockLg = $this->getFgStockLg(Carbon::parse($session->plan_date));
        $fgStockGci = $this->getFgStockGci();

        // Get machine/process from BOM
        $part = GciPart::with('bom.items')->find($request->gci_part_id);
        $machineId = null;
        $processName = null;
        if ($part && $part->bom) {
            foreach ($part->bom->items as $bomItem) {
                if (!empty($bomItem->machine_id))
                    $machineId = $bomItem->machine_id;
                if (!empty($bomItem->process_name))
                    $processName = $bomItem->process_name;
                if ($machineId && $processName)
                    break;
            }
        }

        $line = ProductionPlanningLine::create([
            'session_id' => $request->session_id,
            'gci_part_id' => $request->gci_part_id,
            'machine_id' => $machineId,
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
     * Generate WO from planning session
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
            $planDateStr = Carbon::parse($session->plan_date)->format('Y-m-d');
            $woPrefix = 'WO-' . now()->format('ymd');

            // Pre-fetch the last WO sequence for today to avoid stale reads in loop
            $lastOrder = ProductionOrder::where('production_order_number', 'like', $woPrefix . '%')
                ->orderBy('production_order_number', 'desc')
                ->first();
            $woSeq = $lastOrder ? intval(substr($lastOrder->production_order_number, -4)) : 0;

            foreach ($lines as $line) {
                // Check if WO already exists for this planning line
                $existingWo = ProductionOrder::where('planning_line_id', $line->id)->first();
                if ($existingWo)
                    continue;

                // Skip lines with missing gciPart relation
                if (!$line->gciPart) {
                    \Illuminate\Support\Facades\Log::warning("Planning line {$line->id} has invalid gci_part_id {$line->gci_part_id}, skipping.");
                    continue;
                }

                // Generate production order number (increment in memory)
                $woSeq++;
                $woNumber = $woPrefix . '-' . str_pad($woSeq, 4, '0', STR_PAD_LEFT);

                $order = ProductionOrder::create([
                    'production_order_number' => $woNumber,
                    'transaction_no' => ProductionOrder::generateTransactionNo($planDateStr),
                    'gci_part_id' => $line->gci_part_id,
                    'machine_id' => $line->machine_id,
                    'process_name' => $line->process_name,
                    'planning_line_id' => $line->id,
                    'plan_date' => $session->plan_date,
                    'qty_planned' => $line->plan_qty,
                    'shift' => $line->shift,
                    'production_sequence' => $line->production_sequence,
                    'status' => 'planned',
                    'workflow_stage' => 'planned',
                    'qty_actual' => 0,
                    'qty_rejected' => 0,
                    'created_by' => auth()->id(),
                ]);

                // Auto-link arrivals (SO) — skip if no links found
                try {
                    $arrivalIds = $this->findLinkedArrivalIds($line->gci_part_id);
                    if (!empty($arrivalIds)) {
                        $order->arrivals()->sync($arrivalIds);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to link arrivals for WO {$woNumber}: " . $e->getMessage());
                }

                $generated++;
            }

            if ($generated === 0) {
                DB::rollBack();
                return back()->with('error', 'Tidak ada WO yang bisa digenerate. Semua line sudah punya WO atau data tidak valid.');
            }

            $session->update(['status' => 'confirmed', 'confirmed_by' => auth()->id(), 'confirmed_at' => now()]);

            DB::commit();

            return redirect()->route('production.planning.index', ['date' => Carbon::parse($session->plan_date)->format('Y-m-d')])
                ->with('success', "Berhasil generate {$generated} Work Order (WO)");
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Mass WO Generation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->route('production.planning.index', ['date' => Carbon::parse($session->plan_date)->format('Y-m-d')])
                ->with('error', 'Gagal generate WO: ' . $e->getMessage());
        }
    }

    /**
     * Generate WO for a single planning line
     */
    public function generateMoWoLine(Request $request)
    {
        $request->validate([
            'line_id' => 'required|exists:production_planning_lines,id',
        ]);

        $line = ProductionPlanningLine::with(['gciPart', 'session'])->findOrFail($request->line_id);
        $session = $line->session;

        if (!$line->plan_qty || $line->plan_qty <= 0) {
            return back()->with('error', 'Plan qty harus diisi terlebih dahulu.');
        }

        if (!$line->production_sequence) {
            return back()->with('error', 'Production sequence harus diisi terlebih dahulu.');
        }

        $existingWo = ProductionOrder::where('planning_line_id', $line->id)->first();
        if ($existingWo) {
            return back()->with('error', "WO sudah ada untuk part ini: {$existingWo->production_order_number}");
        }

        if (!$line->gciPart) {
            return redirect()->route('production.planning.index', ['date' => Carbon::parse($session->plan_date)->format('Y-m-d')])
                ->with('error', 'Data part tidak valid untuk planning line ini.');
        }

        DB::beginTransaction();
        try {
            $prefix = 'WO-' . now()->format('ymd');
            $lastOrder = ProductionOrder::where('production_order_number', 'like', $prefix . '%')
                ->orderBy('production_order_number', 'desc')
                ->first();
            $seq = $lastOrder ? intval(substr($lastOrder->production_order_number, -4)) + 1 : 1;
            $woNumber = $prefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $planDateStr = Carbon::parse($session->plan_date)->format('Y-m-d');

            $order = ProductionOrder::create([
                'production_order_number' => $woNumber,
                'transaction_no' => ProductionOrder::generateTransactionNo($planDateStr),
                'gci_part_id' => $line->gci_part_id,
                'machine_name' => $line->machine_name,
                'process_name' => $line->process_name,
                'planning_line_id' => $line->id,
                'plan_date' => $session->plan_date,
                'qty_planned' => $line->plan_qty,
                'shift' => $line->shift,
                'production_sequence' => $line->production_sequence,
                'status' => 'planned',
                'workflow_stage' => 'planned',
                'qty_actual' => 0,
                'qty_rejected' => 0,
                'created_by' => auth()->id(),
            ]);

            // Auto-link arrivals (SO)
            try {
                $arrivalIds = $this->findLinkedArrivalIds($line->gci_part_id);
                if (!empty($arrivalIds)) {
                    $order->arrivals()->sync($arrivalIds);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to link arrivals for WO {$woNumber}: " . $e->getMessage());
            }

            // Check if all lines with qty+seq now have WO → auto-confirm session
            $pendingLines = ProductionPlanningLine::where('session_id', $session->id)
                ->where('plan_qty', '>', 0)
                ->whereNotNull('production_sequence')
                ->whereDoesntHave('productionOrders')
                ->count();

            if ($pendingLines === 0 && $session->status !== 'confirmed') {
                $session->update(['status' => 'confirmed', 'confirmed_by' => auth()->id(), 'confirmed_at' => now()]);
            }

            DB::commit();

            return redirect()->route('production.planning.index', ['date' => Carbon::parse($session->plan_date)->format('Y-m-d')])
                ->with('success', "WO {$woNumber} berhasil dibuat untuk {$line->gciPart->part_no}");
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Single WO Generation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->route('production.planning.index', ['date' => Carbon::parse($session->plan_date)->format('Y-m-d')])
                ->with('error', 'Gagal generate WO: ' . $e->getMessage());
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

        $planDate = \Carbon\Carbon::parse($session->plan_date);
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
     * Get FG Stock LG (from StockAtCustomer based on plan date)
     */
    private function getFgStockLg(Carbon $date): array
    {
        $period = $date->format('Y-m');
        $dayColumn = 'day_' . (int) $date->format('j');

        $stocks = StockAtCustomer::where('period', $period)->get();

        $result = [];
        foreach ($stocks as $stock) {
            if (!isset($result[$stock->gci_part_id])) {
                $result[$stock->gci_part_id] = 0;
            }
            $result[$stock->gci_part_id] += (float) ($stock->{$dayColumn} ?? 0);
        }

        return $result;
    }

    /**
     * Get FG Stock GCI (from Inventory via parts table)
     */
    private function getFgStockGci(): array
    {
        return \App\Models\Inventory::join('parts', 'inventories.part_id', '=', 'parts.id')
            ->whereNotNull('parts.gci_part_id')
            ->select('parts.gci_part_id', \Illuminate\Support\Facades\DB::raw('SUM(inventories.on_hand) as total_on_hand'))
            ->groupBy('parts.gci_part_id')
            ->pluck('total_on_hand', 'parts.gci_part_id')
            ->toArray();
    }

    /**
     * Find linked Arrival IDs for a GCI part (for WO <-> SO traceability)
     */
    private function findLinkedArrivalIds(int $gciPartId): array
    {
        $bom = Bom::where('part_id', $gciPartId)->first();
        if (!$bom) {
            return [];
        }

        $componentPartIds = BomItem::where('bom_id', $bom->id)
            ->whereNotNull('incoming_part_id')
            ->pluck('incoming_part_id')
            ->unique();

        if ($componentPartIds->isEmpty()) {
            $rmPartIds = \App\Models\Part::where('gci_part_id', $gciPartId)->pluck('id');
            if ($rmPartIds->isEmpty()) {
                return [];
            }
            $arrivalIds = \App\Models\ArrivalItem::whereIn('part_id', $rmPartIds)
                ->pluck('arrival_id')->unique();
        } else {
            $arrivalIds = \App\Models\ArrivalItem::whereIn('part_id', $componentPartIds)
                ->pluck('arrival_id')->unique();
        }

        return \App\Models\Arrival::whereIn('id', $arrivalIds)
            ->whereNotNull('transaction_no')
            ->orderBy('created_at', 'asc') // FIFO
            ->limit(20)
            ->pluck('id')
            ->toArray();
    }
}
