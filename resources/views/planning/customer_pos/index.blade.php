<x-app-layout>
    <x-slot name="header">
        Planning • Customer PO
    </x-slot>

    <div class="py-6" x-data="planningCustomerPos()">
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
                            <label class="text-xs font-semibold text-slate-600">Minggu (YYYY-WW)</label>
                            <input type="week" name="minggu" value="{{ $minggu ?? '' }}" class="mt-1 rounded-xl border-slate-200" placeholder="(kosongkan untuk semua)">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Customer</label>
                            <select name="customer_id" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}" @selected((string) $customerId === (string) $c->id)>{{ $c->code }} — {{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Status</label>
                            <select name="status" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                <option value="open" @selected($status === 'open')>Open</option>
                                <option value="closed" @selected($status === 'closed')>Closed</option>
                            </select>
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                    </form>

                    <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold" @click="openCreate()">
                        Add PO
                    </button>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Customer</th>
                                <th class="px-4 py-3 text-left font-semibold">PO No</th>
                                <th class="px-4 py-3 text-left font-semibold">Part GCI</th>
                                <th class="px-4 py-3 text-left font-semibold">Minggu</th>
                                <th class="px-4 py-3 text-right font-semibold">Qty</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($orders as $o)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold">{{ $o->customer->code ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $o->customer->name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $o->po_no ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold">{{ $o->part->part_no ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $o->part->part_name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $o->minggu }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format((float) $o->qty, 3) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $o->status === 'open' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                            {{ strtoupper($o->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" class="text-indigo-600 hover:text-indigo-800 font-semibold" @click="openEdit(@js($o))">Edit</button>
                                        <form action="{{ route('planning.customer-pos.destroy', $o) }}" method="POST" class="inline" onsubmit="return confirm('Delete PO?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ml-3 text-red-600 hover:text-red-800 font-semibold" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-slate-500">No customer PO</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>

        <div class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/40 backdrop-blur-sm px-4 py-8 overflow-y-auto" x-show="modalOpen" x-cloak @keydown.escape.window="close()">
            <div class="w-full max-w-3xl bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900" x-text="mode === 'create' ? 'Add Customer PO' : 'Edit Customer PO'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">✕</button>
                </div>

                <form :action="formAction" method="POST" class="px-5 py-4 space-y-4 overflow-y-auto flex-1">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

	                    <template x-if="mode === 'create'">
	                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-semibold text-slate-700">Customer</label>
                                <select name="customer_id" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.customer_id">
                                    <option value="" disabled>Select customer</option>
                                    @foreach ($customers as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-slate-700">PO No</label>
                                <input name="po_no" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.po_no" placeholder="e.g. PO-12345">
                            </div>
	                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-3">
	                                <div class="flex items-center justify-between gap-3">
	                                    <div>
	                                        <div class="text-sm font-bold text-slate-900">Items</div>
	                                        <div class="text-xs text-slate-500">Klik “Add Part” untuk input lebih dari 1 part dalam 1 PO.</div>
	                                    </div>
	                                    <button
	                                        type="button"
	                                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold"
	                                        @click="addItem()"
	                                    >
	                                        + Add Part
	                                    </button>
	                                </div>

	                                <template x-for="(it, idx) in form.items" :key="it._key">
	                                    <div class="bg-white border border-slate-200 rounded-xl p-4">
	                                        <div class="flex items-start justify-between gap-3">
	                                            <div class="text-sm font-bold text-slate-700">Item <span x-text="idx + 1"></span></div>
	                                            <button
	                                                type="button"
	                                                class="text-sm font-semibold text-red-600 hover:text-red-700"
	                                                @click="removeItem(idx)"
	                                                x-show="form.items.length > 1"
	                                            >
	                                                Remove
	                                            </button>
	                                        </div>

	                                        <div class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
	                                            <div class="md:col-span-3">
	                                                <label class="text-xs font-semibold text-slate-600">Part GCI</label>
	                                                <select
	                                                    class="mt-1 w-full rounded-xl border-slate-200"
	                                                    x-model="it.part_id"
	                                                    :name="`items[${idx}][part_id]`"
	                                                    required
	                                                >
	                                                    <option value="">Select part</option>
	                                                    @foreach ($gciParts as $p)
	                                                        <option value="{{ $p->id }}">{{ $p->part_no }} — {{ $p->part_name ?? '-' }}</option>
	                                                    @endforeach
	                                                </select>
	                                            </div>

	                                            <div class="md:col-span-1">
	                                                <label class="text-xs font-semibold text-slate-600">Qty</label>
	                                                <input
	                                                    type="number"
	                                                    step="0.001"
	                                                    min="0"
	                                                    class="mt-1 w-full rounded-xl border-slate-200"
	                                                    x-model="it.qty"
	                                                    :name="`items[${idx}][qty]`"
	                                                    required
	                                                >
	                                            </div>
	                                        </div>
	                                    </div>
	                                </template>
	                            </div>

                                <div class="p-4 bg-indigo-50 border border-indigo-100 rounded-2xl">
                                    <label class="text-sm font-bold text-indigo-900">Delivery Week (Minggu)</label>
                                    <div class="mt-1 flex items-center gap-3">
                                        <input type="week" name="minggu" class="w-full rounded-xl border-indigo-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm" required x-model="form.minggu">
                                        <div class="text-[10px] text-indigo-500 font-medium whitespace-nowrap">
                                            Format: YYYY-Www
                                        </div>
                                    </div>
                                    <p class="mt-2 text-[11px] text-indigo-700 italic flex items-center gap-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                        Gunakan icon calendar untuk memilih minggu dengan lebih mudah.
                                    </p>
                                </div>
	                        </div>
	                    </template>

                    <template x-if="mode === 'edit'">
                        <div class="text-xs text-slate-500">
                            {{ $minggu }} • Only qty/status/notes are editable.
                        </div>
                    </template>

	                    <div>
	                        <label class="text-sm font-semibold text-slate-700">Qty</label>
	                        <input type="number" step="0.001" min="0" name="qty" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.qty" x-show="mode === 'edit'">
	                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Status</label>
                        <select name="status" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.status">
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Notes</label>
                        <textarea name="notes" class="mt-1 w-full rounded-xl border-slate-200" rows="3" x-model="form.notes"></textarea>
                    </div>

                    <div class="sticky bottom-0 bg-white border-t border-slate-100 flex justify-end gap-2 pt-4 pb-1">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function planningCustomerPos() {
	                return {
                    modalOpen: false,
                    mode: 'create',
                    formAction: @js(route('planning.customer-pos.store')),
                    form: {
                        id: null,
                        minggu: @js($minggu ?? $defaultMinggu ?? now()->format('o-\\WW')),
                        customer_id: '',
                        po_no: '',
                        part_id: '',
	                        qty: '0',
	                        status: 'open',
	                        notes: '',
	                        items: [],
	                    },
	                    _newItem() {
	                        return {
	                            _key: Date.now().toString() + Math.random().toString(16).slice(2),
	                            part_id: '',
	                            qty: '0',
	                        };
	                    },
	                        addItem() {
	                            this.form.items.push(this._newItem());
	                        },
	                    removeItem(idx) {
	                        this.form.items.splice(idx, 1);
	                        if (this.form.items.length < 1) this.addItem();
	                    },
                    openCreate() {
                        this.mode = 'create';
                        this.formAction = @js(route('planning.customer-pos.store'));
                        this.form = {
                            id: null,
                            minggu: @js($defaultMinggu ?? now()->format('o-\\WW')),
                            customer_id: '',
                            po_no: '',
                            part_id: '',
	                            qty: '0',
	                            status: 'open',
	                            notes: '',
	                            items: [this._newItem()],
	                        };
	                        this.modalOpen = true;
	                    },
                    openEdit(o) {
                        this.mode = 'edit';
                        this.formAction = @js(url('/planning/customer-pos')) + '/' + o.id;
                        this.form = {
                            id: o.id,
                            qty: o.qty,
                            status: o.status,
                            notes: o.notes ?? '',
                        };
                        this.modalOpen = true;
                    },
                    close() { this.modalOpen = false; },
                }
            }
        </script>
    </div>
</x-app-layout>
