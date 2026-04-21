@extends('layouts.app')

@section('title', 'GCI Planning Produksi')

@section('content')
    <div class="max-w-full mx-auto" x-data="planningProduksi()">
        {{-- Header --}}
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm">
                            <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                            </svg>
                        </div>
                        GCI PLANNING PRODUKSI
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">FG planning ditampilkan per baris part dengan target total WO. Realisasi per shift dibaca dari transaksi operator saat produksi berjalan.</p>
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    {{-- Date Selector --}}
                    <form action="{{ route('production.planning.index') }}" method="GET" class="flex items-center gap-2">
                        <label class="text-sm font-semibold text-slate-600">DATE:</label>
                        <input type="date" name="date" value="{{ $planDate->format('Y-m-d') }}"
                            class="rounded-lg border-slate-300 text-sm font-semibold shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                            onchange="this.form.submit()">
                        <input type="hidden" name="source_mode" value="{{ $sourceMode ?? 'delivery' }}">
                    </form>
                    <div class="inline-flex items-center rounded-lg border border-slate-200 bg-white p-1 shadow-sm">
                        <a href="{{ route('production.planning.index', ['date' => $planDate->format('Y-m-d'), 'source_mode' => 'delivery']) }}"
                            class="inline-flex h-8 items-center rounded-md px-3 text-xs font-semibold transition-all {{ ($sourceMode ?? 'delivery') === 'delivery' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                            Pull
                        </a>
                        <a href="{{ route('production.planning.index', ['date' => $planDate->format('Y-m-d'), 'source_mode' => 'raw']) }}"
                            class="inline-flex h-8 items-center rounded-md px-3 text-xs font-semibold transition-all {{ ($sourceMode ?? 'delivery') === 'raw' ? 'bg-orange-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                            Raw
                        </a>
                    </div>

                    @if($session)
                        <span
                            class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                            {{ $planningDays }} Days Plan
                        </span>
                        <span
                            class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold ring-1
                            {{ $session->status === 'confirmed' ? 'bg-blue-50 text-blue-700 ring-blue-200' : ($session->status === 'completed' ? 'bg-green-50 text-green-700 ring-green-200' : 'bg-amber-50 text-amber-700 ring-amber-200') }}">
                            {{ ucfirst($session->status) }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Date Display Header --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-4 mb-4">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <div class="text-lg font-bold text-slate-800">
                        DATE: <span class="text-emerald-600">{{ $planDate->format('d') }}</span>
                        <span class="text-slate-500">{{ $planDate->format('F Y') }}</span>
                    </div>
                </div>

                <div class="flex w-full lg:w-auto items-center justify-end gap-2 flex-wrap">
                    @if(!$session)
                        <form action="{{ route('production.planning.create-session') }}" method="POST"
                            class="flex items-center gap-2 shrink-0">
                            @csrf
                            <input type="hidden" name="plan_date" value="{{ $planDate->format('Y-m-d') }}">
                            <select name="planning_days" class="h-10 rounded-lg border-slate-300 text-sm shadow-sm">
                                <option value="7" selected>7 Days</option>
                                <option value="5">5 Days</option>
                                <option value="10">10 Days</option>
                                <option value="14">14 Days</option>
                            </select>
                            <button type="submit"
                                class="inline-flex h-10 items-center gap-2 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600 px-4 text-sm font-semibold text-white shadow-sm hover:from-emerald-600 hover:to-teal-700 transition-all">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create Session
                            </button>
                        </form>
                    @else
                        <form action="{{ route('production.planning.auto-populate') }}" method="POST" class="shrink-0">
                            @csrf
                            <input type="hidden" name="session_id" value="{{ $session->id }}">
                            <input type="hidden" name="source_mode" value="{{ $sourceMode ?? 'delivery' }}">
                            <button type="submit"
                                class="inline-flex h-10 items-center gap-2 rounded-lg bg-gradient-to-r from-fuchsia-500 to-violet-600 px-4 text-sm font-semibold text-white shadow-sm hover:from-fuchsia-600 hover:to-violet-700 transition-all whitespace-nowrap"
                                onclick="return confirm('Auto-populate planning lines from FG parts with BOM? Machine will be auto-filled from BOM data.')">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Auto-Populate (from BOM)
                            </button>
                        </form>

                        <form action="{{ route('production.planning.pull-delivery-requirement') }}" method="POST"
                            class="flex items-end gap-2 rounded-xl border border-emerald-200 bg-emerald-50/70 px-3 py-2">
                            @csrf
                            <input type="hidden" name="session_id" value="{{ $session->id }}">
                            <div>
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-emerald-700">From</label>
                                <input type="date" name="date_from" value="{{ old('date_from', $planDate->format('Y-m-d')) }}"
                                    class="h-9 rounded-lg border-emerald-200 bg-white text-xs shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-emerald-700">To</label>
                                <input type="date" name="date_to"
                                    value="{{ old('date_to', $planDate->copy()->addDays(max($planningDays - 1, 0))->format('Y-m-d')) }}"
                                    class="h-9 rounded-lg border-emerald-200 bg-white text-xs shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                            <button type="submit"
                                class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600 px-4 text-sm font-semibold text-white shadow-sm hover:from-emerald-600 hover:to-teal-700 transition-all whitespace-nowrap"
                                onclick="return confirm('Tarik Delivery Requirement ke kolom terpisah? Plan Qty produksi tidak akan di-overwrite.')">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Pull Delivery Req
                            </button>
                        </form>

                        <button type="button" @click="generateMoAll({{ $session->id }})"
                                class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-all whitespace-nowrap">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Generate WO Bulk
                        </button>

                        <button @click="showAddPartModal = true"
                            class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-gradient-to-r from-slate-600 to-slate-700 px-4 text-sm font-semibold text-white shadow-sm hover:from-slate-700 hover:to-slate-800 transition-all whitespace-nowrap">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Part
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-semibold text-green-700">{{ session('success') }}</span>
                </div>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-semibold text-red-700">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if($session)
            @php
                $grandTotalEstHours = collect($planningLines ?? [])->sum(function ($line) {
                    if (!$line->machine || (float) $line->plan_qty <= 0) {
                        return 0;
                    }

                    return (float) $line->machine->estimateHours((float) $line->plan_qty);
                });
            @endphp
            {{-- Main Planning Table --}}
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
                <div class="overflow-hidden">
                    <table class="w-full table-fixed text-xs border-collapse" id="planningTable" style="table-layout: fixed;">
                        <colgroup>
                            <col style="width: 3%;">
                            <col style="width: 13%;">
                            <col style="width: 7%;">
                            <col style="width: 20%;">
                            <col style="width: 13%;">
                            <col style="width: 7%;">
                            <col style="width: 8%;">
                            <col style="width: 5%;">
                            <col style="width: 8%;">
                            <col style="width: 6%;">
                            <col style="width: 10%;">
                        </colgroup>
                        <thead>
                            <tr class="bg-gradient-to-r from-slate-100 to-slate-50 border-b-2 border-slate-300">
                                <th class="w-10 px-2 py-3 text-center"></th> <!-- Expand Toggle -->
                                <th class="px-3 py-3 text-left font-bold text-slate-700">MACHINE</th>
                                <th class="px-3 py-3 text-left font-bold text-slate-700">MODEL</th>
                                <th class="px-3 py-3 text-left font-bold text-slate-700">PART NAME</th>
                                <th class="px-3 py-3 text-left font-bold text-slate-700">PART NO</th>
                                <th class="px-3 py-3 text-right font-bold text-slate-700">STOCK GCI</th>
                                <th class="px-3 py-3 text-right font-bold text-blue-700">DELIVERY REQ</th>
                                <th class="px-3 py-3 text-center font-bold text-slate-700">SEQ</th>
                                <th class="px-3 py-3 text-right font-bold text-slate-700 text-emerald-700">PLAN QTY</th>
                                <th class="px-3 py-3 text-right font-bold text-indigo-600">EST. HRS</th>
                                <th class="px-3 py-3 text-center font-bold text-slate-700">ACTION</th>
                            </tr>
                        </thead>
                        @forelse(($planningLines ?? collect()) as $line)
                                <!-- Main Row Data -->
                                <tbody x-data="{ expanded: false }" class="border-b border-slate-200 hover:bg-slate-50/80 transition-colors group">
                                        @php
                                            $lineEstHours = ($line->machine && (float) $line->plan_qty > 0)
                                                ? (float) $line->machine->estimateHours((float) $line->plan_qty)
                                                : 0;
                                            $primaryWo = $line->productionOrders->first();
                                        @endphp
                                        <tr data-line-id="{{ $line->id }}">
                                            <td class="w-10 px-2 py-2 text-center cursor-pointer" @click="expanded = !expanded">
                                                <div class="h-6 w-6 rounded-md hover:bg-slate-200 flex items-center justify-center transition-colors text-slate-400 group-hover:text-emerald-600">
                                                    <svg class="h-4 w-4 transform transition-transform" :class="expanded ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </div>
                                            </td>
                                            <td class="px-3 py-2">
                                                <select
                                                    class="w-full min-w-0 text-xs bg-white border border-slate-200 shadow-sm hover:border-slate-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded p-1.5 font-semibold text-slate-700 transition-all cursor-pointer"
                                                    @change="updateLineField($event, {{ $line->id }}, 'machine_id')">
                                                    <option value="">Assign...</option>
                                                    @foreach($machines as $machine)
                                                        <option value="{{ $machine->id }}" {{ (int) $line->machine_id === (int) $machine->id ? 'selected' : '' }}>
                                                            {{ $machine->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-3 py-2 text-[11px] text-slate-500">
                                                <div class="truncate" title="{{ $line->gciPart->model ?? '-' }}">
                                                    {{ $line->gciPart->model ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 font-medium text-[12px] text-slate-800">
                                                <div class="truncate" title="{{ $line->gciPart->part_name ?? '-' }}">
                                                    {{ $line->gciPart->part_name ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 font-mono text-[12px] font-semibold text-slate-700">
                                                <div class="truncate" title="{{ $line->gciPart->part_no ?? '-' }}">
                                                    {{ $line->gciPart->part_no ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-right font-mono text-[12px] font-semibold text-slate-700">
                                                {{ number_format((float) $line->stock_fg_gci, 0) }}
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <div class="font-mono text-[12px] font-semibold text-blue-700">
                                                    {{ number_format((float) $line->delivery_requirement_qty, 0) }}
                                                </div>
                                                @if($line->delivery_requirement_date_from || $line->delivery_requirement_date_to)
                                                    <div class="text-[10px] text-slate-400">
                                                        {{ optional($line->delivery_requirement_date_from)->format('d M') ?? '-' }}
                                                        -
                                                        {{ optional($line->delivery_requirement_date_to)->format('d M') ?? '-' }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <input type="number" step="1" min="1"
                                                    class="w-16 mx-auto text-center text-xs bg-white border border-slate-200 shadow-sm hover:border-slate-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded p-1 font-bold text-slate-700 transition-all placeholder-slate-300"
                                                    value="{{ $line->production_sequence }}" placeholder="Seq"
                                                    @change="updateLineField($event, {{ $line->id }}, 'production_sequence')">
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <input type="number" step="1" min="0"
                                                    class="w-24 ml-auto text-right text-xs bg-white border border-slate-200 shadow-sm hover:border-slate-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded p-1 font-bold text-emerald-700 transition-all placeholder-emerald-300"
                                                    value="{{ $line->plan_qty > 0 ? intval($line->plan_qty) : '' }}" placeholder="0"
                                                    @change="updateLineField($event, {{ $line->id }}, 'plan_qty')">
                                                <div class="mt-1 text-[10px] text-slate-400">
                                                    Auto: Req - Stock, editable manual
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-right font-mono text-[12px] font-bold text-indigo-700">
                                                {{ $lineEstHours > 0 ? number_format($lineEstHours, 2) . 'h' : '-' }}
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <div class="flex flex-col items-center justify-center gap-1.5 transition-opacity">
                                                    @if($line->productionOrders->count())
                                                        <div class="inline-flex items-center gap-1 rounded-lg bg-green-100 px-2 py-1 text-green-700 border border-green-200"
                                                            title="{{ $line->productionOrders->pluck('production_order_number')->implode(', ') }}">
                                                            <span class="text-[10px] font-black">1 WO</span>
                                                        </div>
                                                        @if($primaryWo)
                                                            <div class="text-[10px] font-mono font-semibold text-slate-500">
                                                                {{ $primaryWo->production_order_number }}
                                                            </div>
                                                        @endif
                                                    @elseif($line->plan_qty > 0)
                                                        <button @click="generateMoLine({{ $line->id }}, '{{ addslashes($line->gciPart->part_no ?? '') }}')"
                                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700 border border-amber-200 hover:bg-amber-200 transition-colors shadow-sm"
                                                                title="Generate 1 WO untuk total plan line ini">
                                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                                </svg>
                                                        </button>
                                                    @endif
                                                    <button @click="deleteLine({{ $line->id }})"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-400 hover:bg-red-50 hover:text-red-600 transition-colors" title="Remove part">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Expandable Detail Row (Projected Stock) -->
                                        <tr x-show="expanded" x-transition.opacity x-cloak>
                                            <td colspan="11" class="px-4 py-4 bg-slate-50 border-t border-slate-100 shadow-inner">
                                                <div class="mb-2 text-xs font-semibold text-slate-500 flex items-center gap-2">
                                                    <svg class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                                    </svg>
                                                    Projected Daily Stock vs Plan Requirement
                                                </div>
                                                <div class="flex gap-2 overflow-x-auto pb-2">
                                                    @foreach($dateRange as $dIdx => $date)
                                                        @php
                                                            $fgStock = (float) $line->stock_fg_gci;
                                                            $planQty = (float) $line->plan_qty;
                                                            $dailyReq = isset($dailyPlanData[$line->gci_part_id]) ? ($dailyPlanData[$line->gci_part_id]['total_qty'] ?? 0) : 0;
                                                            $projectedStock = $fgStock + $planQty - ($dailyReq * ($dIdx + 1));
                                                        @endphp
                                                        <div class="flex-shrink-0 w-24 bg-white border border-slate-200 rounded-lg overflow-hidden shadow-sm">
                                                            <div class="bg-blue-50/50 px-2 py-1.5 border-b border-slate-100 text-center">
                                                                <div class="text-xs font-bold text-slate-700">{{ $date->format('d') }}</div>
                                                                <div class="text-[9px] font-medium text-slate-500 uppercase">{{ $date->format('D') }}</div>
                                                            </div>
                                                            <div class="px-2 py-1.5 text-center bg-white border-b border-slate-50">
                                                                <div class="text-[9px] text-slate-400 mb-0.5">Req</div>
                                                                <div class="font-mono text-[11px] font-semibold text-slate-700">{{ $dailyReq > 0 ? number_format($dailyReq, 0) : '-' }}</div>
                                                            </div>
                                                            <div class="px-2 py-1.5 text-center {{ $projectedStock < 0 ? 'bg-red-50' : ($projectedStock < ($dailyReq * 2) ? 'bg-amber-50' : 'bg-emerald-50/30') }}">
                                                                <div class="text-[9px] text-slate-400 mb-0.5">Proj. Stock</div>
                                                                <div class="font-mono text-[12px] font-bold {{ $projectedStock < 0 ? 'text-red-700' : ($projectedStock < ($dailyReq * 2) ? 'text-amber-700' : 'text-emerald-700') }}">
                                                                    {{ number_format($projectedStock, 0) }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                </tbody>
                        @empty
                            <tbody>
                                <tr>
                                    <td colspan="11"
                                        class="border border-slate-300 px-4 py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center gap-3">
                                            <svg class="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                                            </svg>
                                            <div class="text-sm font-medium">No planning lines yet</div>
                                            <div class="text-xs">Click "Auto-Populate (from BOM)" to load FG parts, or "Add Part" to add manually</div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        @endforelse

                        @if(($planningLines ?? collect())->isNotEmpty())
                            <tbody>
                                {{-- Grand Total Row --}}
                                <tr class="bg-slate-100 border-t-2 border-slate-300 font-bold text-sm">
                                    <td colspan="5" class="px-4 py-3 text-right text-slate-700">
                                        Grand Total ({{ $totalParts }} parts)
                                    </td>
                                    <td class="px-3 py-3 text-right font-mono text-slate-800">
                                        {{ number_format($grandTotalFgGci, 0) }}
                                    </td>
                                    <td class="px-3 py-3 text-right font-mono text-blue-700">
                                        {{ number_format($grandTotalDeliveryRequirementQty, 0) }}
                                    </td>
                                    <td class="px-3 py-3"></td>
                                    <td class="px-3 py-3 text-right font-mono text-emerald-700">
                                        {{ number_format($grandTotalPlanQty, 0) }}
                                    </td>
                                    <td colspan="3" class="px-3 py-3"></td>
                                    <td class="px-3 py-3 text-right font-mono text-indigo-700">
                                        {{ $grandTotalEstHours > 0 ? number_format($grandTotalEstHours, 2) . 'h' : '-' }}
                                    </td>
                                    <td class="px-3 py-3"></td>
                                </tr>
                            </tbody>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Info Cards --}}
            <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
                {{-- Flow Steps --}}
                <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-5">
                    <h3 class="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
                        <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Production Planning Steps
                    </h3>
                    <ol class="space-y-2 text-xs text-slate-600">
                        <li class="flex items-center gap-2">
                            <span
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">1</span>
                            Auto-populate parts from BOM
                        </li>
                        <li class="flex items-center gap-2">
                            <span
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">2</span>
                            Isi sequence bila perlu untuk urutan prioritas
                        </li>
                        <li class="flex items-center gap-2">
                            <span
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">3</span>
                            Fill target total WO
                        </li>
                        <li class="flex items-center gap-2">
                            <span
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">4</span>
                            Isi target total WO
                        </li>
                        <li class="flex items-center gap-2">
                            <span
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">5</span>
                            Generate WO bulk atau per line
                        </li>
                        <li class="flex items-center gap-2">
                            <span
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">6</span>
                            Check Material &amp; Dies availability
                        </li>
                    </ol>
                </div>

                {{-- Legend --}}
                <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-5">
                    <h3 class="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
                        <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Color Legend
                    </h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-8 bg-red-100 rounded border border-red-300"></div>
                            <span class="text-slate-600">Negative stock (shortage — need production)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-8 bg-yellow-100 rounded border border-yellow-300"></div>
                            <span class="text-slate-600">Low stock (warning — plan production soon)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-8 bg-yellow-50 rounded border border-yellow-200"></div>
                            <span class="text-slate-600">Editable fields (seq, qty, distribusi shift)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-8 bg-emerald-100 rounded border border-emerald-300"></div>
                            <span class="text-slate-600">Machine grouping from BOM data</span>
                        </div>
                    </div>
                </div>
            </div>

        @else
            {{-- No Session --}}
            <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-12">
                <div class="flex flex-col items-center gap-4 text-center">
                    <div
                        class="h-16 w-16 rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 flex items-center justify-center">
                        <svg class="h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">No Planning Session for {{ $planDate->format('d F Y') }}
                        </h3>
                        <p class="text-sm text-slate-500 mt-1">Create a planning session to start planning production</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Add Part Modal --}}
        @if($session)
            <div x-show="showAddPartModal" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 p-6" @click.outside="showAddPartModal = false">
                    <h3 class="text-lg font-bold text-slate-800 mb-4">Add Part to Planning</h3>
                    <p class="text-xs text-slate-500 mb-4">Machine will be auto-filled from BOM data if available.</p>

                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-semibold text-slate-600">Search Part (GCI)</label>
                            <input type="text" x-model="partSearch" @input.debounce.300ms="searchParts()"
                                class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Type part name or part number...">
                        </div>

                        <div x-show="partResults.length > 0"
                            class="max-h-48 overflow-y-auto rounded-lg border border-slate-200">
                            <template x-for="part in partResults" :key="part.id">
                                <div class="flex items-center justify-between px-3 py-2 hover:bg-slate-50 cursor-pointer border-b border-slate-100"
                                    @click="selectPart(part)">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-800"
                                            x-text="part.part_name || part.part_no"></div>
                                        <div class="text-xs text-slate-500"
                                            x-text="part.part_no + ' (' + (part.classification || '-') + ')'"></div>
                                    </div>
                                    <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button @click="showAddPartModal = false"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            function planningProduksi() {
                return {
                    showAddPartModal: false,
                    partSearch: '',
                    partResults: [],

                    async searchParts() {
                        if (this.partSearch.length < 2) {
                            this.partResults = [];
                            return;
                        }
                        try {
                            const res = await fetch(`{{ route('gci-parts.search') }}?q=${encodeURIComponent(this.partSearch)}`);
                            const data = await res.json();
                            this.partResults = data.data || data || [];
                        } catch (e) {
                            console.error('Search error:', e);
                        }
                    },

                    async selectPart(part) {
                        try {
                            const res = await fetch(`{{ route('production.planning.add-line') }}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({
                                    session_id: {{ $session ? $session->id : 'null' }},
                                    gci_part_id: part.id,
                                    source_mode: @js($sourceMode ?? 'delivery'),
                                }),
                            });
                            const data = await res.json();
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.error || 'Failed to add part');
                            }
                        } catch (e) {
                            console.error('Add part error:', e);
                            alert('Error adding part');
                        }
                    },

                    async updateLineField(event, lineId, field) {
                        const value = event.target.value === '' ? null : event.target.value;
                        try {
                            const res = await fetch(`/production/planning/line/${lineId}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({ field, value }),
                            });
                            const data = await res.json();
                            if (!data.success) {
                                alert('Update failed');
                                return;
                            }

                            if (field === 'machine_id') {
                                window.location.reload();
                            }
                        } catch (e) {
                            console.error('Update error:', e);
                        }
                    },

                    async deleteLine(lineId) {
                        if (!confirm('Remove this part from planning?')) return;
                        try {
                            const res = await fetch(`/production/planning/line/${lineId}`, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                            });
                            const data = await res.json();
                            if (data.success) {
                                window.location.reload();
                            }
                        } catch (e) {
                            console.error('Delete error:', e);
                        }
                    },

                    async generateMoLine(lineId, partNo) {
                        // Force blur to trigger any pending updates
                        if (document.activeElement && document.activeElement.tagName === 'INPUT') {
                            document.activeElement.blur();
                        }
                        
                        // Wait briefly to allow AJAX to complete
                        await new Promise(r => setTimeout(r, 400));

                        if (!confirm(`Generate WO untuk ${partNo || 'part ini'}?`)) return;
                        
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '{{ route("production.planning.generate-mo-line") }}';
                        
                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = '{{ csrf_token() }}';
                        form.appendChild(csrf);

                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'line_id';
                        input.value = lineId;
                        form.appendChild(input);

                        document.body.appendChild(form);
                        form.submit();
                    },

                    async generateMoAll(sessionId) {
                        if (document.activeElement && document.activeElement.tagName === 'INPUT') {
                            document.activeElement.blur();
                        }

                        await new Promise(r => setTimeout(r, 400));

                        if (!confirm('Generate WO untuk semua planning lines yang belum punya WO?')) return;
                        
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '{{ route("production.planning.generate-mo") }}';
                        
                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = '{{ csrf_token() }}';
                        form.appendChild(csrf);

                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'session_id';
                        input.value = sessionId;
                        form.appendChild(input);

                        document.body.appendChild(form);
                        form.submit();
                    },
                };
            }
        </script>
    @endpush
@endsection
