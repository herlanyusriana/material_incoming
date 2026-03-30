<x-app-layout>
    <x-slot name="header">User Management</x-slot>

    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-8px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .animate-fade-in { animation: fadeInUp 0.4s ease-out both; }
        .animate-fade-in-1 { animation-delay: 0.05s; }
        .animate-fade-in-2 { animation-delay: 0.1s; }
        .animate-fade-in-3 { animation-delay: 0.15s; }
        .animate-fade-in-4 { animation-delay: 0.2s; }
        .card-hover { transition: all 0.25s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
        .row-hover { transition: all 0.15s ease; }
        .row-hover:hover { background: linear-gradient(135deg, rgba(99,102,241,0.03), rgba(139,92,246,0.03)); }
        .modal-slide { animation: slideIn 0.25s ease-out both; }
    </style>

    <div x-data="{
        showCreateModal: false,
        showEditModal: false,
        editUser: { id: null, name: '', username: '', email: '', role: '' },
        openEdit(user) {
            this.editUser = { ...user };
            this.showEditModal = true;
        }
    }" class="space-y-5">

        {{-- ══════════════ HEADER ══════════════ --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                        Admin Panel
                    </div>
                    <h1 class="mt-1 text-xl font-black text-slate-900">User Management</h1>
                    <p class="mt-1 text-xs text-slate-500">Kelola semua akun pengguna dan hak akses mereka di sini.</p>
                </div>
                <button @click="showCreateModal = true"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-indigo-600/20 hover:shadow-md hover:shadow-indigo-600/30 transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Tambah User
                </button>
            </div>
        </div>

        {{-- ══════════════ FLASH MESSAGES ══════════════ --}}
        @if (session('success'))
            <div class="animate-fade-in rounded-2xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-teal-50 px-5 py-3.5 text-sm font-semibold text-emerald-700 flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-emerald-500 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                    </svg>
                </div>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="animate-fade-in rounded-2xl border border-red-200 bg-gradient-to-r from-red-50 to-rose-50 px-5 py-3.5 text-sm font-semibold text-red-700 flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-red-500 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </div>
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="animate-fade-in rounded-2xl border border-red-200 bg-gradient-to-r from-red-50 to-rose-50 px-5 py-3.5 text-sm text-red-700">
                <div class="font-bold mb-1">Terdapat kesalahan:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ══════════════ STAT CARDS ══════════════ --}}
        @php
            $totalUsers = $users->total();
            $roleCounts = [];
            foreach ($roles as $r) {
                $roleCounts[$r] = \App\Models\User::where('role', $r)->count();
            }
            $roleColors = [
                'admin' => ['from-indigo-500 to-violet-500', 'bg-indigo-100 text-indigo-700'],
                'staff' => ['from-emerald-500 to-teal-500', 'bg-emerald-100 text-emerald-700'],
                'ppic' => ['from-amber-500 to-orange-500', 'bg-amber-100 text-amber-700'],
                'warehouse' => ['from-cyan-500 to-blue-500', 'bg-cyan-100 text-cyan-700'],
            ];
            $roleIcons = [
                'admin' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
                'staff' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
                'ppic' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z',
                'warehouse' => 'M21 8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8Z M7 6V4h10v2',
            ];
        @endphp

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-{{ 1 + count($roles) }}">
            {{-- Total Users Card --}}
            <div class="animate-fade-in animate-fade-in-1 card-hover rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Total Users</div>
                        <div class="mt-2 text-3xl font-black text-slate-900">{{ $totalUsers }}</div>
                        <div class="text-xs text-slate-400 mt-1">Semua pengguna terdaftar</div>
                    </div>
                    <div class="w-11 h-11 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-xl flex items-center justify-center shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Per-Role Cards --}}
            @foreach ($roles as $i => $r)
                @php
                    $colors = $roleColors[$r] ?? ['from-slate-500 to-slate-600', 'bg-slate-100 text-slate-700'];
                    $icon = $roleIcons[$r] ?? 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z';
                @endphp
                <div class="animate-fade-in animate-fade-in-{{ $i + 2 }} card-hover rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">{{ strtoupper($r) }}</div>
                            <div class="mt-2 text-3xl font-black text-slate-900">{{ $roleCounts[$r] }}</div>
                            <div class="text-xs text-slate-400 mt-1">user terdaftar</div>
                        </div>
                        <div class="w-11 h-11 bg-gradient-to-br {{ $colors[0] }} rounded-xl flex items-center justify-center shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                @foreach(explode(' M', $icon) as $j => $path)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $j === 0 ? $path : 'M' . $path }}"/>
                                @endforeach
                            </svg>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ══════════════ SEARCH & FILTER + TABLE ══════════════ --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            {{-- Search Bar --}}
            <div class="p-5 border-b border-slate-100">
                <form method="GET" class="flex flex-col md:flex-row gap-3 items-end">
                    <div class="flex-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Cari User</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                                </svg>
                            </div>
                            <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama, username, atau email..."
                                class="w-full rounded-xl border-slate-200 pl-10 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="w-full md:w-44">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Role</label>
                        <select name="role" class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Semua Role</option>
                            @foreach($roles as $availableRole)
                                <option value="{{ $availableRole }}" @selected($role === $availableRole)>{{ strtoupper($availableRole) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.users.index') }}"
                            class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                            Reset
                        </a>
                        <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 transition-colors shadow-sm">
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80">
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">User</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Role</th>
                            <th class="px-5 py-3.5 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Dibuat</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-bold uppercase tracking-wider text-slate-400">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            @php
                                $uColors = $roleColors[$user->role] ?? ['from-slate-500 to-slate-600', 'bg-slate-100 text-slate-700'];
                                $initials = collect(explode(' ', $user->name))->map(fn($w) => strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('');
                            @endphp
                            <tr class="row-hover">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $uColors[0] }} flex items-center justify-center shadow-sm shrink-0">
                                            <span class="text-xs font-bold text-white">{{ $initials }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-bold text-slate-900 truncate">{{ $user->name }}</div>
                                            <div class="text-xs text-slate-500 truncate">{{ '@' . $user->username }}</div>
                                            <div class="text-[11px] text-slate-400 truncate">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold {{ $uColors[1] }} ring-1 ring-inset {{ str_replace('bg-', 'ring-', explode(' ', $uColors[1])[0]) }}/30">
                                        {{ strtoupper($user->role) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-xs text-slate-500">{{ $user->created_at?->format('d M Y') ?? '-' }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $user->created_at?->format('H:i') ?? '' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openEdit({
                                            id: {{ $user->id }},
                                            name: '{{ addslashes($user->name) }}',
                                            username: '{{ addslashes($user->username) }}',
                                            email: '{{ addslashes($user->email) }}',
                                            role: '{{ $user->role }}'
                                        })"
                                            class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 text-slate-500 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200 transition-all"
                                            title="Edit user">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                            </svg>
                                        </button>
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus user {{ addslashes($user->name) }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 text-slate-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-all"
                                                title="Hapus user">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900">Belum ada user</div>
                                            <div class="text-xs text-slate-400 mt-0.5">Tambahkan user pertama dengan tombol di atas.</div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($users->hasPages())
                <div class="border-t border-slate-100 bg-slate-50/80 px-5 py-3">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        {{-- ══════════════ CREATE MODAL ══════════════ --}}
        <template x-teleport="body">
            <div x-show="showCreateModal" x-cloak
                class="fixed inset-0 z-[60] flex items-center justify-center p-4"
                @keydown.escape.window="showCreateModal = false">
                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showCreateModal = false"></div>
                <div class="modal-slide relative w-full max-w-lg rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200 overflow-hidden"
                    x-show="showCreateModal" x-transition>
                    {{-- Modal Header --}}
                    <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-white">Tambah User Baru</h3>
                                <p class="text-xs text-indigo-200 mt-0.5">Buat akun baru dan tentukan role-nya</p>
                            </div>
                            <button @click="showCreateModal = false"
                                class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    {{-- Modal Body --}}
                    <form action="{{ route('admin.users.store') }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Nama</label>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Nama lengkap">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Username</label>
                                <input type="text" name="username" value="{{ old('username') }}" required
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="username">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}" required
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="email@company.com">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Role</label>
                                <select name="role" required
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($roles as $availableRole)
                                        <option value="{{ $availableRole }}" @selected(old('role') === $availableRole)>{{ strtoupper($availableRole) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Password</label>
                                <input type="password" name="password" required
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="••••••••">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Konfirmasi</label>
                                <input type="password" name="password_confirmation" required
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="••••••••">
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showCreateModal = false"
                                class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                                Batal
                            </button>
                            <button type="submit"
                                class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:shadow-md transition-all">
                                Simpan User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        {{-- ══════════════ EDIT MODAL ══════════════ --}}
        <template x-teleport="body">
            <div x-show="showEditModal" x-cloak
                class="fixed inset-0 z-[60] flex items-center justify-center p-4"
                @keydown.escape.window="showEditModal = false">
                <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showEditModal = false"></div>
                <div class="modal-slide relative w-full max-w-lg rounded-2xl bg-white shadow-2xl ring-1 ring-slate-200 overflow-hidden"
                    x-show="showEditModal" x-transition>
                    {{-- Modal Header --}}
                    <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-bold text-white">Edit User</h3>
                                <p class="text-xs text-indigo-200 mt-0.5">Perbarui data dan hak akses pengguna</p>
                            </div>
                            <button @click="showEditModal = false"
                                class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    {{-- Modal Body --}}
                    <form :action="`{{ url('admin/users') }}/${editUser.id}`" method="POST" class="p-6 space-y-4">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Nama</label>
                                <input type="text" name="name" x-model="editUser.name" required
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Username</label>
                                <input type="text" name="username" x-model="editUser.username" required
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Email</label>
                                <input type="email" name="email" x-model="editUser.email" required
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Role</label>
                                <select name="role" x-model="editUser.role" required
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($roles as $availableRole)
                                        <option value="{{ $availableRole }}">{{ strtoupper($availableRole) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Password Baru</label>
                                <input type="password" name="password"
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Kosongkan jika tidak diubah">
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Konfirmasi</label>
                                <input type="password" name="password_confirmation"
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Konfirmasi password">
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showEditModal = false"
                                class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                                Batal
                            </button>
                            <button type="submit"
                                class="rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-2.5 text-sm font-bold text-white shadow-sm hover:shadow-md transition-all">
                                Perbarui User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </template>

    </div>
</x-app-layout>
