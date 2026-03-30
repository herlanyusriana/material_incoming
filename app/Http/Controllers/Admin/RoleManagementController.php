<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class RoleManagementController extends Controller
{
    public function index()
    {
        $roles = collect(config('role_permissions.roles', []))
            ->map(function (array $permissions, string $role) {
                return [
                    'name' => $role,
                    'permissions' => $permissions,
                    'user_count' => User::where('role', $role)->count(),
                ];
            })
            ->values();

        $definedPermissions = config('role_permissions.defined_permissions', []);

        return view('admin.roles.index', compact('roles', 'definedPermissions'));
    }
}
