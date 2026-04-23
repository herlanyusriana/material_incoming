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
                    <p class="mt-1 text-sm text-slate-500">FG planning ditampilkan per baris part. Isi target Shift 1/2/3, lalu sistem hitung total WO otomatis.</p>
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

        @php
            $planningLineCollection = collect($planningLines ?? []);
            $lineCount = $planningLineCollection->count();
            $totalShift1Qty = $planningLineCollection->sum(fn($line) => (float) $line->shift_1_qty);
            $totalShift2Qty = $planningLineCollection->sum(fn($line) => (float) $line->shift_2_qty);
            $totalShift3Qty = $planningLineCollection->sum(fn($line) => (float) $line->shift_3_qty);
            $totalPlanQty = $planningLineCollection->sum(fn($line) => (float) $line->plan_qty);
            $generatedWoCount = $planningLineCollection->sum(fn($line) => $line->productionOrders?->count() ?? 0);
        @endphp

        {{-- Planning Control Panel --}}
        <div class="mb-4 overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm">
            <div class="grid gap-0 lg:grid-cols-[minmax(260px,1fr)_auto]">
                <div class="border-b border-blue-100 bg-gradient-to-br from-white via-blue-50 to-sky-100 px-5 py-4 text-slate-900 lg:border-b-0 lg:border-r lg:border-blue-100">
                    <div class="text-[11px] font-bold uppercase tracking-[0.24em] text-blue-700">Planning Date</div>
                    <div class="mt-1 flex flex-wrap items-end gap-2">
                        <div class="text-3xl font-black leading-none text-blue-950">{{ $planDate->format('d') }}</div>
                        <div class="pb-0.5 text-lg font-semibold text-slate-700">{{ $planDate->format('F Y') }}</div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-6">
                        <div class="rounded-xl border border-blue-100 bg-white px-3 py-2 shadow-sm">
                            <div class="text-[10px] uppercase tracking-wide text-slate-500">Lines</div>
                            <div class="font-mono text-lg font-black text-blue-950">{{ number_format($lineCount) }}</div>
                        </div>
                        <div class="rounded-xl border border-blue-100 bg-white px-3 py-2 shadow-sm">
                            <div class="text-[10px] uppercase tracking-wide text-slate-500">Shift 1</div>
                            <div class="font-mono text-lg font-black text-blue-700">{{ number_format($totalShift1Qty, 0) }}</div>
                        </div>
                        <div class="rounded-xl border border-blue-100 bg-white px-3 py-2 shadow-sm">
                            <div class="text-[10px] uppercase tracking-wide text-slate-500">Shift 2</div>
                            <div class="font-mono text-lg font-black text-blue-700">{{ number_format($totalShift2Qty, 0) }}</div>
                        </div>
                        <div class="rounded-xl border border-blue-100 bg-white px-3 py-2 shadow-sm">
                            <div class="text-[10px] uppercase tracking-wide text-slate-500">Shift 3</div>
                            <div class="font-mono text-lg font-black text-blue-700">{{ number_format($totalShift3Qty, 0) }}</div>
                        </div>
                        <div class="rounded-xl border border-blue-100 bg-white px-3 py-2 shadow-sm">
                            <div class="text-[10px] uppercase tracking-wide text-slate-500">Total</div>
                            <div class="font-mono text-lg font-black text-sky-700">{{ number_format($totalPlanQty, 0) }}</div>
                        </div>
                        <div class="rounded-xl border border-blue-100 bg-white px-3 py-2 shadow-sm">
                            <div class="text-[10px] uppercase tracking-wide text-slate-500">WO</div>
                            <div class="font-mono text-lg font-black text-blue-950">{{ number_format($generatedWoCount) }}</div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col justify-center gap-3 px-5 py-4">
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
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <form action="{{ route('production.planning.auto-populate') }}" method="POST" class="shrink-0">
                                @csrf
                                <input type="hidden" name="session_id" value="{{ $session->id }}">
                                <button type="submit"
                                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-gradient-to-r from-fuchsia-500 to-violet-600 px-4 text-sm font-semibold text-white shadow-sm hover:from-fuchsia-600 hover:to-violet-700 transition-all whitespace-nowrap"
                                    onclick="return confirm('Auto-populate planning lines dari FG part dan BOM? WO tetap dibuat per part dan target, bukan per mesin.')">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Auto BOM
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
                        </div>
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
            {{-- Main Planning Table --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-hidden">
                    <table class="w-full table-fixed text-xs border-collapse" id="planningTable" style="table-layout: fixed;">
                        <colgroup>
                            <col style="width: 10%;">
                            <col style="width: 20%;">
                            <col style="width: 14%;">
                            <col style="width: 8%;">
                            <col style="width: 7%;">
                            <col style="width: 8%;">
                            <col style="width: 8%;">
                            <col style="width: 8%;">
                            <col style="width: 8%;">
                            <col style="width: 9%;">
                        </colgroup>
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-900 text-[10px] font-black uppercase tracking-[0.12em] text-slate-300">
                                <th class="px-3 py-3 text-left">Model</th>
                                <th class="px-3 py-3 text-left">Part Name</th>
                                <th class="px-3 py-3 text-left">Part No</th>
                                <th class="px-3 py-3 text-right">Stock</th>
                                <th class="px-3 py-3 text-center">Seq</th>
                                <th class="px-3 py-3 text-right text-emerald-200">Shift 1</th>
                                <th class="px-3 py-3 text-right text-emerald-200">Shift 2</th>
                                <th class="px-3 py-3 text-right text-emerald-200">Shift 3</th>
                                <th class="px-3 py-3 text-right text-cyan-200">Total WO</th>
                                <th class="px-3 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        @forelse(($planningLines ?? collect()) as $line)
                                <!-- Main Row Data -->
                                <tbody class="border-b border-slate-100 odd:bg-white even:bg-slate-50/40 hover:bg-emerald-50/40 transition-colors group">
                                        @php
                                            $primaryWo = $line->productionOrders->first();
                                        @endphp
                                        <tr data-line-id="{{ $line->id }}">
                                            <td class="px-3 py-2 text-[11px] font-semibold text-slate-500">
                                                <div class="truncate" title="{{ $line->gciPart->model ?? '-' }}">
                                                    {{ $line->gciPart->model ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-[12px] font-semibold text-slate-900">
                                                <div class="truncate" title="{{ $line->gciPart->part_name ?? '-' }}">
                                                    {{ $line->gciPart->part_name ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 font-mono text-[12px] font-black text-slate-700">
                                                <div class="truncate" title="{{ $line->gciPart->part_no ?? '-' }}">
                                                    {{ $line->gciPart->part_no ?? '-' }}
                                                </div>
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <span class="font-mono text-sm font-black text-slate-900">{{ number_format((float) $line->stock_fg_gci, 0) }}</span>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <input type="number" step="1" min="1"
                                                    class="mx-auto w-14 rounded-lg border border-slate-200 bg-white p-1.5 text-center text-xs font-black text-slate-700 shadow-sm transition-all hover:border-slate-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                                    value="{{ $line->production_sequence }}" placeholder="Seq"
                                                    @change="updateLineField($event, {{ $line->id }}, 'production_sequence')">
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <input type="number" step="1" min="0"
                                                    class="ml-auto w-20 rounded-lg border border-emerald-200 bg-emerald-50/60 p-1.5 text-right text-sm font-black text-emerald-700 shadow-sm transition-all hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                                    value="{{ $line->shift_1_qty > 0 ? intval($line->shift_1_qty) : '' }}" placeholder="0"
                                                    title="Target internal shift 1."
                                                    @change="updateLineField($event, {{ $line->id }}, 'shift_1_qty')">
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <input type="number" step="1" min="0"
                                                    class="ml-auto w-20 rounded-lg border border-emerald-200 bg-emerald-50/60 p-1.5 text-right text-sm font-black text-emerald-700 shadow-sm transition-all hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                                    value="{{ $line->shift_2_qty > 0 ? intval($line->shift_2_qty) : '' }}" placeholder="0"
                                                    title="Target internal shift 2."
                                                    @change="updateLineField($event, {{ $line->id }}, 'shift_2_qty')">
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <input type="number" step="1" min="0"
                                                    class="ml-auto w-20 rounded-lg border border-emerald-200 bg-emerald-50/60 p-1.5 text-right text-sm font-black text-emerald-700 shadow-sm transition-all hover:border-emerald-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                                    value="{{ $line->shift_3_qty > 0 ? intval($line->shift_3_qty) : '' }}" placeholder="0"
                                                    title="Target internal shift 3."
                                                    @change="updateLineField($event, {{ $line->id }}, 'shift_3_qty')">
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <span class="font-mono text-sm font-black text-cyan-700">
                                                    {{ number_format((float) $line->plan_qty, 0) }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <div class="flex items-center justify-center gap-1.5 transition-opacity">
                                                    @if($line->productionOrders->count())
                                                        <div class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-1 text-green-700 border border-green-200"
                                                            title="{{ $line->productionOrders->pluck('production_order_number')->implode(', ') }}">
                                                            <span class="text-[10px] font-black">{{ $line->productionOrders->count() }} WO</span>
                                                        </div>
                                                        @if($primaryWo)
                                                            <div class="hidden text-[10px] font-mono font-semibold text-slate-500 xl:block">
                                                                {{ $primaryWo->production_order_number }}
                                                            </div>
                                                        @endif
                                                    @elseif($line->plan_qty > 0)
                                                        <button @click="generateMoLine({{ $line->id }}, '{{ addslashes($line->gciPart->part_no ?? '') }}')"
                                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700 border border-amber-200 hover:bg-amber-200 transition-colors shadow-sm"
                                                                title="Generate WO per shift untuk line ini">
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

                                </tbody>
                        @empty
                            <tbody>
                                <tr>
                                    <td colspan="10"
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
                                    <td colspan="3" class="px-4 py-3 text-right text-slate-700">
                                        Grand Total ({{ $totalParts }} parts)
                                    </td>
                                    <td class="px-3 py-3 text-right font-mono text-slate-800">
                                        {{ number_format($grandTotalFgGci, 0) }}
                                    </td>
                                    <td class="px-3 py-3"></td>
                                    <td class="px-3 py-3 text-right font-mono text-emerald-700">
                                        {{ number_format($totalShift1Qty, 0) }}
                                    </td>
                                    <td class="px-3 py-3 text-right font-mono text-emerald-700">
                                        {{ number_format($totalShift2Qty, 0) }}
                                    </td>
                                    <td class="px-3 py-3 text-right font-mono text-emerald-700">
                                        {{ number_format($totalShift3Qty, 0) }}
                                    </td>
                                    <td class="px-3 py-3 text-right font-mono text-cyan-700">
                                        {{ number_format($grandTotalPlanQty, 0) }}
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
                            Isi target Shift 1, Shift 2, dan Shift 3
                        </li>
                        <li class="flex items-center gap-2">
                            <span
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">4</span>
                            Generate WO bulk atau per line
                        </li>
                        <li class="flex items-center gap-2">
                            <span
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">5</span>
                            Operator produksi pilih proses, mesin aktual, dan shift di APK
                        </li>
                        <li class="flex items-center gap-2">
                            <span
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">6</span>
                            Pantau progress WO di Monitoring WO Produksi
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
                            <span class="text-slate-600">Editable fields (sequence dan target Shift 1/2/3)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-4 w-8 bg-emerald-100 rounded border border-emerald-300"></div>
                            <span class="text-slate-600">Mesin aktual dicatat saat operator start WO di APK</span>
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
                    <p class="text-xs text-slate-500 mb-4">Planning cukup pilih part dan target. Proses mengikuti routing BOM, mesin aktual dipilih operator di APK.</p>

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

                            if (['shift_1_qty', 'shift_2_qty', 'shift_3_qty'].includes(field)) {
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

                        if (!confirm(`Generate WO per shift untuk ${partNo || 'part ini'}?`)) return;
                        
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

                        if (!confirm('Generate WO per shift untuk semua planning lines yang belum punya WO?')) return;
                        
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
