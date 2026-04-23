@extends('layouts.app')

@section('title', 'Production Board')

@php
    $statusClass = [
        'planned' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'released' => 'bg-blue-100 text-blue-700 ring-blue-200',
        'kanban_released' => 'bg-blue-100 text-blue-700 ring-blue-200',
        'in_production' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        'paused' => 'bg-amber-100 text-amber-800 ring-amber-200',
        'completed' => 'bg-indigo-100 text-indigo-700 ring-indigo-200',
        'cancelled' => 'bg-red-100 text-red-700 ring-red-200',
    ];

    $fmtQty = fn ($value) => number_format((float) $value, fmod((float) $value, 1.0) === 0.0 ? 0 : 2);
@endphp

@section('content')
    <div class="max-w-full mx-auto space-y-5">
        <div class="rounded-3xl bg-gradient-to-br from-blue-50 via-white to-sky-50 ring-1 ring-blue-100 p-5 shadow-sm">
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-[11px] font-black uppercase tracking-[0.18em] text-blue-700 ring-1 ring-blue-100">
                        Production Board
                    </div>
                    <h1 class="mt-3 text-2xl font-black text-slate-950">Board WO Produksi</h1>
                    <p class="mt-1 max-w-3xl text-sm text-slate-600">
                        Pantau WO per shift, proses berjalan, mesin aktual, OK, NG, WIP, dan FG dalam satu layar.
                    </p>
                </div>

                <form action="{{ route('production.board.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Tanggal</span>
                        <input type="date" name="date" value="{{ $date->toDateString() }}"
                            class="rounded-xl border-blue-100 bg-white px-3 py-2 text-sm font-bold text-slate-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>
                    <button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-black text-white shadow-sm hover:bg-blue-700">
                        View
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-7">
            <div class="rounded-2xl bg-white p-4 ring-1 ring-slate-200 shadow-sm">
                <div class="text-[11px] font-black uppercase tracking-wider text-slate-400">WO</div>
                <div class="mt-1 text-2xl font-black text-slate-900">{{ $fmtQty($summary['wo_count']) }}</div>
            </div>
            <div class="rounded-2xl bg-white p-4 ring-1 ring-emerald-100 shadow-sm">
                <div class="text-[11px] font-black uppercase tracking-wider text-emerald-500">Running</div>
                <div class="mt-1 text-2xl font-black text-emerald-700">{{ $fmtQty($summary['running']) }}</div>
            </div>
            <div class="rounded-2xl bg-white p-4 ring-1 ring-amber-100 shadow-sm">
                <div class="text-[11px] font-black uppercase tracking-wider text-amber-500">Paused</div>
                <div class="mt-1 text-2xl font-black text-amber-700">{{ $fmtQty($summary['paused']) }}</div>
            </div>
            <div class="rounded-2xl bg-white p-4 ring-1 ring-indigo-100 shadow-sm">
                <div class="text-[11px] font-black uppercase tracking-wider text-indigo-500">Completed</div>
                <div class="mt-1 text-2xl font-black text-indigo-700">{{ $fmtQty($summary['completed']) }}</div>
            </div>
            <div class="rounded-2xl bg-white p-4 ring-1 ring-blue-100 shadow-sm">
                <div class="text-[11px] font-black uppercase tracking-wider text-blue-500">Target</div>
                <div class="mt-1 text-2xl font-black text-blue-700">{{ $fmtQty($summary['target_qty']) }}</div>
            </div>
            <div class="rounded-2xl bg-white p-4 ring-1 ring-sky-100 shadow-sm">
                <div class="text-[11px] font-black uppercase tracking-wider text-sky-500">OK</div>
                <div class="mt-1 text-2xl font-black text-sky-700">{{ $fmtQty($summary['ok_qty']) }}</div>
            </div>
            <div class="rounded-2xl bg-white p-4 ring-1 ring-red-100 shadow-sm">
                <div class="text-[11px] font-black uppercase tracking-wider text-red-500">NG</div>
                <div class="mt-1 text-2xl font-black text-red-700">{{ $fmtQty($summary['ng_qty']) }}</div>
            </div>
        </div>

        <div class="overflow-hidden rounded-3xl bg-white ring-1 ring-slate-200 shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-100 bg-slate-50 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="text-sm font-black text-slate-900">Timeline Shift</div>
                    <div class="text-xs text-slate-500">Baris WO tetap satu, aktivitas lapangan dibaca dari input APK.</div>
                </div>
                <div class="flex flex-wrap gap-2 text-[11px] font-bold">
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 ring-1 ring-slate-200">Planned</span>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700 ring-1 ring-emerald-200">Running</span>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-800 ring-1 ring-amber-200">Paused</span>
                    <span class="rounded-full bg-indigo-100 px-3 py-1 text-indigo-700 ring-1 ring-indigo-200">Completed</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1280px] w-full text-sm">
                    <thead class="bg-white text-[11px] uppercase tracking-wider text-slate-500">
                        <tr class="border-b border-slate-200">
                            <th class="px-4 py-3 text-left font-black">WO / Part</th>
                            <th class="px-4 py-3 text-left font-black">Status</th>
                            <th class="px-4 py-3 text-left font-black">Proses Sekarang</th>
                            <th class="px-4 py-3 text-left font-black">Mesin Aktual</th>
                            <th class="px-4 py-3 text-right font-black">Target</th>
                            <th class="px-4 py-3 text-right font-black">OK / NG</th>
                            <th class="px-4 py-3 text-left font-black">Progress</th>
                            <th class="px-4 py-3 text-left font-black min-w-[220px]">Shift 1</th>
                            <th class="px-4 py-3 text-left font-black min-w-[220px]">Shift 2</th>
                            <th class="px-4 py-3 text-left font-black min-w-[220px]">Shift 3</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rows as $row)
                            <tr class="align-top hover:bg-blue-50/30">
                                <td class="px-4 py-4">
                                    <a href="{{ route('production.orders.show', $row['order']->id) }}"
                                        class="font-black text-blue-700 hover:text-blue-900">
                                        {{ $row['wo_number'] }}
                                    </a>
                                    <div class="mt-1 font-bold text-slate-900">{{ $row['part_no'] }}</div>
                                    <div class="max-w-[280px] truncate text-xs text-slate-500">{{ $row['part_name'] }}</div>
                                    <div class="mt-1 text-[11px] font-bold text-slate-400">{{ $row['model'] }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-black uppercase ring-1 {{ $statusClass[$row['status']] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                        {{ str_replace('_', ' ', $row['status']) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 font-bold text-slate-800">{{ $row['current_process'] ?: '-' }}</td>
                                <td class="px-4 py-4">
                                    @forelse($row['actual_machines'] as $machine)
                                        <span class="mb-1 mr-1 inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700 ring-1 ring-blue-100">{{ $machine }}</span>
                                    @empty
                                        <span class="text-slate-400">-</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-4 text-right font-black text-slate-900">{{ $fmtQty($row['target_qty']) }}</td>
                                <td class="px-4 py-4 text-right">
                                    <div class="font-black text-emerald-700">{{ $fmtQty($row['ok_qty']) }}</div>
                                    <div class="text-xs font-bold text-red-600">NG {{ $fmtQty($row['ng_qty']) }}</div>
                                    <div class="mt-1 text-[11px] text-slate-500">WIP {{ $fmtQty($row['wip_qty']) }} · FG {{ $fmtQty($row['fg_qty']) }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-3 w-28 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-blue-600" style="width: {{ $row['progress_percent'] }}%"></div>
                                        </div>
                                        <span class="text-xs font-black text-blue-700">{{ $row['progress_percent'] }}%</span>
                                    </div>
                                </td>
                                @foreach([1, 2, 3] as $shiftNo)
                                    @php($cell = $row['shift_cells'][$shiftNo])
                                    <td class="px-4 py-4">
                                        @if($cell['ok'] > 0 || $cell['ng'] > 0)
                                            <div class="rounded-2xl border border-blue-100 bg-blue-50/60 p-3">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="text-xs font-black text-blue-800">OK {{ $fmtQty($cell['ok']) }}</span>
                                                    <span class="text-xs font-black text-red-600">NG {{ $fmtQty($cell['ng']) }}</span>
                                                </div>
                                                <div class="mt-1 text-[11px] font-bold text-slate-600">WIP {{ $fmtQty($cell['wip']) }} · FG {{ $fmtQty($cell['fg']) }}</div>
                                                <div class="mt-2 flex flex-wrap gap-1">
                                                    @foreach($cell['processes'] as $process)
                                                        <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-bold text-slate-700 ring-1 ring-slate-200">{{ $process }}</span>
                                                    @endforeach
                                                </div>
                                                <div class="mt-2 flex flex-wrap gap-1">
                                                    @foreach($cell['machines'] as $machine)
                                                        <span class="rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-black text-white">{{ $machine }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @else
                                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-3 text-xs font-bold text-slate-400">
                                                Belum ada input
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-16 text-center">
                                    <div class="text-base font-black text-slate-800">Belum ada WO di tanggal ini.</div>
                                    <div class="mt-1 text-sm text-slate-500">Generate WO dari Production Planning dulu, lalu aktivitas APK akan muncul di board.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
