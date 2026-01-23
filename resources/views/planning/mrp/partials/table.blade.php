@php
    /** @var array<int, array{part:\App\Models\GciPart, initial_stock:float|int, days:array<string,array<string,mixed>>, has_purchase?:bool, has_production?:bool}> $mrpRows */
    $mrpRows = $mrpRows ?? [];
    $modeLabel = $modeLabel ?? 'MRP';
    $showPoAction = (bool) ($showPoAction ?? false);
    $showIncoming = (bool) ($showIncoming ?? true);
@endphp

<div class="flex items-center justify-between gap-3">
    <div class="text-sm font-semibold text-slate-900">{{ $modeLabel }}</div>
</div>

@if(empty($mrpRows))
    <div class="rounded-xl border border-dashed border-slate-200 p-10 text-center text-slate-500">
        No data for this section.
    </div>
@else
    @if($showPoAction)
        <form action="{{ route('planning.mrp.generate-po') }}" method="POST" id="po-form-{{ \Illuminate\Support\Str::slug($modeLabel) }}">
            @csrf
            <div class="flex justify-end mb-2">
                <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-sm flex items-center gap-2">
                    Generate PO from Selection
                </button>
            </div>
    @endif

    <div class="overflow-x-auto border border-slate-300 rounded-xl shadow-sm">
        <div class="inline-block min-w-full align-middle">
            <div class="relative overflow-hidden">
                <table class="min-w-full divide-y divide-slate-300 border-collapse table-fixed w-max">
                    <thead class="bg-indigo-900 text-white">
                        <tr>
                            <th scope="col" class="sticky left-0 z-20 bg-indigo-900 px-3 py-2 text-left text-xs font-bold uppercase w-8">No.</th>
                            <th scope="col" class="sticky left-8 z-20 bg-indigo-900 px-3 py-2 text-left text-xs font-bold uppercase w-32 border-l border-indigo-700">Part No</th>
                            <th scope="col" class="sticky left-40 z-20 bg-indigo-900 px-3 py-2 text-left text-xs font-bold uppercase w-48 border-l border-indigo-700">Name / Spec</th>
                            <th scope="col" class="sticky left-80 z-20 bg-indigo-900 px-3 py-2 text-left text-xs font-bold uppercase w-24 border-l border-indigo-700">Item</th>
                            <th scope="col" class="sticky left-[26rem] z-20 bg-indigo-900 px-3 py-2 text-right text-xs font-bold uppercase w-24 border-l border-indigo-700 border-r-2 border-r-indigo-400">Stock</th>

                            @foreach ($dates as $date)
                                <th scope="col" class="px-1 py-1 text-center text-[10px] font-semibold w-12 border-l border-indigo-800">
                                    <div>{{ date('d', strtotime($date)) }}</div>
                                    <div class="text-[9px] opacity-70">{{ date('D', strtotime($date)) }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($mrpRows as $index => $row)
                            @php
                                $part = $row['part'];
                                $bgClass = $loop->even ? 'bg-slate-50' : 'bg-white';
                            @endphp

                            <tr class="{{ $bgClass }} group">
                                <td rowspan="4" class="sticky left-0 z-10 {{ $bgClass }} px-2 py-1 text-xs text-center border-r border-slate-200 font-mono text-slate-500">{{ $index + 1 }}</td>
                                <td rowspan="4" class="sticky left-8 z-10 {{ $bgClass }} px-2 py-1 text-xs border-r border-slate-200 font-mono text-indigo-700 font-bold whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span>{{ $part->part_no }}</span>
                                        @if(!empty($row['has_purchase']) && empty($row['has_production']))
                                            <span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold">BUY</span>
                                        @elseif(!empty($row['has_production']) && empty($row['has_purchase']))
                                            <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold">MAKE</span>
                                        @elseif(!empty($row['has_purchase']) && !empty($row['has_production']))
                                            <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-bold">MIX</span>
                                        @endif
                                    </div>
                                </td>
                                <td rowspan="4" class="sticky left-40 z-10 {{ $bgClass }} px-2 py-1 text-[11px] border-r border-slate-200 text-slate-700">
                                    <div class="font-semibold">{{ $part->part_name ?? '-' }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $part->model ?? '-' }}</div>
                                </td>
                                <td class="sticky left-80 z-10 {{ $bgClass }} px-2 py-1 text-[10px] font-semibold text-indigo-900 border-r border-slate-200">Demand</td>
                                <td class="sticky left-[26rem] z-10 {{ $bgClass }} px-2 py-1 text-right text-xs text-slate-400 border-r-2 border-slate-300">-</td>

                                @foreach ($dates as $date)
                                    @php $val = $row['days'][$date]['demand'] ?? 0; @endphp
                                    <td class="px-1 py-1 text-center text-xs border-l border-slate-100 {{ $val > 0 ? 'font-bold text-slate-900' : 'text-slate-300' }}">
                                        {{ $val > 0 ? formatNumber($val) : '-' }}
                                    </td>
                                @endforeach
                            </tr>

                            @if($showIncoming)
                            <tr class="{{ $bgClass }}">
                                <td class="sticky left-80 z-10 {{ $bgClass }} px-2 py-1 text-[10px] font-semibold text-slate-900 border-r border-slate-200">Incoming</td>
                                <td class="sticky left-[26rem] z-10 {{ $bgClass }} px-2 py-1 text-right text-xs text-slate-400 border-r-2 border-slate-300">-</td>

                                @foreach ($dates as $date)
                                    @php $val = $row['days'][$date]['incoming'] ?? 0; @endphp
                                    <td class="px-1 py-1 text-center text-xs border-l border-slate-100 {{ $val > 0 ? 'bg-emerald-50 font-bold text-emerald-700' : 'text-slate-300' }}">
                                        {{ $val > 0 ? formatNumber($val) : '-' }}
                                    </td>
                                @endforeach
                            </tr>
                            @endif

                            <tr class="{{ $bgClass }}">
                                <td class="sticky left-80 z-10 {{ $bgClass }} px-2 py-1 text-[10px] font-semibold text-slate-900 border-r border-slate-200">Stock</td>
                                <td class="sticky left-[26rem] z-10 {{ $bgClass }} px-2 py-1 text-right text-xs font-bold text-slate-800 border-r-2 border-slate-300 bg-yellow-50">
                                    {{ formatNumber($row['initial_stock']) }}
                                </td>

                                @foreach ($dates as $date)
                                    @php
                                        $val = $row['days'][$date]['projected_stock'] ?? 0;
                                        $stockClass = $val < 0 ? 'text-red-600 font-bold bg-red-50' : 'text-slate-700';
                                    @endphp
                                    <td class="px-1 py-1 text-center text-xs border-l border-slate-100 {{ $stockClass }}">
                                        {{ formatNumber($val) }}
                                    </td>
                                @endforeach
                            </tr>

                            <tr class="{{ $bgClass }} border-b-2 border-slate-300">
                                <td class="sticky left-80 z-10 {{ $bgClass }} px-2 py-1 text-[10px] font-bold text-red-700 border-r border-slate-200">Planned</td>
                                <td class="sticky left-[26rem] z-10 {{ $bgClass }} px-2 py-1 text-right text-xs text-slate-400 border-r-2 border-slate-300">-</td>

                                @foreach ($dates as $date)
                                    @php $val = $row['days'][$date]['planned_order_rec'] ?? 0; @endphp
                                    <td class="px-1 py-1 text-center border-l border-slate-100">
                                        @if($val > 0)
                                            <div class="flex flex-col items-center justify-center gap-1">
                                                <span class="text-xs font-bold text-red-600">{{ formatNumber($val) }}</span>
                                                @if($showPoAction)
                                                    <input type="checkbox" name="items[{{ $part->id }}]" value="{{ $val }}" class="h-3 w-3 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" title="Create PO for {{ $val }} items">
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-slate-300 text-xs">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($showPoAction)
        </form>
    @endif
@endif
