<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionInspection;
use App\Models\ProductionOrder;
use App\Models\Bom;
use App\Models\GciInventory;
use App\Models\FgInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinalInspectionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        
        $query = ProductionInspection::query()
            ->with(['productionOrder.part', 'inspector'])
            ->where('type', 'final')
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->latest();
        
        $inspections = $query->paginate(20)->withQueryString();
        
        return view('production.final-inspection.index', compact('inspections', 'status'));
    }
    
    public function show(ProductionInspection $inspection)
    {
        $inspection->load(['productionOrder.part', 'inspector']);
        return view('production.final-inspection.show', compact('inspection'));
    }
    
    public function update(Request $request, ProductionInspection $inspection)
    {
        $validated = $request->validate([
            'status' => 'required|in:pass,fail,pending',
            'notes' => 'nullable|string',
            'inspected_qty' => 'nullable|numeric|min:0',
        ]);
        
        $inspection->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'inspected_qty' => $validated['inspected_qty'] ?? null,
            'inspected_at' => now(),
            'inspected_by' => Auth::id(),
        ]);
        
        if ($validated['status'] === 'pass') {
            return back()->with('success', 'Final Inspection passed. Ready for Kanban Update & Inventory posting.');
        }
        
        if ($validated['status'] === 'fail') {
            return back()->with('error', 'Final Inspection failed. Please review production.');
        }
        
        return back()->with('success', 'Inspection updated successfully.');
    }
    
    public function kanbanUpdate(Request $request, ProductionOrder $order)
    {
        $validated = $request->validate([
            'qty_good' => 'required|numeric|min:0',
            'qty_ng' => 'nullable|numeric|min:0',
        ]);
        
        $finalInspection = $order->inspections()->where('type', 'final')->latest()->first();
        
        if (!$finalInspection || $finalInspection->status !== 'pass') {
            return back()->with('error', 'Final Inspection must be PASS before Kanban Update.');
        }
        
        if (!in_array($order->workflow_stage, ['final_inspection', 'kanban_update', 'stock_update'], true)) {
            return back()->with('error', 'Order is not ready for Kanban Update.');
        }
        
        $qtyGood = (float) $validated['qty_good'];
        $qtyNg = (float) ($validated['qty_ng'] ?? 0);
        
        DB::transaction(function () use ($order, $qtyGood, $qtyNg) {
            // Update order
            $order->update([
                'qty_actual' => $qtyGood,
                'qty_ng' => $qtyNg,
                'kanban_updated_at' => now(),
                'kanban_updated_by' => Auth::id(),
                'workflow_stage' => 'stock_update',
            ]);
            
            // Update FG Inventory
            $fgInv = FgInventory::firstOrCreate(
                ['gci_part_id' => $order->gci_part_id],
                ['qty_on_hand' => 0]
            );
            $fgInv->increment('qty_on_hand', $qtyGood);
            
            // Backflush components from BOM
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
            
            // Complete the order
            $order->update([
                'status' => 'completed',
                'workflow_stage' => 'finished',
            ]);
        });
        
        return back()->with('success', 'Kanban updated and inventory posted successfully.');
    }
}
