<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            <h2 class="text-2xl font-semibold text-gray-900">Part Number Management</h2>
            <p class="text-sm text-gray-500">Kelola registrasi part number beserta daftar yang sudah tercatat.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif
            
            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @php
                $vendorMap = $vendors->pluck('vendor_name', 'id');
                $filterVendorName = $vendorMap[$vendorId ?? null] ?? '';
            @endphp

            <section class="bg-white border rounded-xl shadow-sm p-6 space-y-6">
                <div class="space-y-2">
                    <h3 class="text-lg font-semibold text-gray-900">Existing Part Numbers</h3>
                    <p class="text-sm text-gray-500">Gunakan filter untuk mempercepat pencarian part.</p>
                </div>

                <form method="GET" id="parts-filter-form" class="grid gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <x-input type="search" name="q" label="Search" placeholder="Cari part..." :value="$search" :preserve-old="false" />
                    </div>
                    <div class="space-y-1">
                        <label for="filter-vendor-name" class="block text-sm font-medium text-gray-700">Vendor</label>
                        <div class="relative">
                            <input
                                type="text"
                                id="filter-vendor-name"
                                name="vendor_name_display"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Ketik nama vendor..."
                                autocomplete="off"
                                value="{{ $filterVendorName }}"
                            />
                            <div id="filter-vendor-suggestions" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden"></div>
                        </div>
                        <input type="hidden" name="vendor_id" id="filter-vendor-id" value="{{ $vendorId }}">
                    </div>
                    <div>
                        <x-select name="status_filter" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" :value="$statusFilter" placeholder="Semua status" :preserve-old="false" />
                    </div>
                    <div class="md:col-span-4 flex justify-end gap-3">
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z" />
                            </svg>
                            Apply Filters
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('parts.export') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors text-sm whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0-3-3m3 3 3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                            </svg>
                            Export
                        </a>
                        <button type="button" onclick="document.getElementById('import-part-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors text-sm whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-6m0 0 3 3m-3-3-3 3m8-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            Import
                        </button>
                    </div>
                </form>

                @php
                    $groupedParts = $parts->getCollection()->groupBy(function ($part) {
                        return $part->part_name_vendor ?: 'Unspecified Vendor Part';
                    });
                @endphp

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">GCI Part Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Part Number</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Size</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">HS Code</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">QC</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Price / UOM</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($groupedParts as $vendorPartName => $group)
                                @php
                                    $statusCounts = $group->groupBy('status')->map(fn($items) => $items->count());
                                    $primaryVendor = $group->first();
                                @endphp
                                <tr class="bg-slate-50/70">
                                    <td colspan="7" class="px-4 py-3">
                                        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                            <div>
                                                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Vendor Part Name</p>
                                                <p class="text-base font-semibold text-slate-900">{{ $vendorPartName }}</p>
                                                <p class="text-xs text-slate-500">
                                                    Vendor: {{ optional($group->first()->vendor)->vendor_name ?? 'Unassigned Vendor' }} &bull;
                                                    {{ $group->count() }} GCI {{ $group->count() === 1 ? 'name' : 'names' }}
                                                </p>
                                            </div>
                                            <div class="flex flex-wrap gap-2 text-xs text-slate-500">
                                                @foreach ($statusCounts as $status => $count)
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700">
                                                        {{ ucfirst($status) }}: {{ $count }}
                                                    </span>
                                                @endforeach
                                                {{-- Registration moved to Register Part page --}}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @foreach ($group as $part)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-4 text-sm text-slate-800">
                                            <div class="ml-6 pl-4 border-l-2 border-slate-100">
                                                <p class="font-semibold text-slate-900">{{ $part->part_name_gci }}</p>
                                                <p class="text-xs text-slate-500">Updated: {{ $part->updated_at?->format('d M Y') ?? '-' }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-700">{{ $part->part_no }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-700">{{ $part->register_no }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $part->hs_code ?? '-' }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-700 font-semibold">{{ strtoupper((string) ($part->quality_inspection ?? '')) === 'YES' ? 'YES' : '-' }}</td>
                                        <td class="px-4 py-4 text-sm text-slate-700">
                                            @if($part->price > 0)
                                                <div class="font-medium text-slate-900">{{ number_format($part->price, 0) }}</div>
                                                <div class="text-xs text-slate-500">{{ $part->uom ? '/ ' . $part->uom : '' }}</div>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $part->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                                {{ ucfirst($part->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-right text-sm">
                                            <div class="inline-flex items-center gap-3">
                                                <a href="{{ route('parts.edit', $part) }}" class="text-blue-600 hover:text-blue-700 font-medium">Edit</a>
                                                <form method="POST" action="{{ route('parts.destroy', $part) }}" onsubmit="return confirm('Delete this part?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-700 font-medium">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="py-12 text-center text-slate-500">
                                            <p class="text-lg font-semibold mb-2">No part numbers found</p>
                                            <p class="text-sm">Use the form above to register the first record.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    {{ $parts->links() }}
                </div>
            </section>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="import-part-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                <h3 class="text-lg font-bold text-slate-900">Import Parts</h3>
                <button type="button" onclick="document.getElementById('import-part-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <form action="{{ route('parts.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Upload Excel File</label>
                    <input type="file" name="file" accept=".xlsx,.xls" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="mt-2 text-xs text-slate-500">Accepted formats: .xlsx, .xls (Max: 2MB)</p>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <p class="text-xs text-blue-800 font-medium mb-1">Kolom wajib (nama harus sama):</p>
                    <p class="text-xs text-blue-700">vendor, vendor_type, part_no, size</p>
                    <p class="text-xs text-blue-800 font-medium mt-2 mb-1">Kolom opsional:</p>
                    <p class="text-xs text-blue-700">part_name_vendor, part_name_gci, hs_code, quality_inspection, status</p>
                    <p class="text-xs text-blue-600 mt-1">Tip: Klik Export untuk dapat template yang sesuai.</p>
                </div>
                
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('import-part-modal').classList.add('hidden')" class="flex-1 px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        Upload & Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const vendorsData = @json($vendors->map(fn($v) => ['id' => $v->id, 'name' => $v->vendor_name])->values());

        function attachTypeahead(inputId, hiddenId, boxId, options = {}) {
            const { suggestionsOnFocus = false, onSelected = null, autoSubmitFormId = null } = options;
            const input = document.getElementById(inputId);
            const hidden = document.getElementById(hiddenId);
            const box = document.getElementById(boxId);
            if (!input || !hidden || !box) return;

            function submitIfNeeded() {
                if (autoSubmitFormId) {
                    const form = document.getElementById(autoSubmitFormId);
                    if (form) form.submit();
                }
            }

            function renderSuggestions(query, force = false) {
                const q = query.toLowerCase();
                if (!q && !force) {
                    box.classList.add('hidden');
                    return;
                }

                const matches = vendorsData
                    .filter(v => force ? true : v.name.toLowerCase().includes(q))
                    .slice(0, 8);

                if (!matches.length) {
                    box.innerHTML = '<div class="px-4 py-2 text-gray-500 text-sm italic">Tidak ada vendor ditemukan</div>';
                    box.classList.remove('hidden');
                    return;
                }

                box.innerHTML = matches.map(v => {
                    const idx = v.name.toLowerCase().indexOf(q);
                    let highlighted = v.name;
                    if (idx !== -1) {
                        highlighted = v.name.substring(0, idx) +
                            '<span class="font-semibold text-blue-600">' +
                            v.name.substring(idx, idx + q.length) +
                            '</span>' +
                            v.name.substring(idx + q.length);
                    }
                    return `<div class="px-4 py-2 cursor-pointer hover:bg-blue-50 border-b border-gray-100 last:border-0" data-id="${v.id}" data-name="${v.name}">${highlighted}</div>`;
                }).join('');

                box.classList.remove('hidden');
            }

            input.addEventListener('input', (e) => {
                hidden.value = '';
                renderSuggestions(e.target.value.trim());
            });

            box.addEventListener('click', (e) => {
                const item = e.target.closest('[data-id]');
                if (!item) return;
                input.value = item.dataset.name;
                hidden.value = item.dataset.id;
                box.classList.add('hidden');
                if (typeof onSelected === 'function') onSelected(item.dataset);
                submitIfNeeded();
            });

            document.addEventListener('click', (e) => {
                if (!box.contains(e.target) && !input.contains(e.target)) {
                    box.classList.add('hidden');
                }
            });

            input.addEventListener('focus', () => {
                if (input.value.trim().length > 0) {
                    renderSuggestions(input.value.trim());
                } else if (suggestionsOnFocus) {
                    renderSuggestions('', true);
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    box.classList.add('hidden');
                }
                if (e.key === 'Enter') {
                    const first = box.querySelector('[data-id]');
                    if (first) {
                        e.preventDefault();
                        input.value = first.dataset.name;
                        hidden.value = first.dataset.id;
                        box.classList.add('hidden');
                        if (typeof onSelected === 'function') onSelected(first.dataset);
                        submitIfNeeded();
                    }
                }
            });
        }

        attachTypeahead('filter-vendor-name', 'filter-vendor-id', 'filter-vendor-suggestions', {
            suggestionsOnFocus: true,
            autoSubmitFormId: 'parts-filter-form'
        });
    </script>
</x-app-layout>
