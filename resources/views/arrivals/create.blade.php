<x-app-layout>
    <x-slot name="header">
        Create Arrival Record
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    Please fix the highlighted fields below.
                </div>
            @endif

            <form method="POST" action="{{ route('arrivals.store') }}" class="space-y-6" id="arrival-form">
                @csrf
                <div class="bg-white border rounded-xl shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Shipment Details</h3>
                            <p class="text-sm text-gray-500">Enter invoice, dates, and vendor.</p>
                        </div>
                        <a href="{{ route('arrivals.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">Back to list</a>
                    </div>

                    <div class="space-y-1">
                        <x-input-label for="vendor_id" value="Vendor" />
                        <select name="vendor_id" id="vendor_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="">Pilih vendor terlebih dahulu</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected(old('vendor_id') == $vendor->id)>{{ $vendor->vendor_name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('vendor_id')" class="mt-1" />
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <x-input-label for="invoice_no" value="Invoice Number" />
                            <x-text-input id="invoice_no" name="invoice_no" type="text" placeholder="INV-2025-001" class="mt-1 block w-full" required value="{{ old('invoice_no') }}" />
                            <x-input-error :messages="$errors->get('invoice_no')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="invoice_date" value="Date" />
                            <x-text-input id="invoice_date" name="invoice_date" type="date" class="mt-1 block w-full" required value="{{ old('invoice_date') }}" />
                            <x-input-error :messages="$errors->get('invoice_date')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="ETD" value="Estimated Time of Departure (ETD)" />
                            <x-text-input id="ETD" name="ETD" type="date" class="mt-1 block w-full" value="{{ old('ETD') }}" />
                            <x-input-error :messages="$errors->get('ETD')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="bill_of_lading" value="Bill of Lading" />
                            <x-text-input id="bill_of_lading" name="bill_of_lading" type="text" placeholder="BL-56789" class="mt-1 block w-full" value="{{ old('bill_of_lading') }}" />
                            <x-input-error :messages="$errors->get('bill_of_lading')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="trucking_company" value="Trucking Company" />
                            <x-text-input id="trucking_company" name="trucking_company" type="text" placeholder="Optional" class="mt-1 block w-full" value="{{ old('trucking_company') }}" />
                            <x-input-error :messages="$errors->get('trucking_company')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="vessel" value="Vessel" />
                            <x-text-input id="vessel" name="vessel" type="text" placeholder="Optional" class="mt-1 block w-full" value="{{ old('vessel') }}" />
                            <x-input-error :messages="$errors->get('vessel')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="port_of_loading" value="Port of Loading" />
                            <x-text-input id="port_of_loading" name="port_of_loading" type="text" placeholder="Optional" class="mt-1 block w-full" value="{{ old('port_of_loading') }}" />
                            <x-input-error :messages="$errors->get('port_of_loading')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="hs_code" value="HS Code" />
                            <x-text-input id="hs_code" name="hs_code" type="text" placeholder="8471.30.00" class="mt-1 block w-full" value="{{ old('hs_code') }}" />
                            <x-input-error :messages="$errors->get('hs_code')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="currency" value="Currency" />
                            <x-text-input id="currency" name="currency" type="text" class="mt-1 block w-full" value="{{ old('currency', 'USD') }}" />
                            <x-input-error :messages="$errors->get('currency')" class="mt-1" />
                        </div>
                        <div class="md:col-span-2 space-y-1">
                            <x-input-label for="notes" value="Notes" />
                            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Special instructions or remarks">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <div class="bg-white border rounded-xl shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Arrival Items</h3>
                            <p class="text-sm text-gray-500">All parts from selected vendor auto-loaded. Fill in quantities, weights & prices.</p>
                        </div>
                        <button type="button" id="add-line" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700 transition-all duration-200 hover:-translate-y-0.5 shadow-sm">+ Add Line</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm" id="items-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Size (L x W x T)</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Part Number</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Qty Bundle</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Qty Goods</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Nett Weight</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Gross Weight</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Price</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Total</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody id="item-rows" class="divide-y divide-gray-200"></tbody>
                        </table>
                    </div>
                    <x-input-error :messages="$errors->get('items')" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button class="px-8">Save Arrival</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const partApiBase = @json(url('/vendors'));
        const partsCache = {};

        const vendorSelect = document.getElementById('vendor_id');
        const itemRows = document.getElementById('item-rows');
        const addLineBtn = document.getElementById('add-line');
        let rowIndex = 0;

        function buildPartOptions(vendorId, partId = null) {
            const list = partsCache[vendorId] || [];
            const options = list
                .map(p => `<option value="${p.id}" ${String(p.id) === String(partId) ? 'selected' : ''}>${p.part_no} — ${p.part_name_vendor}</option>`)
                .join('');
            return `<option value="">Select Part Number</option>${options}`;
        }

        function updateTotal(row) {
            const qty = parseFloat(row.querySelector('.qty-goods').value) || 0;
            const price = parseFloat(row.querySelector('.price').value) || 0;
            const total = (qty * price).toFixed(2);
            row.querySelector('.total').value = total;
        }

        function addRow(existing = null) {
            const vendorId = vendorSelect.value;
            const tr = document.createElement('tr');
            tr.className = 'transition-all duration-200 ease-out';
            tr.innerHTML = `
                <td class="px-3 py-2">
                    <input type="text" name="items[${rowIndex}][size]" class="w-36 rounded-md border-gray-300 text-sm" placeholder="1.00 x 200.0 x C" value="${existing?.size ?? ''}">
                </td>
                <td class="px-3 py-2">
                    <select name="items[${rowIndex}][part_id]" class="part-select block w-48 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" ${vendorId ? '' : 'disabled'} required>
                        ${vendorId ? buildPartOptions(vendorId, existing?.part_id) : '<option value=\"\">Select vendor first</option>'}
                    </select>
                </td>
                <td class="px-3 py-2">
                    <input type="number" name="items[${rowIndex}][qty_bundle]" class="qty-bundle w-24 rounded-md border-gray-300" value="${existing?.qty_bundle ?? 0}" min="0" required>
                </td>
                <td class="px-3 py-2">
                    <input type="number" name="items[${rowIndex}][qty_goods]" class="qty-goods w-24 rounded-md border-gray-300" value="${existing?.qty_goods ?? 0}" min="0" required>
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" name="items[${rowIndex}][weight_nett]" class="w-28 rounded-md border-gray-300" value="${existing?.weight_nett ?? 0}" min="0" required>
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" name="items[${rowIndex}][weight_gross]" class="w-28 rounded-md border-gray-300" value="${existing?.weight_gross ?? 0}" min="0" required>
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" name="items[${rowIndex}][price]" class="price w-28 rounded-md border-gray-300" value="${existing?.price ?? 0}" min="0" required>
                </td>
                <td class="px-3 py-2">
                    <input type="text" class="total w-28 rounded-md border-gray-200 bg-gray-50" value="0.00" readonly>
                    <input type="hidden" name="items[${rowIndex}][notes]" value="${existing?.notes ?? ''}">
                </td>
                <td class="px-3 py-2 text-right">
                    <button type="button" class="remove-line text-red-600 hover:text-red-800">Remove</button>
                </td>
            `;

            itemRows.appendChild(tr);
            rowIndex++;
            bindRow(tr, existing?.part_id);
        }

        function bindRow(row, partId = null) {
            const qtyField = row.querySelector('.qty-goods');
            const priceField = row.querySelector('.price');
            [qtyField, priceField].forEach(field => {
                field.addEventListener('input', () => updateTotal(row));
            });

            const partSelect = row.querySelector('.part-select');
            if (partId) {
                partSelect.value = partId;
            }

            updateTotal(row);

            row.querySelector('.remove-line').addEventListener('click', () => {
                row.remove();
            });
        }

        async function loadParts(vendorId) {
            if (!vendorId) return [];
            if (partsCache[vendorId]) return partsCache[vendorId];
            const response = await fetch(`${partApiBase}/${vendorId}/parts`);
            if (!response.ok) return [];
            const data = await response.json();
            partsCache[vendorId] = data;
            return data;
        }

        async function refreshRowsForVendor(vendorId) {
            itemRows.innerHTML = '';
            rowIndex = 0;
            const parts = await loadParts(vendorId);
            
            // Auto-populate all parts from selected vendor
            if (parts && parts.length > 0) {
                parts.forEach(part => {
                    addRowWithPart(part.id);
                });
            } else {
                addRow();
            }
        }
        
        function addRowWithPart(partId) {
            const vendorId = vendorSelect.value;
            const tr = document.createElement('tr');
            tr.className = 'transition-all duration-200 ease-out';
            tr.innerHTML = `
                <td class="px-3 py-2">
                    <select name="items[${rowIndex}][part_id]" class="part-select block w-48 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        ${vendorId ? buildPartOptions(vendorId, partId) : '<option value="">Select vendor first</option>'}
                    </select>
                </td>
                <td class="px-3 py-2">
                    <input type="text" name="items[${rowIndex}][size]" class="w-36 rounded-md border-gray-300 text-sm" placeholder="1.00 x 200.0 x C" value="">
                </td>
                <td class="px-3 py-2">
                    <input type="number" name="items[${rowIndex}][qty_bundle]" class="qty-bundle w-24 rounded-md border-gray-300" value="0" min="0" required>
                </td>
                <td class="px-3 py-2">
                    <input type="number" name="items[${rowIndex}][qty_goods]" class="qty-goods w-24 rounded-md border-gray-300" value="0" min="0" required>
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" name="items[${rowIndex}][weight_nett]" class="w-28 rounded-md border-gray-300" value="0" min="0" required>
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" name="items[${rowIndex}][weight_gross]" class="w-28 rounded-md border-gray-300" value="0" min="0" required>
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" name="items[${rowIndex}][price]" class="price w-28 rounded-md border-gray-300" value="0" min="0" required>
                </td>
                <td class="px-3 py-2">
                    <input type="text" class="total w-28 rounded-md border-gray-200 bg-gray-50" value="0.00" readonly>
                    <input type="hidden" name="items[${rowIndex}][notes]" value="">
                </td>
                <td class="px-3 py-2 text-right">
                    <button type="button" class="remove-line text-red-600 hover:text-red-800">Remove</button>
                </td>
            `;

            itemRows.appendChild(tr);
            rowIndex++;
            bindRow(tr, partId);
        }

        vendorSelect.addEventListener('change', () => {
            const vendorId = vendorSelect.value;
            refreshRowsForVendor(vendorId);
        });

        addLineBtn.addEventListener('click', () => addRow());

        document.addEventListener('DOMContentLoaded', async () => {
            const vendorId = vendorSelect.value;
            const existingItems = @json(old('items', []));
            await loadParts(vendorId);
            if (existingItems.length) {
                existingItems.forEach(item => addRow(item));
            } else {
                addRow();
            }
        });

        // Filter out items with qty_goods = 0 before submit
        const form = document.getElementById('arrival-form');
        form.addEventListener('submit', function(e) {
            const rows = itemRows.querySelectorAll('tr');
            rows.forEach(row => {
                const qtyGoods = parseFloat(row.querySelector('.qty-goods')?.value) || 0;
                if (qtyGoods === 0) {
                    row.remove();
                }
            });
        });
    </script>
</x-app-layout>
