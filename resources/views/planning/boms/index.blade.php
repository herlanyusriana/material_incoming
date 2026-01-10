<x-app-layout>
    <x-slot name="header">
        Planning • BOM GCI
    </x-slot>

	    <div class="py-6" x-data="planningBoms()">
	        <div class="max-w-screen-2xl mx-auto px-2 sm:px-4 lg:px-6 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    <div class="font-semibold">Validation error</div>
                    <ul class="mt-1 list-disc pl-5 space-y-0.5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

	            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-8 space-y-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">⌕</span>
                            <input
                                name="q"
                                value="{{ $q ?? '' }}"
                                class="rounded-xl border-slate-200 pl-10"
                                placeholder="Search BOM..."
                            >
                        </div>

                        <select name="gci_part_id" class="rounded-xl border-slate-200">
                            <option value="">All Part GCI</option>
                            @foreach ($gciParts as $p)
                                <option value="{{ $p->id }}" @selected((string) ($gciPartId ?? '') === (string) $p->id)>{{ $p->part_no }} — {{ $p->part_name ?? '-' }}</option>
                            @endforeach
                        </select>

                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                    </form>

	                    <div class="flex items-center gap-2">
	                        <a
	                            href="{{ route('planning.boms.export', request()->query()) }}"
	                            class="px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 font-semibold"
	                        >
	                            Export
	                        </a>
	                        <button
	                            type="button"
	                            class="px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 font-semibold"
	                            @click="openImport()"
	                        >
	                            Import
	                        </button>
	                        <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold" @click="openCreate()">
	                            + Add BOM
	                        </button>
	                    </div>
	                </div>

	                <div class="overflow-x-auto border border-slate-200 rounded-xl">
	                    <table class="min-w-[1800px] w-full text-base divide-y divide-slate-200">
	                        <thead class="bg-slate-50">
	                            <tr class="text-slate-600 text-sm uppercase tracking-wider">
                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">No</th>
                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">FG Name</th>
                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">FG Model</th>
                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">FG Part No.</th>
                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">Process Name</th>
                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">Machine Name</th>
                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">WIP Part No.</th>
                                <th class="px-3 py-3 text-right font-semibold whitespace-nowrap">Qty.</th>
                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">UOM</th>
                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">WIP Part Name</th>
                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">Material Size</th>
                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">Material Spec</th>
                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">Material Name</th>
	                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">spesial</th>
	                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">RM Part No.</th>
	                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">Make/Buy</th>
	                                <th class="px-3 py-3 text-right font-semibold whitespace-nowrap">Consumption</th>
	                                <th class="px-3 py-3 text-left font-semibold whitespace-nowrap">UOM_RM</th>
	                                <th class="px-3 py-3 text-right font-semibold whitespace-nowrap">Actions</th>
	                            </tr>
	                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($boms as $bom)
                                @php
                                    $bomId = (int) $bom->id;
                                    $fgNo = $bom->part->part_no ?? '-';
                                    $fgName = $bom->part->part_name ?? '-';
                                    $fgModel = $bom->part->model ?? '';
                                    $items = ($bom->items ?? collect())->sortBy(fn ($i) => $i->line_no ?? 0)->values();
                                @endphp

                                {{-- Parent / header row --}}
                                <tr class="hover:bg-slate-50">
                                    <td class="px-3 py-3 text-slate-400">—</td>
                                    <td class="px-3 py-3 font-semibold text-slate-900 whitespace-nowrap">{{ $fgName }}</td>
                                    <td class="px-3 py-3 text-slate-700 whitespace-nowrap">{{ $fgModel }}</td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <button type="button" class="inline-flex items-center gap-2" @click="toggle({{ $bomId }})">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-md border border-slate-200 bg-white text-slate-700">
                                                <template x-if="expanded[{{ $bomId }}]"><span>▾</span></template>
                                                <template x-if="!expanded[{{ $bomId }}]"><span>▸</span></template>
                                            </span>
                                            <span class="font-mono text-xs font-semibold">{{ $fgNo }}</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $bom->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                                {{ strtoupper($bom->status) }}
                                            </span>
                                        </button>
                                    </td>
	                                    <td class="px-3 py-3" colspan="14">&nbsp;</td>
                                    <td class="px-3 py-3 text-right whitespace-nowrap">
                                        <form action="{{ route('planning.boms.update', $bom) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status" value="{{ $bom->status === 'active' ? 'inactive' : 'active' }}">
                                            <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" title="Toggle status">
                                                ✎
                                            </button>
                                        </form>
                                        <form action="{{ route('planning.boms.destroy', $bom) }}" method="POST" class="inline" onsubmit="return confirm('Delete BOM?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 hover:bg-red-50 text-red-600" title="Delete">
                                                🗑
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Lines --}}
	                                @forelse($items as $idx => $item)
	                                    @php
	                                        $lineNo = $item->line_no ?? ($idx + 1);
	                                        $wipNo = $item->wipPart?->part_no ?? '';
	                                        $wipName = $item->wip_part_name ?: ($item->wipPart?->part_name ?? '');
	                                        $rmNo = $item->componentPart?->part_no ?? '';
	                                        $substitutes = $item->substitutes ?? collect();
	                                        $subCount = $substitutes->count();
	                                    @endphp
	                                    <tr class="bg-slate-50/50" x-show="expanded[{{ $bomId }}]" x-cloak>
	                                        <td class="px-3 py-2 text-slate-700 whitespace-nowrap">{{ $lineNo }}</td>
                                        <td class="px-3 py-2 text-slate-300" colspan="2">&nbsp;</td>
                                        <td class="px-3 py-2 text-slate-300">&nbsp;</td>
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $item->process_name ?? '' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $item->machine_name ?? '' }}</td>
	                                        <td class="px-3 py-2 whitespace-nowrap font-mono text-sm">{{ $wipNo }}</td>
	                                        <td class="px-3 py-2 text-right whitespace-nowrap font-mono text-sm">{{ $item->wip_qty !== null ? rtrim(rtrim(number_format((float) $item->wip_qty, 3, '.', ''), '0'), '.') : '' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $item->wip_uom ?? '' }}</td>
                                        <td class="px-3 py-2">{{ $wipName }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $item->material_size ?? '' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $item->material_spec ?? '' }}</td>
	                                        <td class="px-3 py-2 whitespace-nowrap">{{ $item->material_name ?? '' }}</td>
	                                        <td class="px-3 py-2 whitespace-nowrap">{{ $item->special ?? '' }}</td>
	                                        <td class="px-3 py-2 whitespace-nowrap font-mono text-sm">{{ $rmNo }}</td>
	                                        <td class="px-3 py-2 whitespace-nowrap">
                                                @php $mob = strtolower((string) ($item->make_or_buy ?? 'buy')); @endphp
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $mob === 'make' ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800' }}">
                                                    {{ strtoupper($mob) }}
                                                </span>
                                            </td>
	                                        <td class="px-3 py-2 text-right whitespace-nowrap font-mono text-sm">{{ rtrim(rtrim(number_format((float) $item->usage_qty, 3, '.', ''), '0'), '.') }}</td>
	                                        <td class="px-3 py-2 whitespace-nowrap">{{ $item->consumption_uom ?? '' }}</td>
	                                        <td class="px-3 py-2 text-right whitespace-nowrap">
	                                            <button
	                                                type="button"
	                                                class="relative inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 hover:bg-orange-50 text-orange-700"
	                                                title="Manage substitutes"
	                                                @click="openSubstitutePanel(@js([
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
	                                                    'substitutes' => $substitutes->sortBy(fn ($s) => (int) ($s->priority ?? 1))->map(fn ($s) => [
	                                                        'id' => $s->id,
	                                                        'part_no' => $s->part?->part_no,
	                                                        'part_name' => $s->part?->part_name,
	                                                        'ratio' => $s->ratio,
	                                                        'priority' => $s->priority,
	                                                        'status' => $s->status,
	                                                        'notes' => $s->notes,
	                                                        'delete_url' => route('planning.bom-item-substitutes.destroy', $s),
	                                                    ])->values(),
	                                                ]))"
	                                            >
	                                                ♻
	                                                @if ($subCount > 0)
	                                                    <span class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-orange-600 text-white text-[10px] font-bold">
	                                                        {{ $subCount }}
	                                                    </span>
	                                                @endif
	                                            </button>
	                                            <button
	                                                type="button"
	                                                class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50"
	                                                title="Edit line"
                                                @click="openLineModal(@js([
                                                    'mode' => 'edit',
                                                    'action' => route('planning.boms.items.store', $bom),
                                                    'bom_item_id' => $item->id,
                                                    'fg_label' => $fgNo . ' — ' . $fgName,
                                                    'line_no' => $lineNo,
                                                    'process_name' => $item->process_name,
                                                    'machine_name' => $item->machine_name,
                                                    'wip_part_id' => $item->wip_part_id,
                                                    'wip_qty' => $item->wip_qty,
                                                    'wip_uom' => $item->wip_uom,
                                                    'wip_part_name' => $item->wip_part_name,
                                                    'material_size' => $item->material_size,
                                                    'material_spec' => $item->material_spec,
	                                                    'material_name' => $item->material_name,
	                                                    'special' => $item->special,
	                                                    'component_part_id' => $item->component_part_id,
	                                                    'make_or_buy' => $item->make_or_buy,
	                                                    'usage_qty' => $item->usage_qty,
	                                                    'consumption_uom' => $item->consumption_uom,
	                                                ]))"
                                            >
                                                ✎
                                            </button>
                                            <form action="{{ route('planning.boms.items.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Delete BOM line?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 hover:bg-red-50 text-red-600" title="Delete">
                                                    🗑
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
	                                    <tr class="bg-slate-50/50" x-show="expanded[{{ $bomId }}]" x-cloak>
	                                        <td colspan="19" class="px-3 py-4 text-center text-slate-500">No BOM lines</td>
	                                    </tr>
                                @endforelse

                                {{-- Add line row --}}
	                                <tr class="bg-white" x-show="expanded[{{ $bomId }}]" x-cloak>
	                                    <td colspan="19" class="px-3 py-3">
                                        <button
                                            type="button"
                                            class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold"
                                            @click="openLineModal(@js([
                                                'mode' => 'create',
                                                'action' => route('planning.boms.items.store', $bom),
                                                'bom_item_id' => null,
                                                'fg_label' => $fgNo . ' — ' . $fgName,
                                                'line_no' => null,
                                                'process_name' => null,
                                                'machine_name' => null,
                                                'wip_part_id' => null,
                                                'wip_qty' => null,
                                                'wip_uom' => null,
                                                'wip_part_name' => null,
                                                'material_size' => null,
                                                'material_spec' => null,
	                                                'material_name' => null,
	                                                'special' => null,
	                                                'component_part_id' => null,
	                                                'make_or_buy' => 'buy',
	                                                'usage_qty' => 1,
	                                                'consumption_uom' => null,
	                                            ]))"
                                        >
                                            + Add Line
                                        </button>
                                    </td>
                                </tr>
                            @empty
	                            <tr>
	                                <td colspan="19" class="px-4 py-8 text-center text-slate-500">No BOM</td>
	                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    <span class="font-semibold">Tip:</span> Expand dulu, lalu klik <span class="font-semibold">+ Add Line</span> untuk nambah BOM line. Edit/delete pakai icon di kanan.
                </div>

                <div class="mt-2">
                    {{ $boms->links() }}
                </div>
            </div>
        </div>

	        {{-- Create BOM modal --}}
	        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="modalOpen" x-cloak @keydown.escape.window="closeCreate()">
	            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
	                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
	                    <div class="text-sm font-semibold text-slate-900">Add BOM</div>
	                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="closeCreate()">✕</button>
	                </div>

                <form action="{{ route('planning.boms.store') }}" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-slate-700">FG Part (Part GCI)</label>
                        <select name="part_id" class="mt-1 w-full rounded-xl border-slate-200" required>
                            <option value="" disabled selected>Select part</option>
                            @foreach ($gciParts as $p)
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
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50" @click="closeCreate()">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Create</button>
                    </div>
                </form>
	            </div>
	        </div>

	        {{-- Import BOM modal --}}
	        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="importOpen" x-cloak @keydown.escape.window="closeImport()">
	            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
	                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
	                    <div class="text-sm font-semibold text-slate-900">Import BOM (Excel)</div>
	                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="closeImport()">✕</button>
	                </div>

	                <form action="{{ route('planning.boms.import') }}" method="POST" enctype="multipart/form-data" class="px-5 py-4 space-y-4">
	                    @csrf

	                    <div class="text-sm text-slate-700 space-y-1">
	                        <div>Gunakan format kolom yang sama seperti hasil <span class="font-semibold">Export BOM</span>.</div>
	                        <div class="text-xs text-slate-500">Jika <span class="font-mono">FG Part No.</span>/<span class="font-mono">WIP Part No.</span>/<span class="font-mono">RM Part No.</span> belum ada di master <span class="font-mono">GCI Parts</span>, akan dibuat otomatis saat import.</div>
	                    </div>

	                    <div>
	                        <label class="text-sm font-semibold text-slate-700">File</label>
	                        <input type="file" name="file" accept=".xlsx,.xls,.csv" class="mt-1 w-full rounded-xl border-slate-200" required>
	                    </div>

	                    <div class="flex justify-end gap-2 pt-2">
	                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50" @click="closeImport()">Cancel</button>
	                        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-semibold">Import</button>
	                    </div>
	                </form>
	            </div>
	        </div>

	        {{-- Line modal --}}
	        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="lineModalOpen" x-cloak @keydown.escape.window="closeLineModal()">
	            <div class="w-full max-w-4xl bg-white rounded-2xl shadow-xl border border-slate-200">
	                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
	                    <div class="text-sm font-semibold text-slate-900" x-text="lineForm.mode === 'edit' ? 'Edit BOM Line' : 'Add BOM Line'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="closeLineModal()">✕</button>
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
                            <input type="number" min="1" name="line_no" class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.line_no">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Process Name</label>
                            <input type="text" name="process_name" class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.process_name">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Machine Name</label>
                            <input type="text" name="machine_name" class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.machine_name">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">WIP Part No.</label>
                            <select name="wip_part_id" class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.wip_part_id">
                                <option value="">-</option>
                                @foreach(($wipParts ?? []) as $p)
                                    <option value="{{ $p->id }}">{{ $p->part_no }} — {{ $p->part_name ?? '-' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Qty.</label>
                            <input type="number" step="any" min="0" name="wip_qty" class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.wip_qty">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">UOM</label>
                            <input type="text" name="wip_uom" class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.wip_uom" placeholder="PCS">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">WIP Part Name</label>
                            <input type="text" name="wip_part_name" class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.wip_part_name" placeholder="Optional override">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">spesial</label>
                            <input type="text" name="special" class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.special">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Material Size</label>
                            <input type="text" name="material_size" class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.material_size">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Material Spec</label>
                            <input type="text" name="material_spec" class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.material_spec">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Material Name</label>
                            <input type="text" name="material_name" class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.material_name">
                        </div>
                    </div>

	                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
	                        <div class="md:col-span-2">
	                            <label class="text-xs font-semibold text-slate-600">RM Part No.</label>
	                            <select name="component_part_id" class="mt-1 w-full rounded-xl border-slate-200" required x-model="lineForm.component_part_id">
	                                <option value="" disabled>Select RM part</option>
		                                @foreach (($components ?? []) as $c)
		                                    <option value="{{ $c->id }}">{{ $c->part_no }} — {{ $c->part_name ?? '-' }}</option>
		                                @endforeach
		                            </select>
	                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Make / Buy</label>
                            <select name="make_or_buy" class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.make_or_buy">
                                <option value="buy">BUY</option>
                                <option value="make">MAKE</option>
                            </select>
                        </div>
	                        <div>
	                            <label class="text-xs font-semibold text-slate-600">Consumption</label>
	                            <input type="number" step="any" min="0.0001" name="usage_qty" class="mt-1 w-full rounded-xl border-slate-200" required x-model="lineForm.usage_qty">
	                        </div>
	                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">UOM (Consumption)</label>
                            <input type="text" name="consumption_uom" class="mt-1 w-full rounded-xl border-slate-200" x-model="lineForm.consumption_uom" placeholder="KGM/PCS">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50" @click="closeLineModal()">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>

	        <script>
	            function planningBoms() {
	                return {
	                    modalOpen: false,
	                    importOpen: false,
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
                        machine_name: '',
                        wip_part_id: '',
                        wip_qty: '',
                        wip_uom: '',
                        wip_part_name: '',
                        material_size: '',
                        material_spec: '',
                        material_name: '',
                        special: '',
                        component_part_id: '',
                        make_or_buy: 'buy',
                        usage_qty: '1',
                        consumption_uom: '',
	                    },
	                    openCreate() { this.modalOpen = true; },
	                    closeCreate() { this.modalOpen = false; },
	                    openImport() { this.importOpen = true; },
	                    closeImport() { this.importOpen = false; },
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
	                            machine_name: payload.machine_name ?? '',
	                            wip_part_id: payload.wip_part_id ?? '',
	                            wip_qty: payload.wip_qty ?? '',
	                            wip_uom: payload.wip_uom ?? '',
	                            wip_part_name: payload.wip_part_name ?? '',
	                            material_size: payload.material_size ?? '',
	                            material_spec: payload.material_spec ?? '',
	                            material_name: payload.material_name ?? '',
	                            special: payload.special ?? '',
	                            component_part_id: payload.component_part_id ?? '',
	                            make_or_buy: payload.make_or_buy ?? 'buy',
	                            usage_qty: payload.usage_qty ?? '1',
	                            consumption_uom: payload.consumption_uom ?? '',
	                        };
	                        this.lineModalOpen = true;
	                    },
	                    closeLineModal() { this.lineModalOpen = false; },
	                }
	            }
	        </script>

	        {{-- Substitute drawer --}}
	        <div class="fixed inset-0 z-50" x-show="substituteOpen" x-cloak>
	    <div class="absolute inset-0 bg-slate-900/40" @click="closeSubstitutePanel()"></div>
	    <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl border-l border-slate-200 overflow-y-auto">
	        <div class="sticky top-0 bg-gradient-to-r from-orange-600 to-orange-700 text-white px-5 py-4">
	            <div class="flex items-center justify-between">
	                <div>
	                    <div class="text-sm font-semibold">Manage Substitutes</div>
	                    <div class="text-xs text-orange-100" x-text="substituteForm.fg_label"></div>
	                </div>
	                <button type="button" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20" @click="closeSubstitutePanel()">✕</button>
	            </div>
	        </div>

	        <div class="p-5 space-y-4">
	            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm">
	                <div class="font-semibold text-slate-900">BOM Line</div>
	                <div class="mt-2 space-y-1 text-slate-700">
	                    <div><span class="text-slate-500">Line:</span> <span class="font-mono" x-text="substituteForm.line_no"></span></div>
	                    <div><span class="text-slate-500">Process:</span> <span x-text="substituteForm.process_name"></span></div>
	                    <div><span class="text-slate-500">WIP:</span> <span class="font-mono" x-text="substituteForm.wip_part_no"></span></div>
	                    <div><span class="text-slate-500">RM:</span> <span class="font-mono font-semibold" x-text="substituteForm.component_part_no"></span></div>
	                    <div><span class="text-slate-500">Material:</span> <span class="font-semibold" x-text="substituteForm.material_name"></span></div>
	                    <div><span class="text-slate-500">Spec:</span> <span x-text="substituteForm.material_spec"></span></div>
	                    <div><span class="text-slate-500">Consumption:</span> <span class="font-mono" x-text="substituteForm.consumption"></span> <span class="font-mono" x-text="substituteForm.consumption_uom"></span></div>
	                </div>
	            </div>

	            <div class="rounded-xl border border-orange-200 bg-orange-50 p-3 text-xs text-orange-900">
	                Substitutes = alternatif RM untuk menggantikan RM utama saat MRP/PR (priority & ratio).
	            </div>

	            <div class="space-y-2">
	                <div class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Existing Substitutes</div>
	                <div class="overflow-x-auto border border-slate-200 rounded-xl">
	                    <table class="min-w-full text-xs divide-y divide-slate-200">
	                        <thead class="bg-slate-50">
	                            <tr class="text-slate-600 uppercase tracking-wider">
	                                <th class="px-3 py-2 text-left font-semibold">Part</th>
	                                <th class="px-3 py-2 text-right font-semibold">Ratio</th>
	                                <th class="px-3 py-2 text-right font-semibold">Prio</th>
	                                <th class="px-3 py-2 text-left font-semibold">Status</th>
	                                <th class="px-3 py-2 text-right font-semibold">Act</th>
	                            </tr>
	                        </thead>
	                        <tbody class="divide-y divide-slate-100">
	                            <template x-if="(substituteForm.substitutes || []).length === 0">
	                                <tr>
	                                    <td colspan="5" class="px-3 py-4 text-center text-slate-500">No substitutes</td>
	                                </tr>
	                            </template>
	                            <template x-for="s in (substituteForm.substitutes || [])" :key="s.id">
	                                <tr>
	                                    <td class="px-3 py-2">
	                                        <div class="font-mono text-[11px] font-semibold" x-text="s.part_no || '-'"></div>
	                                        <div class="text-slate-500 truncate" x-text="s.part_name || ''"></div>
	                                        <div class="text-slate-400 truncate" x-text="s.notes || ''"></div>
	                                    </td>
	                                    <td class="px-3 py-2 text-right font-mono" x-text="s.ratio"></td>
	                                    <td class="px-3 py-2 text-right font-mono" x-text="s.priority"></td>
	                                    <td class="px-3 py-2">
	                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
	                                            :class="(s.status || 'active') === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'"
	                                            x-text="(s.status || 'active').toUpperCase()"
	                                        ></span>
	                                    </td>
	                                    <td class="px-3 py-2 text-right">
	                                        <form :action="s.delete_url" method="POST" onsubmit="return confirm('Remove substitute?')">
	                                            @csrf
	                                            @method('DELETE')
	                                            <button type="submit" class="w-9 h-9 inline-flex items-center justify-center rounded-xl border border-slate-200 hover:bg-red-50 text-red-600" title="Delete">🗑</button>
	                                        </form>
	                                    </td>
	                                </tr>
	                            </template>
	                        </tbody>
	                    </table>
	                </div>
	            </div>

	            <div class="space-y-2">
	                <div class="text-xs font-semibold text-slate-700 uppercase tracking-wider">Add / Update Substitute</div>
	                <form :action="substituteForm.action" method="POST" class="rounded-xl border border-slate-200 p-4 space-y-3 bg-white">
	                    @csrf
	                    <div>
	                        <label class="text-xs font-semibold text-slate-600">Substitute Part (GCI)</label>
	                        <select name="substitute_part_id" class="mt-1 w-full rounded-xl border-slate-200" required>
	                            <option value="" disabled selected>Select part</option>
	                            @foreach(($gciParts ?? []) as $p)
	                                <option value="{{ $p->id }}">{{ $p->part_no }} — {{ $p->part_name ?? '-' }}</option>
	                            @endforeach
	                        </select>
	                    </div>
	                    <div class="grid grid-cols-3 gap-2">
	                        <div>
	                            <label class="text-xs font-semibold text-slate-600">Ratio</label>
	                            <input type="number" step="0.001" min="0.001" name="ratio" value="1" class="mt-1 w-full rounded-xl border-slate-200">
	                        </div>
	                        <div>
	                            <label class="text-xs font-semibold text-slate-600">Priority</label>
	                            <input type="number" min="1" name="priority" value="1" class="mt-1 w-full rounded-xl border-slate-200">
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
	                        <input type="text" name="notes" maxlength="255" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Optional (e.g. thicker / other supplier)">
	                    </div>
	                    <div class="flex justify-end">
	                        <button type="submit" class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-semibold">Save</button>
	                    </div>
	                </form>
	            </div>
	        </div>
	    </div>
	</div>
	    </div>
	</x-app-layout>
