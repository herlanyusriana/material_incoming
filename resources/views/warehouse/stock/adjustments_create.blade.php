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
                        <select name="part_id" class="mt-1 w-full rounded-xl border-slate-200" required>
                            <option value="">-- select part --</option>
                            @foreach($parts as $p)
                                <option value="{{ $p->id }}" @selected((string) old('part_id') === (string) $p->id)>
                                    {{ $p->part_no }} — {{ $p->part_name_gci ?? $p->part_name_vendor ?? '' }}
                                </option>
                            @endforeach
                        </select>
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
        const partSelect = document.querySelector('select[name="part_id"]');
        const locationSelect = document.querySelector('select[name="location_code"]');
        const batchSelect = document.getElementById('batch_no');
        const batchContainer = document.getElementById('batch-selector-container');
        const batchCurrentQty = document.getElementById('batch-current-qty');
        const currentQtyValue = document.getElementById('current-qty-value');
        const fromLocationSelect = document.getElementById('from_location_code');
        const fromBatchSelect = document.getElementById('from_batch_no');

        function loadBatches() {
            const partId = partSelect.value;
            const locationCode = locationSelect.value;

            if (!partId || !locationCode) {
                batchContainer.style.display = 'none';
                return;
            }

            fetch(`{{ route('warehouse.stock-adjustments.get-batches') }}?part_id=${partId}&location_code=${encodeURIComponent(locationCode)}`)
                .then(res => res.json())
                .then(batches => {
                    batchSelect.innerHTML = '<option value="">-- All Batches (Total Qty) --</option>';
                    
                    if (batches.length === 0) {
                        batchContainer.style.display = 'none';
                        batchCurrentQty.style.display = 'none';
                        return;
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
                    console.error('Error loading batches:', err);
                    batchContainer.style.display = 'none';
                });
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

        partSelect.addEventListener('change', loadBatches);
        locationSelect.addEventListener('change', loadBatches);
        batchSelect.addEventListener('change', updateCurrentQty);

        function loadFromBatches() {
            const partId = partSelect.value;
            const locationCode = fromLocationSelect ? fromLocationSelect.value : '';

            if (!partId || !locationCode || !fromBatchSelect) {
                return;
            }

            fetch(`{{ route('warehouse.stock-adjustments.get-batches') }}?part_id=${partId}&location_code=${encodeURIComponent(locationCode)}`)
                .then(res => res.json())
                .then(batches => {
                    fromBatchSelect.innerHTML = '<option value="">-- select batch --</option>';
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
                    console.error('Error loading from batches:', err);
                });
        }

        if (fromLocationSelect) {
            partSelect.addEventListener('change', loadFromBatches);
            fromLocationSelect.addEventListener('change', loadFromBatches);
        }
    </script>
</x-app-layout>
