<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ContractNumberController;
use App\Http\Controllers\TruckingController;
use App\Http\Controllers\MachineController;

Route::middleware('can:manage_parts')->group(function () {
    Route::resource('vendors', VendorController::class)->except(['show']);
    Route::get('/vendors/export', [VendorController::class, 'export'])->name('vendors.export');
    Route::post('/vendors/import', [VendorController::class, 'import'])->name('vendors.import');
    Route::get('/vendors/{vendor}/vendor-part-names', [PartController::class, 'vendorPartNames'])->name('vendors.vendor-part-names');
    Route::get('/vendors/{vendor}/contract-numbers', [ContractNumberController::class, 'byVendor'])->name('vendors.contract-numbers');
    Route::get('/vendors/{vendor}/parts', [PartController::class, 'byVendor'])->name('vendors.parts');

    Route::get('/parts', [PartController::class, 'index'])->name('parts.index');
    Route::post('/parts', [PartController::class, 'store'])->name('parts.store');
    Route::post('/parts/bulk-policy', [PartController::class, 'bulkUpdatePolicy'])->name('parts.bulk-policy');
    Route::put('/parts/{part}', [PartController::class, 'update'])->name('parts.update');
    Route::delete('/parts/{part}', [PartController::class, 'destroy'])->name('parts.destroy');
    Route::get('/parts/export', [PartController::class, 'export'])->name('parts.export');
    Route::post('/parts/import', [PartController::class, 'import'])->name('parts.import');
    Route::post('/parts/{part}/vendor-parts', [PartController::class, 'storeVendorPart'])->name('parts.vendor-parts.store');
    Route::put('/vendor-parts/{vendorPart}', [PartController::class, 'updateVendorPart'])->name('parts.vendor-parts.update');
    Route::delete('/vendor-parts/{vendorPart}', [PartController::class, 'destroyVendorPart'])->name('parts.vendor-parts.destroy');

    Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
    Route::get('/pricing/export', [PricingController::class, 'export'])->name('pricing.export');
    Route::post('/pricing/import', [PricingController::class, 'import'])->name('pricing.import');
    Route::get('/pricing/create', [PricingController::class, 'create'])->name('pricing.create');
    Route::post('/pricing', [PricingController::class, 'store'])->name('pricing.store');
    Route::put('/pricing/{pricing}', [PricingController::class, 'update'])->name('pricing.update');
    Route::delete('/pricing/{pricing}', [PricingController::class, 'destroy'])->name('pricing.destroy');

    Route::get('/contract-numbers', [ContractNumberController::class, 'index'])->name('contract-numbers.index');
    Route::get('/contract-numbers/create', [ContractNumberController::class, 'create'])->name('contract-numbers.create');
    Route::post('/contract-numbers', [ContractNumberController::class, 'store'])->name('contract-numbers.store');
    Route::get('/contract-numbers/{contractNumber}', [ContractNumberController::class, 'show'])->name('contract-numbers.show');
    Route::get('/contract-numbers/{contractNumber}/edit', [ContractNumberController::class, 'edit'])->name('contract-numbers.edit');
    Route::put('/contract-numbers/{contractNumber}', [ContractNumberController::class, 'update'])->name('contract-numbers.update');
    Route::delete('/contract-numbers/{contractNumber}', [ContractNumberController::class, 'destroy'])->name('contract-numbers.destroy');

    Route::resource('truckings', TruckingController::class)->except(['show']);

    Route::get('/machines/export', [MachineController::class, 'export'])->name('machines.export');
    Route::post('/machines/import', [MachineController::class, 'import'])->name('machines.import');
    Route::resource('machines', MachineController::class)->except(['show']);
});
