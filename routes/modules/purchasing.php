<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Purchasing\PurchaseRequestController;
use App\Http\Controllers\Purchasing\PurchaseOrderController;

Route::middleware('can:manage_purchasing')->prefix('purchasing')->name('purchasing.')->group(function () {
    Route::get('/purchase-requests/from-mrp', [PurchaseRequestController::class, 'createFromMrp'])->name('purchase-requests.create-from-mrp');
    Route::post('/purchase-requests/{purchase_request}/approve', [PurchaseRequestController::class, 'approve'])->name('purchase-requests.approve');
    Route::post('/purchase-requests/{purchase_request}/convert', [PurchaseRequestController::class, 'convertToPo'])->name('purchase-requests.convert');
    Route::resource('purchase-requests', PurchaseRequestController::class);

    Route::post('/purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('/purchase-orders/{purchase_order}/release', [PurchaseOrderController::class, 'release'])->name('purchase-orders.release');
    Route::get('/purchase-orders/{purchase_order}/print', [PurchaseOrderController::class, 'print'])->name('purchase-orders.print');
    Route::resource('purchase-orders', PurchaseOrderController::class);
});
