@extends('outgoing.layout')

@section('content')
    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                <div class="font-semibold mb-2">Gagal:</div>
                <ul class="list-disc ml-5 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center text-white font-black">
                        DP
                    </div>
                    <div>
                        <div class="text-2xl md:text-3xl font-black text-slate-900">Daily Planning Schedule</div>
                        <div class="mt-1 text-sm text-slate-600">Production line schedule with sequence and quantity</div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('outgoing.daily-planning.template', ['date_from' => $dateFrom->toDateString(), 'date_to' => $dateTo->toDateString()]) }}"
                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Template
                    </a>

                    @if ($plan)
                        <a
                            href="{{ route('outgoing.daily-planning.export', $plan) }}"
                            class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            Export
                        </a>
                    @else
                        <button
                            type="button"
                            class="inline-flex items-center rounded-xl bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 cursor-not-allowed"
                            disabled
                        >
                            Export
                        </button>
                    @endif

                    <form action="{{ route('outgoing.daily-planning.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                        @csrf
                        <input
                            type="file"
                            name="file"
                            accept=".xlsx,.xls,.csv"
                            class="block w-[220px] text-sm text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200"
                            required
                        />
                        <button type="submit" class="inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Import
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-4 items-end">
                <form action="{{ route('outgoing.daily-planning') }}" method="GET" class="flex flex-wrap items-end gap-4">
                    <div>
                        <div class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-700">Date From</div>
                        <input
                            type="date"
                            name="date_from"
                            value="{{ $dateFrom->toDateString() }}"
                            class="rounded-xl border-2 border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <div class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-700">Date To</div>
                        <input
                            type="date"
                            name="date_to"
                            value="{{ $dateTo->toDateString() }}"
                            class="rounded-xl border-2 border-slate-200 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <button type="submit" class="rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900">
                        Load
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            @foreach ($days as $idx => $d)
                @php
                    $k = $d->format('Y-m-d');
                    $total = $totalsByDate[$k] ?? 0;
                    $grad = match ($idx % 5) {
                        0 => 'from-blue-600 to-blue-700',
                        1 => 'from-purple-600 to-purple-700',
                        2 => 'from-emerald-600 to-emerald-700',
                        3 => 'from-orange-600 to-orange-700',
                        default => 'from-pink-600 to-pink-700',
                    };
                @endphp
                <div class="rounded-2xl bg-gradient-to-br {{ $grad }} p-5 text-white shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wider text-white/80 mb-2">{{ $d->format('d-M') }}</div>
                    <div class="text-3xl font-black">{{ number_format($total) }}</div>
                    @if ($idx === 0)
                        <div class="mt-1 text-sm text-white/80">Start</div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gradient-to-r from-slate-700 to-slate-800 text-white">
                        <tr>
                            <th rowspan="2" class="px-3 py-4 text-left font-bold border-r border-slate-600">Production Line</th>
                            <th rowspan="2" class="px-3 py-4 text-left font-bold border-r border-slate-600">Part No</th>
                            @foreach ($days as $idx => $d)
                                <th colspan="2" class="px-3 py-3 text-center font-bold {{ $idx === 0 ? 'bg-blue-600' : '' }} {{ $idx !== count($days) - 1 ? 'border-r border-slate-600' : '' }}">
                                    {{ $d->format('d-M') }}
                                </th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach ($days as $idx => $d)
                                <th class="px-3 py-2 text-center font-semibold {{ $idx === 0 ? 'bg-blue-600 text-blue-100' : 'text-slate-300' }} border-r border-slate-600">Seq.</th>
                                <th class="px-3 py-2 text-center font-semibold {{ $idx === 0 ? 'bg-blue-600 text-blue-100' : 'text-slate-300' }} {{ $idx !== count($days) - 1 ? 'border-r border-slate-600' : '' }}">Qty</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $cellMap = $row->cells->keyBy(fn ($c) => $c->plan_date->format('Y-m-d'));
                            @endphp
                            <tr class="border-b border-slate-100 hover:bg-blue-50 transition-colors">
                                <td class="px-3 py-3 border-r border-slate-100">
                                    <span class="px-2 py-1 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded text-xs font-black">
                                        {{ $row->production_line }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 border-r border-slate-100 font-semibold text-slate-700">{{ $row->part_no }}</td>
                                @foreach ($days as $idx => $d)
                                    @php
                                        $key = $d->format('Y-m-d');
                                        $cell = $cellMap->get($key);
                                        $seq = $cell?->seq ?? '-';
                                        $qty = $cell?->qty ?? '-';
                                    @endphp
                                    <td class="px-3 py-3 border-r border-slate-100 text-center {{ $idx === 0 ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600' }}">{{ $seq }}</td>
                                    <td class="px-3 py-3 text-center {{ $idx === 0 ? 'bg-blue-50 text-blue-700 font-black' : 'text-slate-700 font-semibold' }} {{ $idx !== count($days) - 1 ? 'border-r border-slate-100' : '' }}">{{ $qty }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 2 + (count($days) * 2) }}" class="px-6 py-10 text-center text-slate-500">
                                    Belum ada data. Download template lalu Import file Excel untuk membuat daily planning.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gradient-to-r from-slate-100 to-slate-200 font-bold">
                        <tr>
                            <td colspan="2" class="px-3 py-3 text-right text-slate-700 uppercase tracking-wider">Total Qty:</td>
                            @foreach ($days as $idx => $d)
                                @php
                                    $k = $d->format('Y-m-d');
                                    $t = $totalsByDate[$k] ?? 0;
                                @endphp
                                <td class="px-3 py-3 text-center border-r border-slate-300"></td>
                                <td class="px-3 py-3 text-center {{ $idx === 0 ? 'bg-blue-100 text-blue-700' : 'text-slate-700' }} {{ $idx !== count($days) - 1 ? 'border-r border-slate-300' : '' }}">
                                    {{ number_format($t) }}
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
