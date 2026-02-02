<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', '');
        $search = $request->query('search', '');
        
        $query = ProductionOrder::query()
            ->with(['part', 'creator'])
            ->whereIn('status', ['planned', 'kanban_released'])
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
        
        return view('production.work-orders.index', compact('orders', 'status', 'search'));
    }
    
    public function show(ProductionOrder $order)
    {
        $order->load(['part', 'creator', 'inspections']);
        return view('production.work-orders.show', compact('order'));
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

        return back()->with('success', 'Work Order released to Kanban successfully.');
    }
    
    public function bulkRelease(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:production_orders,id',
        ]);
        
        $count = ProductionOrder::whereIn('id', $validated['order_ids'])
            ->where('status', 'planned')
            ->update([
                'status' => 'kanban_released',
                'workflow_stage' => 'kanban_released',
                'released_at' => now(),
                'released_by' => Auth::id(),
            ]);
        
        return back()->with('success', "{$count} work orders released to Kanban.");
    }
}
