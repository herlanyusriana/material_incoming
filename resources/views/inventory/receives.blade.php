<x-app-layout>
    <x-slot name="header">
        Inventory (Receives)
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex flex-wrap items-end gap-3" id="filterForm">
                        <!-- Live Search with Autocomplete -->
                        <div class="relative" style="min-width: 300px;">
                            <label class="text-xs font-semibold text-slate-600">Search</label>
                            <input 
                                type="text" 
                                id="searchInput"
                                name="search"
                                value="{{ request('search', '') }}"
                                placeholder="Part No, Name, Tag, or Invoice..."
                                class="mt-1 rounded-xl border-slate-200 w-full"
                                autocomplete="off"
                            >
                            <!-- Suggestions Dropdown -->
                            <div id="suggestions" class="absolute z-10 hidden w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Status</label>
                            <select name="qc_status" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                <option value="pass" @selected($qcStatus === 'pass')>Good</option>
                                <option value="reject" @selected($qcStatus === 'reject')>No Good</option>
                            </select>
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                        @if(request('search') || request('qc_status'))
                            <a href="{{ route('inventory.receives') }}" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold">Clear</a>
                        @endif
                    </form>

                    <a href="{{ route('inventory.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold">
                        Inventory Summary
                    </a>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">No</th>
                                <th class="px-4 py-3 text-left font-semibold">Classification</th>
                                <th class="px-4 py-3 text-left font-semibold">Part Number</th>
                                <th class="px-4 py-3 text-left font-semibold">Description</th>
                                <th class="px-4 py-3 text-left font-semibold">Model</th>
                                <th class="px-4 py-3 text-left font-semibold">UOM</th>
                                <th class="px-4 py-3 text-left font-semibold">Storage Location</th>
                                <th class="px-4 py-3 text-left font-semibold">Tag #</th>
                                <th class="px-4 py-3 text-right font-semibold">Bundle Qty</th>
                                <th class="px-4 py-3 text-right font-semibold">Quantity</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                                @forelse ($receives as $idx => $r)
                                    @php
                                        $part = $r->arrivalItem?->part;
                                        $arrivalItem = $r->arrivalItem;
                                        $arrival = $arrivalItem?->arrival;
                                        $classification = strtoupper(trim((string) ($arrivalItem?->material_group ?? 'INCOMING')));
                                        $statusLabel = $r->qc_status === 'pass' ? 'Good' : 'No Good';
                                        $goodsUnit = strtoupper(trim((string) ($arrivalItem?->unit_goods ?? $r->qty_unit ?? '')));
                                        $displayQty = (float) ($r->qty ?? 0);
                                        $displayUom = strtoupper((string) ($r->qty_unit ?? '-'));
                                        if ($goodsUnit === 'COIL') {
                                            $displayQty = (float) ($r->net_weight ?? $r->weight ?? $r->qty ?? 0);
                                            $displayUom = 'KGM';
                                        }
                                    @endphp
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 text-slate-600">{{ $receives->firstItem() + $idx }}</td>
                                        <td class="px-4 py-3">{{ $classification !== '' ? $classification : 'INCOMING' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900">{{ $part?->part_no ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $arrival?->invoice_no ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">{{ $part?->part_name_gci ?? ($part?->part_name_vendor ?? '-') }}</td>
                                    <td class="px-4 py-3">{{ $arrivalItem?->size ?? '-' }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $displayUom }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $locCode = strtoupper(trim((string) ($r->location_code ?? '')));
                                            $loc = ($locCode !== '' && isset($locationMap)) ? ($locationMap[$locCode] ?? null) : null;
                                            $meta = [];
                                            if ($loc?->class) $meta[] = 'Class ' . $loc->class;
                                            if ($loc?->zone) $meta[] = 'Zone ' . $loc->zone;
                                        @endphp
                                        <div class="font-mono text-xs">{{ $locCode !== '' ? $locCode : '-' }}</div>
                                        @if ($meta)
                                            <div class="text-[11px] text-slate-500">{{ implode(' • ', $meta) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $r->tag ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">
                                        {{ number_format((float) ($r->bundle_qty ?? 0), 0) }} {{ strtoupper((string) ($r->bundle_unit ?? '')) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format($displayQty, $goodsUnit === 'COIL' ? 2 : 0) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold {{ $r->qc_status === 'pass' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-4 py-6 text-center text-slate-500">Belum ada receive.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $receives->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const searchInput = document.getElementById('searchInput');
            const suggestionsBox = document.getElementById('suggestions');
            let debounceTimer;
            let currentFocus = -1;

            // Debounced search
            searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    suggestionsBox.classList.add('hidden');
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetch(`{{ route('inventory.receives.search') }}?q=${encodeURIComponent(query)}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.length === 0) {
                                suggestionsBox.innerHTML = '<div class="px-4 py-3 text-sm text-slate-500">No results found</div>';
                                suggestionsBox.classList.remove('hidden');
                                return;
                            }

                            suggestionsBox.innerHTML = data.map((item, idx) => `
                                <div class="suggestion-item px-4 py-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0" data-index="${idx}">
                                    <div class="font-semibold text-sm text-slate-900">${item.part_no}</div>
                                    <div class="text-xs text-slate-600">${item.part_name}</div>
                                    <div class="text-xs text-slate-500 mt-1">
                                        <span class="font-mono">${item.tag}</span> • 
                                        <span>${item.invoice_no}</span> • 
                                        <span class="font-mono">${item.location_code}</span>
                                    </div>
                                </div>
                            `).join('');
                            
                            suggestionsBox.classList.remove('hidden');
                            currentFocus = -1;

                            // Click handlers
                            document.querySelectorAll('.suggestion-item').forEach(el => {
                                el.addEventListener('click', function() {
                                    const idx = parseInt(this.dataset.index);
                                    const selected = data[idx];
                                    searchInput.value = selected.part_no;
                                    suggestionsBox.classList.add('hidden');
                                    document.getElementById('filterForm').submit();
                                });
                            });
                        })
                        .catch(err => console.error('Search error:', err));
                }, 300);
            });

            // Keyboard navigation
            searchInput.addEventListener('keydown', function(e) {
                const items = suggestionsBox.querySelectorAll('.suggestion-item');
                if (items.length === 0) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    currentFocus++;
                    if (currentFocus >= items.length) currentFocus = 0;
                    setActive(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    currentFocus--;
                    if (currentFocus < 0) currentFocus = items.length - 1;
                    setActive(items);
                } else if (e.key === 'Enter' && currentFocus > -1) {
                    e.preventDefault();
                    items[currentFocus].click();
                }
            });

            function setActive(items) {
                items.forEach((item, idx) => {
                    if (idx === currentFocus) {
                        item.classList.add('bg-slate-100');
                    } else {
                        item.classList.remove('bg-slate-100');
                    }
                });
            }

            // Close suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                    suggestionsBox.classList.add('hidden');
                }
            });
        })();
    </script>
</x-app-layout>
