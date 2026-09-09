@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ openRow: null }">
    <x-page-header
        :title="__('cycle_time.title')"
        :subtitle="__('cycle_time.subtitle')"
        :breadcrumbs="[
            ['label' => __('modules.production'), 'url' => route('module.dashboard', 'production')],
            ['label' => __('cycle_time.title')],
        ]"
    />

    {{-- Summary --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('cycle_time.summary_machines') }}</div>
            <div class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ $rows->count() }}</div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('cycle_time.summary_avg') }}</div>
            <div class="mt-1 text-2xl font-semibold tabular-nums {{ ($summary['avg_performance'] ?? 100) < $threshold ? 'text-rose-600' : 'text-emerald-600' }}">
                {{ $summary['avg_performance'] !== null ? number_format($summary['avg_performance'], 1) . '%' : '—' }}
            </div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('cycle_time.summary_flagged') }}</div>
            <div class="mt-1 text-2xl font-semibold tabular-nums {{ $summary['flagged'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ $summary['flagged'] }}</div>
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('cycle_time.summary_qty') }}</div>
            <div class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ number_format($summary['total_qty'], 0) }}</div>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        {{-- Filters --}}
        <div class="border-b border-slate-100 px-6 py-4">
            <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                <div>
                    <label for="from" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('cycle_time.from') }}</label>
                    <input id="from" type="date" name="from" value="{{ $filters['from'] }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                </div>
                <div>
                    <label for="to" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('cycle_time.to') }}</label>
                    <input id="to" type="date" name="to" value="{{ $filters['to'] }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                </div>
                <div>
                    <label for="machine_id" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('cycle_time.machine') }}</label>
                    <select id="machine_id" name="machine_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        <option value="">{{ __('cycle_time.all_machines') }}</option>
                        @foreach($machines as $machine)
                            <option value="{{ $machine->id }}" @selected($filters['machineId'] === $machine->id)>{{ $machine->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex h-10 flex-1 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('cycle_time.filter') }}</button>
                    <a href="{{ route('production.cycle-time.index') }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('cycle_time.reset') }}</a>
                </div>
            </form>
            <div class="mt-3 text-xs text-slate-500">{{ __('cycle_time.legend', ['threshold' => $threshold]) }}</div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">{{ __('cycle_time.machine') }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ __('cycle_time.orders') }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ __('cycle_time.qty') }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ __('cycle_time.std_cycle') }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ __('cycle_time.actual_cycle') }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ __('cycle_time.run_time') }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ __('cycle_time.downtime') }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ __('cycle_time.performance') }}</th>
                        <th class="px-4 py-3 font-semibold">{{ __('cycle_time.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <tr class="{{ $row->warning ? 'bg-rose-50/60' : '' }}">
                            <td class="px-4 py-3">
                                <button type="button" class="text-left font-semibold text-indigo-700 hover:underline"
                                    :aria-expanded="(openRow === {{ $row->machine_id ?? 0 }}).toString()"
                                    @click="openRow = openRow === {{ $row->machine_id ?? 0 }} ? null : {{ $row->machine_id ?? 0 }}">
                                    {{ $row->machine_name }}
                                </button>
                                @if($row->machine_code)
                                    <div class="text-xs text-slate-500">{{ $row->machine_code }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-700">{{ $row->orders }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-700">{{ number_format($row->qty, 0) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-700">
                                {{ $row->std_sec_per_pc > 0 ? number_format($row->std_sec_per_pc, 1) . ' ' . $row->unit : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums font-semibold {{ $row->warning ? 'text-rose-700' : 'text-slate-900' }}">
                                {{ $row->actual_sec_per_pc !== null ? number_format($row->actual_sec_per_pc, 1) . ' sec' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-700">{{ number_format($row->run_minutes, 0) }} {{ __('cycle_time.minutes') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-700">{{ number_format($row->downtime_minutes, 0) }} {{ __('cycle_time.minutes') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums font-semibold {{ $row->warning ? 'text-rose-700' : 'text-emerald-700' }}">
                                {{ $row->performance !== null ? number_format($row->performance, 1) . '%' : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($row->performance === null)
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-500">—</span>
                                @elseif($row->warning)
                                    <span class="inline-flex rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">{{ __('cycle_time.warning') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">{{ __('cycle_time.ok') }}</span>
                                @endif
                            </td>
                        </tr>
                        @if($row->by_part->isNotEmpty())
                            <tr x-show="openRow === {{ $row->machine_id ?? 0 }}" x-cloak>
                                <td colspan="9" class="bg-slate-50 px-6 py-4">
                                    <div class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('cycle_time.detail_by_part') }}</div>
                                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                                        <thead class="text-left text-slate-500">
                                            <tr>
                                                <th class="py-1 pr-4 font-semibold">{{ __('cycle_time.part') }}</th>
                                                <th class="py-1 pr-4 text-right font-semibold">{{ __('cycle_time.qty') }}</th>
                                                <th class="py-1 pr-4 text-right font-semibold">{{ __('cycle_time.run_time') }}</th>
                                                <th class="py-1 pr-4 text-right font-semibold">{{ __('cycle_time.actual_cycle') }}</th>
                                                <th class="py-1 text-right font-semibold">{{ __('cycle_time.performance') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($row->by_part as $partRow)
                                                <tr>
                                                    <td class="py-1.5 pr-4">
                                                        <span class="font-semibold text-slate-800">{{ $partRow['part_no'] }}</span>
                                                        <span class="text-slate-500"> — {{ $partRow['part_name'] }}</span>
                                                    </td>
                                                    <td class="py-1.5 pr-4 text-right tabular-nums">{{ number_format($partRow['qty'], 0) }}</td>
                                                    <td class="py-1.5 pr-4 text-right tabular-nums">{{ number_format($partRow['minutes'], 0) }} {{ __('cycle_time.minutes') }}</td>
                                                    <td class="py-1.5 pr-4 text-right tabular-nums">{{ number_format($partRow['actual_sec'], 1) }} sec</td>
                                                    <td class="py-1.5 text-right tabular-nums font-semibold {{ ($partRow['performance'] ?? 100) < $threshold ? 'text-rose-700' : 'text-emerald-700' }}">
                                                        {{ $partRow['performance'] !== null ? number_format($partRow['performance'], 1) . '%' : '—' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-500">{{ __('cycle_time.no_data') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
