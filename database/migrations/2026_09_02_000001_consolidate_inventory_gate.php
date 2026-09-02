<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Konsolidasi gate inventory: seluruh route warehouse.* pindah ke
     * `can:manage_inventory`. Role `warehouse` yang sebelumnya punya
     * `manage_warehouse` di-reassign ke `manage_inventory`; `manage_warehouse`
     * dihapus dari semua role.
     */
    public function up(): void
    {
        // Reassign warehouse role to manage_inventory (idempotent).
        $warehouseRoleExists = DB::table('roles')->where('name', 'warehouse')->exists();
        if ($warehouseRoleExists) {
            // Remove the old permission (if any) so we don't carry it forward.
            DB::table('role_permissions')
                ->where('role', 'warehouse')
                ->where('permission', 'manage_warehouse')
                ->delete();

            $hasInventory = DB::table('role_permissions')
                ->where('role', 'warehouse')
                ->where('permission', 'manage_inventory')
                ->exists();

            if (!$hasInventory) {
                DB::table('role_permissions')->insert([
                    'role' => 'warehouse',
                    'permission' => 'manage_inventory',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Remove manage_warehouse from all roles (superseded by manage_inventory).
        DB::table('role_permissions')
            ->where('permission', 'manage_warehouse')
            ->delete();
    }

    public function down(): void
    {
        // Restore warehouse role to manage_warehouse.
        $warehouseRoleExists = DB::table('roles')->where('name', 'warehouse')->exists();
        if ($warehouseRoleExists) {
            DB::table('role_permissions')
                ->where('role', 'warehouse')
                ->where('permission', 'manage_inventory')
                ->delete();

            $hasWarehouse = DB::table('role_permissions')
                ->where('role', 'warehouse')
                ->where('permission', 'manage_warehouse')
                ->exists();

            if (!$hasWarehouse) {
                DB::table('role_permissions')->insert([
                    'role' => 'warehouse',
                    'permission' => 'manage_warehouse',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
