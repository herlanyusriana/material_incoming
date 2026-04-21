<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $role = strtolower(trim((string) $request->query('role', '')));
        $roles = RolePermission::availableRoles();

        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('name', 'like', '%' . $q . '%')
                        ->orWhere('username', 'like', '%' . $q . '%')
                        ->orWhere('email', 'like', '%' . $q . '%');
                });
            })
            ->when($role !== '', fn ($query) => $query->where('role', $role))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'roles', 'q', 'role'));
    }

    public function store(Request $request)
    {
        $roles = RolePermission::availableRoles();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'in:' . implode(',', $roles)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => strtolower(trim((string) $validated['username'])),
            'email' => !empty($validated['email']) ? strtolower(trim((string) $validated['email'])) : null,
            'role' => strtolower(trim((string) $validated['role'])),
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $roles = RolePermission::availableRoles();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['required', 'string', 'in:' . implode(',', $roles)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $payload = [
            'name' => $validated['name'],
            'username' => strtolower(trim((string) $validated['username'])),
            'email' => !empty($validated['email']) ? strtolower(trim((string) $validated['email'])) : null,
            'role' => strtolower(trim((string) $validated['role'])),
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        if ($request->user()?->id === $user->id && strtolower((string) $payload['role']) !== 'admin') {
            return back()->with('error', 'Admin yang sedang login tidak boleh menurunkan role dirinya sendiri.');
        }

        $user->update($payload);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()?->id === $user->id) {
            return back()->with('error', 'User yang sedang login tidak bisa dihapus.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }
}
