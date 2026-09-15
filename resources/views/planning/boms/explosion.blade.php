<x-app-layout>
    <x-slot name="header">
        {{ __('planning.boms.explosion.header') }} {{ $bom ? '• ' . $bom->part->part_no : '(' . __('planning.boms.explosion.search_badge') . ')' }}
    </x-slot>

    <div class="py-6" x-data="bomExplorer(
        {{ (int) ($bom->id ?? 0) }},
        @js(route('planning.boms.items.store', $bom ?? 0)),
        @js($fgLabel ?? '')
    )">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Alerts --}}
            @if (session('success'))
                <div class="p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 font-semibold shadow-sm flex items-center gap-2">
                    <svg class="h-5 w-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 font-semibold shadow-sm flex items-center gap-2">
                    <svg class="h-5 w-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    {{ session('error') }}
                </div>
            @endif

            {{-- Search Section --}}
            @if(!$bom || isset($searchMode))
                <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        {{ __('planning.boms.explosion.search_title') }}
                    </h3>

                    <form method="GET" action="{{ route('planning.boms.explosion-search') }}" class="space-y-4">
                        <div class="flex gap-3">
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="mode" value="customer" {{ ($searchMode ?? 'fg') === 'customer' ? 'checked' : '' }} class="sr-only peer">
                                <div class="px-4 py-3 rounded-xl border-2 border-slate-200 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 hover:bg-slate-50 transition-colors">
                                    <div class="font-semibold text-slate-900">{{ __('planning.boms.explosion.by_customer') }}</div>
                                    <div class="text-xs text-slate-600 mt-1">{{ __('planning.boms.explosion.by_customer_hint') }}</div>
                                </div>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="mode" value="fg" {{ ($searchMode ?? 'fg') === 'fg' ? 'checked' : '' }} class="sr-only peer">
                                <div class="px-4 py-3 rounded-xl border-2 border-slate-200 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 hover:bg-slate-50 transition-colors">
                                    <div class="font-semibold text-slate-900">{{ __('planning.boms.explosion.by_fg') }}</div>
                                    <div class="text-xs text-slate-600 mt-1">{{ __('planning.boms.explosion.by_fg_hint') }}</div>
                                </div>
                            </label>
                        </div>

                        <div class="flex gap-3">
                            <input type="text" name="search" value="{{ $searchQuery ?? '' }}"
                                class="flex-1 rounded-xl border-slate-200 text-lg"
                                placeholder="{{ __('planning.boms.explosion.search_ph') }}"
                                aria-label="{{ __('planning.boms.explosion.search_aria') }}" required>
                            <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold">
                                {{ __('planning.boms.explosion.search_btn') }}
                            </button>
                        </div>
                    </form>

                    @if(isset($customerPart) && $customerPartComponents->count() > 0)
                        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                            <h4 class="font-bold text-blue-900 mb-3">{{ __('planning.boms.explosion.customer_found') }}</h4>
                            <div class="text-sm text-blue-800 mb-3">
                                <div><strong>{{ __('planning.boms.explosion.customer_label') }}</strong> {{ $customerPart->customer->name ?? '-' }}</div>
                                <div><strong>{{ __('planning.boms.explosion.part_no_label') }}</strong> {{ $customerPart->customer_part_no }}</div>
                                <div><strong>{{ __('planning.boms.explosion.part_name_label') }}</strong> {{ $customerPart->customer_part_name }}</div>
                            </div>
                            <div class="text-sm font-semibold text-blue-900 mb-2">{{ __('planning.boms.explosion.fg_used') }}</div>
                            <div class="space-y-2">
                                @foreach($customerPartComponents as $component)
                                    <div class="bg-white rounded-lg p-3 border border-blue-200">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="font-mono font-bold">{{ $component->part->part_no }}</div>
                                                <div class="text-xs text-slate-600">{{ $component->part->part_name }}</div>
                                                <div class="text-xs text-slate-500">{{ __('planning.boms.explosion.usage') }} {{ $component->usage_qty }} pcs</div>
                                            </div>
                                            @if($component->part->bom)
                                                <a href="{{ route('planning.boms.explosion', $component->part->bom) }}"
                                                   class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-sm">
                                                    {{ __('planning.boms.explosion.view_explosion') }}
                                                </a>
                                            @else
                                                <span class="text-xs text-slate-400">{{ __('planning.boms.explosion.no_bom') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            @if($bom)
                <style>
                    [x-cloak] { display: none !important; }
                    .expl-hero { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:18px 20px; margin-bottom:18px; display:flex; justify-content:space-between; align-items:center; gap:14px; flex-wrap:wrap; }
                    .expl-hero .name { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:20px; font-weight:800; color:#4f46e5; }
                    .expl-hero .sub { font-size:13px; color:#64748b; font-weight:600; }
                    .expl-hero .pills { display:flex; gap:6px; margin-top:6px; flex-wrap:wrap; }
                    .expl-toolbar { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
                    .expl-btn { border:1px solid #e2e8f0; background:#fff; border-radius:9px; padding:8px 13px; font-size:13px; font-weight:600; cursor:pointer; color:#334155; transition:.15s; }
                    .expl-btn:hover { background:#f1f5f9; }
                    .expl-btn.primary { background:#4f46e5; border-color:#4f46e5; color:#fff; }
                    .expl-btn.primary:hover { background:#4338ca; }
                    .expl-qty-input { border:1px solid #e2e8f0; background:#fff; border-radius:9px; padding:8px 10px; font-size:13px; font-weight:600; color:#334155; width:70px; }
                    .expl-tree { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:10px 4px 14px; }
                    .expl-tree-list { list-style:none; margin:0; padding:0; }
                    .expl-tree-list .expl-tree-list { padding-left:26px; margin-left:12px; border-left:2px solid #e2e8f0; }
                    .expl-node { position:relative; margin:2px 0; }
                    .expl-node-row { display:flex; align-items:center; gap:9px; padding:7px 10px; border-radius:9px; cursor:pointer; transition:background .14s; min-height:38px; }
                    .expl-node-row:hover { background:#f8fafc; }
                    .expl-node-row[data-leaf]:hover { background:#ecfdf5; }
                    .expl-knob { width:18px; height:18px; flex:0 0 18px; border-radius:6px; display:grid; place-items:center; font-size:11px; font-weight:800; color:#fff; user-select:none; }
                    .expl-knob.knob-fg { background:#4f46e5; }
                    .expl-knob.knob-wip { background:#d97706; }
                    .expl-knob.knob-rm { background:#059669; }
                    .expl-kind { font-size:9.5px; font-weight:800; letter-spacing:.04em; padding:2px 7px; border-radius:6px; }
                    .expl-kind.fg { background:#eef2ff; color:#4f46e5; }
                    .expl-kind.wip { background:#fffbeb; color:#d97706; }
                    .expl-kind.rm { background:#ecfdf5; color:#059669; }
                    .expl-mb { font-size:9.5px; font-weight:800; padding:2px 7px; border-radius:6px; color:#fff; }
                    .expl-mb.buy { background:#059669; }
                    .expl-mb.make { background:#0284c7; }
                    .expl-mb.subcon { background:#7c3aed; }
                    .expl-mb.free { background:#e11d48; }
                    .expl-part-no { font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace; font-size:13px; font-weight:700; color:#0f172a; }
                    .expl-part-name { font-size:12.5px; color:#475569; }
                    .expl-dim { font-size:11.5px; color:#64748b; font-family:ui-monospace,Menlo,monospace; }
                    .expl-qty { font-size:12px; font-weight:700; color:#334155; white-space:nowrap; }
                    .expl-subs-chip { font-size:11px; font-weight:700; color:#4f46e5; background:#eef2ff; border:1px solid #c7d2fe; padding:2px 8px; border-radius:999px; white-space:nowrap; }
                    .expl-arrow { margin-left:auto; color:#64748b; font-size:11px; font-weight:800; }
                    .expl-leaf-actions { margin-left:auto; display:inline-flex; gap:4px; align-items:center; }
                    .expl-icon-btn { width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #e2e8f0; border-radius:7px; background:#fff; font-size:12px; cursor:pointer; color:#475569; line-height:1; }
                    .expl-icon-btn:hover { background:#eef2ff; border-color:#c7d2fe; color:#4f46e5; }
                    .expl-icon-btn.danger:hover { background:#fef2f2; border-color:#fecaca; color:#dc2626; }
                    .expl-hint { font-size:11.5px; color:#94a3b8; text-align:center; margin-top:14px; }
                    .expl-badge { display:inline-flex; align-items:center; gap:5px; font-size:10.5px; font-weight:800; letter-spacing:.04em; padding:3px 8px; border-radius:999px; }
                    .expl-badge.fg { background:#eef2ff; color:#4f46e5; border:1px solid #c7d2fe; }
                    .expl-badge.rev { background:#f8fafc; color:#64748b; border:1px solid #e2e8f0; }
                    .expl-badge.ok { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
                    .expl-badge.muted { background:#f8fafc; color:#94a3b8; border:1px solid #e2e8f0; }
                    .expl-summary { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:20px; margin-top:18px; }
                    .expl-summary h4 { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#64748b; margin:0 0 14px; }
                    table.expl-dt { width:100%; border-collapse:collapse; font-size:13px; }
                    .expl-dt th { text-align:left; font-size:10.5px; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:8px 10px; border-bottom:2px solid #e2e8f0; }
                    .expl-dt td { padding:10px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
                    .expl-dt tr:hover td { background:#fafbff; }
                    /* Line editor modal */
                    .bom-editor { width:min(1160px,100%); max-height:calc(100dvh - 32px); display:flex; flex-direction:column; }
                    .bom-editor-body { padding:20px; overflow:auto; display:grid; grid-template-columns:1fr 1fr; gap:18px; align-items:start; }
                    .bom-editor-body > div { min-width:0; }
                    .bom-editor-footer { padding:12px 20px; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px; background:#f8fafc; }
                    .bom-editor label { display:block; font-size:11px !important; margin-bottom:4px; color:#334155; font-weight:600; }
                    .bom-editor input:not([type=hidden]),.bom-editor select { min-height:36px; font-size:13px; border-radius:8px; }
                    .sub-form-label { font-size:11px; font-weight:600; color:#334155; }
                    @media (max-width:900px) { .bom-editor-body { grid-template-columns:1fr; } }
                    @media (prefers-reduced-motion:reduce) { .expl-node-row { transition:none; } }
                </style>

                @if(isset($customerPart))
                    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4">
                        <div class="flex items-center gap-3">
                            <div class="text-3xl" aria-hidden="true">
                                <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-semibold text-blue-900">{{ __('planning.boms.explosion.customer_product') }}</div>
                                <div class="font-bold text-lg text-blue-900">{{ $customerPart->customer_part_no }} - {{ $customerPart->customer_part_name }}</div>
                                <div class="text-sm text-blue-700">{{ $customerPart->customer->name ?? '-' }}</div>
                            </div>
                            <div class="text-2xl text-blue-400">↓</div>
                        </div>
                    </div>
                @endif

                {{-- Hero --}}
                <div class="expl-hero">
                    <div>
                        <div class="name">{{ $bom->part->part_no }}</div>
                        <div class="sub">{{ $bom->part->part_name }}@if($bom->part->model) · {{ $bom->part->model }}@endif</div>
                        <div class="pills">
                            <span class="expl-badge fg">FG</span>
                            <span class="expl-badge rev">REV {{ $bom->revision ?? 'A' }}</span>
                            @if(($bom->status ?? 'active') === 'active')
                                <span class="expl-badge ok">ACTIVE</span>
                            @else
                                <span class="expl-badge muted">{{ strtoupper($bom->status ?? 'inactive') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="expl-toolbar">
                        <button class="expl-btn primary" type="button" @click="openLineCreate()">+ Add Line</button>
                        <form method="GET" action="{{ route('planning.boms.explosion', $bom) }}" class="expl-toolbar">
                            <input class="expl-qty-input" type="number" name="qty" value="{{ $quantity }}" min="1" step="1" title="{{ __('planning.boms.explosion.qty_label') }}">
                            <button class="expl-btn" type="submit">{{ __('planning.boms.explosion.recalculate') }}</button>
                        </form>
                        <button class="expl-btn" type="button" @click="$dispatch('bom-tree-set', true)">Expand all</button>
                        <button class="expl-btn" type="button" @click="$dispatch('bom-tree-set', false)">Collapse all</button>
                    </div>
                </div>

                {{-- Tree --}}
                @php
                    $fgRoot = [
                        'kind' => 'fg',
                        'no' => $bom->part->part_no,
                        'name' => $bom->part->part_name,
                        'children' => $tree,
                    ];
                @endphp
                <div class="expl-tree">
                    @if(empty($tree))
                        <p class="expl-hint">{{ __('planning.boms.explosion.empty_components') }}</p>
                    @else
                        <ul class="expl-tree-list">
                            @include('planning.boms._explorer_node', ['nodes' => [$fgRoot]])
                        </ul>
                    @endif
                    <p class="expl-hint">Garis cabang = tahapan proses (WIP). Klik baris RM untuk detail, pakai ✎ untuk edit, ⇄ untuk substitute, ✕ untuk hapus.</p>
                </div>

                {{-- RM Detail + Substitute Drawer --}}
                <div class="fixed inset-0 z-50" x-show="detailOpen" x-cloak
                    @bom-rm-detail.window="openRmDetail($event.detail)"
                    @keydown.escape.window="detailOpen = false">
                    <div class="absolute inset-0 bg-slate-900/40" @click="detailOpen = false"></div>
                    <div class="absolute right-0 top-0 h-full w-full max-w-xl bg-white shadow-2xl border-l border-slate-200 overflow-y-auto">
                        <div class="sticky top-0 bg-emerald-600 text-white px-5 py-4 flex items-center justify-between z-10">
                            <div class="font-semibold">RM Detail</div>
                            <button type="button" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-white/20" @click="detailOpen = false">✕</button>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-3 mb-5">
                                <span class="expl-knob knob-rm">◆</span>
                                <div>
                                    <div class="expl-part-no" x-text="rmDetail.no || '-'"></div>
                                    <div class="text-sm text-slate-500" x-text="[rmDetail.name, rmDetail.qty, rmDetail.dim].filter(Boolean).join(' · ')"></div>
                                </div>
                                <span class="expl-badge fg ml-auto" x-text="rmDetail.special || rmDetail.mb || ''"></span>
                            </div>

                            {{-- Substitutes table --}}
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Substitutes <span class="ml-1 text-slate-400" x-text="'(' + ((rmDetail.subs_data || []).length) + ')'"></span></h4>
                            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                                <table class="expl-dt min-w-full">
                                    <thead>
                                        <tr class="bg-slate-50">
                                            <th>#</th><th>Substitute part</th><th>Vendor</th><th class="text-right">Ratio</th><th>Status</th><th class="text-right">Act</th>
                                        </tr>
                                    </thead>
                                    <tbody x-show="!(rmDetail.subs_data || []).length">
                                        <tr><td colspan="6" class="py-6 text-center text-slate-500">Tidak ada substitute.</td></tr>
                                    </tbody>
                                    <tbody>
                                        <template x-for="(s, i) in (rmDetail.subs_data || [])" :key="s.id ?? i">
                                            <tr>
                                                <td><span class="inline-block min-w-[22px] text-center font-extrabold text-white bg-slate-400 rounded-md px-1.5 py-0.5 text-[11px]" x-text="s.priority ?? (i + 1)"></span></td>
                                                <td>
                                                    <div class="font-mono font-bold text-slate-900" x-text="s.part_no || '-'"></div>
                                                    <div class="text-slate-500" x-text="s.part_name || ''"></div>
                                                </td>
                                                <td>
                                                    <div class="text-slate-700" x-text="s.vendor_name || '-'"></div>
                                                    <div class="text-[11px] text-slate-400 font-mono" x-text="s.vendor_no || ''"></div>
                                                </td>
                                                <td class="text-right font-mono" x-text="Number(s.ratio).toFixed(2)"></td>
                                                <td>
                                                    <span class="expl-badge ok" x-show="(s.status || 'active') === 'active'">ACTIVE</span>
                                                    <span class="expl-badge muted" x-show="(s.status || 'active') !== 'active'" x-text="String(s.status || '').toUpperCase()"></span>
                                                </td>
                                                <td class="text-right whitespace-nowrap">
                                                    <button type="button" class="expl-icon-btn" title="Edit" @click="editSubstitute(s)">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                    </button>
                                                    <form :action="s.delete_url" method="POST" class="inline"
                                                        onsubmit="return confirm('{{ __('planning.boms.index.confirm_remove_sub') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="expl-icon-btn danger" title="Delete">✕</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Substitute form --}}
                            <div class="mt-5 rounded-xl border border-slate-200 p-4 space-y-3 bg-white">
                                <div class="flex items-center justify-between">
                                    <div class="text-xs font-semibold text-slate-700 uppercase tracking-wider" x-text="subForm.method === 'PUT' ? 'Update Substitute' : 'Add Substitute'"></div>
                                    <button type="button" class="text-[11px] font-semibold text-slate-500 hover:text-slate-700" x-show="subForm.method === 'PUT'" @click="resetSubstituteForm()">Cancel Edit</button>
                                </div>
                                <form :action="subForm.action" method="POST" class="space-y-3">
                                    @csrf
                                    <template x-if="subForm.method === 'PUT'">
                                        <input type="hidden" name="_method" value="PUT">
                                    </template>
                                    <div>
                                        <label class="sub-form-label">Substitute Part (GCI)</label>
                                        <select name="substitute_part_id" class="mt-1 w-full rounded-xl border-slate-200" required x-model="subForm.substitute_part_id">
                                            <option value="" disabled>-- Select part --</option>
                                            @foreach(($rmParts ?? []) as $p)
                                                <option value="{{ optional($p)->id }}">{{ optional($p)->part_no }} - {{ optional($p)->part_name ?? '-' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="sub-form-label">Incoming Part (Vendor)</label>
                                        <select name="vendor_part_id" class="mt-1 w-full rounded-xl border-slate-200" x-model="subForm.vendor_part_id">
                                            <option value="">(No incoming part linked)</option>
                                            @foreach(($incomingParts ?? []) as $ip)
                                                <option value="{{ $ip->id }}">{{ $ip->part_no }} - {{ $ip->part_name ?: ($ip->vendor_part_name ?? '-') }}{{ $ip->vendor ? ' [' . $ip->vendor->vendor_name . ']' : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="sub-form-label">Ratio</label>
                                            <input type="number" step="0.001" min="0.001" name="ratio" class="mt-1 w-full rounded-xl border-slate-200" x-model="subForm.ratio">
                                        </div>
                                        <div>
                                            <label class="sub-form-label">Priority</label>
                                            <input type="number" min="1" name="priority" class="mt-1 w-full rounded-xl border-slate-200" x-model="subForm.priority">
                                        </div>
                                        <div>
                                            <label class="sub-form-label">Status</label>
                                            <select name="status" class="mt-1 w-full rounded-xl border-slate-200" x-model="subForm.status">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="sub-form-label">Notes</label>
                                        <input type="text" name="notes" maxlength="255" class="mt-1 w-full rounded-xl border-slate-200" x-model="subForm.notes">
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold"
                                            x-text="subForm.method === 'PUT' ? 'Update Substitute' : 'Save Substitute'">Save Substitute</button>
                                    </div>
                                </form>
                            </div>
                            <p class="expl-hint">Sumber data: <b>material_substitutes</b> (global, Part Master).</p>
                        </div>
                    </div>
                </div>

                {{-- Line editor modal --}}
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
                    x-show="lineModalOpen" x-cloak
                    @bom-line-edit.window="openLineModal($event.detail)"
                    @keydown.escape.window="closeLineModal()">
                    <div class="bom-editor bg-white rounded-2xl shadow-2xl border border-slate-200/60" role="dialog" aria-modal="true" aria-labelledby="bom-line-title" x-ref="lineDialog" tabindex="-1">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200 bg-slate-50/80 flex-shrink-0">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-indigo-100 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </div>
                                <div>
                                    <div id="bom-line-title" class="text-lg font-bold text-slate-900"
                                        x-text="lineForm.mode === 'edit' ? 'Edit BOM Line' : 'Add BOM Line'"></div>
                                    <div class="text-[10px] text-slate-500 font-medium" x-text="lineForm.fg_label"></div>
                                </div>
                            </div>
                            <button type="button" class="w-7 h-7 rounded-lg border border-slate-200 hover:bg-slate-100 flex items-center justify-center text-slate-500 transition-colors" @click="closeLineModal()" aria-label="Tutup editor baris">✕</button>
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
                                {{-- RM Component --}}
                                <div class="bg-indigo-50/30 rounded-lg border border-indigo-100 p-3 space-y-2">
                                    <div class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider">RM Component</div>
                                    <div>
                                        <label>RM Part <span class="text-red-500">*</span></label>
                                        <template x-if="lineForm.mode === 'edit'">
                                            <div>
                                                <input type="hidden" name="component_part_id" :value="lineForm.component_part_id">
                                                <div class="mt-0.5 w-full rounded border border-slate-200 bg-white px-2 py-1 text-xs text-slate-800" x-text="lineForm.component_part_label || '-'"></div>
                                            </div>
                                        </template>
                                        <template x-if="lineForm.mode !== 'edit'">
                                            <select name="component_part_id" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.component_part_id" required>
                                                <option value="">-- Select RM Part --</option>
                                                @foreach (($rmParts ?? []) as $c)
                                                    <option value="{{ optional($c)->id }}">{{ optional($c)->part_no }} - {{ optional($c)->part_name ?? '-' }}</option>
                                                @endforeach
                                            </select>
                                        </template>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label>Qty <span class="text-red-500">*</span></label>
                                            <input type="number" step="any" min="0" name="usage_qty" class="mt-0.5 w-full rounded border-slate-200 text-xs" required x-model="lineForm.usage_qty">
                                        </div>
                                        <div>
                                            <label>UOM</label>
                                            <select name="consumption_uom_id" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.consumption_uom_id">
                                                <option value="">-</option>
                                                @foreach(($uoms ?? []) as $uom)
                                                    <option value="{{ optional($uom)->id }}">{{ optional($uom)->code }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label>Make / Buy</label>
                                            <select name="make_or_buy" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.make_or_buy">
                                                <option value="buy">BUY</option>
                                                <option value="make">MAKE</option>
                                                <option value="free_issue">FREE ISSUE</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label>Policy Override</label>
                                            <select name="consumption_policy_override" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.consumption_policy_override">
                                                <option value="">Default</option>
                                                <option value="direct_issue">Fully Consumed</option>
                                                <option value="backflush_return">Return Balance</option>
                                                <option value="backflush_line_stock">Line Stock</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                {{-- Material --}}
                                <div class="border border-slate-200 rounded-lg p-3 space-y-2">
                                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Material</div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label>Size</label>
                                            <input type="text" name="material_size" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.material_size" placeholder="0.7 x 530 x C">
                                        </div>
                                        <div>
                                            <label>Spec</label>
                                            <input type="text" name="material_spec" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.material_spec" placeholder="SPCC, SUS304">
                                        </div>
                                        <div>
                                            <label>Name</label>
                                            <input type="text" name="material_name" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.material_name">
                                        </div>
                                        <div>
                                            <label>Special</label>
                                            <input type="text" name="special" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.special">
                                        </div>
                                    </div>
                                </div>

                                {{-- Process & parent --}}
                                <div class="border border-slate-200 rounded-lg p-3 space-y-2">
                                    <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Process & WIP</div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label>Line No</label>
                                            <input type="number" min="1" name="line_no" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.line_no">
                                        </div>
                                        <div>
                                            <label>Process Name</label>
                                            <input type="text" name="process_name" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.process_name">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label>Machine</label>
                                            <select name="machine_id" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.machine_id">
                                                <option value="">-</option>
                                                @foreach ($machines as $machine)
                                                    <option value="{{ $machine->id }}">{{ $machine->code }} - {{ $machine->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label>WIP Part</label>
                                            <template x-if="lineForm.mode === 'edit'">
                                                <div>
                                                    <input type="hidden" name="wip_part_id" :value="lineForm.wip_part_id">
                                                    <div class="mt-0.5 w-full rounded border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-700 truncate" x-text="lineForm.wip_part_label || '-'"></div>
                                                </div>
                                            </template>
                                            <template x-if="lineForm.mode !== 'edit'">
                                                <select name="wip_part_id" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.wip_part_id">
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
                                            <label>WIP Qty</label>
                                            <input type="number" step="any" min="0" name="wip_qty" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.wip_qty">
                                        </div>
                                        <div>
                                            <label>WIP UOM</label>
                                            <select name="wip_uom_id" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.wip_uom_id">
                                                <option value="">-</option>
                                                @foreach (($uoms ?? []) as $uom)
                                                    <option value="{{ $uom->id }}">{{ $uom->code }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label>WIP UOM Legacy</label>
                                            <input type="text" name="wip_uom" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.wip_uom">
                                        </div>
                                        <div>
                                            <label>WIP Part Name</label>
                                            <input type="text" name="wip_part_name" class="mt-0.5 w-full rounded border-slate-200 text-xs" x-model="lineForm.wip_part_name">
                                        </div>
                                    </div>
                                    <input type="hidden" name="scrap_factor" :value="lineForm.scrap_factor">
                                    <input type="hidden" name="yield_factor" :value="lineForm.yield_factor">
                                </div>
                            </div>
                            <div class="bom-editor-footer flex-shrink-0">
                                <button type="button" class="px-3 py-1.5 rounded border border-slate-200 hover:bg-slate-50 text-xs font-medium text-slate-700 transition-colors" @click="closeLineModal()">Cancel</button>
                                <button type="submit" :disabled="lineSubmitting" class="px-4 py-1.5 rounded bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition-colors shadow-sm disabled:opacity-50" x-text="lineSubmitting ? 'Menyimpan…' : 'Save'">Save</button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Material Summary --}}
                <div class="expl-summary">
                    <h4>{{ __('planning.boms.explosion.summary') }}</h4>
                    <div class="overflow-x-auto">
                        <table class="expl-dt min-w-full">
                            <thead>
                                <tr>
                                    <th>{{ __('planning.boms.explosion.sum_part_no') }}</th>
                                    <th>{{ __('planning.boms.explosion.sum_part_name') }}</th>
                                    <th class="text-right">{{ __('planning.boms.explosion.sum_total') }}</th>
                                    <th>{{ __('planning.boms.explosion.sum_uom') }}</th>
                                    <th>{{ __('planning.boms.explosion.sum_make_buy') }}</th>
                                    <th>{{ __('planning.boms.explosion.sum_spec') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materials as $mat)
                                    @php
                                        $mob = strtolower((string) $mat['make_or_buy']);
                                        $mobFree = in_array($mob, ['free', 'free_issue'], true) ? 'free' : $mob;
                                    @endphp
                                    <tr>
                                        <td class="font-mono font-bold text-slate-900">{{ $mat['part_no'] }}</td>
                                        <td class="text-slate-700">{{ $mat['part']->part_name ?? '-' }}</td>
                                        <td class="text-right font-mono font-bold text-slate-900">{{ number_format($mat['total_qty'], 4) }}</td>
                                        <td class="text-xs font-semibold text-slate-600">{{ $mat['uom'] ?? '-' }}</td>
                                        <td>
                                            @if($mob !== '')
                                                <span class="expl-mb {{ $mobFree }}">{{ strtoupper(str_replace('_', ' ', $mat['make_or_buy'])) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-xs text-slate-600">{{ $mat['material_spec'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="py-8 text-center text-slate-500">{{ __('planning.boms.explosion.empty_materials') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function bomExplorer(bomId, storeAction, fgLabel) {
            return {
                bomId: bomId,
                storeAction: storeAction,
                fgLabel: fgLabel,
                detailOpen: false,
                rmDetail: { no: '', name: '', dim: '', spec: '', material: '', qty: '', mb: '', special: '', subs: 0, subs_data: [], substore_url: '' },
                lineModalOpen: false,
                lineSubmitting: false,
                lineSnapshot: '',
                lineTrigger: null,
                previousOverflow: '',
                lineForm: {
                    mode: 'create',
                    bom_id: bomId,
                    action: storeAction,
                    bom_item_id: null,
                    fg_label: fgLabel,
                    component_part_label: '',
                    wip_part_label: '',
                    wip_part_no: '',
                    component_part_no: '',
                    component_part_id: '',
                    usage_qty: '1',
                    consumption_uom_id: '',
                    make_or_buy: 'buy',
                    consumption_policy_override: '',
                    material_size: '',
                    material_spec: '',
                    material_name: '',
                    special: '',
                    line_no: null,
                    process_name: '',
                    machine_id: '',
                    wip_part_id: '',
                    wip_qty: '',
                    wip_uom_id: '',
                    wip_uom: '',
                    wip_part_name: '',
                    scrap_factor: 0,
                    yield_factor: 1,
                },
                subForm: { action: '', store_action: '', method: 'POST', substitute_part_id: '', vendor_part_id: '', ratio: 1, priority: 1, status: 'active', notes: '' },
                openRmDetail(payload) {
                    this.rmDetail = Object.assign(this.rmDetail, payload);
                    this.subForm.action = payload.substore_url || '';
                    this.subForm.store_action = payload.substore_url || '';
                    this.resetSubstituteForm();
                    this.detailOpen = true;
                },
                editSubstitute(s) {
                    this.subForm.action = s.update_url;
                    this.subForm.method = 'PUT';
                    this.subForm.substitute_part_id = String(s.substitute_part_id || '');
                    this.subForm.vendor_part_id = s.vendor_part_id ? String(s.vendor_part_id) : '';
                    this.subForm.ratio = s.ratio || 1;
                    this.subForm.priority = s.priority || 1;
                    this.subForm.status = s.status || 'active';
                    this.subForm.notes = s.notes || '';
                },
                resetSubstituteForm() {
                    this.subForm.action = this.subForm.store_action || this.subForm.action;
                    this.subForm.method = 'POST';
                    this.subForm.substitute_part_id = '';
                    this.subForm.vendor_part_id = '';
                    this.subForm.ratio = 1;
                    this.subForm.priority = 1;
                    this.subForm.status = 'active';
                    this.subForm.notes = '';
                },
                openLineCreate() {
                    this.openLineModal({
                        mode: 'create',
                        bom_id: this.bomId,
                        action: this.storeAction,
                        fg_label: this.fgLabel,
                        bom_item_id: null,
                    });
                },
                openLineModal(payload) {
                    this.lineTrigger = document.activeElement;
                    this.previousOverflow = document.body.style.overflow;
                    this.lineSubmitting = false;
                    this.lineForm = {
                        mode: payload.mode || 'create',
                        bom_id: payload.bom_id ?? this.bomId,
                        action: payload.action || payload.edit_action || this.storeAction,
                        bom_item_id: payload.bom_item_id ?? null,
                        fg_label: payload.fg_label ?? this.fgLabel,
                        component_part_label: payload.component_part_label ?? '',
                        wip_part_label: payload.wip_part_label ?? '',
                        wip_part_no: payload.wip_part_no ?? '',
                        component_part_no: payload.component_part_no ?? '',
                        component_part_id: payload.component_part_id ?? '',
                        usage_qty: payload.usage_qty ?? '1',
                        consumption_uom_id: payload.consumption_uom_id ?? '',
                        make_or_buy: payload.make_or_buy ?? 'buy',
                        consumption_policy_override: payload.consumption_policy_override ?? '',
                        material_size: payload.material_size ?? '',
                        material_spec: payload.material_spec ?? '',
                        material_name: payload.material_name ?? '',
                        special: payload.special ?? '',
                        line_no: payload.line_no ?? null,
                        process_name: payload.process_name ?? '',
                        machine_id: payload.machine_id ?? '',
                        wip_part_id: payload.wip_part_id ?? '',
                        wip_qty: payload.wip_qty ?? '',
                        wip_uom_id: payload.wip_uom_id ?? '',
                        wip_uom: payload.wip_uom ?? '',
                        wip_part_name: payload.wip_part_name ?? '',
                        scrap_factor: payload.scrap_factor ?? 0,
                        yield_factor: payload.yield_factor ?? 1,
                    };
                    this.lineSnapshot = JSON.stringify(this.lineForm);
                    this.lineModalOpen = true;
                    document.body.style.overflow = 'hidden';
                    this.$nextTick(() => {
                        const dlg = this.$refs.lineDialog;
                        dlg.querySelectorAll('label').forEach((label, index) => {
                            const el = label.parentElement.querySelector('input:not([type=hidden]),select,textarea');
                            if (el) { el.id ||= 'bom-line-field-' + index; label.htmlFor = el.id; }
                        });
                        (dlg.querySelector('#bom-line-errors') || dlg).focus();
                    });
                },
                closeLineModal() {
                    if (this.lineSnapshot !== JSON.stringify(this.lineForm) && !window.confirm('Perubahan belum disimpan. Tutup editor?')) return;
                    this.lineModalOpen = false;
                    document.body.style.overflow = this.previousOverflow;
                    this.lineTrigger && this.lineTrigger.focus();
                },
            }
        }
    </script>
</x-app-layout>