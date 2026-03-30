<x-app-layout>
    <x-slot name="header">Role Management</x-slot>

    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeInUp 0.4s ease-out both; }
        .animate-fade-in-1 { animation-delay: 0.05s; }
        .animate-fade-in-2 { animation-delay: 0.1s; }
        .animate-fade-in-3 { animation-delay: 0.15s; }
        .animate-fade-in-4 { animation-delay: 0.2s; }
        .card-hover { transition: all 0.25s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
        .perm-row { transition: all 0.15s ease; }
        .perm-row:hover { background: linear-gradient(135deg, rgba(99,102,241,0.03), rgba(139,92,246,0.03)); }
    </style>

    @php
        $roleStyles = [
            'admin' => [
                'gradient' => 'from-indigo-600 to-violet-600',
                'bg' => 'bg-indigo-100 text-indigo-700',
                'ring' => 'ring-indigo-100',
                'dot' => 'bg-indigo-500',
                'icon' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
                'label' => 'Super Admin',
                'desc' => 'Full system access',
            ],
            'staff' => [
                'gradient' => 'from-emerald-500 to-teal-500',
                'bg' => 'bg-emerald-100 text-emerald-700',
                'ring' => 'ring-emerald-100',
                'dot' => 'bg-emerald-500',
                'icon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
                'label' => 'Staff',
                'desc' => 'Production & material access',
            ],
            'ppic' => [
                'gradient' => 'from-amber-500 to-orange-500',
                'bg' => 'bg-amber-100 text-amber-700',
                'ring' => 'ring-amber-100',
                'dot' => 'bg-amber-500',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z',
                'label' => 'PPIC',
                'desc' => 'Planning & subcon access',
            ],
            'warehouse' => [
                'gradient' => 'from-cyan-500 to-blue-500',
                'bg' => 'bg-cyan-100 text-cyan-700',
                'ring' => 'ring-cyan-100',
                'dot' => 'bg-cyan-500',
                'icon' => 'M21 8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8Z',
                'label' => 'Warehouse',
                'desc' => 'Incoming material access',
            ],
        ];

        // Group permissions by module
        $permissionGroups = [
            'Dashboard' => ['view_dashboard'],
            'Planning' => ['view_planning', 'manage_planning', 'delete_planning'],
            'Production' => ['view_production', 'manage_production'],
            'Material & Warehouse' => ['manage_incoming', 'manage_inventory'],
            'Purchasing' => ['manage_purchasing'],
            'Master Data' => ['manage_users', 'manage_parts', 'manage_customers'],
            'Outgoing & Subcon' => ['manage_outgoing', 'manage_subcon'],
        ];
    @endphp

    <div class="space-y-5">

        {{-- ══════════════ HEADER ══════════════ --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                        Admin Panel
                    </div>
                    <h1 class="mt-1 text-xl font-black text-slate-900">Role & Permission Matrix</h1>
                    <p class="mt-1 text-xs text-slate-500">Visualisasi hak akses per role yang dikonfigurasi di sistem.</p>
                </div>
                <a href="{{ route('admin.users.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                    </svg>
                    Kelola Users
                </a>
            </div>
        </div>

        {{-- ══════════════ ROLE SUMMARY CARDS ══════════════ --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-{{ count($roles) }}">
            @foreach ($roles as $i => $role)
                @php
                    $style = $roleStyles[$role['name']] ?? [
                        'gradient' => 'from-slate-500 to-slate-600',
                        'bg' => 'bg-slate-100 text-slate-700',
                        'ring' => 'ring-slate-100',
                        'dot' => 'bg-slate-500',
                        'icon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
                        'label' => ucfirst($role['name']),
                        'desc' => 'Custom role',
                    ];
                    $isFullAccess = in_array('*', $role['permissions'], true);
                    $grantedCount = $isFullAccess ? count($definedPermissions) : count(array_intersect($role['permissions'], $definedPermissions));
                    $totalPerms = count($definedPermissions);
                    $percentage = $totalPerms > 0 ? round(($grantedCount / $totalPerms) * 100) : 0;
                @endphp
                <div class="animate-fade-in animate-fade-in-{{ $i + 1 }} card-hover rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    {{-- Gradient Header --}}
                    <div class="bg-gradient-to-r {{ $style['gradient'] }} px-5 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white/15 backdrop-blur-sm flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        @foreach(explode(' M', $style['icon']) as $j => $path)
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $j === 0 ? $path : 'M' . $path }}"/>
                                        @endforeach
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-black text-white">{{ strtoupper($role['name']) }}</h3>
                                    <p class="text-[11px] text-white/70 font-medium">{{ $style['desc'] }}</p>
                                </div>
                            </div>
                            @if($isFullAccess)
                                <span class="inline-flex items-center gap-1 rounded-lg bg-white/20 backdrop-blur-sm px-2.5 py-1 text-[10px] font-bold text-white uppercase tracking-wider">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                    </svg>
                                    Full
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="p-5">
                        {{-- Stats Row --}}
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-lg font-black text-slate-900">{{ $role['user_count'] }}</div>
                                    <div class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold -mt-0.5">Users</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-black text-slate-900">{{ $grantedCount }}<span class="text-xs font-medium text-slate-400">/{{ $totalPerms }}</span></div>
                                <div class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold -mt-0.5">Permissions</div>
                            </div>
                        </div>

                        {{-- Progress Bar --}}
                        <div class="mb-4">
                            <div class="flex items-center justify-between text-[10px] mb-1">
                                <span class="font-bold uppercase tracking-wider text-slate-400">Access Level</span>
                                <span class="font-bold text-slate-600">{{ $percentage }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r {{ $style['gradient'] }} transition-all duration-700"
                                    style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>

                        {{-- Permission List --}}
                        <div class="space-y-1.5">
                            @foreach ($permissionGroups as $groupName => $groupPerms)
                                @php
                                    $hasAny = false;
                                    foreach ($groupPerms as $p) {
                                        if ($isFullAccess || in_array($p, $role['permissions'], true)) {
                                            $hasAny = true;
                                            break;
                                        }
                                    }
                                @endphp
                                <div class="perm-row flex items-center justify-between rounded-lg px-3 py-2 {{ $hasAny ? 'bg-emerald-50/60' : 'bg-slate-50/60' }}">
                                    <div class="flex items-center gap-2">
                                        @if($hasAny)
                                            <div class="w-5 h-5 rounded-md bg-emerald-500 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-5 h-5 rounded-md bg-slate-200 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                                </svg>
                                            </div>
                                        @endif
                                        <span class="text-xs font-semibold {{ $hasAny ? 'text-emerald-700' : 'text-slate-400' }}">{{ $groupName }}</span>
                                    </div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider {{ $hasAny ? 'text-emerald-600' : 'text-slate-300' }}">
                                        @php
                                            $groupGranted = 0;
                                            foreach ($groupPerms as $p) {
                                                if ($isFullAccess || in_array($p, $role['permissions'], true)) $groupGranted++;
                                            }
                                        @endphp
                                        {{ $groupGranted }}/{{ count($groupPerms) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ══════════════ DETAIL PERMISSION MATRIX TABLE ══════════════ --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-slate-900">Detail Permission Matrix</h3>
                        <p class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Perbandingan lengkap hak akses per role</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50/80">
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400 sticky left-0 bg-slate-50/80 z-10">Permission</th>
                            @foreach ($roles as $role)
                                @php $style = $roleStyles[$role['name']] ?? ['gradient' => 'from-slate-500 to-slate-600', 'dot' => 'bg-slate-500']; @endphp
                                <th class="px-5 py-3.5 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400 min-w-[100px]">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full {{ $style['dot'] }}"></span>
                                        {{ strtoupper($role['name']) }}
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($permissionGroups as $groupName => $groupPerms)
                            {{-- Group Header --}}
                            <tr>
                                <td colspan="{{ 1 + count($roles) }}" class="px-5 py-2.5 bg-slate-50/50">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $groupName }}</span>
                                </td>
                            </tr>
                            @foreach ($groupPerms as $perm)
                                <tr class="perm-row border-b border-slate-50">
                                    <td class="px-5 py-3 sticky left-0 bg-white z-10">
                                        <code class="text-xs font-semibold text-slate-600 bg-slate-50 px-2 py-0.5 rounded-md">{{ $perm }}</code>
                                    </td>
                                    @foreach ($roles as $role)
                                        @php $has = in_array('*', $role['permissions'], true) || in_array($perm, $role['permissions'], true); @endphp
                                        <td class="px-5 py-3 text-center">
                                            @if($has)
                                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                    </svg>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                                    </svg>
                                                </span>
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

        {{-- ══════════════ INFO NOTE ══════════════ --}}
        <div class="rounded-2xl border border-slate-200 bg-gradient-to-r from-slate-50 to-indigo-50/30 p-4 flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0 mt-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
                </svg>
            </div>
            <div>
                <div class="text-sm font-bold text-slate-700">Roles berbasis konfigurasi sistem</div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Halaman ini menampilkan matrix role-permission yang aktif dipakai aplikasi.
                    Untuk mengubah permission, edit file <code class="bg-slate-200/80 px-1.5 py-0.5 rounded text-[11px] font-semibold text-slate-600">config/role_permissions.php</code>.
                </p>
            </div>
        </div>

    </div>
</x-app-layout>
