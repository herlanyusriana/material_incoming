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
Route::get('/departures/{departure}/export-detail', [ArrivalController::class, 'exportDetail'])->name('departures.export-detail');

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
    Route::get('/local-pos/{arrival}', [LocalPoController::class, 'show'])->name('local-pos.show');
    Route::get('/local-pos/{arrival}/edit', [LocalPoController::class, 'edit'])->name('local-pos.edit');
    Route::put('/local-pos/{arrival}', [LocalPoController::class, 'update'])->name('local-pos.update');
    Route::delete('/local-pos/{arrival}', [LocalPoController::class, 'destroy'])->name('local-pos.destroy');

    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/receives', [InventoryController::class, 'receives'])->name('inventory.receives');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/export', [InventoryController::class, 'export'])->name('inventory.export');
    Route::post('/inventory/import', [InventoryController::class, 'import'])->name('inventory.import');
    Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

    // Inventory Transfers (Bridge between Logistics and Production)
    Route::get('/inventory/transfers', [\App\Http\Controllers\InventoryTransferController::class, 'index'])->name('inventory.transfers.index');
    Route::get('/inventory/transfers/create', [\App\Http\Controllers\InventoryTransferController::class, 'create'])->name('inventory.transfers.create');
    Route::post('/inventory/transfers', [\App\Http\Controllers\InventoryTransferController::class, 'store'])->name('inventory.transfers.store');
    Route::post('/inventory/transfers/auto-sync', [\App\Http\Controllers\InventoryTransferController::class, 'autoSync'])->name('inventory.transfers.auto-sync');

    Route::get('/inventory/locations', [WarehouseLocationController::class, 'index'])->name('inventory.locations.index');
    Route::post('/inventory/locations', [WarehouseLocationController::class, 'store'])->name('inventory.locations.store');
    Route::get('/inventory/locations/export', [WarehouseLocationController::class, 'export'])->name('inventory.locations.export');
    Route::post('/inventory/locations/import', [WarehouseLocationController::class, 'import'])->name('inventory.locations.import');
    Route::get('/inventory/locations/{location}/print', [WarehouseLocationController::class, 'printQr'])->name('inventory.locations.print');
    Route::put('/inventory/locations/{location}', [WarehouseLocationController::class, 'update'])->name('inventory.locations.update');
    Route::delete('/inventory/locations/{location}', [WarehouseLocationController::class, 'destroy'])->name('inventory.locations.destroy');

    // Warehouse Bin Transfers
    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/bin-transfers', [\App\Http\Controllers\BinTransferController::class, 'index'])->name('bin-transfers.index');
        Route::get('/bin-transfers/create', [\App\Http\Controllers\BinTransferController::class, 'create'])->name('bin-transfers.create');
        Route::post('/bin-transfers', [\App\Http\Controllers\BinTransferController::class, 'store'])->name('bin-transfers.store');
        Route::get('/bin-transfers/{binTransfer}', [\App\Http\Controllers\BinTransferController::class, 'show'])->name('bin-transfers.show');
        Route::get('/bin-transfers/{binTransfer}/label', [\App\Http\Controllers\BinTransferController::class, 'printLabel'])->name('bin-transfers.label');

        // AJAX endpoints
        Route::get('/api/location-stock', [\App\Http\Controllers\BinTransferController::class, 'getLocationStock'])->name('bin-transfers.location-stock');
        Route::get('/api/part-locations', [\App\Http\Controllers\BinTransferController::class, 'getPartLocations'])->name('bin-transfers.part-locations');
    });

    Route::prefix('outgoing')->name('outgoing.')->group(function () {
        Route::get('/daily-planning', [OutgoingController::class, 'dailyPlanning'])->name('daily-planning');
        Route::post('/daily-planning/create', [OutgoingController::class, 'createPlan'])->name('daily-planning.create');
        Route::post('/daily-planning/{plan}/row', [OutgoingController::class, 'storeRow'])->name('daily-planning.row');
        Route::post('/daily-planning/cell', [OutgoingController::class, 'updateCell'])->name('daily-planning.cell');
        Route::get('/daily-planning/template', [OutgoingController::class, 'dailyPlanningTemplate'])->name('daily-planning.template');
        Route::post('/daily-planning/import', [OutgoingController::class, 'dailyPlanningImport'])->name('daily-planning.import');
        Route::get('/daily-planning/{plan}/export', [OutgoingController::class, 'dailyPlanningExport'])->name('daily-planning.export');
        Route::get('/customer-po', [OutgoingController::class, 'customerPo'])->name('customer-po');
        Route::get('/product-mapping', [OutgoingController::class, 'productMapping'])->name('product-mapping');
        Route::get('/delivery-requirements', [OutgoingController::class, 'deliveryRequirements'])->name('delivery-requirements');
        Route::get('/stock-at-customers', [OutgoingController::class, 'stockAtCustomers'])->name('stock-at-customers');
        Route::get('/stock-at-customers/template', [OutgoingController::class, 'stockAtCustomersTemplate'])->name('stock-at-customers.template');
        Route::get('/stock-at-customers/export', [OutgoingController::class, 'stockAtCustomersExport'])->name('stock-at-customers.export');
        Route::post('/stock-at-customers/import', [OutgoingController::class, 'stockAtCustomersImport'])->name('stock-at-customers.import');

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

        Route::get('/delivery-plan', [OutgoingController::class, 'deliveryPlan'])->name('delivery-plan');
        Route::post('/delivery-plan', [OutgoingController::class, 'storeDeliveryPlan'])->name('delivery-plan.store');

        Route::resource('delivery-notes', \App\Http\Controllers\Outgoing\DeliveryNoteController::class);
        Route::post('delivery-notes/{delivery_note}/ship', [\App\Http\Controllers\Outgoing\DeliveryNoteController::class, 'ship'])->name('delivery-notes.ship');

        Route::resource('standard-packings', \App\Http\Controllers\Outgoing\StandardPackingController::class);
        Route::post('standard-packings/import', [\App\Http\Controllers\Outgoing\StandardPackingController::class, 'import'])->name('standard-packings.import');
        Route::get('standard-packings/export', [\App\Http\Controllers\Outgoing\StandardPackingController::class, 'export'])->name('standard-packings.export');
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
        Route::post('/boms/substitutes/import', [PlanningBomController::class, 'importSubstitutes'])->name('boms.substitutes.import');
        Route::get('/boms/substitutes/template', [PlanningBomController::class, 'downloadSubstituteTemplate'])->name('boms.substitutes.template');
        Route::put('/boms/{bom}', [PlanningBomController::class, 'update'])->name('boms.update');
        Route::delete('/boms/{bom}', [PlanningBomController::class, 'destroy'])->name('boms.destroy');
        Route::post('/boms/{bom}/items', [PlanningBomController::class, 'storeItem'])->name('boms.items.store');
        Route::get('/boms/where-used-page', [PlanningBomController::class, 'showWhereUsed'])->name('boms.where-used-page');
        Route::get('/boms/where-used', [PlanningBomController::class, 'whereUsed'])->name('boms.where-used');
        Route::get('/boms/explosion-search', [PlanningBomController::class, 'explosion'])->name('boms.explosion-search');
        Route::get('/boms/{bom}/explosion', [PlanningBomController::class, 'explosion'])->name('boms.explosion');
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

        // Classification-specific part routes
        Route::get('/fg-parts', [PlanningGciPartController::class, 'index'])->defaults('classification', 'FG')->name('fg-parts.index');
        Route::get('/wip-parts', [PlanningGciPartController::class, 'index'])->defaults('classification', 'WIP')->name('wip-parts.index');
        Route::get('/rm-parts', [PlanningGciPartController::class, 'index'])->defaults('classification', 'RM')->name('rm-parts.index');

        Route::get('/customer-pos', [PlanningCustomerPoController::class, 'index'])->name('customer-pos.index');
        Route::post('/customer-pos', [PlanningCustomerPoController::class, 'store'])->name('customer-pos.store');
        Route::put('/customer-pos/{customerPo}', [PlanningCustomerPoController::class, 'update'])->name('customer-pos.update');
        Route::delete('/customer-pos/{customerPo}', [PlanningCustomerPoController::class, 'destroy'])->name('customer-pos.destroy');

        Route::get('/forecasts', [PlanningForecastController::class, 'index'])->name('forecasts.index');
        Route::get('/forecasts/preview', [PlanningForecastController::class, 'preview'])->name('forecasts.preview');
        Route::post('/forecasts/generate', [PlanningForecastController::class, 'generate'])->name('forecasts.generate');
        Route::delete('/forecasts/clear', [PlanningForecastController::class, 'clear'])->name('forecasts.clear');
        Route::get('/forecasts/history', [PlanningForecastController::class, 'history'])->name('forecasts.history');

        Route::get('/mps', [PlanningMpsController::class, 'index'])->name('mps.index');
        Route::get('/mps/export', [PlanningMpsController::class, 'export'])->name('mps.export');
        Route::post('/mps/generate', [PlanningMpsController::class, 'generate'])->name('mps.generate');
        Route::post('/mps/generate-range', [PlanningMpsController::class, 'generateRange'])->name('mps.generate-range');
        Route::post('/mps/upsert', [PlanningMpsController::class, 'upsert'])->name('mps.upsert');
        Route::post('/mps/approve', [PlanningMpsController::class, 'approve'])->name('mps.approve');
        Route::post('/mps/approve-monthly', [PlanningMpsController::class, 'approveMonthly'])->name('mps.approve-monthly');
        Route::get('/mps/detail', [PlanningMpsController::class, 'detail'])->name('mps.detail');
        Route::put('/mps/{mps}', [PlanningMpsController::class, 'update'])->name('mps.update');
        Route::delete('/mps/clear', [PlanningMpsController::class, 'clear'])->name('mps.clear');
        Route::get('/mps/history', [PlanningMpsController::class, 'history'])->name('mps.history');

        Route::get('/mrp', [PlanningMrpController::class, 'index'])->name('mrp.index');
        Route::post('/mrp/generate', [PlanningMrpController::class, 'generate'])->name('mrp.generate');
        Route::post('/mrp/generate-range', [PlanningMrpController::class, 'generateRange'])->name('mrp.generate-range');
        Route::post('/mrp/generate-po', [PlanningMrpController::class, 'generatePo'])->name('mrp.generate-po');
        Route::delete('/mrp/clear', [PlanningMrpController::class, 'clear'])->name('mrp.clear');
        Route::get('/mrp/history', [PlanningMrpController::class, 'history'])->name('mrp.history');
    });

    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/labels', [App\Http\Controllers\BarcodeLabelController::class, 'index'])->name('labels.index');
        Route::get('/labels/part/{part}', [App\Http\Controllers\BarcodeLabelController::class, 'printPartLabel'])->name('labels.part');
        Route::post('/labels/bulk', [App\Http\Controllers\BarcodeLabelController::class, 'printBulkLabels'])->name('labels.bulk');
    });

    Route::prefix('production')->name('production.')->group(function () {
        Route::resource('orders', \App\Http\Controllers\ProductionOrderController::class);
        Route::post('/orders/{order}/check-material', [\App\Http\Controllers\ProductionOrderController::class, 'checkMaterial'])->name('orders.check-material');
        Route::post('/orders/{order}/start', [\App\Http\Controllers\ProductionOrderController::class, 'startProduction'])->name('orders.start');
        Route::post('/orders/{order}/finish', [\App\Http\Controllers\ProductionOrderController::class, 'finishProduction'])->name('orders.finish');

        Route::post('/orders/{order}/inspections', [\App\Http\Controllers\ProductionInspectionController::class, 'store'])->name('inspections.store');
        Route::put('/inspections/{inspection}', [\App\Http\Controllers\ProductionInspectionController::class, 'update'])->name('inspections.update');
    });
});

require __DIR__ . '/auth.php';
