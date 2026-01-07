<x-app-layout>
    <x-slot name="header">
        Inventory
    </x-slot>

    <div class="py-6" x-data="inventoryPage()">
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

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Part GCI</label>
                            <select name="part_id" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                @foreach ($parts as $p)
                                    <option value="{{ $p->id }}" @selected((string) $partId === (string) $p->id)>{{ $p->part_no }} — {{ $p->part_name_gci }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                    </form>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('inventory.export') }}" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Export</a>
                        <form action="{{ route('inventory.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                            @csrf
                            <input type="file" name="file" class="rounded-xl border-slate-200 text-sm" required>
                            <button class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-semibold">Import</button>
                        </form>
                        <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold" @click="openCreate()">
                            Add Inventory
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Part GCI</th>
                                <th class="px-4 py-3 text-right font-semibold">On Hand</th>
                                <th class="px-4 py-3 text-right font-semibold">On Order</th>
                                <th class="px-4 py-3 text-left font-semibold">As Of</th>
                                <th class="px-4 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($inventories as $inv)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold">{{ $inv->part->part_no ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $inv->part->part_name_gci ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format((float) $inv->on_hand, 3) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format((float) $inv->on_order, 3) }}</td>
                                    <td class="px-4 py-3 text-slate-600 text-xs">{{ $inv->as_of_date?->format('Y-m-d') ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" class="text-indigo-600 hover:text-indigo-800 font-semibold" @click="openEdit(@js($inv))">Edit</button>
                                        <form action="{{ route('inventory.destroy', $inv) }}" method="POST" class="inline" onsubmit="return confirm('Delete inventory row?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ml-3 text-red-600 hover:text-red-800 font-semibold" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">No inventory rows</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $inventories->links() }}
                </div>
            </div>
        </div>

        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="modalOpen" x-cloak @keydown.escape.window="close()">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900" x-text="mode === 'create' ? 'Add Inventory' : 'Edit Inventory'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">✕</button>
                </div>

                <form :action="formAction" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <template x-if="mode === 'create'">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Part GCI</label>
                            <select name="part_id" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.part_id">
                                <option value="" disabled>Select part</option>
                                @foreach ($parts as $p)
                                    <option value="{{ $p->id }}">{{ $p->part_no }} — {{ $p->part_name_gci }}</option>
                                @endforeach
                            </select>
                        </div>
                    </template>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">On Hand</label>
                        <input type="number" step="0.001" min="0" name="on_hand" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.on_hand">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">On Order</label>
                        <input type="number" step="0.001" min="0" name="on_order" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.on_order">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">As Of Date</label>
                        <input type="date" name="as_of_date" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.as_of_date">
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function inventoryPage() {
                return {
                    modalOpen: false,
                    mode: 'create',
                    formAction: @js(route('inventory.store')),
                    form: { id: null, part_id: '', on_hand: '0', on_order: '0', as_of_date: '' },
                    openCreate() {
                        this.mode = 'create';
                        this.formAction = @js(route('inventory.store'));
                        this.form = { id: null, part_id: '', on_hand: '0', on_order: '0', as_of_date: '' };
                        this.modalOpen = true;
                    },
                    openEdit(inv) {
                        this.mode = 'edit';
                        this.formAction = @js(url('/inventory')) + '/' + inv.id;
                        this.form = {
                            id: inv.id,
                            on_hand: inv.on_hand,
                            on_order: inv.on_order,
                            as_of_date: inv.as_of_date,
                        };
                        this.modalOpen = true;
                    },
                    close() { this.modalOpen = false; },
                }
            }
        </script>
    </div>
</x-app-layout>
