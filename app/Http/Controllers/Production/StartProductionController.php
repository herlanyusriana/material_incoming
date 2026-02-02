<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\ProductionInspection;
use Illuminate\Http\Request;

class StartProductionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search', '');
        
        $query = ProductionOrder::query()
            ->with(['part'])
            ->where('status', 'released')
            ->when($search !== '', function($q) use ($search) {
                $q->where('production_order_number', 'like', "%{$search}%")
                    ->orWhereHas('part', function($qp) use ($search) {
                        $qp->where('part_no', 'like', "%{$search}%")
                            ->orWhere('part_name', 'like', "%{$search}%");
                    });
            })
            ->latest();
        
        $orders = $query->paginate(20)->withQueryString();
        
        return view('production.start-production.index', compact('orders', 'search'));
    }
    
    public function show(ProductionOrder $order)
    {
        $order->load(['part', 'creator']);
        return view('production.start-production.show', compact('order'));
    }
    
    public function start(ProductionOrder $order)
    {
        if ($order->status !== 'released') {
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
