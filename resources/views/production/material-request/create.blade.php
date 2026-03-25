<x-app-layout>
    <x-slot name="header">
        New Production Material Request
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6">
        <div class="rounded-2xl border bg-white p-6 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900">Pengajuan Tambahan Material</h1>
            <p class="mt-1 text-sm text-slate-500">Gunakan form ini untuk meminta material tambahan dari production ke warehouse di luar kebutuhan standar WO.</p>
        </div>

        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                <div class="font-semibold">Data belum bisa disimpan.</div>
                <ul class="mt-2 list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('production.material-request.store') }}"
            x-data="materialRequestForm(@js(old('items', [['part_id' => '', 'qty_requested' => '', 'notes' => '']])), @js($parts->map(fn($part) => [
                'id' => $part->id,
                'part_no' => $part->part_no,
                'part_name' => $part->part_name,
                'uom' => $part->uom ?? 'PCS',
                'stock_on_hand' => (float) ($part->stock_on_hand ?? 0),
                'stock_on_order' => (float) ($part->stock_on_order ?? 0),
            ])->values()))"
            class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="rounded-2xl border bg-white p-6 shadow-sm lg:col-span-1">
                    <h2 class="text-lg font-semibold text-slate-900">Header</h2>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Request Date</label>
                            <input type="date" name="request_date" value="{{ old('request_date', now()->toDateString()) }}"
                                class="mt-1 w-full rounded-lg border-slate-200 text-sm shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">WO Reference</label>
                            <select name="production_order_id" class="mt-1 w-full rounded-lg border-slate-200 text-sm shadow-sm">
                                <option value="">Manual request tanpa WO</option>
                                @foreach($orders as $order)
                                    <option value="{{ $order->id }}" @selected((string) old('production_order_id', $selectedOrderId) === (string) $order->id)>
                                        {{ $order->production_order_number }} - {{ $order->part?->part_no ?? '-' }} - {{ number_format($order->qty_planned) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Reason</label>
                            <input type="text" name="reason" value="{{ old('reason') }}"
                                placeholder="Contoh: shortage, rework, trial, setup, tambahan line"
                                class="mt-1 w-full rounded-lg border-slate-200 text-sm shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600">Notes</label>
                            <textarea name="notes" rows="4" class="mt-1 w-full rounded-lg border-slate-200 text-sm shadow-sm"
                                placeholder="Catatan tambahan untuk warehouse">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border bg-white p-6 shadow-sm lg:col-span-2">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Request Items</h2>
                            <p class="mt-1 text-sm text-slate-500">Pilih RM yang dibutuhkan, qty tambahan, dan catatan per item bila perlu.</p>
                        </div>
                        <button type="button" @click="addRow()"
                            class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                            Add Item
                        </button>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[920px] text-sm">
                            <thead class="border-b bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-3 py-3 text-left font-semibold">Material</th>
                                    <th class="px-3 py-3 text-right font-semibold">Stock On Hand</th>
                                    <th class="px-3 py-3 text-right font-semibold">Stock On Order</th>
                                    <th class="px-3 py-3 text-center font-semibold">UOM</th>
                                    <th class="px-3 py-3 text-right font-semibold">Qty Request</th>
                                    <th class="px-3 py-3 text-left font-semibold">Item Notes</th>
                                    <th class="px-3 py-3 text-center font-semibold">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(item, index) in rows" :key="index">
                                    <tr class="align-top">
                                        <td class="px-3 py-3">
                                            <select :name="`items[${index}][part_id]`" x-model="item.part_id"
                                                class="w-full rounded-lg border-slate-200 text-sm shadow-sm" required>
                                                <option value="">Pilih material</option>
                                                <template x-for="part in partOptions" :key="part.id">
                                                    <option :value="String(part.id)" x-text="`${part.part_no} - ${part.part_name}`"></option>
                                                </template>
                                            </select>
                                        </td>
                                        <td class="px-3 py-3 text-right font-mono text-slate-700" x-text="formatNumber(selectedPart(item.part_id)?.stock_on_hand ?? 0)"></td>
                                        <td class="px-3 py-3 text-right font-mono text-slate-700" x-text="formatNumber(selectedPart(item.part_id)?.stock_on_order ?? 0)"></td>
                                        <td class="px-3 py-3 text-center text-slate-700" x-text="selectedPart(item.part_id)?.uom ?? '-'"></td>
                                        <td class="px-3 py-3">
                                            <input type="number" step="0.0001" min="0.0001" :name="`items[${index}][qty_requested]`"
                                                x-model="item.qty_requested"
                                                class="w-full rounded-lg border-slate-200 text-right text-sm shadow-sm" required>
                                        </td>
                                        <td class="px-3 py-3">
                                            <input type="text" :name="`items[${index}][notes]`" x-model="item.notes"
                                                class="w-full rounded-lg border-slate-200 text-sm shadow-sm"
                                                placeholder="Opsional">
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <button type="button" @click="removeRow(index)"
                                                class="text-xs font-semibold uppercase tracking-wide text-rose-600 hover:text-rose-800">
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('production.material-request.index') }}"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancel
                        </a>
                        <button type="submit"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Submit Material Request
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function materialRequestForm(initialRows, partOptions) {
            return {
                partOptions,
                rows: initialRows.length ? initialRows.map((row) => ({
                    part_id: row.part_id ? String(row.part_id) : '',
                    qty_requested: row.qty_requested ?? '',
                    notes: row.notes ?? '',
                })) : [{ part_id: '', qty_requested: '', notes: '' }],
                addRow() {
                    this.rows.push({ part_id: '', qty_requested: '', notes: '' });
                },
                removeRow(index) {
                    if (this.rows.length === 1) {
                        this.rows[0] = { part_id: '', qty_requested: '', notes: '' };
                        return;
                    }
                    this.rows.splice(index, 1);
                },
                selectedPart(partId) {
                    return this.partOptions.find((part) => String(part.id) === String(partId));
                },
                formatNumber(value) {
                    return Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 4 });
                },
            };
        }
    </script>
</x-app-layout>
