@extends('layouts.app')

@section('title', 'GCI Planning Produksi')

@section('content')
<div class="max-w-full mx-auto" x-data="planningProduksi()">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm">
                        <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/>
                        </svg>
                    </div>
                    GCI PLANNING PRODUKSI
                </h1>
                <p class="mt-1 text-sm text-slate-500">Production Planning by Machine (from BOM) — Daily Planning Production</p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                {{-- Date Selector --}}
                <form action="{{ route('production.planning.index') }}" method="GET" class="flex items-center gap-2">
                    <label class="text-sm font-semibold text-slate-600">DATE:</label>
                    <input type="date" name="date" value="{{ $planDate->format('Y-m-d') }}"
                        class="rounded-lg border-slate-300 text-sm font-semibold shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                        onchange="this.form.submit()">
                </form>

                @if($session)
                <span class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                    {{ $planningDays }} Days Plan
                </span>
                <span class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold ring-1
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

            <div class="flex items-center gap-2">
                @if(!$session)
                <form action="{{ route('production.planning.create-session') }}" method="POST" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="plan_date" value="{{ $planDate->format('Y-m-d') }}">
                    <select name="planning_days" class="rounded-lg border-slate-300 text-sm shadow-sm">
                        <option value="7" selected>7 Days</option>
                        <option value="5">5 Days</option>
                        <option value="10">10 Days</option>
                        <option value="14">14 Days</option>
                    </select>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-emerald-600 hover:to-teal-700 transition-all">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Create Session
                    </button>
                </form>
                @else
                <form action="{{ route('production.planning.auto-populate') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="session_id" value="{{ $session->id }}">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-500 to-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-blue-600 hover:to-indigo-700 transition-all"
                        onclick="return confirm('Auto-populate planning lines from FG parts with BOM? Machine will be auto-filled from BOM data.')">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Auto-Populate (from BOM)
                    </button>
                </form>

                @if($session->status !== 'confirmed')
                <form action="{{ route('production.planning.generate-mo') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="session_id" value="{{ $session->id }}">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-amber-500 to-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-amber-600 hover:to-orange-700 transition-all"
                        onclick="return confirm('Generate MO/WO from all planning lines with qty and sequence?')">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Generate MO/WO
                    </button>
                </form>
                @endif

                <button @click="showAddPartModal = true" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-slate-600 to-slate-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-slate-700 hover:to-slate-800 transition-all">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
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
            <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-sm font-semibold text-green-700">{{ session('success') }}</span>
        </div>
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4">
        <div class="flex items-center gap-2">
            <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-sm font-semibold text-red-700">{{ session('error') }}</span>
        </div>
    </div>
    @endif

    @if($session)
    {{-- Main Planning Table --}}
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse" id="planningTable">
                <thead>
                    <tr class="bg-gradient-to-r from-yellow-100 to-yellow-50">
                        <th class="sticky left-0 z-20 bg-yellow-100 border border-slate-300 px-2 py-2 text-center font-bold text-slate-700 min-w-[120px]" rowspan="2">MESIN<br><span class="text-[10px] font-normal text-slate-500">(from BOM)</span></th>
                        <th class="border border-slate-300 px-2 py-2 text-center font-bold text-slate-700 min-w-[180px]" rowspan="2">PART NAME</th>
                        <th class="border border-slate-300 px-2 py-2 text-center font-bold text-slate-700 bg-yellow-50" colspan="2">Stock Finish Good</th>
                        <th class="border border-slate-300 px-2 py-2 text-center font-bold text-slate-700 bg-yellow-50" colspan="3">Urutan Produksi (Plan GCI)</th>
                        <th class="border border-slate-300 px-2 py-2 text-center font-bold text-slate-700 bg-blue-50" colspan="{{ count($dateRange) }}">FG Stock vs Planning LG</th>
                        <th class="border border-slate-300 px-2 py-2 text-center font-bold text-slate-700 min-w-[100px]" rowspan="2">Remark</th>
                        <th class="border border-slate-300 px-2 py-2 text-center font-bold text-slate-700 min-w-[60px]" rowspan="2">Action</th>
                    </tr>
                    <tr class="bg-gradient-to-r from-yellow-50 to-white">
                        <th class="border border-slate-300 px-2 py-1.5 text-center font-semibold text-slate-600 bg-yellow-50 min-w-[80px]">FG LG</th>
                        <th class="border border-slate-300 px-2 py-1.5 text-center font-semibold text-slate-600 bg-yellow-50 min-w-[80px]">FG GCI</th>
                        <th class="border border-slate-300 px-2 py-1.5 text-center font-semibold text-slate-600 bg-yellow-50 min-w-[50px]">Seq</th>
                        <th class="border border-slate-300 px-2 py-1.5 text-center font-semibold text-slate-600 bg-yellow-50 min-w-[70px]">Plan Qty</th>
                        <th class="border border-slate-300 px-2 py-1.5 text-center font-semibold text-slate-600 bg-yellow-50 min-w-[50px]">Shift</th>
                        @foreach($dateRange as $date)
                        <th class="border border-slate-300 px-2 py-1.5 text-center font-semibold text-slate-600 bg-blue-50 min-w-[80px]">
                            {{ $date->format('d') }}<br><span class="text-[10px] text-slate-400">{{ $date->format('D') }}</span>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($machineGroups as $machineName => $group)
                        @foreach($group['lines'] as $idx => $line)
                        <tr class="hover:bg-slate-50 transition-colors {{ $idx === 0 ? 'border-t-2 border-t-slate-400' : '' }}"
                            data-line-id="{{ $line->id }}">
                            @if($idx === 0)
                            <td class="sticky left-0 z-10 bg-white border border-slate-300 px-2 py-1.5 text-center font-bold text-[11px] text-slate-700 align-middle"
                                rowspan="{{ count($group['lines']) + 1 }}">
                                <div class="font-bold text-emerald-700 text-[11px] leading-tight">{{ $machineName }}</div>
                                @if($group['process_name'] && $group['process_name'] !== '-')
                                <div class="text-[10px] text-slate-500 mt-0.5">{{ $group['process_name'] }}</div>
                                @endif
                            </td>
                            @endif
                            <td class="border border-slate-300 px-2 py-1.5 font-medium text-slate-800 whitespace-nowrap">
                                {{ $line->gciPart->part_name ?? $line->gciPart->part_no ?? '-' }}
                            </td>
                            <td class="border border-slate-300 px-2 py-1 text-right">
                                <input type="number" step="1"
                                    class="w-full text-right text-xs border-0 bg-transparent p-0 focus:ring-0 focus:outline-none font-mono"
                                    value="{{ intval($line->stock_fg_lg) }}"
                                    @change="updateLineField($event, {{ $line->id }}, 'stock_fg_lg')">
                            </td>
                            <td class="border border-slate-300 px-2 py-1 text-right">
                                <input type="number" step="1"
                                    class="w-full text-right text-xs border-0 bg-transparent p-0 focus:ring-0 focus:outline-none font-mono"
                                    value="{{ intval($line->stock_fg_gci) }}"
                                    @change="updateLineField($event, {{ $line->id }}, 'stock_fg_gci')">
                            </td>
                            <td class="border border-slate-300 px-1 py-1 text-center bg-yellow-50">
                                <input type="number" step="1" min="1"
                                    class="w-full text-center text-xs border-0 bg-transparent p-0 focus:ring-0 focus:outline-none font-bold"
                                    value="{{ $line->production_sequence }}"
                                    placeholder="-"
                                    @change="updateLineField($event, {{ $line->id }}, 'production_sequence')">
                            </td>
                            <td class="border border-slate-300 px-1 py-1 text-right bg-yellow-50">
                                <input type="number" step="1" min="0"
                                    class="w-full text-right text-xs border-0 bg-transparent p-0 focus:ring-0 focus:outline-none font-bold text-emerald-700"
                                    value="{{ $line->plan_qty > 0 ? intval($line->plan_qty) : '' }}"
                                    placeholder="0"
                                    @change="updateLineField($event, {{ $line->id }}, 'plan_qty')">
                            </td>
                            <td class="border border-slate-300 px-1 py-1 text-center bg-yellow-50">
                                <select class="w-full text-center text-xs border-0 bg-transparent p-0 focus:ring-0 focus:outline-none"
                                    @change="updateLineField($event, {{ $line->id }}, 'shift')">
                                    <option value="">-</option>
                                    <option value="1" {{ $line->shift == 1 ? 'selected' : '' }}>1</option>
                                    <option value="2" {{ $line->shift == 2 ? 'selected' : '' }}>2</option>
                                    <option value="3" {{ $line->shift == 3 ? 'selected' : '' }}>3</option>
                                </select>
                            </td>
                            {{-- FG Stock vs Planning LG daily columns --}}
                            @foreach($dateRange as $dIdx => $date)
                            @php
                                $fgStock = (float) $line->stock_fg_lg;
                                $planQty = (float) $line->plan_qty;
                                $dailyReq = isset($dailyPlanData[$line->gci_part_id]) ? ($dailyPlanData[$line->gci_part_id]['total_qty'] ?? 0) : 0;
                                $projectedStock = $fgStock + $planQty - ($dailyReq * ($dIdx + 1));
                            @endphp
                            <td class="border border-slate-300 px-1 py-0 text-center">
                                <div class="text-[10px] leading-tight py-0.5 font-mono {{ $projectedStock >= 0 ? 'text-slate-600' : 'text-red-600 font-bold' }}">
                                    {{ number_format($projectedStock, 0) }}
                                </div>
                                <div class="text-[10px] leading-tight py-0.5 font-mono border-t border-slate-200 {{ $projectedStock < 0 ? 'bg-red-100 text-red-700 font-bold' : ($projectedStock < ($dailyReq * 2) ? 'bg-yellow-100 text-amber-700' : 'text-slate-500') }}">
                                    {{ number_format($projectedStock, 0) }}
                                </div>
                            </td>
                            @endforeach
                            <td class="border border-slate-300 px-1 py-1 text-center">
                                <select class="w-full text-center text-[10px] border-0 bg-transparent p-0 focus:ring-0 focus:outline-none"
                                    @change="updateLineField($event, {{ $line->id }}, 'remark')">
                                    <option value="">-</option>
                                    <option value="LG Plan" {{ $line->remark == 'LG Plan' ? 'selected' : '' }}>LG Plan</option>
                                    <option value="GCI Stock" {{ $line->remark == 'GCI Stock' ? 'selected' : '' }}>GCI Stock</option>
                                </select>
                            </td>
                            <td class="border border-slate-300 px-1 py-1 text-center">
                                <button @click="deleteLine({{ $line->id }})"
                                    class="text-red-400 hover:text-red-600 transition-colors" title="Remove">
                                    <svg class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                        {{-- Machine Subtotal Row --}}
                        <tr class="bg-yellow-100 font-bold border-b-2 border-b-slate-400">
                            <td class="border border-slate-300 px-2 py-1.5 text-center text-[11px] text-slate-700" colspan="1">
                                Sub Total
                            </td>
                            <td class="border border-slate-300 px-2 py-1.5 text-right font-mono">
                                {{ number_format($group['subtotal_fg_lg'], 0) }}
                            </td>
                            <td class="border border-slate-300 px-2 py-1.5 text-right font-mono">
                                {{ number_format($group['subtotal_fg_gci'], 0) }}
                            </td>
                            <td class="border border-slate-300 px-1 py-1.5 text-center" colspan="1"></td>
                            <td class="border border-slate-300 px-1 py-1.5 text-right font-mono text-emerald-700">
                                {{ number_format($group['subtotal_plan_qty'], 0) }}
                            </td>
                            <td class="border border-slate-300 px-1 py-1.5" colspan="1"></td>
                            @foreach($dateRange as $date)
                            <td class="border border-slate-300 px-1 py-1.5 text-center font-mono text-[10px]">-</td>
                            @endforeach
                            <td class="border border-slate-300 px-1 py-1.5" colspan="2"></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 8 + count($dateRange) }}" class="border border-slate-300 px-4 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center gap-3">
                                    <svg class="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/></svg>
                                    <div class="text-sm font-medium">No planning lines yet</div>
                                    <div class="text-xs">Click "Auto-Populate (from BOM)" to load FG parts with machine info from BOM, or "Add Part" to add manually</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    @if(!empty($machineGroups))
                    {{-- Grand Total Row --}}
                    <tr class="bg-gradient-to-r from-emerald-100 to-teal-100 font-bold text-sm">
                        <td class="sticky left-0 z-10 border border-slate-400 px-2 py-2 text-center font-bold text-slate-800 bg-emerald-100">
                            Grand Total
                        </td>
                        <td class="border border-slate-400 px-2 py-2 text-center font-bold text-slate-700">
                            {{ $totalParts }} parts
                        </td>
                        <td class="border border-slate-400 px-2 py-2 text-right font-mono font-bold text-slate-800">
                            {{ number_format($grandTotalFgLg, 0) }}
                        </td>
                        <td class="border border-slate-400 px-2 py-2 text-right font-mono font-bold text-slate-800">
                            {{ number_format($grandTotalFgGci, 0) }}
                        </td>
                        <td class="border border-slate-400 px-2 py-2" colspan="1"></td>
                        <td class="border border-slate-400 px-2 py-2 text-right font-mono font-bold text-emerald-700">
                            {{ number_format($grandTotalPlanQty, 0) }}
                        </td>
                        <td class="border border-slate-400 px-2 py-2" colspan="{{ count($dateRange) + 3 }}"></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- Info Cards --}}
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Flow Steps --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-5">
            <h3 class="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
                <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Production Planning Steps
            </h3>
            <ol class="space-y-2 text-xs text-slate-600">
                <li class="flex items-center gap-2">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">1</span>
                    Auto-populate parts from BOM (machine auto-filled from BOM)
                </li>
                <li class="flex items-center gap-2">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">2</span>
                    Fill qty production &amp; sequence
                </li>
                <li class="flex items-center gap-2">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">3</span>
                    Fill Shift 1, 2 or 3
                </li>
                <li class="flex items-center gap-2">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">4</span>
                    Generate MO/WO
                </li>
                <li class="flex items-center gap-2">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">5</span>
                    Check Material &amp; Dies availability
                </li>
            </ol>
        </div>

        {{-- Legend --}}
        <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-5">
            <h3 class="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
                <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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
                    <span class="text-slate-600">Editable fields (seq, qty, shift)</span>
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
            <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-100 flex items-center justify-center">
                <svg class="h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/></svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-800">No Planning Session for {{ $planDate->format('d F Y') }}</h3>
                <p class="text-sm text-slate-500 mt-1">Create a planning session to start planning production</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Add Part Modal --}}
    @if($session)
    <div x-show="showAddPartModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
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

                <div x-show="partResults.length > 0" class="max-h-48 overflow-y-auto rounded-lg border border-slate-200">
                    <template x-for="part in partResults" :key="part.id">
                        <div class="flex items-center justify-between px-3 py-2 hover:bg-slate-50 cursor-pointer border-b border-slate-100"
                            @click="selectPart(part)">
                            <div>
                                <div class="text-sm font-semibold text-slate-800" x-text="part.part_name || part.part_no"></div>
                                <div class="text-xs text-slate-500" x-text="part.part_no + ' (' + (part.classification || '-') + ')'"></div>
                            </div>
                            <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </div>
                    </template>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button @click="showAddPartModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
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
            const value = event.target.value;
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
    };
}
</script>
@endpush
@endsection
