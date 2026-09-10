<x-app-layout>
    <x-slot name="header">
        {{ __('warehouse.bin_transfers.create.header', ['title' => $meta['title']]) }}
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800 mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h2 class="text-xl font-bold text-slate-900">{{ $meta['create_title'] }}</h2>
                    <p class="text-sm text-slate-600 mt-1">{{ $meta['description'] }}</p>
                </div>

                <form method="POST" action="{{ $mode === 'batch_to_batch' ? route('warehouse.batch-transfers.store') : route('warehouse.bin-transfers.store') }}" class="p-6 space-y-6" id="transfer-form">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            {{ __('warehouse.bin_transfers.create.part') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="hidden" name="part_id" id="part_id" value="{{ old('part_id') }}" required>
                        <div class="relative">
                            <input type="text" id="part_search" autocomplete="off"
                                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="{{ __('warehouse.bin_transfers.create.part_ph') }}">
                            <div id="part_suggestions" class="absolute z-20 mt-1 w-full max-h-72 overflow-auto rounded-lg border border-slate-200 bg-white shadow-lg hidden"></div>
                        </div>
                        <p class="text-xs text-slate-500 mt-1" id="part-locations-info">{{ __('warehouse.bin_transfers.create.part_hint') }}</p>
                    </div>

                    @if($mode === 'batch_to_batch')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    {{ __('warehouse.bin_transfers.create.location') }} <span class="text-red-500">*</span>
                                </label>
                                <select name="location_code" id="location_code" required class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">{{ __('warehouse.bin_transfers.create.select_location') }}</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->location_code }}" {{ old('location_code') == $location->location_code ? 'selected' : '' }}>
                                            {{ $location->location_code }}@if($location->zone) - {{ $location->zone }}@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    {{ __('warehouse.bin_transfers.create.quantity') }} <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="qty" id="qty" step="0.0001" min="0.0001" value="{{ old('qty') }}" required
                                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="{{ __('warehouse.bin_transfers.create.qty_ph') }}">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    {{ __('warehouse.bin_transfers.create.from_batch') }} <span class="text-red-500">*</span>
                                </label>
                                <select name="from_batch_no" id="from_batch_no" required class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">{{ __('warehouse.bin_transfers.create.select_source_batch') }}</option>
                                </select>
                                <p class="text-xs text-slate-500 mt-1" id="from-stock-info">{{ __('warehouse.bin_transfers.create.available') }}: -</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    {{ __('warehouse.bin_transfers.create.to_batch') }} <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="to_batch_no" id="to_batch_no" value="{{ old('to_batch_no') }}" required
                                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 uppercase"
                                    placeholder="{{ __('warehouse.bin_transfers.create.to_batch_ph') }}">
                                <p class="text-xs text-slate-500 mt-1">{{ __('warehouse.bin_transfers.create.to_batch_hint') }}</p>
                            </div>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    {{ __('warehouse.bin_transfers.create.from_location') }} <span class="text-red-500">*</span>
                                </label>
                                <select name="from_location_code" id="from_location_code" required class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">{{ __('warehouse.bin_transfers.create.select_source') }}</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->location_code }}" {{ old('from_location_code') == $location->location_code ? 'selected' : '' }}>
                                            {{ $location->location_code }}@if($location->zone) - {{ $location->zone }}@endif
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-slate-500 mt-1" id="from-stock-info">{{ __('warehouse.bin_transfers.create.available') }}: -</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    {{ __('warehouse.bin_transfers.create.to_location') }} <span class="text-red-500">*</span>
                                </label>
                                <select name="to_location_code" id="to_location_code" required class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">{{ __('warehouse.bin_transfers.create.select_destination') }}</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->location_code }}" {{ old('to_location_code') == $location->location_code ? 'selected' : '' }}>
                                            {{ $location->location_code }}@if($location->zone) - {{ $location->zone }}@endif
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-slate-500 mt-1" id="to-stock-info">{{ __('warehouse.bin_transfers.create.current') }}: -</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    {{ __('warehouse.bin_transfers.create.quantity') }} <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="qty" id="qty" step="0.0001" min="0.0001" value="{{ old('qty') }}" required
                                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="{{ __('warehouse.bin_transfers.create.qty_ph') }}">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    {{ __('warehouse.bin_transfers.create.transfer_date') }} <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="transfer_date" value="{{ old('transfer_date', now()->format('Y-m-d')) }}" required
                                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>
                    @endif

                    @if($mode === 'batch_to_batch')
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                {{ __('warehouse.bin_transfers.create.transfer_date') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="transfer_date" value="{{ old('transfer_date', now()->format('Y-m-d')) }}" required
                                class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            {{ __('warehouse.bin_transfers.create.notes') }}
                        </label>
                        <textarea name="notes" rows="3" class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('warehouse.bin_transfers.create.notes_ph') }}">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <a href="{{ $mode === 'batch_to_batch' ? route('warehouse.batch-transfers.index') : route('warehouse.bin-transfers.index') }}"
                            class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 font-semibold hover:bg-slate-50">
                            {{ __('warehouse.bin_transfers.create.cancel') }}
                        </a>
                        <button type="submit" id="submit-btn" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition-colors">
                            {{ __('warehouse.bin_transfers.create.save', ['title' => $meta['title']]) }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            const mode = @json($mode);
            const partSelect = document.getElementById('part_id');
            const partSearch = document.getElementById('part_search');
            const partSuggestions = document.getElementById('part_suggestions');
            const fromLocationSelect = document.getElementById('from_location_code');
            const toLocationSelect = document.getElementById('to_location_code');
            const locationSelect = document.getElementById('location_code');
            const fromBatchSelect = document.getElementById('from_batch_no');
            const toBatchInput = document.getElementById('to_batch_no');
            const fromStockInfo = document.getElementById('from-stock-info');
            const toStockInfo = document.getElementById('to-stock-info');
            const partLocationsInfo = document.getElementById('part-locations-info');
            const qtyInput = document.getElementById('qty');

            let availableStock = 0;
            let partDebounce = null;
            let partAbort = null;

            const T = {
                noMatches: @js(__('warehouse.bin_transfers.create.no_matches')),
                selectSourceBatch: @js(__('warehouse.bin_transfers.create.select_source_batch')),
                noBatch: @js(__('warehouse.bin_transfers.create.no_batch')),
                selectPartHint: @js(__('warehouse.bin_transfers.create.part_hint')),
                stockIn: @js(__('warehouse.bin_transfers.create.stock_in', ['locations' => ':locations'])),
                noStock: @js(__('warehouse.bin_transfers.create.no_stock')),
                available: @js(__('warehouse.bin_transfers.create.available')),
                current: @js(__('warehouse.bin_transfers.create.current')),
                exceeds: @js(__('warehouse.bin_transfers.create.exceeds', ['qty' => ':qty', 'available' => ':available'])),
                diffBatch: @js(__('warehouse.bin_transfers.create.diff_batch')),
            };

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
                    partSuggestions.innerHTML = '<div class="px-3 py-2 text-sm text-slate-500 italic">' + escapeHtml(T.noMatches) + '</div>';
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

            async function fetchJson(url) {
                const response = await fetch(url);
                if (!response.ok) throw new Error('Request failed');
                return await response.json();
            }

            async function loadBatchOptions() {
                if (mode !== 'batch_to_batch' || !partSelect.value || !locationSelect.value || !fromBatchSelect) return;
                const data = await fetchJson(`{{ route('warehouse.bin-transfers.location-batches') }}?part_id=${partSelect.value}&location_code=${encodeURIComponent(locationSelect.value)}`);
                fromBatchSelect.innerHTML = '<option value="">' + escapeHtml(T.selectSourceBatch) + '</option>';
                (data.batches || []).forEach(batch => {
                    const option = document.createElement('option');
                    option.value = batch.batch_no || '';
                    option.dataset.qty = batch.qty_on_hand;
                    option.textContent = `${batch.batch_no || T.noBatch} - ${batch.qty_on_hand}`;
                    fromBatchSelect.appendChild(option);
                });
            }

            async function updateStockInfo() {
                const partId = partSelect.value;
                if (!partId) {
                    if (fromStockInfo) fromStockInfo.textContent = T.available + ': -';
                    if (toStockInfo) toStockInfo.textContent = T.current + ': -';
                    if (partLocationsInfo) partLocationsInfo.textContent = T.selectPartHint;
                    return;
                }

                if (mode === 'batch_to_batch') {
                    if (locationSelect?.value) {
                        await loadBatchOptions();
                        const data = await fetchJson(`{{ route('warehouse.bin-transfers.part-locations') }}?part_id=${partId}`);
                        const locations = (data.locations || []).map(loc => `${loc.location_code} (${loc.formatted_qty})`).join(', ');
                        if (partLocationsInfo) partLocationsInfo.textContent = locations ? T.stockIn.replace(':locations', locations) : T.noStock;
                    }
                    return;
                }

                const fromLocation = fromLocationSelect?.value;
                const toLocation = toLocationSelect?.value;

                if (fromLocation) {
                    const data = await fetchJson(`{{ route('warehouse.bin-transfers.location-stock') }}?part_id=${partId}&location_code=${fromLocation}`);
                    availableStock = parseFloat(data.stock);
                    if (fromStockInfo) {
                        fromStockInfo.textContent = `${T.available}: ${data.formatted}`;
                    }
                }

                if (toLocation) {
                    const data = await fetchJson(`{{ route('warehouse.bin-transfers.location-stock') }}?part_id=${partId}&location_code=${toLocation}`);
                    if (toStockInfo) {
                        toStockInfo.textContent = `${T.current}: ${data.formatted}`;
                    }
                }

                const data = await fetchJson(`{{ route('warehouse.bin-transfers.part-locations') }}?part_id=${partId}`);
                const locations = (data.locations || []).map(loc => `${loc.location_code} (${loc.formatted_qty})`).join(', ');
                if (partLocationsInfo) partLocationsInfo.textContent = locations ? T.stockIn.replace(':locations', locations) : T.noStock;
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

            partSelect?.addEventListener('change', updateStockInfo);
            fromLocationSelect?.addEventListener('change', updateStockInfo);
            toLocationSelect?.addEventListener('change', updateStockInfo);
            locationSelect?.addEventListener('change', updateStockInfo);
            fromBatchSelect?.addEventListener('change', () => {
                const selected = fromBatchSelect.options[fromBatchSelect.selectedIndex];
                availableStock = parseFloat(selected?.dataset?.qty || '0');
                if (fromStockInfo) fromStockInfo.textContent = `${T.available}: ${selected?.dataset?.qty || '-'}`;
            });

            document.getElementById('transfer-form').addEventListener('submit', function (e) {
                const qty = parseFloat(qtyInput.value);
                if (qty > availableStock) {
                    e.preventDefault();
                    alert(T.exceeds.replace(':qty', qty).replace(':available', availableStock));
                    return false;
                }
                if (mode === 'batch_to_batch' && fromBatchSelect && toBatchInput && fromBatchSelect.value === toBatchInput.value.trim()) {
                    e.preventDefault();
                    alert(T.diffBatch);
                    return false;
                }
            });
        </script>
    @endpush
</x-app-layout>
