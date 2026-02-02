<x-app-layout>
    <x-slot name="header">
        Warehouse • New Stock Adjustment
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-slate-900">Create Adjustment</div>
                        <div class="text-sm text-slate-500">Pilih mode: set qty (adjustment) atau perpindahan stok (move)</div>
                    </div>
                    <a href="{{ route('warehouse.stock-adjustments.index') }}" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50">
                        Back
                    </a>
                </div>

                <form method="POST" action="{{ route('warehouse.stock-adjustments.store') }}" class="px-6 py-6 space-y-5" x-data="{ mode: '{{ old('action_type', 'adjustment') }}' }">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Mode</label>
                        <select name="action_type" class="mt-1 w-full rounded-xl border-slate-200" x-model="mode">
                            <option value="adjustment">Adjustment (set qty_after)</option>
                            <option value="move">Move (from → to)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Part</label>
                        <input type="hidden" name="part_id" id="part_id" value="{{ old('part_id') }}" required>
                        <div class="relative mt-1">
                            <input type="text" id="part_search" autocomplete="off"
                                class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Type part no / name (min 2 chars)">
                            <div id="part_suggestions"
                                class="absolute z-20 mt-1 w-full max-h-72 overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg hidden">
                            </div>
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Cari part supaya tidak load ribuan option (hindari page hang).</div>
                    </div>

                    <div x-show="mode === 'adjustment'">
                        <label class="block text-sm font-semibold text-slate-700">Location</label>
                        <select name="location_code" class="mt-1 w-full rounded-xl border-slate-200 uppercase" x-bind:required="mode === 'adjustment'">
                            <option value="">-- select location --</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->location_code }}" @selected(strtoupper((string) old('location_code')) === $loc->location_code)>
                                    {{ $loc->location_code }}{{ $loc->class ? ' • Class ' . $loc->class : '' }}{{ $loc->zone ? ' • Zone ' . $loc->zone : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="mt-1 text-xs text-slate-500">Hanya lokasi status ACTIVE yang bisa dipilih.</div>
                    </div>

                    <div id="batch-selector-container" style="display:none;" x-show="mode === 'adjustment'">
                        <label class="block text-sm font-semibold text-slate-700">Batch No (Optional)</label>
                        <select name="batch_no" id="batch_no" class="mt-1 w-full rounded-xl border-slate-200">
                            <option value="">-- All Batches (Total Qty) --</option>
                        </select>
                        <div class="mt-1 text-xs text-slate-500">Pilih batch tertentu untuk adjustment, atau kosongkan untuk adjust total qty di lokasi.</div>
                        <div id="batch-current-qty" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm" style="display:none;">
                            <strong>Current Stock:</strong> <span id="current-qty-value">-</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div x-show="mode === 'adjustment'">
                            <label class="block text-sm font-semibold text-slate-700">Qty After</label>
                            <input type="number" step="0.0001" min="0" name="qty_after" value="{{ old('qty_after') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="0" x-bind:required="mode === 'adjustment'">
                        </div>
                        <div x-show="mode === 'move'">
                            <label class="block text-sm font-semibold text-slate-700">Qty Move</label>
                            <input type="number" step="0.0001" min="0.0001" name="qty_move" value="{{ old('qty_move') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="0.0001" x-bind:required="mode === 'move'">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Adjusted At</label>
                            <input type="datetime-local" name="adjusted_at" value="{{ old('adjusted_at') }}" class="mt-1 w-full rounded-xl border-slate-200">
                            <div class="mt-1 text-xs text-slate-500">Kosongkan untuk pakai waktu sekarang.</div>
                        </div>
                    </div>

                    <div x-show="mode === 'move'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">From Location</label>
                            <select name="from_location_code" id="from_location_code" class="mt-1 w-full rounded-xl border-slate-200 uppercase" x-bind:required="mode === 'move'">
                                <option value="">-- select location --</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->location_code }}" @selected(strtoupper((string) old('from_location_code')) === $loc->location_code)>
                                        {{ $loc->location_code }}{{ $loc->class ? ' • Class ' . $loc->class : '' }}{{ $loc->zone ? ' • Zone ' . $loc->zone : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">To Location</label>
                            <select name="to_location_code" id="to_location_code" class="mt-1 w-full rounded-xl border-slate-200 uppercase" x-bind:required="mode === 'move'">
                                <option value="">-- select location --</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->location_code }}" @selected(strtoupper((string) old('to_location_code')) === $loc->location_code)>
                                        {{ $loc->location_code }}{{ $loc->class ? ' • Class ' . $loc->class : '' }}{{ $loc->zone ? ' • Zone ' . $loc->zone : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">From Batch</label>
                            <select name="from_batch_no" id="from_batch_no" class="mt-1 w-full rounded-xl border-slate-200" x-bind:required="mode === 'move'">
                                <option value="">-- select batch --</option>
                            </select>
                            <div class="mt-1 text-xs text-slate-500">Wajib pilih 1 batch sumber untuk move.</div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700">To Batch</label>
                            <input type="text" name="to_batch_no" id="to_batch_no" value="{{ old('to_batch_no') }}" class="mt-1 w-full rounded-xl border-slate-200 uppercase" placeholder="BATCH-DEST" x-bind:required="mode === 'move'">
                            <div class="mt-1 text-xs text-slate-500">Isi batch tujuan (boleh batch baru).</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Reason</label>
                        <textarea name="reason" rows="3" class="mt-1 w-full rounded-xl border-slate-200" placeholder="contoh: cycle count / correction">{{ old('reason') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('warehouse.stock-adjustments.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50">Cancel</a>
                        <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save Adjustment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const partIdInput = document.getElementById('part_id');
        const partSearch = document.getElementById('part_search');
        const partSuggestions = document.getElementById('part_suggestions');
        const locationSelect = document.querySelector('select[name="location_code"]');
        const batchSelect = document.getElementById('batch_no');
        const batchContainer = document.getElementById('batch-selector-container');
        const batchCurrentQty = document.getElementById('batch-current-qty');
        const currentQtyValue = document.getElementById('current-qty-value');
        const fromLocationSelect = document.getElementById('from_location_code');
        const fromBatchSelect = document.getElementById('from_batch_no');
        let batchAbort = null;
        let fromBatchAbort = null;
        let batchDebounce = null;
        let fromBatchDebounce = null;
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
            const url = `{{ route('parts.search') }}?q=${encodeURIComponent(q)}&limit=20`;
            const res = await fetch(url, { signal: partAbort.signal, headers: { 'Accept': 'application/json' } });
            if (!res.ok) return [];
            return await res.json();
        }

        function normalizeBatchesPayload(payload) {
            // Backward-compatible: accept array or {batches: [...], total, truncated}
            if (Array.isArray(payload)) {
                return { batches: payload, total: payload.length, limit: payload.length, truncated: false };
            }
            const batches = Array.isArray(payload?.batches) ? payload.batches : [];
            return {
                batches,
                total: Number(payload?.total ?? batches.length),
                limit: Number(payload?.limit ?? batches.length),
                truncated: Boolean(payload?.truncated ?? false),
            };
        }

        async function fetchBatches(partId, locationCode, signal) {
            const res = await fetch(`{{ route('warehouse.stock-adjustments.get-batches') }}?part_id=${partId}&location_code=${encodeURIComponent(locationCode)}&limit=200`, { signal });
            const json = await res.json();
            return normalizeBatchesPayload(json);
        }

        function loadBatches() {
            const partId = partIdInput?.value;
            const locationCode = locationSelect.value;

            if (!partId || !locationCode) {
                batchContainer.style.display = 'none';
                return;
            }

            if (batchDebounce) clearTimeout(batchDebounce);
            batchDebounce = setTimeout(() => {
                if (batchAbort) batchAbort.abort();
                batchAbort = new AbortController();

                batchSelect.innerHTML = '<option value="">Loading batches...</option>';
                batchContainer.style.display = 'block';
                batchCurrentQty.style.display = 'none';

                fetchBatches(partId, locationCode, batchAbort.signal)
                .then(({ batches, total, truncated }) => {
                    batchSelect.innerHTML = '<option value="">-- All Batches (Total Qty) --</option>';
                    
                    if (batches.length === 0) {
                        batchContainer.style.display = 'none';
                        batchCurrentQty.style.display = 'none';
                        return;
                    }

                    if (truncated) {
                        const info = document.createElement('option');
                        info.disabled = true;
                        info.value = '';
                        info.textContent = `Showing first ${batches.length} of ${total} batches (refine location/part)`;
                        batchSelect.appendChild(info);
                    }

                    batches.forEach(batch => {
                        const option = document.createElement('option');
                        option.value = batch.batch_no || '';
                        const batchLabel = batch.batch_no || '(No Batch)';
                        const prodDate = batch.production_date ? ` [${batch.production_date}]` : '';
                        option.textContent = `${batchLabel}${prodDate} - Current: ${batch.qty_on_hand}`;
                        option.dataset.qty = batch.qty_on_hand;
                        batchSelect.appendChild(option);
                    });

                    batchContainer.style.display = 'block';
                })
                .catch(err => {
                    if (err?.name === 'AbortError') return;
                    console.error('Error loading batches:', err);
                    batchContainer.style.display = 'none';
                });
            }, 200);
        }

        function updateCurrentQty() {
            const selectedOption = batchSelect.options[batchSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.qty) {
                currentQtyValue.textContent = selectedOption.dataset.qty;
                batchCurrentQty.style.display = 'block';
            } else {
                batchCurrentQty.style.display = 'none';
            }
        }

        partIdInput?.addEventListener('change', loadBatches);
        locationSelect.addEventListener('change', loadBatches);
        batchSelect.addEventListener('change', updateCurrentQty);

        function loadFromBatches() {
            const partId = partIdInput?.value;
            const locationCode = fromLocationSelect ? fromLocationSelect.value : '';

            if (!partId || !locationCode || !fromBatchSelect) {
                return;
            }

            if (fromBatchDebounce) clearTimeout(fromBatchDebounce);
            fromBatchDebounce = setTimeout(() => {
                if (fromBatchAbort) fromBatchAbort.abort();
                fromBatchAbort = new AbortController();

                fromBatchSelect.innerHTML = '<option value="">Loading batches...</option>';

                fetchBatches(partId, locationCode, fromBatchAbort.signal)
                .then(({ batches, total, truncated }) => {
                    fromBatchSelect.innerHTML = '<option value="">-- select batch --</option>';
                    if (truncated) {
                        const info = document.createElement('option');
                        info.disabled = true;
                        info.value = '';
                        info.textContent = `Showing first ${batches.length} of ${total} batches (refine location/part)`;
                        fromBatchSelect.appendChild(info);
                    }
                    batches.forEach(batch => {
                        const option = document.createElement('option');
                        option.value = batch.batch_no || '';
                        const batchLabel = batch.batch_no || '(No Batch)';
                        const prodDate = batch.production_date ? ` [${batch.production_date}]` : '';
                        option.textContent = `${batchLabel}${prodDate} - Current: ${batch.qty_on_hand}`;
                        fromBatchSelect.appendChild(option);
                    });
                })
                .catch(err => {
                    if (err?.name === 'AbortError') return;
                    console.error('Error loading from batches:', err);
                });
            }, 200);
        }

        if (fromLocationSelect) {
            partIdInput?.addEventListener('change', loadFromBatches);
            fromLocationSelect.addEventListener('change', loadFromBatches);
        }

        // Part autocomplete (avoid huge <select> that can freeze the page)
        partSearch?.addEventListener('input', () => {
            const q = String(partSearch.value || '').trim();
            if (partIdInput) partIdInput.value = '';
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

        partSuggestions?.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-id]');
            if (!btn) return;
            if (partIdInput) {
                partIdInput.value = btn.dataset.id;
                partIdInput.dispatchEvent(new Event('change'));
            }
            const label = btn.dataset.label || '';
            const meta = btn.dataset.meta || '';
            partSearch.value = meta ? `${label} — ${meta}` : label;
            partSuggestions.classList.add('hidden');
            loadBatches();
            loadFromBatches();
        });

        document.addEventListener('click', (e) => {
            if (!partSuggestions || !partSearch) return;
            if (!partSearch.contains(e.target) && !partSuggestions.contains(e.target)) {
                partSuggestions.classList.add('hidden');
            }
        });
    </script>
</x-app-layout>
