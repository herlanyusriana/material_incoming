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
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Part GCI</label>
                            <select name="gci_part_id" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                @foreach ($gciParts as $p)
                                    <option value="{{ $p->id }}" @selected((string) $gciPartId === (string) $p->id)>{{ $p->part_no }} — {{ $p->part_name ?? '-' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                    </form>

                    <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold" @click="openCreate()">
                        Add BOM
                    </button>
                </div>

                <div class="space-y-4">
                    @forelse ($boms as $bom)
                        <div class="border border-slate-200 rounded-2xl p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="text-lg font-semibold text-slate-900">{{ $bom->part->part_no ?? '-' }}</div>
                                    <div class="text-sm text-slate-600">{{ $bom->part->part_name ?? '-' }}</div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $bom->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                        {{ strtoupper($bom->status) }}
                                    </span>
                                    <form action="{{ route('planning.boms.update', $bom) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="{{ $bom->status === 'active' ? 'inactive' : 'active' }}">
                                        <button type="submit" class="text-indigo-600 hover:text-indigo-800 font-semibold">Toggle</button>
                                    </form>
                                    <form action="{{ route('planning.boms.destroy', $bom) }}" method="POST" class="inline" onsubmit="return confirm('Delete BOM?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:text-red-800 font-semibold" type="submit">Delete</button>
                                    </form>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Components (Raw Material)</div>
                                <div class="mt-2 overflow-x-auto border border-slate-200 rounded-xl">
                                    <table class="min-w-full text-sm divide-y divide-slate-200">
                                        <thead class="bg-slate-50">
                                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                                <th class="px-3 py-2 text-left font-semibold">Part No</th>
                                                <th class="px-3 py-2 text-left font-semibold">Part Name</th>
                                                <th class="px-3 py-2 text-right font-semibold">Consumption</th>
                                                <th class="px-3 py-2 text-right font-semibold">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @forelse ($bom->items as $item)
                                                <tr>
                                                    <td class="px-3 py-2 font-semibold">{{ $item->componentPart->part_no ?? '-' }}</td>
                                                    <td class="px-3 py-2 text-slate-600">{{ $item->componentPart->part_name_gci ?? '-' }}</td>
                                                    <td class="px-3 py-2 text-right font-mono text-xs">{{ number_format((float) $item->usage_qty, 3) }}</td>
                                                    <td class="px-3 py-2 text-right">
                                                        <form action="{{ route('planning.boms.items.destroy', $item) }}" method="POST" onsubmit="return confirm('Remove component?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="text-red-600 hover:text-red-800 font-semibold" type="submit">Remove</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-3 py-4 text-center text-slate-500">No components</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <form action="{{ route('planning.boms.items.store', $bom) }}" method="POST" class="mt-3 flex flex-wrap items-end gap-2">
                                    @csrf
                                    <div class="min-w-[280px]">
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
                                    <button class="px-3 py-2 rounded-xl bg-slate-900 text-white font-semibold">Add / Update</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-slate-500">No BOM</div>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $boms->links() }}
                </div>
            </div>
        </div>

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

        <script>
            function planningBoms() {
                return {
                    modalOpen: false,
                    openCreate() { this.modalOpen = true; },
                    close() { this.modalOpen = false; },
                }
            }
        </script>
    </div>
</x-app-layout>
