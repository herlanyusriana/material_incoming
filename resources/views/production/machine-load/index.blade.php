@extends('layouts.app')

@section('title', 'Machine Load')

@section('content')
    <div class="max-w-full mx-auto">
        {{-- Header --}}
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-sm">
                            <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        MACHINE LOAD
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">Load vs Capacity per Machine — Daily Overview</p>
                </div>

                <div class="flex items-center gap-3">
                    <form action="{{ route('production.machine-load.index') }}" method="GET" class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-slate-600">DATE:</label>
                        <input type="date" name="date" value="{{ $date->format('Y-m-d') }}"
                            class="rounded-lg border-slate-300 text-sm font-semibold shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            onchange="this.form.submit()">
                    </form>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-4">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Machines with WO</div>
                <div class="mt-1 text-2xl font-bold text-slate-800">{{ $machinesWithWo }}</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-4">
                <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Avg Load</div>
                <div class="mt-1 text-2xl font-bold {{ $avgLoad > 100 ? 'text-red-600' : ($avgLoad >= 85 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $avgLoad }}%</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-4">
                <div class="text-xs font-semibold text-red-400 uppercase tracking-wider">Overloaded</div>
                <div class="mt-1 text-2xl font-bold text-red-600">{{ $totalOverloaded }}</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-4">
                <div class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Warning</div>
                <div class="mt-1 text-2xl font-bold text-amber-600">{{ $totalWarning }}</div>
            </div>
        </div>

        {{-- Main Table --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gradient-to-r from-slate-100 to-slate-50 border-b-2 border-slate-300">
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Machine Code</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Machine Name</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Group</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">WO</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Shift</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Capacity (Hrs)</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Planned (Hrs)</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700 min-w-[200px]">Load %</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Status</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($machineLoads as $load)
                            @if($load['wo_count'] > 0)
                                <tr class="border-b border-slate-100 hover:bg-slate-50/80 transition-colors
                                    {{ $load['status'] === 'overload' ? 'bg-red-50/50' : ($load['status'] === 'warning' ? 'bg-amber-50/50' : '') }}">
                                    <td class="px-4 py-3 font-mono text-xs font-bold text-slate-700">{{ $load['machine']->code }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $load['machine']->name }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-500">{{ $load['machine']->group_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center font-semibold text-slate-700">{{ $load['wo_count'] }}</td>
                                    <td class="px-4 py-3 text-center text-slate-600">{{ $load['max_shift'] }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-slate-600">{{ number_format($load['capacity_hours'], 1) }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-semibold
                                        {{ $load['status'] === 'overload' ? 'text-red-700' : ($load['status'] === 'warning' ? 'text-amber-700' : 'text-slate-700') }}">
                                        {{ number_format($load['planned_hours'], 1) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-slate-200 rounded-full h-3 overflow-hidden">
                                                <div class="h-full rounded-full transition-all
                                                    {{ $load['status'] === 'overload' ? 'bg-red-500' : ($load['status'] === 'warning' ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                                    style="width: {{ min($load['load_percent'], 100) }}%"></div>
                                            </div>
                                            <span class="text-xs font-bold min-w-[45px] text-right
                                                {{ $load['status'] === 'overload' ? 'text-red-700' : ($load['status'] === 'warning' ? 'text-amber-700' : 'text-emerald-700') }}">
                                                {{ $load['load_percent'] }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($load['status'] === 'overload')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-red-100 text-red-700 ring-1 ring-red-200">OVERLOAD</span>
                                        @elseif($load['status'] === 'warning')
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 ring-1 ring-amber-200">WARNING</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-green-100 text-green-700 ring-1 ring-green-200">OK</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('production.machine-load.show', ['machine' => $load['machine']->id, 'date' => $date->format('Y-m-d')]) }}"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 ring-1 ring-blue-200 transition-colors">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center gap-3">
                                        <svg class="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        <div class="text-sm font-medium">No machine data</div>
                                        <div class="text-xs">No active machines found or no WOs for this date</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        {{-- Idle machines section --}}
                        @php
                            $idleMachines = collect($machineLoads)->filter(fn($l) => $l['wo_count'] === 0);
                        @endphp
                        @if($idleMachines->count() > 0)
                            <tr class="bg-slate-800 text-white">
                                <td colspan="10" class="px-4 py-2">
                                    <span class="font-bold text-[13px] tracking-wide">IDLE MACHINES ({{ $idleMachines->count() }})</span>
                                    <span class="ml-2 text-[11px] text-slate-400">No WO assigned for this date</span>
                                </td>
                            </tr>
                            @foreach($idleMachines as $load)
                                <tr class="border-b border-slate-100 bg-slate-50/30 text-slate-400">
                                    <td class="px-4 py-2 font-mono text-xs font-bold">{{ $load['machine']->code }}</td>
                                    <td class="px-4 py-2 font-medium">{{ $load['machine']->name }}</td>
                                    <td class="px-4 py-2 text-xs">{{ $load['machine']->group_name ?? '-' }}</td>
                                    <td class="px-4 py-2 text-center">0</td>
                                    <td class="px-4 py-2 text-center">-</td>
                                    <td class="px-4 py-2 text-right font-mono">{{ number_format((float) $load['machine']->available_hours_per_shift, 1) }}</td>
                                    <td class="px-4 py-2 text-right font-mono">0.0</td>
                                    <td class="px-4 py-2">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-slate-200 rounded-full h-3"></div>
                                            <span class="text-xs font-bold min-w-[45px] text-right">0%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-400 ring-1 ring-slate-200">IDLE</span>
                                    </td>
                                    <td class="px-4 py-2"></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Legend --}}
        <div class="mt-6 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-5">
            <h3 class="text-sm font-bold text-slate-800 mb-3">Color Legend</h3>
            <div class="flex flex-wrap gap-6 text-xs">
                <div class="flex items-center gap-2">
                    <div class="h-4 w-8 bg-emerald-500 rounded"></div>
                    <span class="text-slate-600">&lt; 85% — Normal</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="h-4 w-8 bg-amber-500 rounded"></div>
                    <span class="text-slate-600">85–100% — Warning</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="h-4 w-8 bg-red-500 rounded"></div>
                    <span class="text-slate-600">&gt; 100% — Overload</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="h-4 w-8 bg-slate-200 rounded"></div>
                    <span class="text-slate-600">Idle — No WO</span>
                </div>
            </div>
            <p class="mt-3 text-[11px] text-slate-400">Load % = (Total Planned Hours / Capacity Hours) x 100. Planned Hours = (Qty x Cycle Time) + Setup Time. Capacity = Available Hours/Shift x Max Shift.</p>
        </div>
    </div>
@endsection
