<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\ArrivalController;
use App\Http\Controllers\ReceiveController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\GciInventoryController;
use App\Http\Controllers\WarehouseLocationController;
use App\Http\Controllers\LocalPoController;
use App\Http\Controllers\OutgoingController;
use App\Http\Controllers\Outgoing\DeliveryOrderController;
use App\Http\Controllers\Outgoing\OspController;
use App\Http\Controllers\Outgoing\PickingFgController;
use App\Http\Controllers\Outgoing\OutgoingPoController;
use App\Http\Controllers\SubconController;
use App\Http\Controllers\TruckingController;
use App\Http\Controllers\LogisticsDashboardController;
use App\Http\Controllers\WarehousePutawayController;
use App\Http\Controllers\WarehouseQcController;
use App\Http\Controllers\Planning\CustomerController as PlanningCustomerController;
use App\Http\Controllers\Planning\BomController as PlanningBomController;
use App\Http\Controllers\Planning\GciPartController as PlanningGciPartController;
use App\Http\Controllers\Planning\CustomerPartController as PlanningCustomerPartController;
use App\Http\Controllers\Planning\CustomerPlanningImportController as PlanningCustomerPlanningImportController;
use App\Http\Controllers\Planning\CustomerPoController as PlanningCustomerPoController;
use App\Http\Controllers\Planning\ForecastController as PlanningForecastController;
use App\Http\Controllers\Planning\MpsController as PlanningMpsController;
use App\Http\Controllers\Planning\MrpController as PlanningMrpController;
use App\Http\Controllers\Purchasing\PurchaseRequestController;
use App\Http\Controllers\Purchasing\PurchaseOrderController;
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
    Route::get('/api/parts/search', [PartController::class, 'search'])->name('parts.search');
    Route::get('/api/gci-parts/search', [PlanningGciPartController::class, 'search'])->name('gci-parts.search');
    Route::get('/api/gci-parts/{gciPart}/bom-info', [PlanningGciPartController::class, 'getBomInfo'])->name('gci-parts.bom-info');

    // Traceability suggest endpoints (FIFO)
    Route::get('/api/suggest-arrivals/{gciPartId}', function (int $gciPartId) {
        // Find RM components for this FG part via BOM
        $bom = \App\Models\Bom::where('part_id', $gciPartId)->first();
        if (!$bom) {
            return response()->json([]);
        }

        $componentPartIds = \App\Models\BomItem::where('bom_id', $bom->id)
            ->whereNotNull('incoming_part_id')
            ->pluck('incoming_part_id')
            ->unique();

        if ($componentPartIds->isEmpty()) {
            // Fallback: find arrivals with items linked to any RM parts mapped to this gci_part
            $rmPartIds = \App\Models\Part::where('gci_part_id', $gciPartId)->pluck('id');
            if ($rmPartIds->isEmpty()) {
                return response()->json([]);
            }
            $arrivalIds = \App\Models\ArrivalItem::whereIn('part_id', $rmPartIds)
                ->pluck('arrival_id')->unique();
        } else {
            $arrivalIds = \App\Models\ArrivalItem::whereIn('part_id', $componentPartIds)
                ->pluck('arrival_id')->unique();
        }

        $arrivals = \App\Models\Arrival::whereIn('id', $arrivalIds)
            ->whereNotNull('transaction_no')
            ->orderBy('created_at', 'asc') // FIFO
            ->limit(20)
            ->get(['id', 'arrival_no', 'transaction_no', 'invoice_no', 'created_at']);

        return response()->json($arrivals);
    })->name('api.suggest-arrivals');

    Route::get('/api/suggest-production-orders/{gciPartId}', function (int $gciPartId) {
        $orders = \App\Models\ProductionOrder::where('gci_part_id', $gciPartId)
            ->whereNotNull('transaction_no')
            ->orderBy('created_at', 'asc') // FIFO
            ->limit(20)
            ->get(['id', 'production_order_number', 'transaction_no', 'plan_date', 'status']);

        return response()->json($orders);
    })->name('api.suggest-production-orders');
    Route::view('/incoming-material', 'incoming-material.dashboard')->name('incoming-material.dashboard');
    Route::get('/logistics', [LogisticsDashboardController::class, 'index'])->name('logistics.dashboard');
    Route::resource('vendors', VendorController::class)->except(['show']);
    Route::get('/vendors/export', [VendorController::class, 'export'])->name('vendors.export');
    Route::post('/vendors/import', [VendorController::class, 'import'])->name('vendors.import');
    Route::get('/parts', [PartController::class, 'index'])->name('parts.index');
    Route::post('/parts', [PartController::class, 'store'])->name('parts.store');
    Route::put('/parts/{part}', [PartController::class, 'update'])->name('parts.update');
    Route::delete('/parts/{part}', [PartController::class, 'destroy'])->name('parts.destroy');
    Route::get('/parts/export', [PartController::class, 'export'])->name('parts.export');
    Route::post('/parts/import', [PartController::class, 'import'])->name('parts.import');
    // Vendor part CRUD under a GCI part
    Route::post('/parts/{part}/vendor-parts', [PartController::class, 'storeVendorPart'])->name('parts.vendor-parts.store');
    Route::put('/vendor-parts/{vendorPart}', [PartController::class, 'updateVendorPart'])->name('parts.vendor-parts.update');
    Route::delete('/vendor-parts/{vendorPart}', [PartController::class, 'destroyVendorPart'])->name('parts.vendor-parts.destroy');
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
    Route::get('/inventory/receives/search', [InventoryController::class, 'searchReceives'])->name('inventory.receives.search');
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
    Route::get('/inventory/locations/print-map', [WarehouseLocationController::class, 'printMap'])->name('inventory.locations.print-map');
    Route::get('/inventory/locations/print-range', [WarehouseLocationController::class, 'printRange'])->name('inventory.locations.print-range');
    Route::get('/inventory/locations/{location}/print', [WarehouseLocationController::class, 'printQr'])->name('inventory.locations.print');
    Route::put('/inventory/locations/{location}', [WarehouseLocationController::class, 'update'])->name('inventory.locations.update');
    Route::delete('/inventory/locations/{location}', [WarehouseLocationController::class, 'destroy'])->name('inventory.locations.destroy');

    // Warehouse Bin Transfers
    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/qc', [WarehouseQcController::class, 'index'])->name('qc.index');
        Route::post('/qc/{receive}', [WarehouseQcController::class, 'update'])->name('qc.update');

        Route::get('/putaway', [WarehousePutawayController::class, 'index'])->name('putaway.index');
        Route::post('/putaway/{receive}', [WarehousePutawayController::class, 'store'])->name('putaway.store');
        Route::post('/putaway', [WarehousePutawayController::class, 'bulk'])->name('putaway.bulk');

        Route::get('/bin-transfers', [\App\Http\Controllers\BinTransferController::class, 'index'])->name('bin-transfers.index');
        Route::get('/bin-transfers/create', [\App\Http\Controllers\BinTransferController::class, 'create'])->name('bin-transfers.create');
        Route::post('/bin-transfers', [\App\Http\Controllers\BinTransferController::class, 'store'])->name('bin-transfers.store');
        Route::get('/bin-transfers/{binTransfer}', [\App\Http\Controllers\BinTransferController::class, 'show'])->name('bin-transfers.show');
        Route::get('/bin-transfers/{binTransfer}/label', [\App\Http\Controllers\BinTransferController::class, 'printLabel'])->name('bin-transfers.label');

        // AJAX endpoints
        Route::get('/api/location-stock', [\App\Http\Controllers\BinTransferController::class, 'getLocationStock'])->name('bin-transfers.location-stock');
        Route::get('/api/part-locations', [\App\Http\Controllers\BinTransferController::class, 'getPartLocations'])->name('bin-transfers.part-locations');

        // Warehouse stock (by location)
        Route::get('/stock', [\App\Http\Controllers\WarehouseStockController::class, 'index'])->name('stock.index');
        Route::get('/stock/reconcile', [\App\Http\Controllers\WarehouseStockController::class, 'reconcile'])->name('stock.reconcile');

        // Warehouse stock adjustments
        Route::get('/stock-adjustments', [\App\Http\Controllers\WarehouseStockAdjustmentController::class, 'index'])->name('stock-adjustments.index');
        Route::get('/stock-adjustments/create', [\App\Http\Controllers\WarehouseStockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
        Route::post('/stock-adjustments', [\App\Http\Controllers\WarehouseStockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
        Route::get('/api/stock-adjustments/batches', [\App\Http\Controllers\WarehouseStockAdjustmentController::class, 'getBatches'])->name('stock-adjustments.get-batches');

        // Production load (schedule for warehouse)
        Route::get('/production-load', [\App\Http\Controllers\WarehouseProductionLoadController::class, 'index'])->name('production-load.index');
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

        Route::get('standard-packings/template', [\App\Http\Controllers\Outgoing\StandardPackingController::class, 'template'])->name('standard-packings.template');
        Route::get('standard-packings/export', [\App\Http\Controllers\Outgoing\StandardPackingController::class, 'export'])->name('standard-packings.export');
        Route::post('standard-packings/import', [\App\Http\Controllers\Outgoing\StandardPackingController::class, 'import'])->name('standard-packings.import');
        Route::resource('standard-packings', \App\Http\Controllers\Outgoing\StandardPackingController::class);

        // OSP Routes
        Route::get('/osp', [OspController::class, 'index'])->name('osp.index');
        Route::get('/osp/create', [OspController::class, 'create'])->name('osp.create');
        Route::post('/osp', [OspController::class, 'store'])->name('osp.store');
        Route::get('/osp/{ospOrder}', [OspController::class, 'show'])->name('osp.show');
        Route::post('/osp/{ospOrder}/progress', [OspController::class, 'updateProgress'])->name('osp.progress');
        Route::post('/osp/{ospOrder}/ship', [OspController::class, 'ship'])->name('osp.ship');
    });

    // Subcon Routes
    Route::prefix('subcon')->name('subcon.')->group(function () {
        Route::get('/', [SubconController::class, 'index'])->name('index');
        Route::get('/create', [SubconController::class, 'create'])->name('create');
        Route::get('/api/parts', [SubconController::class, 'parts'])->name('parts');
        Route::post('/', [SubconController::class, 'store'])->name('store');
        Route::get('/{subconOrder}', [SubconController::class, 'show'])->name('show');
        Route::post('/{subconOrder}/receive', [SubconController::class, 'receive'])->name('receive');
        Route::post('/{subconOrder}/cancel', [SubconController::class, 'cancel'])->name('cancel');
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
        Route::get('/boms/substitutes', [PlanningBomController::class, 'substitutes'])->name('boms.substitutes.index');
        Route::get('/boms/substitutes/export', [PlanningBomController::class, 'exportSubstitutes'])->name('boms.substitutes.export');
        Route::post('/boms/substitutes/import', [PlanningBomController::class, 'importSubstitutes'])->name('boms.substitutes.import');
        Route::post('/boms/substitutes/import-mapping', [PlanningBomController::class, 'importSubstitutesMapping'])->name('boms.substitutes.import-mapping');
        Route::get('/boms/substitutes/template', [PlanningBomController::class, 'downloadSubstituteTemplate'])->name('boms.substitutes.template');
        Route::get('/boms/substitutes/template-mapping', [PlanningBomController::class, 'downloadSubstituteMappingTemplate'])->name('boms.substitutes.template-mapping');
        Route::put('/boms/{bom}', [PlanningBomController::class, 'update'])->name('boms.update');
        Route::delete('/boms/{bom}', [PlanningBomController::class, 'destroy'])->name('boms.destroy');
        Route::post('/boms/{bom}/items', [PlanningBomController::class, 'storeItem'])->name('boms.items.store');
        Route::get('/boms/where-used-page', [PlanningBomController::class, 'showWhereUsed'])->name('boms.where-used-page');
        Route::get('/boms/where-used', [PlanningBomController::class, 'whereUsed'])->name('boms.where-used');
        Route::get('/boms/explosion-search', [PlanningBomController::class, 'explosion'])->name('boms.explosion-search');
        Route::get('/boms/{bom}/explosion', [PlanningBomController::class, 'explosion'])->name('boms.explosion');
        Route::delete('/bom-items/{bomItem}', [PlanningBomController::class, 'destroyItem'])->name('boms.items.destroy');
        Route::post('/bom-items/{bomItem}/substitutes', [PlanningBomController::class, 'storeSubstitute'])->name('bom-items.substitutes.store');
        Route::delete('/bom-item-substitutes/truncate', [PlanningBomController::class, 'truncateSubstitutes'])->name('boms.substitutes.truncate');
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
        Route::get('/mrp/integration', [PlanningMrpController::class, 'integrationDashboard'])->name('mrp.integration-dashboard');
    });

    Route::prefix('delivery')->name('delivery.')->group(function () {
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

    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/labels', [App\Http\Controllers\BarcodeLabelController::class, 'index'])->name('labels.index');
        Route::get('/labels/part/{part}', [App\Http\Controllers\BarcodeLabelController::class, 'printPartLabel'])->name('labels.part');
        Route::post('/labels/bulk', [App\Http\Controllers\BarcodeLabelController::class, 'printBulkLabels'])->name('labels.bulk');

        // Stock Opname
        Route::resource('stock-opname', \App\Http\Controllers\StockOpnameController::class);
        Route::post('stock-opname/{session}/close', [\App\Http\Controllers\StockOpnameController::class, 'close'])->name('stock-opname.close');
        Route::post('stock-opname/{session}/adjust', [\App\Http\Controllers\StockOpnameController::class, 'adjust'])->name('stock-opname.adjust');

        // Trollies
        Route::get('trollies/export', [\App\Http\Controllers\TrollyController::class, 'export'])->name('trollies.export');
        Route::post('trollies/import', [\App\Http\Controllers\TrollyController::class, 'import'])->name('trollies.import');
        Route::get('trollies/print-range', [\App\Http\Controllers\TrollyController::class, 'printRange'])->name('trollies.print-range');
        Route::resource('trollies', \App\Http\Controllers\TrollyController::class);
        Route::get('trollies/{trolly}/print', [\App\Http\Controllers\TrollyController::class, 'printQr'])->name('trollies.print');
    });

    Route::prefix('production')->name('production.')->group(function () {
        // Production Planning (GCI Planning Produksi)
        Route::get('/planning', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'index'])->name('planning.index');
        Route::post('/planning/create-session', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'createSession'])->name('planning.create-session');
        Route::post('/planning/auto-populate', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'autoPopulate'])->name('planning.auto-populate');
        Route::post('/planning/add-line', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'addLine'])->name('planning.add-line');
        Route::put('/planning/line/{line}', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'updateLine'])->name('planning.update-line');
        Route::delete('/planning/line/{line}', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'deleteLine'])->name('planning.delete-line');
        Route::post('/planning/generate-mo', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'generateMoWo'])->name('planning.generate-mo');
        Route::post('/planning/generate-mo-line', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'generateMoWoLine'])->name('planning.generate-mo-line');
        Route::get('/planning/calculations', [\App\Http\Controllers\Production\ProductionPlanningController::class, 'getCalculations'])->name('planning.calculations');

        // Production Orders
        Route::resource('orders', \App\Http\Controllers\ProductionOrderController::class);
        Route::post('/orders/{order}/release-kanban', [\App\Http\Controllers\ProductionOrderController::class, 'releaseKanban'])->name('orders.release-kanban');
        Route::post('/orders/{order}/check-material', [\App\Http\Controllers\ProductionOrderController::class, 'checkMaterial'])->name('orders.check-material');
        Route::post('/orders/{order}/start', [\App\Http\Controllers\ProductionOrderController::class, 'startProduction'])->name('orders.start');
        Route::post('/orders/{order}/finish', [\App\Http\Controllers\ProductionOrderController::class, 'finishProduction'])->name('orders.finish');
        Route::post('/orders/{order}/kanban-update', [\App\Http\Controllers\ProductionOrderController::class, 'kanbanUpdate'])->name('orders.kanban-update');

        // GCI Operator Dashboard (From Flutter App Sync)
        Route::get('/gci-dashboard', [\App\Http\Controllers\Production\ProductionGciWebController::class, 'index'])->name('gci-dashboard.index');
        Route::get('/gci-dashboard/{id}', [\App\Http\Controllers\Production\ProductionGciWebController::class, 'show'])->name('gci-dashboard.show');

        // Production Downtimes (QDC, Breaks, etc.)
        Route::post('/orders/{productionOrder}/downtimes', [\App\Http\Controllers\Production\ProductionDowntimeController::class, 'store'])->name('downtimes.store');
        Route::put('/orders/{productionOrder}/downtimes/{downtime}', [\App\Http\Controllers\Production\ProductionDowntimeController::class, 'update'])->name('downtimes.update');
        Route::delete('/orders/{productionOrder}/downtimes/{downtime}', [\App\Http\Controllers\Production\ProductionDowntimeController::class, 'destroy'])->name('downtimes.destroy');

        // Work Order & Kanban Release
        Route::get('/work-orders', [\App\Http\Controllers\Production\WorkOrderController::class, 'index'])->name('work-orders.index');
        Route::get('/work-orders/{order}', [\App\Http\Controllers\Production\WorkOrderController::class, 'show'])->name('work-orders.show');
        Route::post('/work-orders/{order}/release', [\App\Http\Controllers\Production\WorkOrderController::class, 'releaseKanban'])->name('work-orders.release');
        Route::post('/work-orders/bulk-release', [\App\Http\Controllers\Production\WorkOrderController::class, 'bulkRelease'])->name('work-orders.bulk-release');

        // Material Availability
        Route::get('/material-availability', [\App\Http\Controllers\Production\MaterialAvailabilityController::class, 'index'])->name('material-availability.index');
        Route::get('/material-availability/{order}', [\App\Http\Controllers\Production\MaterialAvailabilityController::class, 'show'])->name('material-availability.show');
        Route::post('/material-availability/{order}/check', [\App\Http\Controllers\Production\MaterialAvailabilityController::class, 'check'])->name('material-availability.check');

        // Start Production
        Route::get('/start-production', [\App\Http\Controllers\Production\StartProductionController::class, 'index'])->name('start-production.index');
        Route::get('/start-production/{order}', [\App\Http\Controllers\Production\StartProductionController::class, 'show'])->name('start-production.show');
        Route::post('/start-production/{order}/start', [\App\Http\Controllers\Production\StartProductionController::class, 'start'])->name('start-production.start');

        // QC Inspection (First Article)
        Route::get('/qc-inspection', [\App\Http\Controllers\Production\QcInspectionController::class, 'index'])->name('qc-inspection.index');
        Route::get('/qc-inspection/{inspection}', [\App\Http\Controllers\Production\QcInspectionController::class, 'show'])->name('qc-inspection.show');
        Route::put('/qc-inspection/{inspection}', [\App\Http\Controllers\Production\QcInspectionController::class, 'update'])->name('qc-inspection.update');

        // Mass Production
        Route::get('/mass-production', [\App\Http\Controllers\Production\MassProductionController::class, 'index'])->name('mass-production.index');
        Route::get('/mass-production/{order}', [\App\Http\Controllers\Production\MassProductionController::class, 'show'])->name('mass-production.show');
        Route::post('/mass-production/{order}/update-progress', [\App\Http\Controllers\Production\MassProductionController::class, 'updateProgress'])->name('mass-production.update-progress');
        Route::post('/mass-production/{order}/request-inspection', [\App\Http\Controllers\Production\MassProductionController::class, 'requestInProcessInspection'])->name('mass-production.request-inspection');

        // In-Process Inspection
        Route::get('/in-process-inspection', [\App\Http\Controllers\Production\InProcessInspectionController::class, 'index'])->name('in-process-inspection.index');
        Route::get('/in-process-inspection/{inspection}', [\App\Http\Controllers\Production\InProcessInspectionController::class, 'show'])->name('in-process-inspection.show');
        Route::put('/in-process-inspection/{inspection}', [\App\Http\Controllers\Production\InProcessInspectionController::class, 'update'])->name('in-process-inspection.update');

        // Finish Production
        Route::get('/finish-production', [\App\Http\Controllers\Production\FinishProductionController::class, 'index'])->name('finish-production.index');
        Route::get('/finish-production/{order}', [\App\Http\Controllers\Production\FinishProductionController::class, 'show'])->name('finish-production.show');
        Route::post('/finish-production/{order}/finish', [\App\Http\Controllers\Production\FinishProductionController::class, 'finish'])->name('finish-production.finish');

        // Final Inspection & Kanban Update
        Route::get('/final-inspection', [\App\Http\Controllers\Production\FinalInspectionController::class, 'index'])->name('final-inspection.index');
        Route::get('/final-inspection/{inspection}', [\App\Http\Controllers\Production\FinalInspectionController::class, 'show'])->name('final-inspection.show');
        Route::put('/final-inspection/{inspection}', [\App\Http\Controllers\Production\FinalInspectionController::class, 'update'])->name('final-inspection.update');
        Route::post('/final-inspection/{order}/kanban-update', [\App\Http\Controllers\Production\FinalInspectionController::class, 'kanbanUpdate'])->name('final-inspection.kanban-update');

        // Legacy inspection routes
        Route::post('/inspections/{inspection}', [\App\Http\Controllers\ProductionInspectionController::class, 'update'])->name('inspections.update');
    });

    Route::prefix('purchasing')->name('purchasing.')->group(function () {
        Route::get('/purchase-requests/from-mrp', [PurchaseRequestController::class, 'createFromMrp'])->name('purchase-requests.create-from-mrp');
        Route::post('/purchase-requests/{purchase_request}/approve', [PurchaseRequestController::class, 'approve'])->name('purchase-requests.approve');
        Route::post('/purchase-requests/{purchase_request}/convert', [PurchaseRequestController::class, 'convertToPo'])->name('purchase-requests.convert');
        Route::resource('purchase-requests', PurchaseRequestController::class);

        Route::post('/purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
        Route::post('/purchase-orders/{purchase_order}/release', [PurchaseOrderController::class, 'release'])->name('purchase-orders.release');
        Route::get('/purchase-orders/{purchase_order}/print', [PurchaseOrderController::class, 'print'])->name('purchase-orders.print');
        Route::resource('purchase-orders', PurchaseOrderController::class);
    });
});

require __DIR__ . '/auth.php';
