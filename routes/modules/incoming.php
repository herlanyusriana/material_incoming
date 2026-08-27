<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArrivalController;
use App\Http\Controllers\ReceiveController;
use App\Http\Controllers\LocalPoController;

Route::middleware('can:manage_incoming')->group(function () {
    // Departure reports (previously public — now requires auth + permission)
    Route::get('/departures/{departure}/invoice', [ArrivalController::class, 'printInvoice'])->name('departures.invoice');
    Route::get('/departures/{departure}/inspection-report', [ArrivalController::class, 'printInspectionReport'])->name('departures.inspection-report');
    Route::get('/departures/{departure}/export-detail', [ArrivalController::class, 'exportDetail'])->name('departures.export-detail');

    Route::resource('departures', ArrivalController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    Route::get('/departure-items/{arrivalItem}/edit', [ArrivalController::class, 'editItem'])->name('departure-items.edit');
    Route::put('/departure-items/{arrivalItem}', [ArrivalController::class, 'updateItem'])->name('departure-items.update');
    Route::get('/departures/{departure}/items/create', [ArrivalController::class, 'createItem'])->name('departure-items.create');
    Route::post('/departures/{departure}/items', [ArrivalController::class, 'storeItem'])->name('departure-items.store');

    Route::get('/receives/import-documents', [ReceiveController::class, 'importDocuments'])->name('receives.import-documents');
    Route::get('/receives/import-documents/export', [ReceiveController::class, 'exportImportDocuments'])->name('receives.import-documents.export');

    Route::get('/local-pos', [LocalPoController::class, 'index'])->name('local-pos.index');
    Route::get('/local-pos/export', [LocalPoController::class, 'export'])->name('local-pos.export');
    Route::get('/local-pos/create', [LocalPoController::class, 'create'])->name('local-pos.create');
    Route::post('/local-pos', [LocalPoController::class, 'store'])->name('local-pos.store');
    Route::get('/local-pos/{arrival}', [LocalPoController::class, 'show'])->name('local-pos.show');
    Route::get('/local-pos/{arrival}/export', [LocalPoController::class, 'exportDetail'])->name('local-pos.export-detail');
    Route::get('/local-pos/{arrival}/edit', [LocalPoController::class, 'edit'])->name('local-pos.edit');
    Route::put('/local-pos/{arrival}', [LocalPoController::class, 'update'])->name('local-pos.update');
    Route::delete('/local-pos/{arrival}', [LocalPoController::class, 'destroy'])->name('local-pos.destroy');

    Route::get('/receives', [ReceiveController::class, 'index'])->name('receives.index');
    Route::get('/departure-items/{arrivalItem}/receive', [ReceiveController::class, 'create'])->name('receives.create');
    Route::post('/departure-items/{arrivalItem}/receive', [ReceiveController::class, 'store'])->name('receives.store');
    Route::get('/receives/invoice/{arrival}', [ReceiveController::class, 'createByInvoice'])->name('receives.invoice.create');
    Route::post('/receives/invoice/{arrival}', [ReceiveController::class, 'storeByInvoice'])->name('receives.invoice.store');
    Route::get('/receives/{receive}/edit', [ReceiveController::class, 'edit'])->name('receives.edit');
    Route::put('/receives/{receive}', [ReceiveController::class, 'update'])->name('receives.update');
    Route::delete('/receives/{receive}', [ReceiveController::class, 'destroy'])->name('receives.destroy');
    Route::get('/receives/{receive}/label', [ReceiveController::class, 'printLabel'])->name('receives.label');
    Route::get('/receives/completed', [ReceiveController::class, 'completed'])->name('receives.completed');
    Route::get('/receives/completed/{arrival}', [ReceiveController::class, 'completedInvoice'])->name('receives.completed.invoice');
    Route::get('/receives/completed/{arrival}/export', [ReceiveController::class, 'exportCompletedInvoice'])->name('receives.completed.invoice.export');
});
