<x-app-layout>
    <x-slot name="header">
        Create Departure Record
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    Please fix the highlighted fields below.
                </div>
            @endif

            <form method="POST" action="{{ route('departures.store') }}" class="space-y-6" id="arrival-form">
                @csrf
                <div class="bg-white border rounded-xl shadow-sm p-6 space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Shipment Details</h3>
                            <p class="text-sm text-gray-500">Enter invoice, dates, and vendor.</p>
                        </div>
                        <a href="{{ route('departures.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">Back to list</a>
                    </div>

                    <div class="space-y-1">
                        <x-input-label for="vendor_name" value="Vendor" />
                        <div class="relative">
                            <input 
                                type="text" 
                                id="vendor_name" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                placeholder="Type vendor name..." 
                                autocomplete="off"
                                required
                                value="{{ old('vendor_name') }}"
                            />
                            <div id="vendor-suggestions" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden"></div>
                        </div>
                        <input type="hidden" name="vendor_id" id="vendor_id" value="{{ old('vendor_id') }}">
                        <x-input-error :messages="$errors->get('vendor_id')" class="mt-1" />
                        <p class="mt-1 text-xs text-gray-500">Start typing to see suggestions</p>
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
                            <x-input-label for="trucking_company_id" value="Trucking Company" />
                            <select id="trucking_company_id" name="trucking_company_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select trucking company</option>
                                @foreach($truckings as $trucking)
                                    <option value="{{ $trucking->id }}" {{ old('trucking_company_id') == $trucking->id ? 'selected' : '' }}>
                                        {{ $trucking->company_name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('trucking_company_id')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="vessel" value="Vessel" />
                            <x-text-input id="vessel" name="vessel" type="text" placeholder="Optional" class="mt-1 block w-full" value="{{ old('vessel') }}" />
                            <x-input-error :messages="$errors->get('vessel')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="port_of_loading" value="Port of Loading" />
                            <x-text-input id="port_of_loading" name="port_of_loading" type="text" placeholder="e.g., HOCHIMINH, VIET NAM" class="mt-1 block w-full" value="{{ old('port_of_loading') }}" />
                            <x-input-error :messages="$errors->get('port_of_loading')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="country" value="Country of Origin" />
                            <select id="country" name="country" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="SOUTH KOREA" {{ old('country', 'SOUTH KOREA') == 'SOUTH KOREA' ? 'selected' : '' }}>SOUTH KOREA</option>
                                <option value="CHINA" {{ old('country') == 'CHINA' ? 'selected' : '' }}>CHINA</option>
                                <option value="JAPAN" {{ old('country') == 'JAPAN' ? 'selected' : '' }}>JAPAN</option>
                                <option value="VIET NAM" {{ old('country') == 'VIET NAM' ? 'selected' : '' }}>VIET NAM</option>
                                <option value="THAILAND" {{ old('country') == 'THAILAND' ? 'selected' : '' }}>THAILAND</option>
                                <option value="TAIWAN" {{ old('country') == 'TAIWAN' ? 'selected' : '' }}>TAIWAN</option>
                            </select>
                            <x-input-error :messages="$errors->get('country')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="container_numbers" value="Container Numbers" />
                            <textarea id="container_numbers" name="container_numbers" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="One per line, e.g.&#10;SKLU1809368 HUPH019101&#10;SKLU1939660 HHPH019102">{{ old('container_numbers') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Enter container numbers, one per line</p>
                            <x-input-error :messages="$errors->get('container_numbers')" class="mt-1" />
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
                            <h3 class="text-lg font-semibold text-gray-900">Departure Items</h3>
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
                                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Unit Bundle</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Qty Goods</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Nett Weight</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-600">Unit Weight</th>
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
                    <x-primary-button class="px-8">Save Departure</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const partApiBase = @json(url('/vendors'));
        const partsCache = {};
        const vendorsData = @json($vendors->map(function($v) { return ['id' => $v->id, 'name' => $v->vendor_name]; })->values()->toArray());

        const vendorInput = document.getElementById('vendor_name');
        const vendorIdInput = document.getElementById('vendor_id');
        const suggestionsBox = document.getElementById('vendor-suggestions');
        const itemRows = document.getElementById('item-rows');
        const addLineBtn = document.getElementById('add-line');
        let rowIndex = 0;

        // Autocomplete functionality
        vendorInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            
            if (query.length === 0) {
                suggestionsBox.classList.add('hidden');
                vendorIdInput.value = '';
                refreshRowsForVendor('');
                return;
            }

            const matches = vendorsData.filter(v => 
                v.name.toLowerCase().includes(query)
            );

            if (matches.length > 0) {
                suggestionsBox.innerHTML = matches.map(v => {
                    const name = v.name;
                    const lowerName = name.toLowerCase();
                    const index = lowerName.indexOf(query);
                    let highlighted = name;
                    
                    if (index !== -1) {
                        highlighted = name.substring(0, index) + 
                            '<span class="font-semibold text-blue-600">' + 
                            name.substring(index, index + query.length) + 
                            '</span>' + 
                            name.substring(index + query.length);
                    }
                    
                    return `
                        <div class="px-4 py-2 cursor-pointer hover:bg-blue-50 border-b border-gray-100 last:border-0 transition-colors" data-id="${v.id}" data-name="${name}">
                            ${highlighted}
                        </div>
                    `;
                }).join('');
                suggestionsBox.classList.remove('hidden');
            } else {
                suggestionsBox.innerHTML = '<div class="px-4 py-2 text-gray-500 text-sm italic">No vendors found</div>';
                suggestionsBox.classList.remove('hidden');
            }
        });

        // Handle suggestion click
        suggestionsBox.addEventListener('click', function(e) {
            const item = e.target.closest('[data-id]');
            if (item) {
                const vendorId = item.dataset.id;
                const vendorName = item.dataset.name;
                
                vendorInput.value = vendorName;
                vendorIdInput.value = vendorId;
                suggestionsBox.classList.add('hidden');
                refreshRowsForVendor(vendorId);
            }
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!vendorInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.classList.add('hidden');
            }
        });

        // Focus input shows suggestions if there's a query
        vendorInput.addEventListener('focus', function() {
            if (this.value.trim().length > 0) {
                const event = new Event('input');
                this.dispatchEvent(event);
            }
        });

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

        function guessUnitBundle(partData) {
            const name = (partData?.part_name_vendor || '').toLowerCase();
            if (name.includes('coil')) return 'Coil';
            if (name.includes('sheet')) return 'Sheet';
            return 'Pallet';
        }

        function guessUnitWeight(partData) {
            return 'KGM';
        }

        function findPart(vendorId, partId) {
            const list = partsCache[vendorId] || [];
            return list.find(p => String(p.id) === String(partId));
        }

        function applyPartDefaults(row, vendorId, partId) {
            const partData = findPart(vendorId, partId);
            if (!partData) return;

            const sizeInput = row.querySelector('input[name*="[size]"]');
            if (sizeInput && !sizeInput.value) {
                sizeInput.value = partData.size || '';
            }

            const unitBundleSelect = row.querySelector('select[name*="[unit_bundle]"]');
            if (unitBundleSelect && !unitBundleSelect.value) {
                unitBundleSelect.value = guessUnitBundle(partData);
            }

            const unitWeightSelect = row.querySelector('select[name*="[unit_weight]"]');
            if (unitWeightSelect && !unitWeightSelect.value) {
                unitWeightSelect.value = guessUnitWeight(partData);
            }
        }

        function addRow(existing = null) {
            const vendorId = vendorIdInput.value;
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
                    <input type="number" name="items[${rowIndex}][qty_bundle]" class="qty-bundle w-24 rounded-md border-gray-300" value="${existing?.qty_bundle ?? 0}" min="0" placeholder="Optional">
                </td>
                <td class="px-3 py-2">
                    <select name="items[${rowIndex}][unit_bundle]" class="w-20 rounded-md border-gray-300 text-sm">
                        <option value="Coil" ${existing?.unit_bundle === 'Coil' ? 'selected' : ''}>Coil</option>
                        <option value="Sheet" ${existing?.unit_bundle === 'Sheet' ? 'selected' : ''}>Sheet</option>
                        <option value="Pallet" ${(existing?.unit_bundle === 'Pallet' || !existing?.unit_bundle) ? 'selected' : ''}>Pallet</option>
                        <option value="Bundle" ${existing?.unit_bundle === 'Bundle' ? 'selected' : ''}>Bundle</option>
                        <option value="Pcs" ${existing?.unit_bundle === 'Pcs' ? 'selected' : ''}>Pcs</option>
                        <option value="Set" ${existing?.unit_bundle === 'Set' ? 'selected' : ''}>Set</option>
                        <option value="Box" ${existing?.unit_bundle === 'Box' ? 'selected' : ''}>Box</option>
                    </select>
                </td>
                <td class="px-3 py-2">
                    <input type="number" name="items[${rowIndex}][qty_goods]" class="qty-goods w-24 rounded-md border-gray-300" value="${existing?.qty_goods ?? 0}" min="0" required>
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" name="items[${rowIndex}][weight_nett]" class="w-28 rounded-md border-gray-300" value="${existing?.weight_nett ?? 0}" min="0" required>
                </td>
                <td class="px-3 py-2">
                    <select name="items[${rowIndex}][unit_weight]" class="w-20 rounded-md border-gray-300 text-sm">
                        <option value="KGM" ${(existing?.unit_weight === 'KGM' || !existing?.unit_weight) ? 'selected' : ''}>KGM</option>
                        <option value="KG" ${existing?.unit_weight === 'KG' ? 'selected' : ''}>KG</option>
                        <option value="Sheet" ${existing?.unit_weight === 'Sheet' ? 'selected' : ''}>Sheet</option>
                        <option value="Ton" ${existing?.unit_weight === 'Ton' ? 'selected' : ''}>Ton</option>
                    </select>
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
                applyPartDefaults(row, vendorIdInput.value, partId);
            }

            updateTotal(row);

            row.querySelector('.remove-line').addEventListener('click', () => {
                row.remove();
            });

            partSelect.addEventListener('change', () => {
                const selectedId = partSelect.value;
                applyPartDefaults(row, vendorIdInput.value, selectedId);
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
                    addRowWithPart(part.id, part);
                });
            } else {
                addRow();
            }
        }
        
        function addRowWithPart(partId, partData = null) {
            const vendorId = vendorIdInput.value;
            const tr = document.createElement('tr');
            tr.className = 'transition-all duration-200 ease-out';
            
            // Get part size if available from part data
            const partSize = partData?.size ?? '';
            const guessedBundle = guessUnitBundle(partData);
            const guessedWeight = guessUnitWeight(partData);
            
            tr.innerHTML = `
                <td class="px-3 py-2">
                    <input type="text" name="items[${rowIndex}][size]" class="w-36 rounded-md border-gray-300 text-sm" placeholder="1.00 x 200.0 x C" value="${partSize}">
                </td>
                <td class="px-3 py-2">
                    <select name="items[${rowIndex}][part_id]" class="part-select block w-48 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                        ${vendorId ? buildPartOptions(vendorId, partId) : '<option value="">Select vendor first</option>'}
                    </select>
                </td>
                <td class="px-3 py-2">
                    <input type="number" name="items[${rowIndex}][qty_bundle]" class="qty-bundle w-24 rounded-md border-gray-300" value="0" min="0" placeholder="Optional">
                </td>
                <td class="px-3 py-2">
                    <select name="items[${rowIndex}][unit_bundle]" class="w-20 rounded-md border-gray-300 text-sm">
                        <option value="Coil" ${guessedBundle === 'Coil' ? 'selected' : ''}>Coil</option>
                        <option value="Sheet" ${guessedBundle === 'Sheet' ? 'selected' : ''}>Sheet</option>
                        <option value="Pallet" ${guessedBundle === 'Pallet' ? 'selected' : ''}>Pallet</option>
                        <option value="Bundle">Bundle</option>
                        <option value="Pcs">Pcs</option>
                        <option value="Set">Set</option>
                        <option value="Box">Box</option>
                    </select>
                </td>
                <td class="px-3 py-2">
                    <input type="number" name="items[${rowIndex}][qty_goods]" class="qty-goods w-24 rounded-md border-gray-300" value="0" min="0" required>
                </td>
                <td class="px-3 py-2">
                    <input type="number" step="0.01" name="items[${rowIndex}][weight_nett]" class="w-28 rounded-md border-gray-300" value="0" min="0" required>
                </td>
                <td class="px-3 py-2">
                    <select name="items[${rowIndex}][unit_weight]" class="w-20 rounded-md border-gray-300 text-sm">
                        <option value="KGM" ${guessedWeight === 'KGM' ? 'selected' : ''}>KGM</option>
                        <option value="KG">KG</option>
                        <option value="Sheet">Sheet</option>
                        <option value="Ton">Ton</option>
                    </select>
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

        addLineBtn.addEventListener('click', () => addRow());

        document.addEventListener('DOMContentLoaded', async () => {
            const vendorId = vendorIdInput.value;
            const existingItems = @json(old('items', []));
            await loadParts(vendorId);
            if (existingItems.length) {
                existingItems.forEach(item => addRow(item));
            } else {
                addRow();
            }
        });

        function markInvalid(el) {
            if (!el) return;
            el.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        }

        function clearInvalid() {
            document.querySelectorAll('.border-red-500').forEach(el => {
                el.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
            });
        }

        const sizePattern = /^\d{1,4}(\.\d{1,2})?\s*x\s*\d{1,4}(\.\d)?\s*x\s*[A-Z]$/i;

        const form = document.getElementById('arrival-form');
        form.addEventListener('submit', function(e) {
            clearInvalid();

            const errors = [];
            if (!vendorIdInput.value) {
                errors.push('Pilih vendor dari daftar.');
                markInvalid(vendorInput);
            }

            const rows = Array.from(itemRows.querySelectorAll('tr'));
            if (!rows.length) {
                errors.push('Tambahkan minimal 1 item.');
            }

            rows.forEach(row => {
                const sizeInput = row.querySelector('input[name*="[size]"]');
                const qtyInput = row.querySelector('.qty-goods');
                const priceInput = row.querySelector('.price');
                const nettInput = row.querySelector('input[name*="[weight_nett]"]');
                const grossInput = row.querySelector('input[name*="[weight_gross]"]');
                const partSelect = row.querySelector('.part-select');

                const sizeVal = sizeInput?.value.trim() || '';
                const qtyVal = parseFloat(qtyInput?.value) || 0;
                const priceVal = parseFloat(priceInput?.value) || 0;
                const nettVal = parseFloat(nettInput?.value) || 0;
                const grossVal = parseFloat(grossInput?.value) || 0;

                if (sizeVal && !sizePattern.test(sizeVal)) {
                    errors.push('Format size harus seperti 3.2x374.5xC (hanya huruf di ujung).');
                    markInvalid(sizeInput);
                }
                if (!partSelect?.value) {
                    errors.push('Pilih Part Number untuk setiap baris.');
                    markInvalid(partSelect);
                }
                if (qtyVal <= 0) {
                    errors.push('Qty Goods harus lebih dari 0.');
                    markInvalid(qtyInput);
                }
                if (nettVal <= 0) {
                    errors.push('Nett Weight harus lebih dari 0.');
                    markInvalid(nettInput);
                }
                if (grossVal <= 0) {
                    errors.push('Gross Weight harus lebih dari 0.');
                    markInvalid(grossInput);
                }
                if (priceVal <= 0) {
                    errors.push('Price harus lebih dari 0.');
                    markInvalid(priceInput);
                }
            });

            if (errors.length) {
                e.preventDefault();
                alert([...new Set(errors)].join('\n'));
                return;
            }

            // Filter out items dengan qty_goods = 0 (seharusnya tidak ada setelah validasi)
            rows.forEach(row => {
                const qtyGoods = parseFloat(row.querySelector('.qty-goods')?.value) || 0;
                if (qtyGoods === 0) {
                    row.remove();
                }
            });
        });
    </script>
</x-app-layout>
