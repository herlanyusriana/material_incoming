@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">{{ __('planning.mrp.integration.title') }}</h1>
        <p class="text-slate-600 mt-2">{{ __('planning.mrp.integration.subtitle') }}</p>
    </div>

    <!-- Period Selector -->
    <div class="mb-6">
        <form method="GET" action="{{ route('planning.mrp.integration-dashboard') }}" class="flex items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __('planning.mrp.integration.month_label') }}</label>
                <input type="month" name="month" value="{{ $period }}" 
                       onchange="this.form.submit()" 
                       class="rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                {{ __('planning.mrp.integration.apply') }}
            </button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <div class="text-slate-500 text-sm font-medium">{{ __('planning.mrp.integration.card_incoming') }}</div>
            <div class="text-3xl font-bold text-slate-900 mt-2">{{ number_format($totalIncoming, 2) }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ __('planning.mrp.integration.card_for', ['period' => $period]) }}</div>
        </div>
        
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <div class="text-slate-500 text-sm font-medium">{{ __('planning.mrp.integration.card_parts') }}</div>
            <div class="text-3xl font-bold text-slate-900 mt-2">{{ count($incomingByPart) }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ __('planning.mrp.integration.card_parts_hint') }}</div>
        </div>
        
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <div class="text-slate-500 text-sm font-medium">{{ __('planning.mrp.integration.card_demand') }}</div>
            <div class="text-3xl font-bold text-slate-900 mt-2">{{ count($mrpDemandByPart) }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ __('planning.mrp.integration.card_demand_hint') }}</div>
        </div>
    </div>

    <!-- Detailed Analysis Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('planning.mrp.integration.table_title') }}</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('planning.mrp.integration.th_part') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('planning.mrp.integration.th_part_no') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('planning.mrp.integration.th_incoming') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('planning.mrp.integration.th_demand') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('planning.mrp.integration.th_status') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($incomingByPart as $partId => $data)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                                {{ $data['part']?->part_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                {{ $data['part']?->part_no ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 font-medium">
                                {{ number_format($data['total'], 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                {{ number_format($mrpDemandByPart[$partId] ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $incoming = $data['total'];
                                    $demand = $mrpDemandByPart[$partId] ?? 0;
                                    $status = $incoming >= $demand ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800';
                                    $label = $incoming >= $demand ? __('planning.mrp.integration.sufficient') : __('planning.mrp.integration.insufficient');
                                @endphp
                                <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $status }}">
                                    {{ $label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                {{ __('planning.mrp.integration.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Additional Info -->
    <div class="mt-8 bg-blue-50 border border-blue-100 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-2">{{ __('planning.mrp.integration.about') }}</h3>
        <p class="text-blue-800">
            {{ __('planning.mrp.integration.about_text') }}
        </p>
    </div>
</div>
@endsection