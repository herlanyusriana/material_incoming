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

            <section class="bg-white border rounded-xl shadow-sm p-6 space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Register New Part Number</h3>
                    <p class="text-sm text-gray-500">Lengkapi informasi vendor dan detail part sebelum menyimpan.</p>
                </div>

                <form method="POST" action="{{ route('parts.store') }}" class="space-y-6 js-loading-form">
                    @csrf
                    <div class="space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Vendor Information</p>
                        <x-select name="vendor_id" label="Vendor" :options="$vendors->pluck('vendor_name', 'id')" placeholder="Pilih vendor" required hint="Pastikan vendor sesuai dengan sumber part." />
                    </div>

                    <div class="space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Part Identification</p>
                        <div class="grid md:grid-cols-2 gap-4">
                            <x-input name="part_no" label="Part Number" placeholder="PN-001" required hint="Gunakan kode internal singkat." />
                            <x-input name="register_no" label="Register Number" placeholder="REG-01" required />
                        </div>
                    </div>

                    <div class="space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Naming Details</p>
                        <div class="grid md:grid-cols-2 gap-4">
                            <x-input name="part_name_vendor" label="Vendor Part Name" placeholder="Nama dari vendor" required />
                            <x-input name="part_name_gci" label="GCI Part Name" placeholder="Nama internal" required />
                        </div>
                    </div>

                    <div class="space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Logistics (Optional)</p>
                        <div class="grid md:grid-cols-2 gap-4">
                            <x-input name="trucking_company" label="Trucking Company" placeholder="Opsional" hint="Isi jika ada vendor logistik tetap." />
                            <x-input name="storage_reg" label="Storage Reg" placeholder="Opsional" />
                        </div>
                    </div>

                    <div class="space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Operational Status</p>
                        <div class="grid md:grid-cols-2 gap-4">
                            <x-select name="status" label="Status" :options="['active' => 'Active', 'inactive' => 'Inactive']" placeholder="Pilih status" value="active" />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-button.primary icon="plus">
                            Register Part Number
                        </x-button.primary>
                    </div>
                </form>
            </section>

            <section class="bg-white border rounded-xl shadow-sm p-6 space-y-6">
                <div class="space-y-2">
                    <h3 class="text-lg font-semibold text-gray-900">Existing Part Numbers</h3>
                    <p class="text-sm text-gray-500">Gunakan filter untuk mempercepat pencarian part.</p>
                </div>

                <form method="GET" class="grid gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <x-input type="search" name="q" label="Search" placeholder="Cari part..." :value="$search" :preserve-old="false" />
                    </div>
                    <div>
                        <x-select name="vendor_id" label="Vendor" :options="$vendors->pluck('vendor_name', 'id')" :value="$vendorId" placeholder="Semua vendor" :preserve-old="false" />
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

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Part Number</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Vendor Part Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">GCI Part Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Vendor</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($parts as $part)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ $part->part_no }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $part->part_name_vendor }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ $part->part_name_gci }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-600">{{ $part->vendor->vendor_name }}</td>
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
                    <p class="text-xs text-blue-800 font-medium mb-1">Required Columns (exact names):</p>
                    <p class="text-xs text-blue-700">part_number, part_name_vendor, part_name_internal, vendor, description, status</p>
                    <p class="text-xs text-blue-600 mt-1">Tip: Export existing data to get the correct format</p>
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
    </script>
</x-app-layout>
