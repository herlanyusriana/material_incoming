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
	                        <div class="md:col-span-2 space-y-1">
	                            <label for="hs_codes" class="text-sm font-medium text-gray-700">HS Code (bisa lebih dari 1)</label>
	                            <textarea id="hs_codes" name="hs_codes" rows="2" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Pisahkan dengan enter atau koma (contoh: 7208.90.00, 7210.70.00)">{{ old('hs_codes', old('hs_code')) }}</textarea>
	                            <p class="text-xs text-gray-500">Opsional. Kalau kosong, sistem bisa ambil dari HS Code per part.</p>
	                            @error('hs_codes') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
	                            @error('hs_code') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
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

	                        <div class="space-y-2">
	                            <div class="flex items-center justify-between gap-3">
	                                <div>
	                                    <label class="text-sm font-medium text-gray-700">Containers & Seal Code</label>
	                                    <p class="text-xs text-gray-500">1 container = 1 seal code. 1 invoice bisa punya banyak container.</p>
	                                </div>
	                            </div>

	                            <div id="container-rows" class="space-y-3"></div>

	                            @error('containers') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
	                            @error('containers.*.container_no') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
	                            @error('containers.*.seal_code') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
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
	                        <div class="space-y-1">
	                            <label for="price_term" class="text-sm font-medium text-gray-700">Price Term</label>
	                            <input type="text" id="price_term" name="price_term" value="{{ old('price_term') }}" placeholder="FOB / CIF / EXW" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
	                            @error('price_term') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
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
        const refreshPartsBtn = document.getElementById('refresh-parts');
        const containerRowsEl = document.getElementById('container-rows');
        const existingItems = @json(old('items', []));
        const refreshBtnLabel = refreshPartsBtn?.querySelector('[data-label]');
        const refreshBtnDefaultText = refreshBtnLabel?.textContent || 'Sync Part Catalog';

        const existingContainers = @json(old('containers', []));
        const legacyContainerNumbers = @json(old('container_numbers'));
        const legacySealCode = @json(old('seal_code'));

        const draftData = !hasOldInput ? loadDraftData() : null;
        const draftGroups = draftData?.groups?.length ? draftData.groups : [];

        let groupIndex = 0;
        let rowIndex = 0;
        let containerIndex = 0;

        function parseLegacyContainers(containerNumbers, sealCode) {
            const rows = [];
            const defaultSeal = String(sealCode ?? '').trim();
            const lines = String(containerNumbers ?? '').split(/\r\n|\r|\n/);
            for (const line of lines) {
                const raw = String(line ?? '').trim();
                if (!raw) continue;
                const parts = raw.split(/\s+/);
                const containerNo = (parts[0] ?? '').trim();
                const seal = (parts[1] ?? defaultSeal).trim();
                if (!containerNo) continue;
                rows.push({ container_no: containerNo, seal_code: seal });
            }
            return rows;
        }

	        function addContainerRow(existing = null) {
	            if (!containerRowsEl) return;
	            const idx = containerIndex++;
	            const containerNo = escapeHtml(existing?.container_no ?? '');
	            const seal = escapeHtml(existing?.seal_code ?? '');

            const row = document.createElement('div');
            row.className = 'rounded-xl border border-slate-200 bg-white p-4 shadow-sm';
            row.innerHTML = `
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Container No</label>
                        <input type="text" name="containers[${idx}][container_no]" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. SKLU1809368" value="${containerNo}" required>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Seal Code</label>
                        <input type="text" name="containers[${idx}][seal_code]" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. HUPH019101" value="${seal}" required>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap justify-end gap-2">
                    <button type="button" class="add-container inline-flex items-center justify-center gap-2 rounded-lg border border-blue-200 px-3 py-2 text-blue-700 hover:bg-blue-50 text-xs font-semibold whitespace-nowrap">
                        Add Container
                    </button>
                    <button type="button" class="remove-container inline-flex items-center justify-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-red-700 hover:bg-red-50 text-xs font-semibold whitespace-nowrap">
                        Hapus Container
                    </button>
                </div>
            `;

                row.querySelector('.add-container')?.addEventListener('click', () => {
                    const newRow = addContainerRow();
                    requestSaveDraft();
                    setTimeout(() => {
                        newRow?.querySelector('input[name*=\"[container_no]\"]')?.focus();
                    }, 0);
                });

	            row.querySelector('.remove-container')?.addEventListener('click', () => {
	                row.remove();
	                if (containerRowsEl.children.length === 0) {
	                    addContainerRow();
	                }
	                requestSaveDraft();
	            });

	            row.querySelectorAll('input').forEach((input) => {
	                input.addEventListener('input', () => requestSaveDraft());
	            });

	            containerRowsEl.appendChild(row);
                return row;
	        }

	        function initContainerRows() {
	            if (!containerRowsEl) return;
	            containerRowsEl.innerHTML = '';
	            containerIndex = 0;

            const rows = Array.isArray(existingContainers) && existingContainers.length
                ? existingContainers
                : parseLegacyContainers(legacyContainerNumbers, legacySealCode);

	            if (rows.length) {
	                rows.forEach(r => addContainerRow(r));
	            } else {
	                addContainerRow();
	            }
	        }

	        function applyDraftContainers(fields) {
	            if (hasOldInput || !fields || !containerRowsEl) return;
	            const indices = Object.keys(fields)
	                .map((key) => {
	                    const match = key.match(/^containers\[(\d+)\]\[(container_no|seal_code)\]$/);
	                    return match ? Number(match[1]) : null;
	                })
	                .filter((value) => Number.isInteger(value));

	            const uniqueIndices = Array.from(new Set(indices)).sort((a, b) => a - b);
	            if (!uniqueIndices.length) return;

	            containerRowsEl.innerHTML = '';
	            containerIndex = 0;
	            uniqueIndices.forEach((idx) => {
	                addContainerRow({
	                    container_no: fields[`containers[${idx}][container_no]`] ?? '',
	                    seal_code: fields[`containers[${idx}][seal_code]`] ?? '',
	                });
	            });

	            if (containerRowsEl.children.length === 0) {
	                addContainerRow();
	            }
	        }

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
	                const title = getGroupTitle(groupEl);
	                const rows = Array.from(groupEl.querySelectorAll('.line-row')).map(row => ({
	                    part_id: row.querySelector('.part-select')?.value || '',
	                    size: row.querySelector('.input-size')?.value || '',
                    qty_bundle: row.querySelector('.qty-bundle')?.value || '',
                    unit_bundle: row.querySelector('.input-unit-bundle')?.value || '',
                    qty_goods: row.querySelector('.qty-goods')?.value || '',
                    unit_goods: row.querySelector('.input-unit-goods')?.value || '',
                    weight_nett: row.querySelector('.weight-nett')?.value || '',
                    unit_weight: row.querySelector('.input-unit-weight')?.value || '',
                    weight_gross: row.querySelector('.weight-gross')?.value || '',
                    total_amount: row.querySelector('.total-input')?.value || '',
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
	            applyDraftContainers(fields);
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
	                rebuildMaterialTitleSelects(null);
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

	        vendorInput.addEventListener('blur', async function () {
	            if (vendorIdInput.value) return;
	            const typed = this.value.toLowerCase().trim();
	            if (!typed) return;
	            const exactMatches = vendorsData.filter(v => v.name.toLowerCase().trim() === typed);
	            if (exactMatches.length !== 1) return;
	            const match = exactMatches[0];
	            vendorIdInput.value = match.id;
	            updateRefreshButtonState();
	            await loadParts(match.id, true);
	            resetGroups([]);
	            requestSaveDraft();
	        });

	        function escapeHtml(value) {
	            return String(value ?? '')
	                .replace(/&/g, '&amp;')
	                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function buildPartOptions(vendorId, groupTitle = '', partId = null) {
            if (!vendorId || !partsCache[vendorId]) {
                return '<option value="">Select vendor first</option>';
            }
            const normalizedTitle = String(groupTitle || '').trim().toLowerCase();
            const sourceList = partsCache[vendorId] || [];
            const filteredList = normalizedTitle
                ? sourceList.filter((p) => String(p.part_name_vendor || '').trim().toLowerCase() === normalizedTitle)
                : sourceList;
            const options = filteredList
                .map(p => {
                    const displaySize = (p.size || p.register_no || '').trim();
                    const label = displaySize !== '' ? displaySize : (p.part_name_vendor || p.part_name_gci || p.part_no || '');
                    return `<option value="${escapeHtml(p.id)}" ${String(p.id) === String(partId) ? 'selected' : ''}>${escapeHtml(label)}</option>`;
                })
                .join('');
            if (!options) {
                return `<option value="">No size for selected group</option>`;
            }
            return `<option value="">Select Size</option>${options}`;
        }

	        function getUniqueMaterialTitles(vendorId) {
	            if (!vendorId || !partsCache[vendorId]) return [];
	            return Array.from(new Set(
	                partsCache[vendorId]
	                    .map(p => (p.part_name_vendor || '').trim())
	                    .filter(Boolean)
	            )).sort((a, b) => a.localeCompare(b));
	        }

	        function buildMaterialTitleOptionsHtml(vendorId) {
	            if (!vendorId || !partsCache[vendorId]) {
	                return '<option value="">Pilih vendor dulu</option>';
	            }
	            const titles = getUniqueMaterialTitles(vendorId);
	            const options = titles.map(title => `<option value="${escapeHtml(title)}">${escapeHtml(title)}</option>`).join('');
	            return [
	                '<option value="">Pilih Jenis Material / Part Name Vendor</option>',
	                options,
	                '<option value="__custom__">Lainnya (ketik manual)...</option>',
	            ].join('');
	        }

	        function getGroupTitle(groupEl) {
	            const select = groupEl.querySelector('.material-title-select');
	            const custom = groupEl.querySelector('.material-title-custom');
	            if (!select) return '';
	            if (select.value === '__custom__') return (custom?.value || '').trim();
	            return (select.value || '').trim();
	        }

	        function setGroupTitle(groupEl, title) {
	            const vendorId = vendorIdInput.value;
	            const select = groupEl.querySelector('.material-title-select');
	            const custom = groupEl.querySelector('.material-title-custom');
	            if (!select) return;

	            const normalized = (title || '').trim();
	            const titles = getUniqueMaterialTitles(vendorId);
	            select.innerHTML = buildMaterialTitleOptionsHtml(vendorId);
	            select.disabled = !vendorId;

	            if (!normalized) {
	                select.value = '';
	                if (custom) {
	                    custom.value = '';
	                    custom.classList.add('hidden');
	                }
	                return;
	            }

	            if (titles.includes(normalized)) {
	                select.value = normalized;
	                if (custom) {
	                    custom.value = '';
	                    custom.classList.add('hidden');
	                }
	                return;
	            }

	            select.value = '__custom__';
	            if (custom) {
	                custom.value = normalized;
	                custom.classList.remove('hidden');
	            }
	        }

	        function rebuildMaterialTitleSelects(vendorId) {
	            document.querySelectorAll('.material-group').forEach(groupEl => {
	                const select = groupEl.querySelector('.material-title-select');
	                const custom = groupEl.querySelector('.material-title-custom');
	                if (!select) return;
	                const current = getGroupTitle(groupEl);
	                setGroupTitle(groupEl, current);
	                if (custom && select.value !== '__custom__') custom.classList.add('hidden');
	                if (custom && select.value === '__custom__') custom.classList.remove('hidden');
	                syncGroupTitle(groupEl);
	            });
	        }

	        function rebuildPartSelects(vendorId) {
	            rebuildMaterialTitleSelects(vendorId);
	            document.querySelectorAll('.material-group').forEach(groupEl => {
	                refreshGroupPartOptions(groupEl);
	            });
	        }

        function updateTotal(row) {
            const qtyEl = row.querySelector('.qty-goods');
            const totalEl = row.querySelector('.total-input');
            const qtyRaw = (qtyEl?.value ?? '').trim();
            const totalRaw = (totalEl?.value ?? '').trim();

            if (!qtyRaw || !totalRaw) {
                const hiddenPrice = row.querySelector('.price');
                if (hiddenPrice) hiddenPrice.value = '';
                const priceDisplay = row.querySelector('.price-display');
                if (priceDisplay) priceDisplay.value = '';
                return;
            }

	            const qty = parseInt(qtyRaw || '0', 10);
                const toCents = (value) => {
                    const raw = String(value ?? '').trim().replace(/,/g, '.');
                    if (!raw) return 0;
                    const parts = raw.split('.');
                    const whole = (parts[0] || '0').replace(/[^\d]/g, '') || '0';
                    const frac = ((parts[1] || '').replace(/[^\d]/g, '') + '00').slice(0, 2);
                    return (parseInt(whole, 10) * 100) + parseInt(frac, 10);
                };
                const formatMilli = (milli) => {
                    const neg = milli < 0;
                    milli = Math.abs(milli);
                    let s = String(milli);
                    if (s.length <= 3) s = s.padStart(4, '0');
                    let intPart = s.slice(0, -3).replace(/^0+/, '');
                    if (!intPart) intPart = '0';
                    const frac = s.slice(-3);
                    return (neg ? '-' : '') + intPart + '.' + frac;
                };

                const totalCents = toCents(totalRaw);
                const priceMilli = qty > 0 ? Math.floor((totalCents * 10) / qty) : 0;
                const priceText = formatMilli(priceMilli);

	            const hiddenPrice = row.querySelector('.price');
	            if (hiddenPrice) hiddenPrice.value = priceText;

	            const priceDisplay = row.querySelector('.price-display');
	            if (priceDisplay) priceDisplay.value = priceText;
	        }

        function installAutoPriceDelegation() {
            if (!groupsContainer) return;

            const handler = (event) => {
                const target = event.target;
                if (!(target instanceof HTMLInputElement)) return;
                if (!target.classList.contains('qty-goods') && !target.classList.contains('total-input')) return;
                const row = target.closest('.line-row');
                if (!row) return;
                updateTotal(row);
            };

            groupsContainer.addEventListener('input', handler);
            groupsContainer.addEventListener('change', handler);
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

        function guessUnitGoods(partData) {
            const name = (partData?.part_name_vendor || '').toLowerCase();
            const sizeText = String(partData?.size || partData?.register_no || '').toLowerCase();
            if (name.includes('coil') || sizeText.includes(' x c') || sizeText.endsWith('c')) return 'Coil';
            if (name.includes('sheet')) return 'Sheet';
            return 'Pcs';
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
	            if (sizeInput) sizeInput.value = partData.size || partData.register_no || '';
	            const partNoInput = row.querySelector('.input-part-no-gci');
	            if (partNoInput) partNoInput.value = partData.part_no || '';
	            const partNameInput = row.querySelector('.input-part-name-gci');
	            if (partNameInput) partNameInput.value = partData.part_name_gci || partData.part_name_vendor || '';
	            const unitBundleSelect = row.querySelector('.input-unit-bundle');
	            if (unitBundleSelect && !unitBundleSelect.value) unitBundleSelect.value = guessUnitBundle(partData);
	            const unitGoodsSelect = row.querySelector('.input-unit-goods');
	            if (unitGoodsSelect && !unitGoodsSelect.value) unitGoodsSelect.value = guessUnitGoods(partData);
	            const unitWeightSelect = row.querySelector('.input-unit-weight');
	            if (unitWeightSelect && !unitWeightSelect.value) unitWeightSelect.value = guessUnitWeight(partData);

	            const groupEl = row.closest('.material-group');
	            if (groupEl) {
	                if (!getGroupTitle(groupEl) && partData.part_name_vendor) {
	                    setGroupTitle(groupEl, partData.part_name_vendor);
	                    syncGroupTitle(groupEl);
	                }
	            }
	        }

	        function syncGroupTitle(groupEl) {
	            const title = getGroupTitle(groupEl);
	            groupEl.querySelectorAll('.material-group-field').forEach(field => field.value = title);
                refreshGroupPartOptions(groupEl);
	        }

        function refreshGroupPartOptions(groupEl) {
            const vendorId = vendorIdInput.value;
            const groupTitle = getGroupTitle(groupEl);
            const rows = groupEl.querySelectorAll('.line-row');
            rows.forEach((row) => {
                const select = row.querySelector('.part-select');
                if (!select) return;
                const current = select.value;
                select.innerHTML = buildPartOptions(vendorId, groupTitle, current);
                const stillValid = Array.from(select.options).some((o) => String(o.value) === String(current));
                if (!stillValid) {
                    select.value = '';
                    applyPartDefaults(row, vendorId, '');
                }
                select.disabled = !vendorId || select.options.length <= 1 || select.options[0].textContent === 'No size for selected group';
            });
        }

	        function addRowToGroup(groupEl, existing = null) {
	            const vendorId = vendorIdInput.value;
	            const rowsContainer = groupEl.querySelector('.group-rows');
	            const row = document.createElement('div');
	            row.className = 'line-row rounded-xl border border-slate-200 bg-white p-4 shadow-sm';
            const guessedBundle = existing?.unit_bundle ?? null;
            const guessedWeight = existing?.unit_weight ?? null;
            const guessedUnitGoods = existing?.unit_goods ?? null;
            const groupTitle = getGroupTitle(groupEl);
            const initialMaterialGroup = escapeHtml(groupTitle);
            const existingQty = Number(existing?.qty_goods ?? 0);
            const existingTotal = existing?.total_amount ?? (existing ? ((Number(existing?.price ?? 0) * existingQty) || 0) : '');
	            row.innerHTML = `
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h4 class="text-xs font-semibold tracking-wide text-slate-600 uppercase">Detail Part</h4>
                </div>

                <div class="space-y-3">
                    <div class="sm:flex sm:items-center sm:gap-4">
                        <label class="text-xs font-semibold text-slate-500 sm:w-44">Size</label>
                        <select name="items[${rowIndex}][part_id]" class="part-select mt-1 block w-full rounded-lg border-slate-300 bg-white text-sm focus:border-blue-500 focus:ring-blue-500 sm:mt-0 sm:flex-1" ${vendorId ? '' : 'disabled'} required>
                            ${buildPartOptions(vendorId, groupTitle, existing?.part_id)}
                        </select>
                    </div>

                    <input type="hidden" name="items[${rowIndex}][size]" class="input-size" value="${escapeHtml(existing?.size ?? '')}">

                    <div class="sm:flex sm:items-center sm:gap-4">
                        <label class="text-xs font-semibold text-slate-500 sm:w-44">Part No GCI</label>
                        <input type="text" class="input-part-no-gci mt-1 w-full rounded-lg border-slate-200 bg-white text-sm sm:mt-0 sm:flex-1" placeholder="Auto dari master part" value="" readonly>
                    </div>

                    <div class="sm:flex sm:items-center sm:gap-4">
                        <label class="text-xs font-semibold text-slate-500 sm:w-44">Part Name GCI</label>
                        <input type="text" class="input-part-name-gci mt-1 w-full rounded-lg border-slate-200 bg-white text-sm sm:mt-0 sm:flex-1" placeholder="Auto dari master part" value="" readonly>
                    </div>

                    <div class="sm:flex sm:items-center sm:gap-4">
                        <label class="text-xs font-semibold text-slate-500 sm:w-44">Qty Goods</label>
                        <input type="number" name="items[${rowIndex}][qty_goods]" class="qty-goods mt-1 w-full rounded-lg border-slate-300 bg-white text-sm sm:mt-0 sm:flex-1" value="${existing?.qty_goods ?? ''}" min="0" placeholder="Contoh: 10" required>
                    </div>

                    <div class="sm:flex sm:items-center sm:gap-4">
                        <label class="text-xs font-semibold text-slate-500 sm:w-44">Unit Code / Satuan Unit</label>
                        <select name="items[${rowIndex}][unit_goods]" class="input-unit-goods mt-1 w-full rounded-lg border-slate-300 bg-white text-sm sm:mt-0 sm:flex-1">
                            <option value="">Pilih satuan</option>
                            <option value="KGM" ${guessedUnitGoods === 'KGM' ? 'selected' : ''}>KGM</option>
                            <option value="Sheet" ${guessedUnitGoods === 'Sheet' ? 'selected' : ''}>Sheet</option>
                            <option value="Coil" ${guessedUnitGoods === 'Coil' ? 'selected' : ''}>Coil</option>
                            <option value="Pcs" ${guessedUnitGoods === 'Pcs' ? 'selected' : ''}>Pcs</option>
                            <option value="Set" ${guessedUnitGoods === 'Set' ? 'selected' : ''}>Set</option>
                            <option value="Box" ${guessedUnitGoods === 'Box' ? 'selected' : ''}>Box</option>
                            <option value="Bundle" ${guessedUnitGoods === 'Bundle' ? 'selected' : ''}>Bundle</option>
                            <option value="Pallet" ${guessedUnitGoods === 'Pallet' ? 'selected' : ''}>Pallet</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="mb-3 flex items-center justify-between">
                    <h4 class="text-xs font-semibold tracking-wide text-slate-600 uppercase">Detail Packaging</h4>
                </div>

                <div class="space-y-3">
                    <div class="sm:flex sm:items-center sm:gap-4">
                        <label class="text-xs font-semibold text-slate-500 sm:w-44">Jenis Package</label>
                        <select name="items[${rowIndex}][unit_bundle]" class="input-unit-bundle mt-1 w-full rounded-lg border-slate-300 bg-white text-sm sm:mt-0 sm:flex-1">
                            <option value="Coil" ${guessedBundle === 'Coil' ? 'selected' : ''}>Coil</option>
                            <option value="Sheet" ${guessedBundle === 'Sheet' ? 'selected' : ''}>Sheet</option>
                            <option value="Pallet" ${(guessedBundle === 'Pallet' || !guessedBundle) ? 'selected' : ''}>Pallet</option>
                            <option value="Bundle" ${guessedBundle === 'Bundle' ? 'selected' : ''}>Bundle</option>
                            <option value="Pcs" ${guessedBundle === 'Pcs' ? 'selected' : ''}>Pcs</option>
                            <option value="Set" ${guessedBundle === 'Set' ? 'selected' : ''}>Set</option>
                            <option value="Box" ${guessedBundle === 'Box' ? 'selected' : ''}>Box</option>
                        </select>
                    </div>

                    <div class="sm:flex sm:items-center sm:gap-4">
                        <label class="text-xs font-semibold text-slate-500 sm:w-44">Qty Package</label>
                        <input type="number" name="items[${rowIndex}][qty_bundle]" class="qty-bundle mt-1 w-full rounded-lg border-slate-300 bg-white text-sm sm:mt-0 sm:flex-1" value="${existing?.qty_bundle ?? ''}" min="0" placeholder="0">
                    </div>

	                    <div class="sm:flex sm:items-center sm:gap-4">
	                        <label class="text-xs font-semibold text-slate-500 sm:w-44">Net Weight (KGM)</label>
	                        <div class="mt-1 flex w-full items-center gap-2 sm:mt-0 sm:flex-1">
	                            <input type="hidden" name="items[${rowIndex}][unit_weight]" value="KGM">
                            <input type="text" inputmode="decimal" name="items[${rowIndex}][weight_nett]" class="weight-nett w-full rounded-lg border-slate-300 bg-white text-sm" value="${existing?.weight_nett ?? ''}" placeholder="0.00" required>
                            <span class="text-xs font-semibold text-slate-500 w-[56px] text-right">KGM</span>
                        </div>
                    </div>

                    <div class="sm:flex sm:items-center sm:gap-4">
                        <label class="text-xs font-semibold text-slate-500 sm:w-44">Gross Weight (KGM)</label>
                        <div class="mt-1 flex w-full items-center gap-2 sm:mt-0 sm:flex-1">
                            <input type="text" inputmode="decimal" name="items[${rowIndex}][weight_gross]" class="weight-gross w-full rounded-lg border-slate-300 bg-white text-sm" value="${existing?.weight_gross ?? ''}" placeholder="0.00" required>
                            <span class="text-xs font-semibold text-slate-500 w-[56px] text-right">KGM</span>
                        </div>
                    </div>

                    <div class="sm:flex sm:items-start sm:gap-4">
                        <label class="text-xs font-semibold text-slate-500 sm:w-44 sm:pt-2">Total Price</label>
                        <div class="mt-1 w-full sm:mt-0 sm:flex-1">
                            <input type="text" inputmode="decimal" name="items[${rowIndex}][total_amount]" class="total-input w-full rounded-lg border-blue-300 bg-white text-sm focus:border-blue-500 focus:ring-blue-500" value="${existingTotal}" placeholder="0.00" required>
                            <input type="hidden" name="items[${rowIndex}][price]" class="price" value="${existing?.price ?? ''}">
                            <div class="mt-1 text-[11px] text-slate-500">Price otomatis = Total / Qty</div>
	                        </div>
	                    </div>

	                    <div class="sm:flex sm:items-center sm:gap-4">
	                        <label class="text-xs font-semibold text-slate-500 sm:w-44">Price (auto)</label>
	                        <input type="text" class="price-display mt-1 w-full rounded-lg border-slate-200 bg-slate-100 text-sm sm:mt-0 sm:flex-1" value="" placeholder="0.000" readonly>
	                    </div>
	                </div>
	            </section>
	        </div>

        <div class="mt-4 border-t border-dashed border-slate-200 pt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="add-line inline-flex items-center justify-center gap-2 rounded-lg border border-blue-200 px-3 py-2 text-blue-700 hover:bg-blue-50 text-sm font-semibold whitespace-nowrap">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-blue-200 bg-white">
                        <span class="leading-none">+</span>
                    </span>
                    Part Line
                </button>
                <button type="button" class="add-group inline-flex items-center justify-center gap-2 rounded-lg border border-blue-200 px-3 py-2 text-blue-700 hover:bg-blue-50 text-sm font-semibold whitespace-nowrap">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-blue-200 bg-white">
                        <span class="leading-none">+</span>
                    </span>
                    Material Group
                </button>
                <button type="button" class="remove-line inline-flex items-center justify-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-red-700 hover:bg-red-50 text-sm font-semibold whitespace-nowrap">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-red-200 bg-white">
                        <span class="leading-none">−</span>
                    </span>
                    Hapus Baris
                </button>
            </div>

            <div>
                <input type="hidden" class="material-group-field" name="items[${rowIndex}][material_group]" value="${initialMaterialGroup}">
                <input type="hidden" name="items[${rowIndex}][notes]" value="${escapeHtml(existing?.notes ?? '')}">
            </div>
        </div>
            `;
	            rowsContainer.appendChild(row);
	            rowIndex++;

            row.querySelector('.add-line').addEventListener('click', () => {
                const newRow = addRowToGroup(groupEl);
                requestSaveDraft();
                setTimeout(() => {
                    newRow?.querySelector('.part-select')?.focus();
                }, 0);
            });

            row.querySelector('.add-group').addEventListener('click', () => {
                const newGroup = createGroup();
                requestSaveDraft();
                setTimeout(() => {
                    newGroup?.querySelector('.material-title-select')?.focus();
                }, 0);
            });

		            const qtyField = row.querySelector('.qty-goods');
		            const totalField = row.querySelector('.total-input');
		            const nettField = row.querySelector('.weight-nett');
		            const grossField = row.querySelector('.weight-gross');

	            [qtyField, totalField, nettField, grossField].forEach(field => {
	                normalizeDecimalInput(field);
	                if (field === qtyField || field === totalField) {
	                    field.addEventListener('input', () => updateTotal(row));
                        field.addEventListener('change', () => updateTotal(row));
	                }
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

            const totalFieldForEnter = row.querySelector('.total-input');
            if (totalFieldForEnter) {
                totalFieldForEnter.addEventListener('keydown', (e) => {
                    if (e.key !== 'Enter') return;
                    e.preventDefault();
                    const newRow = addRowToGroup(groupEl);
                    requestSaveDraft();
                    setTimeout(() => {
                        newRow?.querySelector('.part-select')?.focus();
                    }, 0);
                });
            }

            requestSaveDraft();
            return row;
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
	                        <select class="material-title-select mt-1 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
	                            <option value="">Pilih vendor dulu</option>
	                        </select>
	                        <input type="text" class="material-title-custom mt-2 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm hidden" placeholder="Ketik jenis material (manual)" value="">
	                        <p class="text-xs text-gray-500 mt-1">Dropdown ngambil dari <span class="font-semibold">Part Name Vendor</span> (sesuai vendor). Kalau tidak ada, pilih <span class="font-semibold">Lainnya</span> lalu ketik manual.</p>
	                    </div>
	                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
		                        <button type="button" class="remove-group text-xs text-red-600 hover:text-red-700">Remove Group</button>
		                    </div>
		                </div>
		                <div class="p-4 bg-white rounded-b-lg">
		                    <div class="group-rows space-y-4"></div>
		                </div>
	            `;

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

	            const titleSelect = groupEl.querySelector('.material-title-select');
	            const titleCustom = groupEl.querySelector('.material-title-custom');

	            if (titleSelect) {
	                titleSelect.addEventListener('change', () => {
	                    if (titleSelect.value === '__custom__') {
	                        titleCustom?.classList.remove('hidden');
	                        titleCustom?.focus();
	                    } else {
	                        titleCustom?.classList.add('hidden');
	                    }
	                    syncGroupTitle(groupEl);
	                    requestSaveDraft();
	                });
	            }

	            if (titleCustom) {
	                titleCustom.addEventListener('input', () => {
	                    syncGroupTitle(groupEl);
	                    requestSaveDraft();
	                });
	            }

            if (rows.length) {
                rows.forEach(row => addRowToGroup(groupEl, row));
            } else {
                addRowToGroup(groupEl);
            }

	            setGroupTitle(groupEl, title);
	            syncGroupTitle(groupEl);
	            if (!isRestoringDraft) requestSaveDraft();
                return groupEl;
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
	            rebuildMaterialTitleSelects(vendorId);
	            return data;
	        }

	        document.addEventListener('DOMContentLoaded', async () => {
                installAutoPriceDelegation();
	            initContainerRows();

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
