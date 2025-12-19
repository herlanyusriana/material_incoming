<x-app-layout>
    <x-slot name="header">
        Vendor Management
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

            <div class="grid lg:grid-cols-3 gap-6">
                <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                    <div class="pb-3 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">Add New Vendor</h3>
                        <p class="text-sm text-slate-600 mt-1">Vendor name and country code are required.</p>
                    </div>

                    <form method="POST" action="{{ route('vendors.store') }}" class="space-y-4">
                        @csrf
                        <div class="space-y-1">
                            <x-input-label for="vendor_name" value="Vendor Name" />
                            <x-text-input id="vendor_name" name="vendor_name" type="text" placeholder="e.g., Global Logistics Inc." class="mt-1 block w-full" required value="{{ old('vendor_name') }}" />
                            <x-input-error :messages="$errors->get('vendor_name')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="country_code" value="Country Code (ISO-2)" />
                            <x-text-input id="country_code" name="country_code" type="text" placeholder="e.g., ID" class="mt-1 block w-full uppercase" required maxlength="2" value="{{ old('country_code') }}" />
                            <x-input-error :messages="$errors->get('country_code')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="bank_account" value="Bank Account" />
                            <x-text-input id="bank_account" name="bank_account" type="text" placeholder="e.g., 100123456789" class="mt-1 block w-full" value="{{ old('bank_account') }}" />
                            <x-input-error :messages="$errors->get('bank_account')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="address" value="Address" />
                            <textarea id="address" name="address" rows="3" placeholder="e.g., 123 Main St, Anytown, USA" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('address') }}</textarea>
                            <x-input-error :messages="$errors->get('address')" class="mt-1" />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <x-input-label for="contact_person" value="Contact Person" />
                                <x-text-input id="contact_person" name="contact_person" type="text" placeholder="e.g., Jane Doe" class="mt-1 block w-full" value="{{ old('contact_person') }}" />
                                <x-input-error :messages="$errors->get('contact_person')" class="mt-1" />
                            </div>
                            <div class="space-y-1">
                                <x-input-label for="email" value="Email" />
                                <x-text-input id="email" name="email" type="email" placeholder="e.g., vendor@email.com" class="mt-1 block w-full" value="{{ old('email') }}" />
                                <x-input-error :messages="$errors->get('email')" class="mt-1" />
                            </div>
                            <div class="space-y-1">
                                <x-input-label for="phone" value="Phone" />
                                <x-text-input id="phone" name="phone" type="text" placeholder="e.g., +62 812 3456 7890" class="mt-1 block w-full" value="{{ old('phone') }}" />
                                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                            </div>
                            <div class="space-y-1">
                                <x-input-label for="status" value="Status" />
                                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                    <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-1" />
                            </div>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="w-full px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors shadow-sm">Add Vendor</button>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Vendor List</h3>
                            <p class="text-sm text-slate-600 mt-1">Search and filter vendors by status.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
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
                                        <td class="px-4 py-4 font-semibold text-slate-900">{{ $vendor->vendor_name }}</td>
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
                                        <td colspan="6" class="px-4 py-12 text-center text-slate-500">No vendors found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $vendors->links() }}</div>
                </div>
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
                    <p class="text-xs text-blue-700">vendor_name, country_code, contact_person, email, phone, address, bank_account, status</p>
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
</x-app-layout>
