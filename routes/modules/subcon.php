<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubconController;

Route::middleware('can:manage_subcon')->prefix('subcon')->name('subcon.')->group(function () {
    Route::get('/', [SubconController::class, 'index'])->name('index');
    Route::get('/receive', [SubconController::class, 'receiveIndex'])->name('receive-index');
    Route::get('/receive/contract', [SubconController::class, 'contractReceive'])->name('contract-receive');
    Route::post('/receive/contract', [SubconController::class, 'storeContractReceive'])->name('contract-receive.store');
    Route::get('/traceability', [SubconController::class, 'traceabilityIndex'])->name('traceability-index');
    Route::get('/create', [SubconController::class, 'create'])->name('create');
    Route::get('/api/parts', [SubconController::class, 'parts'])->name('parts');
    Route::post('/', [SubconController::class, 'store'])->name('store');
    Route::get('/{subconOrder}/print-sj', [SubconController::class, 'printSuratJalan'])->name('print-sj');
    Route::get('/{subconOrder}/print-pl', [SubconController::class, 'printPackingList'])->name('print-pl');
    Route::get('/{subconOrder}/print-invoice', [SubconController::class, 'printInvoice'])->name('print-invoice');
    Route::get('/{subconOrder}', [SubconController::class, 'show'])->name('show');
    Route::post('/{subconOrder}/receive', [SubconController::class, 'receive'])->name('receive');
    Route::get('/receive/{subconOrderReceive}/print-label', [SubconController::class, 'printReceiveLabel'])->name('receive.print-label');
    Route::get('/receive/{subconOrderReceive}/print-pl', [SubconController::class, 'printReceivePL'])->name('receive.print-pl');
    Route::post('/{subconOrder}/cancel', [SubconController::class, 'cancel'])->name('cancel');
});
