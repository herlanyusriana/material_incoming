<x-app-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeInUp 0.4s ease-out both; }
        .animate-fade-in-1 { animation-delay: 0.05s; }
        .animate-fade-in-2 { animation-delay: 0.1s; }
        .animate-fade-in-3 { animation-delay: 0.15s; }
        .animate-fade-in-4 { animation-delay: 0.2s; }
        .card-hover { transition: all 0.25s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
        .oee-ring { transition: stroke-dashoffset 1s ease-out; }
        .dept-card { transition: all 0.2s ease; }
        .dept-card:hover { transform: scale(1.01); box-shadow: 0 4px 12px rgba(0,0,0,0.04); }
    </style>

    <div class="space-y-5">

        {{-- ══════════════════════════════════════════════════════
             HEADER + DATE FILTER
             ══════════════════════════════════════════════════════ --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-indigo-600">
                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                        Dashboard
                    </div>
                    <h1 class="mt-1 text-xl font-black text-slate-900">GCI Smart Dashboard</h1>
                    <p class="mt-1 text-xs text-slate-500">
                        Overview Incoming Material dan Plant Performance KPI dalam satu tampilan.
                    </p>
                </div>

                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                            class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                            class="rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <button type="submit"
                        class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 transition-colors shadow-sm">
                        Refresh
                    </button>
                </form>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
             INCOMING MATERIAL SUMMARY CARDS
             ══════════════════════════════════════════════════════ --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $cards = [
                    ['label' => 'Total Departures', 'value' => $incomingSummary['total_departures'], 'sub' => 'All shipments', 'color' => 'indigo', 'icon' => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12'],
                    ['label' => 'Total Receives', 'value' => $incomingSummary['total_receives'], 'sub' => 'Processed items', 'color' => 'indigo', 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z'],
                    ['label' => 'Pending Items', 'value' => $incomingSummary['pending_items'], 'sub' => 'Need processing', 'color' => 'indigo', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                    ['label' => 'Today Receives', 'value' => $incomingSummary['today_receives'], 'sub' => 'Processed today', 'color' => 'indigo', 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
                ];
            @endphp
            @foreach ($cards as $i => $card)
                <div class="animate-fade-in animate-fade-in-{{ $i+1 }} card-hover rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">{{ $card['label'] }}</div>
                            <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format($card['value']) }}</div>
                            <div class="text-xs text-slate-400 mt-1">{{ $card['sub'] }}</div>
                        </div>
                        <div class="w-11 h-11 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/>
                            </svg>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ══════════════════════════════════════════════════════
             PLANT PERFORMANCE — OEE & PRODUCTION SUMMARY
             ══════════════════════════════════════════════════════ --}}
        <div class="grid gap-4 lg:grid-cols-3">
            {{-- OEE Gauge --}}
            <div class="card-hover rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col items-center justify-center">
                <div class="text-xs uppercase tracking-wider text-slate-400 font-bold mb-3">Overall Equipment Effectiveness</div>
                <div class="relative w-36 h-36">
                    <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="#e2e8f0" stroke-width="10"/>
                        @php
                            $circumference = 2 * M_PI * 52;
                            $oeeOffset = $circumference - ($oee / 100) * $circumference;
                            $oeeColor = $oee >= 85 ? '#4f46e5' : ($oee >= 60 ? '#6366f1' : '#818cf8');
                        @endphp
                        <circle cx="60" cy="60" r="52" fill="none" stroke="{{ $oeeColor }}" stroke-width="10"
                            stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $oeeOffset }}"
                            stroke-linecap="round" class="oee-ring"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-black text-slate-900">{{ number_format($oee, 1) }}%</span>
                        <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">OEE</span>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-3 w-full">
                    <div class="text-center p-2 rounded-lg bg-slate-50">
                        <div class="text-base font-bold text-indigo-600">{{ number_format($availability, 1) }}%</div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Avail.</div>
                    </div>
                    <div class="text-center p-2 rounded-lg bg-slate-50">
                        <div class="text-base font-bold text-indigo-600">{{ number_format($performance, 1) }}%</div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Perf.</div>
                    </div>
                    <div class="text-center p-2 rounded-lg bg-slate-50">
                        <div class="text-base font-bold text-indigo-600">{{ number_format($quality, 1) }}%</div>
                        <div class="text-[10px] uppercase tracking-wider text-slate-400 font-semibold">Quality</div>
                    </div>
                </div>
            </div>

            {{-- Production Summary --}}
            <div class="lg:col-span-2 grid gap-4 sm:grid-cols-2">
                <div class="card-hover rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                        <div class="text-xs uppercase tracking-wider text-slate-400 font-bold">Planned Qty</div>
                    </div>
                    <div class="text-3xl font-black text-slate-900">{{ number_format($plantSummary['planned_qty'], 0) }}</div>
                    <div class="mt-1 text-sm text-slate-500">{{ number_format($plantSummary['orders_count']) }} WO in range</div>
                </div>
                <div class="card-hover rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                        <div class="text-xs uppercase tracking-wider text-slate-400 font-bold">Actual Qty</div>
                    </div>
                    <div class="text-3xl font-black text-indigo-600">{{ number_format($plantSummary['actual_qty'], 0) }}</div>
                    <div class="mt-1 text-sm text-slate-500">
                        Good <span class="font-semibold text-slate-700">{{ number_format($plantSummary['good_qty'], 0) }}</span>
                        / NG <span class="font-semibold text-slate-700">{{ number_format($plantSummary['ng_qty'], 0) }}</span>
                    </div>
                </div>
                <div class="card-hover rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                        <div class="text-xs uppercase tracking-wider text-slate-400 font-bold">Production Achievement</div>
                    </div>
                    <div class="text-3xl font-black text-indigo-600">{{ number_format($productionAchievement, 1) }}%</div>
                    <div class="w-full bg-slate-200 rounded-full h-1.5 mt-3 overflow-hidden">
                        <div class="h-full rounded-full bg-indigo-500 transition-all duration-700"
                             style="width: {{ min($productionAchievement, 100) }}%"></div>
                    </div>
                </div>
                <div class="card-hover rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                        <div class="text-xs uppercase tracking-wider text-slate-400 font-bold">Support Data</div>
                    </div>
                    <div class="mt-2 space-y-2 text-sm text-slate-600">
                        <div class="flex justify-between items-center p-2 rounded-lg bg-slate-50">
                            <span>Delivery Notes</span>
                            <span class="font-bold text-slate-900">{{ number_format($plantSummary['delivery_notes_count']) }}</span>
                        </div>
                        <div class="flex justify-between items-center p-2 rounded-lg bg-slate-50">
                            <span>Stock Opname Lines</span>
                            <span class="font-bold text-slate-900">{{ number_format($plantSummary['stock_opname_lines']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
             DEPARTMENT KPIs
             ══════════════════════════════════════════════════════ --}}
        @php
            $deptIcons = [
                'Production' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                'Material' => 'M21 8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8Z M7 6V4h10v2',
                'Logistics' => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
                'Quality' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
            ];
        @endphp

        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($departments as $department => $items)
                <div class="card-hover rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                @foreach(explode(' M', $deptIcons[$department] ?? '') as $j => $path)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $j === 0 ? $path : 'M' . $path }}"/>
                                @endforeach
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900">{{ $department }}</h3>
                            <div class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold">KPI Metrics</div>
                        </div>
                    </div>
                    <div class="grid gap-2 {{ count($items) > 4 ? 'sm:grid-cols-2 xl:grid-cols-3' : 'sm:grid-cols-2' }}">
                        @foreach ($items as $item)
                            <div class="dept-card rounded-xl bg-slate-50 border border-slate-100 p-3">
                                <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold leading-tight">{{ $item['name'] }}</div>
                                <div class="mt-1.5 text-lg font-black text-slate-900">
                                    @if ($item['suffix'] === 'IDR')
                                        Rp {{ number_format($item['value'], 0) }}
                                    @elseif ($item['suffix'] === '%')
                                        {{ number_format($item['value'], 1) }}%
                                    @elseif ($item['suffix'] === 'min')
                                        {{ number_format($item['value'], 0) }} <span class="text-xs font-semibold text-slate-500">min</span>
                                    @elseif ($item['suffix'] === 'h')
                                        {{ number_format($item['value'], 1) }} <span class="text-xs font-semibold text-slate-500">hrs</span>
                                    @else
                                        {{ number_format($item['value'], 1) }} {{ $item['suffix'] }}
                                    @endif
                                </div>
                                <div class="mt-1 text-[10px] text-slate-400 leading-tight">{{ $item['formula'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ══════════════════════════════════════════════════════
             QC STATUS + RECENT RECEIVES
             ══════════════════════════════════════════════════════ --}}
        <div class="grid lg:grid-cols-3 gap-4">
            {{-- QC Status --}}
            <div class="card-hover rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="pb-3 border-b border-slate-100">
                    <h3 class="text-sm font-black text-slate-900">QC Status</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Quality check summary</p>
                </div>
                <div class="mt-4 space-y-2">
                    @php
                        $statuses = ['pass' => 'Pass', 'fail' => 'Fail', 'hold' => 'Hold'];
                        $qcStyles = [
                            'pass' => 'bg-indigo-50 text-indigo-700 ring-indigo-100',
                            'fail' => 'bg-slate-100 text-slate-700 ring-slate-200',
                            'hold' => 'bg-slate-50 text-slate-600 ring-slate-100',
                        ];
                    @endphp
                    @foreach ($statuses as $key => $label)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 ring-1 ring-slate-100">
                            <span class="text-sm font-semibold text-slate-700">{{ $label }}</span>
                            <span class="px-3 py-1 text-sm font-black rounded-lg {{ $qcStyles[$key] }} ring-1">
                                {{ $statusCounts[$key] ?? 0 }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Recent Receives --}}
            <div class="lg:col-span-2 card-hover rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-black text-slate-900">Recent Receives</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Latest 5 processed items</p>
                    </div>
                    <a href="{{ route('receives.completed') }}"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-colors shadow-sm">
                        View All
                    </a>
                </div>
                <div class="mt-4 space-y-2">
                    @forelse ($recentReceives as $receive)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors ring-1 ring-slate-100">
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-slate-900 text-sm truncate">{{ $receive->tag }}</div>
                                <div class="text-xs text-slate-500 truncate">
                                    {{ $receive->arrivalItem?->part?->part_no ?? 'N/A' }} —
                                    {{ $receive->arrivalItem?->arrival?->vendor?->vendor_name ?? '' }}
                                </div>
                            </div>
                            <div class="text-right ml-3 shrink-0">
                                <div class="font-black text-slate-900">{{ number_format($receive->qty) }}</div>
                                @php
                                    $qcColor = match ($receive->qc_status) {
                                        'pass' => 'bg-indigo-100 text-indigo-700',
                                        'fail' => 'bg-slate-200 text-slate-700',
                                        default => 'bg-slate-100 text-slate-600',
                                    };
                                @endphp
                                <span class="inline-flex px-2 py-0.5 text-xs font-bold rounded-lg {{ $qcColor }}">
                                    {{ ucfirst($receive->qc_status) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400 text-center py-8">No receives yet</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════
             DEPARTURE RECORDS
             ══════════════════════════════════════════════════════ --}}
        <div class="card-hover rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-black text-slate-900">Departure Records</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Inbound shipments with pricing breakdowns</p>
                </div>
                <a href="{{ route('departures.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition-colors shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    New Departure
                </a>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($departures as $arrival)
                    @php
                        $totalItems = $arrival->items->count();
                        $totalValue = $arrival->items->sum('total_price');
                        $totalQty = $arrival->items->sum('qty_goods');
                        $totalReceived = $arrival->items->sum(function ($item) {
                            return $item->receives->sum('qty');
                        });
                        $progress = $totalQty > 0 ? round(($totalReceived / $totalQty) * 100) : 0;
                    @endphp
                    <div class="rounded-xl border border-slate-100 p-4 hover:shadow-md transition-all bg-white group">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-2">
                                    <h4 class="text-sm font-black text-slate-900 truncate">
                                        {{ $arrival->invoice_no ?: 'Departure' }}</h4>
                                    <span class="text-[10px] text-slate-400 shrink-0">by {{ $arrival->creator->name ?? 'System' }}</span>
                                </div>
                                <div class="grid md:grid-cols-3 gap-1.5 text-xs text-slate-600">
                                    <div>
                                        <span class="text-slate-400">Vendor:</span>
                                        <span class="font-semibold text-slate-700 ml-1">{{ $arrival->vendor->vendor_name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400">Items:</span>
                                        <span class="font-medium text-slate-700 ml-1">{{ $totalItems }} item{{ $totalItems != 1 ? 's' : '' }}</span>
                                        <span class="text-slate-400">({{ number_format($totalQty) }} pcs)</span>
                                    </div>
                                    <div>
                                        <span class="text-slate-400">Value:</span>
                                        <span class="font-bold text-indigo-600 ml-1">{{ $arrival->currency }} {{ number_format($totalValue, 2) }}</span>
                                    </div>
                                </div>

                                <div class="mt-2.5">
                                    <div class="flex items-center justify-between text-[10px] mb-1">
                                        <span class="font-semibold text-slate-400 uppercase tracking-wider">Received</span>
                                        <span class="font-bold {{ $progress == 100 ? 'text-indigo-600' : 'text-slate-600' }}">{{ $totalReceived }} / {{ number_format($totalQty) }}</span>
                                    </div>
                                    <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-full rounded-full bg-indigo-500 transition-all duration-500"
                                            style="width: {{ $progress }}%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-2 shrink-0">
                                <span class="text-[10px] text-slate-400">{{ $arrival->invoice_date->format('d M Y') }}</span>
                                <a href="{{ route('departures.show', $arrival) }}"
                                    class="px-3 py-1.5 bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 text-xs font-bold rounded-lg transition-colors">
                                    Details →
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <div class="text-slate-300 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-1">No Departures Yet</h3>
                        <p class="text-sm text-slate-400">Start by creating your first departure record.</p>
                    </div>
                @endforelse
            </div>

            @if($departures->hasPages())
                <div class="mt-6 pt-4 border-t border-slate-100">
                    {{ $departures->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>