<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\GciInventoryController;
use App\Http\Controllers\WarehouseLocationController;

Route::middleware('can:manage_inventory')->group(function () {
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/receives', [InventoryController::class, 'receives'])->name('inventory.receives');
    Route::get('/inventory/receives/search', [InventoryController::class, 'searchReceives'])->name('inventory.receives.search');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/export', [InventoryController::class, 'export'])->name('inventory.export');
    Route::post('/inventory/import', [InventoryController::class, 'import'])->name('inventory.import');
    Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

    // GCI Inventory — index merged into /inventory, keep API endpoints
    Route::get('/inventory/gci', fn() => redirect()->route('inventory.index'))->name('inventory.gci.index');
    Route::get('/inventory/gci/export', [GciInventoryController::class, 'export'])->name('inventory.gci.export');
    Route::post('/inventory/gci/update-location', [GciInventoryController::class, 'updateLocation'])->name('inventory.gci.update-location');
    Route::post('/inventory/gci/update-stock', [GciInventoryController::class, 'updateStock'])->name('inventory.gci.update-stock');

    Route::get('/inventory/locations', [WarehouseLocationController::class, 'index'])->name('inventory.locations.index');
    Route::post('/inventory/locations', [WarehouseLocationController::class, 'store'])->name('inventory.locations.store');
    Route::get('/inventory/locations/export', [WarehouseLocationController::class, 'export'])->name('inventory.locations.export');
    Route::post('/inventory/locations/import', [WarehouseLocationController::class, 'import'])->name('inventory.locations.import');
    Route::get('/inventory/locations/print-map', [WarehouseLocationController::class, 'printMap'])->name('inventory.locations.print-map');
    Route::get('/inventory/locations/print-range', [WarehouseLocationController::class, 'printRange'])->name('inventory.locations.print-range');
    Route::get('/inventory/locations/{location}/print', [WarehouseLocationController::class, 'printQr'])->name('inventory.locations.print');
    Route::put('/inventory/locations/{location}', [WarehouseLocationController::class, 'update'])->name('inventory.locations.update');
    Route::delete('/inventory/locations/{location}', [WarehouseLocationController::class, 'destroy'])->name('inventory.locations.destroy');
});
