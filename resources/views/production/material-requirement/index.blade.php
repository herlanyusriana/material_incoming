<x-app-layout>
    <x-slot name="header">
        Material Requirement
    </x-slot>

    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-sm">
                            <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        Material Requirement
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">Material requirements from Production Planning BOM explosion</p>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <form action="{{ route('production.material-requirement.index') }}" method="GET" class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-slate-600">DATE:</label>
                        <input type="date" name="date" value="{{ $planDate->format('Y-m-d') }}"
                            class="rounded-lg border-slate-300 text-sm font-semibold shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            onchange="this.form.submit()">
                        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                        <label class="text-sm font-semibold text-slate-600 ml-2">MODE:</label>
                        <select name="calc_mode"
                            class="rounded-lg border-slate-300 text-sm font-semibold shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            onchange="this.form.submit()">
                            <option value="strict" @selected($calcMode === 'strict')>1. Strict</option>
                            <option value="with_substitute" @selected($calcMode === 'with_substitute')>2. With Substitute</option>
                        </select>
                    </form>

                    @if($session)
                        <span class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold ring-1
                            {{ $session->status === 'confirmed' ? 'bg-blue-50 text-blue-700 ring-blue-200' : ($session->status === 'completed' ? 'bg-green-50 text-green-700 ring-green-200' : 'bg-amber-50 text-amber-700 ring-amber-200') }}">
                            {{ ucfirst($session->status) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        @if($session)
            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">FG Planned</div>
                    <div class="mt-1 text-2xl font-bold text-slate-900">{{ $totalFgPlanned }}</div>
                    <div class="text-xs text-slate-400">parts with plan qty &gt; 0</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Components</div>
                    <div class="mt-1 text-2xl font-bold text-slate-900">{{ $totalComponents }}</div>
                    <div class="text-xs text-slate-400">unique RM/WIP required</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 {{ $totalShortage > 0 ? 'ring-2 ring-red-200' : '' }}">
                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Shortage (BUY)</div>
                    <div class="mt-1 text-2xl font-bold {{ $totalShortage > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $totalShortage }}</div>
                    <div class="text-xs text-slate-400">components with insufficient stock</div>
                </div>
            </div>

            {{-- Table --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b-2 border-slate-900">
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[120px]">
                                    <a href="{{ route('production.material-requirement.index', ['date' => $planDate->format('Y-m-d'), 'calc_mode' => $calcMode, 'sort_by' => 'component', 'sort_dir' => $sortBy === 'component' && $sortDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 inline-flex items-center gap-1">
                                        Component Part No
                                        @if($sortBy === 'component')
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="{{ $sortDir === 'asc' ? 'M5 10l5-5 5 5' : 'M5 10l5 5 5-5' }}"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[150px]">
                                    Component Name
                                </th>
                                <th class="px-3 py-3 text-center text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[70px]">
                                    Class
                                </th>
                                <th class="px-3 py-3 text-center text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[70px]">
                                    <a href="{{ route('production.material-requirement.index', ['date' => $planDate->format('Y-m-d'), 'calc_mode' => $calcMode, 'sort_by' => 'type', 'sort_dir' => $sortBy === 'type' && $sortDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 inline-flex items-center gap-1">
                                        Type
                                        @if($sortBy === 'type')
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="{{ $sortDir === 'asc' ? 'M5 10l5-5 5 5' : 'M5 10l5 5 5-5' }}"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[100px]">
                                    Gross Req
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[100px]">
                                    Stock On Hand
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[170px]">
                                    Substitutes
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[100px]">
                                    <a href="{{ route('production.material-requirement.index', ['date' => $planDate->format('Y-m-d'), 'calc_mode' => $calcMode, 'sort_by' => 'net_qty', 'sort_dir' => $sortBy === 'net_qty' && $sortDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 inline-flex items-center gap-1">
                                        Net Req
                                        @if($sortBy === 'net_qty')
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="{{ $sortDir === 'asc' ? 'M5 10l5-5 5 5' : 'M5 10l5 5 5-5' }}"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-3 py-3 text-center text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[50px]">
                                    UOM
                                </th>
                                <th class="px-3 py-3 text-center text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[80px]">
                                    <a href="{{ route('production.material-requirement.index', ['date' => $planDate->format('Y-m-d'), 'calc_mode' => $calcMode, 'sort_by' => 'status', 'sort_dir' => $sortBy === 'status' && $sortDir === 'asc' ? 'desc' : 'asc']) }}"
                                        class="hover:text-indigo-600 inline-flex items-center gap-1">
                                        Status
                                        @if($sortBy === 'status')
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="{{ $sortDir === 'asc' ? 'M5 10l5-5 5 5' : 'M5 10l5 5 5-5' }}"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[150px]">
                                    Used By (FG)
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @forelse ($requirements as $req)
                                <tr class="hover:bg-slate-50 transition-colors border-b border-slate-100">
                                    <td class="px-4 py-3 font-mono text-xs font-bold text-slate-700 border-x border-slate-100">
                                        {{ $req['component_part_no'] }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600 border-x border-slate-100">
                                        {{ $req['component_part_name'] }}
                                    </td>
                                    <td class="px-3 py-3 text-center text-xs border-x border-slate-100">
                                        @if($req['component_classification'] === 'RM')
                                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[10px] font-bold">RM</span>
                                        @elseif($req['component_classification'] === 'WIP')
                                            <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded text-[10px] font-bold">WIP</span>
                                        @elseif($req['component_classification'] === 'FG')
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-[10px] font-bold">FG</span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center border-x border-slate-100">
                                        @if(in_array($req['make_or_buy'], ['BUY', 'B', 'PURCHASE']))
                                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-semibold">BUY</span>
                                        @elseif(in_array($req['make_or_buy'], ['MAKE', 'M']))
                                            <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded text-xs font-semibold">MAKE</span>
                                        @elseif(in_array($req['make_or_buy'], ['FREE_ISSUE', 'FI', 'FREE ISSUE']))
                                            <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-xs font-semibold">FREE</span>
                                        @else
                                            <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-xs">{{ $req['make_or_buy'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs font-medium text-slate-700 border-x border-slate-100">
                                        {{ number_format($req['gross_qty'], 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs font-medium text-slate-700 border-x border-slate-100">
                                        {{ number_format($req['stock_on_hand'], 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600 border-x border-slate-100">
                                        @if(!empty($req['substitutes']))
                                            @foreach($req['substitutes'] as $sub)
                                                <div class="inline-flex items-center gap-1 mr-2 mb-1">
                                                    <span class="font-mono font-semibold text-slate-700">{{ $sub['part_no'] }}</span>
                                                    <span class="text-slate-400">({{ number_format($sub['stock_on_hand'] ?? 0, 2) }})</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs font-bold border-x border-slate-100 {{ $req['net_qty'] > 0 && $req['status'] === 'shortage' ? 'text-red-600' : 'text-slate-700' }}">
                                        {{ number_format($req['net_qty'], 2) }}
                                    </td>
                                    <td class="px-3 py-3 text-center text-xs text-slate-500 border-x border-slate-100">
                                        {{ $req['uom'] ?? 'PCS' }}
                                    </td>
                                    <td class="px-3 py-3 text-center border-x border-slate-100">
                                        @if($req['status'] === 'N/A')
                                            <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-semibold">N/A</span>
                                        @elseif($req['status'] === 'available')
                                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">OK</span>
                                        @else
                                            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">SHORTAGE</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600 border-x border-slate-100">
                                        @foreach($req['fg_sources'] as $fg)
                                            <div class="inline-flex items-center gap-1 mr-2 mb-1">
                                                <span class="font-mono font-semibold text-slate-700">{{ $fg['part']->part_no }}</span>
                                                <span class="text-slate-400">({{ number_format($fg['plan_qty']) }})</span>
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-6 py-20 text-center text-slate-500 bg-white">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-16 h-16 text-slate-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            <p class="text-lg font-bold text-slate-400 uppercase tracking-widest">No material requirements</p>
                                            <p class="text-sm text-slate-400 mt-1">No planning lines with plan qty &gt; 0 found for this date</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            {{-- No session --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
                <svg class="w-16 h-16 text-slate-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-lg font-bold text-slate-400 uppercase tracking-widest">No Planning Session</p>
                <p class="text-sm text-slate-400 mt-1">No production planning session found for {{ $planDate->format('d M Y') }}</p>
                <a href="{{ route('production.planning.index', ['date' => $planDate->format('Y-m-d')]) }}"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Go to Production Planning
                </a>
            </div>
        @endif
    </div>
</x-app-layout>
