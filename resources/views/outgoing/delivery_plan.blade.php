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
                    <p class="mt-1 text-sm text-slate-600">Sheet view (by delivery class + sequence).</p>
                </div>

                <form method="GET" action="{{ route('outgoing.delivery-plan') }}" class="flex items-end gap-2">
                    <div>
                        <div class="text-xs font-semibold text-slate-500 mb-1">Date</div>
                        <input type="date" name="date" value="{{ $selectedDate }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">
                    </div>
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">View</button>
                </form>
            </div>
        </div>

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
                            <th colspan="{{ count($sequences) }}" class="px-2 py-3 text-center font-bold text-slate-700 border-r border-slate-200">Sequence</th>
                            <th rowspan="2" class="px-2 py-2 h-10 text-right font-bold text-slate-700 w-[90px] sticky-r right-[110px] border-l border-slate-200">Remain</th>
	                            <th rowspan="2" class="px-2 py-2 h-10 text-right font-bold text-slate-700 w-[110px] sticky-r right-0 border-l border-slate-200">Action</th>
                        </tr>
                        <tr>
                            @foreach($sequences as $seq)
                                <th class="px-2 py-1 h-8 text-center font-bold text-slate-700 border-r border-slate-200 w-10">{{ $seq }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @php($no = 0)
                        @forelse(($groups ?? []) as $class => $rows)
                            <tr class="bg-slate-100">
                                <td class="px-2 py-2 border-r border-slate-200 sticky-l left-0 bg-group"></td>
                                <td class="px-2 py-2 font-black text-slate-800 border-r border-slate-200 sticky-l left-12 bg-group" colspan="1">{{ $class }}</td>
	                                <td class="px-2 py-2 border-r border-slate-200 sticky-l left-[168px] bg-group" colspan="4"></td>
                                <td class="px-2 py-2 border-r border-slate-200 sticky-l left-[708px] bg-group"></td>
                                <td class="px-2 py-2 border-r border-slate-200 sticky-l left-[838px] bg-group"></td>
                                <td class="px-2 py-2 border-r border-slate-200 sticky-l left-[928px] bg-group"></td>
                                @foreach($sequences as $seq)
                                    <td class="px-2 py-2 border-r border-slate-200"></td>
                                @endforeach
                                <td class="px-2 py-2 sticky-r right-[110px] bg-group border-l border-slate-200"></td>
	                                <td class="px-2 py-2 sticky-r right-0 bg-group border-l border-slate-200"></td>
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
                                    <td class="px-2 py-2 border-r border-slate-200 text-right font-bold text-slate-900 sticky-l left-[838px]">{{ number_format((float) $r->balance, 0) }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-slate-700 sticky-l left-[928px]">{{ $r->due_date?->format('Y-m-d') }}</td>
                                    @foreach($sequences as $seq)
                                        @php($v = (float) (($r->per_seq[$seq] ?? 0) ?: 0))
                                        <td class="px-2 py-2 border-r border-slate-200 text-center {{ $v > 0 ? 'font-bold text-slate-900' : 'text-slate-400' }}">
                                            {{ $v > 0 ? number_format($v, 0) : '' }}
                                        </td>
                                    @endforeach
                                    <td class="px-2 py-2 text-right font-bold text-slate-900 sticky-r right-[110px] border-l border-slate-200">{{ number_format((float) $r->remain, 0) }}</td>
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
	                                <td colspan="{{ 11 + count($sequences) }}" class="px-6 py-12 text-center text-slate-500 italic">
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
