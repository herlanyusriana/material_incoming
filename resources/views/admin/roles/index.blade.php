<x-app-layout>
    <x-slot name="header">Role Management</x-slot>

    @php
        $permissionGroups = [
            'Dashboard' => ['view_dashboard'],
            'Planning' => ['view_planning', 'manage_planning', 'delete_planning'],
            'Production' => ['view_production', 'manage_production', 'manage_qc_inspection', 'manage_in_process_inspection', 'manage_final_inspection', 'manage_kanban_update'],
            'Material & Warehouse' => ['manage_incoming', 'manage_inventory'],
            'Purchasing' => ['manage_purchasing'],
            'Master Data' => ['manage_users', 'manage_parts', 'manage_customers'],
            'Outgoing & Subcon' => ['manage_outgoing', 'manage_subcon'],
        ];
        $permissionLabels = [
            'view_dashboard' => 'Lihat Dashboard',
            'view_planning' => 'Lihat Planning',
            'manage_planning' => 'Kelola Planning',
            'delete_planning' => 'Hapus Planning',
            'view_production' => 'Lihat Production',
            'manage_production' => 'Kelola Production',
            'manage_qc_inspection' => 'QC Inspection',
            'manage_in_process_inspection' => 'In Process Inspection',
            'manage_final_inspection' => 'Final Inspection',
            'manage_kanban_update' => 'Kanban Update',
            'manage_incoming' => 'Incoming Material',
            'manage_inventory' => 'Inventory',
            'manage_purchasing' => 'Purchasing',
            'manage_users' => 'Users & Roles',
            'manage_parts' => 'Parts Master',
            'manage_customers' => 'Customers',
            'manage_outgoing' => 'Outgoing',
            'manage_subcon' => 'Subcon',
        ];
    @endphp

    <div class="space-y-5">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <div class="font-bold">Cek lagi inputnya:</div>
                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-900">Roles</h1>
                <p class="mt-1 text-sm text-slate-500">Atur akses berdasarkan pekerjaan. Fokusnya: siapa boleh buka modul apa.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                Kelola User
            </a>
        </div>

        <div class="grid gap-5 xl:grid-cols-[360px_1fr]">
            <div class="space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-black text-slate-900">Tambah Role</h2>
                    <p class="mt-1 text-xs text-slate-500">Buat role baru, lalu centang aksesnya di panel kanan.</p>
                    <form action="{{ route('admin.roles.store') }}" method="POST" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Role Key</label>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="quality" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Nama Tampilan</label>
                            <input type="text" name="display_name" value="{{ old('display_name') }}" placeholder="Quality" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Catatan</label>
                            <input type="text" name="description" value="{{ old('description') }}" placeholder="Akses inspection dan laporan QC" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <button class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-black text-white hover:bg-indigo-700">Tambah Role</button>
                    </form>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-black text-slate-900">Daftar Role</h2>
                    <div class="mt-4 space-y-2">
                        @foreach ($roles as $role)
                            @php
                                $fullAccess = in_array('*', $role['permissions'], true);
                                $count = $fullAccess ? count($definedPermissions) : count(array_intersect($role['permissions'], $definedPermissions));
                            @endphp
                            <a href="#role-{{ $role['name'] }}" class="block rounded-xl border border-slate-200 p-3 hover:bg-slate-50">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-black text-slate-900">{{ $role['display_name'] ?? strtoupper($role['name']) }}</div>
                                        <div class="mt-0.5 text-xs text-slate-500">{{ $role['description'] ?: 'Tidak ada catatan' }}</div>
                                    </div>
                                    <div class="text-right text-xs font-bold text-slate-500">
                                        {{ $role['user_count'] }} user
                                        <div>{{ $fullAccess ? 'Full' : $count . '/' . count($definedPermissions) }}</div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                @foreach ($roles as $role)
                    @php($isAdmin = $role['name'] === 'admin')
                    @php($isFullAccess = in_array('*', $role['permissions'], true))
                    <form id="role-{{ $role['name'] }}" action="{{ route('admin.roles.update', $role['name']) }}" method="POST" class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        @csrf
                        @method('PUT')
                        <div class="flex flex-col gap-3 border-b border-slate-200 p-5 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-lg font-black text-slate-900">{{ $role['display_name'] ?? strtoupper($role['name']) }}</h2>
                                    @if ($isAdmin)
                                        <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-bold text-indigo-700">FULL ACCESS</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-slate-500">{{ $role['description'] ?: 'Custom role' }} · {{ $role['user_count'] }} user</p>
                            </div>
                            @if (!$isAdmin)
                                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-black text-white hover:bg-slate-800">Save Permission</button>
                            @endif
                        </div>

                        @if ($isAdmin)
                            <div class="p-5 text-sm text-slate-600">Admin selalu punya semua akses. Role ini sengaja tidak bisa diubah dari halaman ini.</div>
                        @else
                            <div class="grid gap-4 p-5 lg:grid-cols-2">
                                @foreach ($permissionGroups as $groupName => $permissions)
                                    <div class="rounded-xl border border-slate-200 p-4">
                                        <div class="mb-3 text-xs font-black uppercase tracking-wider text-slate-500">{{ $groupName }}</div>
                                        <div class="grid gap-2">
                                            @foreach ($permissions as $permission)
                                                @php($checked = in_array($permission, $role['permissions'], true) || $isFullAccess)
                                                <label @class([
                                                    'flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2 text-sm',
                                                    'border-emerald-200 bg-emerald-50 text-emerald-900' => $checked,
                                                    'border-slate-200 bg-white text-slate-700 hover:bg-slate-50' => !$checked,
                                                ])>
                                                    <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked($checked) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                    <span class="font-semibold">{{ $permissionLabels[$permission] ?? $permission }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </form>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
