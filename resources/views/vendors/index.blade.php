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

            <div class="grid lg:grid-cols-3 gap-6">
                <div class="bg-white shadow-sm border rounded-xl p-6 space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Add New Vendor</h3>
                        <p class="text-sm text-gray-500">Only name, address, and bank account are required.</p>
                    </div>

                    <form method="POST" action="{{ route('vendors.store') }}" class="space-y-4">
                        @csrf
                        <div class="space-y-1">
                            <x-input-label for="vendor_name" value="Vendor Name" />
                            <x-text-input id="vendor_name" name="vendor_name" type="text" placeholder="e.g., Global Logistics Inc." class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('vendor_name')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="bank_account" value="Bank Account" />
                            <x-text-input id="bank_account" name="bank_account" type="text" placeholder="e.g., 100123456789" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('bank_account')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="address" value="Address" />
                            <textarea id="address" name="address" rows="3" placeholder="e.g., 123 Main St, Anytown, USA" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            <x-input-error :messages="$errors->get('address')" class="mt-1" />
                        </div>
                        <div class="flex justify-end">
                            <x-primary-button class="w-full justify-center">Add Vendor</x-primary-button>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white shadow-sm border rounded-xl p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Vendor List</h3>
                            <p class="text-sm text-gray-500">Search and filter vendors by status.</p>
                        </div>
                        <div class="flex items-center gap-4 sm:gap-6 w-full sm:w-auto justify-start sm:justify-end">
                            <form method="GET" class="flex flex-wrap items-center gap-3 sm:gap-5 w-full sm:w-auto justify-start sm:justify-end">
                                <div class="relative w-full sm:w-64">
                                    <input type="text" name="q" value="{{ $search }}" placeholder="Search vendors..." class="pl-9 pr-3 py-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                    <span class="absolute left-3 top-2.5 text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z"/></svg>
                                    </span>
                                </div>
                                <select name="status" class="py-2 px-4 w-full sm:w-52" rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All Status</option>
                                    <option value="active" @selected($status === 'active')>Active</option>
                                    <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                                </select>
                                <button class="px-3 py-2 bg-gray-100 border border-gray-200 rounded-md text-sm">Filter</button>
                            </form>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-gray-500 text-xs uppercase">
                                    <th class="px-3 py-2 text-left">Vendor Name</th>
                                    <th class="px-3 py-2 text-left">Contact</th>
                                    <th class="px-3 py-2 text-left">Email</th>
                                    <th class="px-3 py-2 text-left">Phone</th>
                                    <th class="px-3 py-2 text-left">Status</th>
                                    <th class="px-3 py-2 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($vendors as $vendor)
                                    <tr class="text-gray-800">
                                        <td class="px-3 py-3 font-medium">{{ $vendor->vendor_name }}</td>
                                        <td class="px-3 py-3">{{ $vendor->contact_person }}</td>
                                        <td class="px-3 py-3 text-gray-700">{{ $vendor->email }}</td>
                                        <td class="px-3 py-3 text-gray-700">{{ $vendor->phone }}</td>
                                        <td class="px-3 py-3">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $vendor->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                                {{ ucfirst($vendor->status) }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <div class="flex justify-end gap-3 text-blue-600">
                                                <a href="{{ route('vendors.edit', $vendor) }}" class="hover:text-blue-800">Edit</a>
                                                <form method="POST" action="{{ route('vendors.destroy', $vendor) }}" onsubmit="return confirm('Archive this vendor?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-red-600 hover:text-red-800">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">No vendors found.</td>
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
</x-app-layout>
