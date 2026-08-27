<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Planning\BomController as PlanningBomController;
use App\Http\Controllers\Planning\CustomerController as PlanningCustomerController;
use App\Http\Controllers\Planning\CustomerPartController as PlanningCustomerPartController;
use App\Http\Controllers\Planning\CustomerPlanningImportController as PlanningCustomerPlanningImportController;
use App\Http\Controllers\Planning\CustomerPoController as PlanningCustomerPoController;
use App\Http\Controllers\Planning\ForecastController as PlanningForecastController;
use App\Http\Controllers\Planning\GciPartController as PlanningGciPartController;
use App\Http\Controllers\Planning\MpsController as PlanningMpsController;
use App\Http\Controllers\Planning\MrpController as PlanningMrpController;

Route::middleware('can:manage_planning')->prefix('planning')->name('planning.')->group(function () {
    Route::get('/gci-parts', [PlanningGciPartController::class, 'index'])->name('gci-parts.index');
    Route::post('/gci-parts', [PlanningGciPartController::class, 'store'])->name('gci-parts.store');
    Route::put('/gci-parts/{gciPart}', [PlanningGciPartController::class, 'update'])->name('gci-parts.update');
    Route::delete('/gci-parts/{gciPart}', [PlanningGciPartController::class, 'destroy'])->name('gci-parts.destroy');
    Route::post('/gci-parts/{gciPart}/substitutes', [PlanningGciPartController::class, 'storeSubstitute'])->name('gci-parts.substitutes.store');
    Route::put('/gci-part-substitutes/{substitute}', [PlanningGciPartController::class, 'updateSubstitute'])->name('gci-part-substitutes.update');
    Route::delete('/gci-part-substitutes/{substitute}', [PlanningGciPartController::class, 'destroySubstitute'])->name('gci-part-substitutes.destroy');

    Route::get('/boms', [PlanningBomController::class, 'index'])->name('boms.index');
    Route::get('/boms/export', [PlanningBomController::class, 'export'])->name('boms.export');
    Route::post('/boms/import', [PlanningBomController::class, 'import'])->name('boms.import');
    Route::post('/boms/sync-incoming-parts', [PlanningBomController::class, 'syncIncomingParts'])->name('boms.sync-incoming-parts');
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
    Route::put('/bom-item-substitutes/{substitute}', [PlanningBomController::class, 'updateSubstitute'])->name('bom-item-substitutes.update');
    Route::delete('/boms/truncate', [PlanningBomController::class, 'truncateBoms'])->name('boms.truncate');
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
