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
                    <div class="text-sm text-slate-600 mb-4">
                        Last run: <span class="font-semibold text-slate-900">#{{ $run->id }}</span> • {{ $run->run_at?->format('Y-m-d H:i') }}
                    </div>

                    <div class="space-y-8">
                        <!-- Production Plan -->
                        <div class="border border-slate-200 rounded-2xl p-4">
                            <div class="text-lg font-bold text-slate-900 mb-4">Planned Production Order (FG / WIP)</div>
                            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                                <table class="min-w-full text-sm divide-y divide-slate-200">
                                    <thead class="bg-indigo-50">
                                        <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                            <th class="px-3 py-3 text-left font-bold sticky left-0 bg-indigo-50 z-10">Part Number</th>
                                            @foreach ($dates as $date)
                                                <th class="px-3 py-3 text-center font-bold">{{ date('D, d M', strtotime($date)) }}</th>
                                            @endforeach
                                            <th class="px-3 py-3 text-right font-bold">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @php
                                            $groupedProduction = $run->productionPlans->groupBy('part_id');
                                        @endphp
                                        @forelse ($groupedProduction as $partId => $plans)
                                            @php $part = $plans->first()->part; @endphp
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-3 py-2 sticky left-0 bg-white z-10 border-r border-slate-100">
                                                    <div class="font-bold text-slate-900">{{ $part->part_no ?? '-' }}</div>
                                                    <div class="text-xs text-slate-500">{{ $part->part_name ?? '-' }}</div>
                                                </td>
                                                @foreach ($dates as $date)
                                                    @php
                                                        $qty = $plans->where('plan_date', $date)->sum('planned_qty');
                                                    @endphp
                                                    <td class="px-3 py-2 text-center border-l border-slate-50">
                                                        @if($qty > 0)
                                                            <div class="font-mono text-indigo-700 font-semibold">{{ number_format($qty) }}</div>
                                                        @else
                                                            <span class="text-slate-200">-</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td class="px-3 py-2 text-right border-l border-slate-200 font-bold bg-slate-50">
                                                    {{ number_format($plans->sum('planned_qty')) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ count($dates) + 2 }}" class="px-3 py-4 text-center text-slate-500">No production plans</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Purchase Plan -->
                        <div class="border border-slate-200 rounded-2xl p-4">
                            <div class="text-lg font-bold text-slate-900 mb-4">Planned Purchase (Raw Material / Components)</div>
                            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                                <table class="min-w-full text-sm divide-y divide-slate-200">
                                    <thead class="bg-emerald-50">
                                        <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                            <th class="px-3 py-3 text-left font-bold sticky left-0 bg-emerald-50 z-10">Part Number</th>
                                            <th class="px-3 py-3 text-right font-bold w-24">Stock</th>
                                            @foreach ($dates as $date)
                                                <th class="px-3 py-3 text-center font-bold">{{ date('D, d M', strtotime($date)) }}</th>
                                            @endforeach
                                            <th class="px-3 py-3 text-right font-bold">Total Net Req</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        @php
                                            $groupedPurchase = $run->purchasePlans->groupBy('part_id');
                                        @endphp
                                        @forelse ($groupedPurchase as $partId => $plans)
                                            @php 
                                                $firstRow = $plans->first();
                                                $part = $firstRow->part; 
                                                // Stock is captured at run time, usually static across days in this simplified logic
                                                $stock = $firstRow->on_hand; 
                                            @endphp
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-3 py-2 sticky left-0 bg-white z-10 border-r border-slate-100">
                                                    <div class="font-bold text-slate-900">{{ $part->part_no ?? '-' }}</div>
                                                    <div class="text-xs text-slate-500">{{ $part->part_name ?? '-' }}</div>
                                                </td>
                                                <td class="px-3 py-2 text-right text-xs text-slate-500 font-mono bg-slate-50">
                                                    {{ number_format($stock) }}
                                                </td>
                                                @foreach ($dates as $date)
                                                    @php
                                                        $qty = $plans->where('plan_date', $date)->sum('net_required');
                                                    @endphp
                                                    <td class="px-3 py-2 text-center border-l border-slate-50">
                                                        @if($qty > 0)
                                                            <div class="font-mono text-emerald-700 font-semibold">{{ number_format($qty) }}</div>
                                                        @else
                                                            <span class="text-slate-200">-</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td class="px-3 py-2 text-right border-l border-slate-200 font-bold bg-slate-50">
                                                    {{ number_format($plans->sum('net_required')) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ count($dates) + 3 }}" class="px-3 py-4 text-center text-slate-500">No purchase plans</td>
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
