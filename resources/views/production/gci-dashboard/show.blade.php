@extends('layouts.app')

@section('title', 'WO Detail: ' . $workOrder->order_no)

@section('content')
    <div class="max-w-full mx-auto" x-data="{ tab: 'hourly' }">
        {{-- Breadcrumbs & Actions --}}
        <div class="mb-6 flex flex-col md:flex-row md:items-center md::justify-between gap-4">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3 text-sm text-slate-500">
                    <li><a href="{{ route('gci-dashboard.index') }}"
                            class="hover:text-indigo-600 transition-colors">Dashboard</a></li>
                    <li>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </li>
                    <li class="font-bold text-slate-900">{{ $workOrder->order_no }}</li>
                </ol>
            </nav>

            <div class="flex gap-2">
                <a href="{{ route('gci-dashboard.index') }}"
                    class="inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-all">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to List
                </a>
            </div>
        </div>

        {{-- Main Header Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
            <div class="p-6 md:p-8 bg-gradient-to-r from-slate-900 to-slate-800 text-white">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="flex items-center gap-5">
                        <div
                            class="h-16 w-16 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/20">
                            <svg class="h-8 w-8 text-indigo-300" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-3">
                                <h1 class="text-3xl font-black tracking-tight">{{ $workOrder->order_no }}</h1>
                                <span
                                    class="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-400 text-xs font-black border border-emerald-500/30 uppercase tracking-widest">
                                    SYNCED
                                </span>
                            </div>
                            <p class="text-slate-400 font-medium mt-1">{{ $workOrder->type_model }}</p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-2 md:grid-cols-4 gap-x-8 gap-y-4 border-t border-white/10 md:border-t-0 pt-6 md:pt-0 w-full md:w-auto">
                        <div>
                            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Date</div>
                            <div class="text-base font-bold">{{ \Carbon\Carbon::parse($workOrder->date)->format('d F Y') }}
                            </div>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Shift</div>
                            <div class="text-base font-bold">Shift {{ $workOrder->shift }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Operator</div>
                            <div class="text-base font-bold">{{ $workOrder->operator_name }}</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Foreman</div>
                            <div class="text-base font-bold">{{ $workOrder->foreman }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-slate-100 border-t border-slate-100">
                @php
                    $totalActual = $workOrder->hourlyReports->sum('actual');
                    $totalNG = $workOrder->hourlyReports->sum('ng');
                    $totalTarget = $workOrder->hourlyReports->sum('target');
                    $efficiency = $totalTarget > 0 ? round(($totalActual / $totalTarget) * 100) : 0;
                    $totalDowntime = $workOrder->downtimes->sum('duration_minutes');
                @endphp
                <div class="p-6 text-center">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Total Actual</div>
                    <div class="text-3xl font-black text-slate-900">{{ number_format($totalActual) }}</div>
                    <div class="text-[10px] text-slate-400 mt-1 uppercase font-bold">Units Produced</div>
                </div>
                <div class="p-6 text-center">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Efficiency</div>
                    <div
                        class="text-3xl font-black {{ $efficiency >= 100 ? 'text-emerald-600' : ($efficiency >= 80 ? 'text-amber-600' : 'text-rose-600') }}">
                        {{ $efficiency }}<span class="text-xl">%</span>
                    </div>
                    <div class="text-[10px] text-slate-400 mt-1 uppercase font-bold">VS Target ({{ $totalTarget }})</div>
                </div>
                <div class="p-6 text-center">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Total Defect (NG)</div>
                    <div class="text-3xl font-black text-rose-600">{{ number_format($totalNG) }}</div>
                    <div class="text-[10px] text-slate-400 mt-1 uppercase font-bold">Items Rejected</div>
                </div>
                <div class="p-6 text-center">
                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Total Downtime</div>
                    <div class="text-3xl font-black text-amber-600">{{ $totalDowntime }}<span class="text-xl">m</span></div>
                    <div class="text-[10px] text-slate-400 mt-1 uppercase font-bold">Minutes of Stop</div>
                </div>
            </div>
        </div>

        {{-- Tabs Content --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="flex border-b border-slate-100 bg-slate-50/50 p-2 gap-2">
                <button @click="tab = 'hourly'"
                    :class="tab === 'hourly' ? 'bg-white text-indigo-600 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-slate-100'"
                    class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Hourly Report
                </button>
                <button @click="tab = 'downtime'"
                    :class="tab === 'downtime' ? 'bg-white text-indigo-600 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-slate-100'"
                    class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Downtime Details
                </button>
                <button @click="tab = 'material'"
                    :class="tab === 'material' ? 'bg-white text-indigo-600 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:bg-slate-100'"
                    class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                        </path>
                    </svg>
                    Material Lots
                </button>
            </div>

            <div class="p-0">
                {{-- Hourly Tab --}}
                <div x-show="tab === 'hourly'" x-transition>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/30 border-b border-slate-100">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest w-24">
                                        Hour</th>
                                    <th
                                        class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-center">
                                        Target</th>
                                    <th
                                        class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-center">
                                        Actual</th>
                                    <th
                                        class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-center text-rose-500">
                                        Defect (NG)</th>
                                    <th
                                        class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-center">
                                        Efficiency</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Remarks
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                @forelse($workOrder->hourlyReports as $report)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-6 py-4 font-black text-slate-900">{{ $report->time_range }}</td>
                                        <td class="px-6 py-4 text-center font-bold text-slate-600">{{ $report->target }}</td>
                                        <td class="px-6 py-4 text-center font-black text-slate-900 bg-slate-50/30">
                                            {{ $report->actual }}</td>
                                        <td class="px-6 py-4 text-center font-bold text-rose-600">{{ $report->ng }}</td>
                                        <td class="px-6 py-4 text-center">
                                            @php
                                                $hrEff = $report->target > 0 ? round(($report->actual / $report->target) * 100) : 0;
                                            @endphp
                                            <span
                                                class="px-2 py-1 rounded text-[11px] font-black {{ $hrEff >= 100 ? 'bg-emerald-100 text-emerald-700' : ($hrEff >= 80 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                                                {{ $hrEff }}%
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-slate-500 italic">{{ $report->remarks ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-slate-400 italic">No hourly data
                                            records.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Downtime Tab --}}
                <div x-show="tab === 'downtime'" style="display: none;" x-transition>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/30 border-b border-slate-100">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest w-40">
                                        Period</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">
                                        Category</th>
                                    <th
                                        class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-center">
                                        Duration</th>
                                    <th
                                        class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-center">
                                        Status</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Notes /
                                        Problem</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                @forelse($workOrder->downtimes as $dt)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-6 py-4 font-bold text-slate-900 whitespace-nowrap">
                                            {{ $dt->start_time }} → {{ $dt->end_time ?: '??' }}
                                        </td>
                                        <td class="px-6 py-4 uppercase font-bold text-[11px] text-slate-700 tracking-wider">
                                            {{ str_replace('_', ' ', $dt->reason) }}
                                        </td>
                                        <td class="px-6 py-4 text-center font-black text-slate-900">
                                            @if($dt->duration_minutes !== null)
                                                {{ $dt->duration_minutes }} <span
                                                    class="text-xs text-slate-400 font-normal">min</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @if($dt->is_running)
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded bg-rose-100 text-rose-700 text-[10px] font-black animate-pulse">
                                                    STOPPED
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded bg-slate-100 text-slate-600 text-[10px] font-bold">
                                                    FINISHED
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-slate-500 italic">
                                            {{ $dt->notes ?: 'No description provided.' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-slate-400 italic">No downtime events
                                            recorded.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Material Tab --}}
                <div x-show="tab === 'material'" style="display: none;" x-transition>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/30 border-b border-slate-100">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Invoice
                                        / Tag No.</th>
                                    <th
                                        class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-center">
                                        Required Qty</th>
                                    <th
                                        class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest text-center">
                                        Actual (Scan)</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-widest">Scan
                                        Time</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm">
                                @forelse($workOrder->materialLots as $lot)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-6 py-4">
                                            <div
                                                class="inline-flex px-2 py-1 bg-indigo-50 text-indigo-700 rounded-md font-mono text-xs border border-indigo-100">
                                                {{ $lot->invoice_or_tag }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center font-semibold text-slate-500">
                                            {{ number_format($lot->qty) }}</td>
                                        <td class="px-6 py-4 text-center font-black text-indigo-600 bg-indigo-50/30">
                                            {{ number_format($lot->actual) }}</td>
                                        <td class="px-6 py-4 text-xs text-slate-400">
                                            {{ $lot->created_at->format('H:i:s d/m/Y') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-10 text-center text-slate-400 italic">No material lot
                                            scans recorded.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection