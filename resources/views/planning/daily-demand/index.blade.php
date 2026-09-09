@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ openRow: null }">
    <x-page-header
        :title="__('daily_demand.title')"
        :subtitle="__('daily_demand.subtitle')"
        :breadcrumbs="[
            ['label' => __('modules.planning'), 'url' => route('module.dashboard', 'planning')],
            ['label' => __('daily_demand.title')],
        ]"
    />

    {{-- Plan selector --}}
    <div class="rounded-3xl border border-slate-200 bg-white px-6 py-4 shadow-sm">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="min-w-56 flex-1">
                <label for="plan_id" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('daily_demand.plan') }}</label>
                <select id="plan_id" name="plan_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}" @selected($plan && $plan->id === $p->id)>
                            {{ $p->date_from->format('d M Y') }} — {{ $p->date_to->format('d M Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('daily_demand.select_plan') }}</button>
        </form>
        @if($truncated)
            <div class="mt-2 text-xs font-semibold text-amber-600">{{ __('daily_demand.truncated') }}</div>
        @endif
    </div>

    @if($plan)
        {{-- Summary --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('daily_demand.summary_customers') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ $customers->count() }}</div>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('daily_demand.summary_days') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ $days->count() }}</div>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('daily_demand.summary_total') }}</div>
                <div class="mt-1 text-2xl font-semibold tabular-nums text-indigo-700">{{ number_format($grandTotal, 0) }}</div>
            </div>
        </div>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        @if(!$plan)
            <div class="px-6 py-10 text-center text-sm text-slate-500">{{ __('daily_demand.no_plan') }}</div>
        @elseif($customers->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-slate-500">{{ __('daily_demand.no_data') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-slate-500">
                        <tr>
                            <th class="sticky left-0 z-10 min-w-48 bg-slate-50 px-4 py-3 font-semibold">{{ __('daily_demand.customer') }}</th>
                            @foreach($days as $day)
                                <th class="px-3 py-3 text-right font-semibold {{ $day->isWeekend() ? 'bg-slate-100' : '' }}">
                                    <div>{{ $day->translatedFormat('D') }}</div>
                                    <div class="text-xs font-normal">{{ $day->format('d/m') }}</div>
                                </th>
                            @endforeach
                            <th class="px-4 py-3 text-right font-semibold">{{ __('daily_demand.total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($customers as $index => $bucket)
                            <tr class="cursor-pointer hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600"
                                tabindex="0" role="button"
                                :aria-expanded="(openRow === {{ $index }}).toString()"
                                @click="openRow = openRow === {{ $index }} ? null : {{ $index }}"
                                @keydown.enter.prevent="openRow = openRow === {{ $index }} ? null : {{ $index }}"
                                @keydown.space.prevent="openRow = openRow === {{ $index }} ? null : {{ $index }}">
                                <td class="sticky left-0 z-10 bg-white px-4 py-3 font-semibold text-indigo-700">
                                    {{ $bucket['name'] }}
                                </td>
                                @foreach($days as $day)
                                    @php $qty = $bucket['qty_by_day'][$day->toDateString()] ?? 0; @endphp
                                    <td class="px-3 py-3 text-right tabular-nums {{ $qty > 0 ? 'font-semibold text-slate-900' : 'text-slate-300' }}">
                                        {{ $qty > 0 ? number_format($qty, 0) : '·' }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-right tabular-nums font-semibold text-slate-900">{{ number_format($bucket['total'], 0) }}</td>
                            </tr>
                            <tr x-show="openRow === {{ $index }}" x-cloak>
                                <td colspan="{{ $days->count() + 2 }}" class="bg-slate-50 px-6 py-4">
                                    <div class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('daily_demand.part') }}</div>
                                    <table class="min-w-full text-xs">
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($bucket['parts'] as $partRow)
                                                <tr>
                                                    <td class="py-1.5 pr-4">
                                                        <span class="font-semibold text-slate-800">{{ $partRow['label'] }}</span>
                                                        @if($partRow['name'])<span class="text-slate-500"> — {{ $partRow['name'] }}</span>@endif
                                                        @if($partRow['line'])<span class="ml-2 rounded bg-slate-200 px-1.5 py-0.5 font-semibold text-slate-600">{{ $partRow['line'] }}</span>@endif
                                                    </td>
                                                    <td class="py-1.5 pl-4 text-right tabular-nums font-semibold">{{ number_format($partRow['total'], 0) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @endforeach
                        {{-- Daily totals --}}
                        <tr class="bg-slate-50 font-semibold">
                            <td class="sticky left-0 z-10 bg-slate-50 px-4 py-3 text-slate-700">{{ __('daily_demand.daily_total') }}</td>
                            @foreach($days as $day)
                                @php $dayTotal = $customers->sum(fn ($b) => $b['qty_by_day'][$day->toDateString()] ?? 0); @endphp
                                <td class="px-3 py-3 text-right tabular-nums text-slate-900">{{ $dayTotal > 0 ? number_format($dayTotal, 0) : '·' }}</td>
                            @endforeach
                            <td class="px-4 py-3 text-right tabular-nums text-indigo-700">{{ number_format($grandTotal, 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
