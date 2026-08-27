<x-app-layout>
    @php
        $roleCounts = \App\Models\User::query()
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');
        $totalUsers = \App\Models\User::count();
    @endphp

    <x-page-header
        title="User Management"
        subtitle="Tambah akun, ubah role, reset password, dan hapus user dari satu layar."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => '#'],
            ['label' => 'User Management']
        ]"
    >
        <x-slot name="actions">
            <button type="button" @click="$dispatch('open-modal', 'create-user')" class="gci-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                </svg>
                <span>Tambah User</span>
            </button>
            <a href="{{ route('admin.roles.index') }}" class="gci-btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                </svg>
                <span>Kelola Role</span>
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

    {{-- Stat cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5 mb-6">
        <a href="{{ route('admin.users.index', request()->except('role')) }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 {{ $role === '' ? 'ring-2 ring-indigo-500 border-indigo-300' : '' }}">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold truncate">Semua User</div>
                    <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format($totalUsers) }}</div>
                    <div class="text-xs text-slate-400 mt-1">Total akun terdaftar</div>
                </div>
                <div class="w-11 h-11 bg-gradient-to-br from-slate-500 to-slate-700 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
            </div>
        </a>
        @foreach ($roles as $availableRole)
            <a href="{{ route('admin.users.index', array_merge(request()->except('page'), ['role' => $availableRole])) }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-0.5 {{ $role === $availableRole ? 'ring-2 ring-indigo-500 border-indigo-300' : '' }}">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold truncate">{{ strtoupper($availableRole) }}</div>
                        <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format((int) ($roleCounts[$availableRole] ?? 0)) }}</div>
                        <div class="text-xs text-slate-400 mt-1 truncate">User dengan role ini</div>
                    </div>
                    <div class="w-11 h-11 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Users table --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50/50 px-5 py-4">
            <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div class="relative max-w-xs w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama, username, email..." class="block w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-xl bg-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-colors">
                </div>
                <div class="flex items-center gap-2">
                    <select name="role" class="rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500/20">
                        <option value="">Semua Role</option>
                        @foreach ($roles as $availableRole)
                            <option value="{{ $availableRole }}" @selected($role === $availableRole)>{{ strtoupper($availableRole) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="gci-btn-primary gci-btn-sm">Filter</button>
                    @if($q !== '' || $role !== '')
                        <a href="{{ route('admin.users.index') }}" class="gci-btn-secondary gci-btn-sm">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                    <tr class="text-slate-600 text-xs uppercase tracking-wider">
                        <th class="px-4 py-3 text-left font-semibold">User</th>
                        <th class="px-4 py-3 text-left font-semibold">Role</th>
                        <th class="px-4 py-3 text-center font-semibold">Status</th>
                        <th class="px-4 py-3 text-right font-semibold w-40">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 truncate">{{ $user->name }}</div>
                                        <div class="text-xs text-slate-500 truncate">
                                            {{ $user->username }}
                                            @if($user->email)
                                                <span class="text-slate-300">•</span> {{ $user->email }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-100 text-indigo-700">
                                    {{ strtoupper($user->role) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                @if ($user->id === auth()->id())
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-slate-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                        </svg>
                                        Kamu
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        Active
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        @click="$dispatch('open-modal', 'edit-user-{{ $user->id }}')"
                                        class="gci-btn-icon bg-indigo-50 text-indigo-600 hover:bg-indigo-100"
                                        title="Edit User"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                        </svg>
                                    </button>
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Hapus user {{ addslashes($user->name) }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="gci-btn-icon bg-red-50 text-red-600 hover:bg-red-100" title="Delete User">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12m-9 4v5m6-5v5M9 7l.867-2.6A1 1 0 0 1 10.81 3.5h2.38a1 1 0 0 1 .943.9L15 7m-9 0h12v12a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V7Z" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-center">
                                    <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                        </svg>
                                    </div>
                                    <div class="text-sm font-semibold text-slate-700">Tidak ada user yang cocok</div>
                                    <div class="text-xs text-slate-500 mt-1">Coba ubah kata kunci atau filter role.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- Create User Modal --}}
    <x-modal name="create-user" maxWidth="md">
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            <div class="p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Tambah User Baru</h2>
                        <p class="mt-1 text-sm text-slate-500">Email boleh kosong. Password wajib diisi saat membuat user.</p>
                    </div>
                    <button type="button" @click="$dispatch('close-modal', 'create-user')" class="text-slate-400 hover:text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="px-6 pb-6 space-y-4">
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Nama</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Username</label>
                    <input type="text" name="username" value="{{ old('username') }}" required class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Opsional" class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Role</label>
                    <select name="role" required class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        @foreach ($roles as $availableRole)
                            <option value="{{ $availableRole }}" @selected(old('role') === $availableRole)>{{ strtoupper($availableRole) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Password</label>
                        <input type="password" name="password" required class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Confirm</label>
                        <input type="password" name="password_confirmation" required class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50/50 rounded-b-lg">
                <button type="button" @click="$dispatch('close-modal', 'create-user')" class="gci-btn-secondary gci-btn-sm">Batal</button>
                <button type="submit" class="gci-btn-primary gci-btn-sm">Tambah User</button>
            </div>
        </form>
    </x-modal>

    {{-- Edit User Modals --}}
    @foreach ($users as $user)
        <x-modal name="edit-user-{{ $user->id }}" maxWidth="md">
            <form action="{{ route('admin.users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-black text-slate-900">Edit User</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $user->name }}</p>
                        </div>
                        <button type="button" @click="$dispatch('close-modal', 'edit-user-{{ $user->id }}')" class="text-slate-400 hover:text-slate-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="px-6 pb-6 space-y-4">
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Nama</label>
                        <input type="text" name="name" value="{{ $user->name }}" required class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Username</label>
                            <input type="text" name="username" value="{{ $user->username }}" required class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Email</label>
                            <input type="email" name="email" value="{{ $user->email }}" placeholder="Opsional" class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Role</label>
                        <select name="role" class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                            @foreach ($roles as $availableRole)
                                <option value="{{ $availableRole }}" @selected($user->role === $availableRole)>{{ strtoupper($availableRole) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                        <div class="font-bold mb-1">Reset Password</div>
                        Kosongkan kedua field jika tidak ingin mengubah password.
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Password Baru</label>
                            <input type="password" name="password" placeholder="Kosongkan jika tidak diubah" class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Confirm Password</label>
                            <input type="password" name="password_confirmation" placeholder="Kosongkan jika tidak diubah" class="mt-1 w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50/50 rounded-b-lg">
                    <button type="button" @click="$dispatch('close-modal', 'edit-user-{{ $user->id }}')" class="gci-btn-secondary gci-btn-sm">Batal</button>
                    <button type="submit" class="gci-btn-primary gci-btn-sm">Simpan Perubahan</button>
                </div>
            </form>
        </x-modal>
    @endforeach
</x-app-layout>
