<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            <h2 class="text-2xl font-semibold text-gray-900">Part Number Management</h2>
            <p class="text-sm text-gray-500">Kelola registrasi part number beserta daftar yang sudah tercatat.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
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
                $oldVendorName = $vendorMap[old('vendor_id')] ?? '';
                $filterVendorName = $vendorMap[$vendorId ?? null] ?? '';
            @endphp

            <section id="register-part-section" class="max-w-5xl mx-auto p-8 bg-transparent rounded-2xl border border-transparent">
                <div class="mb-6">
                    <h3 class="text-2xl font-semibold text-gray-900">Register New Part Number</h3>
                    <p class="text-sm text-gray-600">Lengkapi informasi vendor, identitas part, dan status operasional.</p>
                </div>

                <form method="POST" action="{{ route('parts.store') }}" class="space-y-6 js-loading-form">
                    @csrf
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-xs font-semibold text-gray-500 tracking-wide mb-4 uppercase">Vendor Information</h2>
                        <div class="space-y-4">
                            <div>
                                <label for="create-vendor-name" class="text-sm font-medium text-gray-700">Vendor</label>
                                <div class="relative mt-1">
                                    <input
                                        type="text"
                                        id="create-vendor-name"
                                        class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        placeholder="Ketik nama vendor..."
                                        autocomplete="off"
                                        value="{{ $oldVendorName }}"
                                        required
                                    >
                                    <div id="create-vendor-suggestions" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden"></div>
                                </div>
                                <input type="hidden" name="vendor_id" id="create-vendor-id" value="{{ old('vendor_id') }}">
                                @error('vendor_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                <p class="text-xs text-gray-500 mt-1">Ketik satu kata, daftar vendor akan muncul.</p>
                            </div>
                        </div>
                    </div>

                    @php
                        $rawSizeInline = old('register_no', old('register_number'));
                        $thicknessInline = old('thickness');
                        $widthInline = old('width');
                        $lengthInline = old('length');
                        $sizeTypeInline = old('size_type');

                        if (!$thicknessInline && !$widthInline && !$sizeTypeInline && $rawSizeInline) {
                            $segmentsInline = preg_split('/[xX]/', $rawSizeInline);
                            $segmentsInline = array_map('trim', $segmentsInline);

                            if (count($segmentsInline) >= 1) {
                                $thicknessInline = $segmentsInline[0];
                            }
                            if (count($segmentsInline) >= 2) {
                                $widthInline = $segmentsInline[1];
                            }
                            if (count($segmentsInline) >= 3) {
                                $lastSegmentInline = strtoupper($segmentsInline[2]);
                                if ($lastSegmentInline === 'C') {
                                    $sizeTypeInline = 'coil';
                                    $lengthInline = '';
                                } else {
                                    $sizeTypeInline = 'sheet';
                                    $lengthInline = $segmentsInline[2];
                                }
                            }
                        }

                        if (!$sizeTypeInline) {
                            $sizeTypeInline = 'sheet';
                        }
                    @endphp

                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-xs font-semibold text-gray-500 tracking-wide mb-4 uppercase">Part Identification</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="part_number" class="text-sm font-medium text-gray-700">Part Number<span class="text-red-500">*</span></label>
                                <input type="text" id="part_number" name="part_number" value="{{ old('part_number', old('part_no')) }}" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm mt-1" placeholder="PN-001" required>
                                <p class="text-xs text-gray-500 mt-1">Gunakan kode internal singkat.</p>
                                @error('part_number') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                @error('part_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Size<span class="text-red-500">*</span></label>
                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 mt-1">
                                    <div>
                                        <input
                                            type="text"
                                            id="inline_size_thickness"
                                            name="thickness"
                                            value="{{ $thicknessInline }}"
                                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            placeholder="Thickness"
                                        >
                                    </div>
                                    <div>
                                        <input
                                            type="text"
                                            id="inline_size_width"
                                            name="width"
                                            value="{{ $widthInline }}"
                                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            placeholder="Width"
                                        >
                                    </div>
                                    <div>
                                        <input
                                            type="text"
                                            id="inline_size_length"
                                            name="length"
                                            value="{{ $lengthInline }}"
                                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            placeholder="Length"
                                        >
                                    </div>
	                                    <div>
	                                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
	                                            <input
	                                                id="inline_size_is_coil"
	                                                type="checkbox"
	                                                name="inline_is_coil"
	                                                value="1"
	                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
	                                                @checked($sizeTypeInline === 'coil')
	                                            >
	                                            <span>Coil (C)</span>
	                                        </label>
	                                    </div>
	                                </div>
	                                <p class="text-xs text-gray-500 mt-1">
	                                    Preview: <span id="inline-size-preview">{{ $rawSizeInline ?: '-' }}</span>.
	                                    Kalau dicentang, panjang jadi "C".
	                                </p>
                                <input
                                    type="hidden"
                                    id="register_number"
                                    name="register_number"
                                    value="{{ $rawSizeInline }}"
                                >
                                @error('register_number') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                @error('register_no') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-xs font-semibold text-gray-500 tracking-wide mb-4 uppercase">Naming Details</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="vendor_part_name" class="text-sm font-medium text-gray-700">Vendor Part Name<span class="text-red-500">*</span></label>
                                <input type="text" id="vendor_part_name" name="vendor_part_name" value="{{ old('vendor_part_name', old('part_name_vendor')) }}" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm mt-1" placeholder="Nama dari vendor" required>
                                @error('vendor_part_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                @error('part_name_vendor') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="gci_part_name" class="text-sm font-medium text-gray-700">GCI Part Name<span class="text-red-500">*</span></label>
                                <input type="text" id="gci_part_name" name="gci_part_name" value="{{ old('gci_part_name', old('part_name_gci')) }}" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm mt-1" placeholder="Nama internal" required>
                                @error('gci_part_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                @error('part_name_gci') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="mt-4">
                            <label for="hs_code" class="text-sm font-medium text-gray-700">HS Code</label>
                            <input type="text" id="hs_code" name="hs_code" value="{{ old('hs_code') }}" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm mt-1" placeholder="e.g., 7225.99.10">
                            <p class="text-xs text-gray-500 mt-1">Harmonized System code for customs.</p>
                            @error('hs_code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-xs font-semibold text-gray-500 tracking-wide mb-4 uppercase">Operational Status</h2>
                        <div class="space-y-4">
                            <div>
                                <label for="status" class="text-sm font-medium text-gray-700">Status</label>
                                <select id="status" name="status" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm mt-1">
                                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('status') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="part_no" id="hidden-part-no" value="{{ old('part_no', old('part_number')) }}">
                    <input type="hidden" name="register_no" id="hidden-register-no" value="{{ old('register_no', $rawSizeInline) }}">
                    <input type="hidden" name="part_name_vendor" id="hidden-part-name-vendor" value="{{ old('part_name_vendor', old('vendor_part_name')) }}">
                    <input type="hidden" name="part_name_gci" id="hidden-part-name-gci" value="{{ old('part_name_gci', old('gci_part_name')) }}">

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-5 py-3 bg-blue-600 text-white text-sm font-semibold rounded-lg shadow-sm hover:bg-blue-700 transition-colors">
                            Register Part Number
                        </button>
                    </div>
                </form>
            </section>

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
                                    <td colspan="6" class="px-4 py-3">
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
                                                @if($primaryVendor?->vendor_id)
                                                    <button type="button"
                                                        data-fill-create-form
                                                        data-vendor-id="{{ $primaryVendor->vendor_id }}"
                                                        data-vendor-name="{{ e(optional($primaryVendor->vendor)->vendor_name) }}"
                                                        data-vendor-part="{{ e($vendorPartName) }}"
                                                        class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 font-semibold text-blue-700 hover:bg-blue-200 transition">
                                                        + Tambah GCI Name
                                                    </button>
                                                @endif
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
                                    <td colspan="6">
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
                    <p class="text-xs text-blue-700">vendor, part_no, size</p>
                    <p class="text-xs text-blue-800 font-medium mt-2 mb-1">Kolom opsional:</p>
                    <p class="text-xs text-blue-700">part_name_vendor, part_name_gci, hs_code, status</p>
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

        const formFieldSync = [
            ['part_number', 'hidden-part-no'],
            ['vendor_part_name', 'hidden-part-name-vendor'],
            ['gci_part_name', 'hidden-part-name-gci'],
        ];

        formFieldSync.forEach(([sourceId, targetId]) => {
            const source = document.getElementById(sourceId);
            const target = document.getElementById(targetId);
            if (!source || !target) return;
            const syncValue = () => target.value = source.value;
            source.addEventListener('input', syncValue);
            syncValue();
        });

	        (function () {
	            const thicknessInput = document.getElementById('inline_size_thickness');
	            const widthInput = document.getElementById('inline_size_width');
	            const lengthInput = document.getElementById('inline_size_length');
	            const coilCheckbox = document.getElementById('inline_size_is_coil');
	            const previewEl = document.getElementById('inline-size-preview');
	            const visibleRegisterInput = document.getElementById('register_number');
	            const hiddenRegisterInput = document.getElementById('hidden-register-no');

	            if (!thicknessInput || !widthInput || !lengthInput || !coilCheckbox || !previewEl || !visibleRegisterInput || !hiddenRegisterInput) {
	                return;
	            }

	            function updateInlineSize() {
	                const isCoil = coilCheckbox.checked;
	                const thickness = thicknessInput.value.trim();
	                const width = widthInput.value.trim();
	                let length = lengthInput.value.trim();

	                if (isCoil) {
	                    length = 'C';
	                    lengthInput.value = '';
	                    lengthInput.disabled = true;
	                    lengthInput.placeholder = 'C';
	                } else {
	                    lengthInput.disabled = false;
	                    if (lengthInput.placeholder === 'C') {
	                        lengthInput.placeholder = 'Length';
	                    }
	                }

                const parts = [];
                if (thickness) parts.push(thickness);
                if (width) parts.push(width);
                if (length) parts.push(length);

                const sizeString = parts.join(' x ');
                previewEl.textContent = sizeString || '-';
                visibleRegisterInput.value = sizeString;
                hiddenRegisterInput.value = sizeString;
            }

	            ['input', 'change'].forEach(eventName => {
	                thicknessInput.addEventListener(eventName, updateInlineSize);
	                widthInput.addEventListener(eventName, updateInlineSize);
	                lengthInput.addEventListener(eventName, updateInlineSize);
	                coilCheckbox.addEventListener(eventName, updateInlineSize);
	            });

            updateInlineSize();
        })();

        attachTypeahead('create-vendor-name', 'create-vendor-id', 'create-vendor-suggestions', { suggestionsOnFocus: true });
        attachTypeahead('filter-vendor-name', 'filter-vendor-id', 'filter-vendor-suggestions', {
            suggestionsOnFocus: true,
            autoSubmitFormId: 'parts-filter-form'
        });

        document.querySelectorAll('.js-loading-form').forEach((form) => {
            form.addEventListener('submit', () => {
                const button = form.querySelector('button[type="submit"]');
                if (!button) return;
                button.disabled = true;
                button.classList.add('opacity-70', 'cursor-not-allowed');
                const textSpan = button.querySelector('[data-button-text]');
                if (textSpan) {
                    textSpan.textContent = 'Processing...';
                }
            });
        });

        document.querySelectorAll('[data-fill-create-form]').forEach((button) => {
            button.addEventListener('click', () => {
                const vendorNameInput = document.getElementById('create-vendor-name');
                const vendorHiddenInput = document.getElementById('create-vendor-id');
                const vendorPartInput = document.getElementById('vendor_part_name');
                const gciInput = document.getElementById('gci_part_name');
                const registerSection = document.getElementById('register-part-section');

                if (vendorNameInput) vendorNameInput.value = button.dataset.vendorName || '';
                if (vendorHiddenInput) vendorHiddenInput.value = button.dataset.vendorId || '';
                if (vendorPartInput) {
                    vendorPartInput.value = button.dataset.vendorPart || '';
                    vendorPartInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                if (registerSection) {
                    registerSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                if (gciInput) gciInput.focus();
            });
        });
    </script>
</x-app-layout>
