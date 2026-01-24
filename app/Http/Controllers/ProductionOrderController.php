<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\ProductionInspection;
use App\Models\GciPart;
use App\Models\GciInventory;
use App\Models\Bom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductionOrderController extends Controller
{
    public function index()
    {
        $orders = ProductionOrder::with('part')->latest()->paginate(20);
        return view('production.orders.index', compact('orders'));
    }

    public function create()
    {
        $parts = GciPart::where('classification', 'FG')->orWhere('classification', 'WIP')->get();
        return view('production.orders.create', compact('parts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gci_part_id' => 'required|exists:gci_parts,id',
            'process_name' => 'nullable|string|max:255',
            'machine_name' => 'nullable|string|max:255',
            'plan_date' => 'required|date',
            'qty_planned' => 'required|numeric|min:1',
            'production_order_number' => 'required|unique:production_orders,production_order_number',
        ]);

        $order = ProductionOrder::create([
            'production_order_number' => $validated['production_order_number'],
            'gci_part_id' => $validated['gci_part_id'],
            'process_name' => isset($validated['process_name']) && trim((string) $validated['process_name']) !== '' ? trim((string) $validated['process_name']) : null,
            'machine_name' => isset($validated['machine_name']) && trim((string) $validated['machine_name']) !== '' ? trim((string) $validated['machine_name']) : null,
            'plan_date' => $validated['plan_date'],
            'qty_planned' => $validated['qty_planned'],
            'status' => 'planned',
            'workflow_stage' => 'created',
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('production.orders.show', $order);
    }

    public function show(Request $request, ProductionOrder $order)
    {
        $order->load(['part', 'inspections.inspector', 'creator']);
        
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
             $order->update(['status' => 'released', 'workflow_stage' => 'ready']);
             return back()->with('success', 'Material check passed! Order released.');
        } else {
             $order->update(['status' => 'material_hold', 'workflow_stage' => 'material_check']);
             return back()->with('error', 'Material check failed.')->with('missing_materials', $missingMaterials);
        }
    }

    // Workflow Transitions
    
    public function startProduction(ProductionOrder $order)
    {
        if ($order->status !== 'released') {
             return back()->with('error', 'Order must be Released to start.');
        }

        $order->update([
            'status' => 'in_production',
            'workflow_stage' => 'mass_production', // Or 'first_article_inspection' if strictly following flow
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
         // Verify all inspections passed?
         // For now, just finish
         $order->update([
             'status' => 'completed',
             'workflow_stage' => 'finished',
             'end_time' => now(),
             'qty_actual' => $order->qty_planned, // Default to planned, or prompt user
         ]);

         // TODO: TRIGGER INVENTORY UPDATE
         
         // Update Inventory
         // 1. Increment FG Inventory
         $fgInv = \App\Models\FgInventory::firstOrCreate(
             ['gci_part_id' => $order->gci_part_id],
             ['qty_on_hand' => 0]
         );
         $fgInv->increment('qty_on_hand', $order->qty_actual);

         // 2. Decrement Components (Backflush)
         // Assuming we strictly follow BOM here. In real world, we might track actual consumption.
         $bom = Bom::where('part_id', $order->gci_part_id)->latest()->first();
         if ($bom) {
             foreach ($bom->items as $item) {
                 $consumedQty = $item->usage_qty * $order->qty_actual;
                 $compInv = GciInventory::firstOrCreate(
                     ['gci_part_id' => $item->component_part_id],
                     ['on_hand' => 0]
                 );
                 $compInv->decrement('on_hand', $consumedQty);
             }
         }
         
         return back()->with('success', 'Production completed and inventory updated.');
    }
}
