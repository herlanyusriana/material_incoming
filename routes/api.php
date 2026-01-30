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
});
