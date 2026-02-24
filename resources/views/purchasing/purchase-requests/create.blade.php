<x-app-layout>
    <x-slot name="header">
        Purchasing • New Manual PR
    </x-slot>

    <div class="py-6" x-data="manualPr()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-xl border border-slate-200 rounded-3xl overflow-hidden">
                <div class="p-8 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">Generate Manual Purchase Request</h2>
                    <p class="text-sm text-slate-500 mt-1">Manually add parts and quantities for a new purchase request.
                    </p>
                </div>

                <form action="{{ route('purchasing.purchase-requests.store') }}" method="POST">
                    @csrf
                    <div class="p-8 space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <label
                                    class="block text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Select
                                    Vendor</label>
                                <select name="vendor_id" x-model="selectedVendorId" @change="onVendorChange()"
                                    class="w-full rounded-2xl border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm font-semibold"
                                    required>
                                    <option value="">— Choose Vendor —</option>
                                    @foreach ($vendors as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->vendor_code }} -
                                            {{ $vendor->vendor_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-4">
                                <label
                                    class="block text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-1">General
                                    Notes</label>
                                <input type="text" name="notes" class="w-full rounded-2xl border-slate-200 text-sm"
                                    placeholder="Any specific instructions for this request...">
                            </div>
                        </div>

                        <div>
                            <h3
                                class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                                <span class="h-px w-8 bg-slate-200"></span>
                                Order Line Items
                            </h3>
                            <!-- Removed overflow-hidden to allow TomSelect dropdown to overlay properly -->
                            <div class="border border-slate-200 rounded-2xl" style="overflow: visible;">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead
                                        class="bg-slate-50 text-xs text-slate-500 font-bold uppercase tracking-wider rounded-t-2xl">
                                        <tr>
                                            <th class="px-6 py-4 text-left rounded-tl-2xl">Part Description</th>
                                            <th class="px-6 py-4 text-right">Order Qty</th>
                                            <th class="px-6 py-4 text-left">Required Date</th>
                                            <th class="px-6 py-4 text-center rounded-tr-2xl">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-100">
                                        <template x-for="(item, index) in items" :key="index">
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="px-6 py-4">
                                                    <!-- Re-initialize tomselect whenever the options change or new row added -->
                                                    <select :name="`items[${index}][part_id]`"
                                                        class="w-full rounded-xl border-slate-200 text-sm" required
                                                        x-bind:disabled="!selectedVendorId">
                                                        <option value="">Select Part...</option>
                                                        <template x-for="vp in getAvailableParts()" :key="vp.id">
                                                            <option :value="vp.id" x-text="vp.text"></option>
                                                        </template>
                                                    </select>
                                                    <input type="hidden" :name="`items[${index}][selected]`" value="1">
                                                </td>
                                                <td class="px-6 py-4 text-right">
                                                    <input type="number" :name="`items[${index}][qty]`"
                                                        x-model="item.qty" step="0.0001"
                                                        class="w-24 rounded-xl border-slate-200 text-sm text-right"
                                                        required x-bind:disabled="!selectedVendorId">
                                                </td>
                                                <td class="px-6 py-4">
                                                    <input type="date" :name="`items[${index}][required_date]`"
                                                        class="w-full rounded-xl border-slate-200 text-sm" required
                                                        x-bind:disabled="!selectedVendorId">
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <button type="button" @click="removeItem(index)"
                                                        class="text-rose-500 hover:bg-rose-50 p-2 rounded-xl transition-all">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
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
                            <button type="button" @click="addItem()"
                                class="mt-4 px-4 py-2 rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-700 font-bold hover:bg-indigo-100 transition-all flex items-center gap-2 text-xs uppercase tracking-widest shadow-sm">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Add Manual Item
                            </button>
                        </div>
                    </div>

                    <div class="p-8 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-3">
                        <a href="{{ route('purchasing.purchase-requests.index') }}"
                            class="px-6 py-3 rounded-2xl bg-white border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all uppercase text-xs tracking-wider">Discard</a>
                        <button type="submit"
                            class="px-10 py-3 rounded-2xl bg-slate-900 text-white font-bold hover:bg-slate-800 transition-all shadow-xl uppercase text-xs tracking-wider">Generate
                            Official PR</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function manualPr() {
            // PHP passes the array as `gci_part_id => [ vendor_id => vendor_part_data ]`
            // Let's invert it so we can easily get parts by vendor_id
            const vendorPartMap = @js($vendorPartMap ?? []);
            const allParts = @js($parts ?? []);

            const partsByVendor = {};
            // Group parts by vendor
            for (const [gciPartId, vendors] of Object.entries(vendorPartMap)) {
                // Find original part to get its names
                const pt = allParts.find(p => p.id == gciPartId);
                if (!pt) continue;

                for (const [vendorId, vPartData] of Object.entries(vendors)) {
                    if (!partsByVendor[vendorId]) {
                        partsByVendor[vendorId] = [];
                    }
                    partsByVendor[vendorId].push({
                        id: gciPartId,
                        text: pt.part_no + ' - ' + (pt.part_name || '') + ' (' + vPartData.part_no + ')'
                    });
                }
            }

            return {
                selectedVendorId: '',
                items: [{ qty: 1 }],

                getAvailableParts() {
                    if (!this.selectedVendorId || !partsByVendor[this.selectedVendorId]) {
                        return [];
                    }
                    return partsByVendor[this.selectedVendorId];
                },

                onVendorChange() {
                    // Changing vendor resets items
                    this.items = [{ qty: 1 }];
                    // Delay to let alpine render the options, then TomSelect MutationObserver catches it
                },

                addItem() {
                    this.items.push({ qty: 1 });
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                }
            }
        }
    </script>
</x-app-layout>