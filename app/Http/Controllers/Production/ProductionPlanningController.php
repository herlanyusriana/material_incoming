<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionPlanningSession;
use App\Models\ProductionPlanningLine;
use App\Models\ProductionOrder;
use App\Models\GciPart;
use App\Models\GciInventory;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Machine;
use App\Models\OutgoingDailyPlan;
use App\Models\OutgoingDailyPlanCell;
use App\Models\OutgoingDeliveryPlanningLine;
use App\Services\ProductionMaterialRequestService;
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
        $sourceMode = strtolower((string) $request->get('source_mode', 'delivery'));
        if (!in_array($sourceMode, ['delivery', 'raw'], true)) {
            $sourceMode = 'delivery';
        }

        // Get or create session
        $session = ProductionPlanningSession::where('plan_date', $planDate->format('Y-m-d'))->first();

        // Get planning lines grouped by machine (from BOM)
        $machineGroups = [];
        $processLoadRows = collect();
        $planningLines = collect();
        if ($session) {
            $allLines = ProductionPlanningLine::where('session_id', $session->id)
                ->with(['gciPart.bom.items', 'productionOrders', 'machine'])
                ->orderByRaw('CASE WHEN machine_id IS NULL THEN 1 ELSE 0 END')
                ->orderBy('machine_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
            $planningLines = $allLines
                ->sort(function ($a, $b) {
                    $aMachineNull = $a->machine_id === null ? 1 : 0;
                    $bMachineNull = $b->machine_id === null ? 1 : 0;
                    if ($aMachineNull !== $bMachineNull) {
                        return $aMachineNull <=> $bMachineNull;
                    }

                    $aMachineId = (int) ($a->machine_id ?? PHP_INT_MAX);
                    $bMachineId = (int) ($b->machine_id ?? PHP_INT_MAX);
                    if ($aMachineId !== $bMachineId) {
                        return $aMachineId <=> $bMachineId;
                    }

                    $aSortOrder = (int) ($a->sort_order ?? PHP_INT_MAX);
                    $bSortOrder = (int) ($b->sort_order ?? PHP_INT_MAX);
                    if ($aSortOrder !== $bSortOrder) {
                        return $aSortOrder <=> $bSortOrder;
                    }

                    $aPartName = (string) ($a->gciPart?->part_name ?? '');
                    $bPartName = (string) ($b->gciPart?->part_name ?? '');
                    if ($aPartName !== $bPartName) {
                        return strcasecmp($aPartName, $bPartName);
                    }

                    $aModel = (string) ($a->gciPart?->model ?? '');
                    $bModel = (string) ($b->gciPart?->model ?? '');
                    if ($aModel !== $bModel) {
                        return strcasecmp($aModel, $bModel);
                    }

                    return (int) $a->id <=> (int) $b->id;
                })
                ->values();

            // Group by machine_id
            foreach ($planningLines as $line) {
                $machineKey = $line->machine_id ?: 'unassigned';
                $machineName = $line->machine?->name ?: 'Unassigned';
                if (!isset($machineGroups[$machineKey])) {
                    $machineGroups[$machineKey] = [
                        'machine_id' => $line->machine_id,
                        'machine_name' => $machineName,
                        'process_name' => $line->process_name ?: '-',
                        'lines' => [],
                        'subtotal_fg_gci' => 0,
                        'subtotal_delivery_requirement_qty' => 0,
                        'subtotal_plan_qty' => 0,
                    ];
                }
                $machineGroups[$machineKey]['lines'][] = $line;
                $machineGroups[$machineKey]['subtotal_fg_gci'] += (float) $line->stock_fg_gci;
                $machineGroups[$machineKey]['subtotal_delivery_requirement_qty'] += (float) $line->delivery_requirement_qty;
                $machineGroups[$machineKey]['subtotal_plan_qty'] += (float) $line->plan_qty;
            }

            $processLoadRows = $this->buildProcessLoadRows($planningLines, $planDate);
        }

        // Get daily planning data from outgoing
        $dailyPlanData = $this->getDailyPlanningData($planDate, $sourceMode);

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
        $grandTotalFgGci = (float) $planningLines->sum(fn($line) => (float) $line->stock_fg_gci);
        $grandTotalDeliveryRequirementQty = (float) $planningLines->sum(fn($line) => (float) $line->delivery_requirement_qty);
        $grandTotalPlanQty = (float) $planningLines->sum(fn($line) => (float) $line->plan_qty);
        $totalParts = $planningLines->count();

        return view('production.planning.index', compact(
            'session',
            'machineGroups',
            'planningLines',
            'dailyPlanData',
            'fgStockGci',
            'planDate',
            'dateRange',
            'existingSessions',
            'planningDays',
            'machines',
            'grandTotalFgGci',
            'grandTotalDeliveryRequirementQty',
            'grandTotalPlanQty',
            'totalParts',
            'sourceMode',
            'processLoadRows'
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
        $sourceMode = strtolower((string) $request->input('source_mode', 'delivery'));
        if (!in_array($sourceMode, ['delivery', 'raw'], true)) {
            $sourceMode = 'delivery';
        }
        $deliveryRequirements = $this->getDailyPlanningData(Carbon::parse($session->plan_date), $sourceMode);

        $fgStockGci = $this->getFgStockGci();

        $sortOrder = ProductionPlanningLine::where('session_id', $session->id)->max('sort_order') ?? 0;

        DB::beginTransaction();
        try {
            // Get all FG GCI parts that have an active BOM
            $parts = GciPart::where('classification', 'FG')
                ->whereHas('bom', function ($q) {
                    $q->where('status', 'active');
                })
                ->with(['bom.items.machine'])
                ->get();

            $parts = $parts
                ->map(function ($part) {
                    $machineName = 'ZZZ_UNASSIGNED';
                    $processName = null;
                    $machineId = null;

                    $activeBom = $part->bom;
                    if ($activeBom) {
                        foreach ($activeBom->items as $bomItem) {
                            if (!$machineId && !empty($bomItem->machine_id)) {
                                $machineId = $bomItem->machine_id;
                                $machineName = optional($bomItem->machine)->name ?? $machineName;
                            }
                            if (!$processName && !empty($bomItem->process_name)) {
                                $processName = $bomItem->process_name;
                            }
                            if ($machineId && $processName) {
                                break;
                            }
                        }
                    }

                    $part->resolved_machine_id = $machineId;
                    $part->resolved_machine_name = $machineName;
                    $part->resolved_process_name = $processName;

                    return $part;
                })
                ->sortBy([
                    fn ($part) => $part->resolved_machine_name ?? 'ZZZ_UNASSIGNED',
                    fn ($part) => $part->part_name ?? '',
                    fn ($part) => $part->part_no ?? '',
                    fn ($part) => $part->model ?? '',
                    fn ($part) => (int) $part->id,
                ])
                ->values();

            foreach ($parts as $part) {
                // Check if line already exists for this part in session
                $exists = ProductionPlanningLine::where('session_id', $session->id)
                    ->where('gci_part_id', $part->id)
                    ->exists();

                if ($exists)
                    continue;

                // Get machine_id and process_name from BOM
                $machineId = $part->resolved_machine_id;
                $processName = $part->resolved_process_name;

                $sortOrder++;

                $recommendedPlanQty = $this->calculateRecommendedPlanQty(
                    (float) ($deliveryRequirements[$part->id]['total_qty'] ?? 0),
                    (float) ($fgStockGci[$part->id] ?? 0)
                );

                ProductionPlanningLine::create([
                    'session_id' => $session->id,
                    'gci_part_id' => $part->id,
                    'machine_id' => $machineId,
                    'process_name' => $processName,
                    'stock_fg_gci' => $fgStockGci[$part->id] ?? 0,
                    'delivery_requirement_qty' => (float) ($deliveryRequirements[$part->id]['total_qty'] ?? 0),
                    'delivery_requirement_date_from' => $session->plan_date,
                    'delivery_requirement_date_to' => $session->plan_date,
                    'plan_qty' => $recommendedPlanQty,
                    'shift_1_qty' => 0,
                    'shift_2_qty' => 0,
                    'shift_3_qty' => 0,
                    'sort_order' => $sortOrder,
                ]);
            }

            DB::commit();

            return redirect()->route('production.planning.index', [
                'date' => Carbon::parse($session->plan_date)->format('Y-m-d'),
                'source_mode' => $sourceMode,
            ])
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
        $allowed = ['machine_id', 'process_name', 'production_sequence', 'plan_qty', 'shift', 'shift_1_qty', 'shift_2_qty', 'shift_3_qty', 'remark', 'sort_order'];

        if (!in_array($field, $allowed)) {
            return response()->json(['error' => 'Invalid field'], 422);
        }

        $value = $request->value;
        if (in_array($field, ['plan_qty', 'shift_1_qty', 'shift_2_qty', 'shift_3_qty'], true)) {
            $value = (float) ($value ?: 0);
        }

        if ($field === 'plan_qty') {
            $line->update([
                'plan_qty' => $value,
            ]);
        } elseif (in_array($field, ['shift_1_qty', 'shift_2_qty', 'shift_3_qty'], true)) {
            $shift1 = $field === 'shift_1_qty' ? $value : (float) $line->shift_1_qty;
            $shift2 = $field === 'shift_2_qty' ? $value : (float) $line->shift_2_qty;
            $shift3 = $field === 'shift_3_qty' ? $value : (float) $line->shift_3_qty;

            $line->update([
                $field => $value,
                'plan_qty' => $shift1 + $shift2 + $shift3,
            ]);
        } else {
            $line->update([$field => $value]);
        }

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
        $sourceMode = strtolower((string) $request->input('source_mode', 'delivery'));
        if (!in_array($sourceMode, ['delivery', 'raw'], true)) {
            $sourceMode = 'delivery';
        }
        $deliveryRequirements = $this->getDailyPlanningData(Carbon::parse($session->plan_date), $sourceMode);
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

        $recommendedPlanQty = $this->calculateRecommendedPlanQty(
            (float) ($deliveryRequirements[$request->gci_part_id]['total_qty'] ?? 0),
            (float) ($fgStockGci[$request->gci_part_id] ?? 0)
        );

        $line = ProductionPlanningLine::create([
            'session_id' => $request->session_id,
            'gci_part_id' => $request->gci_part_id,
            'machine_id' => $machineId,
            'process_name' => $processName,
            'stock_fg_gci' => $fgStockGci[$request->gci_part_id] ?? 0,
            'delivery_requirement_qty' => (float) ($deliveryRequirements[$request->gci_part_id]['total_qty'] ?? 0),
            'delivery_requirement_date_from' => $session->plan_date,
            'delivery_requirement_date_to' => $session->plan_date,
            'plan_qty' => $recommendedPlanQty,
            'shift_1_qty' => 0,
            'shift_2_qty' => 0,
            'shift_3_qty' => 0,
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
            ->with('gciPart')
            ->get();

        if ($lines->isEmpty()) {
            return back()->with('error', 'No planning lines with production quantity found');
        }

        $lines = $lines->sortBy(function ($line) {
            return [
                (int) ($line->production_sequence ?? PHP_INT_MAX),
                (int) ($line->sort_order ?? PHP_INT_MAX),
                (int) $line->id,
            ];
        })->values();

        DB::beginTransaction();
        try {
            $generated = 0;
            $planDateStr = Carbon::parse($session->plan_date)->format('Y-m-d');
            $woPrefix = 'WO-' . now()->format('ymd');

            $lastOrder = ProductionOrder::where('production_order_number', 'like', $woPrefix . '%')
                ->orderBy('production_order_number', 'desc')
                ->first();
            $woSeq = $lastOrder ? intval(substr($lastOrder->production_order_number, -4)) : 0;

            foreach ($lines as $line) {
                if (!$line->gciPart) {
                    \Illuminate\Support\Facades\Log::warning("Planning line {$line->id} has invalid gci_part_id {$line->gci_part_id}, skipping.");
                    continue;
                }

                $existingWo = ProductionOrder::where('planning_line_id', $line->id)->first();
                if ($existingWo) {
                    continue;
                }

                $plannedQty = $this->resolveWoPlannedQty($line);
                if ($plannedQty <= 0) {
                    continue;
                }

                $woSeq++;
                $woNumber = $woPrefix . '-' . str_pad($woSeq, 4, '0', STR_PAD_LEFT);
                $sequence = $line->production_sequence ?: ($line->sort_order ?: $line->id);

                $order = ProductionOrder::create([
                    'production_order_number' => $woNumber,
                    'transaction_no' => ProductionOrder::generateTransactionNo($planDateStr),
                    'gci_part_id' => $line->gci_part_id,
                    'machine_id' => null,
                    'process_name' => null,
                    'planning_line_id' => $line->id,
                    'plan_date' => $session->plan_date,
                    'qty_planned' => $plannedQty,
                    'shift' => $this->resolveWoShiftLabel($line),
                    'production_sequence' => $sequence,
                    'status' => 'planned',
                    'workflow_stage' => 'planned',
                    'qty_actual' => 0,
                    'qty_rejected' => 0,
                    'created_by' => auth()->id(),
                ]);

                try {
                    $arrivalIds = $this->findLinkedArrivalIds($line->gci_part_id);
                    if (!empty($arrivalIds)) {
                        $order->arrivals()->sync($arrivalIds);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to link arrivals for WO {$woNumber}: " . $e->getMessage());
                }

                app(ProductionMaterialRequestService::class)->syncToOrder($order, auth()->id());

                $generated++;
            }

            if ($generated === 0) {
                DB::rollBack();
                return back()->with('error', 'Tidak ada WO yang bisa digenerate. Semua line/shift sudah punya WO atau data tidak valid.');
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
            $seq = $lastOrder ? intval(substr($lastOrder->production_order_number, -4)) : 0;
            $planDateStr = Carbon::parse($session->plan_date)->format('Y-m-d');
            $existingWo = ProductionOrder::where('planning_line_id', $line->id)->first();
            if ($existingWo) {
                DB::rollBack();
                return back()->with('error', 'Planning line ini sudah punya WO.');
            }

            $plannedQty = $this->resolveWoPlannedQty($line);
            if ($plannedQty <= 0) {
                DB::rollBack();
                return back()->with('error', 'Plan qty line ini masih kosong.');
            }

            $seq++;
            $woNumber = $prefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
            $sequence = $line->production_sequence ?: ($line->sort_order ?: $line->id);

            $order = ProductionOrder::create([
                'production_order_number' => $woNumber,
                'transaction_no' => ProductionOrder::generateTransactionNo($planDateStr),
                'gci_part_id' => $line->gci_part_id,
                'machine_id' => null,
                'process_name' => null,
                'planning_line_id' => $line->id,
                'plan_date' => $session->plan_date,
                'qty_planned' => $plannedQty,
                'shift' => $this->resolveWoShiftLabel($line),
                'production_sequence' => $sequence,
                'status' => 'planned',
                'workflow_stage' => 'planned',
                'qty_actual' => 0,
                'qty_rejected' => 0,
                'created_by' => auth()->id(),
            ]);

            try {
                $arrivalIds = $this->findLinkedArrivalIds($line->gci_part_id);
                if (!empty($arrivalIds)) {
                    $order->arrivals()->sync($arrivalIds);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to link arrivals for WO {$woNumber}: " . $e->getMessage());
            }

            app(ProductionMaterialRequestService::class)->syncToOrder($order, auth()->id());

            $pendingLines = ProductionPlanningLine::where('session_id', $session->id)
                ->where('plan_qty', '>', 0)
                ->whereDoesntHave('productionOrders')
                ->count();

            if ($pendingLines === 0 && $session->status !== 'confirmed') {
                $session->update(['status' => 'confirmed', 'confirmed_by' => auth()->id(), 'confirmed_at' => now()]);
            }

            DB::commit();

            return redirect()->route('production.planning.index', ['date' => Carbon::parse($session->plan_date)->format('Y-m-d')])
                ->with('success', "WO berhasil dibuat untuk {$line->gciPart->part_no}.");
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

        $sourceMode = strtolower((string) $request->get('source_mode', 'delivery'));
        if (!in_array($sourceMode, ['delivery', 'raw'], true)) {
            $sourceMode = 'delivery';
        }
        $dailyRequirements = $this->getDailyRequirements($planDate, $planningDays, $sourceMode);

        $result = [];
        foreach ($lines as $line) {
            $partId = $line->gci_part_id;
            $stockGci = (float) $line->stock_fg_gci;
            $planQty = (float) $line->plan_qty;

            $dailyCalc = [];
            $runningStock = $stockGci + $planQty;

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
                'total_stock' => $stockGci,
            ];
        }

        return response()->json($result);
    }

    /**
     * Pull delivery requirement from a selectable date range into a dedicated column
     */
    public function pullFromDeliveryRequirement(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:production_planning_sessions,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $session = ProductionPlanningSession::findOrFail($request->session_id);
        $dateFrom = Carbon::parse($request->date_from)->format('Y-m-d');
        $dateTo = Carbon::parse($request->date_to)->format('Y-m-d');

        // Sum delivery requirement qty per gci_part_id for selected range
        $requirements = DB::table('outgoing_daily_plan_cells as c')
            ->join('outgoing_daily_plan_rows as r', 'r.id', '=', 'c.row_id')
            ->whereBetween('c.plan_date', [$dateFrom, $dateTo])
            ->whereNotNull('r.gci_part_id')
            ->where('c.qty', '>', 0)
            ->select('r.gci_part_id', DB::raw('SUM(c.qty) as total_qty'))
            ->groupBy('r.gci_part_id')
            ->pluck('total_qty', 'r.gci_part_id');

        if ($requirements->isEmpty()) {
            return back()->with('error', "Tidak ada delivery requirement untuk range {$dateFrom} s/d {$dateTo}.");
        }

        $updated = 0;
        $lines = ProductionPlanningLine::where('session_id', $session->id)->get();

        foreach ($lines as $line) {
            $reqQty = (float) ($requirements->get($line->gci_part_id) ?? 0);
            $recommendedPlanQty = $this->calculateRecommendedPlanQty(
                $reqQty,
                (float) $line->stock_fg_gci
            );
            $line->update([
                'delivery_requirement_qty' => $reqQty,
                'delivery_requirement_date_from' => $dateFrom,
                'delivery_requirement_date_to' => $dateTo,
                'plan_qty' => $recommendedPlanQty,
                'shift_1_qty' => 0,
                'shift_2_qty' => 0,
                'shift_3_qty' => 0,
            ]);
            $updated++;
        }

        return redirect()->route('production.planning.index', ['date' => Carbon::parse($session->plan_date)->format('Y-m-d')])
            ->with('success', "Delivery requirement berhasil ditarik ke kolom terpisah untuk {$updated} planning lines (range {$dateFrom} s/d {$dateTo}).");
    }

    // ==========================================
    // PRIVATE HELPER METHODS
    // ==========================================

    /**
     * Get delivery requirement baseline (from outgoing delivery planning lines).
     * This is the agreed requirement source used by Delivery Requirement/Delivery Plan.
     */
    private function getDailyPlanningData(Carbon $planDate, string $sourceMode = 'delivery'): array
    {
        if ($sourceMode === 'raw') {
            $dateStr = $planDate->format('Y-m-d');
            $cells = OutgoingDailyPlanCell::query()
                ->with('row')
                ->whereDate('plan_date', $dateStr)
                ->where('qty', '>', 0)
                ->get();

            $data = [];
            foreach ($cells as $cell) {
                $partId = (int) ($cell->row->gci_part_id ?? 0);
                if ($partId <= 0) {
                    continue;
                }
                if (!isset($data[$partId])) {
                    $data[$partId] = ['total_qty' => 0];
                }
                $data[$partId]['total_qty'] += (int) $cell->qty;
            }
            return $data;
        }

        $dateStr = $planDate->format('Y-m-d');
        $lines = OutgoingDeliveryPlanningLine::query()
            ->whereDate('delivery_date', $dateStr)
            ->get();

        $data = [];
        foreach ($lines as $line) {
            $partId = (int) ($line->gci_part_id ?? 0);
            if ($partId <= 0) {
                continue;
            }

            $qty = 0;
            for ($t = 1; $t <= 14; $t++) {
                $qty += (int) ($line->{"trip_{$t}"} ?? 0);
            }

            if (!isset($data[$partId])) {
                $data[$partId] = [
                    'total_qty' => 0,
                ];
            }

            $data[$partId]['total_qty'] += $qty;
        }

        return $data;
    }

    /**
     * Get daily requirements for a date range
     */
    private function getDailyRequirements(Carbon $startDate, int $days, string $sourceMode = 'delivery'): array
    {
        $endDate = $startDate->copy()->addDays($days - 1);
        $requirements = [];

        if ($sourceMode === 'raw') {
            $cells = OutgoingDailyPlanCell::query()
                ->with('row')
                ->whereDate('plan_date', '>=', $startDate->format('Y-m-d'))
                ->whereDate('plan_date', '<=', $endDate->format('Y-m-d'))
                ->where('qty', '>', 0)
                ->get();

            foreach ($cells as $cell) {
                $partId = (int) ($cell->row->gci_part_id ?? 0);
                if ($partId <= 0) {
                    continue;
                }

                $dateKey = Carbon::parse($cell->plan_date)->format('Y-m-d');
                if (!isset($requirements[$partId])) {
                    $requirements[$partId] = [];
                }
                if (!isset($requirements[$partId][$dateKey])) {
                    $requirements[$partId][$dateKey] = 0;
                }
                $requirements[$partId][$dateKey] += (int) $cell->qty;
            }

            return $requirements;
        }

        $lines = OutgoingDeliveryPlanningLine::query()
            ->whereDate('delivery_date', '>=', $startDate->format('Y-m-d'))
            ->whereDate('delivery_date', '<=', $endDate->format('Y-m-d'))
            ->get();

        foreach ($lines as $line) {
            $partId = (int) ($line->gci_part_id ?? 0);
            if ($partId <= 0) {
                continue;
            }

            $dateKey = Carbon::parse($line->delivery_date)->format('Y-m-d');
            if (!isset($requirements[$partId])) {
                $requirements[$partId] = [];
            }
            if (!isset($requirements[$partId][$dateKey])) {
                $requirements[$partId][$dateKey] = 0;
            }

            $qty = 0;
            for ($t = 1; $t <= 14; $t++) {
                $qty += (int) ($line->{"trip_{$t}"} ?? 0);
            }
            $requirements[$partId][$dateKey] += $qty;
        }

        return $requirements;
    }

    /**
     * Get FG Stock GCI directly from physical stock by location.
     */
    private function getFgStockGci(): array
    {
        return \App\Models\LocationInventory::query()
            ->whereNotNull('gci_part_id')
            ->select('gci_part_id', \Illuminate\Support\Facades\DB::raw('SUM(qty_on_hand) as total_on_hand'))
            ->groupBy('gci_part_id')
            ->pluck('total_on_hand', 'gci_part_id')
            ->toArray();
    }

    private function calculateRecommendedPlanQty(float $deliveryRequirementQty, float $stockFgGci): float
    {
        return max(0, $deliveryRequirementQty - $stockFgGci);
    }

    private function resolveShiftPlanMap(ProductionPlanningLine $line): array
    {
        $shiftMap = [
            1 => round((float) ($line->shift_1_qty ?? 0), 4),
            2 => round((float) ($line->shift_2_qty ?? 0), 4),
            3 => round((float) ($line->shift_3_qty ?? 0), 4),
        ];

        $shiftMap = array_filter($shiftMap, fn($qty) => $qty > 0);
        if (!empty($shiftMap)) {
            return $shiftMap;
        }

        $legacyShift = (int) ($line->shift ?: 0);
        if ($legacyShift >= 1 && $legacyShift <= 3 && (float) $line->plan_qty > 0) {
            return [$legacyShift => round((float) $line->plan_qty, 4)];
        }

        if ((float) $line->plan_qty > 0) {
            return [1 => round((float) $line->plan_qty, 4)];
        }

        return [];
    }

    private function resolveWoPlannedQty(ProductionPlanningLine $line): float
    {
        return round((float) ($line->plan_qty ?? 0), 4);
    }

    private function resolveWoShiftLabel(ProductionPlanningLine $line): ?string
    {
        $shiftNos = array_keys($this->resolveShiftPlanMap($line));
        if (empty($shiftNos)) {
            return null;
        }

        return count($shiftNos) === 1 ? (string) reset($shiftNos) : null;
    }

    private function buildProcessLoadRows($lines, Carbon $planDate)
    {
        $rows = collect();

        foreach ($lines as $line) {
            $shiftPlanMap = $this->resolveShiftPlanMap($line);
            if (empty($shiftPlanMap)) {
                continue;
            }

            $bom = Bom::activeVersion($line->gci_part_id, $planDate);
            if (!$bom) {
                continue;
            }

            $bom->loadMissing('items.machine', 'items.wipPart');

            foreach ($bom->items as $item) {
                $processName = trim((string) ($item->process_name ?? ''));
                $machineName = $item->machine?->name ?: ($line->machine?->name ?? 'Unassigned');
                $wipPartNo = $item->wipPart?->part_no ?: ($item->wip_part_no ?: '-');
                $wipPartName = $item->wipPart?->part_name ?: ($item->wip_part_name ?: '-');

                if ($processName === '' && $machineName === 'Unassigned' && $wipPartNo === '-') {
                    continue;
                }

                foreach ($shiftPlanMap as $shiftNo => $shiftQty) {
                    $estHours = 0;
                    if ($item->machine && (float) $item->machine->cycle_time > 0) {
                        $estHours = (float) $item->machine->estimateHours($shiftQty);
                    } elseif ($line->machine && (float) $line->machine->cycle_time > 0) {
                        $estHours = (float) $line->machine->estimateHours($shiftQty);
                    }

                    $rows->push([
                        'fg_part_no' => $line->gciPart?->part_no ?? '-',
                        'fg_part_name' => $line->gciPart?->part_name ?? '-',
                        'process_name' => $processName !== '' ? $processName : ($line->process_name ?: '-'),
                        'machine_name' => $machineName,
                        'wip_part_no' => $wipPartNo,
                        'wip_part_name' => $wipPartName,
                        'shift' => $shiftNo,
                        'qty' => $shiftQty,
                        'est_hours' => $estHours,
                    ]);
                }
            }
        }

        return $rows->sortBy([
            ['machine_name', 'asc'],
            ['process_name', 'asc'],
            ['shift', 'asc'],
            ['fg_part_no', 'asc'],
        ])->values();
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
