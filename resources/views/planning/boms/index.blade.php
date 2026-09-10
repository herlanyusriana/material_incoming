<x-app-layout>
    <x-slot name="header">
        {{ __('planning.boms.index.header') }}
    </x-slot>

    <div class="py-6" x-data="planningBoms()">
        <div class="w-full px-2 sm:px-4 lg:px-6 space-y-5">
            @if (session('success'))
                <div
                    class="rounded-xl bg-emerald-50 border border-emerald-200/60 px-5 py-3.5 text-sm text-emerald-800 flex items-center gap-3 shadow-sm animate-fade-in">
                    <span
                        class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div
                    class="rounded-xl bg-red-50 border border-red-200/60 px-5 py-3.5 text-sm text-red-800 flex items-center gap-3 shadow-sm">
                    <span
                        class="flex-shrink-0 w-8 h-8 rounded-full bg-red-100 flex items-center justify-center text-red-600">✕</span>
                    <span class="font-medium">{{ session('error') }}</span>
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl bg-red-50 border border-red-200/60 px-5 py-3.5 text-sm text-red-800 shadow-sm">
                    <div class="font-semibold flex items-center gap-2">
                        <span
                            class="w-5 h-5 rounded-full bg-red-200 flex items-center justify-center text-red-700 text-xs">!</span>
                        {{ __('planning.boms.index.validation') }}
                    </div>
                    <ul class="mt-1.5 list-disc pl-5 space-y-0.5 text-red-700">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <style>
                @keyframes fade-in {
                    from {
                        opacity: 0;
                        transform: translateY(-8px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                .animate-fade-in {
                    animation: fade-in 0.4s ease-out;
                }

                .bom-table {
                    border-collapse: separate;
                    border-spacing: 0;
                    width: 100%;
                    min-width: 900px;
                }

                .bom-table th,
                .bom-table td {
                    border-bottom: 1px solid #e2e8f0;
                    padding: 6px 8px;
                    vertical-align: middle;
                }
                .bom-table th {
                    background: #f8fafc;
                    color: #475569;
                    font-size: 10px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    position: sticky;
                    top: 0;
                    z-index: 20;
                    border-bottom: 2px solid #e2e8f0;
                }
                /* Sticky columns: row number and actions */
                .sticky-col-no {
                    position: sticky;
                    left: 0;
                    z-index: 30;
                    background: inherit;
                    min-width: 40px;
                    max-width: 40px;
                }
                .sticky-col-fg {
                    position: sticky;
                    left: 40px;
                    z-index: 30;
                    background: inherit;
                    min-width: 200px;
                    max-width: 280px;
                }
                .sticky-col-actions {
                    position: sticky;
                    right: 0;
                    z-index: 30;
                    background: inherit;
                    min-width: 100px;
                    max-width: 120px;
                }
                .th-sticky {
                    z-index: 40 !important;
                    background: #f8fafc !important;
                }
                .th-sticky-actions {
                    z-index: 40 !important;
                    background: #f8fafc !important;
                    border-left: 1px solid #e2e8f0;
                }

                .parent-row {
                    background: #fafbff;
                    border-left: 4px solid #6366f1;
                    font-weight: 600;
                }
                .parent-row:hover {
                    background: #f1f4ff;
                }
                .parent-row td {
                    padding-top: 10px;
                    padding-bottom: 10px;
                }

                .child-row {
                    background: #ffffff;
                    border-left: 3px solid transparent;
                    font-size: 13px;
                }
                .child-row:hover {
                    background: #f8fafc;
                    border-left-color: #a5b4fc;
                }
                .child-row td {
                    padding-top: 5px;
                    padding-bottom: 5px;
                }
                .bom-table-comfortable .child-row td {
                    padding-top: 10px;
                    padding-bottom: 10px;
                }

                .action-btn {
                    width: 28px;
                    height: 28px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 6px;
                    border: 1px solid #e2e8f0;
                    font-size: 11px;
                    background: white;
                    cursor: pointer;
                    transition: background 0.1s ease;
                }
                .action-btn:hover {
                    background: #f1f5f9;
                }

                /* Responsive: tablet & mobile */
                @media (max-width: 1024px) {
                    .bom-table {
                        min-width: 700px;
                    }
                    .hide-tablet {
                        display: none !important;
                    }
                    .sticky-col-fg {
                        min-width: 150px;
                        max-width: 200px;
                    }
                }
                @media (max-width: 768px) {
                    .bom-table {
                        min-width: 500px;
                        font-size: 12px;
                    }
                    .hide-mobile {
                        display: none !important;
                    }
                    .sticky-col-no {
                        min-width: 32px;
                        max-width: 32px;
                    }
                    .sticky-col-fg {
                        min-width: 120px;
                        max-width: 160px;
                    }
                    .sticky-col-actions {
                        min-width: 80px;
                        max-width: 90px;
                    }
                    .parent-row td {
                        padding-top: 6px;
                        padding-bottom: 6px;
                    }
                    .child-row td {
                        padding-top: 3px;
                        padding-bottom: 3px;
                    }
                    .action-btn {
                        width: 24px;
                        height: 24px;
                        font-size: 10px;
                    }
                }
                @media (max-width: 480px) {
                    .bom-table {
                        min-width: 380px;
                        font-size: 11px;
                    }
                    .hide-phone {
                        display: none !important;
                    }
                    .sticky-col-fg {
                        min-width: 90px;
                        max-width: 130px;
                    }
                    .sticky-col-actions {
                        min-width: 70px;
                        max-width: 80px;
                    }
                }

                /* Utility: truncate long text */
                .truncate-cell {
                    max-width: 120px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                    display: inline-block;
                    vertical-align: middle;
                }
                .truncate-cell-sm {
                    max-width: 80px;
                }
                .font-mono-compact {
                    font-family: ui-monospace, SFMono-Regular, monospace;
                    font-size: 0.85em;
                }
                [x-cloak] {
                    display: none !important;
                }
            </style>

            <div class="bg-white border-y border-slate-200">
                {{-- Header --}}
                <div class="p-6 border-b border-slate-100">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-3">
                            <div
                                class="h-12 w-12 rounded-xl bg-slate-900 flex items-center justify-center text-white font-black text-sm">
                                BOM
                            </div>
                            <div>
                                <div class="text-2xl md:text-3xl font-black text-slate-900">{{ __('planning.boms.index.title') }}</div>
                                <div class="mt-1 text-sm text-slate-500">
                                    {{ __('planning.boms.index.subtitle') }}
                                    <span
                                        class="ml-2 px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-[10px] font-bold uppercase tracking-wider border border-indigo-100">
                                        {{ __('planning.boms.index.count', ['count' => $boms->total()]) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1" aria-label="Table density">
                                <button type="button" @click="density = 'compact'"
                                    class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors active:translate-y-px"
                                    :class="density === 'compact' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-white'">
                                    {{ __('planning.boms.index.compact') }}
                                </button>
                                <button type="button" @click="density = 'comfortable'"
                                    class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors active:translate-y-px"
                                    :class="density === 'comfortable' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-white'">
                                    {{ __('planning.boms.index.comfortable') }}
                                </button>
                            </div>
                            <button
                                class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 shadow-sm"
                                @click="openCreate()">
                                {{ __('planning.boms.index.add') }}
                            </button>
                        </div>
                    </div>

                    {{-- Toolbar --}}
                    <div class="mt-6 flex flex-wrap gap-4 items-end border-t border-slate-100 pt-6">
                        <form method="GET" class="flex flex-wrap items-end gap-3">
                            <div>
                                <label for="bom-search"
                                    class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('planning.boms.index.search_label') }}</label>
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input id="bom-search" name="q" value="{{ $q ?? '' }}"
                                        class="w-full pl-9 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="{{ __('planning.boms.index.search_ph') }}">
                                </div>
                            </div>

                            <div>
                                <label for="bom-gci"
                                    class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('planning.boms.index.gci_label') }}</label>
                                <select id="bom-gci" name="gci_part_id"
                                    class="rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">{{ __('planning.boms.index.all_gci') }}</option>
                                    @foreach ($fgParts as $p)
                                        <option value="{{ $p->id }}" @selected((string) ($gciPartId ?? '') === (string) $p->id)>
                                            {{ $p->part_no }} - {{ $p->part_name ?? '-' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit"
                                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-semibold text-sm shadow-sm transition-colors">
                                {{ __('planning.boms.index.filter') }}
                            </button>
                        </form>

                        <div class="flex items-center gap-2 ml-auto flex-wrap">
                            <a href="{{ route('planning.boms.export', request()->query()) }}"
                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                {{ __('planning.boms.index.export') }}
                            </a>
                            <button type="button"
                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                @click="openImport()">
                                {{ __('planning.boms.index.import') }}
                            </button>
                            <form action="{{ route('planning.boms.sync-incoming-parts', request()->only(['gci_part_id', 'q'])) }}" method="POST"
                                onsubmit='return confirm(@js(__("planning.boms.index.confirm_autosync")));'>
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100">
                                    {{ __('planning.boms.index.autosync') }}
                                </button>
                            </form>
                            <a href="{{ route('planning.boms.substitutes.export') }}"
                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50"
                                title="{{ __('planning.boms.index.exp_subst_title') }}">
                                {{ __('planning.boms.index.exp_subst') }}
                            </a>
                            <button type="button"
                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50"
                                @click="openImportSubstitute()" title="{{ __('planning.boms.index.imp_subst_title') }}">
                                {{ __('planning.boms.index.imp_subst') }}
                            </button>
                            <form action="{{ route('planning.boms.truncate') }}" method="POST"
                                onsubmit='return confirm(@js(__("planning.boms.index.confirm_truncate")));'>
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center rounded-xl bg-red-50 border border-red-200 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-100"
                                    title="{{ __('planning.boms.index.clear_all_title') }}">
                                    {{ __('planning.boms.index.clear_all') }}
                                </button>
                            </form>
                            <a href="{{ route('outgoing.product-mapping') }}#where-used"
                                class="inline-flex items-center rounded-xl bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                {{ __('planning.boms.index.where_used') }}
                            </a>
                            <a href="{{ route('planning.boms.explosion-search') }}"
                                class="inline-flex items-center rounded-xl bg-blue-50 border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M7 14l3-3 4 4 5-6" /></svg>
                                {{ __('planning.boms.index.explosion') }}
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="max-w-full overflow-auto max-h-[calc(100dvh-250px)] border-t border-slate-200">
                    <table class="bom-table min-w-[2150px] w-full text-sm"
                        :class="density === 'comfortable' ? 'bom-table-comfortable' : 'bom-table-compact'">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="px-2 py-2 text-center sticky-col-no th-sticky w-12">{{ __('planning.boms.index.th_no') }}</th>
                                <th class="px-2 py-2 text-center whitespace-nowrap">{{ __('planning.boms.index.th_seq') }}</th>
                                <th class="px-2 py-2 text-left min-w-[220px]">{{ __('planning.boms.index.th_fg_name') }}</th>
                                <th class="px-2 py-2 text-left min-w-[130px]">{{ __('planning.boms.index.th_fg_model') }}</th>
                                <th class="px-2 py-2 text-left min-w-[150px]">{{ __('planning.boms.index.th_fg_part_no') }}</th>
                                <th class="px-2 py-2 text-left min-w-[140px]">{{ __('planning.boms.index.th_process_name') }}</th>
                                <th class="px-2 py-2 text-left min-w-[170px]">{{ __('planning.boms.index.th_machine_name') }}</th>
                                <th class="px-2 py-2 text-left min-w-[160px]">{{ __('planning.boms.index.th_parent_part_no') }}</th>
                                <th class="px-2 py-2 text-left min-w-[190px]">{{ __('planning.boms.index.th_parent_part_name') }}</th>
                                <th class="px-2 py-2 text-right min-w-[120px]">{{ __('planning.boms.index.th_parent_part_qty') }}</th>
                                <th class="px-2 py-2 text-left min-w-[120px]">{{ __('planning.boms.index.th_parent_part_uom') }}</th>
                                <th class="px-2 py-2 text-left min-w-[160px]">{{ __('planning.boms.index.th_child_part_no') }}</th>
                                <th class="px-2 py-2 text-left min-w-[210px]">{{ __('planning.boms.index.th_child_part_name') }}</th>
                                <th class="px-2 py-2 text-center font-bold sticky-col-actions th-sticky-actions min-w-[100px]">{{ __('planning.boms.index.th_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <?php if ($boms->isNotEmpty()): ?>
                            <?php foreach ($boms as $bom): ?>
                                @php
                                    $bomId = (int) $bom->id;
                                    $fgNo = $bom->part->part_no ?? '-';
                                    $fgName = $bom->part->part_name ?? '-';
                                    $fgModel = $bom->part->model ?? '';
                                    $groupNo = ($boms->firstItem() ?? 1) + $loop->index;
                                    $items = ($bom->items ?? collect())->sortBy(fn($i) => $i->line_no ?? 0)->values();
                                @endphp

                                <tr class="parent-row">
                                    <td colspan="13" class="px-3 py-2.5">
                                        <div class="flex min-w-max items-center gap-3">
                                            <button type="button"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 active:translate-y-px"
                                                @click="toggle({{ $bomId }})" aria-label="Toggle BOM lines">
                                                <span x-text="expanded[{{ $bomId }}] ? '−' : '+'" class="text-sm font-bold"></span>
                                            </button>
                                            <span class="font-mono text-xs font-black text-blue-700">{{ $fgNo }}</span>
                                            <span class="text-xs font-semibold text-slate-800">{{ $fgName }}</span>
                                            @if($fgModel)
                                                <span class="text-[10px] font-mono text-slate-500">{{ $fgModel }}</span>
                                            @endif
                                            <span class="text-[10px] font-bold text-slate-500">REV {{ $bom->revision ?? '-' }}</span>
                                            <span class="text-[10px] font-bold {{ $bom->status === 'active' ? 'text-emerald-700' : 'text-slate-500' }}">
                                                {{ strtoupper($bom->status) }}
                                            </span>
                                            <span class="text-[10px] text-slate-500">{{ __('planning.boms.index.lines', ['count' => $items->count()]) }}</span>
                                        </div>
                                    </td>
                                    <td
                                        class="px-2 py-3 text-center whitespace-nowrap sticky-col-actions bg-white border-l border-slate-200 shadow-[-4px_0_6px_-1px_rgba(0,0,0,0.05)]">
                                        <div class="flex items-center justify-center gap-1">
                                            <form action="{{ route('planning.boms.update', $bom) }}" method="POST"
                                                class="inline"
                                                onsubmit='return confirm(@js(__("planning.boms.index.confirm_toggle", ["status" => $bom->status === 'active' ? 'INACTIVE' : 'ACTIVE'])));'>
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status"
                                                    value="{{ $bom->status === 'active' ? 'inactive' : 'active' }}">
                                                <button type="submit" class="action-btn hover:bg-slate-100"
                                                    title="{{ __('planning.boms.index.toggle_title', ['status' => $bom->status === 'active' ? __('planning.boms.index.inactive') : __('planning.boms.index.active')]) }}"
                                                    aria-label="{{ __('planning.boms.index.toggle_title', ['status' => $bom->status === 'active' ? __('planning.boms.index.inactive') : __('planning.boms.index.active')]) }}">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                            </form>
                                            <button type="button" class="action-btn hover:bg-indigo-50 text-indigo-700"
                                                    title="{{ __('planning.boms.index.change_fg_title') }}" @click="openChangeFg(@js([
                                                    'action' => route('planning.boms.update', $bom),
                                                    'part_id' => $bom->part_id,
                                                    'current_label' => ($bom->part?->part_no ?? '-') . ' - ' . ($bom->part?->part_name ?? '-'),
                                                ]))">FG</button>
                                            <form action="{{ route('planning.boms.destroy', $bom) }}" method="POST"
                                                class="inline" onsubmit='return confirm(@js(__("planning.boms.index.confirm_delete_bom")));'>
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn hover:bg-red-50 text-red-600"
                                                    title="{{ __('planning.boms.index.delete_title') }}" aria-label="{{ __('planning.boms.index.delete_title') }}">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Lines --}}
                                <?php if ($items->isNotEmpty()): ?>
                                <?php foreach ($items as $idx => $item): ?>
                                    @php
                                        $lineNo = $item->line_no ?? ($idx + 1);
                                        $wipNo = $item->wip_part_no ?: ($item->wipPart?->part_no ?? '');
                                        $wipName = $item->wip_part_name ?: ($item->wipPart?->part_name ?? '');
                                        $rmNo = $item->component_part_no ?: ($item->componentPart?->part_no ?? '');
                                        $substitutes = $item->substitutes ?? collect();
                                        $subCount = $substitutes->count();
                                    @endphp
                                    <tr class="child-row group" x-show="expanded[{{ $bomId }}]" x-cloak>
                                        <td class="sticky-col-no px-2 py-2 text-center font-mono text-xs font-black text-slate-700">
                                            {{ $groupNo }}
                                        </td>
                                        <td class="px-2 py-2 text-center font-mono text-xs font-black text-blue-700">
                                            {{ $lineNo }}
                                        </td>
                                        <td class="px-2 py-2 text-xs font-semibold text-slate-800">
                                            <span class="block max-w-[220px] truncate" title="{{ $fgName }}">{{ $fgName }}</span>
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap font-mono text-xs text-slate-600">
                                            {{ $fgModel ?: '-' }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap font-mono text-xs font-bold text-slate-900">
                                            {{ $fgNo }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-xs font-semibold text-slate-700">
                                            {{ $item->process_name ?? '' }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-xs text-slate-600">
                                            {{ $item->machine->name ?? '-' }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap bg-blue-50/50 font-mono text-xs font-bold text-blue-800">
                                            {{ $wipNo ?: '-' }}
                                        </td>
                                        <td class="px-2 py-2 bg-blue-50/50 text-xs font-medium text-slate-700">
                                            <span class="block max-w-[190px] truncate" title="{{ $wipName }}">{{ $wipName ?: '-' }}</span>
                                        </td>
                                        <td class="px-2 py-2 text-right whitespace-nowrap bg-blue-50/50 font-mono text-xs font-bold text-blue-800">
                                            {{ $item->wip_qty !== null ? rtrim(rtrim(number_format((float) $item->wip_qty, 4, '.', ''), '0'), '.') : '-' }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap bg-blue-50/50 text-[10px] font-bold uppercase text-slate-600">
                                            {{ $item->wipUom?->code ?? ($item->wip_uom ?? '') }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap font-mono text-xs font-bold text-slate-900">
                                            {{ $rmNo ?: '-' }}
                                        </td>
                                        <td class="px-2 py-2 text-xs text-slate-700">
                                            @php
                                                $policy = $item->consumption_policy_override ?: ($item->componentPart?->consumption_policy ?: (($item->componentPart?->is_backflush ?? true) ? 'backflush_return' : 'direct_issue'));
                                                $policyLabels = [
                                                    'direct_issue' => [__('planning.boms.index.policy_direct'), 'bg-slate-100 text-slate-700 border-slate-200'],
                                                    'backflush_return' => [__('planning.boms.index.policy_backflush'), 'bg-orange-100 text-orange-800 border-orange-200'],
                                                    'backflush_line_stock' => [__('planning.boms.index.policy_line_full'), 'bg-emerald-100 text-emerald-800 border-emerald-200'],
                                                ];
                                                [$policyLabel, $policyClass] = $policyLabels[$policy] ?? ['-', 'bg-slate-100 text-slate-500 border-slate-200'];
                                            @endphp
                                            <span class="block max-w-[210px] truncate font-medium" title="{{ $item->componentPart?->part_name ?? $item->material_name }}">
                                                {{ $item->componentPart?->part_name ?? ($item->material_name ?: '-') }}
                                            </span>
                                            <span class="mt-0.5 block font-mono text-[10px] text-slate-500">
                                                {{ rtrim(rtrim(number_format((float) $item->usage_qty, 4, '.', ''), '0'), '.') }}
                                                {{ $item->consumptionUom?->code ?? ($item->consumption_uom ?? '') }}
                                                <span class="ml-1 font-sans {{ str_contains($policyClass, 'orange') ? 'text-orange-700' : 'text-slate-500' }}">{{ $policyLabel }}</span>
                                            </span>
                                        </td>
                                        <td
                                            class="px-2 py-1.5 text-center whitespace-nowrap sticky-col-actions bg-white border-l border-slate-200 shadow-[-4px_0_6px_-1px_rgba(0,0,0,0.05)]">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button"
                                                    class="relative h-7 px-2 rounded-lg border border-orange-200 bg-orange-50/60 hover:bg-orange-100 text-orange-700 flex items-center justify-center text-[10px] gap-1 font-semibold transition-all"
                                                    title="{{ __('planning.boms.index.subs_title') }}" @click="openSubstitutePanel(@js([
                                                        'bom_item_id' => $item->id,
                                                        'action' => route('planning.bom-items.substitutes.store', $item),
                                                        'store_action' => route('planning.bom-items.substitutes.store', $item),
                                                        'fg_label' => $fgNo . ' - ' . $fgName,
                                                        'line_no' => $lineNo,
                                                        'process_name' => $item->process_name,
                                                        'wip_part_no' => $wipNo,
                                                        'material_name' => $item->material_name,
                                                        'material_spec' => $item->material_spec,
                                                        'component_part_no' => $rmNo,
                                                        'consumption' => $item->usage_qty,
                                                        'consumption_uom' => $item->consumption_uom,
                                                        'substitutes' => $substitutes->sortBy(fn($s) => (int) ($s->priority ?? 1))->map(fn($s) => [
                                                            'id' => $s->id,
                                                            'substitute_part_id' => $s->substitute_part_id,
                                                            'part_no' => $s->part?->part_no,
                                                            'part_name' => $s->part?->part_name,
                                                            'incoming_part_id' => $s->incoming_part_id,
                                                            'incoming_part_no' => $s->incomingPart?->part_no,
                                                            'incoming_part_label' => $s->incomingPart ? ($s->incomingPart->part_no . ($s->incomingPart->vendor ? ' [' . $s->incomingPart->vendor->name . ']' : '')) : null,
                                                            'ratio' => $s->ratio,
                                                            'priority' => $s->priority,
                                                            'status' => $s->status,
                                                            'notes' => $s->notes,
                                                            'update_url' => route('planning.bom-item-substitutes.update', $s),
                                                            'delete_url' => route('planning.bom-item-substitutes.destroy', $s),
                                                        ])->values(),
                                                    ]))">
                                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                    {{ __('planning.boms.index.subs') }}
                                                    @if ($subCount > 0)
                                                        <span
                                                            class="inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-orange-600 text-white text-[9px] font-bold">
                                                            {{ $subCount }}
                                                        </span>
                                                    @endif
                                                </button>
                                                <button type="button" class="action-btn hover:bg-slate-100" title="{{ __('planning.boms.index.edit_title') }}" aria-label="{{ __('planning.boms.index.edit_title') }}" @click="openLineModal(@js([
                                                    'mode' => 'edit',
                                                    'action' => route('planning.boms.items.store', $bom),
                                                    'bom_item_id' => $item->id,
                                                    'fg_label' => $fgNo . ' - ' . $fgName,
                                                    'classification' => $item->componentPart?->classification ?? '',
                                                    'line_no' => $lineNo,
                                                    'process_name' => $item->process_name,
                                                    'machine_id' => $item->machine_id,
                                                    'wip_part_id' => $item->wip_part_id,
                                                    'wip_qty' => $item->wip_qty,
                                                    'wip_uom' => $item->wip_uom,
                                                    'wip_part_name' => $item->wip_part_name,
                                                    'material_size' => $item->material_size,
                                                    'material_spec' => $item->material_spec,
                                                    'material_name' => $item->material_name,
                                                    'special' => $item->special,
                                                    'component_part_id' => $item->component_part_id,
                                                    'component_part_label' => $item->componentPart?->part_no
                                                        ? ($item->componentPart->part_no . ' - ' . ($item->componentPart->part_name ?? '-'))
                                                        : ($item->component_part_no ?: '-'),
                                                    'wip_part_label' => $item->wipPart?->part_no
                                                        ? ($item->wipPart->part_no . ' - ' . ($item->wipPart->part_name ?? '-'))
                                                        : ($item->wip_part_no ?: '-'),
                                                    'incoming_part_id' => $item->incoming_part_id,
                                                    'make_or_buy' => $item->make_or_buy,
                                                    'consumption_policy_override' => $item->consumption_policy_override,
                                                    'usage_qty' => $item->usage_qty,
                                                    'consumption_uom_id' => $item->consumption_uom_id,
                                                    'wip_uom_id' => $item->wip_uom_id,
                                                    'scrap_factor' => $item->scrap_factor,
                                                    'yield_factor' => $item->yield_factor,
                                                ]))">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                                <form action="{{ route('planning.boms.items.destroy', $item) }}" method="POST"
                                                    class="inline" onsubmit='return confirm(@js(__("planning.boms.index.confirm_delete_line")));'>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn hover:bg-red-50 text-red-600"
                                                        title="{{ __('planning.boms.index.delete_title') }}">🗑</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr class="bg-slate-50/50" x-show="expanded[{{ $bomId }}]" x-cloak>
                                        <td colspan="14" class="px-3 py-4 text-center text-slate-500">{{ __('planning.boms.index.empty_lines') }}</td>
                                    </tr>
                                <?php endif; ?>

                                {{-- Add line row --}}
                                <tr class="bg-white" x-show="expanded[{{ $bomId }}]" x-cloak>
                                    <td colspan="14" class="px-3 py-3">
                                        <button type="button"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm"
                                            @click="openLineModal(@js([
                                                'mode' => 'create',
                                                'action' => route('planning.boms.items.store', $bom),
                                                'bom_item_id' => null,
                                                'fg_label' => $fgNo . ' - ' . $fgName,
                                                'line_no' => null,
                                                'process_name' => null,
                                                'machine_id' => null,
                                                'wip_part_id' => null,
                                                'wip_qty' => null,
                                                'wip_uom' => null,
                                                'wip_part_name' => null,
                                                'material_size' => null,
                                                'material_spec' => null,
                                                'material_name' => null,
                                                'special' => null,
                                                'component_part_id' => null,
                                                'component_part_label' => '',
                                                'incoming_part_id' => null,
                                                'make_or_buy' => 'buy',
                                                'consumption_policy_override' => '',
                                                'usage_qty' => 1,
                                                'wip_part_label' => '',
                                            ]))">
                                            {{ __('planning.boms.index.add_line') }}
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="14" class="px-4 py-8 text-center text-slate-500">{{ __('planning.boms.index.empty') }}</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                {{-- Footer --}}
                <div class="border-t border-slate-200 p-4 bg-slate-50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                            <span class="font-semibold">{{ __('planning.boms.index.tip_label') }}</span> {{ __('planning.boms.index.tip_before') }} <span class="font-semibold">{{ __('planning.boms.index.tip_add') }}</span> {{ __('planning.boms.index.tip_after') }}
                            <span class="font-medium text-slate-700">Showing {{ $boms->firstItem() ?? 0 }}–{{ $boms->lastItem() ?? 0 }} of {{ $boms->total() }} BOMs</span>
                        </div>
                        <div aria-label="BOM pagination">{{ $boms->links() }}</div>
                    </div>
                </div>
            </div>

            {{-- Create BOM modal --}}
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
                x-show="modalOpen" x-cloak @keydown.escape.window="closeCreate()">
                <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200/60">
                    <div
                        class="flex items-center justify-between px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-white">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <div class="text-sm font-semibold text-slate-900">{{ __('planning.boms.index.modal_add') }}</div>
                        </div>
                        <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50"
                            @click="closeCreate()">✕</button>
                    </div>

                    <form action="{{ route('planning.boms.store') }}" method="POST" class="px-5 py-4 space-y-4">
                        @csrf
                        <div>
                            <label for="bom-fg-part" class="text-sm font-semibold text-slate-700">{{ __('planning.boms.index.fg_part') }}</label>
                            <select id="bom-fg-part" name="part_id" class="mt-1 w-full rounded-xl border-slate-200" required>
                                <option value="" disabled selected>{{ __('planning.boms.index.select_part') }}</option>
                                @foreach ($fgParts as $p)
                                    <option value="{{ $p->id }}">{{ $p->part_no }} - {{ $p->part_name ?? '-' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="bom-status" class="text-sm font-semibold text-slate-700">{{ __('planning.boms.index.status') }}</label>
                            <select id="bom-status" name="status" class="mt-1 w-full rounded-xl border-slate-200" required>
                                <option value="active" selected>{{ __('planning.boms.index.active') }}</option>
                                <option value="inactive">{{ __('planning.boms.index.inactive') }}</option>
                            </select>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50"
                                @click="closeCreate()">{{ __('planning.boms.index.cancel') }}</button>
                            <button type="submit"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">{{ __('planning.boms.index.create') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Import BOM modal --}}
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
                x-show="importOpen" x-cloak @keydown.escape.window="closeImport()">
                <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200/60">
                    <div
                        class="flex items-center justify-between px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <div class="text-sm font-semibold text-slate-900">{{ __('planning.boms.index.modal_import') }}</div>
                        </div>
                        <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50"
                            @click="closeImport()">✕</button>
                    </div>

                    <form action="{{ route('planning.boms.import') }}" method="POST" enctype="multipart/form-data"
                        class="px-5 py-4 space-y-4" onsubmit='showLoading(@js(__("planning.boms.index.loading_import_bom")))'>
                        @csrf

                        <div class="text-sm text-slate-700 space-y-1">
                            <div>{{ __('planning.boms.index.import_hint_before') }} <span class="font-semibold">{{ __('planning.boms.index.import_hint_export') }}</span>.
                            </div>
                            <div class="text-xs text-slate-500">{{ __('planning.boms.index.import_hint_master_before') }} <span class="font-mono text-indigo-700">FG Part
                                    No.</span> {{ __('planning.boms.index.import_hint_master_after') }}</div>
                        </div>

                        <div>
                            <label for="bom-import-file" class="text-sm font-semibold text-slate-700">{{ __('planning.boms.index.file') }}</label>
                            <input id="bom-import-file" type="file" name="file" accept=".xlsx,.xls,.csv"
                                class="mt-1 w-full rounded-xl border-slate-200" required>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50"
                                @click="closeImport()">{{ __('planning.boms.index.cancel') }}</button>
                            <button type="submit"
                                class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold">{{ __('planning.boms.index.import_btn') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Import Substitute Modal --}}
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
                x-show="importSubstituteOpen" x-cloak @keydown.escape.window="closeImportSubstitute()">
                <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200/60">
                    <div
                        class="flex items-center justify-between px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-orange-50 to-white">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <div class="text-sm font-semibold text-slate-900">{{ __('planning.boms.index.modal_import_sub') }}</div>
                        </div>
                        <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50"
                            @click="closeImportSubstitute()">✕</button>
                    </div>

                    <div class="px-5 pt-4 pb-2 text-xs text-slate-600">
                        {{ __('planning.boms.index.import_mode') }}
                        <span class="font-semibold">{{ __('planning.boms.index.mode_fg') }}</span> {{ __('planning.boms.index.mode_needs') }}
                        <span class="font-semibold">{{ __('planning.boms.index.mode_mapping') }}</span> {{ __('planning.boms.index.mode_apply') }}
                    </div>

                    <div class="px-5 pb-2 flex gap-2">
                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border"
                            :class="subImportMode === 'fg' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                            @click="subImportMode = 'fg'">
                            {{ __('planning.boms.index.mode_fg') }}
                        </button>
                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border"
                            :class="subImportMode === 'mapping' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                            @click="subImportMode = 'mapping'">
                            {{ __('planning.boms.index.mode_mapping') }}
                        </button>
                    </div>

                    <div x-show="subImportMode === 'fg'" x-cloak>
                        <form action="{{ route('planning.boms.substitutes.import') }}" method="POST"
                            enctype="multipart/form-data" class="px-5 py-4 space-y-4"
                            onsubmit='showLoading(@js(__("planning.boms.index.loading_import_sub")))'>
                            @csrf

                            <div class="text-sm text-slate-700 space-y-1">
                                <div class="flex justify-between items-center">
                                    <div>{{ __('planning.boms.index.upload_cols') }}</div>
                                    <a href="{{ route('planning.boms.substitutes.template') }}"
                                        class="text-xs text-indigo-600 hover:text-indigo-800 underline font-semibold">{{ __('planning.boms.index.download_template') }}</a>
                                </div>
                                <div class="font-mono text-xs bg-slate-100 p-2 rounded">fg_part_no, fg_part_name,
                                    component_part_no, component_part_name, substitute_part_no, substitute_part_name,
                                    ratio,
                                    priority, status</div>
                                <div class="text-xs text-slate-500">{{ __('planning.boms.index.match_hint') }}</div>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-slate-700">{{ __('planning.boms.index.file') }}</label>
                                <input type="file" name="file" accept=".xlsx,.xls,.csv"
                                    class="mt-1 w-full rounded-xl border-slate-200" required>
                            </div>

                            <div class="flex items-center gap-2">
                                <input type="hidden" name="auto_create_parts" value="0">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="auto_create_parts" value="1"
                                        class="rounded border-slate-300">
                                    {{ __('planning.boms.index.auto_create') }}
                                </label>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button"
                                    class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50"
                                    @click="closeImportSubstitute()">{{ __('planning.boms.index.cancel') }}</button>
                                <button type="submit"
                                    class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-semibold">{{ __('planning.boms.index.import_substitutes') }}</button>
                            </div>
                        </form>
                    </div>

                    <div x-show="subImportMode === 'mapping'" x-cloak>
                        <form action="{{ route('planning.boms.substitutes.import-mapping') }}" method="POST"
                            enctype="multipart/form-data" class="px-5 py-4 space-y-4"
                            onsubmit='showLoading(@js(__("planning.boms.index.loading_import_mapping")))'>
                            @csrf

                            <div class="text-sm text-slate-700 space-y-1">
                                <div class="flex justify-between items-center">
                                    <div>{{ __('planning.boms.index.upload_mapping') }}</div>
                                    <a href="{{ route('planning.boms.substitutes.template-mapping') }}"
                                        class="text-xs text-indigo-600 hover:text-indigo-800 underline font-semibold">{{ __('planning.boms.index.download_template') }}</a>
                                </div>
                                <div class="font-mono text-xs bg-slate-100 p-2 rounded">component_part_no,
                                    component_part_name, substitute_part_no, substitute_part_name, supplier, ratio,
                                    priority, status</div>
                                <div class="text-xs text-slate-500">{{ __('planning.boms.index.mapping_hint') }}</div>
                            </div>

                            <div class="flex items-center gap-2">
                                <input type="hidden" name="auto_create_parts" value="0">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="auto_create_parts" value="1"
                                        class="rounded border-slate-300">
                                    {{ __('planning.boms.index.auto_create') }}
                                </label>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-slate-700">{{ __('planning.boms.index.file') }}</label>
                                <input type="file" name="file" accept=".xlsx,.xls,.csv"
                                    class="mt-1 w-full rounded-xl border-slate-200" required>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button"
                                    class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50"
                                    @click="closeImportSubstitute()">{{ __('planning.boms.index.cancel') }}</button>
                                <button type="submit"
                                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">{{ __('planning.boms.index.import_mapping') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Line modal --}}
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
                x-show="lineModalOpen" x-cloak @keydown.escape.window="closeLineModal()">
                <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200/60 max-h-[95vh] flex flex-col">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 bg-slate-50/80 flex-shrink-0">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-slate-900"
                                    x-text="lineForm.mode === 'edit' ? '{{ __('planning.boms.index.line_edit') }}' : '{{ __('planning.boms.index.line_add') }}'"></div>
                                <div class="text-[10px] text-slate-500 font-medium" x-text="lineForm.fg_label"></div>
                            </div>
                        </div>
                        <button type="button" class="w-7 h-7 rounded-lg border border-slate-200 hover:bg-slate-100 flex items-center justify-center text-slate-500 transition-colors"
                            @click="closeLineModal()">✕</button>
                    </div>

                    <form :action="lineForm.action" method="POST" class="px-4 py-3 space-y-3 overflow-y-auto flex-1">
                        @csrf
                        <template x-if="lineForm.bom_item_id">
                            <input type="hidden" name="bom_item_id" :value="lineForm.bom_item_id">
                        </template>

                        {{-- ═══ Main: RM Component ═══ --}}
                        <div class="bg-indigo-50/30 rounded-lg border border-indigo-100 p-3 space-y-2">
                            <div class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider">{{ __('planning.boms.index.rm_section') }}</div>

                            <div>
                                <label class="text-[10px] font-semibold text-slate-700">{{ __('planning.boms.index.rm_part') }} <span class="text-red-500">*</span></label>
                                <template x-if="lineForm.mode === 'edit'">
                                    <div>
                                        <input type="hidden" name="component_part_id" :value="lineForm.component_part_id">
                                        <div class="mt-0.5 w-full rounded border border-slate-200 bg-white px-2 py-1 text-xs text-slate-800"
                                            x-text="lineForm.component_part_label || '-'"></div>
                                    </div>
                                </template>
                                <template x-if="lineForm.mode !== 'edit'">
                                    <select name="component_part_id" class="mt-0.5 w-full rounded border-slate-200 text-xs"
                                        x-model="lineForm.component_part_id" required>
                                        <option value="">{{ __('planning.boms.index.select_rm') }}</option>
                                        @foreach (($rmParts ?? []) as $c)
                                            <option value="{{ optional($c)->id }}">{{ optional($c)->part_no }} - {{ optional($c)->part_name ?? '-' }}</option>
                                        @endforeach
                                    </select>
                                </template>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] font-semibold text-slate-700">{{ __('planning.boms.index.qty') }} <span class="text-red-500">*</span></label>
                                    <input type="number" step="any" min="0" name="usage_qty"
                                        class="mt-0.5 w-full rounded border-slate-200 text-xs" required
                                        x-model="lineForm.usage_qty">
                                </div>
                                <div>
                                    <label class="text-[10px] font-semibold text-slate-700">{{ __('planning.boms.index.uom') }}</label>
                                    <select name="consumption_uom_id" class="mt-0.5 w-full rounded border-slate-200 text-xs"
                                        x-model="lineForm.consumption_uom_id">
                                        <option value="">-</option>
                                        @foreach(($uoms ?? []) as $uom)
                                            <option value="{{ optional($uom)->id }}">{{ optional($uom)->code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="text-[10px] font-semibold text-slate-700">{{ __('planning.boms.index.make_buy') }}</label>
                                    <select name="make_or_buy" class="mt-0.5 w-full rounded border-slate-200 text-xs"
                                        x-model="lineForm.make_or_buy">
                                        <option value="buy">BUY</option>
                                        <option value="make">MAKE</option>
                                        <option value="free_issue">FREE ISSUE</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[10px] font-semibold text-slate-700">{{ __('planning.boms.index.policy_override') }}</label>
                                    <select name="consumption_policy_override" class="mt-0.5 w-full rounded border-slate-200 text-xs"
                                        x-model="lineForm.consumption_policy_override">
                                        <option value="">{{ __('planning.boms.index.policy_default') }}</option>
                                        <option value="direct_issue">{{ __('planning.boms.index.policy_direct') }}</option>
                                        <option value="backflush_return">{{ __('planning.boms.index.policy_backflush') }}</option>
                                        <option value="backflush_line_stock">{{ __('planning.boms.index.policy_line_short') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- ═══ Advanced Options (collapsible) ═══ --}}
                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                            <button type="button"
                                class="w-full flex items-center justify-between px-3 py-1.5 bg-slate-50/60 hover:bg-slate-50 transition-colors text-left"
                                @click="lineForm.showAdvanced = !lineForm.showAdvanced">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">{{ __('planning.boms.index.advanced') }}</span>
                                    <span class="text-[9px] text-slate-400 font-medium" x-show="!lineForm.showAdvanced">{{ __('planning.boms.index.adv_open') }}</span>
                                    <span class="text-[9px] text-slate-400 font-medium" x-show="lineForm.showAdvanced">{{ __('planning.boms.index.adv_close') }}</span>
                                </div>
                                <span class="text-slate-500 font-bold text-xs" x-text="lineForm.showAdvanced ? '▾' : '▸'"></span>
                            </button>

                            <div x-show="lineForm.showAdvanced" x-cloak class="p-3 space-y-2 bg-white">
                                {{-- Material --}}
                                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">{{ __('planning.boms.index.material') }}</div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[9px] font-semibold text-slate-600">{{ __('planning.boms.index.size') }}</label>
                                        <input type="text" name="material_size" class="mt-0.5 w-full rounded border-slate-200 text-xs"
                                            x-model="lineForm.material_size" placeholder="0.7 x 530 x C">
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-semibold text-slate-600">{{ __('planning.boms.index.spec') }}</label>
                                        <input type="text" name="material_spec" class="mt-0.5 w-full rounded border-slate-200 text-xs"
                                            x-model="lineForm.material_spec" placeholder="SPCC, SUS304">
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-semibold text-slate-600">{{ __('planning.boms.index.mat_name') }}</label>
                                        <input type="text" name="material_name" class="mt-0.5 w-full rounded border-slate-200 text-xs"
                                            x-model="lineForm.material_name">
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-semibold text-slate-600">{{ __('planning.boms.index.special') }}</label>
                                        <input type="text" name="special" class="mt-0.5 w-full rounded border-slate-200 text-xs"
                                            x-model="lineForm.special">
                                    </div>
                                </div>

                                <hr class="border-slate-100 my-1">

                                {{-- Process & WIP --}}
                                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">{{ __('planning.boms.index.process_wip') }}</div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[9px] font-semibold text-slate-600">{{ __('planning.boms.index.line_no') }}</label>
                                        <input type="number" min="1" name="line_no"
                                            class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.line_no">
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-semibold text-slate-600">{{ __('planning.boms.index.process_name') }}</label>
                                        <input type="text" name="process_name" class="mt-0.5 w-full rounded border-slate-200 text-xs"
                                            x-model="lineForm.process_name">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[9px] font-semibold text-slate-600">{{ __('planning.boms.index.machine') }}</label>
                                        <select name="machine_id" class="mt-0.5 w-full rounded border-slate-200 text-xs"
                                            x-model="lineForm.machine_id">
                                            <option value="">-</option>
                                            @foreach ($machines as $machine)
                                                <option value="{{ $machine->id }}">{{ $machine->code }} - {{ $machine->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-semibold text-slate-600">{{ __('planning.boms.index.wip_part') }}</label>
                                        <template x-if="lineForm.mode === 'edit'">
                                            <div>
                                                <input type="hidden" name="wip_part_id" :value="lineForm.wip_part_id">
                                                <div class="mt-0.5 w-full rounded border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-700 truncate"
                                                    x-text="lineForm.wip_part_label || '-'"></div>
                                            </div>
                                        </template>
                                        <template x-if="lineForm.mode !== 'edit'">
                                            <select name="wip_part_id" class="mt-0.5 w-full rounded border-slate-200 text-xs"
                                                x-model="lineForm.wip_part_id">
                                                <option value="">-</option>
                                                @foreach(($wipParts ?? []) as $p)
                                                    <option value="{{ optional($p)->id }}">{{ optional($p)->part_no }} - {{ optional($p)->part_name ?? '-' }}</option>
                                                @endforeach
                                            </select>
                                        </template>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[9px] font-semibold text-slate-600">{{ __('planning.boms.index.wip_qty') }}</label>
                                        <input type="number" step="any" min="0" name="wip_qty"
                                            class="mt-0.5 w-full rounded border-slate-200 text-xs"
                                            x-model="lineForm.wip_qty">
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-semibold text-slate-600">{{ __('planning.boms.index.wip_uom') }}</label>
                                        <select name="wip_uom_id" class="mt-0.5 w-full rounded border-slate-200 text-xs"
                                            x-model="lineForm.wip_uom_id">
                                            <option value="">-</option>
                                            @foreach (($uoms ?? []) as $uom)
                                                <option value="{{ $uom->id }}">{{ $uom->code }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[9px] font-semibold text-slate-600">{{ __('planning.boms.index.wip_uom_legacy') }}</label>
                                        <input type="text" name="wip_uom" class="mt-0.5 w-full rounded border-slate-200 text-xs"
                                            x-model="lineForm.wip_uom" placeholder="{{ __('planning.boms.index.wip_uom_legacy_ph') }}">
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-semibold text-slate-600">{{ __('planning.boms.index.wip_part_name') }}</label>
                                        <input type="text" name="wip_part_name" class="mt-0.5 w-full rounded border-slate-200 text-xs"
                                            x-model="lineForm.wip_part_name" placeholder="{{ __('planning.boms.index.wip_part_name_ph') }}">
                                    </div>
                                </div>
                                {{-- Hidden fields untuk scrap_factor & yield_factor --}}
                                <input type="hidden" name="scrap_factor" :value="lineForm.scrap_factor">
                                <input type="hidden" name="yield_factor" :value="lineForm.yield_factor">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2 border-t border-slate-200 flex-shrink-0">
                            <button type="button" class="px-3 py-1.5 rounded border border-slate-200 hover:bg-slate-50 text-xs font-medium text-slate-700 transition-colors"
                                @click="closeLineModal()">{{ __('planning.boms.index.cancel') }}</button>
                            <button type="submit"
                                class="px-4 py-1.5 rounded bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition-colors shadow-sm">{{ __('planning.boms.index.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function planningBoms() {
                    return {
                        density: 'compact',
                        modalOpen: false,
                        importOpen: false,
                        importSubstituteOpen: false,
                        subImportMode: 'fg',
                        changeFgOpen: false,
                        changeFgForm: {
                            action: '',
                            current_label: '',
                            part_id: '',
                        },
                        lineModalOpen: false,
                        substituteOpen: false,
                        substituteForm: {
                            action: '',
                            store_action: '',
                            method: 'POST',
                            bom_item_id: null,
                            fg_label: '',
                            line_no: '',
                            process_name: '',
                            wip_part_no: '',
                            material_name: '',
                            material_spec: '',
                            component_part_no: '',
                            consumption: '',
                            consumption_uom: '',
                            substitutes: [],
                            substitute_part_id: '',
                            incoming_part_id: '',
                            ratio: 1,
                            priority: 1,
                            status: 'active',
                            notes: '',
                        },
                        expanded: @js(($boms ?? collect())->getCollection()->pluck('id')->mapWithKeys(fn ($id) => [(string) $id => true])->all()),
                        lineForm: {
                            mode: 'create',
                            action: '',
                            bom_item_id: null,
                            fg_label: '',
                            classification: '',
                            line_no: null,
                            process_name: '',
                            machine_id: '',
                            wip_part_id: '',
                            wip_qty: '',
                            wip_uom: '',
                            wip_part_name: '',
                            material_size: '',
                            material_spec: '',
                            material_name: '',
                            special: '',
                            component_part_id: '',
                            component_part_label: '',
                            incoming_part_id: '',
                            make_or_buy: 'buy',
                            consumption_policy_override: '',
                            usage_qty: '1',
                            consumption_uom_id: '',
                            wip_uom_id: '',
                            scrap_factor: 0,
                            yield_factor: 1,
                            wip_part_label: '',
                            showAdvanced: false,
                        },
                        openCreate() { this.modalOpen = true; },
                        closeCreate() { this.modalOpen = false; },
                        openImport() { this.importOpen = true; },
                        closeImport() { this.importOpen = false; },
                        openImportSubstitute() { this.importSubstituteOpen = true; },
                        closeImportSubstitute() { this.importSubstituteOpen = false; this.subImportMode = 'fg'; },
                        openChangeFg(payload) {
                            this.changeFgForm = {
                                action: payload.action,
                                current_label: payload.current_label ?? '',
                                part_id: payload.part_id ?? '',
                            };
                            this.changeFgOpen = true;
                        },
                        closeChangeFg() { this.changeFgOpen = false; },
                        toggle(id) { this.expanded[id] = !this.expanded[id]; },
                        openSubstitutePanel(payload) {
                            this.substituteForm = {
                                action: payload.action,
                                store_action: payload.store_action ?? payload.action,
                                method: 'POST',
                                bom_item_id: payload.bom_item_id,
                                fg_label: payload.fg_label,
                                line_no: payload.line_no,
                                process_name: payload.process_name ?? '',
                                wip_part_no: payload.wip_part_no ?? '',
                                material_name: payload.material_name ?? '',
                                material_spec: payload.material_spec ?? '',
                                component_part_no: payload.component_part_no ?? '',
                                consumption: payload.consumption ?? '',
                                consumption_uom: payload.consumption_uom ?? '',
                                substitutes: payload.substitutes ?? [],
                                substitute_part_id: '',
                                incoming_part_id: '',
                                ratio: 1,
                                priority: 1,
                                status: 'active',
                                notes: '',
                            };
                            this.substituteOpen = true;
                        },
                        closeSubstitutePanel() { this.substituteOpen = false; },
                        editSubstitute(s) {
                            this.substituteForm.action = s.update_url;
                            this.substituteForm.method = 'PUT';
                            this.substituteForm.substitute_part_id = String(s.substitute_part_id || '');
                            this.substituteForm.incoming_part_id = s.incoming_part_id ? String(s.incoming_part_id) : '';
                            this.substituteForm.ratio = s.ratio || 1;
                            this.substituteForm.priority = s.priority || 1;
                            this.substituteForm.status = s.status || 'active';
                            this.substituteForm.notes = s.notes || '';
                        },
                        resetSubstituteForm() {
                            this.substituteForm.action = this.substituteForm.store_action || this.substituteForm.action;
                            this.substituteForm.method = 'POST';
                            this.substituteForm.substitute_part_id = '';
                            this.substituteForm.incoming_part_id = '';
                            this.substituteForm.ratio = 1;
                            this.substituteForm.priority = 1;
                            this.substituteForm.status = 'active';
                            this.substituteForm.notes = '';
                        },
                        openLineModal(payload) {
                            this.lineForm = {
                                mode: payload.mode,
                                action: payload.action,
                                bom_item_id: payload.bom_item_id,
                                fg_label: payload.fg_label,
                                classification: payload.classification ?? '',
                                line_no: payload.line_no,
                                process_name: payload.process_name ?? '',
                                machine_id: payload.machine_id ?? '',
                                wip_part_id: payload.wip_part_id ?? '',
                                wip_part_label: payload.wip_part_label ?? '',
                                wip_qty: payload.wip_qty ?? '',
                                wip_uom: payload.wip_uom ?? '',
                                wip_part_name: payload.wip_part_name ?? '',
                                material_size: payload.material_size ?? '',
                                material_spec: payload.material_spec ?? '',
                                material_name: payload.material_name ?? '',
                                special: payload.special ?? '',
                                component_part_id: payload.component_part_id ?? '',
                                component_part_label: payload.component_part_label ?? '',
                                incoming_part_id: payload.incoming_part_id ?? '',
                                make_or_buy: payload.make_or_buy ?? 'buy',
                                consumption_policy_override: payload.consumption_policy_override ?? '',
                                usage_qty: payload.usage_qty ?? '1',
                                consumption_uom_id: payload.consumption_uom_id ?? '',
                                wip_uom_id: payload.wip_uom_id ?? '',
                                scrap_factor: payload.scrap_factor ?? 0,
                                yield_factor: payload.yield_factor ?? 1,
                            };
                            this.lineModalOpen = true;
                        },
                        closeLineModal() { this.lineModalOpen = false; },
                    }
                }
            </script>

            {{-- Change FG modal --}}
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
                x-show="changeFgOpen" x-cloak @keydown.escape.window="closeChangeFg()">
                <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200/60">
                    <div
                        class="flex items-center justify-between px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-white">
                        <div>
                            <div class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-indigo-100 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </div>
                                {{ __('planning.boms.index.change_fg') }}
                            </div>
                            <div class="text-xs text-slate-500 mt-1" x-text="changeFgForm.current_label"></div>
                        </div>
                        <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50"
                            @click="closeChangeFg()">✕</button>
                    </div>

                    <form :action="changeFgForm.action" method="POST" class="px-5 py-4 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="text-xs font-semibold text-slate-600">{{ __('planning.boms.index.new_fg') }}</label>
                            <select name="part_id" class="mt-1 w-full rounded-xl border-slate-200" required
                                x-model="changeFgForm.part_id">
                                <option value="" disabled>{{ __('planning.boms.index.select_fg') }}</option>
                                @foreach(($fgParts ?? []) as $p)
                                    <option value="{{ optional($p)->id }}">{{ optional($p)->part_no }} -
                                        {{ optional($p)->part_name ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-2 text-xs text-slate-500">
                                {{ __('planning.boms.index.change_note_before') }} <span
                                    class="font-semibold">FG</span>
                                {{ __('planning.boms.index.change_note_after') }}
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50"
                                @click="closeChangeFg()">{{ __('planning.boms.index.cancel') }}</button>
                            <button type="submit"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">{{ __('planning.boms.index.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Substitute drawer --}}
            <div class="fixed inset-0 z-50" x-show="substituteOpen" x-cloak>
                <div class="absolute inset-0 bg-slate-900/40" @click="closeSubstitutePanel()"></div>
                <div
                    class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl border-l border-slate-200 overflow-y-auto">
                    <div class="sticky top-0 bg-gradient-to-r from-orange-600 to-orange-700 text-white px-5 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold">{{ __('planning.boms.index.drawer_title') }}</div>
                                <div class="text-xs text-orange-100" x-text="substituteForm.fg_label"></div>
                            </div>
                            <button type="button" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20"
                                @click="closeSubstitutePanel()">✕</button>
                        </div>
                    </div>

                    <div class="p-5 space-y-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                            <div class="font-semibold text-slate-900">{{ __('planning.boms.index.drawer_bom_line') }}</div>
                            <div class="mt-2 space-y-1 text-slate-700">
                                <div><span class="text-slate-500">{{ __('planning.boms.index.drawer_line') }}</span> <span class="font-mono"
                                        x-text="substituteForm.line_no"></span></div>
                                <div><span class="text-slate-500">{{ __('planning.boms.index.drawer_process') }}</span> <span
                                        x-text="substituteForm.process_name"></span></div>
                                <div><span class="text-slate-500">{{ __('planning.boms.index.drawer_wip') }}</span> <span class="font-mono"
                                        x-text="substituteForm.wip_part_no"></span></div>
                                <div><span class="text-slate-500">{{ __('planning.boms.index.drawer_rm') }}</span> <span class="font-mono font-semibold"
                                        x-text="substituteForm.component_part_no"></span></div>
                                <div><span class="text-slate-500">{{ __('planning.boms.index.drawer_material') }}</span> <span class="font-semibold"
                                        x-text="substituteForm.material_name"></span></div>
                                <div><span class="text-slate-500">{{ __('planning.boms.index.drawer_spec') }}</span> <span
                                        x-text="substituteForm.material_spec"></span></div>
                                <div><span class="text-slate-500">{{ __('planning.boms.index.drawer_consumption') }}</span> <span class="font-mono"
                                        x-text="substituteForm.consumption"></span> <span class="font-mono"
                                        x-text="substituteForm.consumption_uom"></span></div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-orange-200 bg-orange-50 p-3 text-xs text-orange-900">
                            {{ __('planning.boms.index.drawer_hint') }}
                        </div>

                        <div class="space-y-2">
                            <div class="text-xs font-semibold text-slate-700 uppercase tracking-wider">{{ __('planning.boms.index.drawer_existing') }}
                            </div>
                            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                                <table class="min-w-full text-xs divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr class="text-slate-600 uppercase tracking-wider">
                                            <th class="px-3 py-2 text-left font-semibold">{{ __('planning.boms.index.drawer_th_part') }}</th>
                                            <th class="px-3 py-2 text-left font-semibold">{{ __('planning.boms.index.drawer_th_incoming') }}</th>
                                            <th class="px-3 py-2 text-right font-semibold">{{ __('planning.boms.index.drawer_th_ratio') }}</th>
                                            <th class="px-3 py-2 text-right font-semibold">{{ __('planning.boms.index.drawer_th_prio') }}</th>
                                            <th class="px-3 py-2 text-left font-semibold">{{ __('planning.boms.index.drawer_th_status') }}</th>
                                            <th class="px-3 py-2 text-right font-semibold">{{ __('planning.boms.index.drawer_th_act') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-if="(substituteForm.substitutes || []).length === 0">
                                            <tr>
                                                <td colspan="6" class="px-3 py-4 text-center text-slate-500">{{ __('planning.boms.index.drawer_empty') }}
                                                </td>
                                            </tr>
                                        </template>
                                        <template x-for="s in (substituteForm.substitutes || [])" :key="s.id">
                                            <tr>
                                                <td class="px-3 py-2">
                                                    <div class="font-mono text-[11px] font-semibold"
                                                        x-text="s.part_no || '-'">
                                                    </div>
                                                    <div class="text-slate-500 truncate" x-text="s.part_name || ''">
                                                    </div>
                                                    <div class="text-slate-400 truncate" x-text="s.notes || ''"></div>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <template x-if="s.incoming_part_no">
                                                        <div>
                                                            <div class="font-mono text-[10px] font-bold text-teal-700"
                                                                x-text="s.incoming_part_no"></div>
                                                            <div class="text-[9px] text-slate-400 truncate"
                                                                x-text="s.incoming_part_label || ''"></div>
                                                        </div>
                                                    </template>
                                                    <template x-if="!s.incoming_part_no">
                                                        <span class="text-slate-300">-</span>
                                                    </template>
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono" x-text="s.ratio"></td>
                                                <td class="px-3 py-2 text-right font-mono" x-text="s.priority"></td>
                                                <td class="px-3 py-2">
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                                        :class="(s.status || 'active') === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'"
                                                        x-text="(s.status || 'active').toUpperCase()"></span>
                                                </td>
                                                <td class="px-3 py-2 text-right">
                                                    <button type="button"
                                                        class="w-9 h-9 inline-flex items-center justify-center rounded-xl border border-slate-200 hover:bg-indigo-50 text-indigo-600 mr-1"
                                                        title="{{ __('planning.boms.index.edit_title') }}" @click="editSubstitute(s)">✎</button>
                                                    <form :action="s.delete_url" method="POST" class="inline-block"
                                                        onsubmit='return confirm(@js(__("planning.boms.index.confirm_remove_sub")));'>
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="w-9 h-9 inline-flex items-center justify-center rounded-xl border border-slate-200 hover:bg-red-50 text-red-600"
                                                            title="{{ __('planning.boms.index.delete_title') }}" aria-label="{{ __('planning.boms.index.delete_title') }}">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-xs font-semibold text-slate-700 uppercase tracking-wider">{{ __('planning.boms.index.drawer_form') }}
                                    Substitute</div>
                                <button type="button"
                                    class="text-[11px] font-semibold text-slate-500 hover:text-slate-700"
                                    x-show="substituteForm.method === 'PUT'" @click="resetSubstituteForm()">{{ __('planning.boms.index.drawer_cancel_edit') }}</button>
                            </div>
                            <form :action="substituteForm.action" method="POST"
                                class="rounded-xl border border-slate-200 p-4 space-y-3 bg-white">
                                @csrf
                                <template x-if="substituteForm.method === 'PUT'">
                                    <input type="hidden" name="_method" value="PUT">
                                </template>
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">{{ __('planning.boms.index.drawer_sub_part') }}</label>
                                    <select name="substitute_part_id" class="mt-1 w-full rounded-xl border-slate-200"
                                        required x-model="substituteForm.substitute_part_id">
                                        <option value="" disabled>{{ __('planning.boms.index.select_part') }}</option>
                                        @foreach(($rmParts ?? []) as $p)
                                            <option value="{{ optional($p)->id }}">{{ optional($p)->part_no }} -
                                                {{ optional($p)->part_name ?? '-' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">{{ __('planning.boms.index.drawer_incoming') }}</label>
                                    <select name="incoming_part_id" class="mt-1 w-full rounded-xl border-slate-200"
                                        x-model="substituteForm.incoming_part_id">
                                        <option value="">{{ __('planning.boms.index.drawer_no_incoming') }}</option>
                                        @foreach(($incomingParts ?? []) as $ip)
                                            <option value="{{ $ip->id }}">
                                                {{ $ip->part_no }} -
                                                {{ $ip->part_name_gci ?: ($ip->part_name_vendor ?? '-') }}
                                                @if($ip->vendor) [{{ $ip->vendor->name }}] @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="text-[10px] text-slate-500 mt-0.5">{{ __('planning.boms.index.drawer_link_hint') }}
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600">{{ __('planning.boms.index.drawer_ratio') }}</label>
                                        <input type="number" step="0.001" min="0.001" name="ratio"
                                            class="mt-1 w-full rounded-xl border-slate-200"
                                            x-model="substituteForm.ratio">
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600">{{ __('planning.boms.index.drawer_priority') }}</label>
                                        <input type="number" min="1" name="priority"
                                            class="mt-1 w-full rounded-xl border-slate-200"
                                            x-model="substituteForm.priority">
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600">{{ __('planning.boms.index.drawer_status') }}</label>
                                        <select name="status" class="mt-1 w-full rounded-xl border-slate-200"
                                            x-model="substituteForm.status">
                                            <option value="active">{{ __('planning.boms.index.active') }}</option>
                                            <option value="inactive">{{ __('planning.boms.index.inactive') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">{{ __('planning.boms.index.drawer_notes') }}</label>
                                    <input type="text" name="notes" maxlength="255"
                                        class="mt-1 w-full rounded-xl border-slate-200"
                                        x-model="substituteForm.notes"
                                        placeholder="{{ __('planning.boms.index.drawer_notes_ph') }}">
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit"
                                        class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-semibold"
                                        x-text="substituteForm.method === 'PUT' ? '{{ __('planning.boms.index.drawer_update') }}' : '{{ __('planning.boms.index.drawer_save') }}'">{{ __('planning.boms.index.drawer_save') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</x-app-layout>
