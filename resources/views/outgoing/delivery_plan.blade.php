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
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                <div class="text-sm font-bold text-slate-900">Summary by Line</div>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-xs divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-[11px] uppercase tracking-wider text-slate-600">
                                <th class="px-3 py-2 text-left font-bold">Line</th>
                                <th class="px-3 py-2 text-right font-bold">Balance</th>
                                <th class="px-3 py-2 text-right font-bold">UPH</th>
                                <th class="px-3 py-2 text-right font-bold">Hours</th>
                                <th class="px-3 py-2 text-right font-bold">JIG NR1</th>
                                <th class="px-3 py-2 text-right font-bold">JIG NR2</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($totalsByLine as $t)
                                <tr>
                                    <td class="px-3 py-2 font-bold text-slate-900">{{ $t->line_type ?? 'UNKNOWN' }}</td>
                                    <td class="px-3 py-2 text-right font-bold text-slate-900">{{ number_format((float) ($t->balance ?? 0), 0) }}</td>
                                    <td class="px-3 py-2 text-right text-slate-700">{{ number_format((float) ($t->uph ?? 0), 2) }}</td>
                                    <td class="px-3 py-2 text-right text-slate-700">{{ number_format((float) ($t->hours ?? 0), 2) }}</td>
                                    <td class="px-3 py-2 text-right text-slate-700">{{ (int) ($t->jig_nr1 ?? 0) }}</td>
                                    <td class="px-3 py-2 text-right text-slate-700">{{ (int) ($t->jig_nr2 ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
                                            JIG NR1: {{ (int) ($r->jig_nr1 ?? 0) }} • NR2: {{ (int) ($r->jig_nr2 ?? 0) }}
                                        </div>
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

	    <script>
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
	    </script>
@endsection
