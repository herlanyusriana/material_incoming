<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;

class MassProductionController extends Controller
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
        
        return view('production.mass-production.index', compact('orders', 'search'));
    }
    
    public function show(ProductionOrder $order)
    {
        $order->load(['part', 'inspections']);
        return view('production.mass-production.show', compact('order'));
    }
    
    public function updateProgress(Request $request, ProductionOrder $order)
    {
        $validated = $request->validate([
            'qty_produced' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        
        $order->update([
            'qty_actual' => $validated['qty_produced'],
            'production_notes' => $validated['notes'] ?? $order->production_notes,
        ]);
        
        return back()->with('success', 'Production progress updated.');
    }
    
    public function requestInProcessInspection(ProductionOrder $order)
    {
        if ($order->workflow_stage !== 'mass_production') {
            return back()->with('error', 'Order must be in Mass Production stage.');
        }
        
        $order->update([
            'workflow_stage' => 'in_process_inspection',
        ]);
        
        // Create In-Process Inspection
        \App\Models\ProductionInspection::create([
            'production_order_id' => $order->id,
            'type' => 'in_process',
            'status' => 'pending',
        ]);
        
        return redirect()
            ->route('production.in-process-inspection.index')
            ->with('success', 'In-Process Inspection requested.');
    }
}
