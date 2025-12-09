<x-app-layout>
    <x-slot name="header">
        Trucking Company Management
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid lg:grid-cols-3 gap-6">
                <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                    <div class="pb-3 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">Add New Trucking Company</h3>
                        <p class="text-sm text-slate-600 mt-1">Manage logistics and shipping companies.</p>
                    </div>

                    <form method="POST" action="{{ route('truckings.store') }}" class="space-y-4">
                        @csrf
                        <div class="space-y-1">
                            <x-input-label for="company_name" value="Company Name" />
                            <x-text-input id="company_name" name="company_name" type="text" placeholder="e.g., Fast Logistics" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('company_name')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="address" value="Address" />
                            <textarea id="address" name="address" rows="3" placeholder="Full company address" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required></textarea>
                            <x-input-error :messages="$errors->get('address')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="phone" value="Phone" />
                            <x-text-input id="phone" name="phone" type="text" placeholder="+62-21-xxx-xxxx" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="email" value="Email" />
                            <x-text-input id="email" name="email" type="email" placeholder="contact@company.com" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>
                        <div class="space-y-1">
                            <x-input-label for="contact_person" value="Contact Person" />
                            <x-text-input id="contact_person" name="contact_person" type="text" placeholder="Person in charge" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('contact_person')" class="mt-1" />
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" class="w-full px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors shadow-sm">Add Trucking Company</button>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Trucking Companies</h3>
                            <p class="text-sm text-slate-600 mt-1">Search and filter companies.</p>
                        </div>
                        <form method="GET" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                            <div class="relative w-full sm:w-64">
                                <input type="text" name="q" value="{{ $search }}" placeholder="Search companies..." class="w-full pl-9 pr-3 py-2 rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
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
                    </div>

                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                                <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left font-semibold">Company Name</th>
                                    <th class="px-4 py-3 text-left font-semibold">Contact Person</th>
                                    <th class="px-4 py-3 text-left font-semibold">Phone</th>
                                    <th class="px-4 py-3 text-left font-semibold">Email</th>
                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($truckings as $trucking)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-3">
                                            <span class="font-medium text-slate-900">{{ $trucking->company_name }}</span>
                                            <div class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $trucking->address }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $trucking->contact_person ?: '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $trucking->phone ?: '-' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $trucking->email ?: '-' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $trucking->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800' }}">
                                                {{ ucfirst($trucking->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right space-x-2">
                                            <a href="{{ route('truckings.edit', $trucking) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('truckings.destroy', $trucking) }}" class="inline-block" onsubmit="return confirm('Delete this trucking company?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                            No trucking companies found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($truckings->hasPages())
                        <div class="pt-4">
                            {{ $truckings->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
