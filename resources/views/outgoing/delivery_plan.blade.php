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

		.fg-row td:first-child {
			cursor: pointer;
		}

		/* H+1 columns */
		.h1-col {
			background: #eff6ff !important;
		}

		/* Calculated columns */
		.calc-col {
			background: #faf5ff !important;
		}

		/* Expand button */
		.expand-btn {
			width: 24px;
			height: 24px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			border-radius: 6px;
			background: #f1f5f9;
			border: none;
			cursor: pointer;
			transition: background 0.15s;
		}

		.expand-btn:hover {
			background: #e2e8f0;
		}

		/* Trip panel (expanded row) */
		.trip-panel {
			background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
			border-top: 2px solid #86efac;
			border-bottom: 2px solid #86efac;
			padding: 12px 16px;
		}

		.trip-grid {
			display: grid;
			grid-template-columns: repeat(7, 1fr);
			gap: 8px;
		}

		.trip-input-box {
			background: white;
			border: 1px solid #bbf7d0;
			border-radius: 8px;
			padding: 4px 6px;
			text-align: center;
			transition: border-color 0.15s, box-shadow 0.15s;
		}

		.trip-input-box:focus-within {
			border-color: #6366f1;
			box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
		}

		.trip-expanded-input {
			width: 100%;
			border: 0;
			background: transparent;
			text-align: center;
			font-weight: 700;
			color: #334155;
			padding: 4px 2px;
			font-size: 13px;
			-moz-appearance: textfield;
		}

		.trip-expanded-input::-webkit-outer-spin-button,
		.trip-expanded-input::-webkit-inner-spin-button {
			-webkit-appearance: none;
			margin: 0;
		}

		.trip-expanded-input:focus {
			outline: none;
		}

		/* Separator between FG part groups */
		.dp-table>tbody+tbody>tr:first-child>td {
			border-top: 2px solid #e2e8f0;
		}

		@media (max-width: 768px) {
			.dp-scroll {
				max-height: 60vh;
			}

			.dp-table {
				font-size: 11px;
			}

			.dp-table th,
			.dp-table td {
				padding: 4px 6px !important;
			}

			.dp-table .s-col {
				position: sticky;
				z-index: 30;
				min-width: 60px;
			}

			.dp-table thead .s-col {
				z-index: 40;
			}

			.trip-grid {
				grid-template-columns: repeat(4, 1fr);
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
					<form id="soForm" method="POST" action="{{ route('outgoing.delivery-plan.generate-so') }}"
						onsubmit="return false;">
						@csrf
						<table class="dp-table w-full text-xs">
							<thead>
								<tr>
									<th class="px-1 py-3 text-center min-w-[36px] s-col" style="left:0">
										<input type="checkbox" id="selectAll" class="rounded border-slate-300 cursor-pointer"
											onchange="document.querySelectorAll('input[name=\\'selected[]\\']').forEach(cb => cb.checked = this.checked)">
									</th>
									<th class="px-2 py-3 text-left min-w-[44px] s-col" style="left:36px">No</th>
									<th class="px-2 py-3 text-left min-w-[70px] s-col" style="left:80px">Category</th>
									<th class="px-2 py-3 text-left min-w-[160px] s-col" style="left:150px">FG Part Name</th>
									<th class="px-2 py-3 text-left min-w-[110px] s-col" style="left:310px">FG Part No.</th>
									<th class="px-2 py-3 text-left min-w-[80px] s-col" style="left:420px">Model</th>
									<th class="px-2 py-3 text-right min-w-[80px]">Stock at<br>Customer</th>
									<th class="px-2 py-3 text-right min-w-[80px]">Daily Plan<br>H</th>
									<th class="px-2 py-3 text-left min-w-[100px] bg-yellow-50 !text-amber-700">Jig Name</th>
									<th class="px-2 py-3 text-center min-w-[60px] bg-yellow-50 !text-amber-700">Jig<br>Qty H
									</th>
									<th class="px-2 py-3 text-center min-w-[60px] bg-yellow-50 !text-amber-700">UpH</th>
									<th class="px-2 py-3 text-right min-w-[70px] bg-yellow-50 !text-amber-700">Total<br>UpH</th>
									<th class="px-2 py-3 text-right min-w-[80px]">Delivery<br>Req.</th>
									<th class="px-2 py-3 text-center min-w-[75px]">Std<br>Packing</th>
									<th class="px-2 py-3 text-right min-w-[75px]" style="background:#dcfce7; color:#166534;">
										Total<br>Trip</th>
									<th class="px-2 py-3 text-right min-w-[75px] calc-col">Finish<br>Time</th>
									<th class="px-2 py-3 text-right min-w-[80px] calc-col">End Stock<br>@Cust</th>
									<th class="px-2 py-3 text-right min-w-[80px] h1-col">Daily Plan<br>H+1</th>
									<th class="px-2 py-3 text-center min-w-[70px] h1-col">Jig Qty<br>H+1</th>
									<th class="px-2 py-3 text-right min-w-[75px] h1-col">Est. Finish<br>Time</th>
								</tr>
							</thead>
							@php $no = 0; @endphp
							@foreach($rows as $row)
								@php
									$no++;
									$jigCount = count($row->jigs);
									$rowSpan = max(1, $jigCount);
									$rowSource = $row->source ?? 'daily_plan';
									// Use loop index to ensure absolute uniqueness of DOM IDs
									$rowKey = 'r' . $no . '-' . $row->gci_part_id;
								@endphp

								<tbody>
									{{-- Main FG row --}}
									<tr class="fg-row" data-part-id="{{ $row->gci_part_id }}" data-source="{{ $rowSource }}">
										{{-- Select checkbox + expand button --}}
										<td class="px-1 py-2 text-center s-col" style="left:0" @if($rowSpan > 1)
										rowspan="{{ $rowSpan }}" @endif>
											<div class="flex items-center justify-center gap-1">
												<input type="checkbox" name="selected[]" value="{{ $no - 1 }}"
													class="rounded border-slate-300 cursor-pointer">
												<button type="button" onclick="toggleRow('{{ $rowKey }}')" class="expand-btn"
													title="Toggle trip inputs">
													<svg id="icon-{{ $rowKey }}"
														class="w-4 h-4 text-slate-500 transition-transform duration-200" fill="none"
														stroke="currentColor" viewBox="0 0 24 24">
														<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
															d="M9 5l7 7-7 7" />
													</svg>
												</button>
											</div>
										</td>
										<td class="px-2 py-2 text-slate-500 font-semibold s-col" style="left:36px" @if($rowSpan > 1)
										rowspan="{{ $rowSpan }}" @endif>
											{{ $no }}
											{{-- Hidden data spans (always in DOM) --}}
											<span class="hidden" id="prod-rate-{{ $rowKey }}">{{ $row->production_rate }}</span>
											<span class="hidden" id="del-req-{{ $rowKey }}">{{ $row->delivery_requirement }}</span>
											<span class="hidden"
												id="prod-rate-h1-{{ $rowKey }}">{{ $row->production_rate_h1 }}</span>
											<span class="hidden" id="plan-h1-{{ $rowKey }}">{{ $row->daily_plan_h1 }}</span>
										</td>
										<td class="px-2 py-2 text-slate-600 text-[10px] font-bold s-col" style="left:80px"
											@if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
											{{ $row->category }}
											@if($rowSource === 'po' && ($row->po_no ?? null))
												<div class="text-[9px] text-amber-600 font-semibold">{{ $row->po_no }}</div>
											@endif
										</td>
										<td class="px-2 py-2 text-slate-900 font-bold whitespace-nowrap s-col" style="left:150px"
											@if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
											<div class="flex items-center gap-2">
												{{ $row->fg_part_name }}
												@if($row->is_osp)
													<span
														class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-tighter bg-emerald-100 text-emerald-800 border border-emerald-200">OSP</span>
												@endif
											</div>
											@if($row->osp_order)
												<div class="mt-0.5 text-[9px] font-medium text-amber-600 flex items-center gap-1">
													<span class="w-1 h-1 rounded-full bg-amber-500"></span>
													Assy: {{ number_format($row->osp_order->qty_assembled) }} /
													{{ number_format($row->osp_order->qty_received_material) }}
													<a href="{{ route('outgoing.osp.show', $row->osp_order) }}"
														class="underline hover:text-amber-700 ml-1">Detail</a>
												</div>
											@endif
										</td>
										<td class="px-2 py-2 text-indigo-700 font-mono font-bold whitespace-nowrap s-col"
											style="left:310px" @if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
											{{ $row->fg_part_no }}
										</td>
										<td class="px-2 py-2 text-slate-600 whitespace-nowrap s-col" style="left:420px" @if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
											{{ $row->model }}
										</td>
										<td class="px-2 py-2 text-right text-slate-700 font-semibold" id="stock-val-{{ $rowKey }}"
											@if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
											{{ $row->stock_at_customer > 0 ? number_format($row->stock_at_customer) : '-' }}
										</td>
										<td class="px-2 py-2 text-right text-slate-900 font-bold" id="plan-val-{{ $rowKey }}"
											@if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
											{{ $row->daily_plan_qty > 0 ? number_format($row->daily_plan_qty) : '-' }}
										</td>

										{{-- First jig --}}
										@if($jigCount > 0)
											<td class="px-2 py-2 bg-yellow-50 font-semibold text-amber-800">
												{{ $row->jigs[0]->jig_name }}
											</td>
											<td class="px-1 py-2 bg-yellow-50 text-center font-bold text-amber-900">
												{{ $row->jigs[0]->jig_qty ?: '-' }}
											</td>
											<td class="px-1 py-2 bg-yellow-50 text-center font-semibold text-amber-700">
												{{ $row->jigs[0]->uph ?: '-' }}
											</td>
											<td class="px-2 py-2 bg-yellow-50 text-right font-bold text-amber-900" @if($rowSpan > 1)
											rowspan="{{ $rowSpan }}" @endif>
												{{ $row->production_rate > 0 ? number_format($row->production_rate) : '-' }}
											</td>
										@else
											<td class="px-2 py-2 bg-yellow-50 text-slate-300 italic">-</td>
											<td class="px-1 py-2 bg-yellow-50 text-center text-slate-300">-</td>
											<td class="px-1 py-2 bg-yellow-50 text-center text-slate-300">-</td>
											<td class="px-2 py-2 bg-yellow-50 text-right text-slate-300">-</td>
										@endif

										<td class="px-2 py-2 text-right font-bold text-slate-900" @if($rowSpan > 1)
										rowspan="{{ $rowSpan }}" @endif>
											{{ $row->delivery_requirement > 0 ? number_format($row->delivery_requirement) : '-' }}
										</td>
										<td class="px-2 py-2 text-center text-slate-700 font-semibold" @if($rowSpan > 1)
										rowspan="{{ $rowSpan }}" @endif>
											{{ $row->std_packing > 0 ? number_format($row->std_packing) : '-' }}
										</td>

										{{-- Total trips (summary) --}}
										<td class="px-2 py-2 text-right font-black text-green-800" style="background:#dcfce7;"
											id="total-{{ $rowKey }}" @if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
											{{ $row->total_trips > 0 ? number_format($row->total_trips) : '-' }}
										</td>
										<td class="px-2 py-2 text-right font-semibold text-slate-700 calc-col"
											id="finish-{{ $rowKey }}" @if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
											{{ $row->finish_time ?? '-' }}
										</td>
										<td class="px-2 py-2 text-right font-bold calc-col" id="endstock-{{ $rowKey }}" @if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
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
											id="estfinish-{{ $rowKey }}" @if($rowSpan > 1) rowspan="{{ $rowSpan }}" @endif>
											{{ $row->est_finish_time ?? '-' }}
										</td>
									</tr>

									{{-- Additional jig sub-rows --}}
									@for($j = 1; $j < $jigCount; $j++)
										<tr class="jig-sub">
											<td class="px-2 py-2 bg-yellow-50 font-semibold text-amber-800">
												{{ $row->jigs[$j]->jig_name }}
											</td>
											<td class="px-1 py-2 bg-yellow-50 text-center font-bold text-amber-900">
												{{ $row->jigs[$j]->jig_qty ?: '-' }}
											</td>
											<td class="px-1 py-2 bg-yellow-50 text-center font-semibold text-amber-700">
												{{ $row->jigs[$j]->uph ?: '-' }}
											</td>
										</tr>
									@endfor

									{{-- Expandable trip input row --}}
									<tr id="trip-row-{{ $rowKey }}" class="hidden transition-all duration-200">
										<td colspan="20" class="p-0" style="border-right:0;">
											<div class="trip-panel">
												<div class="flex items-center justify-between mb-3">
													<div class="flex items-center gap-2">
														<span
															class="inline-flex items-center justify-center w-5 h-5 rounded bg-green-200 text-green-800 text-[10px] font-black">T</span>
														<span class="text-xs font-bold text-green-800">Trip Inputs</span>
														<span
															class="text-[10px] text-green-600 font-semibold">{{ $row->fg_part_name }}</span>
													</div>
													<button type="button" onclick="toggleRow('{{ $rowKey }}')"
														class="text-xs text-slate-400 hover:text-slate-600 flex items-center gap-1">
														<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
																d="M5 15l7-7 7 7" />
														</svg>
														Collapse
													</button>
												</div>
												<div class="trip-grid">
													@for($t = 1; $t <= 14; $t++)
														<div class="trip-input-box">
															<label
																class="text-[10px] font-bold text-green-700 block text-center mb-0.5">Trip
																{{ $t }}</label>
															<input type="number" min="0" value="{{ $row->trips[$t] ?: '' }}"
																placeholder="-" data-part="{{ $row->gci_part_id }}" data-trip="{{ $t }}"
																data-source="{{ $rowSource }}" data-rowkey="{{ $rowKey }}"
																data-po-item-id="{{ $row->outgoing_po_item_id ?? '' }}"
																oninput="recalcRow('{{ $rowKey }}')"
																class="trip-expanded-input trip-input">
														</div>
													@endfor
												</div>
											</div>
										</td>
									</tr>
								</tbody>
							@endforeach
						</table>
				</div>

				{{-- Hidden fields for form submission --}}
				<input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
				@foreach($rows as $idx => $row)
					<input type="hidden" name="lines[{{ $idx }}][gci_part_id]" value="{{ $row->gci_part_id }}">
					<input type="hidden" name="lines[{{ $idx }}][customer_id]" value="{{ $row->gci_part->customer->id ?? 0 }}">
					<input type="hidden" name="lines[{{ $idx }}][part_no]" value="{{ $row->fg_part_no }}">
					<input type="hidden" name="lines[{{ $idx }}][part_name]" value="{{ $row->fg_part_name }}">
					<input type="hidden" name="lines[{{ $idx }}][qty]" value="{{ $row->delivery_requirement }}">
					<input type="hidden" name="lines[{{ $idx }}][source]" value="{{ $row->source ?? 'daily_plan' }}">
					<input type="hidden" name="lines[{{ $idx }}][outgoing_po_item_id]"
						value="{{ $row->outgoing_po_item_id ?? '' }}">
				@endforeach

				{{-- Footer --}}
				<div class="border-t border-slate-200 p-4 bg-slate-50">
					<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
						<div class="text-sm text-slate-500">
							<span class="font-semibold">Tip:</span> Klik tombol panah di setiap baris untuk input Trip (1-14).
							Kolom kuning = data JIG. Kolom ungu = kalkulasi otomatis.
						</div>
						<div class="flex items-center gap-2 flex-wrap">
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
							<button type="button"
								class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-bold text-white hover:bg-slate-700"
								id="savePlanningBtn" onclick="savePlanning()">
								<svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
										d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
								</svg>
								Save Planning
							</button>
							<button type="button"
								class="rounded-xl bg-green-600 px-4 py-2 text-sm font-bold text-white hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
								id="generateSoBtn" disabled onclick="handleGenerateSo(event)">
								<svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
								</svg>
								Generate SO
							</button>
						</div>
					</div>
				</div>
				</form>
		@endif
		</div>

		<script>
			const CSRF_TOKEN = '{{ csrf_token() }}';
			const UPDATE_TRIPS_URL = '{{ route("outgoing.delivery-plan.update-trips") }}';
			const DELIVERY_DATE = '{{ $selectedDate->toDateString() }}';

			async function savePlanning() {
				const btn = document.getElementById('savePlanningBtn');
				const originalText = btn.innerHTML;
				btn.disabled = true;
				btn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Saving...';

				// Collect all inputs with values
				const inputs = document.querySelectorAll('.trip-input');
				const data = [];

				inputs.forEach(input => {
					const val = parseInt(input.value);
					if (!isNaN(val)) {
						data.push({
							gci_part_id: input.dataset.part,
							trip_no: input.dataset.trip,
							qty: val,
							source: input.dataset.source || 'daily_plan',
							outgoing_po_item_id: input.dataset.poItemId || null
						});
					}
				});

				if (data.length === 0) {
					btn.disabled = false;
					btn.innerHTML = originalText;
					return true; // Nothing to save is considered success or "current"
				}

				try {
					const response = await fetch(UPDATE_TRIPS_URL, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json',
							'X-CSRF-TOKEN': CSRF_TOKEN,
							'Accept': 'application/json'
						},
						body: JSON.stringify({
							delivery_date: DELIVERY_DATE,
							data: data
						})
					});

					if (response.ok) {
						// Success - subtle indication (e.g. change button text briefly)
						btn.innerHTML = '<svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Saved';
						setTimeout(() => {
							btn.disabled = false;
							btn.innerHTML = originalText;
						}, 1000);
						return true;
					} else {
						console.error('Save failed');
						btn.disabled = false;
						btn.innerHTML = originalText;
						alert('Failed to save. Please try again.');
						return false;
					}
				} catch (e) {
					console.error('Save error:', e);
					btn.disabled = false;
					btn.innerHTML = originalText;
					alert('Network error. Please try again.');
					return false;
				}
			}

			async function handleGenerateSo(e) {
				e.preventDefault();
				const checkboxes = document.querySelectorAll('input[name="selected[]"]:checked');
				if (checkboxes.length === 0) {
					alert('Pilih minimal 1 part untuk generate SO');
					return;
				}

				// Trigger save first
				const saved = await savePlanning();
				if (saved) {
					const btn = document.getElementById('generateSoBtn');
					btn.disabled = true;
					btn.innerText = 'Creating SO...';
					document.getElementById('soForm').submit();
				}
			}

			function recalcRow(rowKey) {
				// Sum all trip inputs for this rowKey (partId-source)
				const inputs = document.querySelectorAll(`input[data-rowkey="${rowKey}"]`);
				let total = 0;
				inputs.forEach(inp => { total += parseInt(inp.value) || 0; });

				// Update Total
				const totalEl = document.getElementById(`total-${rowKey}`);
				if (totalEl) totalEl.textContent = total > 0 ? total.toLocaleString() : '-';

				// Finish Time = 07:00 + delivery_requirement / production_rate (format HH:MM)
				const prodRate = parseFloat(document.getElementById(`prod-rate-${rowKey}`)?.textContent) || 0;
				const delReq = parseFloat(document.getElementById(`del-req-${rowKey}`)?.textContent?.replace(/,/g, '')) || 0;
				const stockAtCust = parseFloat(document.getElementById(`stock-val-${rowKey}`)?.textContent?.replace(/,/g, '')) || 0;
				const finishEl = document.getElementById(`finish-${rowKey}`);
				if (finishEl) {
					if (prodRate > 0 && delReq > 0) {
						const decimal = 7.0 + delReq / prodRate;
						const h = Math.floor(decimal);
						let m = Math.round((decimal - h) * 60);
						if (m === 60) { h++; m = 0; }
						finishEl.textContent = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
					} else {
						finishEl.textContent = '-';
					}
				}

				// Update End Stock (Stock + Total - Plan)
				const dailyPlan = parseFloat(document.getElementById(`plan-val-${rowKey}`)?.textContent?.replace(/,/g, '')) || 0;
				const endStockEl = document.getElementById(`endstock-${rowKey}`);
				let endStock = stockAtCust + total - dailyPlan;
				if (endStockEl) {
					const span = endStockEl.querySelector('span');
					if (span) {
						span.textContent = endStock.toLocaleString();
						span.className = endStock < 0 ? 'text-red-600' : 'text-slate-900';
					}
				}

				// Est. Finish Time H+1 = 07:00 + end_stock_customer / total_uph_h1 (format HH:MM)
				const prodRateH1 = parseFloat(document.getElementById(`prod-rate-h1-${rowKey}`)?.textContent) || 0;
				const estFinishEl = document.getElementById(`estfinish-${rowKey}`);
				if (estFinishEl) {
					if (prodRateH1 > 0 && endStock > 0) {
						const decimal = 7.0 + endStock / prodRateH1;
						const h = Math.floor(decimal);
						let m = Math.round((decimal - h) * 60);
						if (m === 60) { h++; m = 0; }
						estFinishEl.textContent = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
					} else {
						estFinishEl.textContent = '-';
					}
				}
			}

			// Enable/disable Generate SO button based on checkbox selection
			function updateSoButtonState() {
				const checkboxes = document.querySelectorAll('input[name="selected[]"]');
				const generateSoBtn = document.getElementById('generateSoBtn');
				const hasChecked = Array.from(checkboxes).some(cb => cb.checked);
				generateSoBtn.disabled = !hasChecked;
			}

			function toggleRow(rowKey) {
				const row = document.getElementById('trip-row-' + rowKey);
				const icon = document.getElementById('icon-' + rowKey);

				if (row.classList.contains('hidden')) {
					row.classList.remove('hidden');
					icon.classList.add('rotate-90');
				} else {
					row.classList.add('hidden');
					icon.classList.remove('rotate-90');
				}
			}

			// Setup checkbox listener
			document.addEventListener('DOMContentLoaded', function () {
				const checkboxes = document.querySelectorAll('input[name="selected[]"]');
				const selectAllCheckbox = document.getElementById('selectAll');

				checkboxes.forEach(checkbox => {
					checkbox.addEventListener('change', updateSoButtonState);
				});

				if (selectAllCheckbox) {
					selectAllCheckbox.addEventListener('change', function () {
						checkboxes.forEach(cb => {
							if (!cb.classList.contains('select-all-checkbox')) {
								cb.checked = this.checked;
							}
						});
						updateSoButtonState();
					});
				}
			});
		</script>
@endsection