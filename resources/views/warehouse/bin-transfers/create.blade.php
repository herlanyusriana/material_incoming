<x-app-layout>
    <x-slot name="header">
        Warehouse • New Bin Transfer
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800 mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h2 class="text-xl font-bold text-slate-900">Transfer Material Between Bins</h2>
                    <p class="text-sm text-slate-600 mt-1">Move material from one warehouse location to another</p>
                </div>

                <form method="POST" action="{{ route('warehouse.bin-transfers.store') }}" class="p-6 space-y-6"
                    id="transfer-form">
                    @csrf

                    {{-- Part Selection --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Part <span class="text-red-500">*</span>
                        </label>
                        <input type="hidden" name="part_id" id="part_id" value="{{ old('part_id') }}" required>
                        <div class="relative">
                            <input type="text" id="part_search" autocomplete="off"
                                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Type part no / name (min 2 chars)">
                            <div id="part_suggestions"
                                class="absolute z-20 mt-1 w-full max-h-72 overflow-auto rounded-lg border border-slate-200 bg-white shadow-lg hidden">
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mt-1" id="part-locations-info">Select a part to see available
                            locations</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- From Location --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                From Location <span class="text-red-500">*</span>
                            </label>
                            <select name="from_location_code" id="from_location_code" required
                                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Select Source --</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->location_code }}" {{ old('from_location_code') == $location->location_code ? 'selected' : '' }}>
                                        {{ $location->location_code }}
                                        @if($location->zone) - {{ $location->zone }}@endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-500 mt-1" id="from-stock-info">Available: -</p>
                        </div>

                        {{-- To Location --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                To Location <span class="text-red-500">*</span>
                            </label>
                            <select name="to_location_code" id="to_location_code" required
                                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Select Destination --</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->location_code }}" {{ old('to_location_code') == $location->location_code ? 'selected' : '' }}>
                                        {{ $location->location_code }}
                                        @if($location->zone) - {{ $location->zone }}@endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-500 mt-1" id="to-stock-info">Current: -</p>
                        </div>
                    </div>

                    {{-- Quantity and Date --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Quantity <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="qty" id="qty" step="0.0001" min="0.0001" value="{{ old('qty') }}"
                                required
                                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter quantity to transfer">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Transfer Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="transfer_date"
                                value="{{ old('transfer_date', now()->format('Y-m-d')) }}" required
                                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Notes (Optional)
                        </label>
                        <textarea name="notes" rows="3"
                            class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Add any notes about this transfer...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <a href="{{ route('warehouse.bin-transfers.index') }}"
                            class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 font-semibold hover:bg-slate-50">
                            Cancel
                        </a>
                        <button type="submit" id="submit-btn"
                            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition-colors">
                            Transfer Material
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const partSelect = document.getElementById('part_id'); // hidden input
            const partSearch = document.getElementById('part_search');
            const partSuggestions = document.getElementById('part_suggestions');
            const fromLocationSelect = document.getElementById('from_location_code');
            const toLocationSelect = document.getElementById('to_location_code');
            const fromStockInfo = document.getElementById('from-stock-info');
            const toStockInfo = document.getElementById('to-stock-info');
            const partLocationsInfo = document.getElementById('part-locations-info');
            const qtyInput = document.getElementById('qty');

            let availableStock = 0;
            let partDebounce = null;
            let partAbort = null;

            function escapeHtml(str) {
                return String(str ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function renderPartSuggestions(items) {
                if (!partSuggestions) return;
                if (!items.length) {
                    partSuggestions.innerHTML = '<div class="px-3 py-2 text-sm text-slate-500 italic">No matches</div>';
                    partSuggestions.classList.remove('hidden');
                    return;
                }
                partSuggestions.innerHTML = items.map((p) => {
                    const name = (p.part_name_gci || p.part_name_vendor || '').trim();
                    const reg = (p.register_no || '').trim();
                    const meta = [name, reg].filter(Boolean).join(' • ');
                    return `<button type="button" class="w-full text-left px-3 py-2 hover:bg-slate-50" data-id="${escapeHtml(p.id)}" data-label="${escapeHtml(p.part_no)}" data-meta="${escapeHtml(meta)}">
                        <div class="font-mono text-xs font-bold text-slate-900">${escapeHtml(p.part_no)}</div>
                        <div class="text-xs text-slate-600">${escapeHtml(meta)}</div>
                    </button>`;
                }).join('');
                partSuggestions.classList.remove('hidden');
            }

            async function searchParts(q) {
                if (partAbort) partAbort.abort();
                partAbort = new AbortController();
                const url = `{{ route('parts.search') }}?q=${encodeURIComponent(q)}&in_stock=1&limit=20`;
                const res = await fetch(url, { signal: partAbort.signal, headers: { 'Accept': 'application/json' } });
                if (!res.ok) return [];
                return await res.json();
            }

            partSearch?.addEventListener('input', () => {
                const q = String(partSearch.value || '').trim();
                partSelect.value = '';
                if (q.length < 2) {
                    partSuggestions?.classList.add('hidden');
                    return;
                }
                if (partDebounce) clearTimeout(partDebounce);
                partDebounce = setTimeout(async () => {
                    try {
                        const items = await searchParts(q);
                        renderPartSuggestions(Array.isArray(items) ? items : []);
                    } catch (e) {
                        if (e?.name === 'AbortError') return;
                        partSuggestions?.classList.add('hidden');
                    }
                }, 200);
            });

            partSuggestions?.addEventListener('click', async (e) => {
                const btn = e.target.closest('button[data-id]');
                if (!btn) return;
                partSelect.value = btn.dataset.id;
                const label = btn.dataset.label || '';
                const meta = btn.dataset.meta || '';
                partSearch.value = meta ? `${label} — ${meta}` : label;
                partSuggestions.classList.add('hidden');
                await updateStockInfo();
            });

            document.addEventListener('click', (e) => {
                if (!partSuggestions || !partSearch) return;
                if (!partSearch.contains(e.target) && !partSuggestions.contains(e.target)) {
                    partSuggestions.classList.add('hidden');
                }
            });

            // Update stock info when part or location changes
            async function updateStockInfo() {
                const partId = partSelect.value;
                const fromLocation = fromLocationSelect.value;
                const toLocation = toLocationSelect.value;

                if (!partId) {
                    fromStockInfo.textContent = 'Available: -';
                    toStockInfo.textContent = 'Current: -';
                    partLocationsInfo.textContent = 'Select a part to see available locations';
                    return;
                }

                // Get stock at from location
                if (fromLocation) {
                    try {
                        const response = await fetch(`{{ route('warehouse.bin-transfers.location-stock') }}?part_id=${partId}&location_code=${fromLocation}`);
                        const data = await response.json();
                        availableStock = parseFloat(data.stock);
                        fromStockInfo.textContent = `Available: ${data.formatted}`;
                        fromStockInfo.className = availableStock > 0 ? 'text-xs text-green-600 mt-1 font-semibold' : 'text-xs text-red-600 mt-1 font-semibold';
                    } catch (error) {
                        fromStockInfo.textContent = 'Error loading stock';
                    }
                }

                // Get stock at to location
                if (toLocation) {
                    try {
                        const response = await fetch(`{{ route('warehouse.bin-transfers.location-stock') }}?part_id=${partId}&location_code=${toLocation}`);
                        const data = await response.json();
                        toStockInfo.textContent = `Current: ${data.formatted}`;
                    } catch (error) {
                        toStockInfo.textContent = 'Error loading stock';
                    }
                }

                // Get all locations for part
                try {
                    const response = await fetch(`{{ route('warehouse.bin-transfers.part-locations') }}?part_id=${partId}`);
                    const data = await response.json();
                    if (data.locations.length > 0) {
                        const locationsList = data.locations.map(loc => `${loc.location_code} (${loc.formatted_qty})`).join(', ');
                        partLocationsInfo.textContent = `Stock in: ${locationsList}`;
                    } else {
                        partLocationsInfo.textContent = 'No stock found for this part';
                    }
                } catch (error) {
                    partLocationsInfo.textContent = 'Error loading locations';
                }
            }

            partSelect.addEventListener('change', updateStockInfo);
            fromLocationSelect.addEventListener('change', updateStockInfo);
            toLocationSelect.addEventListener('change', updateStockInfo);

            // Validate quantity before submit
            document.getElementById('transfer-form').addEventListener('submit', function (e) {
                const qty = parseFloat(qtyInput.value);
                if (qty > availableStock) {
                    e.preventDefault();
                    alert(`Cannot transfer ${qty}. Only ${availableStock} available at source location.`);
                    return false;
                }
            });
        </script>
    @endpush
</x-app-layout>
