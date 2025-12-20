<x-app-layout>
    <x-slot name="header">
        Receive Invoice {{ $arrival->invoice_no }}
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <form action="{{ route('receives.invoice.store', $arrival) }}" method="POST" class="bg-white border border-slate-200 rounded-2xl shadow-lg p-8 space-y-8" id="receive-form">
                @csrf

                <div class="flex items-center justify-between pb-6 border-b border-slate-200">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Receive Multiple Items</h3>
                        <p class="text-sm text-slate-600 mt-1">Proses semua item pending dalam satu invoice</p>
                    </div>
                    <a href="{{ route('receives.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">Kembali</a>
                </div>

                <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                    <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-4">Info Invoice</h4>
                    <div class="grid md:grid-cols-2 gap-x-12 gap-y-4 text-sm">
                        <div class="flex items-center">
                            <span class="font-semibold text-slate-700 w-32">Supplier</span>
                            <span class="text-slate-900">= {{ $arrival->vendor->vendor_name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-semibold text-slate-700 w-32">Invoice No.</span>
                            <span class="text-slate-900">= {{ $arrival->invoice_no }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-semibold text-slate-700 w-32">ETD</span>
                            <span class="text-slate-900">= {{ $arrival->ETD ? $arrival->ETD->format('d M Y') : '-' }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-semibold text-slate-700 w-32">ETA</span>
                            <span class="text-slate-900">= {{ $arrival->ETA ? $arrival->ETA->format('d M Y') : '-' }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-semibold text-slate-700 w-32">Total Bundle</span>
                            <span class="text-slate-900">= {{ number_format($pendingItems->sum('qty_bundle') ?? 0) }}</span>
                        </div>
                    </div>
                </div>

                @foreach($pendingItems as $item)
                    <div class="border border-slate-200 rounded-xl shadow-sm">
                        <div class="px-6 py-4 bg-gradient-to-r from-slate-50 to-slate-100 flex items-center justify-between">
                            <div class="space-y-1 text-sm">
                                <div class="text-xs uppercase text-slate-500">Item</div>
                                <div class="font-semibold text-slate-900">{{ $item->part->part_no }} — {{ $item->part->part_name_gci ?? $item->part->part_name_vendor }}</div>
                                <div class="text-slate-600 font-mono text-xs">{{ $item->size ?? '-' }}</div>
                            </div>
                            <div class="flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-700">Planned</span>
                                    <span class="px-2 py-1 rounded-lg bg-slate-100 text-slate-800">{{ number_format($item->qty_goods) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-700">Remaining</span>
                                    <span class="px-2 py-1 rounded-lg bg-green-50 text-green-800" id="remaining-{{ $item->id }}">{{ number_format($item->remaining_qty) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-700">Input Total</span>
                                    <span class="px-2 py-1 rounded-lg bg-blue-50 text-blue-800" id="input-total-{{ $item->id }}">0</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-slate-700">Total Bundle</span>
                                    <span class="px-2 py-1 rounded-lg bg-slate-100 text-slate-800">{{ number_format($item->qty_bundle ?? 0) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-white">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                            Tag
                                            <button type="button" class="ml-2 px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors shadow-sm add-tag-btn" data-item="{{ $item->id }}">+ Add TAG</button>
                                        </th>
	                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Bundle</th>
	                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Qty Goods</th>
	                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Net Weight (KGM)</th>
	                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Gross Weight (KGM)</th>
	                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">QC</th>
	                                    </tr>
	                                </thead>
                                <tbody
                                    class="divide-y divide-slate-100 bg-white tag-rows"
                                    data-item="{{ $item->id }}"
                                    data-default-weight="{{ $item->default_weight }}"
                                    data-bundles="{{ $item->qty_bundle ?? 0 }}"
                                    data-size="{{ $item->size ?? '-' }}"
                                    data-part-no="{{ $item->part->part_no }}"
                                    data-part-name="{{ $item->part->part_name_gci ?? $item->part->part_name_vendor }}"
                                    data-goods-unit="{{ strtoupper($item->unit_goods ?? 'KGM') }}"
                                >
                                    <tr class="tag-row hover:bg-slate-50 transition-colors">
                                        <td class="px-3 py-2 align-top">
                                            <input type="text" name="items[{{ $item->id }}][tags][0][tag]" placeholder="TAG-001" class="w-40 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            @php
                                                $defaultBundleUnit = $item->unit_bundle ?? 'Coil';
                                            @endphp
                                            <div class="flex items-center gap-2">
                                                <input type="number" name="items[{{ $item->id }}][tags][0][bundle_qty]" min="1" value="1" class="w-16 text-center rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
                                                <select name="items[{{ $item->id }}][tags][0][bundle_unit]" class="w-28 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
                                                    <option value="Coil" @selected($defaultBundleUnit === 'Coil')>Coil</option>
                                                    <option value="Bundle" @selected($defaultBundleUnit === 'Bundle')>Bundle</option>
                                                    <option value="Pallets" @selected($defaultBundleUnit === 'Pallets')>Pallets</option>
                                                    <option value="Box" @selected($defaultBundleUnit === 'Box')>Box</option>
                                                    <option value="Pcs" @selected($defaultBundleUnit === 'Pcs')>Pcs</option>
                                                </select>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <div class="flex items-center gap-2">
                                                <input type="number" name="items[{{ $item->id }}][tags][0][qty]" min="1" placeholder="0" class="qty-input w-24 text-center rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required data-item="{{ $item->id }}">
                                                <select name="items[{{ $item->id }}][tags][0][qty_unit]" class="w-24 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
                                                    @php $defaultGoodsUnit = strtoupper($item->unit_goods ?? 'KGM'); @endphp
                                                    <option value="KGM" @selected($defaultGoodsUnit === 'KGM')>KGM</option>
                                                    <option value="SHEET" @selected($defaultGoodsUnit === 'SHEET')>SHEET</option>
                                                    <option value="PCS" @selected($defaultGoodsUnit === 'PCS')>PCS</option>
                                                </select>
                                            </div>
                                        </td>
	                                        <td class="px-3 py-2 align-top">
	                                            <input
	                                                type="number"
	                                                name="items[{{ $item->id }}][tags][0][net_weight]"
	                                                step="0.01"
	                                                placeholder="0.00"
	                                                class="w-24 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5"
	                                            >
	                                        </td>
	                                        <td class="px-3 py-2 align-top">
	                                            <input
	                                                type="number"
	                                                name="items[{{ $item->id }}][tags][0][gross_weight]"
	                                                step="0.01"
	                                                placeholder="0.00"
	                                                class="w-24 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5"
	                                            >
	                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <select name="items[{{ $item->id }}][tags][0][qc_status]" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
                                                <option value="pass">Pass</option>
                                                <option value="reject">Reject</option>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-200">
                    <a href="{{ route('receives.index') }}" class="px-5 py-2.5 text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors text-sm font-medium">Cancel</a>
                    <button type="submit" class="px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors shadow-sm">
                        Simpan Receive
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const tagIndexes = {};
        const remainingMap = {};
        const inputTotals = {};

        document.querySelectorAll('.tag-rows').forEach(tbody => {
            const itemId = tbody.dataset.item;
            tagIndexes[itemId] = 1;
            remainingMap[itemId] = Number(document.getElementById(`remaining-${itemId}`).textContent.replace(/,/g, '')) || 0;
            inputTotals[itemId] = 0;
        });

        function updateTotals(itemId) {
            const rows = document.querySelectorAll(`.tag-rows[data-item="${itemId}"] input.qty-input`);
            let total = 0;
            rows.forEach(input => {
                total += Number(input.value) || 0;
            });
            inputTotals[itemId] = total;
            const totalEl = document.getElementById(`input-total-${itemId}`);
            if (totalEl) totalEl.textContent = total;

            let alertEl = document.getElementById(`alert-${itemId}`);
            if (!alertEl) {
                alertEl = document.createElement('div');
                alertEl.id = `alert-${itemId}`;
                alertEl.className = 'px-4 py-2 text-sm font-semibold';
                document.querySelector(`.tag-rows[data-item="${itemId}"]`).parentElement.appendChild(alertEl);
            }

            if (total > remainingMap[itemId]) {
                alertEl.textContent = `Total qty (${total}) melebihi sisa (${remainingMap[itemId]}).`;
                alertEl.classList.remove('hidden');
                alertEl.classList.add('text-red-600');
            } else {
                alertEl.textContent = '';
                alertEl.classList.add('hidden');
            }
        }

        function bindRowEvents(row, itemId) {
            const qtyInput = row.querySelector('input.qty-input');
            if (qtyInput) {
                qtyInput.addEventListener('input', () => updateTotals(itemId));
            }
            const removeBtn = row.querySelector('.remove-tag');
            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    row.remove();
                    updateTotals(itemId);
                });
            }
        }

	        function createTagRowHtml(itemId, idx, sizeText, partNo, partName, defaultWeight, goodsUnit) {
	            return `
	                <td class="px-3 py-2 align-top">
	                    <input type="text" name="items[${itemId}][tags][${idx}][tag]" placeholder="TAG-${String(idx + 1).padStart(3, '0')}" class="w-40 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
	                </td>
	                <td class="px-3 py-2 align-top">
                        <div class="flex items-center gap-2">
	                        <input type="number" name="items[${itemId}][tags][${idx}][bundle_qty]" min="1" value="1" class="w-16 text-center rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
	                        <select name="items[${itemId}][tags][${idx}][bundle_unit]" class="w-28 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
	                            <option value="Coil">Coil</option>
	                            <option value="Bundle">Bundle</option>
	                            <option value="Pallets">Pallets</option>
	                            <option value="Box">Box</option>
	                            <option value="Pcs">Pcs</option>
	                        </select>
                        </div>
	                </td>
	                <td class="px-3 py-2 align-top">
                        <div class="flex items-center gap-2">
	                        <input type="number" name="items[${itemId}][tags][${idx}][qty]" min="1" value="1" class="qty-input w-24 text-center rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required data-item="${itemId}">
                            <select name="items[${itemId}][tags][${idx}][qty_unit]" class="w-24 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
                                <option value="KGM" ${goodsUnit === 'KGM' ? 'selected' : ''}>KGM</option>
                                <option value="SHEET" ${goodsUnit === 'SHEET' ? 'selected' : ''}>SHEET</option>
                                <option value="PCS" ${goodsUnit === 'PCS' ? 'selected' : ''}>PCS</option>
                            </select>
                        </div>
	                </td>
	                <td class="px-3 py-2 align-top">
	                    <input type="number" name="items[${itemId}][tags][${idx}][net_weight]" step="0.01" placeholder="0.00" value="${defaultWeight || ''}" class="w-24 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5">
	                </td>
	                <td class="px-3 py-2 align-top">
	                    <input type="number" name="items[${itemId}][tags][${idx}][gross_weight]" step="0.01" placeholder="0.00" class="w-24 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5">
	                </td>
                <td class="px-3 py-2 align-top">
                    <select name="items[${itemId}][tags][${idx}][qc_status]" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-xs py-1.5" required>
                        <option value="pass">Pass</option>
                        <option value="reject">Reject</option>
                    </select>
                </td>
            `;
        }

        document.querySelectorAll('.add-tag-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const itemId = btn.dataset.item;
                const tbody = document.querySelector(`.tag-rows[data-item="${itemId}"]`);
                const idx = tagIndexes[itemId] ?? 0;
                const defaultWeight = tbody.dataset.defaultWeight;
                const sizeText = tbody.dataset.size || '';
                const partNo = tbody.dataset.partNo || '';
                const partName = tbody.dataset.partName || '';
                const goodsUnit = tbody.dataset.goodsUnit || 'KGM';

                const row = document.createElement('tr');
                row.className = 'tag-row hover:bg-slate-50 transition-colors';
                row.innerHTML = createTagRowHtml(itemId, idx, sizeText, partNo, partName, defaultWeight, goodsUnit);
                tbody.appendChild(row);
                tagIndexes[itemId] = idx + 1;
                bindRowEvents(row, itemId);
                updateTotals(itemId);
            });
        });

	        document.querySelectorAll('.tag-rows').forEach(tbody => {
	            const itemId = tbody.dataset.item;
	            const bundleCount = Number(tbody.dataset.bundles || '0');
	            const firstNetWeightInput = tbody.querySelector('input[name$="[net_weight]"], input[name$="[weight]"]');
	            tbody.dataset.defaultWeight = firstNetWeightInput ? firstNetWeightInput.value || '' : '';

            const sizeText = tbody.dataset.size || '';
            const partNo = tbody.dataset.partNo || '';
            const partName = tbody.dataset.partName || '';
            const defaultWeight = tbody.dataset.defaultWeight;
            const goodsUnit = tbody.dataset.goodsUnit || 'KGM';

            tbody.innerHTML = '';

            const rowsToCreate = bundleCount > 0 ? bundleCount : 1;

            for (let idx = 0; idx < rowsToCreate; idx++) {
                const row = document.createElement('tr');
                row.className = 'tag-row hover:bg-slate-50 transition-colors';
                row.innerHTML = createTagRowHtml(itemId, idx, sizeText, partNo, partName, defaultWeight, goodsUnit);
                tbody.appendChild(row);
                bindRowEvents(row, itemId);
            }

            tagIndexes[itemId] = rowsToCreate;
            updateTotals(itemId);
        });

        document.getElementById('receive-form').addEventListener('submit', function (e) {
            let hasError = false;
            Object.keys(remainingMap).forEach(itemId => {
                updateTotals(itemId);
                if (inputTotals[itemId] > remainingMap[itemId]) {
                    hasError = true;
                }
            });
            if (hasError) {
                e.preventDefault();
                alert('Ada qty yang melebihi sisa. Mohon periksa kembali.');
            }
        });
    </script>
</x-app-layout>
