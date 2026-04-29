<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\ProductionOrder;
use App\Models\ProductionInspection;
use Illuminate\Http\Request;

class StartProductionController extends Controller
{
    /**
     * Temporary management decision: production may start WO without waiting
     * for WH RM supply while operators are training / WH discipline is being fixed.
     * Set to false to restore the normal material gate.
     */
    private const TEMP_ALLOW_START_WITHOUT_WH_SUPPLY = true;

    private function bypassMaterialGateForWoStart(): bool
    {
        return self::TEMP_ALLOW_START_WITHOUT_WH_SUPPLY;
    }

    private function buildStartReadiness(ProductionOrder $order): array
    {
        $requestLines = collect($order->material_request_lines ?? []);
        $shortageLines = $requestLines
            ->filter(fn ($line) => (float) ($line['shortage_qty'] ?? 0) > 0)
            ->values();

        $bom = Bom::activeVersion($order->gci_part_id, $order->plan_date);
        $bomLineCount = 0;
        $processMatched = false;
        $machineMatched = false;
        $recommendedMachines = collect();

        if ($bom) {
            $bom->loadMissing('items.machine');
            $items = collect($bom->items ?? []);
            $bomLineCount = $items->count();

            $normalizedOrderProcess = strtoupper(trim((string) $order->process_name));
            $processItems = $items->filter(function ($item) use ($normalizedOrderProcess) {
                return strtoupper(trim((string) ($item->process_name ?? ''))) === $normalizedOrderProcess;
            })->values();

            $processMatched = $normalizedOrderProcess === '' ? $items->isNotEmpty() : $processItems->isNotEmpty();

            $recommendedMachines = ($processItems->isNotEmpty() ? $processItems : $items)
                ->map(fn ($item) => trim((string) ($item->machine?->name ?? '')))
                ->filter()
                ->unique()
                ->values();

            $machineMatched = $order->machine_id
                ? ($processItems->isNotEmpty()
                    ? $processItems->contains(fn ($item) => (int) ($item->machine_id ?? 0) === (int) $order->machine_id)
                    : $items->contains(fn ($item) => (int) ($item->machine_id ?? 0) === (int) $order->machine_id))
                : false;
        }

        return [
            'has_bom' => (bool) $bom,
            'bom_line_count' => $bomLineCount,
            'process_matched' => $processMatched,
            'machine_matched' => $machineMatched,
            'recommended_machines' => $recommendedMachines->all(),
            'material_line_count' => $requestLines->count(),
            'shortage_count' => $shortageLines->count(),
            'first_shortage_part_no' => (string) ($shortageLines->first()['component_part_no'] ?? ''),
            'has_material_request' => $requestLines->isNotEmpty(),
            'material_issued' => !is_null($order->material_issued_at),
            'material_handed_over' => !is_null($order->material_handed_over_at),
            'bypass_active' => $this->bypassMaterialGateForWoStart(),
        ];
    }

    public function index(Request $request)
    {
        $search = $request->query('search', '');
        $allowedStatuses = $this->bypassMaterialGateForWoStart()
            ? ['released', 'kanban_released', 'material_hold']
            : ['released'];
        
        $query = ProductionOrder::query()
            ->with(['part', 'machine'])
            ->whereIn('status', $allowedStatuses)
            ->when($search !== '', function($q) use ($search) {
                $q->where('production_order_number', 'like', "%{$search}%")
                    ->orWhereHas('part', function($qp) use ($search) {
                        $qp->where('part_no', 'like', "%{$search}%")
                            ->orWhere('part_name', 'like', "%{$search}%");
                    });
            })
            ->latest();
        
        $orders = $query->paginate(20)->withQueryString();
        $orders->getCollection()->transform(function (ProductionOrder $order) {
            $order->start_readiness = $this->buildStartReadiness($order);
            return $order;
        });
        
        $pageOrders = collect($orders->items());
        $summary = [
            'total' => $pageOrders->count(),
            'shortage' => $pageOrders->filter(fn ($order) => (int) data_get($order, 'start_readiness.shortage_count', 0) > 0)->count(),
            'missing_bom' => $pageOrders->filter(fn ($order) => !data_get($order, 'start_readiness.has_bom', false))->count(),
            'machine_mismatch' => $pageOrders->filter(function ($order) {
                $hasBom = (bool) data_get($order, 'start_readiness.has_bom', false);
                $machineMatched = (bool) data_get($order, 'start_readiness.machine_matched', false);
                return $hasBom && !$machineMatched;
            })->count(),
        ];
        
        return view('production.start-production.index', compact('orders', 'search', 'summary'));
    }
    
    public function show(ProductionOrder $order)
    {
        return redirect()->route('production.orders.show', $order);
    }
    
    public function start(ProductionOrder $order)
    {
        $allowedStatuses = $this->bypassMaterialGateForWoStart()
            ? ['released', 'kanban_released', 'material_hold']
            : ['released'];

        if (!in_array($order->status, $allowedStatuses, true)) {
            return back()->with('error', 'Order must be Released to start production.');
        }

        $order->update([
            'status' => 'in_production',
            'workflow_stage' => 'first_article_inspection',
            'start_time' => now(),
        ]);
        
        // Create First Article Inspection
        ProductionInspection::create([
            'production_order_id' => $order->id,
            'type' => 'first_article',
            'status' => 'pending',
        ]);

        return redirect()
            ->route('production.qc-inspection.index')
            ->with('success', 'Production started. Please complete First Article Inspection.');
    }
}
