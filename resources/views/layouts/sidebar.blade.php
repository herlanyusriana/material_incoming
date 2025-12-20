<aside
    class="hidden md:flex bg-white border-r border-slate-200 min-h-screen flex-col shadow-lg transition-all duration-200"
    :class="sidebarCollapsed ? 'w-20' : 'w-64'"
>
    <div
        class="py-6 flex items-center border-b border-slate-200"
        :class="sidebarCollapsed ? 'px-3 justify-center' : 'px-6 gap-3 justify-between'"
    >
        <div class="flex items-center" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 text-white font-bold grid place-items-center shadow-lg shadow-blue-500/30">
            <span class="text-lg">MF</span>
            </div>
            <div x-show="!sidebarCollapsed" x-cloak>
                <div class="text-lg font-bold text-slate-900">Material Flow</div>
                <div class="text-xs text-slate-500">Inbound & Parts</div>
            </div>
        </div>

        <button
            type="button"
            class="hidden md:inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition-colors"
            @click="toggleSidebar()"
            :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            x-show="!sidebarCollapsed"
            x-cloak
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
        </button>
        <button
            type="button"
            class="hidden md:inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition-colors"
            @click="toggleSidebar()"
            aria-label="Expand sidebar"
            title="Expand sidebar"
            x-show="sidebarCollapsed"
            x-cloak
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5 15.75 12l-7.5 7.5" />
            </svg>
        </button>
    </div>

    @php
        $incomingModuleActive = request()->routeIs('incoming-material.dashboard') || request()->routeIs('departures.*') || request()->routeIs('receives.*');
    @endphp

    <nav class="flex-1 py-6 space-y-6" :class="sidebarCollapsed ? 'px-3' : 'px-4'">
        <div class="space-y-1">
            <a
                href="{{ route('dashboard') }}"
                title="Dashboard"
                class="flex items-center py-2.5 rounded-lg transition-all {{ request()->routeIs('dashboard') ? 'bg-blue-500 text-white shadow-lg shadow-blue-500/30' : 'text-slate-700 hover:bg-gray-100 hover:text-slate-900' }}"
                :class="sidebarCollapsed ? 'justify-center px-3' : 'gap-3 px-4'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.75 12 4.5l8.25 5.25M4.5 10.5V19.5h5.25v-4.5h4.5v4.5H19.5V10.5M4.5 10.5l7.5 5.25L19.5 10.5"/></svg>
                <span class="font-semibold text-sm" x-show="!sidebarCollapsed" x-cloak>Dashboard</span>
            </a>
        </div>

        <div class="space-y-2">
            <p class="px-4 text-xs text-gray-400 uppercase tracking-wide" x-show="!sidebarCollapsed" x-cloak>Master Data</p>
            <div class="space-y-1">
                <a
                    href="{{ route('vendors.index') }}"
                    title="Vendor Management"
                    class="flex items-center py-2.5 rounded-lg transition-all {{ request()->routeIs('vendors.*') ? 'bg-blue-500 text-white shadow-lg shadow-blue-500/30' : 'text-slate-700 hover:bg-gray-100 hover:text-slate-900' }}"
                    :class="sidebarCollapsed ? 'justify-center px-3' : 'gap-3 px-4'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg>
                    <span class="font-medium text-sm" x-show="!sidebarCollapsed" x-cloak>Vendor Management</span>
                </a>
                <a
                    href="{{ route('parts.index') }}"
                    title="Part Management"
                    class="flex items-center py-2.5 rounded-lg transition-all {{ request()->routeIs('parts.*') ? 'bg-blue-500 text-white shadow-lg shadow-blue-500/30' : 'text-slate-700 hover:bg-gray-100 hover:text-slate-900' }}"
                    :class="sidebarCollapsed ? 'justify-center px-3' : 'gap-3 px-4'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/></svg>
                    <span class="font-medium text-sm" x-show="!sidebarCollapsed" x-cloak>Part Management</span>
                </a>
                <a
                    href="{{ route('truckings.index') }}"
                    title="Trucking Management"
                    class="flex items-center py-2.5 rounded-lg transition-all {{ request()->routeIs('truckings.*') ? 'bg-blue-500 text-white shadow-lg shadow-blue-500/30' : 'text-slate-700 hover:bg-gray-100 hover:text-slate-900' }}"
                    :class="sidebarCollapsed ? 'justify-center px-3' : 'gap-3 px-4'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/></svg>
                    <span class="font-medium text-sm" x-show="!sidebarCollapsed" x-cloak>Trucking Management</span>
                </a>
            </div>
        </div>

        <div class="space-y-2">
            <p class="px-4 text-xs text-gray-400 uppercase tracking-wide" x-show="!sidebarCollapsed" x-cloak>Incoming Material</p>
            <details class="group space-y-1" {{ $incomingModuleActive ? 'open' : '' }} x-effect="if (sidebarCollapsed) $el.removeAttribute('open')">
                <summary
                    class="flex items-center py-2.5 rounded-lg cursor-pointer list-none transition-all {{ $incomingModuleActive ? 'bg-blue-500 text-white shadow-lg shadow-blue-500/30' : 'text-slate-700 hover:bg-gray-100 hover:text-slate-900' }}"
                    :class="sidebarCollapsed ? 'justify-center px-3' : 'gap-3 px-4'"
                    title="Incoming Material"
                >
                    <a
                        href="{{ route('incoming-material.dashboard') }}"
                        class="flex items-center flex-1"
                        :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
                        @click.stop
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5V6a2.25 2.25 0 0 1 2.25-2.25H9.75L12 6.75h6.75A2.25 2.25 0 0 1 21 9v8.25A2.25 2.25 0 0 1 18.75 19.5h-6l-2.25 2.25H5.25A2.25 2.25 0 0 1 3 19.5V7.5Z"/></svg>
                        <span class="font-semibold text-sm" x-show="!sidebarCollapsed" x-cloak>
                            Incoming Material
                        </span>
                    </a>
                    <svg class="h-4 w-4 transition-transform group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-show="!sidebarCollapsed" x-cloak><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                </summary>
                <div class="mt-2 ml-6 space-y-1" x-show="!sidebarCollapsed" x-cloak>
                    <a href="{{ route('departures.create') }}" class="flex items-center gap-2 pl-3 pr-3 py-2 rounded-lg text-xs font-medium transition-all {{ request()->routeIs('departures.create') ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-gray-100 hover:text-slate-900' }}">
                        <span>Create Departure</span>
                    </a>
                    <a href="{{ route('departures.index') }}" class="flex items-center gap-2 pl-3 pr-3 py-2 rounded-lg text-xs font-medium transition-all {{ request()->routeIs('departures.index') || request()->routeIs('departures.show') ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-gray-100 hover:text-slate-900' }}">
                        <span>Departure List</span>
                    </a>
                    <a href="{{ route('receives.index') }}" class="flex items-center gap-2 pl-3 pr-3 py-2 rounded-lg text-xs font-medium transition-all {{ request()->routeIs('receives.index') || request()->routeIs('receives.create') ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-gray-100 hover:text-slate-900' }}">
                        <span>Process Receives</span>
                    </a>
                    <a href="{{ route('receives.completed') }}" class="flex items-center gap-2 pl-3 pr-3 py-2 rounded-lg text-xs font-medium transition-all {{ request()->routeIs('receives.completed') ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-gray-100 hover:text-slate-900' }}">
                        <span>Completed Receives</span>
                    </a>
                </div>
            </details>
        </div>
    </nav>

    <div class="px-4 py-4 border-t border-slate-200 space-y-3">
        <a
            href="{{ route('profile.edit') }}"
            title="Profile"
            class="flex items-center py-2 rounded-lg text-slate-700 hover:bg-blue-50 hover:text-blue-600 transition"
            :class="sidebarCollapsed ? 'justify-center px-3' : 'gap-3 px-4'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>
            <span class="text-sm font-medium" x-show="!sidebarCollapsed" x-cloak>Profile</span>
        </a>
        <div class="px-4 text-xs text-slate-400" x-show="!sidebarCollapsed" x-cloak>© {{ date('Y') }} Material Flow</div>
    </div>
</aside>
