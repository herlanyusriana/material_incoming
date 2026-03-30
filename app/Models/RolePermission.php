<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class RolePermission extends Model
{
    protected $fillable = [
        'role',
        'permission',
    ];

    public static function definedPermissions(): array
    {
        return array_values(config('role_permissions.defined_permissions', []));
    }

    public static function effectiveRoles(): array
    {
        $baseRoles = config('role_permissions.roles', []);

        foreach (Role::availableRoleNames() as $roleName) {
            $baseRoles[$roleName] = $baseRoles[$roleName] ?? [];
        }

        if (!Schema::hasTable('role_permissions')) {
            return $baseRoles;
        }

        $overrides = static::query()
            ->orderBy('role')
            ->orderBy('permission')
            ->get(['role', 'permission'])
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('permission')->values()->all())
            ->all();

        foreach ($overrides as $role => $permissions) {
            $baseRoles[$role] = $permissions;
        }

        return $baseRoles;
    }

    public static function availableRoles(): array
    {
        return Role::availableRoleNames();
    }

    public static function permissionsForRole(?string $role): array
    {
        $roleKey = strtolower(trim((string) $role));
        if ($roleKey === '') {
            return [];
        }

        $roles = static::effectiveRoles();

        return array_values($roles[$roleKey] ?? []);
    }
}
