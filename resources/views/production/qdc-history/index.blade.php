<x-app-layout>
    <x-slot name="header">
        {{ $type === 'downtime' ? 'Downtime History' : 'QDC History' }}
    </x-slot>

    @php
        $isDowntime = $type === 'downtime';
        $pageTitle = $isDowntime ? 'Downtime History' : 'QDC History';
        $pageDesc = $isDowntime
            ? 'Machine breakdown & trouble records'
            : 'Planned activity records (changeover, cleaning, briefing, etc)';
        $accentColor = $isDowntime ? 'red' : 'amber';
    @endphp

    <div class="space-y-6">
        {{-- Header + Filters --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900">{{ $pageTitle }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $pageDesc }}</p>
                </div>
                <a href="{{ route($routePrefix . '.pdf', request()->query()) }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900 transition-colors self-start">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download PDF
                </a>
            </div>

            <form action="{{ route($routePrefix . '.index') }}" method="GET"
                class="mt-6 flex flex-wrap items-end gap-3 border-t border-slate-100 pt-6">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                        class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}"
                        class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Category</label>
                    <select name="category"
                        class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Machine</label>
                    <select name="machine"
                        class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All</option>
                        @foreach ($machines as $m)
                            <option value="{{ $m->id }}" {{ (int) $machine === $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Source</label>
                    <select name="source"
                        class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="all" {{ ($source ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                        <option value="wo" {{ ($source ?? '') === 'wo' ? 'selected' : '' }}>Work Order</option>
                        <option value="app" {{ ($source ?? '') === 'app' ? 'selected' : '' }}>Operator App</option>
                    </select>
                </div>
                <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                    Filter
                </button>
            </form>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Total Events</div>
                <div class="text-3xl font-black text-{{ $accentColor }}-600">{{ number_format($totalCount) }}</div>
                <div class="text-xs text-slate-400 mt-1">records</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Total Duration</div>
                <div class="text-3xl font-black text-slate-900">{{ number_format($totalMinutes) }}</div>
                <div class="text-xs text-slate-400 mt-1">minutes ({{ number_format($totalMinutes / 60, 1) }} hrs)</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Machines</div>
                <div class="text-3xl font-black text-indigo-600">{{ $totalMachines }}</div>
                <div class="text-xs text-slate-400 mt-1">affected</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Avg Duration</div>
                <div class="text-3xl font-black text-slate-900">{{ $totalCount > 0 ? number_format($totalMinutes / $totalCount, 1) : 0 }}</div>
                <div class="text-xs text-slate-400 mt-1">min / event</div>
            </div>
        </div>

        {{-- Charts Row --}}
        @if($totalCount > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Category Breakdown --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-4">By Category</h3>
                <div class="space-y-3">
                    @foreach($categoryBreakdown->take(10) as $cb)
                        @php
                            $pct = $totalMinutes > 0 ? round(($cb['minutes'] / $totalMinutes) * 100, 1) : 0;
                            $catLower = strtolower($cb['category'] ?? '');
                            $barColor = match(true) {
                                str_contains($catLower, 'rusak') || str_contains($catLower, 'trouble') || str_contains($catLower, 'breakdown') => 'bg-red-500',
                                str_contains($catLower, 'material') || str_contains($catLower, 'quality') => 'bg-purple-500',
                                str_contains($catLower, 'ganti') || str_contains($catLower, 'setting') => 'bg-amber-500',
                                str_contains($catLower, 'cleaning') => 'bg-green-500',
                                str_contains($catLower, 'maintenance') || str_contains($catLower, 'perbaikan') => 'bg-teal-500',
                                str_contains($catLower, 'tunggu') => 'bg-blue-500',
                                str_contains($catLower, 'briefing') || str_contains($catLower, 'istirahat') => 'bg-sky-400',
                                str_contains($catLower, 'trial') => 'bg-orange-500',
                                default => 'bg-slate-400',
                            };
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-semibold text-slate-700 truncate">{{ $cb['category'] }}</span>
                                <span class="text-xs text-slate-500 font-bold whitespace-nowrap ml-2">{{ number_format($cb['minutes']) }}m &middot; {{ $cb['count'] }}x</span>
                            </div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $barColor }} rounded-full transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Machine Breakdown --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-4">By Machine</h3>
                <div class="space-y-3">
                    @foreach($machineBreakdown->take(10) as $mb)
                        @php
                            $pct = $totalMinutes > 0 ? round(($mb['minutes'] / $totalMinutes) * 100, 1) : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-semibold text-slate-700 truncate">{{ $mb['machine'] }}</span>
                                <span class="text-xs text-slate-500 font-bold whitespace-nowrap ml-2">{{ number_format($mb['minutes']) }}m &middot; {{ $mb['count'] }}x</span>
                            </div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Daily Trend --}}
        @if($dailyTrend->count() > 1)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-4">Daily Trend</h3>
            <div class="flex items-end gap-1 h-32">
                @php $maxMin = $dailyTrend->max('minutes') ?: 1; @endphp
                @foreach($dailyTrend as $day)
                    @php $h = max(4, round(($day['minutes'] / $maxMin) * 100)); @endphp
                    <div class="flex-1 flex flex-col items-center group relative">
                        <div class="w-full bg-{{ $accentColor }}-400 hover:bg-{{ $accentColor }}-500 rounded-t transition-all cursor-pointer"
                             style="height: {{ $h }}%"
                             title="{{ $day['date'] }}: {{ $day['minutes'] }}m ({{ $day['count'] }}x)">
                        </div>
                        @if($dailyTrend->count() <= 14)
                            <div class="text-[8px] text-slate-400 mt-1 truncate w-full text-center">{{ \Carbon\Carbon::parse($day['date'])->format('d/m') }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
            @if($dailyTrend->count() > 14)
                <div class="flex justify-between mt-1">
                    <span class="text-[9px] text-slate-400">{{ \Carbon\Carbon::parse($dailyTrend->first()['date'])->format('d M') }}</span>
                    <span class="text-[9px] text-slate-400">{{ \Carbon\Carbon::parse($dailyTrend->last()['date'])->format('d M') }}</span>
                </div>
            @endif
        </div>
        @endif
        @endif

        {{-- Data Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/30 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Detail Records</h2>
                <span class="text-xs text-slate-400">{{ $downtimes->total() }} total &middot; page {{ $downtimes->currentPage() }}/{{ $downtimes->lastPage() }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b-2 border-slate-900">
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-slate-900 uppercase tracking-wider">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider">Machine</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider">WO / Shift</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-slate-900 uppercase tracking-wider">Category</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-slate-900 uppercase tracking-wider">Start</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-slate-900 uppercase tracking-wider">End</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-slate-900 uppercase tracking-wider">Duration</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider">Notes</th>
                            @if(!$isDowntime)
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider">Refill</th>
                            @endif
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider">Operator</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse ($downtimes as $dt)
                            @php
                                $catLower = strtolower($dt->category ?? '');
                                $categoryColors = [
                                    'mesin rusak' => 'bg-red-100 text-red-700',
                                    'robot trouble' => 'bg-red-100 text-red-700',
                                    'dies trouble' => 'bg-red-100 text-red-700',
                                    'tooling trouble' => 'bg-red-100 text-red-700',
                                    'listrik trouble / mati lampu' => 'bg-red-100 text-red-700',
                                    'breakdown mesin' => 'bg-red-100 text-red-700',
                                    'perbaikan coil' => 'bg-red-100 text-red-600',
                                    'material ng quality' => 'bg-purple-100 text-purple-700',
                                    'quality check' => 'bg-purple-100 text-purple-700',
                                    'maintenance' => 'bg-teal-100 text-teal-700',
                                    'tunggu material' => 'bg-blue-100 text-blue-700',
                                    'material kendor/jatuh' => 'bg-blue-100 text-blue-700',
                                    'ganti type' => 'bg-amber-100 text-amber-700',
                                    'ganti tipe/setting' => 'bg-amber-100 text-amber-700',
                                    'ganti material / reffil material' => 'bg-amber-100 text-amber-700',
                                    'cleaning machine' => 'bg-green-100 text-green-700',
                                    'cleaning' => 'bg-green-100 text-green-700',
                                    'briefing' => 'bg-indigo-100 text-indigo-700',
                                    'trial' => 'bg-orange-100 text-orange-700',
                                    'istirahat' => 'bg-sky-50 text-sky-500',
                                ];
                                $badgeClass = $categoryColors[$catLower] ?? 'bg-slate-100 text-slate-700';
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors border-b border-slate-100">
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    {{ $dt->date?->format('d/m/Y') ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($dt->source === 'app')
                                        <span class="inline-block text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700">APP</span>
                                    @else
                                        <span class="inline-block text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">WO</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-700 font-medium">{{ $dt->machine_name }}</td>
                                <td class="px-4 py-3 text-xs text-slate-700">
                                    @if($dt->wo_id)
                                        <a href="{{ route('production.orders.show', $dt->wo_id) }}" class="font-mono font-bold text-indigo-600 hover:text-indigo-800">{{ $dt->wo_no }}</a>
                                    @elseif($dt->shift)
                                        <span class="font-semibold">{{ $dt->shift }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded {{ $badgeClass }}">{{ strtoupper($dt->category) }}</span>
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-xs text-slate-700">
                                    @if($dt->source === 'app' && $dt->start_time)
                                        {{ \Carbon\Carbon::parse($dt->start_time)->format('H:i') }}
                                    @else
                                        {{ $dt->start_time ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-xs text-slate-700">
                                    @if($dt->source === 'app' && $dt->end_time)
                                        {{ \Carbon\Carbon::parse($dt->end_time)->format('H:i') }}
                                    @else
                                        {{ $dt->end_time ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-bold font-mono text-xs {{ $dt->duration_minutes !== null ? 'text-slate-900' : 'text-amber-600' }}">
                                    @if($dt->start_time && $dt->end_time)
                                        @php
                                            $start = \Carbon\Carbon::parse($dt->start_time);
                                            $end = \Carbon\Carbon::parse($dt->end_time);
                                            $diffSec = $end->diffInSeconds($start);
                                            $m = intdiv($diffSec, 60);
                                            $s = $diffSec % 60;
                                        @endphp
                                        {{ $m }}m {{ str_pad($s, 2, '0', STR_PAD_LEFT) }}s
                                    @elseif($dt->duration_minutes !== null)
                                        {{ $dt->duration_minutes }}m
                                    @else
                                        <span class="text-amber-500 animate-pulse">Running</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600 max-w-[200px] truncate" title="{{ $dt->notes }}">{{ $dt->notes ?? '-' }}</td>
                                @if(!$isDowntime)
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    @if($dt->refill_part_no)
                                        <span class="font-mono font-semibold text-blue-700">{{ $dt->refill_part_no }}</span>
                                        <span class="text-slate-400">x{{ number_format($dt->refill_qty ?? 0) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                @endif
                                <td class="px-4 py-3 text-xs text-slate-600">{{ $dt->operator }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isDowntime ? 10 : 11 }}" class="px-6 py-20 text-center text-slate-500 bg-white">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-16 h-16 text-slate-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-lg font-bold text-slate-400 uppercase tracking-widest">No records found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($downtimes->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $downtimes->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>