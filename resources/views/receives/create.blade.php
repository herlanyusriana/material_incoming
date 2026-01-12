<x-app-layout>
    <x-slot name="header">
        Receive Item
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <form action="{{ route('receives.store', $arrivalItem) }}" method="POST" class="bg-white border border-slate-200 rounded-2xl shadow-lg p-8 space-y-8" id="receive-form">
                @csrf

                <div class="flex items-center justify-between pb-6 border-b border-slate-200">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Receive Details</h3>
                        <p class="text-sm text-slate-600 mt-1">Enter receive information and tag details</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @php
                            $hasContainerInspection = ($arrivalItem->arrival->containers ?? collect())->contains(fn ($c) => (bool) $c->inspection);
                        @endphp
                        @if ($hasContainerInspection)
                            <a href="{{ route('departures.inspection-report', $arrivalItem->arrival) }}" target="_blank" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">Print Inspection</a>
                        @endif
                        <a href="{{ route('receives.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">Back to List</a>
                    </div>
                </div>

                <!-- Information Section -->
                <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                    <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-4">Departure Information</h4>
                    <div class="grid md:grid-cols-2 gap-x-12 gap-y-4 text-sm">
                        <div class="flex items-center">
                            <span class="font-semibold text-slate-700 w-32">Supplier</span>
                            <span class="text-slate-900">= {{ $arrivalItem->arrival->vendor->vendor_name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-semibold text-slate-700 w-32">Invoice No.</span>
                            <span class="text-slate-900">= {{ $arrivalItem->arrival->invoice_no }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-semibold text-slate-700 w-32">ETD</span>
                            <span class="text-slate-900">= {{ $arrivalItem->arrival->ETD ? $arrivalItem->arrival->ETD->format('d M Y') : '-' }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-semibold text-slate-700 w-32">ETA</span>
                            <span class="text-slate-900">= {{ $arrivalItem->arrival->ETA ? $arrivalItem->arrival->ETA->format('d M Y') : '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 border border-slate-200">
                    <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-4">Receiving Date</h4>
                    <div class="grid md:grid-cols-2 gap-x-12 gap-y-4 text-sm">
                        <div class="space-y-1">
                            <label for="receive_date" class="text-sm font-medium text-slate-700">Tanggal Receive</label>
                            <input type="date" id="receive_date" name="receive_date" value="{{ old('receive_date', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                            @error('receive_date') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                @php
                    $containers = $arrivalItem->arrival->containers ?? collect();
                @endphp
                @if ($containers->count())
                    <div class="bg-white rounded-xl p-6 border border-slate-200">
                        <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-4">Container Inspection (per Container)</h4>
                        <div class="overflow-x-auto border border-slate-200 rounded-xl">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                        <th class="px-4 py-3 text-left font-semibold">Container No</th>
                                        <th class="px-4 py-3 text-left font-semibold">Seal Code</th>
                                        <th class="px-4 py-3 text-left font-semibold">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($containers as $c)
                                        <tr>
                                            <td class="px-4 py-3 font-mono text-xs">{{ strtoupper($c->container_no) }}</td>
                                            <td class="px-4 py-3 font-mono text-xs">{{ strtoupper($c->seal_code ?? '-') }}</td>
                                            <td class="px-4 py-3">
                                                @if ($c->inspection)
                                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold border {{ $c->inspection->status === 'damage' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200' }}">
                                                        {{ strtoupper($c->inspection->status) }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold bg-slate-50 text-slate-700 border border-slate-200">
                                                        NOT INSPECTED
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 text-xs text-slate-500">
                            Input inspection dilakukan dari aplikasi mobile (per container). Halaman receive hanya menampilkan status + print report.
                        </div>
                    </div>
                @endif

                <!-- Table Section -->
                <div>
                    <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-4">Tag Details</h4>
                    <div class="flex flex-wrap items-center gap-3 mb-3 text-sm text-slate-700">
                        <span class="font-semibold">Total Qty Planned:</span>
                        <span class="px-2 py-1 rounded-lg bg-slate-100 text-slate-800">{{ number_format($totalPlanned) }}</span>
                        <span class="font-semibold">Remaining:</span>
                        <span class="px-2 py-1 rounded-lg bg-green-50 text-green-800">{{ number_format($remainingQty) }}</span>
                        <span class="font-semibold">Input Total:</span>
                        <span class="px-2 py-1 rounded-lg bg-blue-50 text-blue-800" id="input-total">0</span>
                    </div>
                    <div class="overflow-x-auto border border-slate-200 rounded-xl shadow-sm">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Ukuran</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Part Number</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        Tag
                                        <button type="button" id="add-tag-btn" class="ml-3 px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors shadow-sm">
                                            + Add TAG
                                        </button>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Location</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Package</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Qty Goods</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Net Weight (KGM)</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Gross Weight (KGM)</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">QC</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody id="tag-rows" class="divide-y divide-slate-100 bg-white">
                                <!-- Initial row -->
                                <tr class="tag-row hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 text-sm text-slate-700 font-mono">{{ $arrivalItem->size ?? '-' }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900 text-sm">{{ $arrivalItem->part->part_no }}</div>
                                        <div class="text-xs text-slate-600 mt-0.5">{{ $arrivalItem->part->part_name_gci ?? $arrivalItem->part->part_name_vendor }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="text" name="tags[0][tag]" placeholder="TAG-001" class="w-40 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" required />
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="text" name="tags[0][location_code]" placeholder="RACK-A1" class="w-40 uppercase rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" />
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $defaultBundleUnit = strtoupper($arrivalItem->unit_bundle ?? 'PALLET');
                                        @endphp
                                        <div class="flex items-center gap-2">
                                            <input type="number" name="tags[0][bundle_qty]" min="1" value="1" class="w-20 text-center rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" required />
                                            <select name="tags[0][bundle_unit]" class="w-32 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" required>
                                                <option value="PALLET" @selected($defaultBundleUnit === 'PALLET')>PALLET</option>
                                                <option value="BUNDLE" @selected($defaultBundleUnit === 'BUNDLE')>BUNDLE</option>
                                                <option value="BOX" @selected($defaultBundleUnit === 'BOX')>BOX</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <input type="number" name="tags[0][qty]" min="1" placeholder="0" class="w-36 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" required />
                                            <input type="hidden" name="tags[0][qty_unit]" value="{{ strtoupper($arrivalItem->unit_goods ?? 'KGM') }}" />
                                            <div class="text-sm py-2 text-slate-700 font-semibold">{{ strtoupper($arrivalItem->unit_goods ?? 'KGM') }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <input
                                            type="number"
                                            name="tags[0][net_weight]"
                                            step="0.01"
                                            placeholder="0.00"
                                            class="w-32 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2"
                                        />
                                    </td>
                                    <td class="px-6 py-4">
                                        <input
                                            type="number"
                                            name="tags[0][gross_weight]"
                                            step="0.01"
                                            placeholder="0.00"
                                            class="w-32 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2"
                                        />
                                    </td>
                                    <td class="px-6 py-4">
                                        <select name="tags[0][qc_status]" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" required>
                                            <option value="pass">Pass</option>
                                            <option value="reject">Reject</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 text-center"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-200">
                    <a href="{{ route('receives.index') }}" class="px-5 py-2.5 text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors text-sm font-medium">Cancel</a>
                    <button type="submit" class="px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors shadow-sm">
                        Save Receive
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let tagIndex = 1;
        const tagRows = document.getElementById('tag-rows');
        const addTagBtn = document.getElementById('add-tag-btn');
        const size = @json($arrivalItem->size ?? '-');
        const partNo = @json($arrivalItem->part->part_no);
        const partName = @json($arrivalItem->part->part_name_gci ?? $arrivalItem->part->part_name_vendor);
        const remainingQty = {{ (int) $remainingQty }};
        const inputTotalEl = document.getElementById('input-total');
        const receiveForm = document.getElementById('receive-form');
        const defaultWeight = {{ $defaultWeight !== null ? $defaultWeight : 'null' }};
        const defaultBundleUnit = @json(strtoupper($arrivalItem->unit_bundle ?? 'PALLET'));
        const goodsUnit = @json(strtoupper($arrivalItem->unit_goods ?? 'KGM'));

        function updateTotals() {
            const qtyInputs = tagRows.querySelectorAll('input[name$=\"[qty]\"]');
            let total = 0;
            qtyInputs.forEach(input => {
                total += Number(input.value) || 0;
            });
            inputTotalEl.textContent = total;

            const alertId = 'qty-alert';
            let alertEl = document.getElementById(alertId);
            if (!alertEl) {
                alertEl = document.createElement('div');
                alertEl.id = alertId;
                alertEl.className = 'mt-2 text-sm font-semibold text-red-600';
                tagRows.parentElement.parentElement.appendChild(alertEl);
            }

            if (total > remainingQty) {
                alertEl.textContent = `Total qty (${total}) exceeds remaining qty (${remainingQty}).`;
                alertEl.classList.remove('hidden');
            } else {
                alertEl.textContent = '';
                alertEl.classList.add('hidden');
            }

            return total;
        }

        addTagBtn.addEventListener('click', function() {
            const newRow = document.createElement('tr');
            newRow.className = 'tag-row hover:bg-slate-50 transition-colors';
            newRow.innerHTML = `
                <td class="px-6 py-4 text-sm text-slate-700 font-mono">${size}</td>
                <td class="px-6 py-4">
                    <div class="font-semibold text-slate-900 text-sm">${partNo}</div>
                    <div class="text-xs text-slate-600 mt-0.5">${partName}</div>
                </td>
                <td class="px-6 py-4">
                    <input type="text" name="tags[${tagIndex}][tag]" placeholder="TAG-00${tagIndex + 1}" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" required />
                </td>
                <td class="px-6 py-4">
                    <input type="text" name="tags[${tagIndex}][location_code]" placeholder="RACK-A1" class="w-40 uppercase rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" />
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                        <input type="number" name="tags[${tagIndex}][bundle_qty]" min="1" value="1" class="w-20 text-center rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" required />
                        <select name="tags[${tagIndex}][bundle_unit]" class="w-32 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" required>
                            <option value="PALLET" ${defaultBundleUnit === 'PALLET' ? 'selected' : ''}>PALLET</option>
                            <option value="BUNDLE" ${defaultBundleUnit === 'BUNDLE' ? 'selected' : ''}>BUNDLE</option>
                            <option value="BOX" ${defaultBundleUnit === 'BOX' ? 'selected' : ''}>BOX</option>
                        </select>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                        <input type="number" name="tags[${tagIndex}][qty]" min="1" placeholder="0" class="w-28 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" required />
                        <input type="hidden" name="tags[${tagIndex}][qty_unit]" value="${goodsUnit}" />
                        <div class="text-sm py-2 text-slate-700 font-semibold">${goodsUnit}</div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <input type="number" name="tags[${tagIndex}][net_weight]" step="0.01" placeholder="0.00" class="w-32 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" />
                </td>
                <td class="px-6 py-4">
                    <input type="number" name="tags[${tagIndex}][gross_weight]" step="0.01" placeholder="0.00" class="w-32 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" />
                </td>
                <td class="px-6 py-4">
                    <select name="tags[${tagIndex}][qc_status]" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" required>
                        <option value="pass">Pass</option>
                        <option value="reject">Reject</option>
                    </select>
                </td>
                <td class="px-6 py-4 text-center">
                    <button type="button" class="remove-tag px-3 py-1.5 text-red-600 hover:bg-red-50 hover:text-red-700 text-sm font-medium rounded-lg transition-colors">Remove</button>
                </td>
            `;
            
            tagRows.appendChild(newRow);
            tagIndex++;

            // Bind remove event
            newRow.querySelector('.remove-tag').addEventListener('click', function() {
                newRow.remove();
                updateTotals();
            });

            // Bind qty change
            newRow.querySelector('input[name$="[qty]"]').addEventListener('input', updateTotals);

            updateTotals();
        });

        // Bind initial qty input
        tagRows.querySelector('input[name$="[qty]"]').addEventListener('input', updateTotals);
        updateTotals();

        receiveForm.addEventListener('submit', function(event) {
            const total = updateTotals();
            if (total > remainingQty) {
                event.preventDefault();
                const firstQty = tagRows.querySelector('input[name$="[qty]"]');
                if (firstQty) firstQty.focus();
            }
        });
    </script>
</x-app-layout>
