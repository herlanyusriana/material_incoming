<x-app-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('vendors.index') }}" class="group bg-white border rounded-xl shadow-sm p-4 flex items-center gap-4 hover:border-blue-400 hover:shadow-md transition">
                    <div class="h-10 w-10 rounded-lg bg-blue-50 text-blue-600 grid place-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 6.75A2.25 2.25 0 0 1 6.75 4.5h10.5A2.25 2.25 0 0 1 19.5 6.75V12a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 12V6.75Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12 3 19.5l4.5-1.5L12 21l4.5-3 4.5 1.5L19.5 12"/></svg>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Vendors</div>
                        <div class="text-lg font-semibold text-gray-900">Manage suppliers</div>
                    </div>
                </a>

                <a href="{{ route('parts.index') }}" class="group bg-white border rounded-xl shadow-sm p-4 flex items-center gap-4 hover:border-blue-400 hover:shadow-md transition">
                    <div class="h-10 w-10 rounded-lg bg-indigo-50 text-indigo-600 grid place-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.75v10.5M6.75 12h10.5"/></svg>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Part Numbers</div>
                        <div class="text-lg font-semibold text-gray-900">Register & search</div>
                    </div>
                </a>

                <a href="{{ route('arrivals.create') }}" class="group bg-white border rounded-xl shadow-sm p-4 flex items-center gap-4 hover:border-blue-400 hover:shadow-md transition">
                    <div class="h-10 w-10 rounded-lg bg-emerald-50 text-emerald-600 grid place-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6h18M3 12h18M3 18h18"/></svg>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Arrival Form</div>
                        <div class="text-lg font-semibold text-gray-900">Create new arrival</div>
                    </div>
                </a>

                <a href="{{ route('arrivals.index') }}" class="group bg-white border rounded-xl shadow-sm p-4 flex items-center gap-4 hover:border-blue-400 hover:shadow-md transition">
                    <div class="h-10 w-10 rounded-lg bg-orange-50 text-orange-600 grid place-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7.5 4.5h9A2.25 2.25 0 0 1 18.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-9A2.25 2.25 0 0 1 6.75 17.25V6.75A2.25 2.25 0 0 1 7.5 4.5Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 9h4.5M9.75 12h4.5M9.75 15h2.25"/></svg>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Arrival Records</div>
                        <div class="text-lg font-semibold text-gray-900">Browse arrivals</div>
                    </div>
                </a>
            </div>

            <div class="bg-white border rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                        <p class="text-sm text-gray-500">Jump to common workflows.</p>
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <a href="{{ route('vendors.create') }}" class="border rounded-lg p-4 hover:border-blue-400 hover:shadow transition">
                        <div class="text-sm text-gray-500">Vendors</div>
                        <div class="text-base font-semibold text-gray-900">Add new vendor</div>
                    </a>
                    <a href="{{ route('parts.create') }}" class="border rounded-lg p-4 hover:border-blue-400 hover:shadow transition">
                        <div class="text-sm text-gray-500">Part Numbers</div>
                        <div class="text-base font-semibold text-gray-900">Register new part</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
