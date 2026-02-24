<x-app-layout>
    <x-slot name="header">
        Purchasing • New PR from MRP
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-xl border border-slate-200 rounded-3xl overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Generate PR from MRP Plan</h2>
                    <p class="text-sm text-slate-500 mt-1">Select items from the MRP Purchase Plan to create a formal
                        Purchase Request.</p>
                </div>

                <form action="{{ route('purchasing.purchase-requests.store') }}" method="POST">
                    @csrf
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="overflow-x-auto border border-slate-200 rounded-2xl">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left">
                                                <input type="checkbox" id="select-all"
                                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">
                                                Part Info</th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">
                                                Vendor / Price</th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">
                                                Plan Date</th>
                                            <th
                                                class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">
                                                Net Required</th>
                                            <th
                                                class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">
                                                Planned Order</th>
                                            <th
                                                class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">
                                                PR Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-100">
                                        @forelse ($plans as $index => $plan)
                                            <tr class="hover:bg-slate-50/80 transition-colors">
                                                <td class="px-4 py-3">
                                                    <input type="checkbox" name="items[{{ $index }}][selected]" value="1"
                                                        class="item-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                    <input type="hidden" name="items[{{ $index }}][part_id]"
                                                        value="{{ $plan->part_id }}">
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    <div class="text-xs font-bold text-slate-900 font-mono">
                                                        {{ $plan->part?->part_no }}</div>
                                                    <div class="text-[10px] text-slate-500">
                                                        {{ Str::limit($plan->part?->part_name, 30) }}</div>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap">
                                                    @if (isset($vendorLinks[$plan->part_id]) && $vendorLinks[$plan->part_id]->count())
                                                        @php $bestVl = $vendorLinks[$plan->part_id]->first(); @endphp
                                                        <div class="text-xs font-bold text-indigo-600">
                                                            {{ $bestVl->vendor?->vendor_name }}</div>
                                                        <div class="text-[10px] text-slate-500 font-mono">
                                                            {{ number_format($bestVl->price, 3) }} /unit</div>
                                                        @if ($vendorLinks[$plan->part_id]->count() > 1)
                                                            <div class="text-[9px] text-slate-400">
                                                                +{{ $vendorLinks[$plan->part_id]->count() - 1 }} vendor lain</div>
                                                        @endif
                                                    @else
                                                        <div class="text-[10px] text-slate-400 italic">No vendor data</div>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-600">
                                                    {{ \Carbon\Carbon::parse($plan->plan_date)->format('M d, Y') }}
                                                </td>
                                                <td class="px-4 py-3 text-right text-xs font-mono text-slate-600">
                                                    {{ number_format($plan->net_required, 2) }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 text-right text-xs font-bold text-indigo-600 font-mono">
                                                    {{ number_format($plan->planned_order_rec, 2) }}
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <input type="number" name="items[{{ $index }}][qty]"
                                                        value="{{ (float) $plan->planned_order_rec }}" step="0.0001"
                                                        class="w-24 rounded-xl border-slate-200 text-xs font-bold text-right"
                                                        required>
                                                    <input type="hidden" name="items[{{ $index }}][required_date]"
                                                        value="{{ $plan->plan_date }}">
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="px-4 py-12 text-center text-slate-500">No pending MRP
                                                    plans found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200">
                                <label
                                    class="block text-sm font-bold text-slate-700 uppercase tracking-wider mb-2">Additional
                                    Notes</label>
                                <textarea name="notes" rows="3"
                                    class="w-full rounded-2xl border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                    placeholder="Any specific instructions for this request..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end gap-3">
                        <a href="{{ route('purchasing.purchase-requests.index') }}"
                            class="px-6 py-2.5 rounded-2xl bg-white border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all uppercase text-xs tracking-wider">Cancel</a>
                        <button type="submit"
                            class="px-8 py-2.5 rounded-2xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition-all shadow-lg uppercase text-xs tracking-wider">Create
                            Purchase Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('select-all').addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>
</x-app-layout>