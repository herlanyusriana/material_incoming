@php
    /** @var array<int, array{part:\App\Models\GciPart, initial_stock:float|int, has_purchase?:bool, has_production?:bool, demand_total?:float|int, incoming_total?:float|int, planned_order_total?:float|int, end_stock?:float|int, net_required?:float|int, plan_id?:int|null, plan_status?:string|null, eta_week?:string|null, order_week?:string|null, safety_stock?:float|int, min_order_qty?:float|int, order_multiple?:float|int, lead_time_days?:int|null}> $mrpRows */
    $mrpRows = $mrpRows ?? [];
    $modeLabel = $modeLabel ?? 'MRP';
    $showPoAction = (bool) ($showPoAction ?? false);
    $showIncoming = (bool) ($showIncoming ?? true);
@endphp

<div class="flex items-center justify-between gap-3">
    <div class="text-sm font-semibold text-slate-900">{{ $modeLabel }}</div>
    @if($showPoAction)
        <div class="text-[11px] text-slate-500">
            Check a row then <span class="font-semibold text-emerald-700">Generate PO</span>
            (approved only) or <span class="font-semibold text-indigo-700">Approve</span> /
            <span class="font-semibold text-red-600">Reject</span> in batch.
        </div>
    @endif
</div>

@if(empty($mrpRows))
    <div class="rounded-xl border border-dashed border-slate-200 p-10 text-center text-slate-500">
        No data for this section.
    </div>
@else
    @if($showPoAction)
        <form action="{{ route('planning.mrp.generate-po') }}" method="POST" id="po-form-{{ \Illuminate\Support\Str::slug($modeLabel) }}">
            @csrf
            <div class="flex justify-end mb-2 gap-2">
                @can('approve_mrp')
                    <button formaction="{{ route('planning.mrp.approve') }}"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors">
                        Approve Selected
                    </button>
                    <button formaction="{{ route('planning.mrp.reject') }}"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg shadow-sm transition-colors">
                        Reject Selected
                    </button>
                @endcan
                <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-sm transition-colors">
                    Generate PO from Selection
                </button>
            </div>
    @endif

    <div class="overflow-x-auto border border-slate-300 rounded-xl shadow-sm">
        <div class="inline-block min-w-full align-middle">
            <div class="relative overflow-hidden">
                <table class="min-w-full divide-y divide-slate-300 border-collapse w-full">
                    <thead class="bg-indigo-900 text-white">
                        <tr>
                            <th scope="col" class="px-3 py-2 text-left text-xs font-bold uppercase w-10">No.</th>
                            <th scope="col" class="px-3 py-2 text-left text-xs font-bold uppercase w-44">Part No</th>
                            <th scope="col" class="px-3 py-2 text-left text-xs font-bold uppercase">Name / Spec</th>
                            <th scope="col" class="px-3 py-2 text-right text-xs font-bold uppercase w-20">Safety</th>
                            <th scope="col" class="px-3 py-2 text-right text-xs font-bold uppercase w-20">MOQ</th>
                            <th scope="col" class="px-3 py-2 text-center text-xs font-bold uppercase w-24">ETA Week</th>
                            <th scope="col" class="px-3 py-2 text-center text-xs font-bold uppercase w-24">Order Week</th>
                            <th scope="col" class="px-3 py-2 text-center text-xs font-bold uppercase w-24">Status</th>
                            <th scope="col" class="px-3 py-2 text-right text-xs font-bold uppercase w-28">Stock</th>
                            <th scope="col" class="px-3 py-2 text-right text-xs font-bold uppercase w-28">Demand</th>
                            @if($showIncoming)
                                <th scope="col" class="px-3 py-2 text-right text-xs font-bold uppercase w-28">Incoming</th>
                            @endif
                            <th scope="col" class="px-3 py-2 text-right text-xs font-bold uppercase w-32">Planned</th>
                            <th scope="col" class="px-3 py-2 text-right text-xs font-bold uppercase w-32">End Stock</th>
                            <th scope="col" class="px-3 py-2 text-right text-xs font-bold uppercase w-32">Net Req</th>
                            @if($showPoAction)
                                <th scope="col" class="px-3 py-2 text-center text-xs font-bold uppercase w-20">PO</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($mrpRows as $index => $row)
                            @php
                                $part = $row['part'];
                                $stock = (float) ($row['initial_stock'] ?? 0);
                                $demand = (float) ($row['demand_total'] ?? 0);
                                $incoming = (float) ($row['incoming_total'] ?? 0);
                                $planned = (float) ($row['planned_order_total'] ?? 0);
                                $endStock = (float) ($row['end_stock'] ?? ($stock + $incoming - $demand));
                                $netReq = (float) ($row['net_required'] ?? 0);
                                $safety = (float) ($row['safety_stock'] ?? 0);
                                $moq = (float) ($row['min_order_qty'] ?? 0);
                                $etaWeek = $row['eta_week'] ?? null;
                                $orderWeek = $row['order_week'] ?? null;
                                $planStatus = (string) ($row['plan_status'] ?? '');
                                $bgClass = $loop->even ? 'bg-slate-50' : 'bg-white';
                            @endphp

                            <tr class="{{ $bgClass }} hover:bg-slate-100">
                                <td class="px-3 py-2 text-xs text-center font-mono text-slate-500">{{ $index + 1 }}</td>
                                <td class="px-3 py-2 text-xs font-mono text-indigo-700 font-bold whitespace-nowrap">
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
                                <td class="px-3 py-2 text-[11px] text-slate-700">
                                    <div class="font-semibold">{{ $part->part_name ?? '-' }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $part->model ?? '-' }}</div>
                                </td>
                                <td class="px-3 py-2 text-right text-xs {{ $safety > 0 ? 'font-bold text-sky-700' : 'text-slate-400' }}">{{ $safety > 0 ? formatNumber($safety) : '-' }}</td>
                                <td class="px-3 py-2 text-right text-xs {{ $moq > 0 ? 'font-bold text-slate-700' : 'text-slate-400' }}">{{ $moq > 0 ? formatNumber($moq) : '-' }}</td>
                                <td class="px-3 py-2 text-center text-xs font-mono {{ $etaWeek ? 'text-slate-700' : 'text-slate-300' }}">{{ $etaWeek ?? '-' }}</td>
                                <td class="px-3 py-2 text-center text-xs font-mono {{ $orderWeek ? 'text-slate-700' : 'text-slate-300' }}">{{ $orderWeek ?? '-' }}</td>
                                <td class="px-3 py-2 text-center">
                                    @if($planStatus === 'approved')
                                        <span class="px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">APPROVED</span>
                                    @elseif($planStatus === 'rejected')
                                        <span class="px-1.5 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold">REJECTED</span>
                                    @elseif($planStatus === '')
                                        <span class="text-slate-300 text-xs">-</span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-bold uppercase">PENDING</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right text-xs font-bold text-slate-800 bg-yellow-50">{{ formatNumber($stock) }}</td>
                                <td class="px-3 py-2 text-right text-xs {{ $demand > 0 ? 'font-bold text-slate-900' : 'text-slate-400' }}">{{ $demand > 0 ? formatNumber($demand) : '-' }}</td>
                                @if($showIncoming)
                                    <td class="px-3 py-2 text-right text-xs {{ $incoming > 0 ? 'font-bold text-emerald-700 bg-emerald-50' : 'text-slate-400' }}">{{ $incoming > 0 ? formatNumber($incoming) : '-' }}</td>
                                @endif
                                <td class="px-3 py-2 text-right text-xs {{ $planned > 0 ? 'font-bold text-indigo-700' : 'text-slate-400' }}">{{ $planned > 0 ? formatNumber($planned) : '-' }}</td>
                                <td class="px-3 py-2 text-right text-xs {{ $endStock < 0 ? 'font-bold text-red-600 bg-red-50' : 'text-slate-700' }}">{{ formatNumber($endStock) }}</td>
                                <td class="px-3 py-2 text-right text-xs {{ $netReq > 0 ? 'font-bold text-red-600' : 'text-slate-400' }}">{{ $netReq > 0 ? formatNumber($netReq) : '-' }}</td>
                                @if($showPoAction)
                                    <td class="px-3 py-2 text-center">
                                        @if(!empty($row['plan_id']))
                                            <input type="checkbox" name="plan_ids[]" value="{{ $row['plan_id'] }}"
                                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                                title="Select for PO / approval">
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
