@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-6 text-sm font-medium text-slate-500 items-center gap-2">
            <a href="{{ route('warehouse.stock-opname.index') }}" class="hover:text-indigo-600 transition-colors">Stock Opname</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-slate-900">{{ $stock_opname->session_no }}</span>
        </nav>

        <!-- Session Overview Card -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 border border-slate-200">
            <div class="p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-3">
                                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $stock_opname->name }}</h1>
                                @php
                                    $statusColors = [
                                        'OPEN' => 'bg-green-100 text-green-700 border-green-200',
                                        'CLOSED' => 'bg-amber-100 text-amber-700 border-amber-200',
                                        'ADJUSTED' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    ];
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $statusColors[$stock_opname->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $stock_opname->status }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-6 gap-y-1 mt-2 text-sm text-slate-500">
                                <span class="flex items-center gap-1.5 font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    {{ $stock_opname->session_no }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Started: {{ $stock_opname->start_date?->format('d M Y H:i') ?: '-' }}
                                </span>
                                @if($stock_opname->end_date)
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Ended: {{ $stock_opname->end_date->format('d M Y H:i') }}
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        @if($stock_opname->status === 'OPEN')
                        <form action="{{ route('warehouse.stock-opname.close', $stock_opname) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                class="inline-flex items-center px-4 py-2.5 bg-amber-500 hover:bg-amber-600 border border-transparent rounded-lg font-bold text-sm text-white transition-all shadow-sm shadow-amber-100">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Close Counting
                            </button>
                        </form>
                        @elseif($stock_opname->status === 'CLOSED')
                        <form action="{{ route('warehouse.stock-opname.adjust', $stock_opname) }}" method="POST" onsubmit="return confirm('WARNING: This will adjust your actual stock to match counted values. Continue?')">
                            @csrf
                            <button type="submit" 
                                class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 border border-transparent rounded-lg font-bold text-sm text-white transition-all shadow-sm shadow-indigo-100">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                                Adjust Actual Stock
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Discrepancy Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Scanned</p>
                <p class="text-3xl font-black text-slate-900 mt-1">{{ number_format($stock_opname->items->count()) }} <span class="text-sm font-medium text-slate-400 font-normal">items</span></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Total Selisih (+/-)</p>
                @php $totalDiff = $stock_opname->items->sum('difference'); @endphp
                <p class="text-3xl font-black {{ $totalDiff == 0 ? 'text-slate-900' : ($totalDiff > 0 ? 'text-green-600' : 'text-red-600') }} mt-1">
                    {{ $totalDiff > 0 ? '+' : '' }}{{ number_format($totalDiff) }}
                </p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Status Akurasi</p>
                @php 
                    $mismatchCount = $stock_opname->items->where('difference', '!=', 0)->count();
                    $accuracy = $stock_opname->items->count() > 0 ? (1 - ($mismatchCount / $stock_opname->items->count())) * 100 : 100;
                @endphp
                <p class="text-3xl font-black text-slate-900 mt-1">{{ number_format($accuracy, 1) }}%</p>
            </div>
        </div>

        <!-- Items Table -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-slate-200">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    Counting List
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Part Info</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">System</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Counted</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Difference</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200 text-sm">
                        @forelse($items as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-1 rounded bg-slate-100 text-slate-700 font-mono text-xs uppercase border border-slate-200">
                                    {{ $item->location_code }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-900">{{ $item->part?->part_no }}</span>
                                    <span class="text-xs text-slate-500 line-clamp-1">{{ $item->part?->part_name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center font-medium text-slate-600">
                                {{ number_format($item->system_qty) }}
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-indigo-600">
                                {{ number_format($item->counted_qty) }}
                            </td>
                            <td class="px-6 py-4 text-center font-black">
                                @if($item->difference == 0)
                                    <span class="text-slate-400">-</span>
                                @elseif($item->difference > 0)
                                    <span class="text-green-600">+{{ number_format($item->difference) }}</span>
                                @else
                                    <span class="text-red-600">{{ number_format($item->difference) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-left whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-slate-700 text-xs font-medium">{{ $item->counted_at?->format('d/m/y H:i') }}</span>
                                    <span class="text-slate-400 text-[10px] uppercase font-semibold tracking-tighter">{{ $item->counter?->name }}</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 italic">
                                No counting data recorded yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($items->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                {{ $items->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
