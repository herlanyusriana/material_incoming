<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\ArrivalController;
use App\Http\Controllers\ReceiveController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\WarehouseLocationController;
use App\Http\Controllers\LocalPoController;
use App\Http\Controllers\OutgoingController;
use App\Http\Controllers\TruckingController;
use App\Http\Controllers\Planning\CustomerController as PlanningCustomerController;
use App\Http\Controllers\Planning\BomController as PlanningBomController;
use App\Http\Controllers\Planning\GciPartController as PlanningGciPartController;
use App\Http\Controllers\Planning\CustomerPartController as PlanningCustomerPartController;
use App\Http\Controllers\Planning\CustomerPlanningImportController as PlanningCustomerPlanningImportController;
use App\Http\Controllers\Planning\CustomerPoController as PlanningCustomerPoController;
use App\Http\Controllers\Planning\ForecastController as PlanningForecastController;
use App\Http\Controllers\Planning\MpsController as PlanningMpsController;
use App\Http\Controllers\Planning\MrpController as PlanningMrpController;
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

    Route::get('/local-pos', [LocalPoController::class, 'index'])->name('local-pos.index');
    Route::get('/local-pos/create', [LocalPoController::class, 'create'])->name('local-pos.create');
    Route::post('/local-pos', [LocalPoController::class, 'store'])->name('local-pos.store');

    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/receives', [InventoryController::class, 'receives'])->name('inventory.receives');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/export', [InventoryController::class, 'export'])->name('inventory.export');
    Route::post('/inventory/import', [InventoryController::class, 'import'])->name('inventory.import');
    Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

    Route::get('/inventory/locations', [WarehouseLocationController::class, 'index'])->name('inventory.locations.index');
    Route::post('/inventory/locations', [WarehouseLocationController::class, 'store'])->name('inventory.locations.store');
    Route::get('/inventory/locations/export', [WarehouseLocationController::class, 'export'])->name('inventory.locations.export');
    Route::post('/inventory/locations/import', [WarehouseLocationController::class, 'import'])->name('inventory.locations.import');
    Route::get('/inventory/locations/{location}/print', [WarehouseLocationController::class, 'printQr'])->name('inventory.locations.print');
    Route::put('/inventory/locations/{location}', [WarehouseLocationController::class, 'update'])->name('inventory.locations.update');
    Route::delete('/inventory/locations/{location}', [WarehouseLocationController::class, 'destroy'])->name('inventory.locations.destroy');

    Route::prefix('outgoing')->name('outgoing.')->group(function () {
        Route::get('/daily-planning', [OutgoingController::class, 'dailyPlanning'])->name('daily-planning');
        Route::get('/daily-planning/template', [OutgoingController::class, 'dailyPlanningTemplate'])->name('daily-planning.template');
        Route::post('/daily-planning/import', [OutgoingController::class, 'dailyPlanningImport'])->name('daily-planning.import');
        Route::get('/daily-planning/{plan}/export', [OutgoingController::class, 'dailyPlanningExport'])->name('daily-planning.export');
        Route::get('/customer-po', [OutgoingController::class, 'customerPo'])->name('customer-po');
        Route::get('/product-mapping', [OutgoingController::class, 'productMapping'])->name('product-mapping');
        Route::get('/delivery-requirements', [OutgoingController::class, 'deliveryRequirements'])->name('delivery-requirements');
        Route::get('/gci-inventory', [OutgoingController::class, 'gciInventory'])->name('gci-inventory');
        Route::get('/stock-at-customers', [OutgoingController::class, 'stockAtCustomers'])->name('stock-at-customers');
        Route::get('/delivery-plan', [OutgoingController::class, 'deliveryPlan'])->name('delivery-plan');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/receives', [ReceiveController::class, 'index'])->name('receives.index');
    Route::get('/departure-items/{arrivalItem}/receive', [ReceiveController::class, 'create'])->name('receives.create');
    Route::post('/departure-items/{arrivalItem}/receive', [ReceiveController::class, 'store'])->name('receives.store');
    Route::get('/receives/invoice/{arrival}', [ReceiveController::class, 'createByInvoice'])->name('receives.invoice.create');
    Route::post('/receives/invoice/{arrival}', [ReceiveController::class, 'storeByInvoice'])->name('receives.invoice.store');
    Route::get('/receives/{receive}/edit', [ReceiveController::class, 'edit'])->name('receives.edit');
    Route::put('/receives/{receive}', [ReceiveController::class, 'update'])->name('receives.update');
    Route::get('/receives/{receive}/label', [ReceiveController::class, 'printLabel'])->name('receives.label');
    Route::get('/receives/completed', [ReceiveController::class, 'completed'])->name('receives.completed');
    Route::get('/receives/completed/{arrival}', [ReceiveController::class, 'completedInvoice'])->name('receives.completed.invoice');
    Route::get('/receives/completed/{arrival}/export', [ReceiveController::class, 'exportCompletedInvoice'])->name('receives.completed.invoice.export');

    Route::get('/departure-items/{arrivalItem}/edit', [ArrivalController::class, 'editItem'])->name('departure-items.edit');
    Route::put('/departure-items/{arrivalItem}', [ArrivalController::class, 'updateItem'])->name('departure-items.update');
    Route::get('/departures/{departure}/items/create', [ArrivalController::class, 'createItem'])->name('departure-items.create');
    Route::post('/departures/{departure}/items', [ArrivalController::class, 'storeItem'])->name('departure-items.store');

    Route::prefix('planning')->name('planning.')->group(function () {
        Route::get('/gci-parts', [PlanningGciPartController::class, 'index'])->name('gci-parts.index');
        Route::post('/gci-parts', [PlanningGciPartController::class, 'store'])->name('gci-parts.store');
        Route::put('/gci-parts/{gciPart}', [PlanningGciPartController::class, 'update'])->name('gci-parts.update');
        Route::delete('/gci-parts/{gciPart}', [PlanningGciPartController::class, 'destroy'])->name('gci-parts.destroy');

	        Route::get('/boms', [PlanningBomController::class, 'index'])->name('boms.index');
	        Route::get('/boms/export', [PlanningBomController::class, 'export'])->name('boms.export');
	        Route::post('/boms/import', [PlanningBomController::class, 'import'])->name('boms.import');
	        Route::post('/boms', [PlanningBomController::class, 'store'])->name('boms.store');
	        Route::put('/boms/{bom}', [PlanningBomController::class, 'update'])->name('boms.update');
	        Route::delete('/boms/{bom}', [PlanningBomController::class, 'destroy'])->name('boms.destroy');
	        Route::post('/boms/{bom}/items', [PlanningBomController::class, 'storeItem'])->name('boms.items.store');
	        Route::delete('/bom-items/{bomItem}', [PlanningBomController::class, 'destroyItem'])->name('boms.items.destroy');
	        Route::post('/bom-items/{bomItem}/substitutes', [PlanningBomController::class, 'storeSubstitute'])->name('bom-items.substitutes.store');
	        Route::delete('/bom-item-substitutes/{substitute}', [PlanningBomController::class, 'destroySubstitute'])->name('bom-item-substitutes.destroy');

        Route::get('/customers', [PlanningCustomerController::class, 'index'])->name('customers.index');
        Route::post('/customers', [PlanningCustomerController::class, 'store'])->name('customers.store');
        Route::put('/customers/{customer}', [PlanningCustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [PlanningCustomerController::class, 'destroy'])->name('customers.destroy');

	    Route::get('/customer-parts', [PlanningCustomerPartController::class, 'index'])->name('customer-parts.index');
	    Route::get('/customer-parts/export', [PlanningCustomerPartController::class, 'export'])->name('customer-parts.export');
	    Route::post('/customer-parts/import', [PlanningCustomerPartController::class, 'import'])->name('customer-parts.import');
	        Route::post('/customer-parts', [PlanningCustomerPartController::class, 'store'])->name('customer-parts.store');
	        Route::put('/customer-parts/{customerPart}', [PlanningCustomerPartController::class, 'update'])->name('customer-parts.update');
	        Route::delete('/customer-parts/{customerPart}', [PlanningCustomerPartController::class, 'destroy'])->name('customer-parts.destroy');
	        Route::post('/customer-parts/{customerPart}/components', [PlanningCustomerPartController::class, 'storeComponent'])->name('customer-parts.components.store');
	        Route::delete('/customer-part-components/{component}', [PlanningCustomerPartController::class, 'destroyComponent'])->name('customer-parts.components.destroy');

	        Route::get('/planning-imports', [PlanningCustomerPlanningImportController::class, 'index'])->name('planning-imports.index');
	        Route::post('/planning-imports', [PlanningCustomerPlanningImportController::class, 'store'])->name('planning-imports.store');
	        Route::get('/planning-imports/template', [PlanningCustomerPlanningImportController::class, 'template'])->name('planning-imports.template');
	        Route::get('/planning-imports/template-monthly', [PlanningCustomerPlanningImportController::class, 'templateMonthly'])->name('planning-imports.template-monthly');
	    Route::get('/planning-imports/{import}/export', [PlanningCustomerPlanningImportController::class, 'export'])->name('planning-imports.export');

        Route::get('/gci-parts/export', [PlanningGciPartController::class, 'export'])->name('gci-parts.export');
        Route::post('/gci-parts/import', [PlanningGciPartController::class, 'import'])->name('gci-parts.import');

        Route::get('/customer-pos', [PlanningCustomerPoController::class, 'index'])->name('customer-pos.index');
        Route::post('/customer-pos', [PlanningCustomerPoController::class, 'store'])->name('customer-pos.store');
        Route::put('/customer-pos/{customerPo}', [PlanningCustomerPoController::class, 'update'])->name('customer-pos.update');
        Route::delete('/customer-pos/{customerPo}', [PlanningCustomerPoController::class, 'destroy'])->name('customer-pos.destroy');

        Route::get('/forecasts', [PlanningForecastController::class, 'index'])->name('forecasts.index');
        Route::post('/forecasts/generate', [PlanningForecastController::class, 'generate'])->name('forecasts.generate');

	        Route::get('/mps', [PlanningMpsController::class, 'index'])->name('mps.index');
	        Route::post('/mps/generate', [PlanningMpsController::class, 'generate'])->name('mps.generate');
	        Route::post('/mps/generate-range', [PlanningMpsController::class, 'generateRange'])->name('mps.generate-range');
	        Route::post('/mps/upsert', [PlanningMpsController::class, 'upsert'])->name('mps.upsert');
	        Route::post('/mps/approve', [PlanningMpsController::class, 'approve'])->name('mps.approve');
	        Route::put('/mps/{mps}', [PlanningMpsController::class, 'update'])->name('mps.update');

        Route::get('/mrp', [PlanningMrpController::class, 'index'])->name('mrp.index');
        Route::post('/mrp/generate', [PlanningMrpController::class, 'generate'])->name('mrp.generate');
    });
});

require __DIR__.'/auth.php';
