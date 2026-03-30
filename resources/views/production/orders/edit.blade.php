<x-app-layout>
    <x-slot name="header">
        Update Production Order
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <div class="bg-white border rounded-lg shadow-sm p-6">
            <form action="{{ route('production.orders.update', $order) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700">Production Order Number</label>
                    <input type="text" name="production_order_number"
                        value="{{ old('production_order_number', $order->production_order_number) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Part (FG/WIP)</label>
                    <select id="part_select" name="gci_part_id" data-remote="true"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required>
                        <option value="{{ $order->gci_part_id }}" selected>{{ $order->part?->part_no }} - {{ $order->part?->part_name }}</option>
                    </select>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const sel = document.getElementById('part_select');
                        if (window.initRemoteTomSelect) {
                            window.initRemoteTomSelect(sel, "{{ route('gci-parts.search') }}", {
                                placeholder: 'Search for part number or name...',
                                onChange: function (value) {
                                    if (!value) return;

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
                        <label class="block text-sm font-medium text-gray-700">Process</label>
                        <input type="text" name="process_name" value="{{ old('process_name', $order->process_name) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="ex: PRESS / ASSY">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Machine</label>
                        <select name="machine_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Select Machine --</option>
                            @foreach ($machines as $machine)
                                <option value="{{ $machine->id }}" @selected(old('machine_id', $order->machine_id) == $machine->id)>{{ $machine->code }} - {{ $machine->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Dies</label>
                    <input type="text" name="die_name" value="{{ old('die_name', $order->die_name) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="ex: DIES-01">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Planned Quantity</label>
                    <input type="number" name="qty_planned" step="0.01"
                        value="{{ old('qty_planned', $order->qty_planned) }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Plan Date</label>
                    <input type="date" name="plan_date"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        required value="{{ old('plan_date', \Illuminate\Support\Carbon::parse($order->plan_date)->format('Y-m-d')) }}">
                </div>

                <div id="related-so-section" class="border-t pt-4 mt-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Related SO (Incoming RM)</label>
                    <p class="text-xs text-gray-500 mb-3">Auto-suggest berdasarkan BOM. Bisa diedit.</p>
                    <div id="so-checkboxes" class="space-y-2 max-h-48 overflow-y-auto bg-gray-50 rounded-md p-3">
                        <p class="text-xs text-gray-400 italic">Loading related SO...</p>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const partSelect = document.getElementById('part_select');
                        const soContainer = document.getElementById('so-checkboxes');
                        const selectedArrivalIds = @json($order->arrivals->pluck('id')->map(fn ($id) => (int) $id)->values());
                        let lastPartId = '';

                        function renderArrivals(arrivals) {
                            if (!arrivals.length) {
                                soContainer.innerHTML = '<p class="text-xs text-gray-400 italic">Tidak ada SO terkait untuk part ini.</p>';
                                return;
                            }

                            soContainer.innerHTML = arrivals.map(a => {
                                const checked = selectedArrivalIds.includes(Number(a.id)) ? 'checked' : '';
                                return `
                                    <label class="flex items-center gap-2 text-sm bg-white rounded px-3 py-2 border hover:bg-blue-50 cursor-pointer">
                                        <input type="checkbox" name="arrival_ids[]" value="${a.id}" ${checked}
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="font-bold text-emerald-700">${a.transaction_no ?? '-'}</span>
                                        <span class="text-gray-500">${a.arrival_no ?? '-'}</span>
                                        <span class="text-gray-400 text-xs ml-auto">${a.invoice_no || ''}</span>
                                    </label>
                                `;
                            }).join('');
                        }

                        function fetchSuggestions(partId) {
                            soContainer.innerHTML = '<p class="text-xs text-gray-400">Loading...</p>';
                            fetch(`/api/suggest-arrivals/${partId}`)
                                .then(r => r.json())
                                .then(arrivals => renderArrivals(arrivals))
                                .catch(() => {
                                    soContainer.innerHTML = '<p class="text-xs text-red-400">Error loading suggestions.</p>';
                                });
                        }

                        setInterval(() => {
                            const val = partSelect.value;
                            if (val && val !== lastPartId) {
                                lastPartId = val;
                                fetchSuggestions(val);
                            }
                        }, 500);

                        if (partSelect.value) {
                            lastPartId = partSelect.value;
                            fetchSuggestions(partSelect.value);
                        }
                    });
                </script>

                <div class="flex justify-between gap-3 pt-4 border-t">
                    <form action="{{ route('production.orders.cancel', $order) }}" method="POST"
                        onsubmit="return confirm('Cancel WO ini? Material reserve akan dikembalikan.');">
                        @csrf
                        <button type="submit"
                            class="px-4 py-2 border border-red-200 text-red-600 rounded-lg hover:bg-red-50">
                            Cancel WO
                        </button>
                    </form>

                    <div class="flex gap-3">
                        <a href="{{ route('production.orders.show', $order) }}"
                            class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-50">Back</a>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Update Order
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
