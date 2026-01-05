<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContainerInspectionController;
use App\Http\Controllers\Api\InspectionController;
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
});
