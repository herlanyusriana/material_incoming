<x-app-layout>
    <x-slot name="header">User Management</x-slot>

    @php
        $roleCounts = \App\Models\User::query()
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');
        $totalUsers = \App\Models\User::count();
    @endphp

    <div class="space-y-5">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ session('error') }}
            </div>
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
                <h1 class="text-2xl font-black text-slate-900">Users</h1>
                <p class="mt-1 text-sm text-slate-500">Tambah akun, ubah role, reset password, dan hapus user dari satu layar.</p>
            </div>
            <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                Kelola Role
            </a>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <a href="{{ route('admin.users.index', request()->except('role')) }}" @class([
                'rounded-xl border p-4 shadow-sm',
                'border-indigo-200 bg-indigo-50' => $role === '',
                'border-slate-200 bg-white hover:bg-slate-50' => $role !== '',
            ])>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Semua User</div>
                <div class="mt-1 text-2xl font-black text-slate-900">{{ number_format($totalUsers) }}</div>
            </a>
            @foreach ($roles as $availableRole)
                <a href="{{ route('admin.users.index', array_merge(request()->except('page'), ['role' => $availableRole])) }}" @class([
                    'rounded-xl border p-4 shadow-sm',
                    'border-indigo-200 bg-indigo-50' => $role === $availableRole,
                    'border-slate-200 bg-white hover:bg-slate-50' => $role !== $availableRole,
                ])>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ strtoupper($availableRole) }}</div>
                    <div class="mt-1 text-2xl font-black text-slate-900">{{ number_format((int) ($roleCounts[$availableRole] ?? 0)) }}</div>
                </a>
            @endforeach
        </div>

        <div class="grid gap-5 xl:grid-cols-[360px_1fr]">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-black text-slate-900">Tambah User</h2>
                <p class="mt-1 text-xs text-slate-500">Email boleh kosong. Password wajib diisi saat membuat user.</p>

                <form action="{{ route('admin.users.store') }}" method="POST" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Nama</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Username</label>
                        <input type="text" name="username" value="{{ old('username') }}" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="Opsional" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Role</label>
                        <select name="role" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($roles as $availableRole)
                                <option value="{{ $availableRole }}" @selected(old('role') === $availableRole)>{{ strtoupper($availableRole) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Password</label>
                            <input type="password" name="password" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Confirm</label>
                            <input type="password" name="password_confirmation" required class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <button class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-black text-white hover:bg-indigo-700">
                        Tambah User
                    </button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 p-4">
                    <form method="GET" class="grid gap-3 lg:grid-cols-[1fr_180px_auto_auto] lg:items-end">
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Cari</label>
                            <input type="text" name="q" value="{{ $q }}" placeholder="Nama, username, email..." class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Role</label>
                            <select name="role" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Semua</option>
                                @foreach ($roles as $availableRole)
                                    <option value="{{ $availableRole }}" @selected($role === $availableRole)>{{ strtoupper($availableRole) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">Filter</button>
                        <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-bold text-slate-700 hover:bg-slate-50">Reset</a>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold">User</th>
                                <th class="px-4 py-3 text-left font-bold">Role</th>
                                <th class="px-4 py-3 text-left font-bold">Password Baru</th>
                                <th class="px-4 py-3 text-right font-bold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($users as $user)
                                <tr class="align-top hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="grid gap-2">
                                            <input form="update-user-{{ $user->id }}" type="text" name="name" value="{{ $user->name }}" class="w-full rounded-lg border-slate-300 text-sm font-bold focus:border-indigo-500 focus:ring-indigo-500">
                                            <div class="grid gap-2 md:grid-cols-2">
                                                <input form="update-user-{{ $user->id }}" type="text" name="username" value="{{ $user->username }}" class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                                <input form="update-user-{{ $user->id }}" type="email" name="email" value="{{ $user->email }}" placeholder="Email optional" class="w-full rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <select form="update-user-{{ $user->id }}" name="role" class="w-full min-w-[140px] rounded-lg border-slate-300 text-sm font-bold focus:border-indigo-500 focus:ring-indigo-500">
                                            @foreach ($roles as $availableRole)
                                                <option value="{{ $availableRole }}" @selected($user->role === $availableRole)>{{ strtoupper($availableRole) }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="grid gap-2">
                                            <input form="update-user-{{ $user->id }}" type="password" name="password" placeholder="Kosongkan jika tidak diubah" class="w-full min-w-[190px] rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                            <input form="update-user-{{ $user->id }}" type="password" name="password_confirmation" placeholder="Confirm password" class="w-full min-w-[190px] rounded-lg border-slate-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <button form="update-user-{{ $user->id }}" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-black text-white hover:bg-indigo-700">Save</button>
                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Hapus user {{ addslashes($user->name) }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-black text-red-700 hover:bg-red-50">Delete</button>
                                            </form>
                                        </div>
                                        <form id="update-user-{{ $user->id }}" action="{{ route('admin.users.update', $user) }}" method="POST" class="hidden">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center text-slate-400">Tidak ada user yang cocok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="border-t border-slate-200 px-4 py-3">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
