<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OutgoingController;
use App\Http\Controllers\Outgoing\OutgoingPoController;
use App\Http\Controllers\Outgoing\PickingFgController;
use App\Http\Controllers\Outgoing\DeliveryOrderController;
use App\Http\Controllers\Outgoing\OspController;

// Outgoing / FG Delivery
Route::middleware('can:manage_outgoing')->prefix('outgoing')->name('outgoing.')->group(function () {
    Route::get('/daily-planning', [OutgoingController::class, 'dailyPlanning'])->name('daily-planning');
    Route::post('/daily-planning/create', [OutgoingController::class, 'createPlan'])->name('daily-planning.create');
    Route::post('/daily-planning/{plan}/row', [OutgoingController::class, 'storeRow'])->name('daily-planning.row');
    Route::post('/daily-planning/cell', [OutgoingController::class, 'updateCell'])->name('daily-planning.cell');
    Route::get('/daily-planning/template', [OutgoingController::class, 'dailyPlanningTemplate'])->name('daily-planning.template');
    Route::post('/daily-planning/import', [OutgoingController::class, 'dailyPlanningImport'])->name('daily-planning.import');
    Route::get('/daily-planning/{plan}/export', [OutgoingController::class, 'dailyPlanningExport'])->name('daily-planning.export');
    Route::get('/customer-po', [OutgoingController::class, 'customerPo'])->name('customer-po');
    Route::get('/product-mapping', [OutgoingController::class, 'productMapping'])->name('product-mapping');
    Route::get('/where-used', [OutgoingController::class, 'whereUsed'])->name('where-used');
    Route::get('/delivery-requirements', [OutgoingController::class, 'deliveryRequirements'])->name('delivery-requirements');
    Route::get('/delivery-requirements/export', [OutgoingController::class, 'deliveryRequirementsExport'])->name('delivery-requirements.export');
    Route::post('/delivery-requirements/generate-do', [OutgoingController::class, 'generateDo'])->name('generate-do');
    Route::post('/delivery-requirements/generate-do-bulk', [OutgoingController::class, 'generateDoBulk'])->name('generate-do-bulk');
    Route::get('/stock-at-customers', [OutgoingController::class, 'stockAtCustomers'])->name('stock-at-customers');
    Route::get('/stock-at-customers/template', [OutgoingController::class, 'stockAtCustomersTemplate'])->name('stock-at-customers.template');
    Route::get('/stock-at-customers/export', [OutgoingController::class, 'stockAtCustomersExport'])->name('stock-at-customers.export');
    Route::post('/stock-at-customers/import', [OutgoingController::class, 'stockAtCustomersImport'])->name('stock-at-customers.import');

    // Input JIG Routes
    Route::get('/input-jig', [\App\Http\Controllers\OutgoingJigController::class, 'index'])->name('input-jig');
    Route::post('/input-jig', [\App\Http\Controllers\OutgoingJigController::class, 'storeRow'])->name('input-jig.store');
    Route::post('/input-jig/{setting}/uph', [\App\Http\Controllers\OutgoingJigController::class, 'updateUph'])->name('input-jig.uph');
    Route::post('/input-jig/{setting}/plan', [\App\Http\Controllers\OutgoingJigController::class, 'updatePlan'])->name('input-jig.plan');
    Route::delete('/input-jig/{setting}', [\App\Http\Controllers\OutgoingJigController::class, 'deleteRow'])->name('input-jig.delete');

    Route::get('/trucks', [\App\Http\Controllers\Outgoing\TruckController::class, 'index'])->name('trucks.index');
    Route::post('/trucks', [\App\Http\Controllers\Outgoing\TruckController::class, 'store'])->name('trucks.store');
    Route::put('/trucks/{truck}', [\App\Http\Controllers\Outgoing\TruckController::class, 'update'])->name('trucks.update');
    Route::delete('/trucks/{truck}', [\App\Http\Controllers\Outgoing\TruckController::class, 'destroy'])->name('trucks.destroy');
    Route::get('/trucks/template', [\App\Http\Controllers\Outgoing\TruckController::class, 'template'])->name('trucks.template');
    Route::get('/trucks/export', [\App\Http\Controllers\Outgoing\TruckController::class, 'export'])->name('trucks.export');
    Route::post('/trucks/import', [\App\Http\Controllers\Outgoing\TruckController::class, 'import'])->name('trucks.import');

    Route::get('/drivers', [\App\Http\Controllers\Outgoing\DriverController::class, 'index'])->name('drivers.index');
    Route::post('/drivers', [\App\Http\Controllers\Outgoing\DriverController::class, 'store'])->name('drivers.store');
    Route::put('/drivers/{driver}', [\App\Http\Controllers\Outgoing\DriverController::class, 'update'])->name('drivers.update');
    Route::delete('/drivers/{driver}', [\App\Http\Controllers\Outgoing\DriverController::class, 'destroy'])->name('drivers.destroy');
    Route::get('/drivers/template', [\App\Http\Controllers\Outgoing\DriverController::class, 'template'])->name('drivers.template');
    Route::get('/drivers/export', [\App\Http\Controllers\Outgoing\DriverController::class, 'export'])->name('drivers.export');
    Route::post('/drivers/import', [\App\Http\Controllers\Outgoing\DriverController::class, 'import'])->name('drivers.import');

    // Customer PO Routes
    Route::get('/customer-po', [OutgoingPoController::class, 'index'])->name('customer-po.index');
    Route::get('/customer-po/create', [OutgoingPoController::class, 'create'])->name('customer-po.create');
    Route::post('/customer-po', [OutgoingPoController::class, 'store'])->name('customer-po.store');
    Route::get('/customer-po/search-parts', [OutgoingPoController::class, 'searchParts'])->name('customer-po.search-parts');
    Route::get('/customer-po/{outgoingPo}', [OutgoingPoController::class, 'show'])->name('customer-po.show');
    Route::post('/customer-po/{outgoingPo}/confirm', [OutgoingPoController::class, 'confirm'])->name('customer-po.confirm');
    Route::post('/customer-po/{outgoingPo}/complete', [OutgoingPoController::class, 'complete'])->name('customer-po.complete');
    Route::post('/customer-po/{outgoingPo}/cancel', [OutgoingPoController::class, 'cancel'])->name('customer-po.cancel');

    Route::get('/delivery-plan', [OutgoingController::class, 'deliveryPlan'])->name('delivery-plan');
    Route::post('/delivery-plan/update-trips', [OutgoingController::class, 'updateDeliveryPlanTrips'])->name('delivery-plan.update-trips');
    Route::post('/delivery-plan/update-trip', [OutgoingController::class, 'updateDeliveryPlanTrip'])->name('delivery-plan.update-trip');
    Route::post('/delivery-plan/generate-do', [OutgoingController::class, 'generateDoFromDeliveryPlan'])->name('delivery-plan.generate-do');

    // Picking FG Routes
    Route::get('/picking-fg', [PickingFgController::class, 'index'])->name('picking-fg');
    Route::get('/picking-fg/status', [PickingFgController::class, 'statusJson'])->name('picking-fg.status');
    Route::post('/picking-fg/generate', [PickingFgController::class, 'generate'])->name('picking-fg.generate');
    Route::post('/picking-fg/update-pick', [PickingFgController::class, 'updatePick'])->name('picking-fg.update-pick');
    Route::post('/picking-fg/complete-all', [PickingFgController::class, 'completeAll'])->name('picking-fg.complete-all');
    Route::post('/picking-fg/clear', [PickingFgController::class, 'clear'])->name('picking-fg.clear');

    Route::resource('delivery-orders', DeliveryOrderController::class);
    Route::post('delivery-orders/{delivery_order}/ship', [DeliveryOrderController::class, 'ship'])->name('delivery-orders.ship');

    Route::resource('delivery-notes', \App\Http\Controllers\Outgoing\DeliveryNoteController::class);
    Route::post('delivery-notes/{delivery_note}/start-kitting', [\App\Http\Controllers\Outgoing\DeliveryNoteController::class, 'startKitting'])->name('delivery-notes.start-kitting');
    Route::post('delivery-notes/{delivery_note}/complete-kitting', [\App\Http\Controllers\Outgoing\DeliveryNoteController::class, 'completeKitting'])->name('delivery-notes.complete-kitting');
    Route::post('delivery-notes/{delivery_note}/start-picking', [\App\Http\Controllers\Outgoing\DeliveryNoteController::class, 'startPicking'])->name('delivery-notes.start-picking');
    Route::post('delivery-notes/{delivery_note}/complete-picking', [\App\Http\Controllers\Outgoing\DeliveryNoteController::class, 'completePicking'])->name('delivery-notes.complete-picking');
    Route::get('delivery-notes/{delivery_note}/picking-scan', [\App\Http\Controllers\Outgoing\DeliveryNoteController::class, 'pickingScan'])->name('delivery-notes.picking-scan');
    Route::post('delivery-notes/{delivery_note}/picking-scan', [\App\Http\Controllers\Outgoing\DeliveryNoteController::class, 'pickingScanStore'])->name('delivery-notes.picking-scan.store');
    Route::post('delivery-notes/{delivery_note}/ship', [\App\Http\Controllers\Outgoing\DeliveryNoteController::class, 'ship'])->name('delivery-notes.ship');
    Route::get('delivery-notes/{delivery_note}/print', [\App\Http\Controllers\Outgoing\DeliveryNoteController::class, 'print'])->name('delivery-notes.print');
    Route::get('delivery-notes/{delivery_note}/packing-list', [\App\Http\Controllers\Outgoing\DeliveryNoteController::class, 'printPackingList'])->name('delivery-notes.packing-list');
    Route::get('delivery-notes/{delivery_note}/invoice', [\App\Http\Controllers\Outgoing\DeliveryNoteController::class, 'printInvoice'])->name('delivery-notes.print-invoice');

    Route::get('standard-packings/template', [\App\Http\Controllers\Outgoing\StandardPackingController::class, 'template'])->name('standard-packings.template');
    Route::get('standard-packings/export', [\App\Http\Controllers\Outgoing\StandardPackingController::class, 'export'])->name('standard-packings.export');
    Route::post('standard-packings/import', [\App\Http\Controllers\Outgoing\StandardPackingController::class, 'import'])->name('standard-packings.import');
    Route::resource('standard-packings', \App\Http\Controllers\Outgoing\StandardPackingController::class);

    // OSP Routes
    Route::get('/osp', [OspController::class, 'index'])->name('osp.index');
    Route::get('/osp/create', [OspController::class, 'create'])->name('osp.create');
    Route::post('/osp', [OspController::class, 'store'])->name('osp.store');
    Route::get('/osp/{ospOrder}', [OspController::class, 'show'])->name('osp.show');
    Route::get('/osp/{ospOrder}/print-dn', [OspController::class, 'printDeliveryNote'])->name('osp.print-dn');
    Route::get('/osp/{ospOrder}/print-pl', [OspController::class, 'printPackingList'])->name('osp.print-pl');
    Route::get('/osp/{ospOrder}/print-invoice', [OspController::class, 'printInvoice'])->name('osp.print-invoice');
    Route::post('/osp/{ospOrder}/progress', [OspController::class, 'updateProgress'])->name('osp.progress');
    Route::post('/osp/{ospOrder}/ship', [OspController::class, 'ship'])->name('osp.ship');
});

// Delivery Management (Outgoing)
Route::middleware('can:manage_outgoing')->prefix('delivery')->name('delivery.')->group(function () {
    Route::prefix('outgoing')->name('outgoing.')->group(function () {
        Route::get('/', [App\Http\Controllers\Delivery\DeliveryOutgoingController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Delivery\DeliveryOutgoingController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Delivery\DeliveryOutgoingController::class, 'store'])->name('store');
        Route::get('/{deliveryNote}', [App\Http\Controllers\Delivery\DeliveryOutgoingController::class, 'show'])->name('show');
        Route::get('/{deliveryNote}/edit', [App\Http\Controllers\Delivery\DeliveryOutgoingController::class, 'edit'])->name('edit');
        Route::put('/{deliveryNote}', [App\Http\Controllers\Delivery\DeliveryOutgoingController::class, 'update'])->name('update');
        Route::post('/{deliveryNote}/assign-truck', [App\Http\Controllers\Delivery\DeliveryOutgoingController::class, 'assignTruck'])->name('assign-truck');
        Route::post('/{deliveryNote}/assign-driver', [App\Http\Controllers\Delivery\DeliveryOutgoingController::class, 'assignDriver'])->name('assign-driver');
        Route::post('/{deliveryNote}/update-status', [App\Http\Controllers\Delivery\DeliveryOutgoingController::class, 'updateStatus'])->name('update-status');
        Route::get('/ajax/get-ready-orders', [App\Http\Controllers\Delivery\DeliveryOutgoingController::class, 'getReadyOrders'])->name('get-ready-orders');
    });
});
