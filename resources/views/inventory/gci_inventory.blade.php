<x-app-layout>
    <x-slot name="header">
        Inventory • GCI Inventory
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
                {{-- Classification Tabs --}}
                <div class="flex items-center gap-1 border-b border-slate-200 pb-3">
                    @php
                        $tabs = ['' => 'ALL', 'FG' => 'FG', 'WIP' => 'WIP', 'RM' => 'RM'];
                    @endphp
                    @foreach($tabs as $val => $label)
                        <a href="{{ route('inventory.gci.index', array_merge(request()->except(['classification', 'page']), $val ? ['classification' => $val] : [])) }}"
                            class="px-4 py-2 rounded-t-xl text-sm font-bold transition-all
                                    {{ $classification === $val ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                {{-- Filters --}}
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" action="{{ route('inventory.gci.index') }}"
                        class="flex flex-wrap items-end gap-3">
                        @if($classification)
                            <input type="hidden" name="classification" value="{{ $classification }}">
                        @endif
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Search</label>
                            <input name="search" value="{{ $search }}" class="mt-1 rounded-xl border-slate-200"
                                placeholder="Part no / name / model">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Status</label>
                            <select name="status" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                <option value="active" @selected($status === 'active')>active</option>
                                <option value="inactive" @selected($status === 'inactive')>inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Rows</label>
                            <select name="per_page" class="mt-1 rounded-xl border-slate-200">
                                @foreach([25, 50, 100, 200] as $n)
                                    <option value="{{ $n }}" @selected((int) $perPage === $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                    </form>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('inventory.gci.export', array_filter(['classification' => $classification, 'status' => $status, 'search' => $search])) }}"
                            class="px-4 py-2 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Export Excel
                        </a>
                        <a href="{{ route('inventory.transfers.index') }}"
                            class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold">
                            Inventory Transfers
                        </a>
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Part No</th>
                                <th class="px-4 py-3 text-left font-semibold">Name</th>
                                <th class="px-4 py-3 text-left font-semibold">Model</th>
                                <th class="px-4 py-3 text-left font-semibold">Class</th>
                                <th class="px-4 py-3 text-right font-semibold">On Hand</th>
                                <th class="px-4 py-3 text-right font-semibold">On Order</th>
                                <th class="px-4 py-3 text-left font-semibold">Default Loc</th>
                                <th class="px-4 py-3 text-left font-semibold">As Of</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($rows as $row)
                            @php($p = $row->part)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-mono font-semibold text-slate-900">{{ $p?->part_no ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $p?->customer?->name ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $p?->part_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $p?->model ?? '-' }}</td>
                                <td class="px-4 py-3 font-mono text-xs">
                                    @php($cls = strtoupper((string) ($p?->classification ?? '-')))
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-bold
                                            {{ $cls === 'FG' ? 'bg-blue-100 text-blue-700' : ($cls === 'WIP' ? 'bg-amber-100 text-amber-700' : ($cls === 'RM' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600')) }}">
                                        {{ $cls }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900">
                                    {{ formatNumber((float) ($row->on_hand ?? 0)) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-slate-700">
                                    {{ formatNumber((float) ($row->on_order ?? 0)) }}</td>
                                <td class="px-4 py-3">
                                    <input type="text" value="{{ $p?->default_location ?? '' }}"
                                        class="border border-slate-200 rounded-lg px-2 py-1 text-xs w-24 font-mono focus:outline-none focus:border-indigo-400 location-input"
                                        data-part-id="{{ $p?->id }}" placeholder="Loc..." onchange="saveLocation(this)">
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $row->as_of_date ? \Carbon\Carbon::parse($row->as_of_date)->format('Y-m-d') : '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-slate-500">No data.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $rows->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        async function saveLocation(input) {
            const partId = input.dataset.partId;
            if (!partId) return;

            input.style.background = '#fef9c3';

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
                        default_location: input.value.trim() || null,
                    }),
                });

                const data = await res.json();
                if (data.success) {
                    input.style.background = '#dcfce7';
                    if (data.default_location) {
                        input.value = data.default_location;
                    }
                    setTimeout(() => { input.style.background = ''; }, 800);
                } else {
                    input.style.background = '#fee2e2';
                    setTimeout(() => { input.style.background = ''; }, 1500);
                }
            } catch (e) {
                console.error('Save location failed:', e);
                input.style.background = '#fee2e2';
                setTimeout(() => { input.style.background = ''; }, 1500);
            }
        }
    </script>
</x-app-layout>