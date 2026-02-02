@extends('outgoing.layout')

@section('content')
	<style>
		.dp-scroll {
			overflow: auto;
		}

		/* Keep headers visible while scrolling */
		.dp-table thead tr:first-child th {
			position: sticky;
			top: 0;
			z-index: 50;
			background: rgb(248 250 252); /* slate-50 */
		}
		.dp-table thead tr:nth-child(2) th {
			position: sticky;
			top: 40px; /* matches header row 1 height */
			z-index: 49;
			background: rgb(248 250 252); /* slate-50 */
		}

		/* Sticky left columns */
		.dp-table .sticky-l {
			position: sticky;
			z-index: 40;
			background: #fff;
		}
		.dp-table .sticky-l.bg-group {
			background: rgb(241 245 249); /* slate-100 */
		}

		/* Sticky right columns */
		.dp-table .sticky-r {
			position: sticky;
			z-index: 40;
			background: #fff;
		}

		/* Make sticky cells sit above non-sticky cells */
		.dp-table thead .sticky-l,
		.dp-table thead .sticky-r {
			z-index: 60;
		}
	</style>

    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
	            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
	                <div>
	                    <h1 class="text-2xl md:text-3xl font-black text-slate-900">Delivery Plan</h1>
	                    <p class="mt-1 text-sm text-slate-600">Sheet view (by delivery class + JIG / UPH).</p>
	                </div>

	                <form method="GET" action="{{ route('outgoing.delivery-plan') }}" class="flex flex-wrap items-end gap-2">
	                    <div>
	                        <div class="text-xs font-semibold text-slate-500 mb-1">Date</div>
	                        <input type="date" name="date" value="{{ $selectedDate }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">
	                    </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-500 mb-1">UPH NR1</div>
                            <input type="number" step="0.01" min="0" name="uph_nr1" value="{{ (float) ($uphNr1 ?? 60) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 w-28 text-right">
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-slate-500 mb-1">UPH NR2</div>
                            <input type="number" step="0.01" min="0" name="uph_nr2" value="{{ (float) ($uphNr2 ?? 60) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 w-28 text-right">
                        </div>
	                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">View</button>
	                </form>
	            </div>
	        </div>

        @if(!empty($totalsByLine) && count($totalsByLine) > 0)
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Summary by Line -->
                <div class="xl:col-span-1 bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm font-black text-slate-900 uppercase tracking-wider">Line Capacity Summary</div>
                        <div class="text-[10px] font-bold text-slate-400">TOTAL UNIT: {{ number_format($totalsByLine->sum('balance')) }}</div>
                    </div>
                    <div class="overflow-x-auto overflow-y-auto max-h-[300px]">
                        <table class="min-w-full text-xs divide-y divide-slate-200">
                            <thead class="bg-slate-50 sticky top-0">
                                <tr class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">
                                    <th class="px-3 py-2 text-left">Line</th>
                                    <th class="px-3 py-2 text-right">Balance</th>
                                    <th class="px-3 py-2 text-right">Hours</th>
                                    <th class="px-3 py-2 text-right">Jigs</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($totalsByLine as $t)
                                    <tr>
                                        <td class="px-3 py-2 font-bold text-slate-900">{{ $t->line_type ?? 'UNKNOWN' }}</td>
                                        <td class="px-3 py-2 text-right font-bold text-indigo-600">{{ number_format((float) ($t->balance ?? 0), 0) }}</td>
                                        <td class="px-3 py-2 text-right text-slate-600">{{ number_format((float) ($t->hours ?? 0), 1) }}h</td>
                                        <td class="px-3 py-2 text-right font-semibold text-slate-700">{{ (int) ($t->jigs ?? 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Trip Management -->
                <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm font-black text-slate-900 uppercase tracking-wider">Fleet & Trip Management</div>
                        <button onclick="document.getElementById('addTripModal').classList.remove('hidden')" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            ADD TRIP
                        </button>
                    </div>

                    @if($unassignedSalesOrders->isNotEmpty())
                        <div class="mb-6 p-4 bg-orange-50 border border-orange-100 rounded-xl">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="px-2 py-0.5 rounded bg-orange-200 text-orange-700 text-[10px] font-black uppercase">Unassigned Sales Orders</div>
                                <div class="text-[10px] text-orange-600 font-bold">{{ $unassignedSalesOrders->count() }} Orders Pending Trip Assignment</div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @foreach($unassignedSalesOrders as $so)
                                    <div class="bg-white border border-orange-200 rounded-lg p-3 shadow-sm flex flex-col gap-2 min-w-[200px]">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <div class="text-[11px] font-black text-slate-900">{{ $so->so_no }}</div>
                                                <div class="text-[10px] font-bold text-slate-500 uppercase">{{ $so->customer?->name }}</div>
                                            </div>
                                            <div class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">{{ $so->items->count() }} SKU</div>
                                        </div>
                                        <select onchange="assignSoToTrip({{ $so->id }}, this.value)" class="text-[10px] font-bold border-slate-200 rounded-lg w-full bg-slate-50 focus:ring-indigo-500">
                                            <option value="">Move to Trip...</option>
                                            @foreach($plans as $p)
                                                <option value="{{ $p->id }}">Trip #{{ $p->sequence }} ({{ $p->truck?->plate_no ?? 'No Truck' }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @forelse($plans as $plan)
                            <div class="rounded-xl border border-slate-200 p-4 hover:border-indigo-200 transition-colors bg-slate-50/50">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-black text-sm">
                                            #{{ $plan->sequence }}
                                        </div>
                                        <div>
                                            <div class="text-xs font-black text-slate-900">TRIP {{ $plan->sequence }}</div>
                                            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Status: {{ strtoupper($plan->status) }}</div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-1">
                                         <span class="text-[10px] font-bold text-slate-400">{{ $plan->salesOrders->count() }} SOs Assigned</span>
                                    </div>
                                </div>
                                <div class="mt-4 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
                                        <div class="text-xs font-semibold text-slate-700">{{ $plan->truck?->plate_no ?? 'No Truck' }} <span class="text-[10px] font-normal text-slate-400">({{ $plan->truck?->type ?? '-' }})</span></div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        <div class="text-xs font-semibold text-slate-700">{{ $plan->driver?->name ?? 'No Driver' }}</div>
                                    </div>
                                </div>
                                <div class="mt-4 border-t border-slate-100 pt-3">
                                    <div class="text-[10px] font-black text-slate-400 uppercase mb-2">Attached Orders</div>
                                    <div class="space-y-1.5">
                                        @foreach($plan->salesOrders as $so)
                                            <div class="flex items-center justify-between text-[11px] bg-white p-2 rounded border border-slate-100 shadow-sm">
                                                <div>
                                                    <span class="font-bold text-slate-700">{{ $so->customer?->name }}</span>
                                                    <span class="text-[10px] text-slate-400 ml-1">{{ $so->so_no }}</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <div class="text-indigo-600 font-bold">{{ number_format($so->items->sum('qty_ordered')) }}</div>
                                                    <button onclick="assignSoToTrip({{ $so->id }}, null)" class="text-slate-300 hover:text-red-500" title="Remove from Trip">✕</button>
                                                </div>
                                            </div>
                                        @endforeach
                                        @if($plan->salesOrders->isEmpty())
                                            <div class="text-[10px] italic text-slate-400 py-1 text-center">Empty trip. Assign SOs above.</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-slate-200 flex justify-end gap-2">
                                    <button onclick="openAssignResourcesModal({{ $plan->id }}, {{ $plan->truck_id ?? 'null' }}, {{ $plan->driver_id ?? 'null' }})" class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 bg-white border border-slate-200 px-3 py-1.5 rounded-lg shadow-sm">
                                        EDIT TRIP
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="md:col-span-2 py-8 text-center text-slate-400 italic text-xs bg-slate-50 rounded-xl border border-dashed border-slate-200">
                                No trips scheduled for this date. Click Add Trip to start.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

	        @if(($autoMappedRowCount ?? 0) > 0)
	            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
	                Auto-mapped <span class="font-bold">{{ (int) $autoMappedRowCount }}</span> row(s) ke FG berdasarkan Customer Part Mapping / Product Mapping.
	                <span class="text-emerald-800">Cek kembali mapping-nya kalau ada yang tidak sesuai.</span>
	            </div>
	        @endif

        @if(!empty($unmappedRows) && count($unmappedRows) > 0)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div class="text-sm text-amber-900">
                        Ada <span class="font-bold">{{ count($unmappedRows) }}</span> row yang <span class="font-bold">belum terdaftar / belum bisa di-mapping</span> ke FG (gci_part_id kosong), sehingga tidak masuk ke Delivery Plan utama.
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('planning.customer-parts.index') }}"
                           class="rounded-lg bg-white px-3 py-2 text-xs font-bold text-slate-700 border border-slate-200 hover:bg-slate-50">
                            Customer Part Mapping
                        </a>
                        <a href="{{ route('outgoing.product-mapping') }}"
                           class="rounded-lg bg-white px-3 py-2 text-xs font-bold text-slate-700 border border-slate-200 hover:bg-slate-50">
                            Product Mapping (Where-Used)
                        </a>
                    </div>
                </div>

                <div class="mt-3 overflow-x-auto rounded-xl border border-amber-200 bg-white">
                    <table class="min-w-full text-xs divide-y divide-amber-100">
                        <thead class="bg-amber-50">
                            <tr class="text-[11px] uppercase tracking-wider text-amber-900">
                                <th class="px-3 py-2 text-left font-bold w-16">Row</th>
                                <th class="px-3 py-2 text-left font-bold w-20">Line</th>
                                <th class="px-3 py-2 text-left font-bold w-44">Part No</th>
                                <th class="px-3 py-2 text-right font-bold w-24">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-amber-50">
                            @foreach($unmappedRows as $u)
                                <tr>
                                    <td class="px-3 py-2 text-slate-700 font-mono">{{ (int) ($u->row_id ?? 0) }}</td>
                                    <td class="px-3 py-2 text-slate-700 font-semibold">{{ $u->production_line ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-900 font-mono font-bold">{{ $u->part_no ?? '-' }}</td>
                                    <td class="px-3 py-2 text-right text-slate-900 font-bold">{{ number_format((float) ($u->total_qty ?? 0), 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="dp-scroll">
                <table class="dp-table min-w-max w-full text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th rowspan="2" class="px-2 py-2 h-10 text-left font-bold text-slate-700 border-r border-slate-200 w-12 sticky-l left-0">No</th>
                            <th rowspan="2" class="px-2 py-2 h-10 text-left font-bold text-slate-700 border-r border-slate-200 w-[120px] sticky-l left-12">Classification</th>
                            <th rowspan="2" class="px-2 py-2 h-10 text-left font-bold text-slate-700 border-r border-slate-200 w-[220px] sticky-l left-[168px]">Model</th>
                            <th rowspan="2" class="px-2 py-2 h-10 text-left font-bold text-slate-700 border-r border-slate-200 w-[140px] sticky-l left-[388px]">Part Number</th>
	                            <th rowspan="2" class="px-2 py-2 h-10 text-center font-bold text-slate-700 border-r border-slate-200 w-[90px] sticky-l left-[528px]">Std Pack</th>
                            <th rowspan="2" class="px-2 py-2 h-10 text-right font-bold text-slate-700 border-r border-slate-200 w-[90px] sticky-l left-[618px]">Plan</th>
                            <th rowspan="2" class="px-2 py-2 h-10 text-right font-bold text-slate-700 border-r border-slate-200 w-[130px] sticky-l left-[708px]">Stock at Customer</th>
	                            <th rowspan="2" class="px-2 py-2 h-10 text-right font-bold text-slate-700 border-r border-slate-200 w-[90px] sticky-l left-[838px]">Balance</th>
	                            <th rowspan="2" class="px-2 py-2 h-10 text-left font-bold text-slate-700 border-r border-slate-200 w-[110px] sticky-l left-[928px]">Duedate</th>
                                <th rowspan="2" class="px-2 py-2 h-10 text-left font-bold text-slate-700 border-r border-slate-200 w-[90px]">Line</th>
                                <th rowspan="2" class="px-2 py-2 h-10 text-right font-bold text-slate-700 border-r border-slate-200 w-[90px]">UPH</th>
                                <th rowspan="2" class="px-2 py-2 h-10 text-right font-bold text-slate-700 border-r border-slate-200 w-[90px]">Hours</th>
	                            <th rowspan="2" class="px-2 py-2 h-10 text-right font-bold text-slate-700 w-[90px] sticky-r right-[110px] border-l border-slate-200">JIG</th>
		                            <th rowspan="2" class="px-2 py-2 h-10 text-right font-bold text-slate-700 w-[110px] sticky-r right-0 border-l border-slate-200">Action</th>
	                        </tr>
	                    </thead>
	                    <tbody class="divide-y divide-slate-100">
	                        @php($no = 0)
	                        @forelse(($groups ?? []) as $class => $rows)
	                            <tr class="bg-slate-100">
	                                <td class="px-2 py-2 font-black text-slate-800 bg-group" colspan="14">{{ $class }}</td>
	                            </tr>
	                            @foreach($rows as $r)
	                                @php($no++)
	                                <tr class="hover:bg-slate-50">
                                    <td class="px-2 py-2 border-r border-slate-200 text-slate-600 font-semibold sticky-l left-0">{{ $no }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-slate-700 sticky-l left-12">{{ $r->delivery_class }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-slate-700 sticky-l left-[168px] font-semibold">
	                                    {{ $r->part_model ?: '-' }}
	                                </td>
                                    <td class="px-2 py-2 border-r border-slate-200 font-mono text-indigo-700 font-bold sticky-l left-[388px]">{{ $r->part_no }}</td>
	                                    <td class="px-2 py-2 border-r border-slate-200 text-center text-slate-700 font-semibold sticky-l left-[528px]">
	                                        @if(($r->std_pack_qty ?? null) !== null)
	                                            {{ (float) $r->std_pack_qty > 0 ? number_format((float) $r->std_pack_qty, 0) : '-' }}
	                                            <span class="text-[10px] text-slate-500">{{ $r->std_pack_uom ?? '' }}</span>
	                                            @if(!empty($r->trolley_type))
	                                                <div class="text-[10px] text-slate-500">Trolley: {{ $r->trolley_type }}</div>
	                                            @endif
	                                        @else
	                                            -
	                                        @endif
                                    </td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-right font-bold text-slate-900 sticky-l left-[618px]">{{ number_format((float) $r->plan_total, 0) }}</td>
	                                    <td class="px-2 py-2 border-r border-slate-200 text-right text-slate-700 sticky-l left-[708px]">{{ (float) $r->stock_at_customer > 0 ? number_format((float) $r->stock_at_customer, 0) : '-' }}</td>
	                                    <td class="px-2 py-2 border-r border-slate-200 text-right sticky-l left-[838px]">
	                                        <div class="font-bold text-slate-900">{{ number_format((float) $r->balance, 0) }}</div>
	                                        <div class="text-[10px] font-semibold text-slate-500">
	                                            JIG NR1: {{ (int) ($r->jig_nr1 ?? 0) }} (cap {{ (int) ($r->jig_capacity_nr1 ?? 10) }})
	                                            •
	                                            NR2: {{ (int) ($r->jig_nr2 ?? 0) }} (cap {{ (int) ($r->jig_capacity_nr2 ?? 9) }})
	                                        </div>
	                                        @if(!empty($r->has_overrides))
	                                            <div class="mt-1 inline-flex items-center rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-900">
	                                                OVERRIDE
	                                            </div>
	                                        @endif
	                                    </td>
	                                    <td class="px-2 py-2 border-r border-slate-200 text-slate-700 sticky-l left-[928px]">
	                                        <div>{{ $r->due_date?->format('Y-m-d') }}</div>
	                                        @if(!empty($r->auto_mapped))
	                                            <div class="mt-1 inline-flex items-center rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">
	                                                AUTO-MAPPED
	                                            </div>
	                                        @endif
	                                    </td>
                                        <td class="px-2 py-2 border-r border-slate-200 text-slate-700 font-bold">
                                            <div>{{ $r->line_type ?? 'UNKNOWN' }}</div>
                                            <div class="text-[10px] text-slate-500">{{ $r->production_lines ?? '-' }}</div>
                                        </td>
                                        <td class="px-2 py-2 border-r border-slate-200 text-right text-slate-700">{{ number_format((float) ($r->uph ?? 0), 2) }}</td>
                                        <td class="px-2 py-2 border-r border-slate-200 text-right text-slate-700">{{ number_format((float) ($r->hours ?? 0), 2) }}</td>
	                                    <td class="px-2 py-2 text-right font-bold text-slate-900 sticky-r right-[110px] border-l border-slate-200">{{ (int) ($r->jigs ?? 0) }}</td>
			                                    <td class="px-2 py-2 text-right sticky-r right-0 border-l border-slate-200">
			                                        <div class="flex flex-col gap-2 items-end">
				                                        <button
			                                            type="button"
			                                            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 hover:bg-slate-50 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
			                                            onclick="openOverrideModal(this)"
			                                            data-date="{{ $selectedDate }}"
			                                            data-gci-part-id="{{ (int) ($r->gci_part_id ?? 0) }}"
			                                            data-part-no="{{ $r->part_no }}"
			                                            data-part-name="{{ $r->part_name }}"
			                                            data-line-type="{{ $r->line_type ?? 'UNKNOWN' }}"
			                                            data-cap-nr1="{{ (int) ($r->jig_capacity_nr1 ?? 10) }}"
			                                            data-cap-nr2="{{ (int) ($r->jig_capacity_nr2 ?? 9) }}"
			                                            data-uph-nr1="{{ (float) ($r->uph_nr1 ?? 0) }}"
			                                            data-uph-nr2="{{ (float) ($r->uph_nr2 ?? 0) }}"
			                                            data-ov-line-type="{{ (string) ($r->assignment?->line_type_override ?? '') }}"
			                                            data-ov-cap-nr1="{{ (string) ($r->assignment?->jig_capacity_nr1_override ?? '') }}"
			                                            data-ov-cap-nr2="{{ (string) ($r->assignment?->jig_capacity_nr2_override ?? '') }}"
			                                            data-ov-uph-nr1="{{ (string) ($r->assignment?->uph_nr1_override ?? '') }}"
			                                            data-ov-uph-nr2="{{ (string) ($r->assignment?->uph_nr2_override ?? '') }}"
			                                            data-ov-notes="{{ (string) ($r->assignment?->notes ?? '') }}"
			                                            @disabled(((int) ($r->gci_part_id ?? 0)) <= 0)
			                                        >
			                                            Edit
			                                        </button>
				                                        <button
			                                            type="button"
			                                            class="rounded-lg bg-indigo-600 px-3 py-2 text-[11px] font-bold text-white hover:bg-indigo-700 disabled:bg-slate-300 disabled:cursor-not-allowed"
			                                            onclick="openCreateSoModal(this)"
			                                            data-date="{{ $selectedDate }}"
			                                            data-customer-id="{{ (int) ($r->customer_id ?? 0) }}"
			                                            data-gci-part-id="{{ (int) ($r->gci_part_id ?? 0) }}"
			                                            data-part-no="{{ $r->part_no }}"
			                                            data-part-name="{{ $r->part_name }}"
			                                            data-balance="{{ (float) ($r->balance ?? 0) }}"
			                                            @disabled(((int) ($r->customer_id ?? 0)) <= 0 || ((int) ($r->gci_part_id ?? 0)) <= 0)
			                                        >
			                                            Create SO
			                                        </button>
			                                        </div>
			                                    </td>
			                                </tr>
		                            @endforeach
		                        @empty
		                            <tr>
		                                <td colspan="14" class="px-6 py-12 text-center text-slate-500 italic">
		                                    No data for selected date.
		                                </td>
		                            </tr>
		                        @endforelse
	                    </tbody>
	                </table>
	            </div>
	        </div>
	    </div>

	    <div id="createSoModal" class="hidden fixed inset-0 z-50">
	        <div class="absolute inset-0 bg-slate-900/40" onclick="closeCreateSoModal()"></div>
	        <div class="absolute inset-x-0 top-20 mx-auto w-full max-w-lg px-4">
	            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
	                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
	                    <div class="font-black text-slate-900">Create Sales Order</div>
	                    <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeCreateSoModal()">✕</button>
	                </div>
	                <form id="createSoForm" method="POST" action="{{ route('outgoing.generate-so') }}" class="p-6 space-y-4">
	                    @csrf
	                    <input type="hidden" name="date" id="so_date">
	                    <input type="hidden" name="customer_id" id="so_customer_id">
	                    <input type="hidden" name="items[0][gci_part_id]" id="so_gci_part_id">

	                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
	                        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Item</div>
	                        <div class="mt-1 text-sm font-semibold text-slate-900">
	                            <span class="font-mono" id="so_part_no"></span>
	                            <span class="text-slate-400">•</span>
	                            <span id="so_part_name"></span>
	                        </div>
	                        <div class="mt-2 text-xs text-slate-600">
	                            Default qty = Balance (<span class="font-bold" id="so_balance"></span>)
	                        </div>
	                    </div>

	                    <div>
	                        <div class="text-xs font-semibold text-slate-500 mb-1">Qty (adjustable)</div>
	                        <input type="number" step="0.0001" min="0.0001" name="items[0][qty]" id="so_qty" class="w-full rounded-lg border-slate-300 text-sm text-right" required>
	                    </div>

	                    <div class="flex justify-end gap-2 pt-2">
	                        <button type="button" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50" onclick="closeCreateSoModal()">Cancel</button>
	                        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">Create</button>
	                    </div>
	                </form>
	            </div>
	        </div>
	    </div>

	    <div id="overrideModal" class="hidden fixed inset-0 z-50">
	        <div class="absolute inset-0 bg-slate-900/40" onclick="closeOverrideModal()"></div>
	        <div class="absolute inset-x-0 top-12 mx-auto w-full max-w-xl px-4">
	            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
	                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
	                    <div>
	                        <div class="font-black text-slate-900">Edit Delivery Plan Row</div>
	                        <div class="mt-1 text-xs text-slate-600">
	                            <span class="font-mono font-bold" id="ov_part_no"></span>
	                            <span class="text-slate-400">•</span>
	                            <span id="ov_part_name"></span>
	                        </div>
	                    </div>
	                    <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeOverrideModal()">✕</button>
	                </div>

	                <form id="overrideForm" method="POST" action="{{ route('outgoing.delivery-plan.update-overrides') }}" class="p-6 space-y-4">
	                    @csrf
	                    <input type="hidden" name="plan_date" id="ov_date">
	                    <input type="hidden" name="gci_part_id" id="ov_gci_part_id">

	                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700">
	                        <div class="font-bold text-slate-900">Current</div>
	                        <div class="mt-1" id="ov_current_summary"></div>
	                    </div>

	                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
	                        <div>
	                            <div class="text-xs font-semibold text-slate-500 mb-1">Line Type Override</div>
	                            <select name="line_type_override" id="ov_line_type" class="w-full rounded-lg border-slate-300 text-sm">
	                                <option value="">(Auto)</option>
	                                <option value="NR1">NR1</option>
	                                <option value="NR2">NR2</option>
	                                <option value="MIX">MIX</option>
	                                <option value="UNKNOWN">UNKNOWN</option>
	                            </select>
	                        </div>

	                        <div>
	                            <div class="text-xs font-semibold text-slate-500 mb-1">Notes</div>
	                            <input type="text" maxlength="255" name="notes" id="ov_notes" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Optional note">
	                        </div>
	                    </div>

	                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
	                        <div class="rounded-xl border border-slate-200 p-4">
	                            <div class="text-xs font-bold text-slate-700">NR1</div>
	                            <div class="mt-3 grid grid-cols-2 gap-3">
	                                <div>
	                                    <div class="text-xs font-semibold text-slate-500 mb-1">JIG Capacity</div>
	                                    <input type="number" min="1" step="1" name="jig_capacity_nr1_override" id="ov_cap_nr1" class="w-full rounded-lg border-slate-300 text-sm text-right" placeholder="(Auto)">
	                                </div>
	                                <div>
	                                    <div class="text-xs font-semibold text-slate-500 mb-1">UPH</div>
	                                    <input type="number" min="0.01" step="0.01" name="uph_nr1_override" id="ov_uph_nr1" class="w-full rounded-lg border-slate-300 text-sm text-right" placeholder="(Auto)">
	                                </div>
	                            </div>
	                        </div>

	                        <div class="rounded-xl border border-slate-200 p-4">
	                            <div class="text-xs font-bold text-slate-700">NR2</div>
	                            <div class="mt-3 grid grid-cols-2 gap-3">
	                                <div>
	                                    <div class="text-xs font-semibold text-slate-500 mb-1">JIG Capacity</div>
	                                    <input type="number" min="1" step="1" name="jig_capacity_nr2_override" id="ov_cap_nr2" class="w-full rounded-lg border-slate-300 text-sm text-right" placeholder="(Auto)">
	                                </div>
	                                <div>
	                                    <div class="text-xs font-semibold text-slate-500 mb-1">UPH</div>
	                                    <input type="number" min="0.01" step="0.01" name="uph_nr2_override" id="ov_uph_nr2" class="w-full rounded-lg border-slate-300 text-sm text-right" placeholder="(Auto)">
	                                </div>
	                            </div>
	                        </div>
	                    </div>

	                    <div class="flex items-center justify-between gap-2 pt-2">
	                        <button type="button" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50" onclick="resetOverrideForm()">Reset Overrides</button>
	                        <div class="flex gap-2">
	                            <button type="button" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50" onclick="closeOverrideModal()">Cancel</button>
	                            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">Save</button>
	                        </div>
	                    </div>
	                </form>
	            </div>
	        </div>
	    </div>

	    <div id="addTripModal" class="hidden fixed inset-0 z-50">
	        <div class="absolute inset-0 bg-slate-900/40" onclick="document.getElementById('addTripModal').classList.add('hidden')"></div>
	        <div class="absolute inset-x-0 top-20 mx-auto w-full max-w-lg px-4">
	            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
	                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
	                    <div class="font-black text-slate-900 uppercase">Add New Trip</div>
	                    <button type="button" class="text-slate-500 hover:text-slate-900" onclick="document.getElementById('addTripModal').classList.add('hidden')">✕</button>
	                </div>
	                <form method="POST" action="{{ route('outgoing.delivery-plan.store') }}" class="p-6 space-y-4">
	                    @csrf
	                    <input type="hidden" name="plan_date" value="{{ $selectedDate }}">
	                    
	                    <div>
	                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Select Truck</label>
	                        <select name="truck_id" class="w-full rounded-xl border-slate-200 text-sm">
	                            <option value="">-- Choose Truck --</option>
	                            @foreach($trucks as $truck)
	                                <option value="{{ $truck->id }}">{{ $truck->plate_no }} ({{ $truck->type }})</option>
	                            @endforeach
	                        </select>
	                    </div>

	                    <div>
	                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Select Driver</label>
	                        <select name="driver_id" class="w-full rounded-xl border-slate-200 text-sm">
	                            <option value="">-- Choose Driver --</option>
	                            @foreach($drivers as $driver)
	                                <option value="{{ $driver->id }}">{{ $driver->name }}</option>
	                            @endforeach
	                        </select>
	                    </div>

	                    <div class="flex justify-end gap-2 pt-2">
	                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700" onclick="document.getElementById('addTripModal').classList.add('hidden')">Cancel</button>
	                        <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-slate-200">Create Trip</button>
	                    </div>
	                </form>
	            </div>
	        </div>
	    </div>

	    <div id="assignResourcesModal" class="hidden fixed inset-0 z-50">
	        <div class="absolute inset-0 bg-slate-900/40" onclick="document.getElementById('assignResourcesModal').classList.add('hidden')"></div>
	        <div class="absolute inset-x-0 top-20 mx-auto w-full max-w-lg px-4">
	            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
	                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
	                    <div class="font-black text-slate-900 uppercase">Edit Trip Resources</div>
	                    <button type="button" class="text-slate-500 hover:text-slate-900" onclick="document.getElementById('assignResourcesModal').classList.add('hidden')">✕</button>
	                </div>
	                <form id="assignResourcesForm" method="POST" action="" class="p-6 space-y-4">
	                    @csrf
	                    @method('POST')
	                    
	                    <div>
	                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Update Truck</label>
	                        <select name="truck_id" id="ar_truck_id" class="w-full rounded-xl border-slate-200 text-sm">
	                            <option value="">-- Choose Truck --</option>
	                            @foreach($trucks as $truck)
	                                <option value="{{ $truck->id }}">{{ $truck->plate_no }} ({{ $truck->type }})</option>
	                            @endforeach
	                        </select>
	                    </div>

	                    <div>
	                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Update Driver</label>
	                        <select name="driver_id" id="ar_driver_id" class="w-full rounded-xl border-slate-200 text-sm">
	                            <option value="">-- Choose Driver --</option>
	                            @foreach($drivers as $driver)
	                                <option value="{{ $driver->id }}">{{ $driver->name }}</option>
	                            @endforeach
	                        </select>
	                    </div>

	                    <div class="flex justify-end gap-2 pt-2">
	                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700" onclick="document.getElementById('assignResourcesModal').classList.add('hidden')">Cancel</button>
	                        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-lg shadow-indigo-100">Save Changes</button>
	                    </div>
	                </form>
	            </div>
	        </div>
	    </div>

	    <script>
            function assignSoToTrip(soId, planId) {
                if (planId === "") return;
                
                fetch('{{ route("outgoing.delivery-plan.assign-so") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        sales_order_id: soId,
                        delivery_plan_id: planId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error assigning SO: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to assign SO. Please try again.');
                });
            }

	        function openAssignResourcesModal(planId, currentTruckId, currentDriverId) {
	            const form = document.getElementById('assignResourcesForm');
	            form.action = `/outgoing/delivery-plan/${planId}/assign-resources`;
	            
	            document.getElementById('ar_truck_id').value = currentTruckId || '';
	            document.getElementById('ar_driver_id').value = currentDriverId || '';
	            
	            document.getElementById('assignResourcesModal').classList.remove('hidden');
	        }

	        function openCreateSoModal(btn) {
	            const date = btn.dataset.date || '';
	            const customerId = btn.dataset.customerId || '';
	            const gciPartId = btn.dataset.gciPartId || '';
	            const partNo = btn.dataset.partNo || '';
	            const partName = btn.dataset.partName || '';
	            const balance = Number(btn.dataset.balance || 0);

	            document.getElementById('so_date').value = date;
	            document.getElementById('so_customer_id').value = customerId;
	            document.getElementById('so_gci_part_id').value = gciPartId;
	            document.getElementById('so_part_no').textContent = partNo;
	            document.getElementById('so_part_name').textContent = partName;
	            document.getElementById('so_balance').textContent = String(Math.round(balance));

	            const qtyEl = document.getElementById('so_qty');
	            qtyEl.value = balance > 0 ? balance : '';
	            document.getElementById('createSoModal').classList.remove('hidden');
	            setTimeout(() => qtyEl.focus(), 0);
	        }

	        function closeCreateSoModal() {
	            document.getElementById('createSoModal').classList.add('hidden');
	        }

	        function openOverrideModal(btn) {
	            const date = btn.dataset.date || '';
	            const gciPartId = btn.dataset.gciPartId || '';
	            const partNo = btn.dataset.partNo || '';
	            const partName = btn.dataset.partName || '';

	            const lineType = btn.dataset.lineType || 'UNKNOWN';
	            const capNr1 = btn.dataset.capNr1 || '10';
	            const capNr2 = btn.dataset.capNr2 || '9';
	            const uphNr1 = btn.dataset.uphNr1 || '';
	            const uphNr2 = btn.dataset.uphNr2 || '';

	            const ovLineType = btn.dataset.ovLineType || '';
	            const ovCapNr1 = btn.dataset.ovCapNr1 || '';
	            const ovCapNr2 = btn.dataset.ovCapNr2 || '';
	            const ovUphNr1 = btn.dataset.ovUphNr1 || '';
	            const ovUphNr2 = btn.dataset.ovUphNr2 || '';
	            const ovNotes = btn.dataset.ovNotes || '';

	            document.getElementById('ov_date').value = date;
	            document.getElementById('ov_gci_part_id').value = gciPartId;
	            document.getElementById('ov_part_no').textContent = partNo;
	            document.getElementById('ov_part_name').textContent = partName;

	            document.getElementById('ov_current_summary').textContent =
	                `Line: ${lineType} | Cap NR1: ${capNr1} | Cap NR2: ${capNr2} | UPH NR1: ${uphNr1} | UPH NR2: ${uphNr2}`;

	            document.getElementById('ov_line_type').value = ovLineType;
	            document.getElementById('ov_cap_nr1').value = ovCapNr1;
	            document.getElementById('ov_cap_nr2').value = ovCapNr2;
	            document.getElementById('ov_uph_nr1').value = ovUphNr1;
	            document.getElementById('ov_uph_nr2').value = ovUphNr2;
	            document.getElementById('ov_notes').value = ovNotes;

	            document.getElementById('overrideModal').classList.remove('hidden');
	            setTimeout(() => document.getElementById('ov_line_type').focus(), 0);
	        }

	        function closeOverrideModal() {
	            document.getElementById('overrideModal').classList.add('hidden');
	        }

	        function resetOverrideForm() {
	            document.getElementById('ov_line_type').value = '';
	            document.getElementById('ov_cap_nr1').value = '';
	            document.getElementById('ov_cap_nr2').value = '';
	            document.getElementById('ov_uph_nr1').value = '';
	            document.getElementById('ov_uph_nr2').value = '';
	            document.getElementById('ov_notes').value = '';
	        }
	    </script>
@endsection
