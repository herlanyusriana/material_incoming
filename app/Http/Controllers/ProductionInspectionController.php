<?php

namespace App\Http\Controllers;

use App\Models\ProductionInspection;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductionInspectionController extends Controller
{
    public function update(Request $request, ProductionInspection $inspection)
    {
        // Logic to update inspection status (Pass/Fail)
        $validated = $request->validate([
            'status' => 'required|in:pass,fail',
            'remarks' => 'nullable|string',
        ]);

        $inspection->update([
            'status' => $validated['status'],
            'remarks' => $validated['remarks'],
            'inspector_id' => Auth::id(),
            'inspected_at' => now(),
        ]);
        
        $order = $inspection->productionOrder;

        // Workflow logic based on inspection result and type
        if ($validated['status'] === 'pass') {
            if ($inspection->type === 'first_article') {
                $order->update(['workflow_stage' => 'mass_production']);
                // Maybe automatically create In-Process inspection?
                 ProductionInspection::create([
                    'production_order_id' => $order->id,
                    'type' => 'in_process',
                    'status' => 'pending',
                ]);
            } elseif ($inspection->type === 'in_process') {
                 $order->update(['workflow_stage' => 'mass_production']);
                 // Allow manual creation of Final? Or Auto?
                 // Let's assume after In-Process comes Final when they click "Finish"
            } elseif ($inspection->type === 'final') {
                 // Ready for Kanban Update
                 $order->update(['workflow_stage' => 'kanban_update']);
            }
        } else {
            // If fail, go to Dies Adjustment
            $order->update([
                'status' => 'dies_adjustment',
                'workflow_stage' => 'dies_adjustment',
                'dies_adjustment_notes' => 'Inspection failed: ' . $inspection->type,
            ]);
        }

        return back()->with('success', 'Inspection updated.');
    }
    
    public function store(Request $request, ProductionOrder $order)
    {
        // Manual creation of inspection if needed
         ProductionInspection::create([
            'production_order_id' => $order->id,
            'type' => $request->type, // first_article, in_process, final
            'status' => 'pending',
        ]);
        return back();
    }
}
