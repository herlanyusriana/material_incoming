<x-app-layout>
    <x-slot name="header">
        QDC History
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900">QDC History</h1>
                    <p class="mt-1 text-sm text-slate-500">Downtime records from production orders & operator app</p>
                </div>
                <a href="{{ route('production.qdc-history.pdf', request()->query()) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-red-700 px-4 py-2 text-sm font-bold text-white hover:bg-red-800 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download PDF
                </a>
            </div>

            <div class="mt-6 flex flex-wrap gap-4 items-end border-t border-slate-100 pt-6">
                <form action="{{ route('production.qdc-history.index') }}" method="GET"
                    class="flex flex-wrap items-end gap-3">
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
                                <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>
                                    {{ ucfirst($cat) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Machine</label>
                        <select name="machine"
                            class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach ($machines as $m)
                                <option value="{{ $m->id }}" {{ (int) $machine === $m->id ? 'selected' : '' }}>
                                    {{ $m->name }}
                                </option>
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
                        class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
                        Filter
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b-2 border-slate-900">
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200">Date</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200">Machine</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200">WO / Shift</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200">Category</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200">Start</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200">End</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200">Duration (min)</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200">Notes</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200">Operator</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @forelse ($downtimes as $dt)
                            @php
                                $catLower = strtolower($dt->category ?? '');
                                $categoryColors = [
                                    'qdc' => 'bg-amber-100 text-amber-700',
                                    'breakdown' => 'bg-red-100 text-red-700',
                                    'breakdown mesin' => 'bg-red-100 text-red-700',
                                    'material' => 'bg-blue-100 text-blue-700',
                                    'refill material' => 'bg-blue-100 text-blue-700',
                                    'material kendor/jatuh' => 'bg-blue-100 text-blue-700',
                                    'tunggu material' => 'bg-blue-100 text-blue-700',
                                    'quality' => 'bg-purple-100 text-purple-700',
                                    'quality check' => 'bg-purple-100 text-purple-700',
                                    'changeover' => 'bg-orange-100 text-orange-700',
                                    'ganti tipe/setting' => 'bg-orange-100 text-orange-700',
                                    'perbaikan coil' => 'bg-red-100 text-red-600',
                                    'cleaning' => 'bg-green-100 text-green-700',
                                ];
                                $badgeClass = $categoryColors[$catLower] ?? 'bg-slate-100 text-slate-700';
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors border-b border-slate-100">
                                <td class="px-4 py-3 text-xs text-slate-600 border-x border-slate-100">
                                    {{ $dt->date?->format('d/m/Y') ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-center border-x border-slate-100">
                                    @if($dt->source === 'app')
                                        <span class="inline-block text-[10px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700">APP</span>
                                    @else
                                        <span class="inline-block text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">WO</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-700 font-medium border-x border-slate-100">
                                    {{ $dt->machine_name }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-700 border-x border-slate-100">
                                    @if($dt->wo_id)
                                        <a href="{{ route('production.orders.show', $dt->wo_id) }}" class="font-mono font-bold text-indigo-600 hover:text-indigo-800">
                                            {{ $dt->wo_no }}
                                        </a>
                                    @elseif($dt->shift)
                                        <span class="font-semibold">{{ $dt->shift }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center border-x border-slate-100">
                                    <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded {{ $badgeClass }}">
                                        {{ strtoupper($dt->category) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-xs text-slate-700 border-x border-slate-100">
                                    @if($dt->source === 'app' && $dt->start_time)
                                        {{ \Carbon\Carbon::parse($dt->start_time)->format('H:i') }}
                                    @else
                                        {{ $dt->start_time ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-xs text-slate-700 border-x border-slate-100">
                                    @if($dt->source === 'app' && $dt->end_time)
                                        {{ \Carbon\Carbon::parse($dt->end_time)->format('H:i') }}
                                    @else
                                        {{ $dt->end_time ?? '-' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-xs border-x border-slate-100 {{ $dt->duration_minutes ? 'text-slate-900' : 'text-amber-600' }}">
                                    {{ $dt->duration_minutes !== null ? number_format($dt->duration_minutes) : 'Running' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600 border-x border-slate-100 max-w-[200px] truncate" title="{{ $dt->notes }}">
                                    {{ $dt->notes ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600 border-x border-slate-100">
                                    {{ $dt->operator }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-20 text-center text-slate-500 bg-white">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-16 h-16 text-slate-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-lg font-bold text-slate-400 uppercase tracking-widest">No downtime records found</p>
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
