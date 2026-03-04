@extends('layouts.app')

@section('title', 'Machine Load — ' . $machine->name)

@section('content')
    <div class="max-w-full mx-auto">
        {{-- Header --}}
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <a href="{{ route('production.machine-load.index', ['date' => $date->format('Y-m-d')]) }}"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                            Back to Machine Load
                        </a>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-sm">
                            <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        {{ $machine->code }} — {{ $machine->name }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $date->format('d F Y') }} — Machine Load Detail</p>
                </div>

                <div class="flex items-center gap-3">
                    @if($status === 'overload')
                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-red-100 text-red-700 ring-1 ring-red-200">OVERLOAD</span>
                    @elseif($status === 'warning')
                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-100 text-amber-700 ring-1 ring-amber-200">WARNING</span>
                    @else
                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-green-100 text-green-700 ring-1 ring-green-200">OK</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Machine Info + Load Summary --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            {{-- Machine Specs --}}
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-5">
                <h3 class="text-sm font-bold text-slate-800 mb-3">Machine Specifications</h3>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <div class="text-xs text-slate-400">Code</div>
                        <div class="font-mono font-bold text-slate-700">{{ $machine->code }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400">Group</div>
                        <div class="font-medium text-slate-700">{{ $machine->group_name ?? '-' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400">Cycle Time</div>
                        <div class="font-mono font-bold text-slate-700">{{ $machine->cycle_time }} {{ $machine->cycle_time_unit }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400">Setup Time</div>
                        <div class="font-mono font-bold text-slate-700">{{ $machine->setup_time_minutes }} min</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400">Available Hours/Shift</div>
                        <div class="font-mono font-bold text-slate-700">{{ $machine->available_hours_per_shift }} hrs</div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-400">Shifts Today</div>
                        <div class="font-mono font-bold text-slate-700">{{ $maxShift }}</div>
                    </div>
                </div>
            </div>

            {{-- Load Summary --}}
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-5">
                <h3 class="text-sm font-bold text-slate-800 mb-3">Load Summary</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-slate-600">Planned / Capacity</span>
                            <span class="font-bold {{ $status === 'overload' ? 'text-red-700' : ($status === 'warning' ? 'text-amber-700' : 'text-emerald-700') }}">
                                {{ number_format($totalPlannedHours, 1) }}h / {{ number_format($capacityHours, 1) }}h
                            </span>
                        </div>
                        <div class="bg-slate-200 rounded-full h-5 overflow-hidden">
                            <div class="h-full rounded-full transition-all {{ $status === 'overload' ? 'bg-red-500' : ($status === 'warning' ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                style="width: {{ min($loadPercent, 100) }}%"></div>
                        </div>
                    </div>
                    <div class="text-center">
                        <span class="text-4xl font-black {{ $status === 'overload' ? 'text-red-600' : ($status === 'warning' ? 'text-amber-600' : 'text-emerald-600') }}">
                            {{ $loadPercent }}%
                        </span>
                        <div class="text-xs text-slate-400 mt-1">Load Percentage</div>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500">
                        <span>{{ count($orderDetails) }} Work Orders</span>
                        @if($capacityHours > $totalPlannedHours)
                            <span class="text-emerald-600 font-semibold">{{ number_format($capacityHours - $totalPlannedHours, 1) }}h remaining</span>
                        @else
                            <span class="text-red-600 font-semibold">{{ number_format($totalPlannedHours - $capacityHours, 1) }}h over capacity</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- WO List Table --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-200">
                <h3 class="text-sm font-bold text-slate-800">Work Orders on {{ $date->format('d M Y') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gradient-to-r from-slate-100 to-slate-50 border-b-2 border-slate-300">
                            <th class="px-4 py-3 text-center font-bold text-slate-700">#</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">WO Number</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Part No</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Part Name</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Qty Planned</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Est. Hours</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Shift</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orderDetails as $idx => $detail)
                            <tr class="border-b border-slate-100 hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-3 text-center text-slate-400 font-mono">{{ $idx + 1 }}</td>
                                <td class="px-4 py-3 font-mono text-xs font-bold text-blue-700">
                                    <a href="{{ route('production.orders.show', $detail['order']->id) }}" class="hover:underline">
                                        {{ $detail['order']->production_order_number }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-700">{{ $detail['order']->part->part_no ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-800">{{ $detail['order']->part->part_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-right font-mono font-semibold text-slate-700">{{ number_format((float) $detail['order']->qty_planned, 0) }}</td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-indigo-700">{{ number_format($detail['est_hours'], 2) }}h</td>
                                <td class="px-4 py-3 text-center text-slate-600">{{ $detail['order']->shift ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $woStatus = $detail['order']->status;
                                        $statusColors = [
                                            'planned' => 'bg-slate-100 text-slate-600 ring-slate-200',
                                            'kanban_released' => 'bg-blue-100 text-blue-700 ring-blue-200',
                                            'released' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                                            'material_hold' => 'bg-amber-100 text-amber-700 ring-amber-200',
                                            'resource_hold' => 'bg-orange-100 text-orange-700 ring-orange-200',
                                            'in_production' => 'bg-indigo-100 text-indigo-700 ring-indigo-200',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-bold ring-1 {{ $statusColors[$woStatus] ?? 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                                        {{ strtoupper(str_replace('_', ' ', $woStatus)) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-slate-400 text-sm">
                                    No work orders assigned to this machine for {{ $date->format('d M Y') }}
                                </td>
                            </tr>
                        @endforelse

                        @if(count($orderDetails) > 0)
                            <tr class="bg-slate-100 border-t-2 border-slate-300 font-bold">
                                <td colspan="4" class="px-4 py-3 text-right text-slate-700">Total</td>
                                <td class="px-4 py-3 text-right font-mono text-slate-800">
                                    {{ number_format(collect($orderDetails)->sum(fn($d) => (float) $d['order']->qty_planned), 0) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-indigo-700">{{ number_format($totalPlannedHours, 2) }}h</td>
                                <td colspan="2" class="px-4 py-3"></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
