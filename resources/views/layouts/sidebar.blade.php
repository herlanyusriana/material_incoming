<aside
    class="hidden md:flex min-h-screen flex-col border-r border-slate-200 bg-slate-50/70 backdrop-blur transition-all duration-200"
    :class="sidebarCollapsed ? 'w-20' : 'w-72'"
>
    @php
        $incomingModuleActive = request()->routeIs('incoming-material.dashboard') || request()->routeIs('departures.*') || request()->routeIs('receives.*');
    @endphp

    <div class="px-4 pt-6">
        <div
            class="flex items-center rounded-2xl border border-slate-200 bg-white/80 shadow-sm"
            :class="sidebarCollapsed ? 'justify-center px-3 py-4' : 'justify-between px-4 py-4'"
        >
            <div class="flex items-center" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                <div class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-slate-900 to-slate-700 text-white shadow-sm">
                    <span class="text-sm font-bold tracking-wide">GCI</span>
                </div>
                <div x-show="!sidebarCollapsed" x-cloak>
                    <div class="text-sm font-semibold text-slate-900 leading-5">Geum Cheon Indo</div>
                    <div class="text-xs text-slate-500">Material incoming</div>
                </div>
            </div>

            <button
                type="button"
                class="hidden md:inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition-colors"
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
                class="hidden md:inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition-colors"
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
    </div>

    <nav class="flex-1 px-4 pb-6 pt-6 space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white/80 p-2 shadow-sm">
            <a
                href="{{ route('dashboard') }}"
                title="Dashboard"
                class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all"
                :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
                @class([
                    'bg-slate-900 text-white shadow-sm' => request()->routeIs('dashboard'),
                    'text-slate-700 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('dashboard'),
                ])
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5V21h6v-6h6v6h6v-7.5L12 3 3 10.5" />
                </svg>
                <span x-show="!sidebarCollapsed" x-cloak>Dashboard</span>
            </a>

            <div class="mt-3 border-t border-slate-200/70 pt-3">
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400" x-show="!sidebarCollapsed" x-cloak>Master</div>

                <a
                    href="{{ route('vendors.index') }}"
                    title="Vendors"
                    class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all"
                    :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
                    @class([
                        'bg-slate-900 text-white shadow-sm' => request()->routeIs('vendors.*'),
                        'text-slate-700 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('vendors.*'),
                    ])
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak>Vendors</span>
                </a>

                <a
                    href="{{ route('parts.index') }}"
                    title="Parts"
                    class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all"
                    :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
                    @class([
                        'bg-slate-900 text-white shadow-sm' => request()->routeIs('parts.*'),
                        'text-slate-700 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('parts.*'),
                    ])
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.29 7L12 12l8.71-5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12" />
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak>Parts</span>
                </a>

                <a
                    href="{{ route('truckings.index') }}"
                    title="Truckings"
                    class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all"
                    :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
                    @class([
                        'bg-slate-900 text-white shadow-sm' => request()->routeIs('truckings.*'),
                        'text-slate-700 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('truckings.*'),
                    ])
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h11v10H3V7Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4l3 3v4h-7v-7Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak>Truckings</span>
                </a>
            </div>

            <div class="mt-3 border-t border-slate-200/70 pt-3">
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400" x-show="!sidebarCollapsed" x-cloak>Operations</div>

                <details class="group" {{ $incomingModuleActive ? 'open' : '' }} x-effect="if (sidebarCollapsed) $el.removeAttribute('open')">
                    <summary class="list-none cursor-pointer" title="Incoming Material" :class="sidebarCollapsed ? 'flex justify-center' : ''">
                        <div
                            class="flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all"
                            :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
                            @class([
                                'bg-slate-900 text-white shadow-sm' => $incomingModuleActive,
                                'text-slate-700 hover:bg-slate-100 hover:text-slate-900' => !$incomingModuleActive,
                            ])
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5V6a2 2 0 0 1 2-2h5l2 2h6a2 2 0 0 1 2 2v9.5a2 2 0 0 1-2 2h-6l-2 2H5a2 2 0 0 1-2-2V7.5Z" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-cloak class="flex-1">Incoming</span>
                            <svg class="h-4 w-4 transition-transform group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-show="!sidebarCollapsed" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </summary>

                    <div class="mt-2" x-show="!sidebarCollapsed" x-cloak>
                        <div class="ml-4 border-l border-slate-200 pl-4 space-y-1">
                            <a
                                href="{{ route('departures.create') }}"
                                class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-all"
                                @class([
                                    'bg-slate-100 text-slate-900' => request()->routeIs('departures.create'),
                                    'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !request()->routeIs('departures.create'),
                                ])
                            >
                                Create Departure
                            </a>
                            <a
                                href="{{ route('departures.index') }}"
                                class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-all"
                                @class([
                                    'bg-slate-100 text-slate-900' => request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit'),
                                    'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !(request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit')),
                                ])
                            >
                                Departure List
                            </a>
                            <a
                                href="{{ route('receives.index') }}"
                                class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-all"
                                @class([
                                    'bg-slate-100 text-slate-900' => request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*'),
                                    'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !(request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*')),
                                ])
                            >
                                Process Receives
                            </a>
                            <a
                                href="{{ route('receives.completed') }}"
                                class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-all"
                                @class([
                                    'bg-slate-100 text-slate-900' => request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice'),
                                    'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !(request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice')),
                                ])
                            >
                                Completed Receives
                            </a>
                        </div>
                    </div>
                </details>
            </div>
        </div>
    </nav>

    <div class="px-4 pb-5">
        <div class="rounded-2xl border border-slate-200 bg-white/80 p-2 shadow-sm">
            <a
                href="{{ route('profile.edit') }}"
                title="Profile"
                class="flex items-center rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition"
                :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 13a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" />
                </svg>
                <span x-show="!sidebarCollapsed" x-cloak>Profile</span>
            </a>
        </div>
        <div class="mt-3 px-2 text-xs text-slate-400" x-show="!sidebarCollapsed" x-cloak>© {{ date('Y') }} Geum Cheon Indo</div>
    </div>
</aside>

