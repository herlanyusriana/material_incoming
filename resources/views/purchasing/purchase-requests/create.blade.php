<x-app-layout>
    <x-slot name="header">
        Purchasing • New Manual PR
    </x-slot>

    <div class="py-6" x-data="manualPr()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-xl border border-slate-200 rounded-3xl overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Create Manual Purchase Request</h2>
                    <p class="text-sm text-slate-500 mt-1">Manually add parts and quantities for a new purchase request.</p>
                </div>

                <form action="{{ route('purchasing.purchase-requests.store') }}" method="POST">
                    @csrf
                    <div class="p-6">
                        <div class="space-y-6">
                            <div class="overflow-x-auto border border-slate-200 rounded-2xl">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-widest w-1/2">Part</th>
                                            <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">Quantity</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">Required Date</th>
                                            <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-100">
                                        <template x-for="(item, index) in items" :key="index">
                                            <tr class="hover:bg-slate-50/80 transition-colors">
                                                <td class="px-4 py-3">
                                                    <div class="relative">
                                                        <select :name="`items[${index}][part_id]`" class="w-full rounded-xl border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                                                            <option value="">Select Part...</option>
                                                            @foreach (\App\Models\GciPart::all() as $part)
                                                                <option value="{{ $part->id }}">{{ $part->part_no }} - {{ $part->part_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <input type="hidden" :name="`items[${index}][selected]`" value="1">
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <input type="number" :name="`items[${index}][qty]`" x-model="item.qty" step="0.0001" class="w-32 rounded-xl border-slate-200 text-sm font-bold text-right" required>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="date" :name="`items[${index}][required_date]`" class="w-full rounded-xl border-slate-200 text-sm" required>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <button type="button" @click="removeItem(index)" class="p-2 text-rose-500 hover:bg-rose-50 rounded-xl transition-all">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <button type="button" @click="addItem()" class="px-4 py-2 rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-700 font-bold hover:bg-indigo-100 transition-all flex items-center gap-2 text-xs uppercase tracking-widest shadow-sm">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add More Item
                            </button>

                            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200">
                                <label class="block text-sm font-bold text-slate-700 uppercase tracking-wider mb-2">Additional Notes</label>
                                <textarea name="notes" rows="3" class="w-full rounded-2xl border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm" placeholder="Any specific instructions for this request..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-3">
                        <a href="{{ route('purchasing.purchase-requests.index') }}" class="px-6 py-2.5 rounded-2xl bg-white border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all uppercase text-xs tracking-wider">Cancel</a>
                        <button type="submit" class="px-8 py-2.5 rounded-2xl bg-slate-900 text-white font-bold hover:bg-slate-800 transition-all shadow-lg uppercase text-xs tracking-wider">Submit Purchase Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function manualPr() {
            return {
                items: [{ qty: 1 }],
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
