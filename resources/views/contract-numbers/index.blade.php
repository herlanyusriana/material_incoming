<x-app-layout>
    <x-slot name="header">
        Contract Numbers
    </x-slot>

    <div class="space-y-6" x-data="{ openCreate: false, openEditId: null }">
        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">Management Nomor Kontrak</h1>
                    <p class="mt-1 text-sm text-slate-500">Master nomor kontrak vendor agar flow subcon tidak perlu input manual terus.</p>
                </div>
                <button type="button" @click="openCreate = !openCreate" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Tambah Nomor Kontrak
                </button>
            </div>

            <div x-show="openCreate" x-cloak class="border-b border-slate-100 bg-slate-50 px-6 py-5">
                <form method="POST" action="{{ route('contract-numbers.store') }}" class="grid gap-4 lg:grid-cols-3">
                    @csrf
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Vendor</label>
                        <select name="vendor_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                            <option value="">Pilih vendor</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->vendor_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Nomor Kontrak</label>
                        <input type="text" name="contract_no" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Deskripsi</label>
                        <input type="text" name="description" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Effective From</label>
                        <input type="date" name="effective_from" value="{{ now()->toDateString() }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Effective To</label>
                        <input type="date" name="effective_to" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Status</label>
                        <select name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="lg:col-span-3">
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Notes</label>
                        <textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border-slate-200 text-sm"></textarea>
                    </div>
                    <div class="lg:col-span-3 flex justify-end gap-2">
                        <button type="button" @click="openCreate = false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Cancel</button>
                        <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Save</button>
                    </div>
                </form>
            </div>

            <div class="border-b border-slate-100 px-6 py-4">
                <form method="GET" class="grid gap-3 lg:grid-cols-4">
                    <input name="search" value="{{ $filters['search'] }}" class="rounded-xl border-slate-200 text-sm lg:col-span-2" placeholder="Cari nomor kontrak atau vendor">
                    <select name="vendor_id" class="rounded-xl border-slate-200 text-sm">
                        <option value="">Semua Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected($filters['vendorId'] == $vendor->id)>{{ $vendor->vendor_name }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="rounded-xl border-slate-200 text-sm">
                        <option value="">Semua Status</option>
                        <option value="active" @selected($filters['status'] === 'active')>Active</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
                    </select>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Nomor Kontrak</th>
                            <th class="px-4 py-3 font-semibold">Vendor</th>
                            <th class="px-4 py-3 font-semibold">Deskripsi</th>
                            <th class="px-4 py-3 font-semibold">Effective</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($contracts as $contract)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $contract->contract_no }}</div>
                                    <div class="text-xs text-slate-500">{{ $contract->notes ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $contract->vendor?->vendor_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $contract->description ?: '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div>{{ $contract->effective_from?->format('d M Y') ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">to {{ $contract->effective_to?->format('d M Y') ?: 'open' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $contract->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ ucfirst($contract->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            @click="openEditId = openEditId === {{ $contract->id }} ? null : {{ $contract->id }}">
                                            Edit
                                        </button>
                                        <form action="{{ route('contract-numbers.destroy', $contract) }}" method="POST" onsubmit="return confirm('Hapus nomor kontrak ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr x-show="openEditId === {{ $contract->id }}" x-cloak>
                                <td colspan="6" class="bg-slate-50 px-4 py-4">
                                    <form action="{{ route('contract-numbers.update', $contract) }}" method="POST" class="grid gap-3 lg:grid-cols-3">
                                        @csrf
                                        @method('PUT')
                                        <div>
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Vendor</label>
                                            <select name="vendor_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                                @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}" @selected($contract->vendor_id === $vendor->id)>{{ $vendor->vendor_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Nomor Kontrak</label>
                                            <input type="text" name="contract_no" value="{{ $contract->contract_no }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Deskripsi</label>
                                            <input type="text" name="description" value="{{ $contract->description }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Effective From</label>
                                            <input type="date" name="effective_from" value="{{ $contract->effective_from?->toDateString() }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Effective To</label>
                                            <input type="date" name="effective_to" value="{{ $contract->effective_to?->toDateString() }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Status</label>
                                            <select name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                                <option value="active" @selected($contract->status === 'active')>Active</option>
                                                <option value="inactive" @selected($contract->status === 'inactive')>Inactive</option>
                                            </select>
                                        </div>
                                        <div class="lg:col-span-3">
                                            <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Notes</label>
                                            <textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border-slate-200 text-sm">{{ $contract->notes }}</textarea>
                                        </div>
                                        <div class="lg:col-span-3 flex justify-end gap-2">
                                            <button type="button" @click="openEditId = null" class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-white">Cancel</button>
                                            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Update</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada nomor kontrak.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-6 py-4">
                {{ $contracts->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
