<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    public static function masterRoles(): array
    {
        $configRoles = collect(config('role_permissions.roles', []))
            ->keys()
            ->mapWithKeys(fn ($role) => [
                $role => [
                    'name' => $role,
                    'display_name' => strtoupper($role),
                    'description' => null,
                    'is_system' => true,
                ],
            ])
            ->all();

        if (!Schema::hasTable('roles')) {
            return $configRoles;
        }

        $dbRoles = static::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($role) => [
                $role->name => [
                    'name' => $role->name,
                    'display_name' => $role->display_name ?: strtoupper($role->name),
                    'description' => $role->description,
                    'is_system' => array_key_exists($role->name, $configRoles),
                ],
            ])
            ->all();

        return array_replace($configRoles, $dbRoles);
    }

    public static function availableRoleNames(): array
    {
        return array_keys(static::masterRoles());
    }
}
