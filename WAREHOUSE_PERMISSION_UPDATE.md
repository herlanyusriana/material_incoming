# Warehouse Permission Update - manage_warehouse

## Summary
Created new permission `manage_warehouse` untuk warehouse operations (QC, putaway, transfer, return, adjustment) untuk konsistensi dengan permission structure lainnya.

## Changes Made

### 1. Updated Routes Middleware ✅
**File:** `routes/web.php` (Line 188)

Changed warehouse operations group middleware:
```php
// Before
Route::middleware('can:manage_incoming')->prefix('warehouse')->name('warehouse.')->group(function () {

// After
Route::middleware('can:manage_warehouse')->prefix('warehouse')->name('warehouse.')->group(function () {
```

**Routes Protected:**
- `/warehouse/qc` - Quality Control operations
- `/warehouse/putaway` - Putaway operations
- `/warehouse/transfer` - Transfer operations
- `/warehouse/return` - Return operations
- `/warehouse/adjustment` - Adjustment operations

### 2. Added Permission to Config ✅
**File:** `config/role_permissions.php`

#### Added to `defined_permissions` (Line 82):
```php
'manage_warehouse',    // Warehouse operations (QC, putaway, transfer, return, adjustment)
```

#### Added to `warehouse` Role (Line 39):
```php
'warehouse' => [
    'view_dashboard',
    'view_incoming',
    'view_logistics',
    'manage_incoming',
    'manage_warehouse',  // NEW
    'manage_subcounts',
],
```

### 3. Updated Documentation ✅
**File:** `RBAC_IMPLEMENTATION_COMPLETE.md`

Updated permission matrix to include:
- New warehouse operations row with `manage_warehouse` permission
- Updated total protected routes count from ~328 to ~348 routes

## Database Migration Required

### Create Permission in Database
You need to add the new permission to your database. Run this in Laravel Tinker or create a migration:

```php
use Spatie\Permission\Models\Permission;

// Create the permission
Permission::create(['name' => 'manage_warehouse', 'guard_name' => 'web']);

// Assign to warehouse role
$warehouseRole = Role::where('name', 'warehouse')->first();
if ($warehouseRole) {
    $warehouseRole->givePermissionTo('manage_warehouse');
}

// Admin role already has '*' wildcard, so no need to add explicitly
```

Or create a migration:

```bash
php artisan make:migration add_manage_warehouse_permission
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Create permission
        Permission::create(['name' => 'manage_warehouse', 'guard_name' => 'web']);

        // Assign to warehouse role
        $warehouseRole = Role::where('name', 'warehouse')->first();
        if ($warehouseRole) {
            $warehouseRole->givePermissionTo('manage_warehouse');
        }
    }

    public function down(): void
    {
        Permission::where('name', 'manage_warehouse')->delete();
    }
};
```

## Testing

After running the migration and clearing caches:

### 1. Clear Caches
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### 2. Verify Permission
```bash
php artisan tinker

# Check permission exists
\Spatie\Permission\Models\Permission::where('name', 'manage_warehouse')->first();

# Check warehouse role has permission
$role = \Spatie\Permission\Models\Role::where('name', 'warehouse')->first();
$role->permissions->pluck('name');
```

### 3. Test Access
Login as user with `warehouse` role and verify:
- ✅ Can access `/warehouse/qc`
- ✅ Can access `/warehouse/putaway`
- ✅ Can access `/warehouse/transfer`
- ✅ Can access `/warehouse/return`
- ✅ Can access `/warehouse/adjustment`

Login as user without `manage_warehouse` permission and verify:
- ❌ Gets 403 Forbidden for warehouse routes

## Files Modified

1. `routes/web.php` - Changed middleware from `manage_incoming` to `manage_warehouse`
2. `config/role_permissions.php` - Added `manage_warehouse` to defined_permissions and warehouse role
3. `RBAC_IMPLEMENTATION_COMPLETE.md` - Updated documentation
4. `WAREHOUSE_PERMISSION_UPDATE.md` - This file (new)

## Rollback Instructions

If you need to rollback these changes:

```bash
# Revert routes/web.php
git checkout HEAD -- routes/web.php

# Revert config/role_permissions.php
git checkout HEAD -- config/role_permissions.php

# Remove permission from database
php artisan tinker
\Spatie\Permission\Models\Permission::where('name', 'manage_warehouse')->delete();

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

## Next Steps

1. ✅ Routes updated
2. ✅ Config updated
3. ✅ Documentation updated
4. ⏳ Create and run database migration
5. ⏳ Clear caches
6. ⏳ Test with different user roles

**Status:** Code changes complete, database migration required.
