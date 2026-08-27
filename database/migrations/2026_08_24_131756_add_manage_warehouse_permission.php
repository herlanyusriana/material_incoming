<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if warehouse role exists
        $warehouseRoleExists = DB::table('roles')->where('name', 'warehouse')->exists();

        if ($warehouseRoleExists) {
            // Check if permission already exists for warehouse role
            $exists = DB::table('role_permissions')
                ->where('role', 'warehouse')
                ->where('permission', 'manage_warehouse')
                ->exists();

            if (!$exists) {
                // Add manage_warehouse permission to warehouse role
                DB::table('role_permissions')->insert([
                    'role' => 'warehouse',
                    'permission' => 'manage_warehouse',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove manage_warehouse permission from all roles
        DB::table('role_permissions')
            ->where('permission', 'manage_warehouse')
            ->delete();
    }
};
