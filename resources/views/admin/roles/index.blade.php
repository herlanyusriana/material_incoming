<x-app-layout>
    <x-slot name="header">Role Management</x-slot>

    <div class="py-3">
        <div class="px-4 sm:px-6 lg:px-8 space-y-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                @foreach($roles as $role)
                    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">{{ strtoupper($role['name']) }}</h2>
                                <p class="text-sm text-slate-500">{{ $role['user_count'] }} user</p>
                            </div>
                            @if(in_array('*', $role['permissions'], true))
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">FULL ACCESS</span>
                            @endif
                        </div>

                        <div class="space-y-2">
                            @forelse($definedPermissions as $permission)
                                @php($hasPermission = in_array('*', $role['permissions'], true) || in_array($permission, $role['permissions'], true))
                                <div class="flex items-center justify-between rounded-xl border px-3 py-2 {{ $hasPermission ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50' }}">
                                    <span class="text-xs font-semibold {{ $hasPermission ? 'text-emerald-700' : 'text-slate-500' }}">{{ $permission }}</span>
                                    <span class="text-[10px] font-bold {{ $hasPermission ? 'text-emerald-700' : 'text-slate-400' }}">{{ $hasPermission ? 'YES' : 'NO' }}</span>
                                </div>
                            @empty
                                <div class="text-sm text-slate-500">Belum ada permission terdefinisi.</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                Role saat ini masih berbasis config sistem. Halaman ini menampilkan matrix role-permission yang aktif dipakai aplikasi.
            </div>
        </div>
    </div>
</x-app-layout>
