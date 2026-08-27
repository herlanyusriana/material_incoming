<x-app-layout>
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

    <x-page-header
        title="Role Management"
        subtitle="Atur akses berdasarkan pekerjaan. Fokusnya: siapa boleh buka modul apa."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => '#'],
            ['label' => 'Role Management']
        ]"
    >
        <x-slot name="actions">
            <button type="button" @click="$dispatch('open-modal', 'create-role')" class="gci-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Tambah Role</span>
            </button>
            <a href="{{ route('admin.users.index') }}" class="gci-btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <span>Kelola User</span>
            </a>
        </x-slot>
    </x-page-header>

    {{-- Notifications --}}
    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 animate-fade-in-up">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 animate-fade-in-up">
            {{ session('error') }}
        </div>
    @endif
    @if (isset($errors) && $errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 animate-fade-in-up">
            <div class="font-bold">Cek lagi inputnya:</div>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Stats summary --}}
    <div class="grid gap-4 sm:grid-cols-3 mb-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Total Role</div>
            <div class="mt-2 text-3xl font-black text-slate-900">{{ count($roles) }}</div>
            <div class="text-xs text-slate-400 mt-1">Role terdaftar</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">System Role</div>
            <div class="mt-2 text-3xl font-black text-slate-900">{{ collect($roles)->where('is_system', true)->count() }}</div>
            <div class="text-xs text-slate-400 mt-1">Bawaan aplikasi</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Custom Role</div>
            <div class="mt-2 text-3xl font-black text-slate-900">{{ collect($roles)->where('is_system', false)->count() }}</div>
            <div class="text-xs text-slate-400 mt-1">Dibuat pengguna</div>
        </div>
    </div>

    {{-- Role cards --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($roles as $role)
            @php
                $isAdmin = $role['name'] === 'admin';
                $isFullAccess = in_array('*', $role['permissions'], true);
                $fullAccess = $isFullAccess;
                $count = $fullAccess ? count($definedPermissions) : count(array_intersect($role['permissions'], $definedPermissions));
                $roleColors = [
                    'admin' => ['bg' => 'from-rose-500 to-rose-600', 'badge' => 'bg-rose-100 text-rose-700'],
                    'super-admin' => ['bg' => 'from-rose-500 to-rose-600', 'badge' => 'bg-rose-100 text-rose-700'],
                    'quality' => ['bg' => 'from-amber-500 to-amber-600', 'badge' => 'bg-amber-100 text-amber-700'],
                    'production' => ['bg' => 'from-sky-500 to-sky-600', 'badge' => 'bg-sky-100 text-sky-700'],
                    'warehouse' => ['bg' => 'from-emerald-500 to-emerald-600', 'badge' => 'bg-emerald-100 text-emerald-700'],
                    'planning' => ['bg' => 'from-violet-500 to-violet-600', 'badge' => 'bg-violet-100 text-violet-700'],
                ];
                $c = $roleColors[$role['name']] ?? ['bg' => 'from-indigo-500 to-indigo-600', 'badge' => 'bg-indigo-100 text-indigo-700'];
            @endphp
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 overflow-hidden flex flex-col">
                {{-- Card header --}}
                <div class="p-5 border-b border-slate-100">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 bg-gradient-to-br {{ $c['bg'] }} rounded-xl flex items-center justify-center shadow-sm shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h2 class="text-base font-black text-slate-900 truncate">{{ $role['display_name'] ?? strtoupper($role['name']) }}</h2>
                                </div>
                                <div class="text-xs text-slate-500 truncate">{{ $role['name'] }}</div>
                            </div>
                        </div>
                        @if ($isAdmin)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $c['badge'] }} shrink-0">FULL ACCESS</span>
                        @endif
                    </div>
                </div>

                {{-- Card body --}}
                <div class="p-5 flex-1">
                    <p class="text-sm text-slate-600 min-h-[2.5rem]">{{ $role['description'] ?: 'Tidak ada catatan untuk role ini.' }}</p>
                    <div class="mt-4 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            <span class="font-semibold">{{ $role['user_count'] }} user</span>
                        </div>
                        <div class="text-xs font-bold text-slate-500">
                            @if ($isFullAccess)
                                <span class="inline-flex items-center gap-1 text-emerald-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Full Access
                                </span>
                            @else
                                {{ $count }}/{{ count($definedPermissions) }} permission
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Card footer --}}
                <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                    @if ($isAdmin)
                        <div class="text-xs text-slate-400 text-center py-1">Admin selalu punya semua akses</div>
                    @else
                        <button
                            type="button"
                            @click="$dispatch('open-modal', 'edit-role-{{ $role['name'] }}')"
                            class="w-full gci-btn-primary gci-btn-sm"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                            </svg>
                            Edit Permission
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Create Role Modal --}}
    <x-modal name="create-role" maxWidth="md">
        <form action="{{ route('admin.roles.store') }}" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Tambah Role Baru</h2>
                        <p class="mt-1 text-sm text-slate-500">Buat role baru, lalu atur aksesnya di panel permission.</p>
                    </div>
                    <button type="button" @click="$dispatch('close-modal', 'create-role')" class="text-slate-400 hover:text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="px-6 pb-6 space-y-4">
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Role Key</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="quality" required class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <p class="mt-1 text-xs text-slate-400">Huruf kecil, tanpa spasi. Contoh: quality, supervisor, checker.</p>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Nama Tampilan</label>
                    <input type="text" name="display_name" value="{{ old('display_name') }}" placeholder="Quality" class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Deskripsi</label>
                    <input type="text" name="description" value="{{ old('description') }}" placeholder="Akses inspection dan laporan QC" class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                </div>
            </div>

            <div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50/50 rounded-b-lg">
                <button type="button" @click="$dispatch('close-modal', 'create-role')" class="gci-btn-secondary gci-btn-sm">Batal</button>
                <button type="submit" class="gci-btn-primary gci-btn-sm">Tambah Role</button>
            </div>
        </form>
    </x-modal>

    {{-- Edit Permission Modals --}}
    @foreach ($roles as $role)
        @if ($role['name'] !== 'admin')
            <x-modal name="edit-role-{{ $role['name'] }}" maxWidth="2xl">
                <form action="{{ route('admin.roles.update', $role['name']) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="text-lg font-black text-slate-900">Edit Permission — {{ $role['display_name'] ?? strtoupper($role['name']) }}</h2>
                                <p class="mt-1 text-sm text-slate-500">Centang modul yang boleh diakses role ini.</p>
                            </div>
                            <button type="button" @click="$dispatch('close-modal', 'edit-role-{{ $role['name'] }}')" class="text-slate-400 hover:text-slate-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 pb-6">
                        @php($isFullAccess = in_array('*', $role['permissions'], true))
                        <div class="grid gap-4 lg:grid-cols-2 max-h-[50vh] overflow-y-auto pr-1">
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
                    </div>

                    <div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50/50 rounded-b-lg">
                        <button type="button" @click="$dispatch('close-modal', 'edit-role-{{ $role['name'] }}')" class="gci-btn-secondary gci-btn-sm">Batal</button>
                        <button type="submit" class="gci-btn-primary gci-btn-sm">Simpan Permission</button>
                    </div>
                </form>
            </x-modal>
        @endif
    @endforeach
</x-app-layout>
