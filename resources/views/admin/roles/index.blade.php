<x-app-layout>
    <x-slot name="header">Role Management</x-slot>

    @php
        $permissionGroups = [
            'Dashboard' => ['view_dashboard'],
            'Planning' => ['view_planning', 'manage_planning', 'delete_planning'],
            'Production' => [
                'view_production',
                'manage_production',
                'manage_qc_inspection',
                'manage_in_process_inspection',
                'manage_final_inspection',
                'manage_kanban_update',
            ],
            'Material & Warehouse' => ['manage_incoming', 'manage_inventory'],
            'Purchasing' => ['manage_purchasing'],
            'Master Data' => ['manage_users', 'manage_parts', 'manage_customers'],
            'Outgoing & Subcon' => ['manage_outgoing', 'manage_subcon'],
        ];
    @endphp

    <div class="space-y-5">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-semibold text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-3 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Admin Panel</div>
                    <h1 class="mt-1 text-xl font-black text-slate-900">Role Management</h1>
                    <p class="mt-1 text-sm text-slate-500">Kelola role dan permission yang aktif dipakai aplikasi.</p>
                </div>

                <a href="{{ route('admin.users.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                    Kelola Users
                </a>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-sm font-black text-slate-900">Tambah Role Baru</h2>
                <p class="mt-1 text-xs text-slate-500">Contoh: <span class="font-semibold text-slate-700">purchasing</span> atau <span class="font-semibold text-slate-700">quality</span>.</p>
            </div>

            <form action="{{ route('admin.roles.store') }}" method="POST" class="grid gap-3 md:grid-cols-[180px_minmax(0,1fr)_minmax(0,1.2fr)_auto] md:items-end">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-400">Role Key</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="purchasing"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-400">Display Name</label>
                    <input type="text" name="display_name" value="{{ old('display_name') }}" placeholder="Purchasing"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-400">Description</label>
                    <input type="text" name="description" value="{{ old('description') }}" placeholder="Purchase request and PO access"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
                    Buat Role
                </button>
            </form>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($roles as $role)
                @php
                    $isFullAccess = in_array('*', $role['permissions'], true);
                    $grantedCount = $isFullAccess ? count($definedPermissions) : count(array_intersect($role['permissions'], $definedPermissions));
                @endphp
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-black text-slate-900">{{ $role['display_name'] ?? strtoupper($role['name']) }}</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ $role['description'] ?: 'Custom role' }}</p>
                        </div>
                        @if ($isFullAccess)
                            <span class="rounded-lg bg-indigo-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-indigo-700">
                                Full Access
                            </span>
                        @endif
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-center">
                        <div class="rounded-xl bg-slate-50 px-3 py-3">
                            <div class="text-lg font-black text-slate-900">{{ $role['user_count'] }}</div>
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Users</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 px-3 py-3">
                            <div class="text-lg font-black text-slate-900">{{ $grantedCount }}/{{ count($definedPermissions) }}</div>
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">Permissions</div>
                        </div>
                    </div>

                    @if ($role['name'] !== 'admin')
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <a href="#role-editor-{{ $role['name'] }}"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                Edit Permission
                            </a>
                        </div>
                    @else
                        <div class="mt-4 pt-4 border-t border-slate-100 text-xs font-semibold text-slate-400">
                            Admin tetap full access.
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="space-y-4">
            @foreach ($roles as $role)
                @if ($role['name'] !== 'admin')
                    <form id="role-editor-{{ $role['name'] }}" action="{{ route('admin.roles.update', $role['name']) }}" method="POST"
                        class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                        @csrf
                        @method('PUT')

                        <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
                            <div>
                                <h3 class="text-sm font-black text-slate-900">Edit Role {{ $role['display_name'] ?? strtoupper($role['name']) }}</h3>
                                <p class="text-xs text-slate-500">Centang permission yang diizinkan untuk role ini.</p>
                            </div>
                            <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">
                                Save Permission
                            </button>
                        </div>

                        <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($permissionGroups as $groupName => $groupPerms)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                                    <div class="mb-3 text-xs font-black uppercase tracking-wider text-slate-500">{{ $groupName }}</div>
                                    <div class="space-y-2">
                                        @foreach ($groupPerms as $perm)
                                            @php($hasPermission = in_array($perm, $role['permissions'], true))
                                            <label class="flex items-start gap-3 rounded-xl border px-3 py-2 {{ $hasPermission ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white' }}">
                                                <input type="checkbox" name="permissions[]" value="{{ $perm }}" @checked($hasPermission)
                                                    class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                <span class="text-sm font-semibold text-slate-800">{{ $perm }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </form>
                @endif
            @endforeach
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-black text-slate-900">Detail Permission Matrix</h3>
                <p class="text-xs text-slate-500">Perbandingan lengkap hak akses per role.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50">
                            <th class="px-5 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Permission</th>
                            @foreach ($roles as $role)
                                <th class="px-5 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400 min-w-[100px]">
                                    {{ strtoupper($role['name']) }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($permissionGroups as $groupName => $groupPerms)
                            <tr>
                                <td colspan="{{ 1 + count($roles) }}" class="bg-slate-50 px-5 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                    {{ $groupName }}
                                </td>
                            </tr>
                            @foreach ($groupPerms as $perm)
                                <tr class="border-b border-slate-50">
                                    <td class="px-5 py-3">
                                        <code class="rounded-md bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-600">{{ $perm }}</code>
                                    </td>
                                    @foreach ($roles as $role)
                                        @php($hasPermission = in_array('*', $role['permissions'], true) || in_array($perm, $role['permissions'], true))
                                        <td class="px-5 py-3 text-center">
                                            @if ($hasPermission)
                                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">✓</span>
                                            @else
                                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-300">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
            Perubahan permission dari halaman ini disimpan sebagai override di database. Role <span class="font-bold">admin</span> tetap full access.
        </div>
    </div>
</x-app-layout>
