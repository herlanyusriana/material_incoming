# RBAC Implementation Testing Analysis

## Status Overview
✅ **Completed**: 15 route groups dengan permission middleware sudah ditambahkan
⚠️ **Needs Review**: Beberapa routes belum ter-protect dengan permission middleware

## Routes dengan Permission Middleware (✅ SELESAI)

### 1. Admin Module (`can:manage_users`)
- `/admin/users` - User management
- `/admin/roles` - Role management

### 2. Master Data (`can:manage_parts`)
- `/vendors` - Vendor management
- `/parts` - Parts management
- `/contract-numbers` - Contract numbers
- `/pricing` - Pricing management
- `/machines` - Machine management
- `/customers` - Customer management

### 3. Inventory Module (`can:manage_inventory`)
- `/inventory` - Inventory list & CRUD
- `/inventory/gci` - GCI inventory
- `/inventory/locations` - Warehouse locations
- `/inventory/transfers` - Inventory transfers
- `/bin-transfers` - Bin transfers

### 4. Incoming Module (`can:manage_incoming`)
- `/local-po` - Local PO management
- `/arrivals` - Arrivals/Departures
- `/arrival-planner` - Arrival planning

### 5. Purchasing Module (`can:manage_purchasing`)
- `/purchase-orders` - Purchase orders
- `/vendors/{vendor}/purchase-orders` - Vendor POs

### 6. Outgoing Module (`can:manage_outgoing`)
- `/outgoing/customer-po` - Customer PO
- `/outgoing/delivery-orders` - Delivery orders
- `/outgoing/delivery-notes` - Delivery notes
- `/outgoing/standard-packings` - Standard packings
- `/outgoing/osp` - OSP orders
- `/outgoing/daily-planning` - Daily planning

### 7. Subcon Module (`can:manage_subcon`)
- `/subcon` - Subcon management
- `/subcon/receive` - Subcon receive
- `/subcon/traceability` - Traceability

### 8. Planning Module (`can:manage_planning`)
- `/planning/gci-parts` - GCI parts
- `/planning/boms` - Bill of Materials
- `/planning/customers` - Customer management
- `/planning/mrp` - Material Requirements Planning

### 9. Production Module (`can:manage_production`)
- `/production/planning` - Production planning
- `/production/material-requirements` - Material requirements
- `/production/material-requests` - Material requests
- `/production/material-availability` - Material availability
- `/production/start-production` - Start production
- `/production/gci-dashboard` - GCI dashboard

### 10. Quality Control (`can:manage_qc_inspection`)
- `/inspections/incoming` - Incoming inspection

### 11. In-Process Inspection (`can:manage_in_process_inspection`)
- `/inspections/in-process` - In-process inspection

### 12. Final Inspection (`can:manage_final_inspection`)
- `/inspections/final` - Final inspection

### 13. Kanban (`can:manage_kanban_update`)
- `/kanban` - Kanban management

### 14. Container Inspection (`can:manage_container_inspection`)
- `/container-inspections` - Container inspection

### 15. Customer Stock (`can:manage_customer_stock`)
- `/customer-stock` - Customer stock management

## Routes TANPA Permission Middleware (⚠️ PERLU REVIEW)

### A. Dashboard & Profile (Semua authenticated users perlu akses)
```
Line 46: GET /dashboard → DashboardController@index
         Middleware: auth, verified
         Permission: NONE
         Status: ⚠️ PERLU view_dashboard permission?

Lines 362-364: Profile routes
         GET    /profile → ProfileController@edit
         PATCH  /profile → ProfileController@update
         DELETE /profile → ProfileController@destroy
         Middleware: auth only
         Permission: NONE
         Status: ✅ OK (semua user harus bisa edit profile)
```

### B. API Search Endpoints (Digunakan banyak modul)
```
Line 64: GET /api/parts/search → PartController@search
Line 65: GET /api/gci-parts/search → GciPartController@search
Line 66: GET /api/gci-parts/{gciPart}/bom-info → GciPartController@getBomInfo
         Middleware: auth only
         Permission: NONE
         Status: ⚠️ DECISION NEEDED
         
         Options:
         1. Biarkan accessible untuk semua authenticated users (current)
         2. Require view_parts permission
         3. Require permission based on calling context
```

### C. Subcounts Module
```
Line 68: GET /subcounts → SubcountController@index
Line 69: GET /subcounts/{subcount} → SubcountController@show
Line 70: PUT /subcounts/records/{record}/netto → updateRecordNetto
         Middleware: auth only
         Permission: NONE
         Status: ⚠️ PERLU manage_subcounts permission?
```

### D. API Suggestions & Monitoring
```
Line 72: GET /api/suggest-arrivals/{gciPartId}
Line 73: GET /api/suggest-production-orders/{gciPartId}
Line 74: GET /api/production-gci/wo-monitoring
         Middleware: auth only
         Permission: NONE
         Status: ⚠️ DECISION NEEDED
```

### E. Dashboards
```
Line 75: GET /incoming-material → view('incoming-material.dashboard')
Line 76: GET /logistics → LogisticsDashboardController@index
         Middleware: auth only
         Permission: NONE
         Status: ⚠️ PERLU view_incoming / view_logistics permission?
```

### F. Receive Routes (Incoming material receiving)
```
Lines 365-376: Receive routes
         GET    /departure-items/{arrivalItem}/receive → create
         POST   /departure-items/{arrivalItem}/receive → store
         GET    /receives/invoice/{arrival} → createByInvoice
         POST   /receives/invoice/{arrival} → storeByInvoice
         GET    /receives/{receive}/edit
         PUT    /receives/{receive}
         DELETE /receives/{receive}
         GET    /receives/{receive}/label
         GET    /receives/completed
         GET    /receives/completed/{arrival}
         GET    /receives/completed/{arrival}/export
         Middleware: auth only
         Permission: NONE
         Status: ⚠️ PERLU manage_incoming permission?
```

### G. Departure Items (Incoming material items)
```
Lines 377-380: Departure item routes
         GET  /departure-items/{arrivalItem}/edit
         PUT  /departure-items/{arrivalItem}
         GET  /departures/{departure}/items/create
         POST /departures/{departure}/items
         Middleware: auth only
         Permission: NONE
         Status: ⚠️ PERLU manage_incoming permission?
```

### H. Public Routes (By Design - NO AUTH)
```
Lines 48-51: Public invoice/inspection routes
         GET /departures/{departure}/invoice
         GET /departures/{departure}/inspection-report
         GET /departures/{departure}/export-detail
         Middleware: NONE (public)
         Permission: NONE
         Status: ✅ OK (for PDF generation, no auth by design)
```

## Recommendations

### 1. High Priority - Add Permission Middleware

#### A. Dashboard Route
```php
// Line 46 - routes/web.php
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'can:view_dashboard'])
    ->name('dashboard');
```

#### B. Subcounts Module
```php
// After line 67 - routes/web.php
Route::middleware('can:manage_subcounts')->group(function () {
    Route::get('/subcounts', [SubcountController::class, 'index'])->name('subcounts.index');
    Route::get('/subcounts/{subcount}', [SubcountController::class, 'show'])->name('subcounts.show');
    Route::put('/subcounts/records/{record}/netto', [SubcountController::class, 'updateRecordNetto'])->name('subcounts.records.netto');
});
```

#### C. Receive Routes (Incoming)
```php
// Lines 365-376 - routes/web.php
Route::middleware('can:manage_incoming')->group(function () {
    Route::get('/departure-items/{arrivalItem}/receive', [ReceiveController::class, 'create'])->name('receives.create');
    Route::post('/departure-items/{arrivalItem}/receive', [ReceiveController::class, 'store'])->name('receives.store');
    // ... all other receive routes
});
```

#### D. Departure Items Routes
```php
// Lines 377-380 - routes/web.php
Route::middleware('can:manage_incoming')->group(function () {
    Route::get('/departure-items/{arrivalItem}/edit', [ArrivalController::class, 'editItem'])->name('departure-items.edit');
    Route::put('/departure-items/{arrivalItem}', [ArrivalController::class, 'updateItem'])->name('departure-items.update');
    Route::get('/departures/{departure}/items/create', [ArrivalController::class, 'createItem'])->name('departure-items.create');
    Route::post('/departures/{departure}/items', [ArrivalController::class, 'storeItem'])->name('departure-items.store');
});
```

#### E. Dashboard Views
```php
// Lines 75-76 - routes/web.php
Route::get('/incoming-material', function () {
    return view('incoming-material.dashboard');
})->middleware('can:view_incoming')->name('incoming-material.dashboard');

Route::get('/logistics', [LogisticsDashboardController::class, 'index'])
    ->middleware('can:view_logistics')
    ->name('logistics.dashboard');
```

### 2. Medium Priority - Decision Needed

#### A. API Search Endpoints
**Options:**
1. **Keep as-is** (accessible to all authenticated users) - RECOMMENDED
   - Reasoning: Search endpoints digunakan oleh banyak modul berbeda
   - Risk: Low - hanya search/read, tidak modify data
   
2. **Add view_parts permission**
   - Reasoning: Membatasi akses hanya untuk user dengan permission parts
   - Risk: Medium - bisa break existing functionality

**Recommendation:** Keep as-is untuk sekarang

#### B. API Suggestions & Monitoring
**Recommendation:** Keep as-is (accessible to all authenticated users)
- Suggestions digunakan untuk auto-complete di berbagai form
- WO monitoring digunakan oleh production dashboard

### 3. Low Priority - Keep As-Is

#### Profile Routes
**Status:** ✅ OK - semua authenticated users harus bisa edit profile mereka

#### Public Invoice Routes
**Status:** ✅ OK - by design untuk PDF generation tanpa auth

## Permission Yang Perlu Ditambahkan ke Config

### config/role_permissions.php

```php
'permissions' => [
    'view_dashboard',      // ✅ Already exists
    'manage_subcounts',    // ⚠️ NEED TO ADD
    'view_incoming',       // ⚠️ NEED TO ADD
    'view_logistics',      // ⚠️ NEED TO ADD
],
```

### Role Assignment Recommendations

```php
'roles' => [
    'admin' => ['*'],
    'staff' => [
        'view_dashboard',
        'view_planning',
        'view_production',
        'view_incoming',      // ADD
        'manage_incoming',
        'manage_subcounts',   // ADD (if staff need to update subcounts)
    ],
    'ppic' => [
        'view_dashboard',
        'manage_planning',
        'view_production',
        'manage_subcon',
        'view_logistics',     // ADD
    ],
    'warehouse' => [
        'view_dashboard',
        'manage_incoming',
        'view_incoming',      // ADD
        'manage_subcounts',   // ADD
        'view_logistics',     // ADD
    ],
],
```

## Testing Checklist

- [ ] Verify dashboard requires view_dashboard permission
- [ ] Verify subcounts requires manage_subcounts permission
- [ ] Verify receive routes require manage_incoming permission
- [ ] Verify departure-items routes require manage_incoming permission
- [ ] Verify incoming-material dashboard requires view_incoming
- [ ] Verify logistics dashboard requires view_logistics
- [ ] Test dengan user tanpa permission → harus dapat 403
- [ ] Test dengan user dengan permission → harus bisa akses
- [ ] Verify API search endpoints masih accessible untuk semua authenticated users
- [ ] Verify profile routes accessible untuk semua authenticated users
- [ ] Verify public invoice routes accessible tanpa auth

## Summary

**Total Routes Analyzed:** ~100+ routes
**Routes dengan Permission Middleware:** ~80 routes (15 route groups) ✅
**Routes tanpa Permission:** ~20 routes ⚠️

**Next Steps:**
1. Add permission middleware untuk high priority routes (dashboard, subcounts, receive, departure-items, dashboards)
2. Add missing permissions ke config/role_permissions.php
3. Update role assignments
4. Test dengan berbagai role
5. Verify 403 responses untuk unauthorized access
