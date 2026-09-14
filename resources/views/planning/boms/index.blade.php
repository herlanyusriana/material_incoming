<x-app-layout>
    <x-slot name="header">
        {{ __('planning.boms.index.header') }}
    </x-slot>

    <div class="bom-workspace py-2 min-w-0" x-data="planningBoms()" @resize.window="syncSheet()">
        <div class="w-full min-w-0 space-y-5">
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
                [x-cloak] { display: none !important; }
                .bom-scroll-tools { display:flex; align-items:center; gap:12px; flex-wrap:wrap; padding:12px 16px; background:#f8fafc; border-bottom:1px solid #cbd5e1; }
                .bom-scroll-tools button { padding:8px 14px; border:1px solid #cbd5e1; border-radius:8px; background:white; font-size:14px; font-weight:600; }
                .bom-scroll-tools button:disabled { opacity:.4; cursor:default; }
                .bom-scroll-tools input { flex:1; min-width:120px; accent-color:#4f46e5; }
                .bom-sheet { overflow:auto; max-height:70vh; scrollbar-width:auto; scrollbar-color:#64748b #e2e8f0; }
                .bom-sheet::-webkit-scrollbar { width:14px; height:14px; }
                .bom-sheet::-webkit-scrollbar-thumb { background:#64748b; border:3px solid #e2e8f0; border-radius:8px; }
                .bom-sheet::-webkit-scrollbar-track { background:#e2e8f0; }
                .bom-table { border-collapse:separate; border-spacing:0; table-layout:fixed; width:100%; min-width:1840px; }
                .bom-table th,.bom-table td { border-bottom:1px solid #e2e8f0; padding:12px; vertical-align:top; white-space:normal; overflow-wrap:break-word; font-size:14px; line-height:1.5; }
                .bom-table th { position:sticky; top:0; z-index:20; background:#e9eef5; color:#334155; font-weight:700; text-transform:none; letter-spacing:0; }
                .bom-table .child-row { background:white; }
                .bom-table .child-row:hover { background:#f0f5ff; }
                .bom-table .child-row td:first-child { position:sticky; left:0; background:#f8fafc; z-index:10; }
                .bom-table .sticky-col-actions { position:sticky; right:0; background:white; z-index:15; box-shadow:-3px 0 5px #0f172a0a; }
                .bom-table .th-sticky-actions { z-index:30; background:#e9eef5; }
                .bom-table .parent-row { background:#edf2fa; }
                .bom-table .parent-row td { border-top:2px solid #cbd5e1; padding:14px 12px; }
                .bom-table .parent-row td > div { gap:12px; }
                .bom-table .truncate { white-space:normal; max-width:none; overflow:visible; text-overflow:clip; }
                .bom-table .text-\[10px\],.bom-table .text-\[9px\] { font-size:12px; }
                .bom-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(460px, 1fr)); gap:14px; }
                .bom-card { display:flex; flex-direction:column; min-width:0; }
                
                .bom-tree-body { border-top:1px solid #e2e8f0; }
                .bom-node:hover { background:#f8fafc; }
                .action-btn { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border:1px solid #cbd5e1; border-radius:8px; background:white; flex-shrink:0; }
                .action-btn:hover { background:#eef2ff; }
                .bom-table .sticky-col-actions button { min-height:36px; }
                #bom-gci { width:100%; max-width:340px; }
                .bom-missing { color:#92400e; font-size:12px; font-weight:500; }
                .bom-workspace :is(button,a,input,select):focus-visible { outline:2px solid #4f46e5; outline-offset:3px; }
                .bom-workspace [role=dialog] { overscroll-behavior:contain; }
                .bom-workspace > div > .fixed > div { max-height:calc(100dvh - 32px); overflow-y:auto; }
                .bom-workspace > div > .fixed > .bom-editor { overflow:hidden; }
                .bom-editor { width:min(1160px,100%); max-height:calc(100dvh - 32px); display:flex; flex-direction:column; overflow:hidden; }
                .bom-editor form { min-height:0; }
                .bom-editor-body { padding:24px; overflow:auto; display:grid; grid-template-columns:1fr 1fr; gap:24px; align-items:start; }
                .bom-editor-body > div { min-width:0; }
                .bom-material-fields { grid-column:1; }
                .bom-editor-body > div:last-child { grid-column:2; grid-row:1 / span 2; }
                .bom-editor-body:has(#bom-line-errors) > div:last-child { grid-row:2 / span 2; }
                .bom-table .parent-row td:first-child > div { position:sticky; left:12px; width:max-content; max-width:calc(100vw - 240px); flex-wrap:wrap; }
                .bom-editor label { display:block; font-size:14px !important; margin-bottom:6px; color:#334155; }
                .bom-editor input:not([type=hidden]),.bom-editor select { min-height:42px; font-size:14px; border-radius:8px; }
                .bom-editor .text-\[9px\],.bom-editor .text-\[10px\] { font-size:13px; }
                .bom-editor .grid { gap:16px; }
                .bom-editor-footer { padding:16px 24px; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:12px; background:#f8fafc; }
                .bom-editor-footer button { min-height:42px; padding:10px 20px; font-size:14px; border-radius:8px; }
                @media (max-width:768px) {
                    .bom-editor { max-height:100dvh; border-radius:0; }
                    .bom-editor-body { grid-template-columns:1fr; padding:16px; }
                    .bom-editor-body > div:last-child { grid-column:auto; grid-row:auto; }
                    .bom-editor-body:has(#bom-line-errors) > div:last-child { grid-row:auto; }
                    .bom-table .sticky-col-actions { position:static; }
                    .bom-scroll-tools { gap:8px; }
                }
                @media (prefers-reduced-motion:reduce) { .bom-workspace * { scroll-behavior:auto !important; animation:none !important; } }
            </style>

            <div class="bg-white border-y border-slate-200">
                {{-- Header --}}
                <div class="p-4 border-b border-slate-100">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-3">
                            <div
                                class="h-12 w-12 rounded-xl bg-slate-900 flex items-center justify-center text-white font-black text-sm">
                                BOM
                            </div>
                            <div>
                                <div class="text-xl font-semibold text-slate-900">{{ __('planning.boms.index.title') }}</div>
                                <div class="mt-1 text-sm text-slate-500">
                                    {{ __('planning.boms.index.subtitle') }}
                                    <span
                                        class="ml-2 px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-[10px] font-bold uppercase tracking-wider border border-indigo-100">
                                        {{ __('planning.boms.index.count', ['count' => $boms->count()]) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 shadow-sm"
                                @click="openCreate()">
                                {{ __('planning.boms.index.add') }}
                            </button>
                        </div>
                    </div>

                    {{-- Toolbar --}}
                    <div class="mt-4 flex flex-wrap gap-3 items-end border-t border-slate-100 pt-4">
                        <div class="w-full max-w-md">
                            <label for="bom-search"
                                class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('planning.boms.index.search_label') }}</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input id="bom-search" type="search" x-model="bomQuery" @input="bomPage = 1"
                                    autocomplete="off" spellcheck="false"
                                    class="w-full pl-9 pr-9 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="{{ __('planning.boms.index.search_ph') }}">
                                <button type="button" x-show="bomQuery" x-cloak @click="bomQuery = ''; bomPage = 1"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                    aria-label="Clear search">&times;</button>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('planning.boms.index') }}">
                            <label for="bom-gci"
                                class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">{{ __('planning.boms.index.gci_label') }}</label>
                            <select id="bom-gci" name="gci_part_id" @change="$el.form.submit()"
                                class="rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('planning.boms.index.all_gci') }}</option>
                                @foreach ($fgParts as $p)
                                    <option value="{{ $p->id }}" @selected((string) ($gciPartId ?? '') === (string) $p->id)>
                                        {{ $p->part_no }} - {{ $p->part_name ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        <details class="relative ml-auto" @keydown.escape="$el.open = false" @click.outside="$el.open = false">
                            <summary class="cursor-pointer rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Tools</summary>
                            <div class="absolute right-0 z-50 mt-2 flex w-72 max-w-[80vw] flex-wrap gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
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
                        </details>
                    </div>
                </div>

                {{-- BOM explorer: card grid + accordion tree --}}
                <div class="border-t border-slate-200 bg-slate-50/60">
                    <div class="flex flex-wrap items-center gap-3 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <button type="button" @click="setAllDetails(true)"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                ⊞ Expand all
                            </button>
                            <button type="button" @click="setAllDetails(false)"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                                ⊟ Collapse all
                            </button>
                        </div>
                        <span class="font-mono text-xs text-slate-500"
                            x-text="`${filteredBoms.length} / {{ $boms->count() }} BOM`"></span>
                        <div class="ml-auto flex items-center gap-2 text-xs text-slate-500" aria-hidden="true">
                            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-indigo-600"></span>FG</span>
                            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-amber-500"></span>WIP</span>
                            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></span>RM</span>
                        </div>
                    </div>
                </div>

                <div id="bom-grid" class="bom-grid px-4 pb-4">
                    @forelse ($boms as $bomIndex => $bom)
                        @php
                            $bomId = (int) $bom->id;
                            $fgNo = $bom->part->part_no ?? '-';
                            $fgName = $bom->part->part_name ?? '-';
                            $fgModel = $bom->part->model ?? '';
                            $items = ($bom->items ?? collect())->sortBy(fn($i) => $i->line_no ?? 0)->values();
                        @endphp

                        <article class="bom-card border border-slate-200 bg-white rounded-xl shadow-sm"
                            x-show="isBomVisible({{ $bomId }})" x-cloak>
                            <div class="bom-tree">
                                <div class="flex flex-wrap items-center gap-2 px-4 py-3 cursor-pointer select-none border-l-4 border-indigo-600 hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-indigo-500"
                                    role="button" tabindex="0" @click="toggle({{ $bomId }})"
                                    @keydown.enter.self="toggle({{ $bomId }})" @keydown.space.prevent.self="toggle({{ $bomId }})"
                                    :aria-expanded="!!expanded[{{ $bomId }}]">
                                    <svg class="w-4 h-4 text-slate-400 transition-transform"
                                        :class="expanded[{{ $bomId }}] ? 'rotate-90' : ''" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                    <span class="font-mono text-sm font-black text-indigo-700">{{ $fgNo }}</span>
                                    <span class="text-sm font-semibold text-slate-800">{{ $fgName }}</span>
                                    @if ($fgModel)
                                        <span class="text-[10px] font-mono text-slate-500">{{ $fgModel }}</span>
                                    @endif
                                    <span class="text-[10px] font-bold text-slate-500">REV {{ $bom->revision ?? '-' }}</span>
                                    <span
                                        class="text-[10px] font-bold uppercase {{ $bom->status === 'active' ? 'text-emerald-700' : 'text-slate-500' }}">
                                        {{ $bom->status }}</span>
                                    <span
                                        class="text-[10px] text-slate-500">{{ __('planning.boms.index.lines', ['count' => $items->count()]) }}</span>

                                    <div class="ml-auto flex items-center gap-1" @click.stop @keydown.stop>
                                        <form action="{{ route('planning.boms.update', $bom) }}" method="POST" class="inline"
                                            onsubmit='return confirm(@js(__("planning.boms.index.confirm_toggle", ["status" => $bom->status === 'active' ? 'INACTIVE' : 'ACTIVE'])));'>
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="{{ $bom->status === 'active' ? 'inactive' : 'active' }}">
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
                                        <form action="{{ route('planning.boms.destroy', $bom) }}" method="POST" class="inline"
                                            onsubmit='return confirm(@js(__("planning.boms.index.confirm_delete_bom")));'>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="action-btn hover:bg-red-50 text-red-600"
                                                title="{{ __('planning.boms.index.delete_title') }}"
                                                aria-label="{{ __('planning.boms.index.delete_title') }}">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="bom-tree-body border-t border-slate-100" x-show="expanded[{{ $bomId }}]" x-cloak>
                                    @if ($items->isNotEmpty())
                                        <div class="divide-y divide-slate-100">
                                            @foreach ($items as $idx => $item)
                                                @php
                                                    $lineNo = $item->line_no ?? ($idx + 1);
                                                    $wipNo = $item->wip_part_no ?: ($item->wipPart?->part_no ?? '');
                                                    $wipName = trim((string) $item->wip_part_name) ?: trim((string) $item->wipPart?->part_name);
                                                    $rmNo = $item->component_part_no ?: ($item->componentPart?->part_no ?? '');
                                                    $rmName = trim((string) $item->componentPart?->part_name)
                                                        ?: (trim((string) $item->material_name)
                                                        ?: trim((string) $item->incomingPart?->vendor_part_name));
                                                    $wipName = trim((string) $wipName);
                                                    $substitutes = $item->substitutes ?? collect();
                                                    $subCount = $substitutes->count();
                                                    $policy = $item->consumption_policy_override ?: ($item->componentPart?->consumption_policy ?: (($item->componentPart?->is_backflush ?? true) ? 'backflush_return' : 'direct_issue'));
                                                    $policyLabels = [
                                                        'direct_issue' => [__('planning.boms.index.policy_direct'), 'bg-slate-100 text-slate-700 border-slate-200'],
                                                        'backflush_return' => [__('planning.boms.index.policy_backflush'), 'bg-orange-100 text-orange-800 border-orange-200'],
                                                        'backflush_line_stock' => [__('planning.boms.index.policy_line_full'), 'bg-emerald-100 text-emerald-800 border-emerald-200'],
                                                    ];
                                                    [$policyLabel, $policyClass] = $policyLabels[$policy] ?? ['-', 'bg-slate-100 text-slate-500 border-slate-200'];
                                                    $mob = strtolower((string) ($item->make_or_buy ?? ''));
                                                    $mobStyles = [
                                                        'make' => 'bg-sky-100 text-sky-800 border-sky-200',
                                                        'buy' => 'bg-slate-100 text-slate-700 border-slate-200',
                                                        'subcon' => 'bg-violet-100 text-violet-800 border-violet-200',
                                                        'free_issue' => 'bg-rose-100 text-rose-800 border-rose-200',
                                                    ];
                                                    $mobClass = $mobStyles[$mob] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                                                @endphp
                                                <div class="bom-node group/line px-4 py-3">
                                                    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                                        <span class="font-mono text-[11px] font-black text-slate-400">#{{ $lineNo }}</span>
                                                        <span class="text-xs font-semibold text-slate-700">{{ $item->process_name ?? '—' }}</span>
                                                        <span class="text-[11px] text-slate-400">⚙ {{ $item->machine->name ?? '-' }}</span>
                                                        <span class="ml-auto inline-flex items-center gap-1">
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
                                                                        'part_no' => $s->substitutePart?->part_no,
                                                                        'part_name' => $s->substitutePart?->part_name,
                                                                        'vendor_part_id' => $s->vendor_part_id,
                                                                        'incoming_part_no' => $s->vendorPart?->part_no,
                                                                        'incoming_part_label' => $s->vendorPart ? ($s->vendorPart->part_no . ($s->vendorPart->vendor ? ' [' . $s->vendorPart->vendor->name . ']' : '')) : null,
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
                                                                    <span class="inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-orange-600 text-white text-[9px] font-bold">{{ $subCount }}</span>
                                                                @endif
                                                            </button>
                                                            <button type="button" class="action-btn hover:bg-slate-100"
                                                                title="{{ __('planning.boms.index.edit_title') }}" aria-label="{{ __('planning.boms.index.edit_title') }}" @click="openLineModal(@js([
                                                                    'mode' => 'edit',
                                                                    'action' => route('planning.boms.items.store', $bom),
                                                                    'bom_id' => $bom->id,
                                                                    'bom_item_id' => $item->id,
                                                                    'fg_label' => $fgNo . ' - ' . $fgName,
                                                                    'classification' => $item->componentPart?->classification ?? '',
                                                                    'line_no' => $lineNo,
                                                                    'process_name' => $item->process_name,
                                                                    'machine_id' => $item->machine_id,
                                                                    'wip_part_id' => $item->wip_part_id,
                                                                    'wip_qty' => $item->wip_qty,
                                                                    'wip_uom' => $item->display_wip_uom,
                                                                    'wip_part_no' => $wipNo,
                                                                    'component_part_no' => $rmNo,
                                                                    'wip_part_name' => $wipName,
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
                                                                    'consumption_uom_id' => $item->consumption_uom_id ?: $uoms->firstWhere('code', $item->display_consumption_uom)?->id,
                                                                    'wip_uom_id' => $item->wip_uom_id ?: $uoms->firstWhere('code', $item->display_wip_uom)?->id,
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
                                                        </span>
                                                    </div>

                                                    <div class="mt-2.5 grid grid-cols-1 md:grid-cols-2 gap-2">
                                                        {{-- WIP node --}}
                                                        <div class="rounded-lg border border-amber-200 bg-amber-50/40 px-3 py-2">
                                                            <div class="text-[10px] font-bold uppercase tracking-wider text-amber-700">WIP</div>
                                                            <div class="mt-0.5 font-mono text-xs font-bold text-amber-900">{{ $wipNo ?: '-' }}</div>
                                                            <div class="text-xs text-amber-800/90">{{ $wipName ?: 'Nama parent belum diisi' }}</div>
                                                            <div class="mt-1 font-mono text-xs text-amber-800">
                                                                {{ $item->wip_qty !== null ? rtrim(rtrim(number_format((float) $item->wip_qty, 4, '.', ''), '0'), '.') : '-' }}
                                                                <span class="ml-1 text-[10px] font-bold uppercase">{{ $item->display_wip_uom ?: '—' }}</span>
                                                            </div>
                                                        </div>

                                                        {{-- RM node --}}
                                                        <div class="rounded-lg border border-emerald-200 bg-emerald-50/40 px-3 py-2">
                                                            <div class="flex items-center gap-2">
                                                                <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">RM</div>
                                                                <span class="ml-auto inline-flex items-center rounded border px-1.5 py-0.5 text-[10px] font-semibold {{ $policyClass }}">{{ $policyLabel }}</span>
                                                                <span class="inline-flex items-center rounded border px-1.5 py-0.5 text-[10px] font-semibold uppercase {{ $mobClass }}">{{ strtoupper($item->make_or_buy ?: 'buy') }}</span>
                                                            </div>
                                                            <div class="mt-0.5 font-mono text-xs font-bold text-emerald-900">{{ $rmNo ?: '-' }}</div>
                                                            <div class="text-xs text-emerald-800/90">{{ $rmName ?: '—' }}</div>
                                                            <div class="mt-1 font-mono text-xs text-emerald-800">
                                                                {{ rtrim(rtrim(number_format((float) $item->usage_qty, 4, '.', ''), '0'), '.') }}
                                                                <span class="ml-1 text-[10px] font-bold uppercase">{{ $item->display_consumption_uom ?: '—' }}</span>
                                                            </div>
                                                            @if (filled($item->material_name) || filled($item->material_size) || filled($item->material_spec) || filled($item->special))
                                                                <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-0.5 text-[11px] text-emerald-900/80">
                                                                    @if (filled($item->material_name))<span>{{ $item->material_name }}</span>@endif
                                                                    @if (filled($item->material_size))<span class="font-mono">{{ $item->material_size }}</span>@endif
                                                                    @if (filled($item->material_spec))<span class="font-mono">{{ $item->material_spec }}</span>@endif
                                                                    @if (filled($item->special))<span>{{ $item->special }}</span>@endif
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="px-4 py-6 text-center text-sm text-slate-500">{{ __('planning.boms.index.empty_lines') }}</div>
                                    @endif

                                    <div class="border-t border-slate-100 px-4 py-3">
                                        <button type="button"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm"
                                            @click="openLineModal(@js([
                                                'mode' => 'create',
                                                'action' => route('planning.boms.items.store', $bom),
                                                'bom_id' => $bom->id,
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
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full px-4 py-12 text-center text-slate-500">{{ __('planning.boms.index.empty') }}</div>
                    @endforelse

                    <div x-show="filteredBoms.length === 0 && {{ $boms->isNotEmpty() ? 'true' : 'false' }}" x-cloak
                        class="col-span-full px-4 py-12 text-center text-sm text-slate-500">
                        Tidak ada BOM yang cocok dengan pencarian.
                    </div>
                </div>

                {{-- Footer --}}
                <div class="border-t border-slate-200 p-4 bg-slate-50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                            <span class="font-semibold">{{ __('planning.boms.index.tip_label') }}</span> {{ __('planning.boms.index.tip_before') }} <span class="font-semibold">{{ __('planning.boms.index.tip_add') }}</span> {{ __('planning.boms.index.tip_after') }}
                            <span class="font-medium text-slate-700"
                                x-text="`Halaman ${bomPage} / ${bomTotalPages} · ${filteredBoms.length} BOM`"></span>
                        </div>
                        <div class="flex items-center gap-1" x-show="bomTotalPages > 1">
                            <button type="button" @click="setBomPage(bomPage - 1)" :disabled="bomPage <= 1"
                                class="action-btn hover:bg-slate-100 disabled:opacity-40" aria-label="Previous page">‹</button>
                            <span class="px-2 font-mono text-xs text-slate-600"
                                x-text="`${bomPage} / ${bomTotalPages}`" aria-live="polite"></span>
                            <button type="button" @click="setBomPage(bomPage + 1)" :disabled="bomPage >= bomTotalPages"
                                class="action-btn hover:bg-slate-100 disabled:opacity-40" aria-label="Next page">›</button>
                        </div>
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
                x-show="lineModalOpen" x-cloak @keydown.escape.window="if (lineModalOpen) closeLineModal()" @keydown.tab="trapLineFocus($event)">
                <div class="bom-editor bg-white rounded-2xl shadow-2xl border border-slate-200/60" role="dialog" aria-modal="true" aria-labelledby="bom-line-title" x-ref="lineDialog" tabindex="-1">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 bg-slate-50/80 flex-shrink-0">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <div>
                                <div id="bom-line-title" class="text-lg font-bold text-slate-900"
                                    x-text="lineForm.mode === 'edit' ? '{{ __('planning.boms.index.line_edit') }}' : '{{ __('planning.boms.index.line_add') }}'"></div>
                                <div class="text-[10px] text-slate-500 font-medium" x-text="lineForm.fg_label"></div>
                            </div>
                        </div>
                        <button type="button" class="w-7 h-7 rounded-lg border border-slate-200 hover:bg-slate-100 flex items-center justify-center text-slate-500 transition-colors"
                            @click="closeLineModal()" aria-label="Tutup editor baris">✕</button>
                    </div>

                    <form :action="lineForm.action" method="POST" class="flex flex-col flex-1 min-h-0" @submit="lineSubmitting = true">
                        @csrf
                        <input type="hidden" name="_bom_id" :value="lineForm.bom_id">
                        <input type="hidden" name="_fg_label" :value="lineForm.fg_label">
                        <input type="hidden" name="_component_label" :value="lineForm.component_part_label">
                        <input type="hidden" name="_parent_label" :value="lineForm.wip_part_label">
                        <input type="hidden" name="wip_part_no" :value="lineForm.wip_part_no">
                        <input type="hidden" name="component_part_no" :value="lineForm.component_part_no">
                        <template x-if="lineForm.bom_item_id">
                            <input type="hidden" name="bom_item_id" :value="lineForm.bom_item_id">
                        </template>

                        <div class="bom-editor-body">
                        @if ($errors->any() && old('_bom_id'))
                            <div class="bg-red-50 text-red-800 p-4 rounded-lg" style="grid-column:1 / -1" role="alert" tabindex="-1" id="bom-line-errors">
                                <strong>Belum tersimpan. Periksa isian berikut:</strong>
                                <ul class="list-disc pl-5">
                                    @foreach ($errors->all() as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
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

                        <div class="border border-slate-200 rounded-lg p-4 space-y-4 bom-material-fields">
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
                        </div>
                        {{-- Process and parent fields are always visible. --}}
                        <div class="border border-slate-200 rounded-lg overflow-hidden">
                            <div class="px-4 py-3 bg-slate-50 font-semibold text-slate-800">Proses & parent part</div>
                            <div class="p-4 space-y-4 bg-white">

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

                        </div>
                        <div class="bom-editor-footer flex-shrink-0">
                            <button type="button" class="px-3 py-1.5 rounded border border-slate-200 hover:bg-slate-50 text-xs font-medium text-slate-700 transition-colors"
                                @click="closeLineModal()">{{ __('planning.boms.index.cancel') }}</button>
                            <button type="submit"
                                :disabled="lineSubmitting" class="px-4 py-1.5 rounded bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition-colors shadow-sm disabled:opacity-50" x-text="lineSubmitting ? 'Menyimpan…' : @js(__('planning.boms.index.save'))"></button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function planningBoms() {
                    return {
                        sheetPosition: 0,
                        sheetMax: 0,
                        lineSubmitting: false,
                        lineSnapshot: '',
                        lineTrigger: null,
                        previousOverflow: '',
                        init() {
                            this.$nextTick(() => this.syncSheet());
                            @if ($errors->any() && (int) old('_bom_id') > 0)
                                this.$nextTick(() => this.openLineModal(@js(array_merge(old(), [
                                    'bom_id' => (int) old('_bom_id'),
                                    'action' => route('planning.boms.items.store', (int) old('_bom_id')),
                                    'mode' => old('bom_item_id') ? 'edit' : 'create',
                                    'fg_label' => old('_fg_label', ''),
                                    'component_part_label' => old('_component_label', ''),
                                    'wip_part_label' => old('_parent_label', ''),
                                ]))));
                            @endif
                        },
                        syncSheet() {
                            if (!this.$refs.sheet) return;
                            this.sheetPosition = this.$refs.sheet.scrollLeft;
                            this.sheetMax = Math.max(0, this.$refs.sheet.scrollWidth - this.$refs.sheet.clientWidth);
                        },
                        moveSheet(direction) {
                            this.$refs.sheet.scrollBy({left: direction * this.$refs.sheet.clientWidth * .7, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'});
                        },
                        trapLineFocus(event) {
                            if (!this.lineModalOpen) return;
                            const controls = [...this.$refs.lineDialog.querySelectorAll('button, input, select, textarea, a[href], [tabindex="0"]')].filter(el => !el.disabled && el.getClientRects().length);
                            const first = controls[0], last = controls[controls.length - 1];
                            if (event.shiftKey && (document.activeElement === first || document.activeElement === this.$refs.lineDialog)) { event.preventDefault(); last?.focus(); }
                            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
                        },
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
                            vendor_part_id: '',
                            ratio: 1,
                            priority: 1,
                            status: 'active',
                            notes: '',
                        },
                        expanded: @js($boms->pluck('id')->mapWithKeys(fn ($id) => [(string) $id => false])->all()),
                        allExpanded: false,
                        bomQuery: @js($q ?? ''),
                        bomPage: 1,
                        bomPerPage: 12,
                        bomSearchKeys: @js($boms->map(fn($bom) => [
                            'id' => (int) $bom->id,
                            'key' => mb_strtolower(trim(implode(' ', array_filter([
                                $bom->part->part_no ?? '',
                                $bom->part->part_name ?? '',
                                $bom->part->model ?? '',
                                (string) ($bom->revision ?? ''),
                            ])))),
                        ])->values()->all()),
                        get filteredBoms() {
                            const q = String(this.bomQuery || '').trim().toLowerCase();
                            return q ? this.bomSearchKeys.filter(k => k.key.includes(q)) : this.bomSearchKeys;
                        },
                        get bomTotalPages() { return Math.max(1, Math.ceil(this.filteredBoms.length / this.bomPerPage)); },
                        get bomPageIds() {
                            const start = (this.bomPage - 1) * this.bomPerPage;
                            return this.filteredBoms.map(k => k.id).slice(start, start + this.bomPerPage);
                        },
                        isBomVisible(id) { return this.bomPageIds.indexOf(Number(id)) !== -1; },
                        setBomPage(p) { this.bomPage = Math.min(Math.max(1, p), this.bomTotalPages); },
                        setAllDetails(open) { this.allExpanded = open; this.expanded = Object.fromEntries(Object.keys(this.expanded).map(k => [k, open])); },
                        lineForm: {
                            bom_id: '',
                            wip_part_no: '',
                            component_part_no: '',
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
                            this.substituteForm.vendor_part_id = s.vendor_part_id ? String(s.vendor_part_id) : '';
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
                            this.lineTrigger = document.activeElement;
                            this.previousOverflow = document.body.style.overflow;
                            this.lineSubmitting = false;
                            this.lineForm = {
                                mode: payload.mode,
                                bom_id: payload.bom_id,
                                action: payload.action,
                                bom_item_id: payload.bom_item_id,
                                fg_label: payload.fg_label,
                                classification: payload.classification ?? '',
                                line_no: payload.line_no,
                                process_name: payload.process_name ?? '',
                                machine_id: payload.machine_id ?? '',
                                wip_part_id: payload.wip_part_id ?? '',
                                wip_part_no: payload.wip_part_no ?? '',
                                component_part_no: payload.component_part_no ?? '',
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
                            this.lineSnapshot = JSON.stringify(this.lineForm);
                            this.lineModalOpen = true;
                            document.body.style.overflow = 'hidden';
                            this.$nextTick(() => {
                                this.$refs.lineDialog.querySelectorAll('label').forEach((label, index) => {
                                    const input = label.parentElement.querySelector('input:not([type=hidden]),select,textarea');
                                    if (input) { input.id ||= 'bom-line-field-' + index; label.htmlFor = input.id; }
                                });
                                (this.$refs.lineDialog.querySelector('#bom-line-errors') || this.$refs.lineDialog).focus();
                            });
                        },
                        closeLineModal() {
                            if (this.lineSnapshot !== JSON.stringify(this.lineForm) && !window.confirm('Perubahan belum disimpan. Tutup editor?')) return;
                            this.lineModalOpen = false;
                            document.body.style.overflow = this.previousOverflow;
                            this.lineTrigger?.focus();
                        },
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
                                    <select name="vendor_part_id" class="mt-1 w-full rounded-xl border-slate-200"
                                        x-model="substituteForm.vendor_part_id">
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
