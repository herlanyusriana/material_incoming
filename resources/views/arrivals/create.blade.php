<x-app-layout>
    <x-slot name="header">
        Create Departure Record
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 space-y-2">
                    <div class="font-semibold">Silakan periksa kembali kolom yang ditandai.</div>
                    <ul class="list-disc list-inside space-y-1 text-red-800">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Departure Form</h1>
                    <p class="text-sm text-gray-600">Isi detail vendor, jadwal, transport, dan dokumen.</p>
                </div>
                <a href="{{ route('departures.index') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Back to list</a>
            </div>

            <form method="POST" action="{{ route('departures.store') }}" class="space-y-6" id="arrival-form">
                @csrf

                <!-- Section 1: Vendor & Invoice -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xs font-semibold text-gray-500 tracking-wide mb-4 uppercase">Vendor & Invoice</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2 space-y-1">
                            <label for="vendor_name" class="text-sm font-medium text-gray-700">Vendor</label>
                            <div class="relative">
                                <input type="text" id="vendor_name" name="vendor_name" value="{{ old('vendor_name') }}" placeholder="Ketik nama vendor..." class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" autocomplete="off" required>
                                <div id="vendor-suggestions" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden"></div>
                            </div>
                            <p class="text-xs text-gray-500">Mulai ketik untuk melihat saran (autocomplete).</p>
                            <input type="hidden" name="vendor_id" id="vendor_id" value="{{ old('vendor_id') }}">
                            @error('vendor_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="invoice_no" class="text-sm font-medium text-gray-700">Invoice Number</label>
                            <input type="text" id="invoice_no" name="invoice_no" value="{{ old('invoice_no') }}" placeholder="INV-2025-001" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                            @error('invoice_no') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="invoice_date" class="text-sm font-medium text-gray-700">Invoice Date</label>
                            <input type="date" id="invoice_date" name="invoice_date" value="{{ old('invoice_date') }}" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                            @error('invoice_date') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="currency" class="text-sm font-medium text-gray-700">Currency</label>
                            <select id="currency" name="currency" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="USD" {{ old('currency', 'USD') == 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="IDR" {{ old('currency') == 'IDR' ? 'selected' : '' }}>IDR</option>
                                <option value="EUR" {{ old('currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                                <option value="JPY" {{ old('currency') == 'JPY' ? 'selected' : '' }}>JPY</option>
                            </select>
                            @error('currency') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 2: Shipment Schedule -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xs font-semibold text-gray-500 tracking-wide mb-1 uppercase">Shipment Schedule</h2>
                    <p class="text-xs text-gray-500 mb-4">Isi jadwal keberangkatan, kedatangan, serta detail kapal dan pelabuhan.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="etd" class="text-sm font-medium text-gray-700">Estimated Time of Departure (ETD)</label>
                            <input type="date" id="etd" name="etd" value="{{ old('etd') }}" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @error('etd') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1">
                            <label for="eta" class="text-sm font-medium text-gray-700">Estimated Time of Arrival (ETA)</label>
                            <input type="date" id="eta" name="eta" value="{{ old('eta') }}" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @error('eta') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1">
                            <label for="vessel" class="text-sm font-medium text-gray-700">Vessel</label>
                            <input type="text" id="vessel" name="vessel" value="{{ old('vessel') }}" placeholder="Nama kapal (opsional)" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @error('vessel') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1">
                            <label for="port_of_loading" class="text-sm font-medium text-gray-700">Port of Loading</label>
                            <input type="text" id="port_of_loading" name="port_of_loading" value="{{ old('port_of_loading') }}" placeholder="e.g., HOCHIMINH, VIET NAM" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @error('port_of_loading') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 3: Transport -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xs font-semibold text-gray-500 tracking-wide mb-1 uppercase">Transport</h2>
                    <p class="text-xs text-gray-500 mb-4">Detail trucking dan container.</p>
                    <div class="space-y-4">
                        <div class="space-y-1">
                            <label for="trucking_company_id" class="text-sm font-medium text-gray-700">Trucking Company</label>
                            <select id="trucking_company_id" name="trucking_company_id" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Pilih trucking company</option>
                                @foreach($truckings as $trucking)
                                    <option value="{{ $trucking->id }}" {{ old('trucking_company_id') == $trucking->id ? 'selected' : '' }}>
                                        {{ $trucking->company_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('trucking_company_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="container_numbers" class="text-sm font-medium text-gray-700">Container Numbers</label>
                            <textarea id="container_numbers" name="container_numbers" rows="3" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Satu per baris, contoh:&#10;SKLU1809368 HUPH019101&#10;SKLU1939660 HHPH019102">{{ old('container_numbers') }}</textarea>
                            <p class="text-xs text-gray-500">Enter one container number per line.</p>
                            @error('container_numbers') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1">
                            <label for="seal_code" class="text-sm font-medium text-gray-700">Seal Code</label>
                            <input type="text" id="seal_code" name="seal_code" value="{{ old('seal_code') }}" placeholder="Contoh: HUPH019101" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <p class="text-xs text-gray-500">Kode segel (optional).</p>
                            @error('seal_code') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 4: Documents & Notes -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xs font-semibold text-gray-500 tracking-wide mb-4 uppercase">Documents & Notes</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="bl_no" class="text-sm font-medium text-gray-700">Bill of Lading</label>
                            <input type="text" id="bl_no" name="bl_no" value="{{ old('bl_no') }}" placeholder="BL-56789" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @error('bl_no') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2 space-y-1">
                            <label for="notes" class="text-sm font-medium text-gray-700">Notes</label>
                            <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Catatan atau instruksi khusus">{{ old('notes') }}</textarea>
                            @error('notes') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <!-- Material & Part Lines -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-xs font-semibold text-gray-500 tracking-wide uppercase">Material & Part Lines</h3>
                            <p class="text-sm text-gray-600">Kelompokkan part internal berdasarkan jenis material (contoh: SPHC / PO STEEL IN COIL).</p>
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                            <button type="button" id="refresh-parts" class="inline-flex w-full items-center justify-center gap-2 px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 rounded-md border border-slate-200 hover:bg-slate-200 disabled:opacity-50 disabled:cursor-not-allowed transition sm:w-auto" disabled>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 9.75a7.5 7.5 0 0 1 13.208-3.464M19.5 14.25a7.5 7.5 0 0 1-13.208 3.464M4.5 4.5v5.25H9.75M19.5 19.5v-5.25H14.25" />
                                </svg>
                                <span data-label>Sync Part Catalog</span>
                            </button>
                            <button type="button" id="add-material-group" class="inline-flex w-full items-center justify-center px-3 py-2 bg-blue-600 text-white text-xs rounded-md hover:bg-blue-700 transition-all duration-200 shadow-sm sm:w-auto">+ Material Group</button>
                        </div>
                    </div>

                    <div id="material-groups" class="space-y-6"></div>

                    @error('items') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        Save Departure
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const partApiBase = @json(url('/vendors'));
        const partsCache = {};
        const vendorsData = @json($vendors->map(fn($v) => ['id' => $v->id, 'name' => $v->vendor_name])->values());
        const hasOldInput = @json(!empty(session()->getOldInput()));
        const draftStorageKey = 'arrival-form-draft';
        let isRestoringDraft = false;
        let draftSaveTimeout;

        const vendorInput = document.getElementById('vendor_name');
        const vendorIdInput = document.getElementById('vendor_id');
        const suggestionsBox = document.getElementById('vendor-suggestions');
        const groupsContainer = document.getElementById('material-groups');
        const addGroupBtn = document.getElementById('add-material-group');
        const refreshPartsBtn = document.getElementById('refresh-parts');
        const existingItems = @json(old('items', []));
        const refreshBtnLabel = refreshPartsBtn?.querySelector('[data-label]');
        const refreshBtnDefaultText = refreshBtnLabel?.textContent || 'Sync Part Catalog';

        const draftData = !hasOldInput ? loadDraftData() : null;
        const draftGroups = draftData?.groups?.length ? draftData.groups : [];

        let groupIndex = 0;
        let rowIndex = 0;

        function updateRefreshButtonState() {
            if (!refreshPartsBtn) return;
            refreshPartsBtn.disabled = !vendorIdInput.value;
        }

        function loadDraftData() {
            try {
                const raw = localStorage.getItem(draftStorageKey);
                return raw ? JSON.parse(raw) : null;
            } catch (error) {
                console.warn('Failed to load draft data', error);
                return null;
            }
        }

        function clearDraftData() {
            localStorage.removeItem(draftStorageKey);
        }

        function collectFormFields() {
            if (!form) return {};
            const data = {};
            Array.from(form.elements).forEach(el => {
                if (!el.name || el.name === '_token' || el.name.startsWith('items[')) return;
                if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
                data[el.name] = el.value;
            });
            return data;
        }

        function collectGroupDefinitions() {
            return Array.from(document.querySelectorAll('.material-group')).map(groupEl => {
                const title = groupEl.querySelector('.material-title')?.value || '';
                const rows = Array.from(groupEl.querySelectorAll('.line-row')).map(row => ({
                    part_id: row.querySelector('.part-select')?.value || '',
                    size: row.querySelector('.input-size')?.value || '',
                    qty_bundle: row.querySelector('.qty-bundle')?.value || '',
                    unit_bundle: row.querySelector('.input-unit-bundle')?.value || '',
                    qty_goods: row.querySelector('.qty-goods')?.value || '',
                    weight_nett: row.querySelector('.weight-nett')?.value || '',
                    unit_weight: row.querySelector('.input-unit-weight')?.value || '',
                    weight_gross: row.querySelector('.weight-gross')?.value || '',
                    price: row.querySelector('.price')?.value || '',
                    notes: row.querySelector('input[name*="[notes]"]')?.value || '',
                    material_group: title,
                }));
                return { title, rows };
            });
        }

        function saveDraft() {
            if (isRestoringDraft) return;
            const payload = {
                fields: collectFormFields(),
                groups: collectGroupDefinitions(),
            };
            try {
                localStorage.setItem(draftStorageKey, JSON.stringify(payload));
            } catch (error) {
                console.warn('Failed to save draft', error);
            }
        }

        function requestSaveDraft() {
            if (isRestoringDraft) return;
            clearTimeout(draftSaveTimeout);
            draftSaveTimeout = setTimeout(saveDraft, 400);
        }

        function applyDraftFields(fields) {
            if (hasOldInput || !fields) return;
            Object.entries(fields).forEach(([name, value]) => {
                const elements = document.getElementsByName(name);
                if (!elements.length) return;
                Array.from(elements).forEach(el => {
                    if (el.name.startsWith('items[') || el.name === '_token') return;
                    if (el.type === 'checkbox' || el.type === 'radio') {
                        el.checked = el.value === value;
                    } else {
                        el.value = value;
                    }
                });
            });
        }

        vendorInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            if (query.length === 0) {
                suggestionsBox.classList.add('hidden');
                vendorIdInput.value = '';
                resetGroups([]);
                updateRefreshButtonState();
                requestSaveDraft();
                return;
            }
            const matches = vendorsData.filter(v => v.name.toLowerCase().includes(query));
            if (matches.length > 0) {
                suggestionsBox.innerHTML = matches.map(v => {
                    const name = v.name;
                    const lowerName = name.toLowerCase();
                    const index = lowerName.indexOf(query);
                    let highlighted = name;
                    if (index !== -1) {
                        highlighted = name.substring(0, index)
                            + '<span class="font-semibold text-blue-600">'
                            + name.substring(index, index + query.length)
                            + '</span>'
                            + name.substring(index + query.length);
                    }
                    return `<div class="px-4 py-2 cursor-pointer hover:bg-blue-50 border-b border-gray-100 last:border-0 transition-colors" data-id="${v.id}" data-name="${name}">
                        ${highlighted}
                    </div>`;
                }).join('');
                suggestionsBox.classList.remove('hidden');
            } else {
                suggestionsBox.innerHTML = '<div class="px-4 py-2 text-gray-500 text-sm italic">No vendors found</div>';
                suggestionsBox.classList.remove('hidden');
            }
        });

        suggestionsBox.addEventListener('click', async function (e) {
            const item = e.target.closest('[data-id]');
            if (!item) return;
            vendorInput.value = item.dataset.name;
            vendorIdInput.value = item.dataset.id;
            suggestionsBox.classList.add('hidden');
            updateRefreshButtonState();
            await loadParts(item.dataset.id, true);
            resetGroups([]);
            requestSaveDraft();
        });

        document.addEventListener('click', function (e) {
            if (!vendorInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.classList.add('hidden');
            }
        });

        vendorInput.addEventListener('focus', function () {
            if (this.value.trim().length > 0) {
                this.dispatchEvent(new Event('input'));
            }
        });

        function buildPartOptions(vendorId, partId = null) {
            if (!vendorId || !partsCache[vendorId]) {
                return '<option value="">Select vendor first</option>';
            }
            const options = partsCache[vendorId]
                .map(p => {
                    const displayName = p.part_name_gci || p.part_no;
                    const detail = p.part_no ? `(${p.part_no})` : '';
                    return `<option value="${p.id}" ${String(p.id) === String(partId) ? 'selected' : ''}>${displayName} ${detail}</option>`;
                })
                .join('');
            return `<option value="">Select Part Number</option>${options}`;
        }

        function rebuildPartSelects(vendorId) {
            document.querySelectorAll('.part-select').forEach(select => {
                const currentValue = select.value;
                select.innerHTML = buildPartOptions(vendorId, currentValue);
            });
        }

        function updateTotal(row) {
            const qty = parseFloat(row.querySelector('.qty-goods')?.value || 0);
            const price = parseFloat(row.querySelector('.price')?.value || 0);
            row.querySelector('.total').value = (qty * price).toFixed(2);
        }

        function normalizeDecimalInput(input) {
            if (!input) return;
            input.addEventListener('input', () => {
                input.value = input.value.replace(/,/g, '.');
            });
        }

        function guessUnitBundle(partData) {
            const name = (partData?.part_name_vendor || '').toLowerCase();
            if (name.includes('coil')) return 'Coil';
            if (name.includes('sheet')) return 'Sheet';
            return 'Pallet';
        }

        function guessUnitWeight() {
            return 'KGM';
        }

        function findPart(vendorId, partId) {
            const list = partsCache[vendorId] || [];
            return list.find(p => String(p.id) === String(partId));
        }

        function applyPartDefaults(row, vendorId, partId) {
            const partData = findPart(vendorId, partId);
            if (!partData) return;
            const sizeInput = row.querySelector('.input-size');
            if (sizeInput && !sizeInput.value) sizeInput.value = partData.size || partData.register_no || '';
            const unitBundleSelect = row.querySelector('.input-unit-bundle');
            if (unitBundleSelect && !unitBundleSelect.value) unitBundleSelect.value = guessUnitBundle(partData);
            const unitWeightSelect = row.querySelector('.input-unit-weight');
            if (unitWeightSelect && !unitWeightSelect.value) unitWeightSelect.value = guessUnitWeight(partData);

            const groupEl = row.closest('.material-group');
            if (groupEl) {
                const titleInput = groupEl.querySelector('.material-title');
                if (titleInput && !titleInput.value.trim() && partData.part_name_vendor) {
                    titleInput.value = partData.part_name_vendor;
                    syncGroupTitle(groupEl);
                }
            }
        }

        function syncGroupTitle(groupEl) {
            const title = groupEl.querySelector('.material-title')?.value?.trim() || '';
            groupEl.querySelectorAll('.material-group-field').forEach(field => field.value = title);
        }

        function addRowToGroup(groupEl, existing = null) {
            const vendorId = vendorIdInput.value;
            const rowsContainer = groupEl.querySelector('.group-rows');
            const row = document.createElement('div');
            row.className = 'line-row grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-start border border-gray-200 rounded-lg p-4 lg:bg-transparent lg:border-0 lg:border-b lg:rounded-none lg:p-0 lg:pb-3';
            const guessedBundle = existing?.unit_bundle ?? null;
            const guessedWeight = existing?.unit_weight ?? null;
            row.innerHTML = `
        <div class="col-span-1 sm:col-span-1 lg:col-span-2">
            <label class="lg:hidden text-xs font-semibold text-gray-500 mb-1 block">Size</label>
            <input type="text" name="items[${rowIndex}][size]" class="input-size w-full rounded-md border-gray-300 text-sm" placeholder="1.00 x 200.0 x C" value="${existing?.size ?? ''}">
        </div>
        <div class="col-span-1 sm:col-span-1 lg:col-span-3">
            <label class="lg:hidden text-xs font-semibold text-gray-500 mb-1 block">Part Number</label>
            <select name="items[${rowIndex}][part_id]" class="part-select block w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" ${vendorId ? '' : 'disabled'} required>
                ${buildPartOptions(vendorId, existing?.part_id)}
            </select>
        </div>
                <div class="col-span-1 lg:col-span-1">
                    <label class="lg:hidden text-xs font-semibold text-gray-500 mb-1 block">Qty Bundle</label>
                    <input type="number" name="items[${rowIndex}][qty_bundle]" class="qty-bundle w-full rounded-md border-gray-300 text-sm" value="${existing?.qty_bundle ?? 0}" min="0" placeholder="0">
                </div>
                <div class="col-span-1 lg:col-span-1">
                    <label class="lg:hidden text-xs font-semibold text-gray-500 mb-1 block">Unit Bundle</label>
                    <select name="items[${rowIndex}][unit_bundle]" class="input-unit-bundle w-full rounded-md border-gray-300 text-sm">
                        <option value="Coil" ${guessedBundle === 'Coil' ? 'selected' : ''}>Coil</option>
                        <option value="Sheet" ${guessedBundle === 'Sheet' ? 'selected' : ''}>Sheet</option>
                        <option value="Pallet" ${(guessedBundle === 'Pallet' || !guessedBundle) ? 'selected' : ''}>Pallet</option>
                        <option value="Bundle" ${guessedBundle === 'Bundle' ? 'selected' : ''}>Bundle</option>
                        <option value="Pcs" ${guessedBundle === 'Pcs' ? 'selected' : ''}>Pcs</option>
                        <option value="Set" ${guessedBundle === 'Set' ? 'selected' : ''}>Set</option>
                        <option value="Box" ${guessedBundle === 'Box' ? 'selected' : ''}>Box</option>
                    </select>
                </div>
                <div class="col-span-1 lg:col-span-1">
                    <label class="lg:hidden text-xs font-semibold text-gray-500 mb-1 block">Qty Goods</label>
                    <input type="number" name="items[${rowIndex}][qty_goods]" class="qty-goods w-full rounded-md border-gray-300 text-sm" value="${existing?.qty_goods ?? 0}" min="0" required>
                </div>
                <div class="col-span-1 lg:col-span-1">
                    <label class="lg:hidden text-xs font-semibold text-gray-500 mb-1 block">Nett Wt</label>
                    <input type="number" step="0.01" name="items[${rowIndex}][weight_nett]" class="weight-nett w-full rounded-md border-gray-300 text-sm" value="${existing?.weight_nett ?? 0}" min="0" required>
                </div>
                <div class="col-span-1 lg:col-span-1">
                    <label class="lg:hidden text-xs font-semibold text-gray-500 mb-1 block">Unit Wt</label>
                    <select name="items[${rowIndex}][unit_weight]" class="input-unit-weight w-full rounded-md border-gray-300 text-sm">
                        <option value="KGM" ${(guessedWeight === 'KGM' || !guessedWeight) ? 'selected' : ''}>KGM</option>
                        <option value="KG" ${guessedWeight === 'KG' ? 'selected' : ''}>KG</option>
                        <option value="Sheet" ${guessedWeight === 'Sheet' ? 'selected' : ''}>Sheet</option>
                        <option value="Ton" ${guessedWeight === 'Ton' ? 'selected' : ''}>Ton</option>
                    </select>
                </div>
                <div class="col-span-1 lg:col-span-1">
                    <label class="lg:hidden text-xs font-semibold text-gray-500 mb-1 block">Gross Wt</label>
                    <input type="number" step="0.01" name="items[${rowIndex}][weight_gross]" class="weight-gross w-full rounded-md border-gray-300 text-sm" value="${existing?.weight_gross ?? 0}" min="0" required>
                </div>
                <div class="col-span-1 lg:col-span-1">
                    <label class="lg:hidden text-xs font-semibold text-gray-500 mb-1 block">Price</label>
                    <input type="number" step="0.01" name="items[${rowIndex}][price]" class="price w-full rounded-md border-gray-300 text-sm" value="${existing?.price ?? 0}" min="0" required>
                </div>
                <div class="col-span-1 sm:col-span-2 lg:col-span-1">
                    <label class="lg:hidden text-xs font-semibold text-gray-500 mb-1 block">Total</label>
                    <input type="text" class="total w-full rounded-md border-gray-200 bg-gray-50 text-sm" value="0.00" readonly>
                    <input type="hidden" class="material-group-field" name="items[${rowIndex}][material_group]" value="${groupEl.querySelector('.material-title')?.value?.trim() || ''}">
                    <input type="hidden" name="items[${rowIndex}][notes]" value="${existing?.notes ?? ''}">
                </div>
                <div class="col-span-1 sm:col-span-2 lg:col-span-1 flex items-center justify-start lg:justify-center">
                    <button type="button" class="remove-line text-red-600 hover:text-red-800 text-xs whitespace-nowrap">Remove</button>
                </div>
            `;
            rowsContainer.appendChild(row);
            rowIndex++;

            const qtyField = row.querySelector('.qty-goods');
            const priceField = row.querySelector('.price');
            [qtyField, priceField].forEach(field => {
                normalizeDecimalInput(field);
                field.addEventListener('input', () => updateTotal(row));
            });
            updateTotal(row);

            const partSelect = row.querySelector('.part-select');
            partSelect.addEventListener('change', () => applyPartDefaults(row, vendorIdInput.value, partSelect.value));
            if (existing?.part_id) {
                partSelect.value = existing.part_id;
                applyPartDefaults(row, vendorIdInput.value, existing.part_id);
            }

            row.querySelector('.remove-line').addEventListener('click', () => {
                row.remove();
                ensureAtLeastOneRow(groupEl);
                requestSaveDraft();
            });

            requestSaveDraft();
        }

        function ensureAtLeastOneRow(groupEl) {
            const rowsContainer = groupEl.querySelector('.group-rows');
            if (rowsContainer.children.length === 0) {
                addRowToGroup(groupEl);
            }
        }

        function createGroup({ title = '', rows = [] } = {}) {
            const groupEl = document.createElement('div');
            groupEl.className = 'material-group border border-gray-200 rounded-lg shadow-sm';
            groupEl.innerHTML = `
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between border-b border-gray-100 bg-gray-50 p-4 rounded-t-lg">
                    <div class="w-full">
                        <label class="text-sm font-semibold text-gray-700">Jenis Material / Part Name Vendor</label>
                        <input type="text" class="material-title mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="SPHC / PO STEEL IN COIL" value="${title}">
                        <p class="text-xs text-gray-500 mt-1">Nama material vendor yang akan menjadi judul tebal di invoice.</p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                        <button type="button" class="add-part-line inline-flex w-full items-center justify-center px-3 py-2 bg-blue-600 text-white text-xs rounded-md shadow-sm hover:bg-blue-700 sm:w-auto">+ Part Line</button>
                        <button type="button" class="remove-group text-xs text-red-600 hover:text-red-700">Remove Group</button>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="hidden lg:grid lg:grid-cols-12 text-xs font-semibold text-gray-500 bg-gray-50 rounded-md px-3 py-2">
                        <div class="lg:col-span-2">Size</div>
                        <div class="lg:col-span-3">Part Number</div>
                        <div class="lg:col-span-1">Qty Bundle</div>
                        <div class="lg:col-span-1">Unit Bundle</div>
                        <div class="lg:col-span-1">Qty Goods</div>
                        <div class="lg:col-span-1">Nett Wt</div>
                        <div class="lg:col-span-1">Unit Wt</div>
                        <div class="lg:col-span-1">Gross Wt</div>
                        <div class="lg:col-span-1">Price</div>
                        <div class="lg:col-span-1">Total</div>
                        <div class="lg:col-span-1 text-right">Action</div>
                    </div>
                    <div class="group-rows space-y-3"></div>
                </div>
            `;

            const addLineBtn = groupEl.querySelector('.add-part-line');
            addLineBtn.addEventListener('click', () => {
                addRowToGroup(groupEl);
                requestSaveDraft();
            });

            const removeGroupBtn = groupEl.querySelector('.remove-group');
            removeGroupBtn.addEventListener('click', () => {
                if (groupsContainer.children.length === 1) {
                    alert('Minimal satu grup material diperlukan.');
                    return;
                }
                groupEl.remove();
                requestSaveDraft();
            });

            groupsContainer.appendChild(groupEl);

            const titleInput = groupEl.querySelector('.material-title');
            titleInput.addEventListener('input', () => {
                syncGroupTitle(groupEl);
                requestSaveDraft();
            });

            if (rows.length) {
                rows.forEach(row => addRowToGroup(groupEl, row));
            } else {
                addRowToGroup(groupEl);
            }

            syncGroupTitle(groupEl);
            if (!isRestoringDraft) requestSaveDraft();
        }

        function resetGroups(groupDefinitions = []) {
            groupsContainer.innerHTML = '';
            groupIndex = 0;
            rowIndex = 0;
            if (!groupDefinitions.length) {
                createGroup();
            } else {
                groupDefinitions.forEach(group => createGroup(group));
            }
            if (!isRestoringDraft) requestSaveDraft();
        }

        async function loadParts(vendorId, force = false) {
            if (!vendorId) return [];
            if (!force && partsCache[vendorId]) return partsCache[vendorId];
            const response = await fetch(`${partApiBase}/${vendorId}/parts`);
            if (!response.ok) return [];
            const data = await response.json();
            partsCache[vendorId] = data;
            return data;
        }

        addGroupBtn.addEventListener('click', () => {
            createGroup();
            requestSaveDraft();
        });

        document.addEventListener('DOMContentLoaded', async () => {
            if (draftData?.fields && !hasOldInput) {
                isRestoringDraft = true;
                applyDraftFields(draftData.fields);
                isRestoringDraft = false;
            }

            const vendorId = vendorIdInput.value;
            if (vendorId) {
                await loadParts(vendorId);
            }

            if (existingItems.length) {
                const grouped = existingItems.reduce((acc, item) => {
                    const key = item.material_group || '';
                    if (!acc[key]) acc[key] = [];
                    acc[key].push(item);
                    return acc;
                }, {});
                const definitions = Object.entries(grouped).map(([title, rows]) => ({ title, rows }));
                isRestoringDraft = true;
                resetGroups(definitions);
                isRestoringDraft = false;
            } else if (draftGroups.length) {
                isRestoringDraft = true;
                resetGroups(draftGroups);
                isRestoringDraft = false;
            } else {
                resetGroups([]);
            }
            updateRefreshButtonState();
            requestSaveDraft();
        });

        const form = document.getElementById('arrival-form');
        form.addEventListener('submit', () => {
            document.querySelectorAll('.group-rows .line-row').forEach(row => {
                const qtyGoods = parseFloat(row.querySelector('.qty-goods')?.value || 0);
                if (qtyGoods === 0) {
                    row.remove();
                }
            });
            clearDraftData();
        });

        form.addEventListener('input', requestSaveDraft);
        form.addEventListener('change', requestSaveDraft);

        if (refreshPartsBtn) {
            refreshPartsBtn.addEventListener('click', async () => {
                const vendorId = vendorIdInput.value;
                if (!vendorId) {
                    alert('Pilih vendor terlebih dahulu untuk sinkronisasi part.');
                    return;
                }
                refreshPartsBtn.disabled = true;
                if (refreshBtnLabel) refreshBtnLabel.textContent = 'Syncing...';
                await loadParts(vendorId, true);
                rebuildPartSelects(vendorId);
                if (refreshBtnLabel) refreshBtnLabel.textContent = refreshBtnDefaultText;
                refreshPartsBtn.disabled = false;
                requestSaveDraft();
            });
        }
    </script>
</x-app-layout>
