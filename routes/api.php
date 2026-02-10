<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContainerInspectionController;
use App\Http\Controllers\Api\InspectionController;
use App\Http\Controllers\Api\OutgoingPickingController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

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
});
