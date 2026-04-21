<x-app-layout>
    <x-slot name="header">
        Contract Numbers
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">Management Nomor Kontrak</h1>
                    <p class="mt-1 text-sm text-slate-500">Master nomor kontrak vendor agar flow subcon tidak perlu input manual terus.</p>
                </div>
                <a href="{{ route('contract-numbers.create') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Tambah Nomor Kontrak
                </a>
            </div>

            {{-- Filter --}}
            <div class="border-b border-slate-100 px-6 py-4">
                <form method="GET" class="grid gap-3 lg:grid-cols-4">
                    <input name="search" value="{{ $filters['search'] }}" class="rounded-xl border-slate-200 text-sm lg:col-span-2" placeholder="Cari nomor kontrak atau deskripsi">
                    <select name="vendor_id" class="rounded-xl border-slate-200 text-sm">
                        <option value="">Semua Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected($filters['vendorId'] == $vendor->id)>{{ $vendor->vendor_name }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-2">
                        <select name="status" class="w-full rounded-xl border-slate-200 text-sm">
                            <option value="">Semua Status</option>
                            <option value="active" @selected($filters['status'] === 'active')>Active</option>
                            <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
                        </select>
                        <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">Filter</button>
                    </div>
                </form>
            </div>

            {{-- List --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Nomor Kontrak</th>
                            <th class="px-4 py-3 font-semibold">Vendor</th>
                            <th class="px-4 py-3 font-semibold">Item Parts</th>
                            <th class="px-4 py-3 font-semibold">Periode</th>
                            <th class="px-4 py-3 font-semibold text-center">Status</th>
                            <th class="px-4 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($contracts as $contract)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-900">{{ $contract->contract_no }}</div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">{{ $contract->description ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700 font-semibold">{{ $contract->vendor?->vendor_name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-xs">
                                        @if($contract->items->count() > 0)
                                            <span class="inline-block px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold border border-indigo-200">{{ $contract->items->count() }} Items</span>
                                        @else
                                            <span class="text-slate-400 italic">No Items</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div class="font-semibold">{{ $contract->effective_from?->format('d M Y') ?: '-' }}</div>
                                    <div class="text-[10px] uppercase font-bold text-slate-500 mt-0.5">Exp: {{ $contract->effective_to?->format('d M Y') ?: 'OPEN' }}</div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-bold {{ $contract->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ strtoupper($contract->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('contract-numbers.show', $contract) }}" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100">
                                            Detail
                                        </a>
                                        <form action="{{ route('contract-numbers.destroy', $contract) }}" method="POST" onsubmit="return confirm('Hapus nomor kontrak beserta itemnya secara permanen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50">Del</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <div class="text-slate-400 font-semibold">Belum ada nomor kontrak.</div>
                                </td>
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
