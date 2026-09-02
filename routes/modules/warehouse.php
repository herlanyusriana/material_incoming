<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WarehouseQcController;
use App\Http\Controllers\WarehousePutawayController;

Route::middleware('can:manage_inventory')->prefix('warehouse')->name('warehouse.')->group(function () {
    Route::get('/qc', [WarehouseQcController::class, 'index'])->name('qc.index');
    Route::post('/qc/{receive}', [WarehouseQcController::class, 'update'])->name('qc.update');

    Route::get('/putaway', [WarehousePutawayController::class, 'index'])->name('putaway.index');
    Route::post('/putaway/{receive}', [WarehousePutawayController::class, 'store'])->name('putaway.store');
    Route::delete('/putaway/{receive}', [WarehousePutawayController::class, 'destroy'])->name('putaway.destroy');
    Route::post('/putaway', [WarehousePutawayController::class, 'bulk'])->name('putaway.bulk');

    Route::get('/bin-transfers', [\App\Http\Controllers\BinTransferController::class, 'index'])->defaults('mode', 'bin_to_bin')->name('bin-transfers.index');
    Route::get('/bin-transfers/create', [\App\Http\Controllers\BinTransferController::class, 'create'])->defaults('mode', 'bin_to_bin')->name('bin-transfers.create');
    Route::post('/bin-transfers', [\App\Http\Controllers\BinTransferController::class, 'store'])->defaults('mode', 'bin_to_bin')->name('bin-transfers.store');
    Route::get('/batch-transfers', [\App\Http\Controllers\BinTransferController::class, 'index'])->defaults('mode', 'batch_to_batch')->name('batch-transfers.index');
    Route::get('/batch-transfers/create', [\App\Http\Controllers\BinTransferController::class, 'create'])->defaults('mode', 'batch_to_batch')->name('batch-transfers.create');
    Route::post('/batch-transfers', [\App\Http\Controllers\BinTransferController::class, 'store'])->defaults('mode', 'batch_to_batch')->name('batch-transfers.store');
    Route::get('/bin-transfers/{binTransfer}', [\App\Http\Controllers\BinTransferController::class, 'show'])->name('bin-transfers.show');
    Route::get('/bin-transfers/{binTransfer}/label', [\App\Http\Controllers\BinTransferController::class, 'printLabel'])->name('bin-transfers.label');

    // AJAX endpoints
    Route::get('/api/location-stock', [\App\Http\Controllers\BinTransferController::class, 'getLocationStock'])->name('bin-transfers.location-stock');
    Route::get('/api/part-locations', [\App\Http\Controllers\BinTransferController::class, 'getPartLocations'])->name('bin-transfers.part-locations');
    Route::get('/api/location-batches', [\App\Http\Controllers\BinTransferController::class, 'getLocationBatches'])->name('bin-transfers.location-batches');

    // Warehouse stock (by location)
    Route::get('/stock', [\App\Http\Controllers\WarehouseStockController::class, 'index'])->name('stock.index');
    Route::get('/stock/reconcile', [\App\Http\Controllers\WarehouseStockController::class, 'reconcile'])->name('stock.reconcile');
    Route::post('/stock/import', [\App\Http\Controllers\WarehouseStockController::class, 'importLocationStock'])->name('stock.import');
    Route::get('/stock/export', [\App\Http\Controllers\WarehouseStockController::class, 'export'])->name('stock.export');

    // Warehouse stock adjustments
    Route::get('/stock-adjustments', [\App\Http\Controllers\WarehouseStockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
    Route::get('/stock-adjustments/create', [\App\Http\Controllers\WarehouseStockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
    Route::post('/stock-adjustments', [\App\Http\Controllers\WarehouseStockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
    Route::get('/api/stock-adjustments/batches', [\App\Http\Controllers\WarehouseStockAdjustmentController::class, 'getBatches'])->name('stock-adjustments.get-batches');

    // Production load (schedule for warehouse)
    Route::get('/production-load', [\App\Http\Controllers\WarehouseProductionLoadController::class, 'index'])->name('production-load.index');

    // Barcode Labels
    Route::get('/labels', [App\Http\Controllers\BarcodeLabelController::class, 'index'])->name('labels.index');
    Route::get('/labels/part/{part}', [App\Http\Controllers\BarcodeLabelController::class, 'printPartLabel'])->name('labels.part');
    Route::get('/labels/line-stock/{part}', [App\Http\Controllers\BarcodeLabelController::class, 'printLineStockLabel'])->name('labels.line-stock');
    Route::post('/labels/bulk', [App\Http\Controllers\BarcodeLabelController::class, 'printBulkLabels'])->name('labels.bulk');

    // Stock Opname
    Route::resource('stock-opname', \App\Http\Controllers\StockOpnameController::class);
    Route::post('stock-opname/{session}/close', [\App\Http\Controllers\StockOpnameController::class, 'close'])->name('stock-opname.close');
    Route::post('stock-opname/{session}/adjust', [\App\Http\Controllers\StockOpnameController::class, 'adjust'])->name('stock-opname.adjust');

    // Trollies
    Route::get('trollies/export', [\App\Http\Controllers\TrollyController::class, 'export'])->name('trollies.export');
    Route::post('trollies/import', [\App\Http\Controllers\TrollyController::class, 'import'])->name('trollies.import');
    Route::get('trollies/print-range', [\App\Http\Controllers\TrollyController::class, 'printRange'])->name('trollies.print-range');
    Route::resource('trollies', \App\Http\Controllers\TrollyController::class);
    Route::get('trollies/{trolly}/print', [\App\Http\Controllers\TrollyController::class, 'printQr'])->name('trollies.print');
});
