@php
    $incomingModuleActive = request()->routeIs('incoming-material.dashboard') || request()->routeIs('departures.*') || request()->routeIs('receives.*');
    $vendorsActive = request()->routeIs('vendors.*');
    $partsActive = request()->routeIs('parts.*');

    $navLinkBase = 'group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-colors';
    $navIconBase = 'h-5 w-5 shrink-0';
    $navActive = 'bg-indigo-600 text-white shadow-sm';
    $navInactive = 'text-slate-600 hover:bg-indigo-50 hover:text-slate-900';
    $navDisabled = 'text-slate-400 cursor-not-allowed';
@endphp

{{-- Mobile overlay --}}
<div
    class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm md:hidden"
    x-show="mobileSidebarOpen"
    x-cloak
    @click="mobileSidebarOpen = false"
></div>

{{-- Mobile drawer --}}
<aside
    class="fixed inset-y-0 left-0 z-50 w-72 bg-white shadow-xl ring-1 ring-slate-200 md:hidden transform transition-transform duration-200"
    :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    x-cloak
>
    <div class="flex h-full flex-col">
        <div class="flex items-center justify-between px-5 py-5 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white shadow-sm">
                    <span class="text-sm font-bold tracking-wide">GCI</span>
                </div>
                <div>
                    <div class="text-sm font-semibold text-slate-900 leading-5">Geum Cheon Indo</div>
                    <div class="text-xs text-slate-500">Material incoming</div>
                </div>
            </div>
            <button
                type="button"
                class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition-colors"
                @click="mobileSidebarOpen = false"
                aria-label="Close sidebar"
                title="Close sidebar"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-5 space-y-6">
            <div class="space-y-1">
                <a
                    href="{{ route('dashboard') }}"
                    @class([$navLinkBase, $navActive => request()->routeIs('dashboard'), $navInactive => !request()->routeIs('dashboard') ])
                    @click="mobileSidebarOpen = false"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5V21h6v-6h6v6h6v-7.5L12 3 3 10.5" />
                    </svg>
                    <span class="ml-3">Dashboard</span>
                </a>
            </div>

            <div>
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Master Data</div>
                <div class="space-y-1">
                    <details class="group" {{ $vendorsActive ? 'open' : '' }}>
                        <summary class="list-none cursor-pointer">
                            <div @class([$navLinkBase, $navActive => $vendorsActive, $navInactive => !$vendorsActive ])>
                                <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                                <span class="ml-3 flex-1">Vendor</span>
                                <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                </svg>
                            </div>
                        </summary>
                        <div class="mt-2 ml-4 border-l border-slate-200 pl-4 space-y-1">
                            <a href="{{ route('vendors.create') }}" class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-colors"
                               @class(['bg-indigo-50 text-indigo-700' => request()->routeIs('vendors.create'), 'text-slate-600 hover:bg-indigo-50 hover:text-slate-900' => !request()->routeIs('vendors.create')])
                               @click="mobileSidebarOpen = false">Create Vendor</a>
                            <a href="{{ route('vendors.index') }}" class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-colors"
                               @class(['bg-indigo-50 text-indigo-700' => request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'), 'text-slate-600 hover:bg-indigo-50 hover:text-slate-900' => !(request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'))])
                               @click="mobileSidebarOpen = false">Vendor List</a>
                        </div>
                    </details>

                    <details class="group" {{ $partsActive ? 'open' : '' }}>
                        <summary class="list-none cursor-pointer">
                            <div @class([$navLinkBase, $navActive => $partsActive, $navInactive => !$partsActive ])>
                                <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.29 7L12 12l8.71-5" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12" />
                                </svg>
                                <span class="ml-3 flex-1">Part</span>
                                <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                </svg>
                            </div>
                        </summary>
                        <div class="mt-2 ml-4 border-l border-slate-200 pl-4 space-y-1">
                            <a href="{{ route('parts.create') }}" class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-colors"
                               @class(['bg-indigo-50 text-indigo-700' => request()->routeIs('parts.create'), 'text-slate-600 hover:bg-indigo-50 hover:text-slate-900' => !request()->routeIs('parts.create')])
                               @click="mobileSidebarOpen = false">Register Part</a>
                            <a href="{{ route('parts.index') }}" class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-colors"
                               @class(['bg-indigo-50 text-indigo-700' => request()->routeIs('parts.index') || request()->routeIs('parts.edit'), 'text-slate-600 hover:bg-indigo-50 hover:text-slate-900' => !(request()->routeIs('parts.index') || request()->routeIs('parts.edit'))])
                               @click="mobileSidebarOpen = false">Existing Part List</a>
                        </div>
                    </details>

                    <a
                        href="{{ route('truckings.index') }}"
                        @class([$navLinkBase, $navActive => request()->routeIs('truckings.*'), $navInactive => !request()->routeIs('truckings.*') ])
                        @click="mobileSidebarOpen = false"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h11v10H3V7Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4l3 3v4h-7v-7Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                        </svg>
                        <span class="ml-3">Truckings</span>
                    </a>
	                </div>
	            </div>

                <div>
                    <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Planning</div>
                    <div class="space-y-1">
                        <a href="{{ route('planning.gci-parts.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}" @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h10" />
                            </svg>
                            <span class="ml-3 flex-1">Part GCI</span>
                        </a>
                        <a href="{{ route('planning.customers.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}" @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3h9M4.5 7.5h15M6 12h12M7.5 16.5h9M9 21h6" />
                            </svg>
                            <span class="ml-3 flex-1">Customers</span>
                        </a>
                        <a href="{{ route('planning.customer-parts.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}" @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 12h10M9 17h6" />
                            </svg>
                            <span class="ml-3 flex-1">Customer Part Mapping</span>
                        </a>
                        <a href="{{ route('planning.planning-imports.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}" @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14" />
                            </svg>
                            <span class="ml-3 flex-1">Customer Planning</span>
                        </a>
                        <a href="{{ route('planning.customer-pos.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}" @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6a2 2 0 0 1 2 2v16l-5-3-5 3V5a2 2 0 0 1 2-2Z" />
                            </svg>
                            <span class="ml-3 flex-1">Customer PO</span>
                        </a>
                        <a href="{{ route('planning.forecasts.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}" @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-5 5-4-4-3 3" />
                            </svg>
                            <span class="ml-3 flex-1">Forecast (Part GCI)</span>
                        </a>
                        <a href="{{ route('planning.mps.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}" @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10" />
                            </svg>
                            <span class="ml-3 flex-1">MPS</span>
                        </a>
                        <a href="{{ route('planning.mrp.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}" @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21V3m9 18V3M3 7.5h18M3 16.5h18" />
                            </svg>
                            <span class="ml-3 flex-1">MRP</span>
                        </a>
                    </div>
                </div>

	            <div>
	                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Incoming</div>
	                <div class="space-y-1">
	                    <a
	                        href="{{ route('departures.create') }}"
                        @class([$navLinkBase, $navActive => request()->routeIs('departures.create'), $navInactive => !request()->routeIs('departures.create') ])
                        @click="mobileSidebarOpen = false"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="ml-3">Create Departure</span>
                    </a>

                    <a
                        href="{{ route('departures.index') }}"
                        @class([$navLinkBase,
                            $navActive => request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit'),
                            $navInactive => !(request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit')),
                        ])
                        @click="mobileSidebarOpen = false"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                        <span class="ml-3">Departure List</span>
                    </a>

                    <a
                        href="{{ route('receives.index') }}"
                        @class([$navLinkBase,
                            $navActive => request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*'),
                            $navInactive => !(request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*')),
                        ])
                        @click="mobileSidebarOpen = false"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6m16 0v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-4m16 0H4" />
                        </svg>
                        <span class="ml-3">Process Receives</span>
                    </a>

                    <a
                        href="{{ route('receives.completed') }}"
                        @class([$navLinkBase,
                            $navActive => request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice'),
                            $navInactive => !(request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice')),
                        ])
                        @click="mobileSidebarOpen = false"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span class="ml-3">Completed Receives</span>
	                    </a>
	                </div>
	            </div>

                <div>
                    <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Inventory</div>
                    <div class="space-y-1">
                    <a
                        href="{{ route('inventory.index') }}"
                        @class([$navLinkBase, $navActive => request()->routeIs('inventory.*'), $navInactive => !request()->routeIs('inventory.*') ])
                        @click="mobileSidebarOpen = false"
                    >
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 6V4h10v2" />
                            </svg>
                            <span class="ml-3 flex-1">Inventory</span>
                        </a>
                    </div>
                </div>
	        </nav>

        <div class="border-t border-slate-200 px-4 py-4">
            <a
                href="{{ route('profile.edit') }}"
                class="{{ $navLinkBase }} {{ $navInactive }}"
                @click="mobileSidebarOpen = false"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 13a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" />
                </svg>
                <span class="ml-3">Profile</span>
            </a>
        </div>
    </div>
</aside>

{{-- Desktop sidebar --}}
<aside
    class="hidden md:flex min-h-screen flex-col border-r border-slate-200 bg-white transition-all duration-200"
    :class="sidebarCollapsed ? 'w-20' : 'w-72'"
>
    <div class="px-4 pt-6">
        <div
            class="flex items-center rounded-2xl border border-slate-200 bg-white shadow-sm"
            :class="sidebarCollapsed ? 'justify-center px-3 py-4' : 'justify-between px-4 py-4'"
        >
            <div class="flex items-center" :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                <div class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white shadow-sm">
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
            >
                <svg x-show="!sidebarCollapsed" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                <svg x-show="sidebarCollapsed" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5 15.75 12l-7.5 7.5" />
                </svg>
            </button>
        </div>
    </div>

    <nav class="flex-1 px-4 pb-6 pt-6 space-y-6">
	        <div class="space-y-1">
	            <a
	                href="{{ route('dashboard') }}"
	                title="Dashboard"
	                @class([$navLinkBase, $navActive => request()->routeIs('dashboard'), $navInactive => !request()->routeIs('dashboard') ])
	                :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
	            >
                <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5V21h6v-6h6v6h6v-7.5L12 3 3 10.5" />
                </svg>
                <span x-show="!sidebarCollapsed" x-cloak>Dashboard</span>
	            </a>
	        </div>

            <div>
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400" x-show="!sidebarCollapsed" x-cloak>Planning</div>
                <div class="space-y-1" x-show="!sidebarCollapsed" x-cloak>
                    <a href="{{ route('planning.gci-parts.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h10" />
                        </svg>
                        <span class="ml-3 flex-1">Part GCI</span>
                    </a>
                    <a href="{{ route('planning.customers.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3h9M4.5 7.5h15M6 12h12M7.5 16.5h9M9 21h6" />
                        </svg>
                        <span class="ml-3 flex-1">Customers</span>
                    </a>
                    <a href="{{ route('planning.customer-parts.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 12h10M9 17h6" />
                        </svg>
                        <span class="ml-3 flex-1">Customer Part Mapping</span>
                    </a>
                    <a href="{{ route('planning.planning-imports.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14" />
                        </svg>
                        <span class="ml-3 flex-1">Customer Planning</span>
                    </a>
                    <a href="{{ route('planning.customer-pos.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6a2 2 0 0 1 2 2v16l-5-3-5 3V5a2 2 0 0 1 2-2Z" />
                        </svg>
                        <span class="ml-3 flex-1">Customer PO</span>
                    </a>
                    <a href="{{ route('planning.forecasts.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-5 5-4-4-3 3" />
                        </svg>
                        <span class="ml-3 flex-1">Forecast (Part GCI)</span>
                    </a>
                    <a href="{{ route('planning.mps.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10" />
                        </svg>
                        <span class="ml-3 flex-1">MPS</span>
                    </a>
                    <a href="{{ route('planning.mrp.index') }}" class="{{ $navLinkBase }} {{ $navInactive }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21V3m9 18V3M3 7.5h18M3 16.5h18" />
                        </svg>
                        <span class="ml-3 flex-1">MRP</span>
                    </a>
                </div>
            </div>

	        <div>
	            <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400" x-show="!sidebarCollapsed" x-cloak>Master Data</div>
	            <div class="space-y-1">
                <a
                    x-show="sidebarCollapsed"
                    x-cloak
                    href="{{ route('vendors.index') }}"
                    title="Vendor List"
                    @class([$navLinkBase, $navActive => $vendorsActive, $navInactive => !$vendorsActive ])
                    :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                </a>

                <details
                    x-show="!sidebarCollapsed"
                    x-cloak
                    class="group"
                    {{ $vendorsActive ? 'open' : '' }}
                >
                    <summary class="list-none cursor-pointer" title="Vendors" :class="sidebarCollapsed ? 'flex justify-center' : ''">
                        <div
                            @class([$navLinkBase, $navActive => $vendorsActive, $navInactive => !$vendorsActive ])
                            :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-cloak class="flex-1">Vendor</span>
                            <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-show="!sidebarCollapsed" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </summary>
                    <div class="mt-2">
                        <div class="ml-4 border-l border-slate-200 pl-4 space-y-1">
                            <a href="{{ route('vendors.create') }}" class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-colors"
                               @class(['bg-indigo-50 text-indigo-700' => request()->routeIs('vendors.create'), 'text-slate-600 hover:bg-indigo-50 hover:text-slate-900' => !request()->routeIs('vendors.create')])>
                                Create Vendor
                            </a>
                            <a href="{{ route('vendors.index') }}" class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-colors"
                               @class(['bg-indigo-50 text-indigo-700' => request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'), 'text-slate-600 hover:bg-indigo-50 hover:text-slate-900' => !(request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'))])>
                                Vendor List
                            </a>
                        </div>
                    </div>
                </details>

                <a
                    x-show="sidebarCollapsed"
                    x-cloak
                    href="{{ route('parts.index') }}"
                    title="Existing Part List"
                    @class([$navLinkBase, $navActive => $partsActive, $navInactive => !$partsActive ])
                    :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.29 7L12 12l8.71-5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12" />
                    </svg>
                </a>

                <details
                    x-show="!sidebarCollapsed"
                    x-cloak
                    class="group"
                    {{ $partsActive ? 'open' : '' }}
                >
                    <summary class="list-none cursor-pointer" title="Parts" :class="sidebarCollapsed ? 'flex justify-center' : ''">
                        <div
                            @class([$navLinkBase, $navActive => $partsActive, $navInactive => !$partsActive ])
                            :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.29 7L12 12l8.71-5" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-cloak class="flex-1">Part</span>
                            <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-show="!sidebarCollapsed" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </summary>
                    <div class="mt-2">
                        <div class="ml-4 border-l border-slate-200 pl-4 space-y-1">
                            <a href="{{ route('parts.create') }}" class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-colors"
                               @class(['bg-indigo-50 text-indigo-700' => request()->routeIs('parts.create'), 'text-slate-600 hover:bg-indigo-50 hover:text-slate-900' => !request()->routeIs('parts.create')])>
                                Register Part
                            </a>
                            <a href="{{ route('parts.index') }}" class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-colors"
                               @class(['bg-indigo-50 text-indigo-700' => request()->routeIs('parts.index') || request()->routeIs('parts.edit'), 'text-slate-600 hover:bg-indigo-50 hover:text-slate-900' => !(request()->routeIs('parts.index') || request()->routeIs('parts.edit'))])>
                                Existing Part List
                            </a>
                        </div>
                    </div>
                </details>

                <a
                    href="{{ route('truckings.index') }}"
                    title="Truckings"
                    @class([$navLinkBase, $navActive => request()->routeIs('truckings.*'), $navInactive => !request()->routeIs('truckings.*') ])
                    :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h11v10H3V7Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4l3 3v4h-7v-7Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                    </svg>
                    <span x-show="!sidebarCollapsed" x-cloak>Truckings</span>
                </a>
            </div>
        </div>

        <div>
            <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400" x-show="!sidebarCollapsed" x-cloak>Incoming</div>

            <details class="group" {{ $incomingModuleActive ? 'open' : '' }} x-effect="if (sidebarCollapsed) $el.removeAttribute('open')">
                <summary class="list-none cursor-pointer" title="Incoming Material" :class="sidebarCollapsed ? 'flex justify-center' : ''">
                    <div
                        @class([$navLinkBase, $navActive => $incomingModuleActive, $navInactive => !$incomingModuleActive ])
                        :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
                            class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-colors"
                            @class([
                                'bg-indigo-50 text-indigo-700' => request()->routeIs('departures.create'),
                                'text-slate-600 hover:bg-indigo-50 hover:text-slate-900' => !request()->routeIs('departures.create'),
                            ])
                        >
                            Create Departure
                        </a>
                        <a
                            href="{{ route('departures.index') }}"
                            class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-colors"
                            @class([
                                'bg-indigo-50 text-indigo-700' => request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit'),
                                'text-slate-600 hover:bg-indigo-50 hover:text-slate-900' => !(request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit')),
                            ])
                        >
                            Departure List
                        </a>
                        <a
                            href="{{ route('receives.index') }}"
                            class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-colors"
                            @class([
                                'bg-indigo-50 text-indigo-700' => request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*'),
                                'text-slate-600 hover:bg-indigo-50 hover:text-slate-900' => !(request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*')),
                            ])
                        >
                            Process Receives
                        </a>
                        <a
                            href="{{ route('receives.completed') }}"
                            class="flex items-center rounded-xl px-3 py-2 text-xs font-semibold transition-colors"
                            @class([
                                'bg-indigo-50 text-indigo-700' => request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice'),
                                'text-slate-600 hover:bg-indigo-50 hover:text-slate-900' => !(request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice')),
                            ])
                        >
                            Completed Receives
                        </a>
                    </div>
                </div>
	            </details>
	        </div>

            <div>
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400" x-show="!sidebarCollapsed" x-cloak>Inventory</div>
                <div class="space-y-1" x-show="!sidebarCollapsed" x-cloak>
                    <a
                        href="{{ route('inventory.index') }}"
                        @class([$navLinkBase, $navActive => request()->routeIs('inventory.*'), $navInactive => !request()->routeIs('inventory.*') ])
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 6V4h10v2" />
                        </svg>
                        <span class="ml-3 flex-1">Inventory</span>
                    </a>
                </div>
            </div>
	    </nav>

    <div class="px-4 pb-5">
        <a
            href="{{ route('profile.edit') }}"
            title="Profile"
            class="{{ $navLinkBase }} {{ $navInactive }}"
            :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 13a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" />
            </svg>
            <span x-show="!sidebarCollapsed" x-cloak>Profile</span>
        </a>
        <div class="mt-3 px-2 text-xs text-slate-400" x-show="!sidebarCollapsed" x-cloak>© {{ date('Y') }} Geum Cheon Indo</div>
    </div>
</aside>
