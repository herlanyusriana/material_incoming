@php
    /** @var array<int, array{part:\App\Models\GciPart, initial_stock:float|int, has_purchase?:bool, has_production?:bool, monthly_demand?:array<string,float|int>, demand_total?:float|int, planned_order_total?:float|int, end_stock?:float|int, net_required?:float|int}> $mrpRows */
    $mrpRows = $mrpRows ?? [];
    $modeLabel = $modeLabel ?? 'MRP';
    $showPoAction = (bool) ($showPoAction ?? false);
    /** @var list<string> $months */
    $months = $months ?? [];
    /** @var array<string,string> $monthLabels */
    $monthLabels = $monthLabels ?? [];
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
                            <th scope="col" class="sticky left-0 z-20 bg-indigo-900 px-3 py-2 text-left text-xs font-bold uppercase w-10">No.</th>
                            <th scope="col" class="sticky left-10 z-20 bg-indigo-900 px-3 py-2 text-left text-xs font-bold uppercase w-36 border-l border-indigo-700">Part No</th>
                            <th scope="col" class="sticky left-46 z-20 bg-indigo-900 px-3 py-2 text-left text-xs font-bold uppercase w-64 border-l border-indigo-700">Name / Spec</th>
                            <th scope="col" class="sticky left-[26rem] z-20 bg-indigo-900 px-3 py-2 text-right text-xs font-bold uppercase w-24 border-l border-indigo-700 border-r-2 border-r-indigo-400">Stock</th>
                            @foreach ($months as $ym)
                                <th scope="col" class="px-2 py-2 text-center text-xs font-bold uppercase w-20 border-l border-indigo-800">
                                    {{ $monthLabels[$ym] ?? $ym }}
                                </th>
                            @endforeach
                            <th scope="col" class="px-2 py-2 text-right text-xs font-bold uppercase w-28 border-l border-indigo-700">Total</th>
                            @if($showPoAction)
                                <th scope="col" class="px-2 py-2 text-center text-xs font-bold uppercase w-16 border-l border-indigo-700">PO</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($mrpRows as $index => $row)
                            @php
                                $part = $row['part'];
                                $bgClass = $loop->even ? 'bg-slate-50' : 'bg-white';
                                $stock = (float) ($row['initial_stock'] ?? 0);
                                $monthly = $row['monthly_demand'] ?? [];
                                $total = (float) ($row['demand_total'] ?? array_sum($monthly));
                                $planned = (float) ($row['planned_order_total'] ?? 0);
                            @endphp
                            <tr class="{{ $bgClass }} hover:bg-slate-100">
                                <td class="sticky left-0 z-10 {{ $bgClass }} px-3 py-2 text-xs text-center font-mono text-slate-500">{{ $index + 1 }}</td>
                                <td class="sticky left-10 z-10 {{ $bgClass }} px-3 py-2 text-xs border-l border-slate-200 font-mono text-indigo-700 font-bold whitespace-nowrap">
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
                                <td class="sticky left-46 z-10 {{ $bgClass }} px-3 py-2 text-[11px] border-l border-slate-200 text-slate-700">
                                    <div class="font-semibold">{{ $part->part_name ?? '-' }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $part->model ?? '-' }}</div>
                                </td>
                                <td class="sticky left-[26rem] z-10 {{ $bgClass }} px-3 py-2 text-right text-xs font-bold text-slate-800 border-l border-slate-200 bg-yellow-50 border-r-2 border-slate-300">
                                    {{ formatNumber($stock) }}
                                </td>
                                @foreach ($months as $ym)
                                    @php($val = (float) ($monthly[$ym] ?? 0))
                                    <td class="px-2 py-2 text-right text-xs border-l border-slate-100 {{ $val > 0 ? 'font-bold text-slate-900' : 'text-slate-300' }}">
                                        {{ $val > 0 ? formatNumber($val) : '-' }}
                                    </td>
                                @endforeach
                                <td class="px-2 py-2 text-right text-xs font-bold border-l border-slate-200">
                                    {{ $total > 0 ? formatNumber($total) : '-' }}
                                </td>
                                @if($showPoAction)
                                    <td class="px-2 py-2 text-center border-l border-slate-200">
                                        @if($planned > 0)
                                            <input type="checkbox" name="items[{{ $part->id }}]" value="{{ $planned }}" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" title="Create PO for {{ $planned }} items">
                                        @else
                                            <span class="text-slate-300 text-xs">-</span>
                                        @endif
                                    </td>
                                @endif
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

