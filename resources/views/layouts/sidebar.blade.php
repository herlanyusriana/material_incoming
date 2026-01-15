@php
    $incomingModuleActive = request()->routeIs('incoming-material.dashboard') || request()->routeIs('departures.*') || request()->routeIs('receives.*') || request()->routeIs('local-pos.*');
    $outgoingModuleActive = request()->routeIs('outgoing.*');
    $vendorsActive = request()->routeIs('vendors.*');
    $partsActive = request()->routeIs('parts.*');

    $navLinkBase = 'group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200';
    $navIconBase = 'h-5 w-5 shrink-0';
    $navActive = 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-sm shadow-indigo-600/20';
    $navInactive = 'text-slate-600 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-violet-50 hover:text-slate-900';
    $navDisabled = 'text-slate-400 cursor-not-allowed';

    $subLinkBase = 'group flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold transition-all duration-200';
    $subActive = 'bg-gradient-to-r from-indigo-50 to-violet-50 text-indigo-700 ring-1 ring-indigo-100';
    $subInactive = 'text-slate-600 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-violet-50 hover:text-slate-900';
    $subDotBase = 'h-1.5 w-1.5 rounded-full';
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
                                <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                </svg>
                            </div>
                        </summary>
                        <div class="relative mt-2 ml-4 pl-4 space-y-1">
                            <div class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent"></div>

                            <a href="{{ route('vendors.create') }}"
                               @class([$subLinkBase, $subActive => request()->routeIs('vendors.create'), $subInactive => !request()->routeIs('vendors.create')])
                               @click="mobileSidebarOpen = false">
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('vendors.create'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('vendors.create')])></span>
                                <span class="flex-1">Create Vendor</span>
                            </a>
                            <a href="{{ route('vendors.index') }}"
                               @class([$subLinkBase,
                                    $subActive => request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'),
                                    $subInactive => !(request()->routeIs('vendors.index') || request()->routeIs('vendors.edit')),
                               ])
                               @click="mobileSidebarOpen = false">
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'), 'bg-slate-300 group-hover:bg-indigo-400' => !(request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'))])></span>
                                <span class="flex-1">Vendor List</span>
                            </a>
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
                                <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                </svg>
                            </div>
                        </summary>
                        <div class="relative mt-2 ml-4 pl-4 space-y-1">
                            <div class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent"></div>

                            <a href="{{ route('parts.create') }}"
                               @class([$subLinkBase, $subActive => request()->routeIs('parts.create'), $subInactive => !request()->routeIs('parts.create')])
                               @click="mobileSidebarOpen = false">
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('parts.create'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('parts.create')])></span>
                                <span class="flex-1">Register Part</span>
                            </a>
                            <a href="{{ route('parts.index') }}"
                               @class([$subLinkBase,
                                    $subActive => request()->routeIs('parts.index') || request()->routeIs('parts.edit'),
                                    $subInactive => !(request()->routeIs('parts.index') || request()->routeIs('parts.edit')),
                               ])
                               @click="mobileSidebarOpen = false">
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('parts.index') || request()->routeIs('parts.edit'), 'bg-slate-300 group-hover:bg-indigo-400' => !(request()->routeIs('parts.index') || request()->routeIs('parts.edit'))])></span>
                                <span class="flex-1">Existing Part List</span>
                            </a>
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
	                        <details class="group" {{ request()->routeIs('planning.*') ? 'open' : '' }}>
	                            <summary class="list-none cursor-pointer">
	                                <div @class([$navLinkBase, $navActive => request()->routeIs('planning.*'), $navInactive => !request()->routeIs('planning.*') ])>
	                                    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
	                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
	                                    </svg>
	                                    <span class="ml-3 flex-1">Planning</span>
	                                    <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
	                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
	                                    </svg>
	                                </div>
	                            </summary>
	                            <div class="relative mt-2 ml-4 pl-4 space-y-1">
	                                <div class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent"></div>

	                                <a href="{{ route('planning.customers.index') }}" 
	                                   @class([$subLinkBase, $subActive => request()->routeIs('planning.customers.*'), $subInactive => !request()->routeIs('planning.customers.*')])
	                                   @click="mobileSidebarOpen = false">
	                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.customers.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.customers.*')])></span>
	                                    <span class="flex-1">Customers</span>
	                                </a>
	                                <a href="{{ route('planning.gci-parts.index') }}" 
	                                   @class([$subLinkBase, $subActive => request()->routeIs('planning.gci-parts.*'), $subInactive => !request()->routeIs('planning.gci-parts.*')])
	                                   @click="mobileSidebarOpen = false">
	                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.gci-parts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.gci-parts.*')])></span>
	                                    <span class="flex-1">Part GCI</span>
	                                </a>
	                                <a href="{{ route('planning.customer-parts.index') }}" 
	                                   @class([$subLinkBase, $subActive => request()->routeIs('planning.customer-parts.*'), $subInactive => !request()->routeIs('planning.customer-parts.*')])
	                                   @click="mobileSidebarOpen = false">
	                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.customer-parts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.customer-parts.*')])></span>
	                                    <span class="flex-1">Customer Part Mapping</span>
	                                </a>
	                                <a href="{{ route('planning.planning-imports.index') }}" 
	                                   @class([$subLinkBase, $subActive => request()->routeIs('planning.planning-imports.*'), $subInactive => !request()->routeIs('planning.planning-imports.*')])
	                                   @click="mobileSidebarOpen = false">
	                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.planning-imports.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.planning-imports.*')])></span>
	                                    <span class="flex-1">Customer Planning</span>
	                                </a>
	                                <a href="{{ route('planning.customer-pos.index') }}" 
	                                   @class([$subLinkBase, $subActive => request()->routeIs('planning.customer-pos.*'), $subInactive => !request()->routeIs('planning.customer-pos.*')])
	                                   @click="mobileSidebarOpen = false">
	                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.customer-pos.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.customer-pos.*')])></span>
	                                    <span class="flex-1">Customer PO</span>
	                                </a>
	                                <a href="{{ route('planning.forecasts.index') }}" 
	                                   @class([$subLinkBase, $subActive => request()->routeIs('planning.forecasts.*'), $subInactive => !request()->routeIs('planning.forecasts.*')])
	                                   @click="mobileSidebarOpen = false">
	                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.forecasts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.forecasts.*')])></span>
	                                    <span class="flex-1">Forecast (Part GCI)</span>
	                                </a>
	                                <a href="{{ route('planning.mps.index') }}" 
	                                   @class([$subLinkBase, $subActive => request()->routeIs('planning.mps.*'), $subInactive => !request()->routeIs('planning.mps.*')])
	                                   @click="mobileSidebarOpen = false">
	                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.mps.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.mps.*')])></span>
	                                    <span class="flex-1">MPS</span>
	                                </a>
	                                <a href="{{ route('planning.boms.index') }}" 
	                                   @class([$subLinkBase, $subActive => request()->routeIs('planning.boms.*'), $subInactive => !request()->routeIs('planning.boms.*')])
	                                   @click="mobileSidebarOpen = false">
	                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.boms.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.boms.*')])></span>
	                                    <span class="flex-1">BOM GCI</span>
	                                </a>
	                                <a href="{{ route('planning.mrp.index') }}" 
	                                   @class([$subLinkBase, $subActive => request()->routeIs('planning.mrp.*'), $subInactive => !request()->routeIs('planning.mrp.*')])
	                                   @click="mobileSidebarOpen = false">
	                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.mrp.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.mrp.*')])></span>
	                                    <span class="flex-1">MRP</span>
	                                </a>
	                            </div>
	                        </details>
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
                        href="{{ route('local-pos.create') }}"
                        @class([$navLinkBase, $navActive => request()->routeIs('local-pos.create'), $navInactive => !request()->routeIs('local-pos.create') ])
                        @click="mobileSidebarOpen = false"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 11h10M7 15h6" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                        </svg>
                        <span class="ml-3">Create Local PO</span>
                    </a>

                    <a
                        href="{{ route('local-pos.index') }}"
                        @class([$navLinkBase, $navActive => request()->routeIs('local-pos.*') && !request()->routeIs('local-pos.create'), $navInactive => !(request()->routeIs('local-pos.*') && !request()->routeIs('local-pos.create')) ])
                        @click="mobileSidebarOpen = false"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                        <span class="ml-3">Local PO List</span>
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
                        <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Outgoing</div>
                        <details class="group" {{ $outgoingModuleActive ? 'open' : '' }}>
                            <summary class="list-none cursor-pointer">
                                <div @class([$navLinkBase, $navActive => $outgoingModuleActive, $navInactive => !$outgoingModuleActive ])>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h14l4 4v10a2 2 0 0 1-2 2H3V7Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 11h10M7 15h10M7 19h6" />
                                    </svg>
                                    <span class="ml-3 flex-1">Outgoing</span>
                                    <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                    </svg>
                                </div>
                            </summary>

                            <div class="mt-2">
                                <div class="relative ml-4 pl-4 space-y-1">
                                    <div class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent"></div>

                                    <a href="{{ route('outgoing.daily-planning') }}"
                                       @class([$subLinkBase, $subActive => request()->routeIs('outgoing.daily-planning'), $subInactive => !request()->routeIs('outgoing.daily-planning')])
                                       @click="mobileSidebarOpen = false">
                                        <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.daily-planning'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.daily-planning')])></span>
                                        <span class="flex-1">Customers Daily Planning</span>
                                    </a>
                                    <a href="{{ route('outgoing.customer-po') }}"
                                       @class([$subLinkBase, $subActive => request()->routeIs('outgoing.customer-po'), $subInactive => !request()->routeIs('outgoing.customer-po')])
                                       @click="mobileSidebarOpen = false">
                                        <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.customer-po'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.customer-po')])></span>
                                        <span class="flex-1">Customers PO</span>
                                    </a>
                                    <a href="{{ route('outgoing.product-mapping') }}"
                                       @class([$subLinkBase, $subActive => request()->routeIs('outgoing.product-mapping'), $subInactive => !request()->routeIs('outgoing.product-mapping')])
                                       @click="mobileSidebarOpen = false">
                                        <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.product-mapping'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.product-mapping')])></span>
                                        <span class="flex-1">Customer Product Mapping</span>
                                    </a>
                                    <a href="{{ route('outgoing.delivery-requirements') }}"
                                       @class([$subLinkBase, $subActive => request()->routeIs('outgoing.delivery-requirements'), $subInactive => !request()->routeIs('outgoing.delivery-requirements')])
                                       @click="mobileSidebarOpen = false">
                                        <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.delivery-requirements'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.delivery-requirements')])></span>
                                        <span class="flex-1">Delivery Requirements</span>
                                    </a>
                                    <a href="{{ route('outgoing.gci-inventory') }}"
                                       @class([$subLinkBase, $subActive => request()->routeIs('outgoing.gci-inventory'), $subInactive => !request()->routeIs('outgoing.gci-inventory')])
                                       @click="mobileSidebarOpen = false">
                                        <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.gci-inventory'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.gci-inventory')])></span>
                                        <span class="flex-1">GCI Inventory</span>
                                    </a>
                                    <a href="{{ route('outgoing.stock-at-customers') }}"
                                       @class([$subLinkBase, $subActive => request()->routeIs('outgoing.stock-at-customers'), $subInactive => !request()->routeIs('outgoing.stock-at-customers')])
                                       @click="mobileSidebarOpen = false">
                                        <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.stock-at-customers'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.stock-at-customers')])></span>
                                        <span class="flex-1">Stock at Customers</span>
                                    </a>
                                    <a href="{{ route('outgoing.delivery-plan') }}"
                                       @class([$subLinkBase, $subActive => request()->routeIs('outgoing.delivery-plan'), $subInactive => !request()->routeIs('outgoing.delivery-plan')])
                                       @click="mobileSidebarOpen = false">
                                        <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.delivery-plan'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.delivery-plan')])></span>
                                        <span class="flex-1">Delivery Plan &amp; Arrangement</span>
                                    </a>
                                    <a href="{{ route('outgoing.delivery-notes.index') }}"
                                       @class([$subLinkBase, $subActive => request()->routeIs('outgoing.delivery-notes.*'), $subInactive => !request()->routeIs('outgoing.delivery-notes.*')])
                                       @click="mobileSidebarOpen = false">
                                        <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.delivery-notes.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.delivery-notes.*')])></span>
                                        <span class="flex-1 text-indigo-600 font-bold">Delivery Notes (Surat Jalan)</span>
                                    </a>
                                </div>
                            </div>
                        </details>
                    </div>

	                <div>
	                    <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Inventory</div>
	                    <div class="space-y-1">
	                    <a
                        href="{{ route('inventory.index') }}"
                        @class([$navLinkBase, $navActive => request()->routeIs('inventory.index'), $navInactive => !request()->routeIs('inventory.index') ])
                        @click="mobileSidebarOpen = false"
                    >
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 6V4h10v2" />
                            </svg>
                            <span class="ml-3 flex-1">Inventory</span>
                        </a>
                        <a
                            href="{{ route('inventory.receives') }}"
                            @class([$navLinkBase, $navActive => request()->routeIs('inventory.receives'), $navInactive => !request()->routeIs('inventory.receives') ])
                            @click="mobileSidebarOpen = false"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                            <span class="ml-3 flex-1">Inventory Receives</span>
                        </a>
                        <a
                            href="{{ route('inventory.locations.index') }}"
                            @class([$navLinkBase, $navActive => request()->routeIs('inventory.locations.*'), $navInactive => !request()->routeIs('inventory.locations.*') ])
                            @click="mobileSidebarOpen = false"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                            </svg>
                            <span class="ml-3 flex-1">Warehouse Locations</span>
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
            class="flex items-center rounded-2xl border border-slate-200 bg-white shadow-sm px-4 py-4"
            :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
        >
            <div class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white shadow-sm">
                <span class="text-sm font-bold tracking-wide">GCI</span>
            </div>
            <div x-show="!sidebarCollapsed" x-cloak>
                <div class="text-sm font-semibold text-slate-900 leading-5">Geum Cheon Indo</div>
                <div class="text-xs text-slate-500">Material incoming</div>
            </div>
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
                            <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-show="!sidebarCollapsed" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </summary>
                    <div class="mt-2">
                        <div class="relative ml-4 pl-4 space-y-1">
                            <div class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent"></div>

                            <a href="{{ route('vendors.create') }}"
                               @class([$subLinkBase, $subActive => request()->routeIs('vendors.create'), $subInactive => !request()->routeIs('vendors.create')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('vendors.create'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('vendors.create')])></span>
                                <span class="flex-1">Create Vendor</span>
                            </a>
                            <a href="{{ route('vendors.index') }}"
                               @class([$subLinkBase,
                                    $subActive => request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'),
                                    $subInactive => !(request()->routeIs('vendors.index') || request()->routeIs('vendors.edit')),
                               ])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'), 'bg-slate-300 group-hover:bg-indigo-400' => !(request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'))])></span>
                                <span class="flex-1">Vendor List</span>
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
                            <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-show="!sidebarCollapsed" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </summary>
                    <div class="mt-2">
                        <div class="relative ml-4 pl-4 space-y-1">
                            <div class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent"></div>

                            <a href="{{ route('parts.create') }}"
                               @class([$subLinkBase, $subActive => request()->routeIs('parts.create'), $subInactive => !request()->routeIs('parts.create')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('parts.create'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('parts.create')])></span>
                                <span class="flex-1">Register Part</span>
                            </a>
                            <a href="{{ route('parts.index') }}"
                               @class([$subLinkBase,
                                    $subActive => request()->routeIs('parts.index') || request()->routeIs('parts.edit'),
                                    $subInactive => !(request()->routeIs('parts.index') || request()->routeIs('parts.edit')),
                               ])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('parts.index') || request()->routeIs('parts.edit'), 'bg-slate-300 group-hover:bg-indigo-400' => !(request()->routeIs('parts.index') || request()->routeIs('parts.edit'))])></span>
                                <span class="flex-1">Existing Part List</span>
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
	                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400" x-show="!sidebarCollapsed" x-cloak>Planning</div>
	                
	                <details 
	                    class="group" 
	                    {{ request()->routeIs('planning.*') ? 'open' : '' }} 
	                    x-effect="if (sidebarCollapsed) $el.removeAttribute('open')"
	                >
	                    <summary class="list-none cursor-pointer" title="Planning" :class="sidebarCollapsed ? 'flex justify-center' : ''">
	                        <div
	                            @class([$navLinkBase, $navActive => request()->routeIs('planning.*'), $navInactive => !request()->routeIs('planning.*') ])
	                            :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
	                        >
	                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
	                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
	                            </svg>
	                            <span x-show="!sidebarCollapsed" x-cloak class="flex-1">Planning</span>
	                            <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-show="!sidebarCollapsed" x-cloak>
	                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
	                            </svg>
	                        </div>
	                    </summary>

	                    <div class="mt-2" x-show="!sidebarCollapsed" x-cloak>
	                        <div class="relative ml-4 pl-4 space-y-1">
	                            <div class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent"></div>

	                            <a href="{{ route('planning.customers.index') }}" 
	                               @class([$subLinkBase, $subActive => request()->routeIs('planning.customers.*'), $subInactive => !request()->routeIs('planning.customers.*')])>
	                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.customers.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.customers.*')])></span>
	                                <span class="flex-1">Customers</span>
	                            </a>
	                            <a href="{{ route('planning.gci-parts.index') }}" 
	                               @class([$subLinkBase, $subActive => request()->routeIs('planning.gci-parts.*'), $subInactive => !request()->routeIs('planning.gci-parts.*')])>
	                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.gci-parts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.gci-parts.*')])></span>
	                                <span class="flex-1">Part GCI</span>
	                            </a>
	                            <a href="{{ route('planning.customer-parts.index') }}" 
	                               @class([$subLinkBase, $subActive => request()->routeIs('planning.customer-parts.*'), $subInactive => !request()->routeIs('planning.customer-parts.*')])>
	                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.customer-parts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.customer-parts.*')])></span>
	                                <span class="flex-1">Customer Part Mapping</span>
	                            </a>
	                            <a href="{{ route('planning.planning-imports.index') }}" 
	                               @class([$subLinkBase, $subActive => request()->routeIs('planning.planning-imports.*'), $subInactive => !request()->routeIs('planning.planning-imports.*')])>
	                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.planning-imports.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.planning-imports.*')])></span>
	                                <span class="flex-1">Customer Planning</span>
	                            </a>
	                            <a href="{{ route('planning.customer-pos.index') }}" 
	                               @class([$subLinkBase, $subActive => request()->routeIs('planning.customer-pos.*'), $subInactive => !request()->routeIs('planning.customer-pos.*')])>
	                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.customer-pos.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.customer-pos.*')])></span>
	                                <span class="flex-1">Customer PO</span>
	                            </a>
	                            <a href="{{ route('planning.forecasts.index') }}" 
	                               @class([$subLinkBase, $subActive => request()->routeIs('planning.forecasts.*'), $subInactive => !request()->routeIs('planning.forecasts.*')])>
	                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.forecasts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.forecasts.*')])></span>
	                                <span class="flex-1">Forecast (Part GCI)</span>
	                            </a>
	                            <a href="{{ route('planning.mps.index') }}" 
	                               @class([$subLinkBase, $subActive => request()->routeIs('planning.mps.*'), $subInactive => !request()->routeIs('planning.mps.*')])>
	                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.mps.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.mps.*')])></span>
	                                <span class="flex-1">MPS</span>
	                            </a>
	                            <a href="{{ route('planning.boms.index') }}" 
	                               @class([$subLinkBase, $subActive => request()->routeIs('planning.boms.*'), $subInactive => !request()->routeIs('planning.boms.*')])>
	                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.boms.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.boms.*')])></span>
	                                <span class="flex-1">BOM GCI</span>
	                            </a>
	                            <a href="{{ route('planning.mrp.index') }}" 
	                               @class([$subLinkBase, $subActive => request()->routeIs('planning.mrp.*'), $subInactive => !request()->routeIs('planning.mrp.*')])>
	                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.mrp.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.mrp.*')])></span>
	                                <span class="flex-1">MRP</span>
	                            </a>
	                        </div>
	                    </div>
	                </details>
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
                        <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-show="!sidebarCollapsed" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                        </svg>
                    </div>
                </summary>

                <div class="mt-2" x-show="!sidebarCollapsed" x-cloak>
                    <div class="relative ml-4 pl-4 space-y-1">
                        <div class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent"></div>
                        <a
                            href="{{ route('departures.create') }}"
                            @class([$subLinkBase, $subActive => request()->routeIs('departures.create'), $subInactive => !request()->routeIs('departures.create')])
                        >
                            <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('departures.create'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('departures.create')])></span>
                            <span class="flex-1">Create Departure</span>
                        </a>
                        <a
                            href="{{ route('departures.index') }}"
                            @class([$subLinkBase,
                                $subActive => request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit'),
                                $subInactive => !(request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit')),
                            ])
                        >
                            <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit'), 'bg-slate-300 group-hover:bg-indigo-400' => !(request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit'))])></span>
                            <span class="flex-1">Departure List</span>
                        </a>
                        <a
                            href="{{ route('local-pos.create') }}"
                            @class([$subLinkBase, $subActive => request()->routeIs('local-pos.create'), $subInactive => !request()->routeIs('local-pos.create')])
                        >
                            <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('local-pos.create'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('local-pos.create')])></span>
                            <span class="flex-1">Create Local PO</span>
                        </a>
                        <a
                            href="{{ route('local-pos.index') }}"
                            @class([$subLinkBase, $subActive => request()->routeIs('local-pos.index'), $subInactive => !request()->routeIs('local-pos.index')])
                        >
                            <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('local-pos.index'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('local-pos.index')])></span>
                            <span class="flex-1">Local PO List</span>
                        </a>
                        <a
                            href="{{ route('receives.index') }}"
                            @class([$subLinkBase,
                                $subActive => request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*'),
                                $subInactive => !(request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*')),
                            ])
                        >
                            <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !(request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*'))])></span>
                            <span class="flex-1">Process Receives</span>
                        </a>
                        <a
                            href="{{ route('receives.completed') }}"
                            @class([$subLinkBase,
                                $subActive => request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice'),
                                $subInactive => !(request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice')),
                            ])
                        >
                            <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice'), 'bg-slate-300 group-hover:bg-indigo-400' => !(request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice'))])></span>
                            <span class="flex-1">Completed Receives</span>
                        </a>
                    </div>
                </div>
	            </details>
	        </div>

        <div>
            <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400" x-show="!sidebarCollapsed" x-cloak>Outgoing</div>

            <details class="group" {{ $outgoingModuleActive ? 'open' : '' }} x-effect="if (sidebarCollapsed) $el.removeAttribute('open')">
                <summary class="list-none cursor-pointer" title="Outgoing" :class="sidebarCollapsed ? 'flex justify-center' : ''">
                    <div
                        @class([$navLinkBase, $navActive => $outgoingModuleActive, $navInactive => !$outgoingModuleActive ])
                        :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h14l4 4v10a2 2 0 0 1-2 2H3V7Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 11h10M7 15h10M7 19h6" />
                        </svg>
                        <span x-show="!sidebarCollapsed" x-cloak class="flex-1">Outgoing</span>
                        <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" x-show="!sidebarCollapsed" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                        </svg>
                    </div>
                </summary>

                <div class="mt-2" x-show="!sidebarCollapsed" x-cloak>
                    <div class="relative ml-4 pl-4 space-y-1">
                        <div class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent"></div>

                        <a href="{{ route('outgoing.daily-planning') }}"
                           @class([$subLinkBase, $subActive => request()->routeIs('outgoing.daily-planning'), $subInactive => !request()->routeIs('outgoing.daily-planning')])>
                            <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.daily-planning'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.daily-planning')])></span>
                            <span class="flex-1">Customers Daily Planning</span>
                        </a>
                        <a href="{{ route('outgoing.customer-po') }}"
                           @class([$subLinkBase, $subActive => request()->routeIs('outgoing.customer-po'), $subInactive => !request()->routeIs('outgoing.customer-po')])>
                            <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.customer-po'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.customer-po')])></span>
                            <span class="flex-1">Customers PO</span>
                        </a>
                        <a href="{{ route('outgoing.product-mapping') }}"
                           @class([$subLinkBase, $subActive => request()->routeIs('outgoing.product-mapping'), $subInactive => !request()->routeIs('outgoing.product-mapping')])>
                            <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.product-mapping'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.product-mapping')])></span>
                            <span class="flex-1">Customer Product Mapping</span>
                        </a>
                        <a href="{{ route('outgoing.delivery-requirements') }}"
                           @class([$subLinkBase, $subActive => request()->routeIs('outgoing.delivery-requirements'), $subInactive => !request()->routeIs('outgoing.delivery-requirements')])>
                            <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.delivery-requirements'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.delivery-requirements')])></span>
                            <span class="flex-1">Delivery Requirements</span>
                        </a>
                        <a href="{{ route('outgoing.gci-inventory') }}"
                           @class([$subLinkBase, $subActive => request()->routeIs('outgoing.gci-inventory'), $subInactive => !request()->routeIs('outgoing.gci-inventory')])>
                            <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.gci-inventory'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.gci-inventory')])></span>
                            <span class="flex-1">GCI Inventory</span>
                        </a>
                        <a href="{{ route('outgoing.stock-at-customers') }}"
                           @class([$subLinkBase, $subActive => request()->routeIs('outgoing.stock-at-customers'), $subInactive => !request()->routeIs('outgoing.stock-at-customers')])>
                            <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.stock-at-customers'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.stock-at-customers')])></span>
                            <span class="flex-1">Stock at Customers</span>
                        </a>
                        <a href="{{ route('outgoing.delivery-plan') }}"
                           @class([$subLinkBase, $subActive => request()->routeIs('outgoing.delivery-plan'), $subInactive => !request()->routeIs('outgoing.delivery-plan')])>
                            <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.delivery-plan'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.delivery-plan')])></span>
                            <span class="flex-1">Delivery Plan &amp; Arrangement</span>
                        </a>
                        <a href="{{ route('outgoing.delivery-notes.index') }}"
                           @class([$subLinkBase, $subActive => request()->routeIs('outgoing.delivery-notes.*'), $subInactive => !request()->routeIs('outgoing.delivery-notes.*')])>
                            <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.delivery-notes.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.delivery-notes.*')])></span>
                            <span class="flex-1 text-indigo-600 font-bold">Delivery Notes (Surat Jalan)</span>
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
	                        @class([$navLinkBase, $navActive => request()->routeIs('inventory.index'), $navInactive => !request()->routeIs('inventory.index') ])
	                    >
	                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
	                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8Z" />
	                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 6V4h10v2" />
	                        </svg>
	                        <span class="ml-3 flex-1">Inventory</span>
	                    </a>
	                        <a
	                            href="{{ route('inventory.receives') }}"
	                            @class([$navLinkBase, $navActive => request()->routeIs('inventory.receives'), $navInactive => !request()->routeIs('inventory.receives') ])
	                        >
	                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
	                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
	                            </svg>
	                            <span class="ml-3 flex-1">Inventory Receives</span>
	                        </a>
	                        <a
	                            href="{{ route('inventory.locations.index') }}"
	                            @class([$navLinkBase, $navActive => request()->routeIs('inventory.locations.*'), $navInactive => !request()->routeIs('inventory.locations.*') ])
	                        >
	                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
	                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11Z" />
	                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
	                            </svg>
	                            <span class="ml-3 flex-1">Warehouse Locations</span>
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
