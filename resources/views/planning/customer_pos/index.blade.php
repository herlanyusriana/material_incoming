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
                            <input name="minggu" value="{{ $minggu }}" class="mt-1 rounded-xl border-slate-200" placeholder="2026-W01">
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
                                <th class="px-4 py-3 text-left font-semibold">Customer Part</th>
                                <th class="px-4 py-3 text-left font-semibold">Auto Part GCI</th>
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
                                    <td class="px-4 py-3">{{ $o->customer_part_no ?? '-' }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-700">
                                        @if ($o->part_id)
                                            <div class="flex items-baseline justify-between gap-3">
                                                <div class="truncate">
                                                    <span class="font-semibold">{{ $o->part->part_no ?? '-' }}</span>
                                                    <span class="text-slate-500">{{ $o->part->part_name ?? '' }}</span>
                                                </div>
                                                <div class="font-mono text-[11px] text-slate-600">{{ number_format((float) $o->qty, 3) }}</div>
                                            </div>
                                        @else
                                            @php($translated = $translatedByPoId[$o->id] ?? [])
                                            @if (!empty($translated))
                                                <div class="space-y-1">
                                                    @foreach ($translated as $t)
                                                        <div class="flex items-baseline justify-between gap-3">
                                                            <div class="truncate">
                                                                <span class="font-semibold">{{ $t['part_no'] }}</span>
                                                                <span class="text-slate-500">{{ $t['part_name'] ?? '' }}</span>
                                                            </div>
                                                            <div class="font-mono text-[11px] text-slate-600">
                                                                {{ number_format((float) $t['demand_qty'], 3) }}
                                                                <span class="text-slate-400">(×{{ number_format((float) $t['usage_qty'], 3) }})</span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        @endif
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

        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="modalOpen" x-cloak @keydown.escape.window="close()">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900" x-text="mode === 'create' ? 'Add Customer PO' : 'Edit Customer PO'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">✕</button>
                </div>

                <form :action="formAction" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

	                    <template x-if="mode === 'create'">
	                        <div class="space-y-4">
	                            <div>
	                                <label class="text-sm font-semibold text-slate-700">PO Type</label>
	                                <select
	                                    name="po_type"
	                                    class="mt-1 w-full rounded-xl border-slate-200"
	                                    x-model="form.po_type"
	                                    @change="
	                                        if (form.po_type === 'gci_part') { form.customer_part_no = ''; }
	                                        if (form.po_type === 'customer_part') { form.part_id = ''; }
	                                    "
	                                >
	                                    <option value="customer_part">Customer Part (recommended)</option>
	                                    <option value="gci_part">Part GCI</option>
	                                </select>
	                                <div class="mt-1 text-xs text-slate-500">Default input is Customer Part No + Qty; system will translate to Part GCI using mapping.</div>
	                            </div>
                            <div>
                                <label class="text-sm font-semibold text-slate-700">Minggu (YYYY-WW)</label>
                                <input name="minggu" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.minggu">
                            </div>
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
	                                <input name="po_no" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.po_no">
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

	                                        <div class="mt-3 grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
	                                            <div class="md:col-span-1">
	                                                <label class="text-xs font-semibold text-slate-600">Type</label>
	                                                <select
	                                                    class="mt-1 w-full rounded-xl border-slate-200"
	                                                    x-model="it.po_type"
	                                                    @change="if (it.po_type === 'gci_part') { it.customer_part_no=''; } else { it.part_id=''; }"
	                                                >
	                                                    <option value="customer_part">Customer Part</option>
	                                                    <option value="gci_part">Part GCI</option>
	                                                </select>
	                                                <input type="hidden" :name="`items[${idx}][po_type]`" :value="it.po_type">
	                                            </div>

	                                            <div class="md:col-span-2" x-show="it.po_type === 'customer_part'">
	                                                <label class="text-xs font-semibold text-slate-600">Customer Part No</label>
	                                                <input
	                                                    class="mt-1 w-full rounded-xl border-slate-200"
	                                                    x-model="it.customer_part_no"
	                                                    :name="`items[${idx}][customer_part_no]`"
	                                                    :required="it.po_type === 'customer_part'"
	                                                >
	                                            </div>

	                                            <div class="md:col-span-2" x-show="it.po_type === 'gci_part'">
	                                                <label class="text-xs font-semibold text-slate-600">Part GCI</label>
	                                                <select
	                                                    class="mt-1 w-full rounded-xl border-slate-200"
	                                                    x-model="it.part_id"
	                                                    :name="`items[${idx}][part_id]`"
	                                                    :required="it.po_type === 'gci_part'"
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

                    <div class="flex justify-end gap-2 pt-2">
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
                        minggu: @js($minggu),
                        customer_id: '',
                        po_no: '',
                        po_type: 'customer_part',
                        customer_part_no: '',
                        part_id: '',
	                        qty: '0',
	                        status: 'open',
	                        notes: '',
	                        items: [],
	                    },
	                    _newItem(poType = 'customer_part') {
	                        return {
	                            _key: Date.now().toString() + Math.random().toString(16).slice(2),
	                            po_type: poType,
	                            customer_part_no: '',
	                            part_id: '',
	                            qty: '0',
	                        };
	                    },
	                    addItem() {
	                        this.form.items.push(this._newItem(this.form.po_type || 'customer_part'));
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
                            minggu: @js($minggu),
                            customer_id: '',
                            po_no: '',
                            po_type: 'customer_part',
                            customer_part_no: '',
	                            part_id: '',
	                            qty: '0',
	                            status: 'open',
	                            notes: '',
	                            items: [this._newItem('customer_part')],
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
