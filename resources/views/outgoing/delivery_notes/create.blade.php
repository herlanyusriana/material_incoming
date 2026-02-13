@extends('outgoing.layout')

@section('content')
    <div class="max-w-5xl mx-auto space-y-6" x-data="deliveryNoteForm()">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center gap-3 mb-6">
                <a href="{{ route('outgoing.delivery-notes.index') }}"
                    class="h-10 w-10 flex items-center justify-center rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-500">
                    ←
                </a>
                <h1 class="text-2xl font-black text-slate-900">Create New Delivery Note</h1>
            </div>

            <form action="{{ route('outgoing.delivery-notes.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">DN Number</label>
                        <input type="text" name="dn_no" required
                            value="{{ \App\Models\DeliveryNote::generateDeliveryNoteNo() }}"
                            class="w-full rounded-xl border-2 border-slate-200 px-4 py-2.5 focus:border-indigo-500 focus:ring-indigo-500 @error('dn_no') border-red-300 @enderror">
                        @error('dn_no') <p class="mt-1 text-xs text-red-600 font-semibold">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Customer</label>
                        <select name="customer_id" required
                            class="w-full rounded-xl border-2 border-slate-200 px-4 py-2.5 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select Customer</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" {{ ($prefilledData['customer_id'] ?? '') == $customer->id ? 'selected' : '' }}>{{ $customer->name }} ({{ $customer->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Delivery Date</label>
                        <input type="date" name="delivery_date" required value="{{ date('Y-m-d') }}"
                            class="w-full rounded-xl border-2 border-slate-200 px-4 py-2.5 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>



                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Notes</label>
                        <input type="text" name="notes" placeholder="Optional notes..."
                            class="w-full rounded-xl border-2 border-slate-200 px-4 py-2.5 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-4">
                            <h2 class="text-lg font-black text-slate-900">Delivery Items</h2>
                            <div class="h-4 w-px bg-slate-200"></div>
                            <div class="flex items-center gap-2">
                                <label class="text-[10px] font-black uppercase tracking-wider text-slate-400">Import from
                                    Picking:</label>
                                <select @change="importPicking($event.target.value)"
                                    class="text-xs rounded-lg border-slate-200 py-1">
                                    <option value="">- Select Completed Picking -</option>
                                    @foreach($completedPickings as $p)
                                        <option
                                            value="{{ json_encode(['id' => $p->gci_part_id, 'part_no' => $p->part?->part_no, 'name' => $p->part?->part_name, 'qty' => $p->qty_picked, 'po_item_id' => $p->outgoing_po_item_id]) }}">
                                            {{ $p->delivery_date->format('d/m') }} - {{ $p->part?->part_no }} (Qty:
                                            {{ $p->qty_picked }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button type="button" @click="addItem()"
                            class="px-4 py-2 bg-slate-800 text-white rounded-xl text-sm font-bold hover:bg-slate-900 transition-all active:scale-95">
                            ＋ Add Manual
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(item, index) in items" :key="index">
                            <div
                                class="flex flex-col md:flex-row gap-3 p-4 bg-slate-50 rounded-2xl border border-slate-200 group">
                                <div class="flex-1">
                                    <label
                                        class="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">Part
                                        FG</label>
                                    <select :name="`items[${index}][gci_part_id]`" x-model="item.gci_part_id" required
                                        class="w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select Part</option>
                                        @foreach ($gciParts as $part)
                                            <option value="{{ $part->id }}">{{ $part->part_no }} - {{ $part->part_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-full md:w-32">
                                    <label
                                        class="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">Quantity</label>
                                    <input type="number" :name="`items[${index}][qty]`" x-model="item.qty" required
                                        step="0.0001" min="0.0001"
                                        class="w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="0.00">
                                </div>
                                <div class="w-full md:w-64">
                                    <label
                                        class="block text-[10px] font-black uppercase tracking-wider text-slate-500 mb-1">Remarks</label>
                                    <input type="text" :name="`items[${index}][remarks]`" x-model="item.remarks"
                                        class="w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Optional notes...">
                                </div>
                                <input type="hidden" :name="`items[${index}][outgoing_po_item_id]`"
                                    x-model="item.outgoing_po_item_id">
                                <input type="hidden" :name="`items[${index}][sales_order_id]`"
                                    x-model="item.sales_order_id">
                                <div class="flex items-end pb-1">
                                    <button type="button" @click="removeItem(index)"
                                        class="h-9 w-9 flex items-center justify-center rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all">
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div x-show="items.length === 0"
                            class="py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl text-slate-400 font-semibold">
                            Drag or click "Add Item" to start adding delivery content
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-slate-100">
                    <a href="{{ route('outgoing.delivery-notes.index') }}"
                        class="px-6 py-2.5 rounded-xl border border-slate-200 font-bold text-slate-600 hover:bg-slate-50">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-8 py-2.5 rounded-xl bg-indigo-600 text-white font-black shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all active:scale-95 disabled:opacity-50"
                        :disabled="items.length === 0">
                        Save Delivery Note
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function deliveryNoteForm() {
            return {
                items: @json($prefilledData['items'] ?? []),
                addItem() {
                    this.items.push({ gci_part_id: '', qty: '', outgoing_po_item_id: '', remarks: '' });
                },
                removeItem(index) {
                    this.items.splice(index, 1);
                },
                importPicking(dataStr) {
                    if (!dataStr) return;
                    const data = JSON.parse(dataStr);

                    // Check if already exists
                    const exists = this.items.find(i => i.gci_part_id == data.id && i.outgoing_po_item_id == data.po_item_id);
                    if (exists) {
                        exists.qty = parseFloat(exists.qty) + parseFloat(data.qty);
                    } else {
                        this.items.push({
                            gci_part_id: data.id,
                            qty: data.qty,
                            outgoing_po_item_id: data.po_item_id,
                            remarks: ''
                        });
                    }
                }
            }
        }
    </script>
@endsection