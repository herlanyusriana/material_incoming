<?php

use App\Http\Controllers\Purchasing\MaterialPriceController;
use App\Http\Controllers\Purchasing\PurchaseOrderController;
use App\Http\Controllers\Purchasing\PurchaseRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('can:manage_purchasing')->prefix('purchasing')->name('purchasing.')->group(function () {
    Route::get('/purchase-requests/from-mrp', [PurchaseRequestController::class, 'createFromMrp'])->name('purchase-requests.create-from-mrp');
    Route::post('/purchase-requests/{purchase_request}/approve', [PurchaseRequestController::class, 'approve'])->name('purchase-requests.approve');
    Route::post('/purchase-requests/{purchase_request}/convert', [PurchaseRequestController::class, 'convertToPo'])->name('purchase-requests.convert');
    Route::resource('purchase-requests', PurchaseRequestController::class)->only(['index', 'create', 'store', 'show']);

    // Material Price — buy-side view of pricing_masters (purchase_price & material_cost)
    Route::get('/material-prices', [MaterialPriceController::class, 'index'])->name('material-prices.index');
    Route::post('/material-prices', [MaterialPriceController::class, 'store'])->name('material-prices.store');
    Route::put('/material-prices/{pricing}', [MaterialPriceController::class, 'update'])->name('material-prices.update');
    Route::delete('/material-prices/{pricing}', [MaterialPriceController::class, 'destroy'])->name('material-prices.destroy');

    Route::post('/purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('/purchase-orders/{purchase_order}/release', [PurchaseOrderController::class, 'release'])->name('purchase-orders.release');
    Route::get('/purchase-orders/{purchase_order}/print', [PurchaseOrderController::class, 'print'])->name('purchase-orders.print');
    Route::resource('purchase-orders', PurchaseOrderController::class)->only(['index', 'create', 'store', 'show']);
});
