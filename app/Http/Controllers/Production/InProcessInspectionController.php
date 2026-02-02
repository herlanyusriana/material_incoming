<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InProcessInspectionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        
        $query = ProductionInspection::query()
            ->with(['productionOrder.part', 'inspector'])
            ->where('type', 'in_process')
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->latest();
        
        $inspections = $query->paginate(20)->withQueryString();
        
        return view('production.in-process-inspection.index', compact('inspections', 'status'));
    }
    
    public function show(ProductionInspection $inspection)
    {
        $inspection->load(['productionOrder.part', 'inspector']);
        return view('production.in-process-inspection.show', compact('inspection'));
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
        
        $order = $inspection->productionOrder;
        
        if ($validated['status'] === 'pass') {
            $order->update([
                'workflow_stage' => 'mass_production',
            ]);
            return back()->with('success', 'In-Process Inspection passed. Continue Mass Production.');
        }
        
        if ($validated['status'] === 'fail') {
            return back()->with('error', 'In-Process Inspection failed. Please review production process.');
        }
        
        return back()->with('success', 'Inspection updated successfully.');
    }
}
