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

        $bomItems = $bom->items()->with(['componentPart', 'substitutes.part'])->get();
        $materials = [];
        $allAvailable = true;

        foreach ($bomItems as $item) {
            $requiredQty = $item->usage_qty * $order->qty_planned;
            
            // Get stock from primary part
            $primaryStock = GciInventory::where('gci_part_id', $item->component_part_id)->sum('on_hand');
            
            // Get stock from substitute parts
            $substituteStock = 0;
            $substituteDetails = [];
            if ($item->substitutes && $item->substitutes->count() > 0) {
                foreach ($item->substitutes as $substitute) {
                    $subStock = GciInventory::where('gci_part_id', $substitute->substitute_part_id)->sum('on_hand');
                    $substituteStock += $subStock;
                    if ($subStock > 0) {
                        $substituteDetails[] = [
                            'part_no' => $substitute->part?->part_no ?? 'Unknown',
                            'part_name' => $substitute->part?->part_name ?? 'Unknown',
                            'stock' => $subStock,
                        ];
                    }
                }
            }
            
            // Total available stock = primary + substitutes
            $totalStock = $primaryStock + $substituteStock;
            
            // Only check shortage for BUY items
            $makeOrBuy = strtoupper(trim($item->make_or_buy ?? ''));
            $isBuyItem = in_array($makeOrBuy, ['BUY', 'B', 'PURCHASE']);
            
            $isAvailable = $totalStock >= $requiredQty;
            
            // Only mark as unavailable if it's a BUY item AND stock is insufficient
            if ($isBuyItem && !$isAvailable) {
                $allAvailable = false;
            }
            
            $materials[] = [
                'part_no' => $item->componentPart?->part_no ?? 'Unknown',
                'part_name' => $item->componentPart?->part_name ?? 'Unknown',
                'make_or_buy' => $makeOrBuy ?: 'N/A',
                'required' => $requiredQty,
                'primary_stock' => $primaryStock,
                'substitute_stock' => $substituteStock,
                'available' => $totalStock,
                'shortage' => $isBuyItem ? max(0, $requiredQty - $totalStock) : 0,
                'status' => !$isBuyItem ? 'N/A' : ($isAvailable ? 'available' : 'shortage'),
                'substitutes' => $substituteDetails,
            ];
        }
        
        if ($allAvailable) {
            // Check if machine/process is filled
            if (!$order->process_name || !$order->machine_id) {
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
            $bomItems = $bom->items()->with(['componentPart', 'substitutes.part'])->get();
            foreach ($bomItems as $item) {
                $requiredQty = $item->usage_qty * $order->qty_planned;
                
                // Get stock from primary part
                $primaryStock = GciInventory::where('gci_part_id', $item->component_part_id)->sum('on_hand');
                
                // Get stock from substitute parts
                $substituteStock = 0;
                $substituteDetails = [];
                if ($item->substitutes && $item->substitutes->count() > 0) {
                    foreach ($item->substitutes as $substitute) {
                        $subStock = GciInventory::where('gci_part_id', $substitute->substitute_part_id)->sum('on_hand');
                        $substituteStock += $subStock;
                        if ($subStock > 0) {
                            $substituteDetails[] = [
                                'part_no' => $substitute->part?->part_no ?? 'Unknown',
                                'part_name' => $substitute->part?->part_name ?? 'Unknown',
                                'stock' => $subStock,
                            ];
                        }
                    }
                }
                
                // Total available stock = primary + substitutes
                $totalStock = $primaryStock + $substituteStock;
                
                // Only check shortage for BUY items
                $makeOrBuy = strtoupper(trim($item->make_or_buy ?? ''));
                $isBuyItem = in_array($makeOrBuy, ['BUY', 'B', 'PURCHASE']);
                
                $materials[] = [
                    'part_no' => $item->componentPart?->part_no ?? 'Unknown',
                    'part_name' => $item->componentPart?->part_name ?? 'Unknown',
                    'uom' => $item->componentPart?->uom ?? 'PCS',
                    'make_or_buy' => $makeOrBuy ?: 'N/A',
                    'required' => $requiredQty,
                    'primary_stock' => $primaryStock,
                    'substitute_stock' => $substituteStock,
                    'available' => $totalStock,
                    'shortage' => $isBuyItem ? max(0, $requiredQty - $totalStock) : 0,
                    'status' => !$isBuyItem ? 'N/A' : ($totalStock >= $requiredQty ? 'available' : 'shortage'),
                    'substitutes' => $substituteDetails,
                ];
            }
        }
        
        return view('production.material-availability.show', compact('order', 'materials'));
    }
}
