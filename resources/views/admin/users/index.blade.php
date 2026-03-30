<x-app-layout>
    <x-slot name="header">User Management</x-slot>

    <div class="py-3">
        <div class="px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="xl:col-span-1">
                    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                        <div class="mb-4">
                            <h2 class="text-lg font-semibold text-slate-900">Add User</h2>
                            <p class="text-sm text-slate-500">Buat akun baru dan tentukan role-nya.</p>
                        </div>

                        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-semibold text-slate-600">Name</label>
                                <input type="text" name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600">Username</label>
                                <input type="text" name="username" value="{{ old('username') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600">Role</label>
                                <select name="role" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                    @foreach($roles as $availableRole)
                                        <option value="{{ $availableRole }}" @selected(old('role') === $availableRole)>{{ strtoupper($availableRole) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600">Password</label>
                                <input type="password" name="password" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600">Confirm Password</label>
                                <input type="password" name="password_confirmation" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                            </div>
                            <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                                Save User
                            </button>
                        </form>
                    </div>
                </div>

                <div class="xl:col-span-2">
                    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-slate-200">
                            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-600">Search</label>
                                    <input type="text" name="q" value="{{ $q }}" placeholder="Name / username / email" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600">Role</label>
                                    <select name="role" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                        <option value="">All</option>
                                        @foreach($roles as $availableRole)
                                            <option value="{{ $availableRole }}" @selected($role === $availableRole)>{{ strtoupper($availableRole) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="md:col-span-3 flex justify-end gap-2">
                                    <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                                    <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Filter</button>
                                </div>
                            </form>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                        <th class="px-4 py-3">User</th>
                                        <th class="px-4 py-3">Role</th>
                                        <th class="px-4 py-3">Update Access</th>
                                        <th class="px-4 py-3 text-right">Delete</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($users as $user)
                                        <tr>
                                            <td class="px-4 py-4 align-top">
                                                <div class="font-semibold text-slate-900">{{ $user->name }}</div>
                                                <div class="text-xs text-slate-500">{{ $user->username }}</div>
                                                <div class="text-xs text-slate-400">{{ $user->email }}</div>
                                            </td>
                                            <td class="px-4 py-4 align-top">
                                                <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ strtoupper($user->role) }}</span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <form action="{{ route('admin.users.update', $user) }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="text" name="name" value="{{ $user->name }}" class="rounded-xl border-slate-200 text-sm" required>
                                                    <input type="text" name="username" value="{{ $user->username }}" class="rounded-xl border-slate-200 text-sm" required>
                                                    <input type="email" name="email" value="{{ $user->email }}" class="rounded-xl border-slate-200 text-sm md:col-span-2" required>
                                                    <select name="role" class="rounded-xl border-slate-200 text-sm" required>
                                                        @foreach($roles as $availableRole)
                                                            <option value="{{ $availableRole }}" @selected($user->role === $availableRole)>{{ strtoupper($availableRole) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="password" name="password" placeholder="New password (optional)" class="rounded-xl border-slate-200 text-sm">
                                                    <input type="password" name="password_confirmation" placeholder="Confirm password" class="rounded-xl border-slate-200 text-sm md:col-span-2">
                                                    <div class="md:col-span-2 flex justify-end">
                                                        <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                                            Update
                                                        </button>
                                                    </div>
                                                </form>
                                            </td>
                                            <td class="px-4 py-4 align-top text-right">
                                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Hapus user ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="rounded-xl border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-10 text-center text-slate-500">Belum ada user.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="border-t border-slate-200 bg-slate-50 px-4 py-3">
                            {{ $users->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
