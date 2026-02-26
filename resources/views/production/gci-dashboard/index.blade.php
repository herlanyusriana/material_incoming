@extends('layouts.app')

@section('title', 'GCI Operator Dashboard')

@section('content')
    <div class="max-w-full mx-auto">
        {{-- Header --}}
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-sm">
                            <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" />
                            </svg>
                        </div>
                        GCI OPERATOR DASHBOARD
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">Real-time production data synchronized from Android Mobile App.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div
                        class="inline-flex items-center rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200">
                        <span class="mr-1.5 flex h-2 w-2">
                            <span
                                class="absolute inline-flex h-2 w-2 animate-ping rounded-full bg-indigo-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-indigo-500"></span>
                        </span>
                        Live Sync Active
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Row (Optional) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                <div class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-1">Total Synced WOs</div>
                <div class="text-2xl font-bold text-slate-900">{{ $workOrders->total() }}</div>
            </div>
            {{-- Add more stats if needed --}}
        </div>

        {{-- Main Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-200">
                            <th class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider">Date & Shift
                            </th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider">Work Order
                            </th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider">Type / Model
                            </th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider">Operator
                            </th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider">Progress
                            </th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider">Synced At
                            </th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-600 uppercase tracking-wider text-right">
                                Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($workOrders as $wo)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-slate-900">
                                        {{ \Carbon\Carbon::parse($wo->date)->format('d M Y') }}</div>
                                    <div class="text-xs text-slate-500">Shift {{ $wo->shift }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-indigo-600">
                                    {{ $wo->order_no }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-900 font-medium">{{ $wo->type_model }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-500 border border-slate-200">
                                            {{ substr($wo->operator_name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="text-sm text-slate-900 leading-tight">{{ $wo->operator_name }}</div>
                                            <div class="text-xs text-slate-400 capitalize">{{ $wo->foreman }} (Foreman)</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $totalActual = $wo->hourlyReports->sum('actual');
                                        $totalTarget = $wo->hourlyReports->sum('target');
                                        $efficiency = $totalTarget > 0 ? round(($totalActual / $totalTarget) * 100) : 0;
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full {{ $efficiency >= 100 ? 'bg-emerald-500' : ($efficiency >= 80 ? 'bg-amber-500' : 'bg-rose-500') }}"
                                                style="width: {{ min($efficiency, 100) }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold text-slate-700">{{ $efficiency }}%</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 mt-1">{{ $totalActual }} / {{ $totalTarget }} Units
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    {{ $wo->created_at->format('H:i:s d/m') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('gci-dashboard.show', $wo->id) }}"
                                        class="inline-flex items-center justify-center h-8 px-4 text-xs font-bold text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-all">
                                        View Report
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500 italic">
                                    No production data synchronized yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($workOrders->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/30">
                    {{ $workOrders->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection