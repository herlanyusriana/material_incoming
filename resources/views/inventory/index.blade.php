<x-app-layout>
    <x-slot name="header">
        Master Inventory
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-800 flex items-center gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-800 flex items-center gap-3 shadow-sm">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            <!-- Top Actions & Navigation -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
                <!-- Classification Tabs -->
                <div class="flex p-1 bg-slate-100 rounded-xl w-full md:w-auto overflow-x-auto">
                    <a href="{{ route('inventory.index', ['tab' => 'rm']) }}"
                        class="px-6 py-2.5 rounded-lg text-sm font-bold transition-all whitespace-nowrap flex-1 md:flex-none text-center {{ $activeTab === 'rm' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50' }}">
                        <span class="inline-flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            RM
                            <span class="text-xs font-normal text-slate-400">({{ number_format($summary->rm_count ?? 0) }})</span>
                        </span>
                    </a>
                    <a href="{{ route('inventory.index', ['tab' => 'wip']) }}"
                        class="px-6 py-2.5 rounded-lg text-sm font-bold transition-all whitespace-nowrap flex-1 md:flex-none text-center {{ $activeTab === 'wip' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50' }}">
                        <span class="inline-flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            WIP
                            <span class="text-xs font-normal text-slate-400">({{ number_format($summary->wip_count ?? 0) }})</span>
                        </span>
                    </a>
                    <a href="{{ route('inventory.index', ['tab' => 'fg']) }}"
                        class="px-6 py-2.5 rounded-lg text-sm font-bold transition-all whitespace-nowrap flex-1 md:flex-none text-center {{ $activeTab === 'fg' ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/50' }}">
                        <span class="inline-flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                            FG
                            <span class="text-xs font-normal text-slate-400">({{ number_format($summary->fg_count ?? 0) }})</span>
                        </span>
                    </a>
                </div>

                <!-- Global Actions -->
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('inventory.gci.export', array_filter(['classification' => strtoupper($activeTab), 'status' => $status, 'search' => $search])) }}"
                        class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm transition-all">
                        Export
                    </a>
                    <button onclick="document.getElementById('importModal').classList.remove('hidden')"
                        class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm transition-all">
                        Import
                    </button>
                </div>
            </div>

            <!-- Content Card -->
            <div class="bg-white shadow-sm border border-slate-200 rounded-2xl p-6 space-y-6 transition-all">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-xl font-black text-slate-900">
                            @if($activeTab === 'rm')
                                Raw Material (RM) Inventory
                            @elseif($activeTab === 'wip')
                                Work In Progress (WIP) Inventory
                            @else
                                Finished Goods (FG) Inventory
                            @endif
                        </h2>
                        <p class="text-sm text-slate-500">
                            @if($activeTab === 'rm')
                                Manage raw materials stock levels.
                            @elseif($activeTab === 'wip')
                                Manage semi-finished / work in progress stock levels.
                            @else
                                Manage finished goods stock levels.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Items</div>
                        <div class="mt-2 text-2xl font-black text-slate-900">{{ number_format((int) ($kpi['item_count'] ?? 0)) }}</div>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                        <div class="text-xs font-bold uppercase tracking-wider text-emerald-700">On Hand</div>
                        <div class="mt-2 text-2xl font-black text-emerald-900">{{ formatNumber((float) ($kpi['total_on_hand'] ?? 0)) }}</div>
                    </div>
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                        <div class="text-xs font-bold uppercase tracking-wider text-amber-700">On Order</div>
                        <div class="mt-2 text-2xl font-black text-amber-900">{{ formatNumber((float) ($kpi['total_on_order'] ?? 0)) }}</div>
                    </div>
                    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4">
                        <div class="text-xs font-bold uppercase tracking-wider text-blue-700">Available</div>
                        <div class="mt-2 text-2xl font-black text-blue-900">{{ formatNumber((float) ($kpi['total_available'] ?? 0)) }}</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                        <div class="w-full sm:w-64">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Search Part</label>
                            <input type="text" name="search" value="{{ $search }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Part no / Name / Model...">
                        </div>
                        <div class="w-full sm:w-32">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                            <select name="status"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All</option>
                                <option value="active" @selected($status === 'active')>Active</option>
                                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                            </select>
                        </div>
                        <div class="flex gap-2 w-full sm:w-auto">
                            <button type="submit"
                                class="flex-1 sm:flex-none px-4 py-2 rounded-lg bg-slate-800 text-white font-bold text-sm hover:bg-slate-900 transition-all">Filter</button>
                            @if($search !== '' || $status !== '')
                                <a href="{{ route('inventory.index', ['tab' => $activeTab]) }}"
                                    class="flex-1 sm:flex-none px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-sm text-center transition-all">Reset</a>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="px-4 py-3 text-left font-black text-slate-600 uppercase tracking-wider text-xs">Part No</th>
                                <th class="px-4 py-3 text-left font-black text-slate-600 uppercase tracking-wider text-xs">Name / Model</th>
                                <th class="px-4 py-3 text-center font-black text-slate-600 uppercase tracking-wider text-xs">Class</th>
                                <th class="px-4 py-3 text-left font-black text-slate-600 uppercase tracking-wider text-xs">Batch / Invoice</th>
                                <th class="px-4 py-3 text-right font-black text-slate-600 uppercase tracking-wider text-xs">On Hand</th>
                                <th class="px-4 py-3 text-right font-black text-slate-600 uppercase tracking-wider text-xs">On Order</th>
                                <th class="px-4 py-3 text-right font-black text-slate-600 uppercase tracking-wider text-xs">Available</th>
                                <th class="px-4 py-3 text-right font-black text-slate-600 uppercase tracking-wider text-xs">Locations</th>
                                <th class="px-4 py-3 text-left font-black text-slate-600 uppercase tracking-wider text-xs">Default Loc</th>
                                <th class="px-4 py-3 text-left font-black text-slate-600 uppercase tracking-wider text-xs">As Of</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($rows as $inv)
                                @php($p = $inv)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="font-bold font-mono text-indigo-700">{{ $p?->part_no ?? '-' }}</div>
                                        <div class="text-xs text-slate-400">{{ $p?->customers->pluck('name')->implode(', ') ?: '' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-800">{{ $p?->part_name ?? '-' }}</div>
                                        <div class="text-xs text-slate-500 mt-0.5">{{ $p?->model ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span @class([
                                            'inline-flex items-center px-2.5 py-1 rounded-md text-xs font-black tracking-wider',
                                            'bg-emerald-100 text-emerald-700' => $p?->classification === 'RM',
                                            'bg-amber-100 text-amber-700' => $p?->classification === 'WIP',
                                            'bg-blue-100 text-blue-700' => $p?->classification === 'FG',
                                        ])>
                                            {{ $p?->classification ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="space-y-1">
                                            <div class="font-mono text-xs text-slate-700">{{ $inv->batch_no ?? ($inv->latest_batch_received ?? '-') }}</div>
                                            <div class="text-[11px] text-slate-500">
                                                Invoice:
                                                <span class="font-semibold text-slate-700">{{ $inv->latest_source_invoice_no ?? '-' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-1 rounded shadow-sm border border-slate-200">
                                            {{ formatNumber((float) ($inv->on_hand ?? 0)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-mono text-slate-500">{{ formatNumber((float) ($inv->on_order ?? 0)) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @php($availableQty = (float) ($inv->available_qty ?? 0))
                                        <span @class([
                                            'font-mono font-bold',
                                            'text-red-600' => $availableQty < 0,
                                            'text-slate-900' => $availableQty >= 0,
                                        ])>{{ formatNumber($availableQty) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-mono text-slate-500">{{ number_format((int) ($inv->location_count ?? 0)) }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <select data-part-id="{{ $p?->id }}" onchange="saveLocation(this)"
                                            class="border border-slate-200 rounded-lg px-2 py-1 text-xs font-mono focus:outline-none focus:border-indigo-400 w-28">
                                            <option value="">—</option>
                                            @foreach($warehouseLocations as $loc)
                                                <option value="{{ $loc }}" @selected(($p?->default_location ?? '') === $loc)>{{ $loc }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 text-xs font-medium">
                                        {{ $inv->as_of_date ? \Carbon\Carbon::parse($inv->as_of_date)->format('d M Y') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-12 text-center">
                                        <p class="text-slate-500 font-semibold mb-1">No {{ $classification }} inventory found</p>
                                        <p class="text-xs text-slate-400">Try adjusting your filters.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($rows->hasPages())
                    <div class="pt-4 border-t border-slate-100">
                        {{ $rows->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50" onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-black text-slate-900">Import Inventory</h3>
                <button onclick="document.getElementById('importModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('inventory.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Excel File</label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                        class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>
                <div class="bg-slate-50 rounded-xl p-3 text-xs text-slate-500 space-y-1">
                    <p class="font-bold text-slate-600">Format kolom:</p>
                    <p><span class="font-mono bg-white px-1 rounded">part_no</span> | <span class="font-mono bg-white px-1 rounded">part_name</span> | <span class="font-mono bg-white px-1 rounded">model</span> | <span class="font-mono bg-white px-1 rounded">classification</span> | <span class="font-mono bg-white px-1 rounded">default_location</span> | <span class="font-mono bg-white px-1 rounded">location_code</span> | <span class="font-mono bg-white px-1 rounded">qty</span> | <span class="font-mono bg-white px-1 rounded">batch_no</span></p>
                    <p class="text-slate-400">Qty = target stock (bukan tambahan). Part baru otomatis dibuat jika ada part_name. Gunakan Export untuk template.</p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('importModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-sm">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm">Import</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        async function saveLocation(el) {
            const partId = el.dataset.partId;
            if (!partId) return;

            el.style.background = '#fef9c3';

            try {
                const res = await fetch('{{ route("inventory.gci.update-location") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        gci_part_id: partId,
                        default_location: el.value.trim() || null,
                    }),
                });

                const data = await res.json();
                if (data.success) {
                    el.style.background = '#dcfce7';
                    if (data.synced_qty > 0) {
                        alert('Location saved. ' + data.synced_qty + ' qty synced to Stock by Location.');
                    }
                    setTimeout(() => { el.style.background = ''; }, 800);
                } else {
                    el.style.background = '#fee2e2';
                    setTimeout(() => { el.style.background = ''; }, 1500);
                }
            } catch (e) {
                console.error('Save location failed:', e);
                el.style.background = '#fee2e2';
                setTimeout(() => { el.style.background = ''; }, 1500);
            }
        }
    </script>
</x-app-layout>
