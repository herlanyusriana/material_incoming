<x-app-layout>
    <x-slot name="header">
        Planning • BOM GCI
    </x-slot>

    <div class="py-6" x-data="planningBoms()">
        <div class="max-w-screen-2xl mx-auto px-2 sm:px-4 lg:px-6 space-y-5">
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
                        Validation error
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
                }

                .bom-table th,
                .bom-table td {
                    border-bottom: 1px solid #e2e8f0;
                    border-right: 1px solid #f1f5f9;
                }

                .bom-table th {
                    background: #f8fafc;
                    color: #64748b;
                    position: sticky;
                    top: 0;
                    z-index: 20;
                }

                /* Sticky left columns */
                .sticky-col-1 {
                    position: sticky;
                    left: 0;
                    z-index: 30;
                    background: inherit;
                }

                .sticky-col-2 {
                    position: sticky;
                    left: 48px;
                    z-index: 30;
                    background: inherit;
                }

                .sticky-col-3 {
                    position: sticky;
                    left: 248px;
                    z-index: 30;
                    background: inherit;
                }

                .sticky-col-4 {
                    position: sticky;
                    left: 348px;
                    z-index: 30;
                    background: inherit;
                }

                .th-sticky {
                    z-index: 40 !important;
                    background: #f8fafc !important;
                }

                .parent-row {
                    background: #ffffff;
                    border-left: 3px solid #6366f1;
                }

                .parent-row:hover {
                    background: #f8fafc;
                }

                .child-row {
                    background-color: #ffffff;
                    border-left: 3px solid transparent;
                }

                .child-row:hover {
                    background-color: #f8fafc;
                    border-left-color: #a5b4fc;
                }

                /* Action buttons */
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
                    background: #f8fafc;
                }

                /* Responsive: on small screens let sticky columns flow normally */
                @media (max-width: 768px) {

                    .sticky-col-1,
                    .sticky-col-2,
                    .sticky-col-3,
                    .sticky-col-4,
                    .th-sticky {
                        position: static !important;
                        z-index: auto !important;
                    }
                }
            </style>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
                {{-- Header --}}
                <div class="p-6 border-b border-slate-100">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-start gap-3">
                            <div
                                class="h-12 w-12 rounded-xl bg-slate-900 flex items-center justify-center text-white font-black text-sm">
                                BOM
                            </div>
                            <div>
                                <div class="text-2xl md:text-3xl font-black text-slate-900">Bill of Materials</div>
                                <div class="mt-1 text-sm text-slate-500">
                                    BOM GCI • Manage component structures
                                    <span
                                        class="ml-2 px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-[10px] font-bold uppercase tracking-wider border border-indigo-100">
                                        {{ $boms->total() }} BOMs
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 shadow-sm"
                                @click="openCreate()">
                                + Add BOM
                            </button>
                        </div>
                    </div>

                    {{-- Toolbar --}}
                    <div class="mt-6 flex flex-wrap gap-4 items-end border-t border-slate-100 pt-6">
                        <form method="GET" class="flex flex-wrap items-end gap-3">
                            <div>
                                <label
                                    class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Search</label>
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input name="q" value="{{ $q ?? '' }}"
                                        class="w-full pl-9 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Part no / name...">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Part
                                    GCI</label>
                                <select name="gci_part_id"
                                    class="rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Part GCI</option>
                                    @foreach ($fgParts as $p)
                                        <option value="{{ $p->id }}" @selected((string) ($gciPartId ?? '') === (string) $p->id)>
                                            {{ $p->part_no }} — {{ $p->part_name ?? '-' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit"
                                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-semibold text-sm shadow-sm transition-colors">
                                Filter
                            </button>
                        </form>

                        <div class="flex items-center gap-2 ml-auto flex-wrap">
                            <a href="{{ route('planning.boms.export', request()->query()) }}"
                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Export
                            </a>
                            <button type="button"
                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                @click="openImport()">
                                Import
                            </button>
                            <a href="{{ route('planning.boms.substitutes.export') }}"
                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50"
                                title="Export Substitutes to Excel">
                                Exp. Subst.
                            </a>
                            <button type="button"
                                class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50"
                                @click="openImportSubstitute()" title="Import Substitutes via Excel">
                                Imp. Subst.
                            </button>
                            <form action="{{ route('planning.boms.truncate') }}" method="POST"
                                onsubmit="return confirm('HAPUS SEMUA BOM? Data items & substitutes juga akan terhapus. Aksi ini tidak bisa di-undo!')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center rounded-xl bg-red-50 border border-red-200 px-3 py-2 text-xs font-bold text-red-600 hover:bg-red-100"
                                    title="Hapus semua BOM beserta items dan substitutes">
                                    Clear All BOM
                                </button>
                            </form>
                            <a href="{{ route('outgoing.product-mapping') }}#where-used"
                                class="inline-flex items-center rounded-xl bg-emerald-50 border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                                🔍 Where-Used
                            </a>
                            <a href="{{ route('planning.boms.explosion-search') }}"
                                class="inline-flex items-center rounded-xl bg-blue-50 border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                🌳 Explosion
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto max-h-[700px]">
                    <table class="bom-table min-w-[2000px] w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="px-2 py-3 text-left font-bold sticky-col-1 th-sticky w-12">No</th>
                                <th class="px-2 py-3 text-left font-bold sticky-col-2 th-sticky w-48">FG Name</th>
                                <th class="px-2 py-3 text-left font-bold sticky-col-3 th-sticky w-24">FG Model</th>
                                <th class="px-2 py-3 text-left font-bold sticky-col-4 th-sticky w-48">FG Part No.</th>
                                <th class="px-2 py-3 text-left font-bold whitespace-nowrap">Process Name</th>
                                <th class="px-2 py-3 text-left font-bold whitespace-nowrap">Machine</th>
                                <th class="px-2 py-3 text-left font-bold whitespace-nowrap">WIP Part No.</th>
                                <th class="px-2 py-3 text-right font-bold whitespace-nowrap">Qty.</th>
                                <th class="px-2 py-3 text-left font-bold whitespace-nowrap">UOM</th>
                                <th class="px-2 py-3 text-left font-bold whitespace-nowrap">WIP Part Name</th>
                                <th class="px-2 py-3 text-left font-bold whitespace-nowrap">Material Size</th>
                                <th class="px-2 py-2 text-left font-bold whitespace-nowrap">Material Spec</th>
                                <th class="px-2 py-2 text-left font-bold whitespace-nowrap">Material Name</th>
                                <th class="px-2 py-2 text-left font-bold whitespace-nowrap">Special</th>
                                <th class="px-2 py-2 text-left font-bold whitespace-nowrap">RM Part No.</th>
                                <th class="px-2 py-2 text-left font-bold whitespace-nowrap">Incoming Part</th>
                                <th class="px-2 py-2 text-left font-bold whitespace-nowrap">Make/Buy</th>
                                <th class="px-2 py-2 text-right font-bold whitespace-nowrap">Consump.</th>
                                <th class="px-2 py-2 text-left font-bold whitespace-nowrap">UOM_RM</th>
                                <th
                                    class="px-2 py-2 text-center font-bold sticky right-0 bg-slate-50 z-20 border-l border-slate-200">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($boms as $bom)
                                @php
                                    $bomId = (int) $bom->id;
                                    $fgNo = $bom->part->part_no ?? '-';
                                    $fgName = $bom->part->part_name ?? '-';
                                    $fgModel = $bom->part->model ?? '';
                                    $items = ($bom->items ?? collect())->sortBy(fn($i) => $i->line_no ?? 0)->values();
                                @endphp

                                {{-- Parent / header row --}}
                                <tr class="parent-row">
                                    <td class="px-2 py-3 text-center text-slate-300 sticky-col-1">—</td>
                                    <td class="px-2 py-3 font-bold text-slate-900 whitespace-nowrap sticky-col-2">
                                        {{ $fgName }}
                                    </td>
                                    <td class="px-2 py-3 text-slate-600 whitespace-nowrap sticky-col-3 text-xs">
                                        {{ $fgModel }}
                                    </td>
                                    <td class="px-2 py-3 whitespace-nowrap sticky-col-4">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                class="inline-flex items-center justify-center w-5 h-5 rounded border border-slate-300 bg-white text-slate-700 shadow-sm"
                                                @click="toggle({{ $bomId }})">
                                                <span x-text="expanded[{{ $bomId }}] ? '▾' : '▸'"
                                                    class="text-[10px] font-bold"></span>
                                            </button>
                                            <span
                                                class="font-mono text-[11px] font-black text-indigo-700">{{ $fgNo }}</span>
                                            @if(isset($bom->revision))
                                                <span
                                                    class="px-1.5 py-0.5 rounded text-[9px] font-black bg-blue-600 text-white uppercase">REV
                                                    {{ $bom->revision }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-2 py-3 bg-slate-50/30" colspan="15">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $bom->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                                {{ strtoupper($bom->status) }}
                                            </span>
                                            <span
                                                class="text-[10px] text-slate-400 font-bold uppercase tracking-widest italic">FG
                                                Master Header</span>
                                        </div>
                                    </td>
                                    <td
                                        class="px-2 py-3 text-center whitespace-nowrap sticky right-0 bg-white border-l border-slate-200 shadow-[-4px_0_6px_-1px_rgba(0,0,0,0.05)]">
                                        <div class="flex items-center justify-center gap-1">
                                            <form action="{{ route('planning.boms.update', $bom) }}" method="POST"
                                                class="inline">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status"
                                                    value="{{ $bom->status === 'active' ? 'inactive' : 'active' }}">
                                                <button type="submit" class="action-btn hover:bg-slate-100"
                                                    title="Toggle status">✎</button>
                                            </form>
                                            <button type="button" class="action-btn hover:bg-indigo-50 text-indigo-700"
                                                title="Change FG" @click="openChangeFg(@js([
                                                    'action' => route('planning.boms.update', $bom),
                                                    'part_id' => $bom->part_id,
                                                    'current_label' => ($bom->part?->part_no ?? '-') . ' — ' . ($bom->part?->part_name ?? '-'),
                                                ]))">FG</button>
                                            <form action="{{ route('planning.boms.destroy', $bom) }}" method="POST"
                                                class="inline" onsubmit="return confirm('Delete BOM?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="action-btn hover:bg-red-50 text-red-600"
                                                    title="Delete">🗑</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Lines --}}
                                @forelse($items as $idx => $item)
                                    @php
                                        $lineNo = $item->line_no ?? ($idx + 1);
                                        $wipNo = $item->wip_part_no ?: ($item->wipPart?->part_no ?? '');
                                        $wipName = $item->wip_part_name ?: ($item->wipPart?->part_name ?? '');
                                        $rmNo = $item->component_part_no ?: ($item->componentPart?->part_no ?? '');
                                        $substitutes = $item->substitutes ?? collect();
                                        $subCount = $substitutes->count();
                                    @endphp
                                    <tr class="child-row group" x-show="expanded[{{ $bomId }}]" x-cloak>
                                        <td class="px-2 py-1.5 text-slate-500 font-bold text-center text-xs sticky-col-1">
                                            {{ $lineNo }}
                                        </td>
                                        <td class="px-2 py-1.5 text-slate-300 sticky-col-2" colspan="2">&nbsp;</td>
                                        <td class="px-2 py-1.5 text-slate-300 sticky-col-4">&nbsp;</td>
                                        <td class="px-2 py-1.5 whitespace-nowrap text-xs text-slate-600 font-medium">
                                            {{ $item->process_name ?? '' }}
                                        </td>
                                        <td class="px-2 py-1.5 whitespace-nowrap text-xs text-slate-600">
                                            {{ $item->machine->name ?? '' }}
                                        </td>
                                        <td class="px-2 py-1.5 whitespace-nowrap font-mono-compact font-bold text-slate-800">
                                            {{ $wipNo }}
                                        </td>
                                        <td
                                            class="px-2 py-1.5 text-right whitespace-nowrap font-mono-compact font-bold text-indigo-700 bg-indigo-50/20">
                                            {{ $item->wip_qty !== null ? rtrim(rtrim(number_format((float) $item->wip_qty, 3, '.', ''), '0'), '.') : '' }}
                                        </td>
                                        <td
                                            class="px-2 py-1.5 whitespace-nowrap text-[10px] font-bold text-slate-500 uppercase">
                                            {{ $item->wipUom?->code ?? ($item->wip_uom ?? '') }}
                                        </td>
                                        <td class="px-2 py-1.5 text-xs text-slate-600 max-w-[200px] truncate"
                                            title="{{ $wipName }}">
                                            {{ $wipName }}
                                        </td>
                                        <td class="px-2 py-1.5 whitespace-nowrap text-[10px] text-slate-600">
                                            {{ $item->material_size ?? '' }}
                                        </td>
                                        <td class="px-2 py-1.5 whitespace-nowrap text-[10px] text-slate-600">
                                            {{ $item->material_spec ?? '' }}
                                        </td>
                                        <td class="px-2 py-1.5 whitespace-nowrap text-[10px] text-slate-600">
                                            {{ $item->material_name ?? '' }}
                                        </td>
                                        <td class="px-2 py-1.5 whitespace-nowrap text-[10px] text-slate-500 italic">
                                            {{ $item->special ?? '' }}
                                        </td>
                                        <td class="px-2 py-1.5 whitespace-nowrap font-mono-compact font-bold text-slate-800">
                                            {{ $rmNo }}
                                        </td>
                                        <td class="px-2 py-1.5 whitespace-nowrap text-xs">
                                            @if($item->incomingPart)
                                                <div class="font-mono-compact font-bold text-teal-700">
                                                    {{ $item->incomingPart->part_no }}
                                                </div>
                                                <div class="text-[10px] text-slate-500 truncate max-w-[150px]"
                                                    title="{{ $item->incomingPart->vendor?->name ?? '' }}">
                                                    {{ $item->incomingPart->part_name_gci ?: ($item->incomingPart->part_name_vendor ?? '') }}
                                                    @if($item->incomingPart->vendor)
                                                        <span class="text-slate-400">· {{ $item->incomingPart->vendor->name }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-slate-300">—</span>
                                            @endif
                                        </td>
                                        <td class="px-2 py-1.5 text-center whitespace-nowrap">
                                            @php $mob = strtolower((string) ($item->make_or_buy ?? 'buy')); @endphp
                                            <span
                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black {{ $mob === 'make' ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800' }} border {{ $mob === 'make' ? 'border-indigo-200' : 'border-amber-200' }}">
                                                {{ strtoupper($mob) }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-2 py-1.5 text-right whitespace-nowrap font-mono-compact font-bold text-emerald-700 bg-emerald-50/20">
                                            {{ rtrim(rtrim(number_format((float) $item->usage_qty, 3, '.', ''), '0'), '.') }}
                                        </td>
                                        <td
                                            class="px-2 py-1.5 whitespace-nowrap text-[10px] font-bold text-slate-500 uppercase">
                                            {{ $item->consumptionUom?->code ?? ($item->consumption_uom ?? '') }}
                                        </td>
                                        <td
                                            class="px-2 py-1.5 text-center whitespace-nowrap sticky right-0 bg-white border-l border-slate-200 shadow-[-4px_0_6px_-1px_rgba(0,0,0,0.05)]">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button"
                                                    class="relative h-7 px-2 rounded-lg border border-orange-200 bg-orange-50/60 hover:bg-orange-100 text-orange-700 flex items-center justify-center text-[10px] gap-1 font-semibold transition-all"
                                                    title="Manage Substitutes" @click="openSubstitutePanel(@js([
                                                        'bom_item_id' => $item->id,
                                                        'action' => route('planning.bom-items.substitutes.store', $item),
                                                        'fg_label' => $fgNo . ' — ' . $fgName,
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
                                                            'part_no' => $s->part?->part_no,
                                                            'part_name' => $s->part?->part_name,
                                                            'incoming_part_no' => $s->incomingPart?->part_no,
                                                            'incoming_part_label' => $s->incomingPart ? ($s->incomingPart->part_no . ($s->incomingPart->vendor ? ' [' . $s->incomingPart->vendor->name . ']' : '')) : null,
                                                            'ratio' => $s->ratio,
                                                            'priority' => $s->priority,
                                                            'status' => $s->status,
                                                            'notes' => $s->notes,
                                                            'delete_url' => route('planning.bom-item-substitutes.destroy', $s),
                                                        ])->values(),
                                                    ]))">
                                                    ♻ Subs
                                                    @if ($subCount > 0)
                                                        <span
                                                            class="inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-orange-600 text-white text-[9px] font-bold">
                                                            {{ $subCount }}
                                                        </span>
                                                    @endif
                                                </button>
                                                <button type="button" class="action-btn hover:bg-slate-100" title="Edit" @click="openLineModal(@js([
                                                    'mode' => 'edit',
                                                    'action' => route('planning.boms.items.store', $bom),
                                                    'bom_item_id' => $item->id,
                                                    'fg_label' => $fgNo . ' — ' . $fgName,
                                                    'line_no' => $lineNo,
                                                    'process_name' => $item->process_name,
                                                    'machine_id' => $item->machine_id,
                                                    'wip_part_id' => $item->wip_part_id,
                                                    'wip_part_no' => $item->wip_part_no,
                                                    'wip_qty' => $item->wip_qty,
                                                    'wip_uom' => $item->wip_uom,
                                                    'wip_part_name' => $item->wip_part_name,
                                                    'material_size' => $item->material_size,
                                                    'material_spec' => $item->material_spec,
                                                    'material_name' => $item->material_name,
                                                    'special' => $item->special,
                                                    'component_part_id' => $item->component_part_id,
                                                    'component_part_no' => $item->component_part_no,
                                                    'incoming_part_id' => $item->incoming_part_id,
                                                    'make_or_buy' => $item->make_or_buy,
                                                    'usage_qty' => $item->usage_qty,
                                                    'consumption_uom' => $item->consumption_uom,
                                                    'consumption_uom_id' => $item->consumption_uom_id,
                                                    'wip_uom_id' => $item->wip_uom_id,
                                                    'scrap_factor' => $item->scrap_factor,
                                                    'yield_factor' => $item->yield_factor,
                                                ]))">
                                                    ✎
                                                </button>
                                                <form action="{{ route('planning.boms.items.destroy', $item) }}" method="POST"
                                                    class="inline" onsubmit="return confirm('Delete BOM line?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn hover:bg-red-50 text-red-600"
                                                        title="Delete">🗑</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="bg-slate-50/50" x-show="expanded[{{ $bomId }}]" x-cloak>
                                        <td colspan="20" class="px-3 py-4 text-center text-slate-500">No BOM lines</td>
                                    </tr>
                                @endforelse

                                {{-- Add line row --}}
                                <tr class="bg-white" x-show="expanded[{{ $bomId }}]" x-cloak>
                                    <td colspan="20" class="px-3 py-3">
                                        <button type="button"
                                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm"
                                            @click="openLineModal(@js([
                                                'mode' => 'create',
                                                'action' => route('planning.boms.items.store', $bom),
                                                'bom_item_id' => null,
                                                'fg_label' => $fgNo . ' — ' . $fgName,
                                                'line_no' => null,
                                                'process_name' => null,
                                                'machine_id' => null,
                                                'wip_part_id' => null,
                                                'wip_part_no' => null,
                                                'wip_qty' => null,
                                                'wip_uom' => null,
                                                'wip_part_name' => null,
                                                'material_size' => null,
                                                'material_spec' => null,
                                                'material_name' => null,
                                                'special' => null,
                                                'component_part_id' => null,
                                                'component_part_no' => null,
                                                'incoming_part_id' => null,
                                                'make_or_buy' => 'buy',
                                                'usage_qty' => 1,
                                                'consumption_uom' => null,
                                            ]))">
                                            + Add Line
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="20" class="px-4 py-8 text-center text-slate-500">No BOM</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer --}}
                <div class="border-t border-slate-200 p-4 bg-slate-50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="text-sm text-slate-500">
                            <span class="font-semibold">Tip:</span> Expand dulu, lalu klik <span class="font-semibold">+
                                Add Line</span> untuk nambah BOM line. Edit/delete pakai icon di kanan.
                        </div>
                        <div>{{ $boms->links() }}</div>
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
                            <div class="text-sm font-semibold text-slate-900">Add BOM</div>
                        </div>
                        <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50"
                            @click="closeCreate()">✕</button>
                    </div>

                    <form action="{{ route('planning.boms.store') }}" method="POST" class="px-5 py-4 space-y-4">
                        @csrf
                        <div>
                            <label class="text-sm font-semibold text-slate-700">FG Part (Part GCI)</label>
                            <select name="part_id" class="mt-1 w-full rounded-xl border-slate-200" required>
                                <option value="" disabled selected>Select part</option>
                                @foreach ($fgParts as $p)
                                    <option value="{{ $p->id }}">{{ $p->part_no }} — {{ $p->part_name ?? '-' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Status</label>
                            <select name="status" class="mt-1 w-full rounded-xl border-slate-200" required>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50"
                                @click="closeCreate()">Cancel</button>
                            <button type="submit"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Create</button>
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
                            <div class="text-sm font-semibold text-slate-900">Import BOM (Excel)</div>
                        </div>
                        <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50"
                            @click="closeImport()">✕</button>
                    </div>

                    <form action="{{ route('planning.boms.import') }}" method="POST" enctype="multipart/form-data"
                        class="px-5 py-4 space-y-4" onsubmit="showLoading('Importing BOM...')">
                        @csrf

                        <div class="text-sm text-slate-700 space-y-1">
                            <div>Gunakan format kolom yang sama seperti hasil <span class="font-semibold">Export
                                    BOM</span>.
                            </div>
                            <div class="text-xs text-slate-500">Master <span class="font-mono text-indigo-700">FG Part
                                    No.</span> harus sudah terdaftar di GCI Parts sebelum import. Komponen RM/WIP akan
                                disimpan langsung di level BOM.</div>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-slate-700">File</label>
                            <input type="file" name="file" accept=".xlsx,.xls,.csv"
                                class="mt-1 w-full rounded-xl border-slate-200" required>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50"
                                @click="closeImport()">Cancel</button>
                            <button type="submit"
                                class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold">Import</button>
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
                            <div class="text-sm font-semibold text-slate-900">Import Substitutes (Excel)</div>
                        </div>
                        <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50"
                            @click="closeImportSubstitute()">✕</button>
                    </div>

                    <div class="px-5 pt-4 pb-2 text-xs text-slate-600">
                        Pilih mode import:
                        <span class="font-semibold">Per-FG</span> (butuh `fg_part_no`) atau
                        <span class="font-semibold">Mapping</span> (RM→Substitute, apply ke semua BOM line yang match).
                    </div>

                    <div class="px-5 pb-2 flex gap-2">
                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border"
                            :class="subImportMode === 'fg' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                            @click="subImportMode = 'fg'">
                            Per-FG
                        </button>
                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border"
                            :class="subImportMode === 'mapping' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                            @click="subImportMode = 'mapping'">
                            Mapping
                        </button>
                    </div>

                    <div x-show="subImportMode === 'fg'" x-cloak>
                        <form action="{{ route('planning.boms.substitutes.import') }}" method="POST"
                            enctype="multipart/form-data" class="px-5 py-4 space-y-4"
                            onsubmit="showLoading('Importing Substitutes...')">
                            @csrf

                            <div class="text-sm text-slate-700 space-y-1">
                                <div class="flex justify-between items-center">
                                    <div>Upload Excel dengan kolom:</div>
                                    <a href="{{ route('planning.boms.substitutes.template') }}"
                                        class="text-xs text-blue-600 hover:text-blue-800 underline font-semibold">Download
                                        Template</a>
                                </div>
                                <div class="font-mono text-xs bg-slate-100 p-2 rounded">fg_part_no, fg_part_name,
                                    component_part_no, component_part_name, substitute_part_no, substitute_part_name,
                                    ratio,
                                    priority, status</div>
                                <div class="text-xs text-slate-500">Pastikan FG Part No dan Component Part No sesuai
                                    untuk
                                    mencocokan BOM line yang tepat.</div>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-slate-700">File</label>
                                <input type="file" name="file" accept=".xlsx,.xls,.csv"
                                    class="mt-1 w-full rounded-xl border-slate-200" required>
                            </div>

                            <div class="flex items-center gap-2">
                                <input type="hidden" name="auto_create_parts" value="0">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="auto_create_parts" value="1"
                                        class="rounded border-slate-300">
                                    Auto-create missing substitute part (optional)
                                </label>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button"
                                    class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50"
                                    @click="closeImportSubstitute()">Cancel</button>
                                <button type="submit"
                                    class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-semibold">Import
                                    Substitutes</button>
                            </div>
                        </form>
                    </div>

                    <div x-show="subImportMode === 'mapping'" x-cloak>
                        <form action="{{ route('planning.boms.substitutes.import-mapping') }}" method="POST"
                            enctype="multipart/form-data" class="px-5 py-4 space-y-4"
                            onsubmit="showLoading('Importing Substitute Mapping...')">
                            @csrf

                            <div class="text-sm text-slate-700 space-y-1">
                                <div class="flex justify-between items-center">
                                    <div>Upload Excel mapping:</div>
                                    <a href="{{ route('planning.boms.substitutes.template-mapping') }}"
                                        class="text-xs text-blue-600 hover:text-blue-800 underline font-semibold">Download
                                        Template</a>
                                </div>
                                <div class="font-mono text-xs bg-slate-100 p-2 rounded">component_part_no,
                                    component_part_name, substitute_part_no, substitute_part_name, supplier, ratio,
                                    priority, status</div>
                                <div class="text-xs text-slate-500">Akan diterapkan ke semua BOM line yang memakai
                                    `component_part_no`. Disarankan matikan auto-create agar tidak membuat part baru
                                    tanpa
                                    kontrol.</div>
                            </div>

                            <div class="flex items-center gap-2">
                                <input type="hidden" name="auto_create_parts" value="0">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="auto_create_parts" value="1"
                                        class="rounded border-slate-300">
                                    Auto-create missing substitute part (optional)
                                </label>
                            </div>

                            <div>
                                <label class="text-sm font-semibold text-slate-700">File</label>
                                <input type="file" name="file" accept=".xlsx,.xls,.csv"
                                    class="mt-1 w-full rounded-xl border-slate-200" required>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button"
                                    class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50"
                                    @click="closeImportSubstitute()">Cancel</button>
                                <button type="submit"
                                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Import
                                    Mapping</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Line modal --}}
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
                x-show="lineModalOpen" x-cloak @keydown.escape.window="closeLineModal()">
                <div class="w-full max-w-4xl bg-white rounded-2xl shadow-2xl border border-slate-200/60">
                    <div
                        class="flex items-center justify-between px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-white">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <div class="text-sm font-semibold text-slate-900"
                                x-text="lineForm.mode === 'edit' ? 'Edit BOM Line' : 'Add BOM Line'"></div>
                        </div>
                        <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50"
                            @click="closeLineModal()">✕</button>
                    </div>

                    <form :action="lineForm.action" method="POST" class="px-5 py-4 space-y-4">
                        @csrf
                        <template x-if="lineForm.bom_item_id">
                            <input type="hidden" name="bom_item_id" :value="lineForm.bom_item_id">
                        </template>

                        <div class="text-sm text-slate-700">
                            <div class="font-semibold" x-text="lineForm.fg_label"></div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-600">No</label>
                                <input type="number" min="1" name="line_no"
                                    class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.line_no">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Process Name</label>
                                <input type="text" name="process_name" class="mt-1 w-full rounded-xl border-slate-200"
                                    x-model="lineForm.process_name">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Machine</label>
                                <select name="machine_id" class="mt-1 w-full rounded-xl border-slate-200"
                                    x-model="lineForm.machine_id">
                                    <option value="">-- Select Machine --</option>
                                    @foreach ($machines as $machine)
                                        <option value="{{ $machine->id }}">{{ $machine->code }} - {{ $machine->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-600">WIP Part No.</label>
                                <div class="flex gap-2">
                                    <template x-if="!lineForm.wip_part_id">
                                        <input type="text" name="wip_part_no"
                                            class="mt-1 w-full rounded-xl border-slate-200"
                                            x-model="lineForm.wip_part_no" placeholder="Part No String">
                                    </template>
                                    <select name="wip_part_id" class="mt-1 w-full rounded-xl border-slate-200"
                                        x-model="lineForm.wip_part_id">
                                        <option value="">(Use text / -)</option>
                                        @foreach(($wipParts ?? []) as $p)
                                            <option value="{{ optional($p)->id }}">{{ optional($p)->part_no }} —
                                                {{ optional($p)->part_name ?? '-' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Qty.</label>
                                <input type="number" step="any" min="0" name="wip_qty"
                                    class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.wip_qty">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">UOM</label>
                                <select name="wip_uom_id" class="mt-1 w-full rounded-xl border-slate-200"
                                    x-model="lineForm.wip_uom_id">
                                    <option value="">-</option>
                                    @foreach(($uoms ?? []) as $uom)
                                        <option value="{{ optional($uom)->id }}">{{ optional($uom)->code }} -
                                            {{ optional($uom)->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-600">WIP Part Name</label>
                                <input type="text" name="wip_part_name" class="mt-1 w-full rounded-xl border-slate-200"
                                    x-model="lineForm.wip_part_name" placeholder="Optional override">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">spesial</label>
                                <input type="text" name="special" class="mt-1 w-full rounded-xl border-slate-200"
                                    x-model="lineForm.special">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Material Size</label>
                                <input type="text" name="material_size" class="mt-1 w-full rounded-xl border-slate-200"
                                    x-model="lineForm.material_size">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Material Spec</label>
                                <input type="text" name="material_spec" class="mt-1 w-full rounded-xl border-slate-200"
                                    x-model="lineForm.material_spec">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Material Name</label>
                                <input type="text" name="material_name" class="mt-1 w-full rounded-xl border-slate-200"
                                    x-model="lineForm.material_name">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div class="md:col-span-2">
                                <label class="text-xs font-semibold text-slate-600">Component Part (GCI /
                                    String)</label>
                                <div class="flex gap-2">
                                    <template x-if="!lineForm.component_part_id">
                                        <input type="text" name="component_part_no"
                                            class="mt-1 w-full rounded-xl border-slate-200"
                                            x-model="lineForm.component_part_no" placeholder="RM/Part No">
                                    </template>
                                    <select name="component_part_id" class="mt-1 w-full rounded-xl border-slate-200"
                                        x-model="lineForm.component_part_id">
                                        <option value="">(Use text)</option>
                                        <template x-if="['buy','free_issue'].includes((lineForm.make_or_buy || 'buy'))">
                                            <optgroup label="BUY (RM)">
                                                @foreach (($rmParts ?? []) as $c)
                                                    <option value="{{ optional($c)->id }}">{{ optional($c)->part_no }} —
                                                        {{ optional($c)->part_name ?? '-' }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        </template>
                                        <template x-if="(lineForm.make_or_buy || 'buy') === 'make'">
                                            <optgroup label="MAKE (FG/WIP)">
                                                @foreach (($makeParts ?? []) as $c)
                                                    <option value="{{ optional($c)->id }}">{{ optional($c)->part_no }} —
                                                        {{ optional($c)->part_name ?? '-' }}
                                                        ({{ strtoupper(optional($c)->classification ?? '') }})
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        </template>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Make / Buy</label>
                                <select name="make_or_buy" class="mt-1 w-full rounded-xl border-slate-200"
                                    x-model="lineForm.make_or_buy">
                                    <option value="buy">BUY</option>
                                    <option value="make">MAKE</option>
                                    <option value="free_issue">FREE ISSUE</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Consumption</label>
                                <input type="number" step="any" min="0" name="usage_qty"
                                    class="mt-1 w-full rounded-xl border-slate-200" required
                                    x-model="lineForm.usage_qty">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-600 block mb-1">Incoming Part (Vendor /
                                    RM)</label>
                                <select name="incoming_part_id" class="w-full rounded-xl border-slate-200 bg-slate-50"
                                    x-model="lineForm.incoming_part_id">
                                    <option value="">(No incoming part linked)</option>
                                    @foreach(($incomingParts ?? []) as $ip)
                                        <option value="{{ $ip->id }}">
                                            {{ $ip->part_no }} — {{ $ip->part_name_gci ?: ($ip->part_name_vendor ?? '-') }}
                                            @if($ip->vendor) [{{ $ip->vendor->name }}] @endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="text-[10px] text-slate-500 mt-0.5">Link RM ke Part Incoming yang terdaftar
                                    di
                                    receiving.</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-600">UOM (Consumption)</label>
                                <select name="consumption_uom_id" class="mt-1 w-full rounded-xl border-slate-200"
                                    x-model="lineForm.consumption_uom_id">
                                    <option value="">-</option>
                                    @foreach(($uoms ?? []) as $uom)
                                        <option value="{{ optional($uom)->id }}">{{ optional($uom)->code }} -
                                            {{ optional($uom)->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Scrap Factor</label>
                                <input type="number" step="0.01" min="0" max="1" name="scrap_factor"
                                    class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.scrap_factor"
                                    placeholder="0.05 = 5%">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Yield Factor</label>
                                <input type="number" step="0.01" min="0" max="1" name="yield_factor"
                                    class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.yield_factor"
                                    placeholder="0.95 = 95%">
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50"
                                @click="closeLineModal()">Cancel</button>
                            <button type="submit"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function planningBoms() {
                    return {
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
                        },
                        expanded: {},
                        lineForm: {
                            mode: 'create',
                            action: '',
                            bom_item_id: null,
                            fg_label: '',
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
                            component_part_no: '',
                            incoming_part_id: '',
                            make_or_buy: 'buy',
                            usage_qty: '1',
                            consumption_uom: '',
                            wip_part_no: '',
                            consumption_uom_id: '',
                            wip_uom_id: '',
                            scrap_factor: 0,
                            yield_factor: 1,

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
                            };
                            this.substituteOpen = true;
                        },
                        closeSubstitutePanel() { this.substituteOpen = false; },
                        openLineModal(payload) {
                            this.lineForm = {
                                mode: payload.mode,
                                action: payload.action,
                                bom_item_id: payload.bom_item_id,
                                fg_label: payload.fg_label,
                                line_no: payload.line_no,
                                process_name: payload.process_name ?? '',
                                machine_id: payload.machine_id ?? '',
                                wip_part_id: payload.wip_part_id ?? '',
                                wip_qty: payload.wip_qty ?? '',
                                wip_uom: payload.wip_uom ?? '',
                                wip_part_name: payload.wip_part_name ?? '',
                                material_size: payload.material_size ?? '',
                                material_spec: payload.material_spec ?? '',
                                material_name: payload.material_name ?? '',
                                special: payload.special ?? '',
                                component_part_id: payload.component_part_id ?? '',
                                component_part_no: payload.component_part_no ?? '',
                                incoming_part_id: payload.incoming_part_id ?? '',
                                make_or_buy: payload.make_or_buy ?? 'buy',
                                usage_qty: payload.usage_qty ?? '1',
                                consumption_uom: payload.consumption_uom ?? '',
                                wip_part_no: payload.wip_part_no ?? '',
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
                                Change FG for BOM
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
                            <label class="text-xs font-semibold text-slate-600">New FG Part</label>
                            <select name="part_id" class="mt-1 w-full rounded-xl border-slate-200" required
                                x-model="changeFgForm.part_id">
                                <option value="" disabled>Select FG…</option>
                                @foreach(($fgParts ?? []) as $p)
                                    <option value="{{ optional($p)->id }}">{{ optional($p)->part_no }} —
                                        {{ optional($p)->part_name ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-2 text-xs text-slate-500">
                                Note: FG hanya bisa dipindah ke part classification <span
                                    class="font-semibold">FG</span>
                                dan tidak boleh sudah punya BOM lain.
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50"
                                @click="closeChangeFg()">Cancel</button>
                            <button type="submit"
                                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save</button>
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
                                <div class="text-sm font-semibold">Manage Substitutes</div>
                                <div class="text-xs text-orange-100" x-text="substituteForm.fg_label"></div>
                            </div>
                            <button type="button" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20"
                                @click="closeSubstitutePanel()">✕</button>
                        </div>
                    </div>

                    <div class="p-5 space-y-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
                            <div class="font-semibold text-slate-900">BOM Line</div>
                            <div class="mt-2 space-y-1 text-slate-700">
                                <div><span class="text-slate-500">Line:</span> <span class="font-mono"
                                        x-text="substituteForm.line_no"></span></div>
                                <div><span class="text-slate-500">Process:</span> <span
                                        x-text="substituteForm.process_name"></span></div>
                                <div><span class="text-slate-500">WIP:</span> <span class="font-mono"
                                        x-text="substituteForm.wip_part_no"></span></div>
                                <div><span class="text-slate-500">RM:</span> <span class="font-mono font-semibold"
                                        x-text="substituteForm.component_part_no"></span></div>
                                <div><span class="text-slate-500">Material:</span> <span class="font-semibold"
                                        x-text="substituteForm.material_name"></span></div>
                                <div><span class="text-slate-500">Spec:</span> <span
                                        x-text="substituteForm.material_spec"></span></div>
                                <div><span class="text-slate-500">Consumption:</span> <span class="font-mono"
                                        x-text="substituteForm.consumption"></span> <span class="font-mono"
                                        x-text="substituteForm.consumption_uom"></span></div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-orange-200 bg-orange-50 p-3 text-xs text-orange-900">
                            Substitutes = alternatif RM untuk menggantikan RM utama saat MRP/PR (priority & ratio).
                        </div>

                        <div class="space-y-2">
                            <div class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Existing
                                Substitutes
                            </div>
                            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                                <table class="min-w-full text-xs divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr class="text-slate-600 uppercase tracking-wider">
                                            <th class="px-3 py-2 text-left font-semibold">Part</th>
                                            <th class="px-3 py-2 text-left font-semibold">Incoming</th>
                                            <th class="px-3 py-2 text-right font-semibold">Ratio</th>
                                            <th class="px-3 py-2 text-right font-semibold">Prio</th>
                                            <th class="px-3 py-2 text-left font-semibold">Status</th>
                                            <th class="px-3 py-2 text-right font-semibold">Act</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-if="(substituteForm.substitutes || []).length === 0">
                                            <tr>
                                                <td colspan="6" class="px-3 py-4 text-center text-slate-500">No
                                                    substitutes
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
                                                        <span class="text-slate-300">—</span>
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
                                                    <form :action="s.delete_url" method="POST"
                                                        onsubmit="return confirm('Remove substitute?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="w-9 h-9 inline-flex items-center justify-center rounded-xl border border-slate-200 hover:bg-red-50 text-red-600"
                                                            title="Delete">🗑</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Add / Update
                                Substitute</div>
                            <form :action="substituteForm.action" method="POST"
                                class="rounded-xl border border-slate-200 p-4 space-y-3 bg-white">
                                @csrf
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Substitute Part (GCI)</label>
                                    <select name="substitute_part_id" class="mt-1 w-full rounded-xl border-slate-200"
                                        required>
                                        <option value="" disabled selected>Select part</option>
                                        @foreach(($rmParts ?? []) as $p)
                                            <option value="{{ optional($p)->id }}">{{ optional($p)->part_no }} —
                                                {{ optional($p)->part_name ?? '-' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Incoming Part (Vendor)</label>
                                    <select name="incoming_part_id" class="mt-1 w-full rounded-xl border-slate-200">
                                        <option value="">(No incoming part linked)</option>
                                        @foreach(($incomingParts ?? []) as $ip)
                                            <option value="{{ $ip->id }}">
                                                {{ $ip->part_no }} —
                                                {{ $ip->part_name_gci ?: ($ip->part_name_vendor ?? '-') }}
                                                @if($ip->vendor) [{{ $ip->vendor->name }}] @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="text-[10px] text-slate-500 mt-0.5">Link substitute ke Part Incoming.
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600">Ratio</label>
                                        <input type="number" step="0.001" min="0.001" name="ratio" value="1"
                                            class="mt-1 w-full rounded-xl border-slate-200">
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600">Priority</label>
                                        <input type="number" min="1" name="priority" value="1"
                                            class="mt-1 w-full rounded-xl border-slate-200">
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-600">Status</label>
                                        <select name="status" class="mt-1 w-full rounded-xl border-slate-200">
                                            <option value="active" selected>Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Notes</label>
                                    <input type="text" name="notes" maxlength="255"
                                        class="mt-1 w-full rounded-xl border-slate-200"
                                        placeholder="Optional (e.g. thicker / other supplier)">
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit"
                                        class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-semibold">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</x-app-layout>