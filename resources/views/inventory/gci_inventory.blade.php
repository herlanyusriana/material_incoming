<x-app-layout>
    <x-slot name="header">
        Inventory • GCI Inventory
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Search</label>
                            <input name="search" value="{{ $search }}" class="mt-1 rounded-xl border-slate-200" placeholder="Part no / name / model">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Class</label>
                            <select name="classification" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                @foreach (['FG', 'WIP', 'RM'] as $c)
                                    <option value="{{ $c }}" @selected($classification === $c)>{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Status</label>
                            <select name="status" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                <option value="active" @selected($status === 'active')>active</option>
                                <option value="inactive" @selected($status === 'inactive')>inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Rows</label>
                            <select name="per_page" class="mt-1 rounded-xl border-slate-200">
                                @foreach([25,50,100,200] as $n)
                                    <option value="{{ $n }}" @selected((int) $perPage === $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                    </form>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('inventory.transfers.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold">
                            Inventory Transfers
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Part No</th>
                                <th class="px-4 py-3 text-left font-semibold">Name</th>
                                <th class="px-4 py-3 text-left font-semibold">Model</th>
                                <th class="px-4 py-3 text-left font-semibold">Class</th>
                                <th class="px-4 py-3 text-right font-semibold">On Hand</th>
                                <th class="px-4 py-3 text-right font-semibold">On Order</th>
                                <th class="px-4 py-3 text-left font-semibold">As Of</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($rows as $row)
                                @php($p = $row->part)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="font-mono font-semibold text-slate-900">{{ $p?->part_no ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $p?->customer?->name ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $p?->part_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $p?->model ?? '-' }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ strtoupper((string) ($p?->classification ?? '-')) }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900">{{ formatNumber((float) ($row->on_hand ?? 0)) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-slate-700">{{ formatNumber((float) ($row->on_order ?? 0)) }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $row->as_of_date ? \Carbon\Carbon::parse($row->as_of_date)->format('Y-m-d') : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-slate-500">No data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $rows->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

