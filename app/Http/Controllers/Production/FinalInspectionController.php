<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProductionOrderController;
use App\Models\ProductionInspection;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinalInspectionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = $this->baseFinalInspectionQuery()
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->latest();

        $inspections = $query->paginate(20)->withQueryString();

        $pageTitle = 'Final Inspection';
        $pageDescription = 'Daftar WO yang sudah finish production dan masuk tahap final inspection sebelum kanban update.';
        $mode = 'inspection';

        return view('production.final-inspection.index', compact('inspections', 'status', 'pageTitle', 'pageDescription', 'mode'));
    }

    public function kanbanIndex(Request $request)
    {
        $kanbanStatus = $request->query('kanban_status', 'pending');

        $query = $this->baseFinalInspectionQuery()
            ->where('status', 'pass');

        if ($kanbanStatus === 'pending') {
            $query->whereHas('productionOrder', function ($orderQuery) {
                $orderQuery->whereNull('kanban_updated_at');
            });
        } elseif ($kanbanStatus === 'updated') {
            $query->whereHas('productionOrder', function ($orderQuery) {
                $orderQuery->whereNotNull('kanban_updated_at');
            });
        }

        $inspections = $query->latest()->paginate(20)->withQueryString();

        $status = 'pass';
        $pageTitle = 'Kanban Update';
        $pageDescription = 'Daftar WO yang final inspection-nya sudah PASS dan siap diproses untuk kanban update.';
        $mode = 'kanban';

        return view('production.final-inspection.index', compact('inspections', 'status', 'pageTitle', 'pageDescription', 'mode', 'kanbanStatus'));
    }

    private function baseFinalInspectionQuery()
    {
        return ProductionInspection::query()
            ->with(['productionOrder.part', 'inspector'])
            ->where('type', 'final')
            ->whereHas('productionOrder', function ($orderQuery) {
                $orderQuery->whereIn('workflow_stage', ['final_inspection', 'kanban_update', 'warehouse_supply', 'finished']);
            });
    }
    
    public function show(ProductionInspection $inspection)
    {
        $inspection->load(['productionOrder.part', 'inspector']);
        return view('production.final-inspection.show', compact('inspection'));
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
            'inspector_id' => Auth::id(),
        ]);
        
        if ($validated['status'] === 'pass') {
            return back()->with('success', 'Final Inspection passed. Ready for Kanban Update.');
        }
        
        if ($validated['status'] === 'fail') {
            return back()->with('error', 'Final Inspection failed. Please review production.');
        }
        
        return back()->with('success', 'Inspection updated successfully.');
    }
    
    public function kanbanUpdate(Request $request, ProductionOrder $order)
    {
        // Reuse the active production flow so Kanban Update only updates result/backflush/on-order,
        // while physical FG warehouse posting stays in "Production Supply to WH".
        return app(ProductionOrderController::class)->kanbanUpdate($request, $order);
    }
}
