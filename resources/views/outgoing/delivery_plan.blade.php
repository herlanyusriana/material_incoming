@extends('outgoing.layout')

@section('content')
	<style>
		.dp-scroll {
			overflow: auto;
			max-height: 80vh;
		}

		.dp-table {
			border-collapse: separate;
			border-spacing: 0;
		}

		.dp-table th,
		.dp-table td {
			border-bottom: 1px solid #e2e8f0;
			border-right: 1px solid #f1f5f9;
		}

		.dp-table thead th {
			position: sticky;
			top: 0;
			z-index: 20;
			background: #f8fafc;
			color: #64748b;
			font-size: 10px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.04em;
		}

		/* Sticky left columns */
		.dp-table .s-col {
			position: sticky;
			z-index: 30;
			background: inherit;
		}

		.dp-table thead .s-col {
			z-index: 40;
			background: #f8fafc !important;
		}

		/* Jig sub-row */
		.jig-sub {
			background: #fefce8;
			border-left: 3px solid #fbbf24;
		}

		.jig-sub td {
			border-bottom: 1px solid #fef3c7 !important;
		}

		/* FG row highlight */
		.fg-row {
			background: #ffffff;
			border-left: 3px solid #6366f1;
		}

		.fg-row:hover {
			background: #f8fafc;
		}

		/* Trip input cells */
		.trip-cell input {
			width: 100%;
			border: 0;
			background: transparent;
			text-align: center;
			font-weight: 600;
			color: #334155;
			padding: 6px 2px;
			font-size: 12px;
		}

		.trip-cell input:focus {
			outline: none;
			background: #eef2ff;
			box-shadow: inset 0 0 0 2px #6366f1;
			border-radius: 4px;
		}

		.trip-cell {
			background: #f0fdf4;
		}

		/* H+1 columns */
		.h1-col {
			background: #eff6ff !important;
		}

		/* Calculated columns */
		.calc-col {
			background: #faf5ff !important;
		}

		@media (max-width: 768px) {
			.dp-table .s-col {
				position: static !important;
				z-index: auto !important;
			}
		}
	</style>

	<div class="space-y-6">
		{{-- Header Card --}}
		<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
			<div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
				<div class="flex items-start gap-3">
					<div
						class="h-12 w-12 rounded-xl bg-slate-900 flex items-center justify-center text-white font-black text-sm">
						DP
					</div>
					<div>
						<div class="text-2xl md:text-3xl font-black text-slate-900">Delivery Planning</div>
						<div class="mt-1 text-sm text-slate-600">
							Delivery Date: <span
								class="font-bold text-slate-900">{{ $selectedDate->format('d M Y') }}</span>
							<span class="text-slate-400 mx-1">•</span>
							<span class="text-xs text-slate-500">{{ $selectedDate->translatedFormat('l') }}</span>
							@if($rows->isNotEmpty())
								<span
									class="ml-2 px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-[10px] font-bold uppercase tracking-wider border border-indigo-100">
									{{ $rows->count() }} FG Parts
								</span>
							@endif
						</div>
					</div>
				</div>

				<form method="GET" action="{{ route('outgoing.delivery-plan') }}" class="flex flex-wrap items-end gap-2">
					<div>
						<div class="text-xs font-semibold text-slate-500 mb-1">Delivery Date</div>
						<input type="date" name="date" value="{{ $selectedDate->toDateString() }}"
							class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">
					</div>
					<button
						class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">View</button>
				</form>
			</div>
		</div>

		@if($rows->isEmpty())
			<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
				<div class="text-slate-400 text-sm italic">No FG parts with daily plan data for this date.</div>
				<div class="mt-2 text-xs text-slate-400">Import a Daily Plan or select a different date.</div>
			</div>
		@else
			{{-- Main Table Card --}}
			<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
				<div class="dp-scroll">
					<table class="dp-table min-w-max w-full text-xs">
						<thead>
							<tr>
								{{-- Frozen left columns --}}
								<th class="px-2 py-3 text-left w-16 s-col" style="left:0">No</th>
								<th class="px-2 py-3 text-left w-20 s-col" style="left:64px">Category</th>
								<th class="px-2 py-3 text-left w-44 s-col" style="left:144px">FG Part Name</th>
								<th class="px-2 py-3 text-left w-36 s-col" style="left:320px">FG Part No.</th>
								<th class="px-2 py-3 text-left w-28 s-col" style="left:464px">Model</th>

								{{-- Data columns --}}
								<th class="px-2 py-3 text-right w-24">Stock at<br>Customer</th>
								<th class="px-2 py-3 text-right w-24">Daily Plan<br>H</th>
								<th class="px-2 py-3 text-left w-32 bg-yellow-50 !text-amber-700">Jig Name</th>
								<th class="px-2 py-3 text-center w-16 bg-yellow-50 !text-amber-700">Jig<br>Qty H</th>
								<th class="px-2 py-3 text-center w-16 bg-yellow-50 !text-amber-700">UpH</th>
								<th class="px-2 py-3 text-right w-24">Delivery<br>Req.</th>
								<th class="px-2 py-3 text-center w-20">Std<br>Packing</th>

								{{-- Trip columns (1-14) --}}
								@for($t = 1; $t <= 14; $t++)
									<th class="px-1 py-3 text-center w-16" style="background:#dcfce7; color:#166534;">{{ $t }}</th>
								@endfor

								<th class="px-2 py-3 text-right w-20 calc-col">Total</th>
								<th class="px-2 py-3 text-right w-20 calc-col">Finish<br>Time</th>
								<th class="px-2 py-3 text-right w-24 calc-col">End Stock<br>@Cust</th>

								{{-- H+1 --}}
								<th class="px-2 py-3 text-right w-24 h1-col">Daily Plan<br>H+1</th>
								<th class="px-2 py-3 text-center w-20 h1-col">Jig Qty<br>H+1</th>
								<th class="px-2 py-3 text-right w-20 h1-col">Est. Finish<br>Time</th>
							</tr>
						</thead>
						<tbody>
							@php $no = 0; @endphp
							@foreach($rows as $row)
								@php
									$no++;
									$jigCount = count($row->jigs);
									$rowSpan = max(1, $jigCount);
								@endphp

								{{-- First row: FG Part data + first jig (if any) --}}
								<tr class="fg-row" data-part-id="{{ $row->gci_part_id }}">
									<td class="px-2 py-2 text-slate-500 font-semibold s-col" style="left:0" @if($rowSpan > 1)
									rowspan="{{ $rowSpan }}" @endif>
										{{ $no }}
									</td>
									<td class="px-2 py-2 text-slate-600 text-[10px] font-bold s-col" style="left:64px" @if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
										{{ $row->category }}
									</td>
									<td class="px-2 py-2 text-slate-900 font-bold whitespace-nowrap s-col" style="left:144px"
										@if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
										{{ $row->fg_part_name }}
									</td>
									<td class="px-2 py-2 text-indigo-700 font-mono font-bold whitespace-nowrap s-col"
										style="left:320px" @if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
										{{ $row->fg_part_no }}
									</td>
									<td class="px-2 py-2 text-slate-600 whitespace-nowrap s-col" style="left:464px" @if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
										{{ $row->model }}
									</td>
									<td class="px-2 py-2 text-right text-slate-700 font-semibold"
										id="stock-val-{{ $row->gci_part_id }}" @if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
										{{ $row->stock_at_customer > 0 ? number_format($row->stock_at_customer) : '-' }}
									</td>
									<td class="px-2 py-2 text-right text-slate-900 font-bold" id="plan-val-{{ $row->gci_part_id }}"
										@if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
										{{ $row->daily_plan_qty > 0 ? number_format($row->daily_plan_qty) : '-' }}
									</td>

									{{-- First jig row --}}
									@if($jigCount > 0)
										<td class="px-2 py-2 bg-yellow-50 font-semibold text-amber-800">{{ $row->jigs[0]->jig_name }}
										</td>
										<td class="px-1 py-2 bg-yellow-50 text-center font-bold text-amber-900">
											{{ $row->jigs[0]->jig_qty ?: '-' }}
										</td>
										<td class="px-1 py-2 bg-yellow-50 text-center font-semibold text-amber-700">
											{{ $row->jigs[0]->uph ?: '-' }}
											<span class="hidden" id="prod-rate-{{ $row->gci_part_id }}">{{ $row->production_rate }}</span>
											<span class="hidden" id="del-req-{{ $row->gci_part_id }}">{{ $row->delivery_requirement }}</span>
											<span class="hidden" id="prod-rate-h1-{{ $row->gci_part_id }}">{{ $row->production_rate_h1 }}</span>
											<span class="hidden" id="plan-h1-{{ $row->gci_part_id }}">{{ $row->daily_plan_h1 }}</span>
										</td>
									@else
										<td class="px-2 py-2 bg-yellow-50 text-slate-300 italic">-</td>
										<td class="px-1 py-2 bg-yellow-50 text-center text-slate-300">-</td>
										<td class="px-1 py-2 bg-yellow-50 text-center text-slate-300">-</td>
									@endif

									<td class="px-2 py-2 text-right font-bold text-slate-900" @if($rowSpan > 1)
									rowspan="{{ $rowSpan }}" @endif>
										{{ $row->delivery_requirement > 0 ? number_format($row->delivery_requirement) : '-' }}
									</td>
									<td class="px-2 py-2 text-center text-slate-700 font-semibold" @if($rowSpan > 1)
									rowspan="{{ $rowSpan }}" @endif>
										{{ $row->std_packing > 0 ? number_format($row->std_packing) : '-' }}
									</td>

									{{-- Trip inputs --}}
									@for($t = 1; $t <= 14; $t++)
										<td class="p-0 trip-cell" @if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
											<input type="number" min="0" value="{{ $row->trips[$t] ?: '' }}" placeholder=""
												data-part="{{ $row->gci_part_id }}" data-trip="{{ $t }}" onchange="updateTrip(this)">
										</td>
									@endfor

									<td class="px-2 py-2 text-right font-black text-slate-900 calc-col"
										id="total-{{ $row->gci_part_id }}" @if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
										{{ $row->total_trips > 0 ? number_format($row->total_trips) : '-' }}
									</td>
									<td class="px-2 py-2 text-right font-semibold text-slate-700 calc-col"
										id="finish-{{ $row->gci_part_id }}" @if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
										{{ $row->finish_time !== null ? number_format($row->finish_time, 2) : '-' }}
									</td>
									<td class="px-2 py-2 text-right font-bold calc-col" id="endstock-{{ $row->gci_part_id }}"
										@if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
										@if($row->end_stock < 0)
											<span class="text-red-600">{{ number_format($row->end_stock) }}</span>
										@else
											<span class="text-slate-900">{{ number_format($row->end_stock) }}</span>
										@endif
									</td>

									{{-- H+1 --}}
									<td class="px-2 py-2 text-right font-bold text-blue-800 h1-col" @if($rowSpan > 1)
									rowspan="{{ $rowSpan }}" @endif>
										{{ $row->daily_plan_h1 > 0 ? number_format($row->daily_plan_h1) : '-' }}
									</td>
									<td class="px-2 py-2 text-center font-semibold text-blue-700 h1-col" @if($rowSpan > 1)
									rowspan="{{ $rowSpan }}" @endif>
										{{ $row->jig_qty_h1 > 0 ? $row->jig_qty_h1 : '-' }}
									</td>
									<td class="px-2 py-2 text-right font-semibold text-blue-700 h1-col"
										id="estfinish-{{ $row->gci_part_id }}" @if($rowSpan > 1)
									rowspan="{{ $rowSpan }}" @endif>
										{{ $row->est_finish_time !== null ? number_format($row->est_finish_time, 2) : '-' }}
									</td>
								</tr>

								{{-- Additional jig rows (sub-rows) --}}
								@for($j = 1; $j < $jigCount; $j++)
									<tr class="jig-sub">
										{{-- No / Category / Name / PartNo / Model / Stock / Plan cols are row-spanned --}}
										<td class="px-2 py-2 bg-yellow-50 font-semibold text-amber-800">{{ $row->jigs[$j]->jig_name }}
										</td>
										<td class="px-1 py-2 bg-yellow-50 text-center font-bold text-amber-900">
											{{ $row->jigs[$j]->jig_qty ?: '-' }}
										</td>
										<td class="px-1 py-2 bg-yellow-50 text-center font-semibold text-amber-700">
											{{ $row->jigs[$j]->uph ?: '-' }}
										</td>
										{{-- DeliveryReq, StdPack, Trips, Total, etc. are row-spanned --}}
									</tr>
								@endfor
							@endforeach
						</tbody>
					</table>
				</div>

				{{-- Footer --}}
				<div class="border-t border-slate-200 p-4 bg-slate-50">
					<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
						<div class="text-sm text-slate-500">
							<span class="font-semibold">Tip:</span> Kolom hijau (1-14) = truck sequence input manual.
							Kolom kuning = data JIG dari Input JIG. Kolom ungu = kalkulasi otomatis.
						</div>
						<div class="flex items-center gap-3">
							<span class="inline-flex items-center gap-1.5 text-[10px] font-bold">
								<span class="w-3 h-3 rounded bg-green-100 border border-green-300"></span> Input Trip
							</span>
							<span class="inline-flex items-center gap-1.5 text-[10px] font-bold">
								<span class="w-3 h-3 rounded bg-yellow-100 border border-yellow-300"></span> JIG Data
							</span>
							<span class="inline-flex items-center gap-1.5 text-[10px] font-bold">
								<span class="w-3 h-3 rounded bg-purple-100 border border-purple-300"></span> Calculated
							</span>
							<span class="inline-flex items-center gap-1.5 text-[10px] font-bold">
								<span class="w-3 h-3 rounded bg-blue-100 border border-blue-300"></span> H+1
							</span>
						</div>
					</div>
				</div>
			</div>
		@endif
	</div>

	<script>
		const CSRF_TOKEN = '{{ csrf_token() }}';
		const UPDATE_URL = '{{ route("outgoing.delivery-plan.update-trip") }}';
		const DELIVERY_DATE = '{{ $selectedDate->toDateString() }}';

		async function updateTrip(input) {
			const partId = input.dataset.part;
			const tripNo = input.dataset.trip;
			const qty = parseInt(input.value) || 0;

			input.style.background = '#fef9c3'; // yellow flash

			try {
				const response = await fetch(UPDATE_URL, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-CSRF-TOKEN': CSRF_TOKEN,
						'Accept': 'application/json'
					},
					body: JSON.stringify({
						delivery_date: DELIVERY_DATE,
						gci_part_id: partId,
						trip_no: tripNo,
						qty: qty
					})
				});

				const data = await response.json();

				if (data.success) {
					input.style.background = '#dcfce7'; // green success

					// Recalculate total from all trip inputs for this part
					recalcRow(partId);

					setTimeout(() => { input.style.background = 'transparent'; }, 800);
				} else {
					input.style.background = '#fee2e2'; // red error
					setTimeout(() => { input.style.background = 'transparent'; }, 1500);
				}
			} catch (e) {
				console.error('Failed:', e);
				input.style.background = '#fee2e2';
				setTimeout(() => { input.style.background = 'transparent'; }, 1500);
			}
		}

		function recalcRow(partId) {
			// Sum all trip inputs for this partId
			const inputs = document.querySelectorAll(`input[data-part="${partId}"]`);
			let total = 0;
			inputs.forEach(inp => { total += parseInt(inp.value) || 0; });

			// Update Total
			const totalEl = document.getElementById(`total-${partId}`);
			if (totalEl) totalEl.textContent = total > 0 ? total.toLocaleString() : '-';

			// Finish Time = 7.00 + delivery_requirement / production_rate (doesn't change with trips)
			const prodRate = parseFloat(document.getElementById(`prod-rate-${partId}`)?.textContent) || 0;
			const delReq = parseFloat(document.getElementById(`del-req-${partId}`)?.textContent) || 0;
			const finishEl = document.getElementById(`finish-${partId}`);
			if (finishEl) {
				const finishTime = (prodRate > 0 && delReq > 0) ? (7.0 + delReq / prodRate).toFixed(2) : '-';
				finishEl.textContent = finishTime;
			}

			// Update End Stock (Stock + Total - Plan)
			const stockAtCust = parseFloat(document.getElementById(`stock-val-${partId}`)?.textContent?.replace(/,/g, '')) || 0;
			const dailyPlan = parseFloat(document.getElementById(`plan-val-${partId}`)?.textContent?.replace(/,/g, '')) || 0;
			const endStockEl = document.getElementById(`endstock-${partId}`);
			let endStock = stockAtCust + total - dailyPlan;
			if (endStockEl) {
				const span = endStockEl.querySelector('span');
				if (span) {
					span.textContent = endStock.toLocaleString();
					span.className = endStock < 0 ? 'text-red-600' : 'text-slate-900';
				}
			}

			// Est. Finish Time H+1 = 7.00 + delivery_req_h1 / production_rate_h1
			const prodRateH1 = parseFloat(document.getElementById(`prod-rate-h1-${partId}`)?.textContent) || 0;
			const planH1 = parseFloat(document.getElementById(`plan-h1-${partId}`)?.textContent) || 0;
			const estFinishEl = document.getElementById(`estfinish-${partId}`);
			if (estFinishEl) {
				const delReqH1 = Math.max(0, planH1 - Math.max(0, endStock));
				const estFinish = (prodRateH1 > 0 && delReqH1 > 0) ? (7.0 + delReqH1 / prodRateH1).toFixed(2) : '-';
				estFinishEl.textContent = estFinish;
			}
		}
	</script>
@endsection