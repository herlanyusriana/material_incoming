<x-app-layout>
    <x-slot name="header">
        Create Production Order
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <div class="bg-white border rounded-lg shadow-sm p-6">
            <form action="{{ route('production.orders.store') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700">Production Order Number</label>
                    <input type="text" name="production_order_number"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Part (FG/WIP)</label>
                    <select id="part_select" name="gci_part_id" data-remote="true"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required></select>
                </div>


                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const sel = document.getElementById('part_select');
                        if (window.initRemoteTomSelect) {
                            const tomSelect = window.initRemoteTomSelect(sel, "{{ route('gci-parts.search') }}", {
                                placeholder: 'Search for part number or name...',
                                onChange: function (value) {
                                    if (!value) return;

                                    // Fetch BOM data for selected part
                                    fetch(`/api/gci-parts/${value}/bom-info`)
                                        .then(res => res.json())
                                        .then(data => {
                                            if (data.success && data.bom) {
                                                const processInput = document.querySelector('input[name="process_name"]');
                                                const machineInput = document.querySelector('input[name="machine_name"]');

                                                if (data.bom.process_name) {
                                                    processInput.value = data.bom.process_name;
                                                    processInput.classList.add('bg-green-50');
                                                }
                                                if (data.bom.machine_name) {
                                                    machineInput.value = data.bom.machine_name;
                                                    machineInput.classList.add('bg-green-50');
                                                }
                                            }
                                        })
                                        .catch(err => console.error('Failed to fetch BOM info:', err));
                                }
                            });
                        }
                    });
                </script>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Process</label>
                        <input type="text" name="process_name" value="{{ old('process_name') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="ex: PRESS / ASSY">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Machine</label>
                        <input type="text" name="machine_name" value="{{ old('machine_name') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="ex: LINE-01 / MACHINE-A">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Dies</label>
                    <input type="text" name="die_name" value="{{ old('die_name') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="ex: DIES-01">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Planned Quantity</label>
                    <input type="number" name="qty_planned" step="0.01"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Plan Date</label>
                    <input type="date" name="plan_date"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required value="{{ date('Y-m-d') }}">
                </div>

                {{-- Related SO (Incoming RM) — Traceability --}}
                <div id="related-so-section" class="border-t pt-4 mt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">🔗 Related SO (Incoming RM)</label>
                    <p class="text-xs text-gray-500 mb-3">Auto-suggest berdasarkan BOM. Bisa diedit.</p>
                    <div id="so-checkboxes" class="space-y-2 max-h-48 overflow-y-auto bg-gray-50 rounded-md p-3">
                        <p class="text-xs text-gray-400 italic">Pilih Part terlebih dahulu...</p>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const partSelect = document.getElementById('part_select');
                        const soContainer = document.getElementById('so-checkboxes');

                        // Watch for part selection via TomSelect onChange (already defined above)
                        // We'll use MutationObserver or poll for value changes
                        let lastPartId = '';
                        setInterval(() => {
                            const val = partSelect.value;
                            if (val && val !== lastPartId) {
                                lastPartId = val;
                                fetchSuggestions(val);
                            }
                        }, 500);

                        function fetchSuggestions(partId) {
                            soContainer.innerHTML = '<p class="text-xs text-gray-400">Loading...</p>';
                            fetch(`/api/suggest-arrivals/${partId}`)
                                .then(r => r.json())
                                .then(arrivals => {
                                    if (!arrivals.length) {
                                        soContainer.innerHTML = '<p class="text-xs text-gray-400 italic">Tidak ada SO terkait untuk part ini.</p>';
                                        return;
                                    }
                                    soContainer.innerHTML = arrivals.map(a => `
                                        <label class="flex items-center gap-2 text-sm bg-white rounded px-3 py-2 border hover:bg-blue-50 cursor-pointer">
                                            <input type="checkbox" name="arrival_ids[]" value="${a.id}" checked
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="font-bold text-emerald-700">${a.transaction_no}</span>
                                            <span class="text-gray-500">${a.arrival_no}</span>
                                            <span class="text-gray-400 text-xs ml-auto">${a.invoice_no || ''}</span>
                                        </label>
                                    `).join('');
                                })
                                .catch(() => {
                                    soContainer.innerHTML = '<p class="text-xs text-red-400">Error loading suggestions.</p>';
                                });
                        }
                    });
                </script>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="{{ route('production.orders.index') }}"
                        class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Create
                        Order</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>