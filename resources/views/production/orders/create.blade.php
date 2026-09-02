<x-app-layout>
    <x-slot name="header">
        Create Production Order
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <div class="bg-white border rounded-lg shadow-sm p-6">
            <form action="{{ route('production.orders.store') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700" for="production_order_number">Production Order Number</label>
                    <input type="text" name="production_order_number" id="production_order_number"
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700" for="part_select">Part (FG/WIP)</label>
                    <select id="part_select" name="gci_part_id" data-remote="true"
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                                const machineSelect = document.querySelector('select[name="machine_id"]');

                                                if (data.bom.process_name) {
                                                    processInput.value = data.bom.process_name;
                                                    processInput.classList.add('bg-green-50');
                                                }
                                                if (data.bom.machine_id) {
                                                    machineSelect.value = data.bom.machine_id;
                                                    machineSelect.classList.add('bg-green-50');
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
                        <label class="block text-sm font-medium text-slate-700" for="process_name">Process</label>
                        <input type="text" name="process_name" id="process_name" value="{{ old('process_name') }}"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="ex: PRESS / ASSY">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700" for="machine_id">Machine</label>
                        <select name="machine_id" id="machine_id"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Select Machine --</option>
                            @foreach ($machines as $machine)
                                <option value="{{ $machine->id }}" @selected(old('machine_id') == $machine->id)>{{ $machine->code }} - {{ $machine->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700" for="die_name">Dies</label>
                    <input type="text" name="die_name" id="die_name" value="{{ old('die_name') }}"
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="ex: DIES-01">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700" for="qty_planned">Planned Quantity</label>
                    <input type="number" name="qty_planned" id="qty_planned" step="0.01"
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700" for="plan_date">Plan Date</label>
                    <input type="date" name="plan_date" id="plan_date"
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required value="{{ date('Y-m-d') }}">
                </div>

                {{-- Related SO (Incoming RM) — Traceability --}}
                <div id="related-so-section" class="border-t pt-4 mt-2">
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="inline h-4 w-4 mb-0.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                        Related SO (Incoming RM)
                    </label>
                    <p class="text-xs text-slate-500 mb-3">Auto-suggest berdasarkan BOM. Bisa diedit.</p>
                    <div id="so-checkboxes" class="space-y-2 max-h-48 overflow-y-auto bg-slate-50 rounded-xl p-3">
                        <p class="text-xs text-slate-400 italic">Pilih Part terlebih dahulu...</p>
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
                            soContainer.innerHTML = '<p class="text-xs text-slate-400">Loading...</p>';
                            fetch(`/api/suggest-arrivals/${partId}`)
                                .then(r => r.json())
                                .then(arrivals => {
                                    if (!arrivals.length) {
                                        soContainer.innerHTML = '<p class="text-xs text-slate-400 italic">Tidak ada SO terkait untuk part ini.</p>';
                                        return;
                                    }
                                    soContainer.innerHTML = arrivals.map(a => `
                                        <label class="flex items-center gap-2 text-sm bg-white rounded px-3 py-2 border hover:bg-blue-50 cursor-pointer">
                                            <input type="checkbox" name="arrival_ids[]" value="${a.id}" checked
                                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="font-bold text-emerald-700">${a.transaction_no}</span>
                                            <span class="text-slate-500">${a.arrival_no}</span>
                                            <span class="text-slate-400 text-xs ml-auto">${a.invoice_no || ''}</span>
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
                        class="px-4 py-2 border rounded-lg text-slate-600 hover:bg-slate-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Create
                        Order</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>