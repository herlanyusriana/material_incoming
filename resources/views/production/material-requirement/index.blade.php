<x-app-layout>
    <x-slot name="header">
        Material Requirement
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Material Requirement</h1>
                    <p class="mt-1 text-sm text-slate-500">Kebutuhan material per Work Order pada tanggal produksi yang dipilih.</p>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <form action="{{ route('production.material-requirement.index') }}" method="GET" class="flex items-center gap-2 flex-wrap">
                        <label class="text-sm font-semibold text-slate-600">Date:</label>
                        <input type="date" name="date" value="{{ $planDate->format('Y-m-d') }}"
                            class="rounded-lg border-slate-300 text-sm font-semibold shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            onchange="this.form.submit()">
                        <label class="text-sm font-semibold text-slate-600 ml-2">Mode:</label>
                        <select name="calc_mode"
                            class="rounded-lg border-slate-300 text-sm font-semibold shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            onchange="this.form.submit()">
                            <option value="strict" @selected($calcMode === 'strict')>Strict</option>
                            <option value="with_substitute" @selected($calcMode === 'with_substitute')>With Substitute</option>
                        </select>
                        <label class="text-sm font-semibold text-slate-600 ml-2">Search:</label>
                        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="WO / transaction / part no"
                            class="rounded-lg border-slate-300 text-sm shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <input type="hidden" name="sort_by" value="{{ $sortBy }}">
                        <input type="hidden" name="sort_dir" value="{{ $sortDir }}">
                        <button type="submit"
                            class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                            Apply
                        </button>
                        @if(!empty($q))
                            <a href="{{ route('production.material-requirement.index', ['date' => $planDate->format('Y-m-d'), 'calc_mode' => $calcMode, 'sort_by' => $sortBy, 'sort_dir' => $sortDir]) }}"
                                class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                Reset Search
                            </a>
                        @endif
                    </form>

                    @if($session)
                        <span class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold ring-1
                            {{ $session->status === 'confirmed' ? 'bg-blue-50 text-blue-700 ring-blue-200' : ($session->status === 'completed' ? 'bg-green-50 text-green-700 ring-green-200' : 'bg-amber-50 text-amber-700 ring-amber-200') }}">
                            Planning Session: {{ ucfirst($session->status) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">WO Count</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $totalOrders }}</div>
                <div class="text-xs text-slate-400">work order pada tanggal ini</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">FG Planned</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $totalFgPlanned }}</div>
                <div class="text-xs text-slate-400">WO dengan qty planned &gt; 0</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Material Rows</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $totalComponents }}</div>
                <div class="text-xs text-slate-400">baris kebutuhan material per WO</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 {{ $totalShortage > 0 ? 'ring-2 ring-red-200' : '' }}">
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Shortage Rows</div>
                <div class="mt-1 text-2xl font-bold {{ $totalShortage > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $totalShortage }}</div>
                <div class="text-xs text-slate-400">item material dengan kekurangan stok</div>
            </div>
        </div>

        @forelse($requirementsByOrder as $woRequirement)
            @php
                $order = $woRequirement['order'];
                $materials = $woRequirement['materials'];
            @endphp
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-lg font-bold text-slate-900">{{ $order->production_order_number ?: 'WO#' . $order->id }}</h2>
                                @if($order->transaction_no)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">{{ $order->transaction_no }}</span>
                                @endif
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ strtoupper(str_replace('_', ' ', $order->status)) }}</span>
                            </div>
                            <div class="mt-1 text-sm text-slate-600">
                                {{ $order->part?->part_no ?? '-' }} • {{ $order->part?->part_name ?? '-' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-500">
                                Plan Date: {{ $order->plan_date ? \Carbon\Carbon::parse($order->plan_date)->format('d M Y') : '-' }}
                                • Qty Planned: {{ number_format((float) $order->qty_planned, 2) }}
                                @if($order->process_name)
                                    • Process: {{ $order->process_name }}
                                @endif
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-right">
                            <div>
                                <div class="text-[11px] uppercase tracking-wide text-slate-500">Materials</div>
                                <div class="text-lg font-bold text-slate-900">{{ $woRequirement['material_count'] }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] uppercase tracking-wide text-slate-500">Shortage</div>
                                <div class="text-lg font-bold {{ $woRequirement['shortage_count'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $woRequirement['shortage_count'] }}</div>
                            </div>
                            <div>
                                <div class="text-[11px] uppercase tracking-wide text-slate-500">Net Shortage</div>
                                <div class="text-lg font-bold {{ $woRequirement['shortage_total'] > 0 ? 'text-red-600' : 'text-slate-700' }}">{{ number_format((float) $woRequirement['shortage_total'], 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                @if(!$woRequirement['has_bom'])
                    <div class="px-6 py-6 text-sm text-amber-700 bg-amber-50 border-t border-amber-200">
                        WO ini belum punya BOM aktif, jadi kebutuhan material belum bisa dihitung.
                    </div>
                @elseif($materials->isEmpty())
                    <div class="px-6 py-6 text-sm text-slate-500">
                        Tidak ada komponen material yang dihitung untuk WO ini.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-white border-b border-slate-200">
                                <tr>
                                    <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-wider text-slate-600">NO</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-600">Component</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-600">Process</th>
                                    <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-wider text-slate-600">Class</th>
                                    <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-wider text-slate-600">Type</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-600">Gross Req</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-600">Stock</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-600">Net Req</th>
                                    <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-wider text-slate-600">UOM</th>
                                    <th class="px-3 py-3 text-center text-xs font-bold uppercase tracking-wider text-slate-600">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-600">Substitutes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($materials as $material)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-3 py-3 text-center text-xs font-mono text-slate-500">
                                            {{ $loop->iteration }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-mono text-xs font-bold text-slate-800">{{ $material['component_part_no'] }}</div>
                                            <div class="text-xs text-slate-500">{{ $material['component_part_name'] }}</div>
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="text-xs font-semibold text-slate-700">{{ $material['process_name'] ?: ($material['component_classification'] === 'RM' ? 'Base Material' : '-') }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-center text-xs">
                                            @if($material['component_classification'] === 'RM')
                                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[10px] font-bold">RM</span>
                                            @elseif($material['component_classification'] === 'WIP')
                                                <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded text-[10px] font-bold">WIP</span>
                                            @elseif($material['component_classification'] === 'FG')
                                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-[10px] font-bold">FG</span>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-center text-xs">
                                            <span class="px-2 py-1 rounded text-xs font-semibold
                                                {{ in_array($material['make_or_buy'], ['BUY', 'B', 'PURCHASE'], true) ? 'bg-blue-100 text-blue-700' : (in_array($material['make_or_buy'], ['MAKE', 'M'], true) ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700') }}">
                                                {{ $material['make_or_buy'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-xs text-slate-700">{{ number_format((float) $material['gross_qty'], 2) }}</td>
                                        <td class="px-4 py-3 text-right font-mono text-xs text-slate-700">{{ number_format((float) $material['effective_stock_on_hand'], 2) }}</td>
                                        <td class="px-4 py-3 text-right font-mono text-xs font-bold {{ $material['status'] === 'shortage' ? 'text-red-600' : 'text-slate-800' }}">{{ number_format((float) $material['net_qty'], 2) }}</td>
                                        <td class="px-3 py-3 text-center text-xs text-slate-500">{{ $material['uom'] }}</td>
                                        <td class="px-3 py-3 text-center">
                                            @if($material['status'] === 'N/A')
                                                <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-semibold">N/A</span>
                                            @elseif($material['status'] === 'available')
                                                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">OK</span>
                                            @else
                                                <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">SHORTAGE</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs text-slate-600">
                                            @if(!empty($material['substitutes']))
                                                @foreach($material['substitutes'] as $substitute)
                                                    <div class="mb-1">
                                                        <span class="font-mono font-semibold text-slate-700">{{ $substitute['part_no'] }}</span>
                                                        <span class="text-slate-400">({{ number_format((float) ($substitute['stock_on_hand'] ?? 0), 2) }})</span>
                                                    </div>
                                                @endforeach
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
                <p class="text-lg font-bold text-slate-400 uppercase tracking-widest">No Work Orders</p>
                <p class="text-sm text-slate-400 mt-1">Tidak ada work order dengan qty planned &gt; 0 pada tanggal {{ $planDate->format('d M Y') }}</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
