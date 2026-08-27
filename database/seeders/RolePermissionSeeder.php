<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('role_permissions');
        $roles = $config['roles'];
        $definedPermissions = $config['defined_permissions'];

        // Clear existing data
        DB::table('role_permissions')->truncate();
        DB::table('roles')->truncate();

        foreach ($roles as $roleName => $permissions) {
            // Insert role
            DB::table('roles')->insert([
                'name' => $roleName,
                'display_name' => ucfirst($roleName),
                'description' => "Role: {$roleName}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert permissions for this role
            // If role has '*', give it all defined permissions
            if (in_array('*', $permissions)) {
                foreach ($definedPermissions as $permission) {
                    DB::table('role_permissions')->insert([
                        'role' => $roleName,
                        'permission' => $permission,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                // Insert specific permissions
                foreach ($permissions as $permission) {
                    DB::table('role_permissions')->insert([
                        'role' => $roleName,
                        'permission' => $permission,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
