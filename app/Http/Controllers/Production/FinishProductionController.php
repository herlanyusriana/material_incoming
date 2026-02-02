<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\ProductionInspection;
use Illuminate\Http\Request;

class FinishProductionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search', '');
        
        $query = ProductionOrder::query()
            ->with(['part'])
            ->where('status', 'in_production')
            ->where('workflow_stage', 'mass_production')
            ->when($search !== '', function($q) use ($search) {
                $q->where('production_order_number', 'like', "%{$search}%")
                    ->orWhereHas('part', function($qp) use ($search) {
                        $qp->where('part_no', 'like', "%{$search}%")
                            ->orWhere('part_name', 'like', "%{$search}%");
                    });
            })
            ->latest();
        
        $orders = $query->paginate(20)->withQueryString();
        
        return view('production.finish-production.index', compact('orders', 'search'));
    }
    
    public function show(ProductionOrder $order)
    {
        $order->load(['part', 'inspections']);
        return view('production.finish-production.show', compact('order'));
    }
    
    public function finish(Request $request, ProductionOrder $order)
    {
        if ($order->status !== 'in_production') {
            return back()->with('error', 'Order must be In Production to finish.');
        }
        
        $validated = $request->validate([
            'qty_actual' => 'required|numeric|min:0',
        ]);
        
        $order->update([
            'workflow_stage' => 'final_inspection',
            'end_time' => now(),
            'qty_actual' => $validated['qty_actual'],
        ]);
        
        // Create Final Inspection
        if (!$order->inspections()->where('type', 'final')->exists()) {
            ProductionInspection::create([
                'production_order_id' => $order->id,
                'type' => 'final',
                'status' => 'pending',
            ]);
        }
        
        return redirect()
            ->route('production.final-inspection.index')
            ->with('success', 'Production finished. Please complete Final Inspection.');
    }
}
