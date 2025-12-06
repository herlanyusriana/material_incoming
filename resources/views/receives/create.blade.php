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
                    <a href="{{ route('receives.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">Back to List</a>
                </div>

                <!-- Information Section -->
                <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                    <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-4">Arrival Information</h4>
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
                            <span class="text-slate-900">= -</span>
                        </div>
                    </div>
                </div>

                <!-- Table Section -->
                <div>
                    <h4 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-4">Tag Details</h4>
                    <div class="overflow-x-auto border border-slate-200 rounded-xl shadow-sm">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Ukuran</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Part Number</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                        TAG
                                        <button type="button" id="add-tag-btn" class="ml-3 px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors shadow-sm">
                                            + Add TAG
                                        </button>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">QTY</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Unit (KG)</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody id="tag-rows" class="divide-y divide-slate-100 bg-white">
                                <!-- Initial row -->
                                <tr class="tag-row hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 text-sm text-slate-700 font-mono">{{ $arrivalItem->size ?? '-' }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900 text-sm">{{ $arrivalItem->part->part_no }}</div>
                                        <div class="text-xs text-slate-600 mt-0.5">{{ $arrivalItem->part->part_name_vendor }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="text" name="tags[0][tag]" placeholder="TAG-001" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" required />
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="number" name="tags[0][qty]" min="1" placeholder="0" class="w-28 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" required />
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="number" name="tags[0][weight]" step="0.01" placeholder="0.00" class="w-32 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" />
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
        const partName = @json($arrivalItem->part->part_name_vendor);

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
                    <input type="number" name="tags[${tagIndex}][qty]" min="1" placeholder="0" class="w-28 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" required />
                </td>
                <td class="px-6 py-4">
                    <input type="number" name="tags[${tagIndex}][weight]" step="0.01" placeholder="0.00" class="w-32 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2" />
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
            });
        });
    </script>
</x-app-layout>
