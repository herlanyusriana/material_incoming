@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="text-sm font-semibold uppercase tracking-wider text-indigo-600">Dashboard</div>
                    <h1 class="mt-1 text-2xl font-black text-slate-900">Plant Performance</h1>
                    <p class="mt-2 text-sm text-slate-500">
                        KPI plant performance berdasarkan data produksi, material, logistics, dan quality yang sudah ada di sistem.
                    </p>
                </div>

                <form method="GET" class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Date From</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Date To</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                            class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
                            Refresh
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Planned Qty</div>
                <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format($summary['planned_qty'], 2) }}</div>
                <div class="mt-1 text-sm text-slate-500">{{ number_format($summary['orders_count']) }} WO in range</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Actual Qty</div>
                <div class="mt-2 text-3xl font-black text-emerald-600">{{ number_format($summary['actual_qty'], 2) }}</div>
                <div class="mt-1 text-sm text-slate-500">Good {{ number_format($summary['good_qty'], 2) }} / NG {{ number_format($summary['ng_qty'], 2) }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">OEE Components</div>
                <div class="mt-3 space-y-1 text-sm text-slate-600">
                    <div>Availability: <span class="font-bold text-slate-900">{{ number_format($availability, 2) }}%</span></div>
                    <div>Performance: <span class="font-bold text-slate-900">{{ number_format($performance, 2) }}%</span></div>
                    <div>Quality: <span class="font-bold text-slate-900">{{ number_format($quality, 2) }}%</span></div>
                </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Support Data</div>
                <div class="mt-3 space-y-1 text-sm text-slate-600">
                    <div>Delivery Notes: <span class="font-bold text-slate-900">{{ number_format($summary['delivery_notes_count']) }}</span></div>
                    <div>Stock Opname Lines: <span class="font-bold text-slate-900">{{ number_format($summary['stock_opname_lines']) }}</span></div>
                    <div>Transport Defect Source:
                        <span class="font-bold {{ $summary['transport_defect_source_ready'] ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ $summary['transport_defect_source_ready'] ? 'READY' : 'PENDING' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @foreach ($departments as $department => $items)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-black text-slate-900">{{ $department }}</h2>
                        <div class="text-sm text-slate-500">Kalkulasi mengikuti formula yang Anda berikan.</div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($items as $item)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $department }}</div>
                            <div class="mt-2 text-base font-bold text-slate-900">{{ $item['name'] }}</div>
                            <div class="mt-3 text-3xl font-black text-indigo-700">
                                @if ($item['suffix'] === 'IDR')
                                    Rp {{ number_format($item['value'], 2) }}
                                @elseif ($item['suffix'] === '%')
                                    {{ number_format($item['value'], 2) }}%
                                @elseif ($item['suffix'] === 'min')
                                    {{ number_format($item['value'], 2) }} min
                                @else
                                    {{ number_format($item['value'], 2) }} {{ $item['suffix'] }}
                                @endif
                            </div>
                            <div class="mt-3 text-xs leading-5 text-slate-500">
                                Formula: {{ $item['formula'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900 shadow-sm">
            <div class="font-bold">Catatan sumber data</div>
            <div class="mt-2">
                `Quality Defect in Transport (IDR)` saat ini masih fallback ke `0` jika belum ada tabel/log khusus defect handling saat delivery.
                KPI lain sudah dihitung dari data produksi, stock opname, material handover, dan shipment yang ada sekarang.
            </div>
        </div>
    </div>
@endsection
