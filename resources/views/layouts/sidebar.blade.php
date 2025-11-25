<aside class="hidden md:flex w-64 bg-white border-r min-h-screen flex-col">
    <div class="px-6 py-6 flex items-center gap-3">
        <div class="h-10 w-10 rounded-lg bg-blue-600 text-white font-bold grid place-items-center">MF</div>
        <div>
            <div class="text-lg font-semibold text-gray-900">Material Flow</div>
            <div class="text-xs text-gray-500">Inbound & Parts</div>
        </div>
    </div>

    <nav class="flex-1 px-3 space-y-1">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-md border-l-4 {{ request()->routeIs('dashboard') ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-700 hover:bg-gray-50' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 9.75 12 4.5l8.25 5.25M4.5 10.5V19.5h5.25v-4.5h4.5v4.5H19.5V10.5M4.5 10.5l7.5 5.25L19.5 10.5"/></svg>
            <span class="font-medium text-sm">Dashboard</span>
        </a>
        <a href="{{ route('vendors.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md border-l-4 {{ request()->routeIs('vendors.*') ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-700 hover:bg-gray-50' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 6.75A2.25 2.25 0 0 1 6.75 4.5h10.5A2.25 2.25 0 0 1 19.5 6.75V12a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 12V6.75Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12 3 19.5l4.5-1.5L12 21l4.5-3 4.5 1.5L19.5 12"/></svg>
            <span class="font-medium text-sm">Vendor Management</span>
        </a>
        <a href="{{ route('parts.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md border-l-4 {{ request()->routeIs('parts.*') ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-700 hover:bg-gray-50' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.75v10.5M6.75 12h10.5"/></svg>
            <span class="font-medium text-sm">Part Number Management</span>
        </a>
        <a href="{{ route('arrivals.create') }}" class="flex items-center gap-3 px-3 py-2 rounded-md border-l-4 {{ request()->routeIs('arrivals.*') ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-700 hover:bg-gray-50' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6h18M3 12h18M3 18h18"/></svg>
            <span class="font-medium text-sm">Arrival Form</span>
        </a>
    </nav>

    <div class="px-4 py-4 border-t space-y-3 text-sm text-gray-600">
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 text-gray-700 hover:text-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9ZM19.5 21a7.5 7.5 0 0 0-15 0"/></svg>
            Profile
        </a>
        <div class="text-xs text-gray-400">© {{ date('Y') }} Material Flow.</div>
    </div>
</aside>
