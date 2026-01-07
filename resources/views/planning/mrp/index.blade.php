<x-app-layout>
    <x-slot name="header">
        Planning • MRP
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
                    <form method="GET" class="flex items-end gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Minggu (YYYY-WW)</label>
                            <input name="minggu" value="{{ $minggu }}" class="mt-1 rounded-xl border-slate-200" placeholder="2026-W01">
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Load</button>
                    </form>

                    <form method="POST" action="{{ route('planning.mrp.generate') }}" onsubmit="return confirm('Generate MRP for this week?')">
                        @csrf
                        <input type="hidden" name="minggu" value="{{ $minggu }}">
                        <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Generate MRP</button>
                    </form>
                </div>

                @if (!$run)
                    <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-slate-500">
                        No MRP run yet for this week.
                    </div>
                @else
                    <div class="text-sm text-slate-600">
                        Last run: <span class="font-semibold text-slate-900">#{{ $run->id }}</span> • {{ $run->run_at?->format('Y-m-d H:i') }}
                    </div>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <div class="border border-slate-200 rounded-2xl p-4">
                            <div class="text-sm font-semibold text-slate-900">Planned Production Order</div>
                            <div class="mt-3 overflow-x-auto border border-slate-200 rounded-xl">
                                <table class="min-w-full text-sm divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                            <th class="px-3 py-2 text-left font-semibold">Part GCI</th>
                                            <th class="px-3 py-2 text-right font-semibold">Planned Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse ($run->productionPlans as $row)
                                            <tr>
                                                <td class="px-3 py-2">
                                                    <div class="font-semibold">{{ $row->part->part_no ?? '-' }}</div>
                                                    <div class="text-xs text-slate-500">{{ $row->part->part_name ?? '-' }}</div>
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono text-xs">{{ number_format((float) $row->planned_qty, 3) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="px-3 py-4 text-center text-slate-500">No production plans</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="border border-slate-200 rounded-2xl p-4">
                            <div class="text-sm font-semibold text-slate-900">Planned Purchase Order / PR</div>
                            <div class="mt-3 overflow-x-auto border border-slate-200 rounded-xl">
                                <table class="min-w-full text-sm divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                            <th class="px-3 py-2 text-left font-semibold">Component Part</th>
                                            <th class="px-3 py-2 text-right font-semibold">Required</th>
                                            <th class="px-3 py-2 text-right font-semibold">On Hand</th>
                                            <th class="px-3 py-2 text-right font-semibold">On Order</th>
                                            <th class="px-3 py-2 text-right font-semibold">Net Required</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse ($run->purchasePlans as $row)
                                            <tr>
                                                <td class="px-3 py-2">
                                                    <div class="font-semibold">{{ $row->part->part_no ?? '-' }}</div>
                                                    <div class="text-xs text-slate-500">{{ $row->part->part_name ?? '-' }}</div>
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono text-xs">{{ number_format((float) $row->required_qty, 3) }}</td>
                                                <td class="px-3 py-2 text-right font-mono text-xs">{{ number_format((float) $row->on_hand, 3) }}</td>
                                                <td class="px-3 py-2 text-right font-mono text-xs">{{ number_format((float) $row->on_order, 3) }}</td>
                                                <td class="px-3 py-2 text-right font-mono text-xs font-semibold">{{ number_format((float) $row->net_required, 3) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-3 py-4 text-center text-slate-500">No purchase plans</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
