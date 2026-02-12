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
                            <label class="text-xs font-semibold text-slate-600">Period (YYYY-MM or YYYY-WW)</label>
                            <input name="period" value="{{ $period ?? '' }}" class="mt-1 rounded-xl border-slate-200"
                                placeholder="(blank for all)">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Customer</label>
                            <select name="customer_id" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}" @selected((string) $customerId === (string) $c->id)>
                                        {{ $c->code }} — {{ $c->name }}
                                    </option>
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

                    <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold"
                        @click="openCreate()">
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
                                <th class="px-4 py-3 text-left font-semibold">Delivery Date</th>
                                <th class="px-4 py-3 text-right font-semibold">Qty</th>
                                <th class="px-4 py-3 text-right font-semibold">Price</th>
                                <th class="px-4 py-3 text-right font-semibold">Amount</th>
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
                                    <td class="px-4 py-3 font-mono text-xs">
                                        {{ $o->delivery_date?->format('d/m/Y') ?? $o->period }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">
                                        {{ number_format((float) $o->qty, 3) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">
                                        {{ number_format((float) $o->price, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs font-bold text-indigo-600">
                                        {{ number_format((float) $o->amount, 2) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $o->status === 'open' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                            {{ strtoupper($o->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" class="text-indigo-600 hover:text-indigo-800 font-semibold"
                                            @click="openEdit(@js($o))">Edit</button>
                                        <form action="{{ route('planning.customer-pos.destroy', $o) }}" method="POST"
                                            class="inline" onsubmit="return confirm('Delete PO?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ml-3 text-red-600 hover:text-red-800 font-semibold"
                                                type="submit">Delete</button>
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

        <div class="fixed inset-0 z-50 flex items-start justify-center bg-slate-900/40 backdrop-blur-sm px-4 py-8 overflow-y-auto"
            x-show="modalOpen" x-cloak @keydown.escape.window="close()">
            <div
                class="w-full max-w-3xl bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900"
                        x-text="mode === 'create' ? 'Add Customer PO' : 'Edit Customer PO'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50"
                        @click="close()">✕</button>
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
                                <select name="customer_id" class="mt-1 w-full rounded-xl border-slate-200" required
                                    x-model="form.customer_id">
                                    <option value="" disabled>Select customer</option>
                                    @foreach ($customers as $c)
                                        <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-semibold text-slate-700">PO No</label>
                                    <input name="po_no" class="mt-1 w-full rounded-xl border-slate-200"
                                        x-model="form.po_no" placeholder="e.g. PO-12345">
                                </div>
                                <div>
                                    <label class="text-sm font-semibold text-slate-700">PO Date</label>
                                    <input type="date" name="po_date" class="mt-1 w-full rounded-xl border-slate-200"
                                        x-model="form.po_date">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="text-sm font-semibold text-slate-700">Delivery Date</label>
                                <input type="date" name="delivery_date" class="mt-1 w-full rounded-xl border-slate-200"
                                    x-model="form.delivery_date">
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-bold text-slate-900">Items</div>
                                        <div class="text-xs text-slate-500">Klik “Add Part” untuk input lebih dari 1
                                            part dalam 1 PO.</div>
                                    </div>
                                    <button type="button"
                                        class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold"
                                        @click="addItem()">
                                        + Add Part
                                    </button>
                                </div>

                                <template x-for="(it, idx) in form.items" :key="it._key">
                                    <div class="bg-white border border-slate-200 rounded-xl p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="text-sm font-bold text-slate-700">Item <span
                                                    x-text="idx + 1"></span></div>
                                            <button type="button"
                                                class="text-sm font-semibold text-red-600 hover:text-red-700"
                                                @click="removeItem(idx)" x-show="form.items.length > 1">
                                                Remove
                                            </button>
                                        </div>

                                        <div class="mt-3 grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                                            <div class="md:col-span-3" x-data="{
                                                open: false,
                                                search: '',
                                                trigger: null,
                                                dropdown: null,
                                                get filteredParts() {
                                                    if (this.search === '') return parts;
                                                    return parts.filter(p => p.label.toLowerCase().includes(this.search.toLowerCase()));
                                                },
                                                get selectedLabel() {
                                                    let p = parts.find(p => p.id == it.part_id);
                                                    return p ? p.label : 'Select part';
                                                },
                                                init() {
                                                    this.trigger = this.$refs.trigger;
                                                },
                                                toggle() {
                                                    this.open = !this.open;
                                                    if (this.open) {
                                                        this.$nextTick(() => this.updatePosition());
                                                    }
                                                },
                                                updatePosition() {
                                                    if (!this.open) return;
                                                    const rect = this.trigger.getBoundingClientRect();
                                                    this.dropdown = this.$refs.dropdown;
                                                    
                                                    this.dropdown.style.top = (rect.bottom + window.scrollY + 5) + 'px';
                                                    this.dropdown.style.left = (rect.left + window.scrollX) + 'px';
                                                    this.dropdown.style.width = rect.width + 'px';
                                                },
                                                select(id) {
                                                    it.part_id = id;
                                                    this.open = false;
                                                    this.search = '';
                                                }
                                            }" @resize.window="updatePosition()" @scroll.window="updatePosition()">
                                                <label class="text-xs font-semibold text-slate-600">Part GCI</label>
                                                <input type="hidden" :name="`items[${idx}][part_id]`"
                                                    x-model="it.part_id">

                                                <div class="relative mt-1">
                                                    <button type="button" x-ref="trigger"
                                                        class="w-full text-left bg-white border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent flex items-center justify-between"
                                                        @click="toggle()">
                                                        <span class="block truncate" x-text="selectedLabel"></span>
                                                        <svg class="h-5 w-5 text-gray-400"
                                                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                            fill="currentColor">
                                                            <path fill-rule="evenodd"
                                                                d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                    </button>

                                                    <template x-teleport="body">
                                                        <div x-show="open" x-ref="dropdown"
                                                            @click.outside="open = false"
                                                            class="absolute z-[9999] bg-white shadow-xl max-h-60 rounded-xl py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm"
                                                            style="display: none;">

                                                            <div
                                                                class="sticky top-0 z-10 bg-white px-2 py-1.5 border-b border-slate-100">
                                                                <input type="text" x-model="search"
                                                                    class="w-full border-slate-200 rounded-lg text-xs placeholder-slate-400 focus:border-indigo-500 focus:ring-indigo-500"
                                                                    placeholder="Search part..." @click.stop>
                                                            </div>

                                                            <template x-for="p in filteredParts" :key="p.id">
                                                                <div class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-50 text-slate-900"
                                                                    @click="select(p.id)">
                                                                    <span class="block truncate" x-text="p.label"
                                                                        :class="{ 'font-semibold': it.part_id == p.id, 'font-normal': it.part_id != p.id }"></span>

                                                                    <span x-show="it.part_id == p.id"
                                                                        class="text-indigo-600 absolute inset-y-0 right-0 flex items-center pr-4">
                                                                        <svg class="h-5 w-5"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            viewBox="0 0 20 20" fill="currentColor">
                                                                            <path fill-rule="evenodd"
                                                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L8.586 13.586l7.293-7.293a1 1 0 011.414 0z"
                                                                                clip-rule="evenodd" />
                                                                        </svg>
                                                                    </span>
                                                                </div>
                                                            </template>

                                                            <div x-show="filteredParts.length === 0"
                                                                class="cursor-default select-none relative py-2 pl-3 pr-9 text-slate-500 italic">
                                                                No part found
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <div class="md:col-span-1">
                                                <label class="text-xs font-semibold text-slate-600">Qty</label>
                                                <input type="number" step="0.001" min="0"
                                                    class="mt-1 w-full rounded-xl border-slate-200" x-model="it.qty"
                                                    :name="`items[${idx}][qty]`" required>
                                            </div>

                                            <div class="md:col-span-2">
                                                <label class="text-xs font-semibold text-slate-600">Price</label>
                                                <input type="number" step="0.01" min="0"
                                                    class="mt-1 w-full rounded-xl border-slate-200" x-model="it.price"
                                                    :name="`items[${idx}][price]`">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="p-4 bg-indigo-50 border border-indigo-100 rounded-2xl">
                                <label class="text-sm font-bold text-indigo-900">Period (Month or Week)</label>
                                <div class="mt-1 flex items-center gap-3">
                                    <input name="period"
                                        class="w-full rounded-xl border-indigo-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm"
                                        required x-model="form.period" placeholder="e.g. 2026-01 or 2026-W01">
                                </div>
                                <p class="mt-2 text-[11px] text-indigo-700 italic flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Use format YYYY-MM for monthly or YYYY-Www for weekly.
                                </p>
                            </div>
                        </div>
                    </template>

                    <template x-if="mode === 'edit'">
                        <div class="text-xs text-slate-500">
                            Only qty/status/notes are editable.
                        </div>
                    </template>

                    <div class="grid grid-cols-2 gap-4">
                        <div x-show="mode === 'edit'">
                            <label class="text-sm font-semibold text-slate-700">Qty</label>
                            <input type="number" step="0.001" min="0" name="qty"
                                class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.qty">
                        </div>
                        <div x-show="mode === 'edit'">
                            <label class="text-sm font-semibold text-slate-700">Price</label>
                            <input type="number" step="0.01" min="0" name="price"
                                class="mt-1 w-full rounded-xl border-slate-200" x-model="form.price">
                        </div>
                    </div>
                    <div x-show="mode === 'edit'" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-slate-700">PO Date</label>
                            <input type="date" name="po_date" class="mt-1 w-full rounded-xl border-slate-200"
                                x-model="form.po_date">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-slate-700">Delivery Date</label>
                            <input type="date" name="delivery_date" class="mt-1 w-full rounded-xl border-slate-200"
                                x-model="form.delivery_date">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Status</label>
                        <select name="status" class="mt-1 w-full rounded-xl border-slate-200" required
                            x-model="form.status">
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Notes</label>
                        <textarea name="notes" class="mt-1 w-full rounded-xl border-slate-200" rows="3"
                            x-model="form.notes"></textarea>
                    </div>

                    <div class="sticky bottom-0 bg-white border-t border-slate-100 flex justify-end gap-2 pt-4 pb-1">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50"
                            @click="close()">Cancel</button>
                        <button type="submit"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save</button>
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
                        period: @js($period ?? $defaultPeriod ?? now()->format('Y-m')),
                        customer_id: '',
                        po_no: '',
                        po_date: '',
                        delivery_date: '',
                        part_id: '',
                        qty: '0',
                        price: '0',
                        status: 'open',
                        notes: '',
                        items: [],
                    },
                    _newItem() {
                        return {
                            _key: Date.now().toString() + Math.random().toString(16).slice(2),
                            part_id: '',
                            qty: '0',
                            price: '0',
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
                            period: @js($defaultPeriod ?? now()->format('Y-m')),
                            customer_id: '',
                            po_no: '',
                            po_date: '',
                            delivery_date: '',
                            part_id: '',
                            qty: '0',
                            price: '0',
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
                            price: o.price,
                            po_date: o.po_date ? o.po_date.substring(0, 10) : '',
                            delivery_date: o.delivery_date ? o.delivery_date.substring(0, 10) : '',
                            status: o.status,
                            notes: o.notes ?? '',
                        };
                        this.modalOpen = true;
                    },
                    close() { this.modalOpen = false; },
                    parts: @js($gciParts->map(fn($p) => ['id' => $p->id, 'label' => $p->part_no . ' — ' . ($p->part_name ?? '-')])->values()),
                }
            }
        </script>
    </div>
</x-app-layout>