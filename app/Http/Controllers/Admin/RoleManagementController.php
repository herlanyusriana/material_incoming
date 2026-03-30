<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;

class RoleManagementController extends Controller
{
    public function index()
    {
        $roleMasters = Role::masterRoles();
        $roles = collect(RolePermission::effectiveRoles())
            ->map(function (array $permissions, string $role) use ($roleMasters) {
                $meta = $roleMasters[$role] ?? ['display_name' => strtoupper($role), 'description' => null, 'is_system' => false];
                return [
                    'name' => $role,
                    'display_name' => $meta['display_name'] ?? strtoupper($role),
                    'description' => $meta['description'] ?? null,
                    'is_system' => (bool) ($meta['is_system'] ?? false),
                    'permissions' => $permissions,
                    'user_count' => User::where('role', $role)->count(),
                ];
            })
            ->values();

        $definedPermissions = config('role_permissions.defined_permissions', []);

        return view('admin.roles.index', compact('roles', 'definedPermissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', 'unique:roles,name'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        Role::create([
            'name' => strtolower(trim((string) $validated['name'])),
            'display_name' => trim((string) ($validated['display_name'] ?? '')) ?: strtoupper(trim((string) $validated['name'])),
            'description' => trim((string) ($validated['description'] ?? '')) ?: null,
        ]);

        return redirect()->route('admin.roles.index')->with('success', 'Role baru berhasil dibuat.');
    }

    public function update(Request $request, string $role)
    {
        $role = strtolower(trim($role));
        $availableRoles = RolePermission::availableRoles();
        $definedPermissions = RolePermission::definedPermissions();

        abort_unless(in_array($role, $availableRoles, true), 404);

        if ($role === 'admin') {
            return back()->with('error', 'Role admin tetap full access dan tidak bisa diubah dari halaman ini.');
        }

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', $definedPermissions)],
        ]);

        $permissions = collect($validated['permissions'] ?? [])
            ->map(fn ($permission) => strtolower(trim((string) $permission)))
            ->filter()
            ->unique()
            ->values();

        RolePermission::query()->where('role', $role)->delete();

        foreach ($permissions as $permission) {
            RolePermission::create([
                'role' => $role,
                'permission' => $permission,
            ]);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Permission role berhasil diperbarui.');
    }
}
