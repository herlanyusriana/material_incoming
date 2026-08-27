<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\ArrivalController;
use App\Http\Controllers\ReceiveController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\GciInventoryController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ContractNumberController;
use App\Http\Controllers\WarehouseLocationController;
use App\Http\Controllers\LocalPoController;
use App\Http\Controllers\OutgoingController;
use App\Http\Controllers\Outgoing\DeliveryOrderController;
use App\Http\Controllers\Outgoing\OspController;
use App\Http\Controllers\Outgoing\PickingFgController;
use App\Http\Controllers\Outgoing\OutgoingPoController;
use App\Http\Controllers\SubconController;
use App\Http\Controllers\Api\SuggestionController;
use App\Http\Controllers\SubcountController;
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
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\RoleManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'can:view_dashboard'])->name('dashboard');

// Invoice/Report routes — require authentication (use signed URLs for external access)
// Moved inside auth middleware group below for security

Route::middleware('auth')->group(function () {
    Route::middleware('can:manage_users')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        Route::get('/roles', [RoleManagementController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleManagementController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [RoleManagementController::class, 'update'])->name('roles.update');
    });

    Route::get('/api/parts/search', [PartController::class, 'search'])->name('parts.search');
    Route::get('/api/gci-parts/search', [PlanningGciPartController::class, 'search'])->name('gci-parts.search');
    Route::get('/api/gci-parts/{gciPart}/bom-info', [PlanningGciPartController::class, 'getBomInfo'])->name('gci-parts.bom-info');

    Route::middleware('can:manage_subcounts')->group(function () {
        Route::get('/subcounts', [SubcountController::class, 'index'])->name('subcounts.index');
        Route::get('/subcounts/{subcount}', [SubcountController::class, 'show'])->name('subcounts.show');
        Route::put('/subcounts/records/{record}/netto', [SubcountController::class, 'updateRecordNetto'])->name('subcounts.records.netto');
    });

    Route::get('/api/suggest-arrivals/{gciPartId}', [SuggestionController::class, 'arrivals'])->name('api.suggest-arrivals');
    Route::get('/api/suggest-production-orders/{gciPartId}', [SuggestionController::class, 'productionOrders'])->name('api.suggest-production-orders');
    Route::get('/api/production-gci/wo-monitoring', [\App\Http\Controllers\Api\ProductionGciApiController::class, 'woMonitoringData'])->name('api.production-gci.wo-monitoring');
    Route::view('/incoming-material', 'incoming-material.dashboard')->middleware('can:view_incoming')->name('incoming-material.dashboard');
    Route::get('/logistics', [LogisticsDashboardController::class, 'index'])->middleware('can:view_logistics')->name('logistics.dashboard');

    // Master Data - Parts, Vendors, Pricing, Contracts, Machines
    require __DIR__ . '/modules/master_data.php';

    // Material Incoming - Departures, Local POs, Receives
    // Material Incoming — Departures, Local POs, Receives, Reports
    require __DIR__ . '/modules/incoming.php';

    // Inventory Management
    require __DIR__ . '/modules/inventory.php';

    // Warehouse Operations
    require __DIR__ . '/modules/warehouse.php';

    // Outgoing / FG Delivery
    require __DIR__ . '/modules/outgoing.php';

    // Subcon Module
    require __DIR__ . '/modules/subcon.php';

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // NOTE: Duplicate receive/departure-item routes removed — they are already
    // defined inside the 'can:manage_incoming' middleware group above.

    // Planning Module
    require __DIR__ . '/modules/planning.php';

    // Delivery Management (Outgoing) - Moved to modules/outgoing.php

    // Production Module
    require __DIR__ . '/modules/production.php';

    // Purchasing Module
    require __DIR__ . '/modules/purchasing.php';
});

require __DIR__ . '/auth.php';
