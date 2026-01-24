<x-app-layout>
    <x-slot name="header">
        Warehouse • Production Load
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-xl font-bold text-slate-900">Production Load</div>
                        <div class="text-sm text-slate-500">Jadwal produksi berdasarkan <span class="font-mono">production_orders.plan_date</span></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('production.orders.index') }}" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50">
                            Production Orders
                        </a>
                    </div>
                </div>

                <form method="GET" class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">From</label>
                            <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">To</label>
                            <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                            <select name="status" class="w-full rounded-lg border-slate-300 text-sm">
                                <option value="">All</option>
                                @foreach (['planned', 'material_hold', 'released', 'in_production', 'completed'] as $s)
                                    <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Search</label>
                            <input name="search" value="{{ $search }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="order no / part no / name">
                        </div>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-semibold">Apply</button>
                        <a href="{{ route('warehouse.production-load.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700">Clear</a>
                    </div>
                </form>

                @if(!empty($totalsByDate))
                    <div class="px-6 py-4 border-b border-slate-200 bg-white">
                        <div class="text-sm font-semibold text-slate-700">Total qty per date</div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($totalsByDate as $d => $qty)
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-700">
                                    <span class="font-mono font-semibold">{{ $d }}</span>
                                    <span class="text-slate-400">•</span>
                                    <span class="font-semibold">{{ formatNumber((float) $qty) }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Plan Date</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Order No</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Part</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-700 uppercase">Qty Planned</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @forelse($orders as $o)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-sm text-slate-700 font-mono">
                                        {{ $o->plan_date ? \Carbon\Carbon::parse($o->plan_date)->format('Y-m-d') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <a href="{{ route('production.orders.show', $o) }}" class="font-mono font-semibold text-indigo-700 hover:text-indigo-900">
                                            {{ $o->production_order_number ?? ('#' . $o->id) }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <div class="font-semibold text-slate-900">{{ $o->part?->part_no ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $o->part?->part_name ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold border border-slate-200 bg-slate-50">
                                            {{ (string) ($o->status ?? '-') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900">
                                        {{ formatNumber((float) ($o->qty_planned ?? 0)) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">No production orders in range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

