<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\ProductionInspection;
use App\Models\GciPart;
use App\Models\GciInventory;
use App\Models\Bom;
use App\Models\Machine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductionOrderController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $month = $request->query('month');
        $status = strtolower(trim((string) $request->query('status', '')));
        $q = trim((string) $request->query('q', ''));
        $gciPartId = (int) $request->query('gci_part_id', 0);

        if ($month && preg_match('/^\\d{4}-\\d{2}$/', (string) $month)) {
            try {
                $from = now()->parse($month . '-01')->startOfMonth();
                $to = $from->copy()->endOfMonth();
                $dateFrom = $dateFrom ?: $from->toDateString();
                $dateTo = $dateTo ?: $to->toDateString();
            } catch (\Throwable) {
            }
        }

        $query = ProductionOrder::query()
            ->with(['part', 'dailyPlanCell.row'])
            ->when($gciPartId > 0, fn($qr) => $qr->where('gci_part_id', $gciPartId))
            ->when($status !== '', fn($qr) => $qr->where('status', $status))
            ->when($dateFrom || $dateTo, function ($qr) use ($dateFrom, $dateTo) {
                $from = $dateFrom ? $dateFrom : '1900-01-01';
                $to = $dateTo ? $dateTo : '2999-12-31';
                $qr->whereBetween('plan_date', [$from, $to]);
            })
            ->when($q !== '', function ($qr) use ($q) {
                $s = strtoupper($q);
                $qr->where('production_order_number', 'like', '%' . $s . '%')
                    ->orWhereHas('part', function ($qp) use ($s) {
                        $qp->where('part_no', 'like', '%' . $s . '%')
                            ->orWhere('part_name', 'like', '%' . $s . '%')
                            ->orWhere('model', 'like', '%' . $s . '%');
                    });
            })
            ->latest();

        $orders = $query->paginate(20)->withQueryString();

        return view('production.orders.index', compact('orders', 'dateFrom', 'dateTo', 'month', 'status', 'q', 'gciPartId'));
    }

    public function create()
    {
        $machines = Machine::where('is_active', true)->orderBy('name')->get();
        return view('production.orders.create', compact('machines'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gci_part_id' => 'required|exists:gci_parts,id',
            'process_name' => 'nullable|string|max:255',
            'machine_id' => 'nullable|exists:machines,id',
            'die_name' => 'nullable|string|max:255',
            'plan_date' => 'required|date',
            'qty_planned' => 'required|numeric|min:1',
            'production_order_number' => 'required|unique:production_orders,production_order_number',
            'arrival_ids' => 'nullable|array',
            'arrival_ids.*' => 'integer|exists:arrivals,id',
        ]);

        $order = ProductionOrder::create([
            'production_order_number' => $validated['production_order_number'],
            'gci_part_id' => $validated['gci_part_id'],
            'process_name' => isset($validated['process_name']) && trim((string) $validated['process_name']) !== '' ? trim((string) $validated['process_name']) : null,
            'machine_id' => $validated['machine_id'] ?? null,
            'die_name' => isset($validated['die_name']) && trim((string) $validated['die_name']) !== '' ? trim((string) $validated['die_name']) : null,
            'plan_date' => $validated['plan_date'],
            'qty_planned' => $validated['qty_planned'],
            'status' => 'planned',
            'workflow_stage' => 'planned',
            'qty_actual' => 0,
            'created_by' => Auth::id(),
        ]);

        // Auto-generate WO transaction number on creation
        if (empty($order->transaction_no)) {
            $order->transaction_no = ProductionOrder::generateTransactionNo($validated['plan_date']);
            $order->save();
        }

        // Save traceability: WO ↔ SO
        if (!empty($validated['arrival_ids'])) {
            $order->arrivals()->sync($validated['arrival_ids']);
        }

        return redirect()->route('production.orders.show', $order);
    }

    public function show(Request $request, ProductionOrder $order)
    {
        $order->load([
            'part',
            'machine',
            'inspections.inspector',
            'creator',
            'mrpRun',
            'dailyPlanCell.row.plan',
            'arrivals',
        ]);

        if ($request->ajax()) {
            return view('production.orders.partials.detail_content', compact('order'));
        }

        return view('production.orders.show', compact('order'));
    }

    public function checkMaterial(ProductionOrder $order)
    {
        // Logic to check BOM vs Inventory
        $part = $order->part;
        $bom = Bom::where('part_id', $part->id)->latest()->first(); // Assuming latest BOM

        if (!$bom) {
            return back()->with('error', 'No BOM found for this part.');
        }

        $bomItems = $bom->items;
        $missingMaterials = [];
        $isAvailable = true;

        foreach ($bomItems as $item) {
            $requiredQty = $item->usage_qty * $order->qty_planned;
            // Check inventory (GciInventory)
            $currentStock = GciInventory::where('gci_part_id', $item->component_part_id)->sum('on_hand');

            if ($currentStock < $requiredQty) {
                $isAvailable = false;
                $missingMaterials[] = [
                    'part' => $item->componentPart?->part_no ?? 'Unknown',
                    'required' => $requiredQty,
                    'available' => $currentStock,
                ];
            }
        }

        if ($isAvailable) {
            if ($order->status === 'planned') {
                return back()->with('error', 'Kanban belum di-release. Release dulu sebelum check material.');
            }
            if (in_array($order->status, ['kanban_released', 'material_hold', 'resource_hold'], true)) {
                // Require machine/die info for flow parity (best-effort).
                if (!$order->process_name || !$order->machine_id) {
                    $order->update(['status' => 'resource_hold', 'workflow_stage' => 'resource_check']);
                    return back()->with('error', 'Machine/Process belum diisi. Isi dulu lalu ulangi check.');
                }
                $order->update(['status' => 'released', 'workflow_stage' => 'ready']);
            }
            return back()->with('success', 'Material check passed! Order released.');
        } else {
            $order->update(['status' => 'material_hold', 'workflow_stage' => 'material_check']);
            return back()->with('error', 'Material check failed.')->with('missing_materials', $missingMaterials);
        }
    }

    public function releaseKanban(ProductionOrder $order)
    {
        if ($order->status !== 'planned') {
            return back()->with('error', 'Only planned orders can be released to Kanban.');
        }

        $order->update([
            'status' => 'kanban_released',
            'workflow_stage' => 'kanban_released',
            'released_at' => now(),
            'released_by' => Auth::id(),
        ]);

        return back()->with('success', 'Kanban released.');
    }

    // Workflow Transitions

    public function startProduction(ProductionOrder $order)
    {
        if ($order->status !== 'released') {
            return back()->with('error', 'Order must be Released to start.');
        }

        $order->update([
            'status' => 'in_production',
            'workflow_stage' => 'first_article_inspection',
            'start_time' => now(),
        ]);

        // Initial transition to First Article Inspection
        $this->createInspection($order, 'first_article');

        return back()->with('success', 'Production started.');
    }

    public function createInspection(ProductionOrder $order, $type)
    {
        ProductionInspection::create([
            'production_order_id' => $order->id,
            'type' => $type,
            'status' => 'pending',
        ]);
    }

    public function finishProduction(ProductionOrder $order)
    {
        // Move to Final Inspection stage (inventory update happens on Kanban Update after final pass).
        if ($order->status !== 'in_production') {
            return back()->with('error', 'Order must be In Production to finish production.');
        }

        $order->update([
            'workflow_stage' => 'final_inspection',
            'end_time' => now(),
            // Keep qty_actual for Kanban Update; default to planned for convenience.
            'qty_actual' => $order->qty_actual > 0 ? $order->qty_actual : $order->qty_planned,
        ]);

        // Ensure final inspection exists
        if (!$order->inspections()->where('type', 'final')->exists()) {
            $this->createInspection($order, 'final');
        }

        return back()->with('success', 'Production finished. Please complete Final Inspection, then Kanban Update.');
    }

    public function kanbanUpdate(Request $request, ProductionOrder $order)
    {
        $validated = $request->validate([
            'qty_good' => ['required', 'numeric', 'min:0'],
            'qty_ng' => ['nullable', 'numeric', 'min:0'],
        ]);

        $final = $order->inspections()->where('type', 'final')->latest('id')->first();
        if (!$final || $final->status !== 'pass') {
            return back()->with('error', 'Final inspection must be PASS before Kanban Update.');
        }

        if (!in_array($order->workflow_stage, ['final_inspection', 'kanban_update', 'stock_update'], true)) {
            return back()->with('error', 'Order is not ready for Kanban Update.');
        }

        $qtyGood = (float) $validated['qty_good'];
        $qtyNg = (float) ($validated['qty_ng'] ?? 0);

        DB::transaction(function () use ($order, $qtyGood, $qtyNg) {
            $order->update([
                'qty_actual' => $qtyGood,
                'qty_ng' => $qtyNg,
                'kanban_updated_at' => now(),
                'kanban_updated_by' => Auth::id(),
                'workflow_stage' => 'stock_update',
            ]);

            // Update Inventory
            $fgInv = \App\Models\FgInventory::firstOrCreate(
                ['gci_part_id' => $order->gci_part_id],
                ['qty_on_hand' => 0]
            );
            $fgInv->increment('qty_on_hand', $qtyGood);

            // Decrement Components (Backflush) based on BOM net_required, skip free_issue.
            $bom = Bom::where('part_id', $order->gci_part_id)->latest()->first();
            if ($bom) {
                foreach ($bom->items as $item) {
                    $mob = strtolower((string) ($item->make_or_buy ?? 'buy'));
                    if ($mob === 'free_issue') {
                        continue;
                    }
                    $consumedQty = (float) ($item->net_required ?? $item->usage_qty ?? 0) * $qtyGood;
                    if ($consumedQty <= 0) {
                        continue;
                    }

                    $compInv = GciInventory::firstOrCreate(
                        ['gci_part_id' => $item->component_part_id],
                        ['on_hand' => 0, 'on_order' => 0, 'as_of_date' => now()->toDateString()]
                    );
                    $newOnHand = (float) ($compInv->on_hand ?? 0) - $consumedQty;
                    $compInv->update([
                        'on_hand' => $newOnHand,
                        'as_of_date' => now()->toDateString(),
                    ]);
                }
            }

            $order->update([
                'status' => 'completed',
                'workflow_stage' => 'finished',
            ]);
        });

        return back()->with('success', 'Kanban updated and inventory posted.');
    }
}
