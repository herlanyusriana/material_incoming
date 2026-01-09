<x-app-layout>
    <x-slot name="header">
        Planning • BOM GCI
    </x-slot>

    <div class="py-6" x-data="planningBoms()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
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

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
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
                        <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold" @click="openCreate()">
                            + Add BOM
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Part ID</th>
                                <th class="px-4 py-3 text-left font-semibold">Part Name</th>
                                <th class="px-4 py-3 text-right font-semibold whitespace-nowrap">Quantity</th>
                                <th class="px-4 py-3 text-right font-semibold whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($boms as $bom)
                                @php
                                    $bomId = (int) $bom->id;
                                @endphp

                                {{-- Parent row --}}
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2"
                                            @click="toggle({{ $bomId }})"
                                            aria-label="Toggle BOM"
                                        >
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-md border border-slate-200 bg-white text-slate-700">
                                                <template x-if="expanded[{{ $bomId }}]">
                                                    <span>▾</span>
                                                </template>
                                                <template x-if="!expanded[{{ $bomId }}]">
                                                    <span>▸</span>
                                                </template>
                                            </span>
                                            <span class="font-mono text-xs font-semibold">{{ $bom->part->part_no ?? '-' }}</span>
                                        </button>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900">{{ $bom->part->part_name ?? '-' }}</div>
                                        <div class="text-xs mt-0.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full font-semibold {{ $bom->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                                {{ strtoupper($bom->status) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-500">—</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
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

                                {{-- Child rows --}}
                                @forelse($bom->items as $item)
                                    <tr class="bg-slate-50/50" x-show="expanded[{{ $bomId }}]" x-cloak>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <div class="pl-10 font-mono text-xs">
                                                {{ $item->componentPart->part_no ?? '-' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="pl-2 text-slate-700">
                                                {{ $item->componentPart->part_name_gci ?? '-' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap text-slate-700">
                                            Qty: {{ rtrim(rtrim(number_format((float) $item->usage_qty, 4, '.', ''), '0'), '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50"
                                                title="Edit quantity"
                                                @click="openEditItem(@js([
                                                    'bomPartNo' => $bom->part->part_no ?? '-',
                                                    'bomPartName' => $bom->part->part_name ?? '-',
                                                    'componentId' => $item->component_part_id,
                                                    'componentLabel' => ($item->componentPart->part_no ?? '-') . ' — ' . ($item->componentPart->part_name_gci ?? '-'),
                                                    'usageQty' => (string) $item->usage_qty,
                                                    'storeUrl' => route('planning.boms.items.store', $bom),
                                                ]))"
                                            >
                                                ✎
                                            </button>
                                            <form action="{{ route('planning.boms.items.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Delete BOM item?')">
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
                                        <td colspan="4" class="px-4 py-4 text-center text-slate-500">No components</td>
                                    </tr>
                                @endforelse

                                {{-- Add component row --}}
                                <tr class="bg-slate-50" x-show="expanded[{{ $bomId }}]" x-cloak>
                                    <td colspan="4" class="px-4 py-3">
                                        <form action="{{ route('planning.boms.items.store', $bom) }}" method="POST" class="flex flex-wrap items-end gap-2">
                                            @csrf
                                            <div class="min-w-[320px]">
                                                <label class="text-xs font-semibold text-slate-600">Component (Incoming Part)</label>
                                                <select name="component_part_id" class="mt-1 w-full rounded-xl border-slate-200" required>
                                                    <option value="" disabled selected>Select component</option>
                                                    @foreach ($components as $c)
                                                        <option value="{{ $c->id }}">{{ $c->part_no }} — {{ $c->part_name_gci }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-slate-600">Consumption</label>
                                                <input type="number" step="any" min="0" name="usage_qty" class="mt-1 rounded-xl border-slate-200" required>
                                            </div>
                                            <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Add / Update</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-slate-500">No BOM</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    <span class="font-semibold">Tip:</span> Klik icon panah untuk expand/collapse BOM. Icon ✎ untuk edit qty komponen / toggle status BOM.
                </div>

                <div class="mt-2">
                    {{ $boms->links() }}
                </div>
            </div>
        </div>

        {{-- Create BOM modal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="modalOpen" x-cloak @keydown.escape.window="close()">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900">Add BOM</div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">✕</button>
                </div>

                <form action="{{ route('planning.boms.store') }}" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Part GCI</label>
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
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Create</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Edit BOM item modal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="editItemOpen" x-cloak @keydown.escape.window="closeEditItem()">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900">Edit Component Qty</div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="closeEditItem()">✕</button>
                </div>

                <form :action="editForm.action" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <input type="hidden" name="component_part_id" :value="editForm.component_id">

                    <div class="text-sm text-slate-700">
                        <div class="font-semibold" x-text="editForm.parentLabel"></div>
                        <div class="text-xs text-slate-500 mt-1" x-text="editForm.componentLabel"></div>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Consumption</label>
                        <input type="number" step="any" min="0" name="usage_qty" class="mt-1 w-full rounded-xl border-slate-200" required x-model="editForm.usage_qty">
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50" @click="closeEditItem()">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function planningBoms() {
                return {
                    modalOpen: false,
                    editItemOpen: false,
                    expanded: {},
                    editForm: {
                        action: '',
                        component_id: '',
                        usage_qty: '',
                        parentLabel: '',
                        componentLabel: '',
                    },
                    openCreate() { this.modalOpen = true; },
                    close() { this.modalOpen = false; },
                    toggle(id) { this.expanded[id] = !this.expanded[id]; },
                    openEditItem(payload) {
                        this.editForm.action = payload.storeUrl;
                        this.editForm.component_id = payload.componentId;
                        this.editForm.usage_qty = payload.usageQty ?? '0';
                        this.editForm.parentLabel = `${payload.bomPartNo} — ${payload.bomPartName}`;
                        this.editForm.componentLabel = payload.componentLabel;
                        this.editItemOpen = true;
                    },
                    closeEditItem() { this.editItemOpen = false; },
                }
            }
        </script>
    </div>
</x-app-layout>

