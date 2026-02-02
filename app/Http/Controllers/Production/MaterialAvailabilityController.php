<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\Bom;
use App\Models\GciInventory;
use Illuminate\Http\Request;

class MaterialAvailabilityController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', '');
        $search = $request->query('search', '');
        
        $query = ProductionOrder::query()
            ->with(['part'])
            ->whereIn('status', ['kanban_released', 'material_hold', 'resource_hold'])
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->when($search !== '', function($q) use ($search) {
                $q->where('production_order_number', 'like', "%{$search}%")
                    ->orWhereHas('part', function($qp) use ($search) {
                        $qp->where('part_no', 'like', "%{$search}%")
                            ->orWhere('part_name', 'like', "%{$search}%");
                    });
            })
            ->latest();
        
        $orders = $query->paginate(20)->withQueryString();
        
        return view('production.material-availability.index', compact('orders', 'status', 'search'));
    }
    
    public function check(ProductionOrder $order)
    {
        $part = $order->part;
        $bom = Bom::where('part_id', $part->id)->latest()->first();
        
        if (!$bom) {
            return back()->with('error', 'No BOM found for this part.');
        }

        $bomItems = $bom->items()->with('componentPart')->get();
        $materials = [];
        $allAvailable = true;

        foreach ($bomItems as $item) {
            $requiredQty = $item->usage_qty * $order->qty_planned;
            $currentStock = GciInventory::where('gci_part_id', $item->component_part_id)->sum('on_hand');
            
            $isAvailable = $currentStock >= $requiredQty;
            if (!$isAvailable) {
                $allAvailable = false;
            }
            
            $materials[] = [
                'part_no' => $item->componentPart?->part_no ?? 'Unknown',
                'part_name' => $item->componentPart?->part_name ?? 'Unknown',
                'required' => $requiredQty,
                'available' => $currentStock,
                'shortage' => max(0, $requiredQty - $currentStock),
                'status' => $isAvailable ? 'available' : 'shortage',
            ];
        }
        
        if ($allAvailable) {
            // Check if machine/process is filled
            if (!$order->process_name || !$order->machine_name) {
                $order->update([
                    'status' => 'resource_hold',
                    'workflow_stage' => 'resource_check'
                ]);
                return back()
                    ->with('warning', 'Material available but Machine/Process information is missing.')
                    ->with('materials', $materials);
            }
            
            $order->update([
                'status' => 'released',
                'workflow_stage' => 'ready'
            ]);
            return back()
                ->with('success', 'Material check passed! Order released.')
                ->with('materials', $materials);
        } else {
            $order->update([
                'status' => 'material_hold',
                'workflow_stage' => 'material_check'
            ]);
            return back()
                ->with('error', 'Material shortage detected.')
                ->with('materials', $materials);
        }
    }
    
    public function show(ProductionOrder $order)
    {
        $order->load(['part']);
        $bom = Bom::where('part_id', $order->part->id)->latest()->first();
        
        $materials = [];
        if ($bom) {
            $bomItems = $bom->items()->with('componentPart')->get();
            foreach ($bomItems as $item) {
                $requiredQty = $item->usage_qty * $order->qty_planned;
                $currentStock = GciInventory::where('gci_part_id', $item->component_part_id)->sum('on_hand');
                
                $materials[] = [
                    'part_no' => $item->componentPart?->part_no ?? 'Unknown',
                    'part_name' => $item->componentPart?->part_name ?? 'Unknown',
                    'uom' => $item->componentPart?->uom ?? 'PCS',
                    'required' => $requiredQty,
                    'available' => $currentStock,
                    'shortage' => max(0, $requiredQty - $currentStock),
                    'status' => $currentStock >= $requiredQty ? 'available' : 'shortage',
                ];
            }
        }
        
        return view('production.material-availability.show', compact('order', 'materials'));
    }
}
