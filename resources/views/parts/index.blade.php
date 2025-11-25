<x-app-layout>
    <x-slot name="header">
        Part Number Management
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm border rounded-xl p-6 space-y-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Register New Part Number</h3>
                    <p class="text-sm text-gray-500">Enter details for a new part number to add to the system.</p>
                </div>

                <form method="POST" action="{{ route('parts.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <x-input-label for="part_no" value="Part Number" />
                            <x-text-input id="part_no" name="part_no" type="text" placeholder="e.g., PN-00123" class="mt-1 block w-full" required value="{{ old('part_no') }}" />
                            <x-input-error :messages="$errors->get('part_no')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="part_name_vendor" value="Vendor Part Name" />
                            <x-text-input id="part_name_vendor" name="part_name_vendor" type="text" placeholder="e.g., Supplier XYZ Widget" class="mt-1 block w-full" required value="{{ old('part_name_vendor') }}" />
                            <x-input-error :messages="$errors->get('part_name_vendor')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="part_name_gci" value="GCI Part Name" />
                            <x-text-input id="part_name_gci" name="part_name_gci" type="text" placeholder="e.g., Global Component Item Alpha" class="mt-1 block w-full" required value="{{ old('part_name_gci') }}" />
                            <x-input-error :messages="$errors->get('part_name_gci')" class="mt-1" />
                        </div>
                    </div>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <x-input-label for="register_no" value="Register No" />
                            <x-text-input id="register_no" name="register_no" type="text" placeholder="e.g., REG-2025-01" class="mt-1 block w-full" required value="{{ old('register_no') }}" />
                            <x-input-error :messages="$errors->get('register_no')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="vendor_id" value="Vendor" />
                            <select id="vendor_id" name="vendor_id" class="w-44 sm:w-52 mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="">Select vendor</option>
                                @foreach ($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected(old('vendor_id') == $vendor->id)>{{ $vendor->vendor_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('vendor_id')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="status" value="Status" />
                            <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="inactive" @selected(old('status', 'active') === 'inactive')>Inactive</option>
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-1" />
                        </div>
                    </div>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <x-input-label for="trucking_company" value="Trucking Company" />
                            <x-text-input id="trucking_company" name="trucking_company" type="text" placeholder="Optional" class="mt-1 block w-full" value="{{ old('trucking_company') }}" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="storage_reg" value="Storage Reg" />
                            <x-text-input id="storage_reg" name="storage_reg" type="text" placeholder="Optional" class="mt-1 block w-full" value="{{ old('storage_reg') }}" />
                        </div>
                        <div class="flex items-end">
                            <x-primary-button class="w-full justify-center">Register Part Number</x-primary-button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-white shadow-sm border rounded-xl p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Existing Part Numbers</h3>
                        <p class="text-sm text-gray-500">Manage and search through all registered part numbers.</p>
                    </div>
                    <form method="GET" class="flex flex-wrap items-center gap-3 sm:gap-5 w-full sm:w-auto justify-start sm:justify-end">
                        <div class="relative w-full sm:w-64">
                            <input type="text" name="q" value="{{ $search }}" placeholder="Search part numbers..." class="pl-9 pr-3 py-2 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <span class="absolute left-3 top-2.5 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d=\"m21 21-4.35-4.35M11 18a7 7 0 1 1 0-14 7 7 0 0 1 0 14Z\"/></svg>
                            </span>
                        </div>
                        <select name="vendor_id" class="py-2 px-4 w-full sm:w-56 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Vendors</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected($vendorId == $vendor->id)>{{ $vendor->vendor_name }}</option>
                            @endforeach
                        </select>
                        <button class="px-3 py-2 bg-gray-100 border border-gray-200 rounded-md text-sm">Filter</button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-gray-500 text-xs uppercase">
                                <th class="px-3 py-2 text-left">Part Number</th>
                                <th class="px-3 py-2 text-left">Vendor Part Name</th>
                                <th class="px-3 py-2 text-left">GCI Part Name</th>
                                <th class="px-3 py-2 text-left">Vendor</th>
                                <th class="px-3 py-2 text-left">Status</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($parts as $part)
                                <tr class="text-gray-800">
                                    <td class="px-3 py-3 font-medium">{{ $part->part_no }}</td>
                                    <td class="px-3 py-3">{{ $part->part_name_vendor }}</td>
                                    <td class="px-3 py-3">{{ $part->part_name_gci }}</td>
                                    <td class="px-3 py-3 text-gray-700">{{ $part->vendor->vendor_name }}</td>
                                    <td class="px-3 py-3">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $part->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                            {{ ucfirst($part->status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <div class="flex justify-end gap-3 text-blue-600">
                                            <a href="{{ route('parts.edit', $part) }}" class="hover:text-blue-800">Edit</a>
                                            <form method="POST" action="{{ route('parts.destroy', $part) }}" onsubmit="return confirm('Delete this part?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="text-red-600 hover:text-red-800">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-6 text-center text-gray-500">No parts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $parts->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
