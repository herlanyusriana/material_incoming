<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductionOrderController;
use App\Http\Controllers\ProductionInspectionController;

Route::middleware('can:view_production')->prefix('production')->name('production.')->group(function () {
    // Production Planning (GCI Planning Produksi)
    Route::get('/planning', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'index'])->name('planning.index');
    Route::redirect('/planning/pull-delivery-requirement', '/production/planning?source_mode=delivery');
    Route::post('/planning/create-session', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'createSession'])->name('planning.create-session');
    Route::post('/planning/auto-populate', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'autoPopulate'])->name('planning.auto-populate');
    Route::post('/planning/add-line', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'addLine'])->name('planning.add-line');
    Route::put('/planning/line/{line}', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'updateLine'])->name('planning.update-line');
    Route::delete('/planning/line/{line}', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'deleteLine'])->name('planning.delete-line');
    Route::post('/planning/generate-mo', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'generateMoWo'])->name('planning.generate-mo');
    Route::post('/planning/generate-mo-line', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'generateMoWoLine'])->name('planning.generate-mo-line');
    Route::post('/planning/pull-delivery-requirement', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'pullFromDeliveryRequirement'])->name('planning.pull-delivery-requirement');
    Route::get('/planning/calculations', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'getCalculations'])->name('planning.calculations');

    // Material Requirement (from Production Planning BOM explosion)
    Route::get('/material-requirement', [\App\Http\Controllers\Production\MaterialRequirementController::class, 'index'])->name('material-requirement.index');
    Route::get('/material-request', [\App\Http\Controllers\Production\MaterialRequestController::class, 'index'])->name('material-request.index');
    Route::get('/material-request/create', [\App\Http\Controllers\Production\MaterialRequestController::class, 'create'])->name('material-request.create');
    Route::post('/material-request', [\App\Http\Controllers\Production\MaterialRequestController::class, 'store'])->name('material-request.store');
    Route::get('/material-request/{materialRequest}', [\App\Http\Controllers\Production\MaterialRequestController::class, 'show'])->name('material-request.show');
    Route::get('/warehouse-supply', [\App\Http\Controllers\ProductionOrderController::class, 'warehouseSupplyIndex'])->name('warehouse-supply.index');
    Route::get('/production-supply-wh', [\App\Http\Controllers\ProductionOrderController::class, 'productionSupplyWhIndex'])->name('production-supply-wh.index');

    // Machine Load (Capacity Planning)
    Route::get('/machine-load', [\App\Http\Controllers\Production\MachineLoadController::class, 'index'])->name('machine-load.index');
    Route::get('/machine-load/{machine}', [\App\Http\Controllers\Production\MachineLoadController::class, 'show'])->name('machine-load.show');

    // Production Orders
    Route::get('/orders-history', [\App\Http\Controllers\ProductionOrderController::class, 'history'])->name('orders.history');
    Route::get('/orders-export', [\App\Http\Controllers\ProductionOrderController::class, 'export'])->name('orders.export');
    Route::resource('orders', \App\Http\Controllers\ProductionOrderController::class);
    Route::post('/orders/{order}/release-kanban', [\App\Http\Controllers\ProductionOrderController::class, 'releaseKanban'])->name('orders.release-kanban');
    Route::post('/orders/{order}/check-material', [\App\Http\Controllers\ProductionOrderController::class, 'checkMaterial'])->name('orders.check-material');
    Route::post('/orders/{order}/refresh-material', [\App\Http\Controllers\ProductionOrderController::class, 'refreshMaterial'])->name('orders.refresh-material');
    Route::post('/orders/{order}/material-request', [\App\Http\Controllers\ProductionOrderController::class, 'createMaterialRequest'])->name('orders.material-request');
    Route::post('/orders/{order}/material-issue', [\App\Http\Controllers\ProductionOrderController::class, 'issueMaterial'])->name('orders.material-issue');
    Route::post('/orders/{order}/material-handover', [\App\Http\Controllers\ProductionOrderController::class, 'handoverMaterial'])->name('orders.material-handover');
    Route::post('/orders/{order}/fg-supply-wh', [\App\Http\Controllers\ProductionOrderController::class, 'supplyFinishedGoodsToWarehouse'])->name('orders.fg-supply-wh');
    Route::post('/orders/{order}/fg-handover-wh', [\App\Http\Controllers\ProductionOrderController::class, 'handoverFinishedGoodsToWarehouse'])->name('orders.fg-handover-wh');
    Route::post('/orders/{order}/start', [\App\Http\Controllers\ProductionOrderController::class, 'startProduction'])->name('orders.start');
    Route::post('/orders/{order}/finish', [\App\Http\Controllers\ProductionOrderController::class, 'finishProduction'])->name('orders.finish');
    Route::post('/orders/{order}/kanban-update', [\App\Http\Controllers\ProductionOrderController::class, 'kanbanUpdate'])->name('orders.kanban-update');
    Route::post('/orders/{order}/cancel', [\App\Http\Controllers\ProductionOrderController::class, 'cancel'])->name('orders.cancel');

    // GCI Operator Dashboard (From Flutter App Sync)
    Route::get('/gci-dashboard', [\App\Http\Controllers\Production\ProductionGciWebController::class, 'index'])->name('gci-dashboard.index');
    Route::get('/gci-dashboard/{id}', [\App\Http\Controllers\Production\ProductionGciWebController::class, 'show'])->name('gci-dashboard.show');

    // WO Monitoring Dashboard (real-time)
    Route::get('/wo-monitoring', [\App\Http\Controllers\Production\ProductionGciWebController::class, 'woMonitoring'])->name('wo-monitoring.index');
    Route::get('/board', [\App\Http\Controllers\Production\ProductionBoardController::class, 'index'])->name('board.index');

    // Plant Performance Dashboard
    Route::get('/plant-performance', fn() => redirect()->route('dashboard', request()->only('date_from', 'date_to')))->name('plant-performance.index');

    // Operator KPI Dashboard
    Route::get('/operator-kpi', [\App\Http\Controllers\Production\ProductionGciWebController::class, 'operatorKpi'])->name('operator-kpi.index');
    Route::get('/operator-kpi/data', [\App\Http\Controllers\Production\ProductionGciWebController::class, 'operatorKpiData'])->name('operator-kpi.data');
    Route::get('/operator-kpi/pdf', [\App\Http\Controllers\Production\ProductionGciWebController::class, 'operatorKpiPdf'])->name('operator-kpi.pdf');

    // Downtime History (machine breakdowns & troubles)
    Route::get('/downtime-history', [\App\Http\Controllers\Production\QdcHistoryController::class, 'downtimeIndex'])->name('downtime-history.index');
    Route::get('/downtime-history/pdf', [\App\Http\Controllers\Production\QdcHistoryController::class, 'downtimePdf'])->name('downtime-history.pdf');

    // QDC History (planned activities: changeover, cleaning, etc.)
    Route::get('/qdc-history', [\App\Http\Controllers\Production\QdcHistoryController::class, 'index'])->name('qdc-history.index');
    Route::get('/qdc-history/pdf', [\App\Http\Controllers\Production\QdcHistoryController::class, 'pdf'])->name('qdc-history.pdf');

    // Production Downtimes (QDC, Breaks, etc.)
    Route::post('/orders/{productionOrder}/downtimes', [\App\Http\Controllers\Production\ProductionDowntimeController::class, 'store'])->name('downtimes.store');
    Route::put('/orders/{productionOrder}/downtimes/{downtime}', [\App\Http\Controllers\Production\ProductionDowntimeController::class, 'update'])->name('downtimes.update');
    Route::delete('/orders/{productionOrder}/downtimes/{downtime}', [\App\Http\Controllers\Production\ProductionDowntimeController::class, 'destroy'])->name('downtimes.destroy');

    // Material Availability
    Route::get('/material-availability', [\App\Http\Controllers\Production\MaterialAvailabilityController::class, 'index'])->name('material-availability.index');
    Route::get('/material-availability/{order}', [\App\Http\Controllers\Production\MaterialAvailabilityController::class, 'show'])->name('material-availability.show');
    Route::post('/material-availability/{order}/check', [\App\Http\Controllers\Production\MaterialAvailabilityController::class, 'check'])->name('material-availability.check');

    // Start Production
    Route::get('/start-production', [\App\Http\Controllers\Production\StartProductionController::class, 'index'])->name('start-production.index');
    Route::get('/start-production/{order}', [\App\Http\Controllers\Production\StartProductionController::class, 'show'])->name('start-production.show');
    Route::post('/start-production/{order}/start', [\App\Http\Controllers\Production\StartProductionController::class, 'start'])->name('start-production.start');

    // QC Inspection (First Article)
    Route::middleware('can:manage_qc_inspection')->group(function () {
        Route::get('/qc-inspection', [\App\Http\Controllers\Production\QcInspectionController::class, 'index'])->name('qc-inspection.index');
        Route::get('/qc-inspection/{inspection}', [\App\Http\Controllers\Production\QcInspectionController::class, 'show'])->name('qc-inspection.show');
        Route::put('/qc-inspection/{inspection}', [\App\Http\Controllers\Production\QcInspectionController::class, 'update'])->name('qc-inspection.update');
    });

    // Mass Production
    Route::get('/mass-production', [\App\Http\Controllers\Production\MassProductionController::class, 'index'])->name('mass-production.index');
    Route::get('/mass-production/{order}', [\App\Http\Controllers\Production\MassProductionController::class, 'show'])->name('mass-production.show');
    Route::post('/mass-production/{order}/update-progress', [\App\Http\Controllers\Production\MassProductionController::class, 'updateProgress'])->name('mass-production.update-progress');
    Route::post('/mass-production/{order}/request-inspection', [\App\Http\Controllers\Production\MassProductionController::class, 'requestInProcessInspection'])->name('mass-production.request-inspection');

    // In-Process Inspection
    Route::middleware('can:manage_in_process_inspection')->group(function () {
        Route::get('/in-process-inspection', [\App\Http\Controllers\Production\InProcessInspectionController::class, 'index'])->name('in-process-inspection.index');
        Route::get('/in-process-inspection/{inspection}', [\App\Http\Controllers\Production\InProcessInspectionController::class, 'show'])->name('in-process-inspection.show');
        Route::put('/in-process-inspection/{inspection}', [\App\Http\Controllers\Production\InProcessInspectionController::class, 'update'])->name('in-process-inspection.update');
    });

    // Finish Production
    Route::get('/finish-production', [\App\Http\Controllers\Production\FinishProductionController::class, 'index'])->name('finish-production.index');
    Route::get('/finish-production/{order}', [\App\Http\Controllers\Production\FinishProductionController::class, 'show'])->name('finish-production.show');
    Route::post('/finish-production/{order}/finish', [\App\Http\Controllers\Production\FinishProductionController::class, 'finish'])->name('finish-production.finish');

    // Final Inspection & Kanban Update
    Route::middleware('can:manage_final_inspection')->group(function () {
        Route::get('/final-inspection', [\App\Http\Controllers\Production\FinalInspectionController::class, 'index'])->name('final-inspection.index');
        Route::get('/final-inspection/{inspection}', [\App\Http\Controllers\Production\FinalInspectionController::class, 'show'])->name('final-inspection.show');
        Route::put('/final-inspection/{inspection}', [\App\Http\Controllers\Production\FinalInspectionController::class, 'update'])->name('final-inspection.update');
    });
    Route::middleware('can:manage_kanban_update')->group(function () {
        Route::get('/kanban-update', [\App\Http\Controllers\Production\FinalInspectionController::class, 'kanbanIndex'])->name('kanban-update.index');
        Route::post('/final-inspection/{order}/kanban-update', [\App\Http\Controllers\Production\FinalInspectionController::class, 'kanbanUpdate'])->name('final-inspection.kanban-update');
    });

    // Release Kanban (Bulk/Fast release for Spv/Adm)
    Route::get('/kanban-release', [\App\Http\Controllers\ProductionOrderController::class, 'kanbanReleaseIndex'])->name('kanban-release.index');
    Route::post('/kanban-release/bulk', [\App\Http\Controllers\ProductionOrderController::class, 'bulkReleaseKanban'])->name('kanban-release.bulk');

    // Legacy inspection routes
    Route::post('/inspections/{inspection}', [\App\Http\Controllers\ProductionInspectionController::class, 'update'])->name('inspections.update');
});
