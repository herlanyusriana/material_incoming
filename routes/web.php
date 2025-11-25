<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\ArrivalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('vendors', VendorController::class)->except(['show']);
    Route::resource('parts', PartController::class)->except(['show']);
    Route::resource('arrivals', ArrivalController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/vendors/{vendor}/parts', [PartController::class, 'byVendor'])->name('vendors.parts');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
