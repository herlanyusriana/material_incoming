<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Planning\BomController as PlanningBomController;
use App\Http\Controllers\Planning\CustomerController as PlanningCustomerController;
use App\Http\Controllers\Planning\CustomerPartController as PlanningCustomerPartController;
use App\Http\Controllers\Planning\ForecastController as PlanningForecastController;
use App\Http\Controllers\Planning\GciPartController as PlanningGciPartController;
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

    Route::get('/gci-parts/export', [PlanningGciPartController::class, 'export'])->name('gci-parts.export');
    Route::post('/gci-parts/import', [PlanningGciPartController::class, 'import'])->name('gci-parts.import');

    // Classification-specific part routes
    Route::get('/fg-parts', [PlanningGciPartController::class, 'index'])->defaults('classification', 'FG')->name('fg-parts.index');
    Route::get('/wip-parts', [PlanningGciPartController::class, 'index'])->defaults('classification', 'WIP')->name('wip-parts.index');
    Route::get('/rm-parts', [PlanningGciPartController::class, 'index'])->defaults('classification', 'RM')->name('rm-parts.index');

    Route::get('/forecasts', [PlanningForecastController::class, 'index'])->name('forecasts.index');
    Route::post('/forecasts/preview-plan', [PlanningForecastController::class, 'previewPlan'])->name('forecasts.preview-plan');
    Route::post('/forecasts/{document}/confirm', [PlanningForecastController::class, 'confirmPlan'])->name('forecasts.confirm-plan');
    Route::delete('/forecasts/clear', [PlanningForecastController::class, 'clear'])->name('forecasts.clear');
    Route::get('/forecasts/history', [PlanningForecastController::class, 'history'])->name('forecasts.history');

    Route::get('/mrp', [PlanningMrpController::class, 'index'])->name('mrp.index');
    Route::post('/mrp/generate', [PlanningMrpController::class, 'generate'])->name('mrp.generate');
    Route::post('/mrp/generate-range', [PlanningMrpController::class, 'generateRange'])->name('mrp.generate-range');
    Route::post('/mrp/generate-po', [PlanningMrpController::class, 'generatePo'])->name('mrp.generate-po');
    Route::post('/mrp/approve', [PlanningMrpController::class, 'approvePlans'])->name('mrp.approve');
    Route::post('/mrp/reject', [PlanningMrpController::class, 'rejectPlans'])->name('mrp.reject');
    Route::post('/mrp/purchase-orders/{purchaseOrderId}/release', [PlanningMrpController::class, 'releasePo'])->name('mrp.po-release');
    Route::post('/mrp/purchase-orders/{purchaseOrderId}/actualize', [PlanningMrpController::class, 'actualizePo'])->name('mrp.po-actualize');
    Route::delete('/mrp/clear', [PlanningMrpController::class, 'clear'])->name('mrp.clear');
    Route::get('/mrp/history', [PlanningMrpController::class, 'history'])->name('mrp.history');
    Route::get('/mrp/integration', [PlanningMrpController::class, 'integrationDashboard'])->name('mrp.integration-dashboard');
});
