<x-app-layout>
    <x-slot name="header">
        Outgoing • Edit Sales Order (PO Outgoing)
    </x-slot>

    <div class="py-6" x-data="soForm()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-xl border border-slate-200 rounded-3xl overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Edit Sales Order:
                        {{ $salesOrder->so_no }}</h2>
                    <p class="text-sm text-slate-500 mt-1">Update purchase order from customer.</p>
                </div>

                <form action="{{ route('outgoing.sales-orders.update', $salesOrder) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">SO
                                    Number</label>
                                <input type="text" name="so_no" value="{{ old('so_no', $salesOrder->so_no) }}" required
                                    class="w-full rounded-xl border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold"
                                    placeholder="E.g. SO/2026/001">
                                @error('so_no') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1">
                                <label
                                    class="text-xs font-bold text-slate-500 uppercase tracking-widest">Customer</label>
                                <select name="customer_id" required
                                    class="w-full rounded-xl border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                    <option value="">Select Customer...</option>
                                    @foreach($customers as $c)
                                        <option value="{{ $c->id }}" @selected(old('customer_id', $salesOrder->customer_id) == $c->id)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                @error('customer_id') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">SO
                                    Date</label>
                                <input type="date" name="so_date"
                                    value="{{ old('so_date', $salesOrder->so_date ? $salesOrder->so_date->toDateString() : '') }}"
                                    required
                                    class="w-full rounded-xl border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                @error('so_date') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Item Details</h3>
                                <button type="button" @click="addItem()"
                                    class="px-4 py-2 rounded-xl bg-indigo-50 text-indigo-700 font-bold hover:bg-indigo-100 transition-all text-xs border border-indigo-200">
                                    + ADD ITEM
                                </button>
                            </div>

                            <div class="overflow-x-auto border border-slate-200 rounded-2xl">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">
                                                Part</th>
                                            <th
                                                class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-widest w-32">
                                                Qty Ordered</th>
                                            <th
                                                class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-widest w-20">
                                                Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-100">
                                        <template x-for="(item, index) in items" :key="index">
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="px-4 py-3">
                                                    <select :name="`items[${index}][part_id]`" required
                                                        class="w-full rounded-xl border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                        x-model="item.part_id">
                                                        <option value="">Select FG Part...</option>
                                                        @foreach($parts as $p)
                                                            <option value="{{ $p->id }}">{{ $p->part_no }} -
                                                                {{ $p->part_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="number" :name="`items[${index}][qty]`"
                                                        x-model="item.qty" step="0.0001" required
                                                        class="w-full rounded-xl border-slate-200 text-sm text-right font-bold focus:ring-indigo-500 focus:border-indigo-500">
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <button type="button" @click="removeItem(index)"
                                                        class="p-2 text-rose-500 hover:bg-rose-50 rounded-xl transition-all"
                                                        x-show="items.length > 1">
                                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-8 space-y-1">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Notes</label>
                            <textarea name="notes" rows="3"
                                class="w-full rounded-2xl border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                placeholder="Additional information...">{{ old('notes', $salesOrder->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="p-6 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-3">
                        <a href="{{ route('outgoing.sales-orders.index') }}"
                            class="px-6 py-2.5 rounded-2xl bg-white border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all uppercase text-xs tracking-wider">Cancel</a>
                        <button type="submit"
                            class="px-8 py-2.5 rounded-2xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition-all shadow-lg uppercase text-xs tracking-wider">Update
                            Sales Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function soForm() {
            return {
                items: @json($salesOrder->items->map(fn($i) => ['part_id' => $i->gci_part_id, 'qty' => (float) $i->qty_ordered])),
                addItem() {
                    this.items.push({ part_id: '', qty: 0 });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                }
            }
        }
    </script>
</x-app-layout>