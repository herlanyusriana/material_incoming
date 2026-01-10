<x-app-layout>
    <x-slot name="header">
        Vendor Management
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

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Vendor List</h3>
                            <p class="text-sm text-slate-600 mt-1">Search and filter vendors by status.</p>
                        </div>
                        <div class="flex items-center gap-2">
	                            <form method="GET" id="vendor-filter-form" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                                <div class="relative w-full sm:w-64">
                                    <input type="text" name="q" value="{{ $search }}" placeholder="Search vendors..." class="w-full pl-9 pr-3 py-2 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                                    <span class="absolute left-3 top-2.5 text-slate-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z"/></svg>
                                    </span>
                                </div>
                                <select name="status" class="py-2 px-4 w-full sm:w-44 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="">All Status</option>
                                    <option value="active" @selected($status === 'active')>Active</option>
                                    <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                                </select>
                                <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">Filter</button>
                            </form>
                            <a href="{{ route('vendors.export') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors text-sm whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0-3-3m3 3 3-3m2 8H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                                </svg>
                                Export
                            </a>
                            <button type="button" onclick="document.getElementById('import-vendor-modal').classList.remove('hidden')" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors text-sm whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-6m0 0 3 3m-3-3-3 3m8-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Import
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="min-w-full text-sm">
	                            <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
	                                <tr class="text-slate-600 text-xs uppercase tracking-wider">
	                                    <th class="px-4 py-3 text-left font-semibold">Vendor Name</th>
	                                    <th class="px-4 py-3 text-left font-semibold">Type</th>
	                                    <th class="px-4 py-3 text-left font-semibold">Country</th>
	                                    <th class="px-4 py-3 text-left font-semibold">Contact</th>
	                                    <th class="px-4 py-3 text-left font-semibold">Email</th>
	                                    <th class="px-4 py-3 text-left font-semibold">Phone</th>
	                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
	                                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
	                                </tr>
	                            </thead>
	                            <tbody class="divide-y divide-slate-100 bg-white">
	                                @forelse ($vendors as $vendor)
	                                    <tr class="hover:bg-slate-50 transition-colors">
	                                        <td class="px-4 py-4 font-semibold text-slate-900">
	                                            <div class="flex items-center gap-2">
	                                                <span>{{ $vendor->vendor_name }}</span>
	                                                @if (!$vendor->is_complete)
	                                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700 border border-amber-200"
	                                                        title="Data vendor belum lengkap: {{ implode(', ', $vendor->missing_fields) }}">
	                                                        Incomplete
	                                                    </span>
	                                                @endif
	                                            </div>
	                                        </td>
	                                        <td class="px-4 py-4 text-slate-700 font-semibold">{{ strtoupper($vendor->vendor_type ?? 'IMPORT') }}</td>
	                                        <td class="px-4 py-4 text-slate-600">{{ $vendor->country_code ?? '-' }}</td>
	                                        <td class="px-4 py-4 text-slate-700">{{ $vendor->contact_person }}</td>
	                                        <td class="px-4 py-4 text-slate-600">{{ $vendor->email }}</td>
	                                        <td class="px-4 py-4 text-slate-600">{{ $vendor->phone }}</td>
                                        <td class="px-4 py-4">
                                            <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full {{ $vendor->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                                {{ ucfirst($vendor->status) }}
                                            </span>
                                        </td>
	                                        <td class="px-4 py-4 text-right">
	                                            <div class="flex justify-end gap-3">
	                                                <a href="{{ route('vendors.edit', $vendor) }}" class="text-blue-600 hover:text-blue-700 font-medium">Edit</a>
	                                                <form method="POST" action="{{ route('vendors.destroy', $vendor) }}" onsubmit="return confirm('Archive this vendor?')">
	                                                    @csrf
	                                                    @method('DELETE')
	                                                    <button class="text-red-600 hover:text-red-700 font-medium">Delete</button>
	                                                </form>
	                                            </div>
	                                        </td>
	                                    </tr>
	                                @empty
	                                    <tr>
	                                        <td colspan="8" class="px-4 py-12 text-center text-slate-500">No vendors found.</td>
	                                    </tr>
	                                @endforelse
	                            </tbody>
	                        </table>
	                    </div>

                    <div class="mt-4">{{ $vendors->links() }}</div>
                </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="import-vendor-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                <h3 class="text-lg font-bold text-slate-900">Import Vendors</h3>
                <button type="button" onclick="document.getElementById('import-vendor-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <form action="{{ route('vendors.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Upload Excel File</label>
                    <input type="file" name="file" accept=".xlsx,.xls" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="mt-2 text-xs text-slate-500">Accepted formats: .xlsx, .xls (Max: 2MB)</p>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <p class="text-xs text-blue-800 font-medium mb-1">Required Columns (exact names):</p>
                    <p class="text-xs text-blue-700">vendor_name, vendor_type, country_code, contact_person, email, phone, address, bank_account, status</p>
                    <p class="text-xs text-blue-600 mt-1">Tip: Export existing data to get the correct format</p>
                </div>
                
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('import-vendor-modal').classList.add('hidden')" class="flex-1 px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors">
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
        (function () {
            const form = document.getElementById('vendor-filter-form');
            if (!form) return;

            const searchInput = form.querySelector('input[name="q"]');
            const statusSelect = form.querySelector('select[name="status"]');

            let t = null;
            function submitDebounced() {
                window.clearTimeout(t);
                t = window.setTimeout(() => form.requestSubmit(), 350);
            }

            if (searchInput) {
                searchInput.addEventListener('input', submitDebounced);
            }
            if (statusSelect) {
                statusSelect.addEventListener('change', () => form.requestSubmit());
            }
        })();
    </script>
</x-app-layout>
