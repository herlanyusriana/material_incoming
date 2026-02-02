<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\ProductionInspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QcInspectionController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type', 'first_article');
        $status = $request->query('status', 'pending');
        
        $query = ProductionInspection::query()
            ->with(['productionOrder.part', 'inspector'])
            ->where('type', $type)
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->latest();
        
        $inspections = $query->paginate(20)->withQueryString();
        
        return view('production.qc-inspection.index', compact('inspections', 'type', 'status'));
    }
    
    public function show(ProductionInspection $inspection)
    {
        $inspection->load(['productionOrder.part', 'inspector']);
        return view('production.qc-inspection.show', compact('inspection'));
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
        
        // Update order workflow based on inspection result
        if ($inspection->type === 'first_article' && $validated['status'] === 'pass') {
            $order->update([
                'workflow_stage' => 'mass_production',
            ]);
            return back()->with('success', 'First Article Inspection passed. Ready for Mass Production.');
        }
        
        if ($inspection->type === 'first_article' && $validated['status'] === 'fail') {
            $order->update([
                'workflow_stage' => 'first_article_inspection',
            ]);
            return back()->with('error', 'First Article Inspection failed. Please review and restart.');
        }
        
        return back()->with('success', 'Inspection updated successfully.');
    }
}
