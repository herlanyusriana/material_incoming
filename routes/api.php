<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContainerInspectionController;
use App\Http\Controllers\Api\InspectionController;
use App\Http\Controllers\Api\OutgoingPickingController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::post('/production-gci/sync', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'sync']);
Route::get('/production-gci/machines', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'machines']);
Route::get('/production-gci/work-orders', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'workOrders']);
Route::get('/production-gci/parts', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'parts']);
Route::get('/production-gci/wo/{id}/material-status', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'materialStatus']);
Route::get('/production-gci/wo/{id}/material-issue-history', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'materialIssueHistory']);
Route::get('/production-gci/wo/{id}/routing', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'workOrderRouting']);
Route::post('/production-gci/wo/{id}/start', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'startWo']);
Route::post('/production-gci/wo/{id}/pause', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'pauseWo']);
Route::post('/production-gci/wo/{id}/resume', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'resumeWo']);
Route::post('/production-gci/wo/{id}/finish', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'finishWo']);
Route::get('/production-gci/wo/{id}/hourly', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'getHourlyReports']);
Route::post('/production-gci/wo/{id}/hourly', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'saveHourlyReport']);
Route::get('/production-gci/machines/{id}/downtimes', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'machineDowntimes']);
Route::post('/production-gci/machines/{id}/downtimes', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'startMachineDowntime']);
Route::post('/production-gci/downtimes/{id}/stop', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'stopMachineDowntime']);
Route::post('/production-gci/qdc-session', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'storeQdcSession']);
Route::get('/production-gci/machines/{id}/qdc-sessions', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'machineQdcSessions']);
Route::get('/production-gci/wo-monitoring', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'woMonitoringData']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/arrivals/pending-inspection', [InspectionController::class, 'pending']);
    Route::get('/arrivals/{arrival}/inspection', [InspectionController::class, 'show']);
    Route::post('/arrivals/{arrival}/inspection', [InspectionController::class, 'upsert']);

    Route::get('/arrivals/{arrival}/containers', [ContainerInspectionController::class, 'listByArrival']);
    Route::get('/containers/{container}/inspection', [ContainerInspectionController::class, 'show']);
    Route::post('/containers/{container}/inspection', [ContainerInspectionController::class, 'upsert']);

    // Outgoing picking (scan-friendly)
    Route::get('/outgoing/delivery-notes/{deliveryNote}', [OutgoingPickingController::class, 'show']);
    Route::post('/outgoing/delivery-notes/{deliveryNote}/start-picking', [OutgoingPickingController::class, 'startPicking']);
    Route::post('/outgoing/delivery-notes/{deliveryNote}/pick', [OutgoingPickingController::class, 'pick']);

    // Picking FG (from delivery plan)
    Route::prefix('picking-fg')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\PickingFgApiController::class, 'index']);
        Route::get('/status', [\App\Http\Controllers\Api\PickingFgApiController::class, 'status']);
        Route::get('/lookup', [\App\Http\Controllers\Api\PickingFgApiController::class, 'lookupPart']);
        Route::post('/pick', [\App\Http\Controllers\Api\PickingFgApiController::class, 'updatePick']);

        // DO-based picking flow (scan-friendly)
        Route::get('/delivery-orders', [\App\Http\Controllers\Api\PickingFgApiController::class, 'deliveryOrders']);
        Route::get('/delivery-orders/{id}', [\App\Http\Controllers\Api\PickingFgApiController::class, 'deliveryOrderDetail']);
        Route::post('/scan-location', [\App\Http\Controllers\Api\PickingFgApiController::class, 'scanLocation']);
        Route::post('/scan-part', [\App\Http\Controllers\Api\PickingFgApiController::class, 'scanPart']);
    });

    // Warehouse Scanning App
    Route::prefix('warehouse')->group(function () {
        Route::get('/work-orders', [\App\Http\Controllers\Api\WarehouseApiController::class, 'pendingWorkOrders']);
        Route::get('/work-orders/{id}', [\App\Http\Controllers\Api\WarehouseApiController::class, 'getWorkOrder']);
        Route::post('/work-orders/{id}/scan', [\App\Http\Controllers\Api\WarehouseApiController::class, 'scanTag']);
        Route::delete('/work-orders/{id}/scan/{tagNo}', [\App\Http\Controllers\Api\WarehouseApiController::class, 'deleteTag']);
        Route::post('/work-orders/{id}/handover', [\App\Http\Controllers\Api\WarehouseApiController::class, 'handover']);
    });

    // Warehouse Scanning & Putaway
    Route::get('/warehouse/tag-info', [\App\Http\Controllers\Api\WarehouseScanningController::class, 'getTagInfo']);
    Route::post('/warehouse/putaway', [\App\Http\Controllers\Api\WarehouseScanningController::class, 'putaway']);
    Route::get('/warehouse/locations', [\App\Http\Controllers\Api\WarehouseScanningController::class, 'getLocations']);

    // Stock Opname API
    Route::prefix('so')->group(function () {
        Route::get('/sessions/active', [\App\Http\Controllers\Api\StockOpnameApiController::class, 'activeSessions']);
        Route::post('/scan-part', [\App\Http\Controllers\Api\StockOpnameApiController::class, 'scanPart']);
        Route::post('/scan-location', [\App\Http\Controllers\Api\StockOpnameApiController::class, 'scanLocation']);
        Route::post('/submit-count', [\App\Http\Controllers\Api\StockOpnameApiController::class, 'submitCount']);
        Route::get('/sessions/{session}/export', [\App\Http\Controllers\Api\StockOpnameApiController::class, 'export']);
    });

    // Inventory View & Transfers
    Route::prefix('inventory')->group(function () {
        Route::get('/search', [\App\Http\Controllers\Api\InventoryApiController::class, 'search']);
        Route::post('/transfer', [\App\Http\Controllers\Api\InventoryApiController::class, 'transfer']);
    });

    // Customer stock update
    Route::prefix('customer-stock')->group(function () {
        Route::get('/customers', [\App\Http\Controllers\Api\CustomerStockApiController::class, 'customers']);
        Route::get('/parts', [\App\Http\Controllers\Api\CustomerStockApiController::class, 'parts']);
        Route::get('/entries', [\App\Http\Controllers\Api\CustomerStockApiController::class, 'entries']);
        Route::post('/upsert', [\App\Http\Controllers\Api\CustomerStockApiController::class, 'upsert']);
    });
});
