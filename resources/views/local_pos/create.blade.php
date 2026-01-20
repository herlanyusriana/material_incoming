<x-app-layout>
    <x-slot name="header">
        Create Local PO
    </x-slot>

    @php
        $partsPayload = collect($parts)->map(fn ($p) => [
            'id' => $p->id,
            'vendor_id' => $p->vendor_id,
            'price' => $p->price,
            'uom' => $p->uom,
            'label' => trim((string) $p->part_no) . ' — ' . trim((string) ($p->part_name_gci ?? $p->part_name_vendor ?? '')),
        ])->values();
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                    <div class="font-semibold mb-2">Gagal membuat Local PO:</div>
                    <ul class="list-disc ml-5 space-y-1">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('local-pos.store') }}" method="POST" enctype="multipart/form-data"
                class="bg-white border border-slate-200 rounded-2xl shadow-lg p-8 space-y-8" id="local-po-form">
                @csrf

                <div class="flex items-center justify-between pb-6 border-b border-slate-200">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Local PO</h3>
                        <p class="text-sm text-slate-600 mt-1">Buat PO lokal tanpa proses Departure, lalu langsung Receive.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('local-pos.index') }}"
                            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">Back</a>
                    </div>
                </div>

                <div class="grid md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label class="text-sm font-semibold text-slate-700">Vendor (LOCAL)</label>
                        <select name="vendor_id" class="mt-1 w-full rounded-xl border-slate-200" required id="vendor-select">
                            <option value="" disabled @selected(old('vendor_id') === null || old('vendor_id') === '')>Select vendor</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}" @selected((string) old('vendor_id') === (string) $v->id)>{{ $v->vendor_name }}</option>
                            @endforeach
                        </select>
                        @error('vendor_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">PO No</label>
                        <input type="text" name="po_no" value="{{ old('po_no') }}"
                            class="mt-1 w-full rounded-xl border-slate-200 uppercase" placeholder="PO-LOCAL-001" required>
                        @error('po_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">PO Date</label>
                        <input type="date" name="po_date" value="{{ old('po_date', now()->toDateString()) }}"
                            class="mt-1 w-full rounded-xl bor   der-slate-200" required>
                        @error('po_date') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid md:grid-cols-4 gap-4">
                    <div>
                        <label class="text-sm font-semibold text-slate-700">Currency</label>
                        <input type="text" name="currency" value="{{ old('currency', 'IDR') }}"
                            class="mt-1 w-full rounded-xl border-slate-200 uppercase" placeholder="IDR">
                        @error('currency') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3">
                        <label class="text-sm font-semibold text-slate-700">Notes</label>
                        <input type="text" name="notes" value="{{ old('notes') }}"
                            class="mt-1 w-full rounded-xl border-slate-200 uppercase" placeholder="Optional">
                        @error('notes') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>



                <div class="bg-white rounded-xl p-6 border border-slate-200 space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Items</h4>
                        <button type="button" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold" id="add-item-btn">+ Add Item</button>
                    </div>

                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="min-w-full divide-y divide-slate-200 text-sm" id="items-table">
                            <thead class="bg-slate-50">
                                <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left font-semibold">Part</th>
                                    <th class="px-4 py-3 text-left font-semibold">Size</th>
                                    <th class="px-4 py-3 text-right font-semibold">Qty Goods</th>
                                    <th class="px-4 py-3 text-right font-semibold">Price (IDR)</th>
                                    <th class="px-4 py-3 text-right font-semibold">Net (KGM)</th>
                                    <th class="px-4 py-3 text-right font-semibold">Gross (KGM)</th>
                                    <th class="px-4 py-3 text-right font-semibold">Action</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100" id="items-tbody">
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <select name="items[0][part_id]" class="w-72 rounded-xl border-slate-200" required data-part-select data-old="{{ old('items.0.part_id') }}">
                                            <option value="" disabled selected>Select vendor first</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" name="items[0][size]" class="w-40 rounded-xl border-slate-200 uppercase"
                                            placeholder="0.7 X 530 X C" value="{{ old('items.0.size') }}">
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <input type="number" name="items[0][qty_goods]" min="0" step="1"
                                                value="{{ old('items.0.qty_goods', 0) }}" class="w-24 text-right rounded-xl border-slate-200" required>
                                            <select name="items[0][unit_goods]" class="w-24 rounded-xl border-slate-200" required>
                                                <option value="PCS">PCS</option>
                                                <option value="COIL">COIL</option>
                                                <option value="SHEET">SHEET</option>
                                                <option value="SET">SET</option>
                                                <option value="EA">EA</option>
                                                <option value="KGM">KGM</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" name="items[0][price]" step="0.01" min="0"
                                            value="{{ old('items.0.price', 0) }}" class="w-32 text-right rounded-xl border-slate-200" placeholder="0">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" name="items[0][weight_nett]" step="0.01" min="0"
                                            value="{{ old('items.0.weight_nett', 0) }}" class="w-24 text-right rounded-xl border-slate-200">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" name="items[0][weight_gross]" step="0.01" min="0"
                                            value="{{ old('items.0.weight_gross', 0) }}" class="w-24 text-right rounded-xl border-slate-200">
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" class="text-red-600 hover:text-red-800 font-semibold remove-item" disabled>Remove</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    @error('items') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-200">
                    <a href="{{ route('local-pos.index') }}"
                        class="px-5 py-2.5 text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors text-sm font-medium">Cancel</a>
                    <button type="submit"
                        class="px-8 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition-colors shadow-sm">
                        Create &amp; Receive
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const vendorSelect = document.getElementById('vendor-select');
            const tbody = document.getElementById('items-tbody');
            const addBtn = document.getElementById('add-item-btn');
            let idx = 1;

            const parts = {{ \Illuminate\Support\Js::from($partsPayload) }};

            function setPartsOptions(selectEl) {
                if (!selectEl) return;
                const vendorId = vendorSelect.value ? Number(vendorSelect.value) : null;
                const current = selectEl.value;
                const desired = selectEl.dataset.old && String(selectEl.dataset.old).trim() !== '' ? String(selectEl.dataset.old).trim() : current;

                selectEl.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.disabled = true;
                placeholder.selected = true;
                placeholder.textContent = vendorId ? 'Select part' : 'Select vendor first';
                selectEl.appendChild(placeholder);

                if (!vendorId) return;

                const list = parts.filter(p => Number(p.vendor_id) === vendorId);
                list.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = String(p.id);
                    opt.textContent = p.label;
                    selectEl.appendChild(opt);
                });

                if (desired) {
                    const exists = Array.from(selectEl.options).some(o => o.value === desired);
                    if (exists) {
                        selectEl.value = desired;
                        delete selectEl.dataset.old;
                    }
                }
            }

            function bindRemoveButtons() {
                const rows = Array.from(tbody.querySelectorAll('tr'));
                rows.forEach((row) => {
                    const btn = row.querySelector('.remove-item');
                    if (!btn) return;
                    btn.disabled = rows.length === 1;
                });
            }

            function addRow() {
                const row = document.createElement('tr');
                row.className = 'hover:bg-slate-50';
                row.innerHTML = `
                    <td class="px-4 py-3">
                        <select name="items[${idx}][part_id]" class="w-72 rounded-xl border-slate-200" required data-part-select data-old="">
                            <option value="" disabled selected>Select vendor first</option>
                        </select>
                    </td>
                    <td class="px-4 py-3">
                        <input type="text" name="items[${idx}][size]" class="w-40 rounded-xl border-slate-200 uppercase" placeholder="0.7 X 530 X C">
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <input type="number" name="items[${idx}][qty_goods]" min="0" step="1" value="0" class="w-24 text-right rounded-xl border-slate-200" required>
                            <select name="items[${idx}][unit_goods]" class="w-24 rounded-xl border-slate-200" required>
                                <option value="PCS">PCS</option>
                                <option value="COIL">COIL</option>
                                <option value="SHEET">SHEET</option>
                                <option value="SET">SET</option>
                                <option value="EA">EA</option>
                                <option value="KGM">KGM</option>
                            </select>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <input type="number" name="items[${idx}][price]" step="0.01" min="0" value="0" class="w-32 text-right rounded-xl border-slate-200" placeholder="0">
                    </td>
                    <td class="px-4 py-3">
                        <input type="number" name="items[${idx}][weight_nett]" step="0.01" min="0" value="0" class="w-24 text-right rounded-xl border-slate-200">
                    </td>
                    <td class="px-4 py-3">
                        <input type="number" name="items[${idx}][weight_gross]" step="0.01" min="0" value="0" class="w-24 text-right rounded-xl border-slate-200">
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button type="button" class="text-red-600 hover:text-red-800 font-semibold remove-item">Remove</button>
                    </td>
                `;
                tbody.appendChild(row);
                idx++;

                setPartsOptions(row.querySelector('select[data-part-select]'));
                bindRemoveButtons();

                row.querySelector('.remove-item')?.addEventListener('click', () => {
                    row.remove();
                    bindRemoveButtons();
                });
            }

            vendorSelect?.addEventListener('change', () => {
                tbody.querySelectorAll('select[data-part-select]').forEach(sel => setPartsOptions(sel));
            });

            // Auto-fill price and uom when part is selected
            tbody.addEventListener('change', (e) => {
                if (e.target && e.target.matches('select[data-part-select]')) {
                    const row = e.target.closest('tr');
                    const partId = e.target.value;
                    const part = parts.find(p => String(p.id) === String(partId));
                    if (part && row) {
                        // Fill price if available
                        if (part.price) {
                            const priceInput = row.querySelector('input[name*="[price]"]');
                            if (priceInput) priceInput.value = part.price;
                        }
                        // Select unit_goods if matches uom
                        if (part.uom) {
                            const uomSelect = row.querySelector('select[name*="[unit_goods]"]');
                            if (uomSelect) {
                                // check if option exists
                                const exists = Array.from(uomSelect.options).some(o => o.value === part.uom);
                                if (exists) uomSelect.value = part.uom;
                            }
                        }
                    }
                }
            });

            addBtn?.addEventListener('click', addRow);

            document.querySelectorAll('.remove-item').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const row = e.target.closest('tr');
                    if (!row) return;
                    if (tbody.querySelectorAll('tr').length <= 1) return;
                    row.remove();
                    bindRemoveButtons();
                });
            });

            tbody.querySelectorAll('select[data-part-select]').forEach(sel => setPartsOptions(sel));
            bindRemoveButtons();
        })();
    </script>
</x-app-layout>
