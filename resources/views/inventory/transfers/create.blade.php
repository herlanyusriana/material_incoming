<x-app-layout>
    <x-slot name="header">
        Inventory • New Transfer
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
                    <h2 class="text-xl font-bold text-slate-900">Transfer Inventory</h2>
                    <p class="text-sm text-slate-600 mt-1">Move inventory from Logistics to Production</p>
                </div>

                <form method="POST" action="{{ route('inventory.transfers.store') }}" class="p-6 space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Source Part (Logistics) --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                From: Logistics Part <span class="text-red-500">*</span>
                            </label>
                            <select name="part_id" 
                                    id="part_id"
                                    required
                                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Select Part --</option>
                                @foreach($parts as $part)
                                    <option value="{{ $part->id }}" 
                                            data-stock="{{ $part->inventory->on_hand ?? 0 }}"
                                            data-partno="{{ strtoupper(trim($part->part_no)) }}"
                                            {{ old('part_id') == $part->id ? 'selected' : '' }}>
                                        {{ $part->part_no }} - {{ $part->part_name_gci }} 
                                        (Stock: {{ formatNumber($part->inventory->on_hand ?? 0) }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-500 mt-1" id="available-stock">Select a part to see available stock</p>
                        </div>

                        {{-- Target Part (Production) --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                To: Production Part <span class="text-red-500">*</span>
                            </label>
                            <select name="gci_part_id" 
                                    id="gci_part_id"
                                    required
                                    class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Select GCI Part --</option>
                                @foreach($gciParts as $gciPart)
                                    <option value="{{ $gciPart->id }}" 
                                            data-partno="{{ strtoupper(trim($gciPart->part_no)) }}"
                                            {{ old('gci_part_id') == $gciPart->id ? 'selected' : '' }}>
                                        {{ $gciPart->part_no }} - {{ $gciPart->part_name }}
                                        @if($gciPart->classification)
                                            [{{ $gciPart->classification }}]
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-amber-600 mt-1 font-semibold" id="part-match-warning" style="display: none;">
                                ⚠️ Warning: Part numbers don't match!
                            </p>
                            <p class="text-xs text-green-600 mt-1 font-semibold" id="part-match-success" style="display: none;">
                                ✓ Part numbers match
                            </p>
                        </div>
                    </div>

                    {{-- Quantity --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Quantity <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               name="qty" 
                               step="0.0001"
                               min="0.0001"
                               value="{{ old('qty') }}"
                               required
                               class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="Enter quantity to transfer">
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">
                            Notes (Optional)
                        </label>
                        <textarea name="notes" 
                                  rows="3"
                                  class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                                  placeholder="Add any notes about this transfer...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <a href="{{ route('inventory.transfers.index') }}" 
                           class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 font-semibold hover:bg-slate-50">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition-colors">
                            Transfer Inventory
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Update available stock display
        document.getElementById('part_id').addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const stock = selected.getAttribute('data-stock') || 0;
            document.getElementById('available-stock').textContent = `Available stock: ${stock}`;
            
            // Check part number match
            checkPartMatch();
        });
        
        // Check part number matching when production part changes
        document.getElementById('gci_part_id').addEventListener('change', function() {
            checkPartMatch();
        });
        
        function checkPartMatch() {
            const logisticsPart = document.getElementById('part_id');
            const productionPart = document.getElementById('gci_part_id');
            const warningEl = document.getElementById('part-match-warning');
            const successEl = document.getElementById('part-match-success');
            
            const logisticsSelected = logisticsPart.options[logisticsPart.selectedIndex];
            const productionSelected = productionPart.options[productionPart.selectedIndex];
            
            const logisticsPartNo = logisticsSelected?.getAttribute('data-partno') || '';
            const productionPartNo = productionSelected?.getAttribute('data-partno') || '';
            
            // Hide both messages initially
            warningEl.style.display = 'none';
            successEl.style.display = 'none';
            
            // Only check if both are selected
            if (logisticsPartNo && productionPartNo) {
                if (logisticsPartNo === productionPartNo) {
                    successEl.style.display = 'block';
                } else {
                    warningEl.style.display = 'block';
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
