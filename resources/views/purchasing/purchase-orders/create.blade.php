<x-app-layout>
    <x-slot name="header">
        Purchasing • Create PO
    </x-slot>

    <div class="py-6" x-data="createPo()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-xl border border-slate-200 rounded-3xl overflow-hidden">
                <div class="p-8 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">Generate Official Purchase Order</h2>
                    @if ($pr)
                        <p class="text-sm text-slate-500 mt-1">Converting approved request <span class="font-bold text-indigo-600">{{ $pr->pr_number }}</span> to PO.</p>
                    @else
                        <p class="text-sm text-slate-500 mt-1">Manually creating an official purchase order.</p>
                    @endif
                </div>

                <form action="{{ route('purchasing.purchase-orders.store') }}" method="POST">
                    @csrf
                    @if ($pr)
                        <input type="hidden" name="pr_id" value="{{ $pr->id }}">
                    @endif

                    <div class="p-8 space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Select Vendor</label>
                                <select name="vendor_id" x-model="selectedVendorId" @change="onVendorChange()" class="w-full rounded-2xl border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm font-semibold" required>
                                    <option value="">— Choose Vendor —</option>
                                    @foreach ($vendors as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->vendor_code }} - {{ $vendor->vendor_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-4">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-1">General Notes</label>
                                <input type="text" name="notes" class="w-full rounded-2xl border-slate-200 text-sm" placeholder="PO reference, shipping terms, etc.">
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                                <span class="h-px w-8 bg-slate-200"></span>
                                Order Line Items
                            </h3>
                            <div class="overflow-hidden border border-slate-200 rounded-2xl">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50 text-xs text-slate-500 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="px-6 py-4 text-left">Part Description</th>
                                            <th class="px-6 py-4 text-right">Order Qty</th>
                                            <th class="px-6 py-4 text-right">Unit Price</th>
                                            <th class="px-6 py-4 text-right">Line Subtotal</th>
                                            @if (!$pr)
                                                <th class="px-6 py-4 text-center">Actions</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-100">
                                        @if ($pr)
                                            @foreach ($pr->items as $index => $item)
                                                <tr class="hover:bg-slate-50/50 transition-colors">
                                                    <td class="px-6 py-4">
                                                        <div class="text-sm font-bold text-slate-900 font-mono">{{ $item->part?->part_no }}</div>
                                                        <div class="text-[10px] text-slate-500">{{ $item->part?->part_name }}</div>
                                                        <input type="hidden" name="items[{{ $index }}][part_id]" value="{{ $item->part_id }}">
                                                        <input type="hidden" name="items[{{ $index }}][pr_item_id]" value="{{ $item->id }}">
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <input type="number" name="items[{{ $index }}][qty]" x-model="line_items[{{ $index }}].qty" step="0.0001" class="w-32 rounded-xl border-slate-200 text-sm font-bold text-right" required>
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <input type="number" name="items[{{ $index }}][unit_price]" x-model="line_items[{{ $index }}].price" step="0.01" class="w-32 rounded-xl border-slate-200 text-sm font-bold text-right" required>
                                                    </td>
                                                    <td class="px-6 py-4 text-right text-sm font-bold text-slate-900 font-mono">
                                                        <span x-text="formatCurrency(line_items[{{ $index }}].qty * line_items[{{ $index }}].price)"></span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <template x-for="(item, index) in line_items" :key="index">
                                                <tr class="hover:bg-slate-50/50 transition-colors">
                                                    <td class="px-6 py-4">
                                                        <select :name="`items[${index}][part_id]`" class="w-full rounded-xl border-slate-200 text-sm" required>
                                                            <option value="">Select Part...</option>
                                                            @foreach (\App\Models\GciPart::all() as $part)
                                                                <option value="{{ $part->id }}">{{ $part->part_no }} - {{ $part->part_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <input type="number" :name="`items[${index}][qty]`" x-model="item.qty" step="0.0001" class="w-24 rounded-xl border-slate-200 text-sm text-right" required>
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <input type="number" :name="`items[${index}][unit_price]`" x-model="item.price" step="0.01" class="w-24 rounded-xl border-slate-200 text-sm text-right" required>
                                                    </td>
                                                    <td class="px-6 py-4 text-right text-sm font-bold text-slate-900 font-mono">
                                                        <span x-text="formatCurrency(item.qty * item.price)"></span>
                                                    </td>
                                                    <td class="px-6 py-4 text-center">
                                                        <button type="button" @click="removeItem(index)" class="text-rose-500 hover:bg-rose-50 p-2 rounded-xl">
                                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        @endif
                                    </tbody>
                                    <tfoot class="bg-slate-50/30">
                                        <tr>
                                            <td colspan="3" class="px-6 py-5 text-right text-xs font-black text-slate-400 uppercase tracking-widest">Grand Total Amount</td>
                                            <td class="px-6 py-5 text-right text-xl font-black text-indigo-600 font-mono">
                                                <span x-text="formatCurrency(calculateTotal())"></span>
                                            </td>
                                            @if (!$pr) <td></td> @endif
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            @if (!$pr)
                                <button type="button" @click="addItem()" class="mt-4 px-4 py-2 rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-700 font-bold hover:bg-indigo-100 transition-all flex items-center gap-2 text-xs uppercase tracking-widest shadow-sm">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Manual Item
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="p-8 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-3">
                        <a href="{{ route('purchasing.purchase-orders.index') }}" class="px-6 py-3 rounded-2xl bg-white border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all uppercase text-xs tracking-wider">Discard</a>
                        <button type="submit" class="px-10 py-3 rounded-2xl bg-slate-900 text-white font-bold hover:bg-slate-800 transition-all shadow-xl uppercase text-xs tracking-wider">Generate Official PO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function createPo() {
            const itemPrices = @js($itemPrices ?? []);
            const partIds = @js($pr ? $pr->items->pluck('part_id')->toArray() : []);
            const suggestedVendorId = @js($suggestedVendorId ? (string) $suggestedVendorId : '');

            function getPriceForPart(partId, vendorId) {
                if (itemPrices[partId] && itemPrices[partId][vendorId]) {
                    return itemPrices[partId][vendorId];
                }
                return 0;
            }

            return {
                selectedVendorId: suggestedVendorId,
                line_items: @js($pr ? $pr->items->map(fn($i) => ['qty' => $i->qty, 'price' => 0, 'part_id' => $i->part_id]) : [['qty' => 1, 'price' => 0, 'part_id' => null]]),

                init() {
                    if (this.selectedVendorId) {
                        this.onVendorChange();
                    }
                },

                onVendorChange() {
                    const vendorId = this.selectedVendorId;
                    if (!vendorId) return;
                    this.line_items.forEach((item, index) => {
                        const partId = partIds[index] || item.part_id;
                        if (partId) {
                            item.price = getPriceForPart(partId, vendorId);
                        }
                    });
                },

                addItem() {
                    this.line_items.push({ qty: 1, price: 0, part_id: null });
                },

                removeItem(index) {
                    if (this.line_items.length > 1) {
                        this.line_items.splice(index, 1);
                    }
                },

                calculateTotal() {
                    return this.line_items.reduce((sum, item) => sum + (parseFloat(item.qty || 0) * parseFloat(item.price || 0)), 0);
                },

                formatCurrency(value) {
                    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);
                }
            }
        }
    </script>
</x-app-layout>
