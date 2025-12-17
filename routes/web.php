<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\ArrivalController;
use App\Http\Controllers\ReceiveController;
use App\Http\Controllers\TruckingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    // Dashboard with comprehensive information
    $departures = \App\Models\Arrival::with(['vendor', 'creator', 'items.receives'])
        ->latest()
        ->paginate(10);
    
    $summary = [
        'total_departures' => \App\Models\Arrival::count(),
        'total_receives' => \App\Models\Receive::count(),
        'pending_items' => \App\Models\ArrivalItem::whereHas('arrival')
            ->where('qty_goods', '>', 0)
            ->get()
            ->filter(function ($item) {
                $totalReceived = $item->receives->sum('qty');
                return ($item->qty_goods - $totalReceived) > 0;
            })
            ->count(),
        'today_receives' => \App\Models\Receive::whereDate('created_at', now())->count(),
    ];

    $recentReceives = \App\Models\Receive::with(['arrivalItem.part', 'arrivalItem.arrival.vendor'])
        ->latest()
        ->limit(5)
        ->get();

    $statusCounts = \App\Models\Receive::select('qc_status', \DB::raw('count(*) as total'))
        ->groupBy('qc_status')
        ->pluck('total', 'qc_status');

    return view('dashboard', compact('departures', 'summary', 'recentReceives', 'statusCounts'));
})->middleware(['auth', 'verified'])->name('dashboard');

// Invoice route (public untuk generate PDF)
Route::get('/departures/{departure}/invoice', [ArrivalController::class, 'printInvoice'])->name('departures.invoice');
Route::get('/departures/{departure}/inspection-report', [ArrivalController::class, 'printInspectionReport'])->name('departures.inspection-report');

Route::middleware('auth')->group(function () {
    Route::view('/incoming-material', 'incoming-material.dashboard')->name('incoming-material.dashboard');
    Route::resource('vendors', VendorController::class)->except(['show']);
    Route::get('/vendors/export', [VendorController::class, 'export'])->name('vendors.export');
    Route::post('/vendors/import', [VendorController::class, 'import'])->name('vendors.import');
    Route::resource('parts', PartController::class)->except(['show']);
    Route::get('/parts/export', [PartController::class, 'export'])->name('parts.export');
    Route::post('/parts/import', [PartController::class, 'import'])->name('parts.import');
    Route::resource('truckings', TruckingController::class)->except(['show']);
    Route::resource('departures', ArrivalController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::get('/vendors/{vendor}/parts', [PartController::class, 'byVendor'])->name('vendors.parts');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/receives', [ReceiveController::class, 'index'])->name('receives.index');
    Route::get('/departure-items/{arrivalItem}/receive', [ReceiveController::class, 'create'])->name('receives.create');
    Route::post('/departure-items/{arrivalItem}/receive', [ReceiveController::class, 'store'])->name('receives.store');
    Route::get('/receives/invoice/{arrival}', [ReceiveController::class, 'createByInvoice'])->name('receives.invoice.create');
    Route::post('/receives/invoice/{arrival}', [ReceiveController::class, 'storeByInvoice'])->name('receives.invoice.store');
    Route::get('/receives/{receive}/label', [ReceiveController::class, 'printLabel'])->name('receives.label');
    Route::get('/receives/completed', [ReceiveController::class, 'completed'])->name('receives.completed');
    Route::get('/receives/completed/{arrival}', [ReceiveController::class, 'completedInvoice'])->name('receives.completed.invoice');
});

require __DIR__.'/auth.php';
