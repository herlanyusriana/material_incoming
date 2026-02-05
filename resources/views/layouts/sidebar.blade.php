@php
    $incomingModuleActive = request()->routeIs('incoming-material.dashboard') || request()->routeIs('departures.*') || request()->routeIs('receives.*') || request()->routeIs('local-pos.*');
    $outgoingModuleActive = request()->routeIs('outgoing.*');
    $vendorsActive = request()->routeIs('vendors.*');
    $partsActive = request()->routeIs('parts.*');
    $logisticsActive = request()->routeIs('logistics.*');
    $purchasingActive = request()->routeIs('purchasing.*');

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
<div class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm md:hidden" x-show="mobileSidebarOpen" x-cloak
    @click="mobileSidebarOpen = false"></div>

{{-- Mobile drawer --}}
<aside
    class="fixed inset-y-0 left-0 z-50 w-72 bg-white shadow-xl ring-1 ring-slate-200 md:hidden transform transition-transform duration-200"
    :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'" x-cloak>
    <div class="flex h-full flex-col">
        <div class="flex items-center justify-between px-5 py-5 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <div
                    class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white shadow-sm">
                    <span class="text-sm font-bold tracking-wide">GCI</span>
                </div>
                <div>
                    <div class="text-sm font-semibold text-slate-900 leading-5">Geum Cheon Indo</div>
                    <div class="text-xs text-slate-500">Smart Application System</div>
                </div>
            </div>
            <button type="button"
                class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition-colors"
                @click="mobileSidebarOpen = false" aria-label="Close sidebar" title="Close sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-5 space-y-6">
            <div class="space-y-1">
                <a href="{{ route('dashboard') }}" @class([$navLinkBase, $navActive => request()->routeIs('dashboard'), $navInactive => !request()->routeIs('dashboard')]) @click="mobileSidebarOpen = false">
                    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 13.5V21h6v-6h6v6h6v-7.5L12 3 3 10.5" />
                    </svg>
                    <span class="ml-3">Dashboard</span>
                </a>
            </div>

            @can('manage_planning')
                <div>
                    <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Master Data
                    </div>
                    <div class="space-y-1">
                        <details class="group" {{ $vendorsActive ? 'open' : '' }}>
                            <summary class="list-none cursor-pointer">
                                <div @class([$navLinkBase, $navActive => $vendorsActive, $navInactive => !$vendorsActive])>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M7.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16 3.13a4 4 0 0 1 0 7.75" />
                                    </svg>
                                    <span class="ml-3 flex-1">Vendor</span>
                                    <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                    </svg>
                                </div>
                            </summary>
                            <div class="relative mt-2 ml-4 pl-4 space-y-1">
                                <div
                                    class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent">
                                </div>

                                <a href="{{ route('vendors.create') }}" @class([$subLinkBase, $subActive => request()->routeIs('vendors.create'), $subInactive => !request()->routeIs('vendors.create')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('vendors.create'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('vendors.create')])></span>
                                    <span class="flex-1">Create Vendor</span>
                                </a>
                                <a href="{{ route('vendors.index') }}" @class([
                                    $subLinkBase,
                                    $subActive => request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'),
                                    $subInactive => !(request()->routeIs('vendors.index') || request()->routeIs('vendors.edit')),
                                ]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'), 'bg-slate-300 group-hover:bg-indigo-400' => !(request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'))])></span>
                                    <span class="flex-1">Vendor List</span>
                                </a>
                            </div>
                        </details>

                        <details class="group" {{ $partsActive ? 'open' : '' }}>
                            <summary class="list-none cursor-pointer">
                                <div @class([$navLinkBase, $navActive => $partsActive, $navInactive => !$partsActive])>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.29 7L12 12l8.71-5" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12" />
                                    </svg>
                                    <span class="ml-3 flex-1">Part</span>
                                    <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                    </svg>
                                </div>
                            </summary>
                            <div class="relative mt-2 ml-4 pl-4 space-y-1">
                                <div
                                    class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent">
                                </div>

                                <a href="{{ route('parts.create') }}" @class([$subLinkBase, $subActive => request()->routeIs('parts.create'), $subInactive => !request()->routeIs('parts.create')])
                                    @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('parts.create'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('parts.create')])></span>
                                    <span class="flex-1">Register Part</span>
                                </a>
                                <a href="{{ route('parts.index') }}" @class([
                                    $subLinkBase,
                                    $subActive => request()->routeIs('parts.index') || request()->routeIs('parts.edit'),
                                    $subInactive => !(request()->routeIs('parts.index') || request()->routeIs('parts.edit')),
                                ])
                                    @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('parts.index') || request()->routeIs('parts.edit'), 'bg-slate-300 group-hover:bg-indigo-400' => !(request()->routeIs('parts.index') || request()->routeIs('parts.edit'))])></span>
                                    <span class="flex-1">Existing Part List</span>
                                </a>
                            </div>
                        </details>

                        <a href="{{ route('truckings.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('truckings.*'), $navInactive => !request()->routeIs('truckings.*')])
                            @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h11v10H3V7Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4l3 3v4h-7v-7Z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                            </svg>
                            <span class="ml-3">Truckings</span>
                        </a>
                    </div>
                </div>
            @endcan

            @can('view_planning')
                <div>
                    <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Planning</div>
                    <div class="space-y-1">
                        <details class="group" {{ request()->routeIs('planning.*') ? 'open' : '' }}>
                            <summary class="list-none cursor-pointer">
                                <div @class([$navLinkBase, $navActive => request()->routeIs('planning.*'), $navInactive => !request()->routeIs('planning.*')])>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                                    </svg>
                                    <span class="ml-3 flex-1">Planning</span>
                                    <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                    </svg>
                                </div>
                            </summary>
                            <div class="relative mt-2 ml-4 pl-4 space-y-1">
                                <div
                                    class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent">
                                </div>

                                <a href="{{ route('planning.customers.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.customers.*'), $subInactive => !request()->routeIs('planning.customers.*')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.customers.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.customers.*')])></span>
                                    <span class="flex-1">Customers</span>
                                </a>
                                <a href="{{ route('planning.fg-parts.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.fg-parts.*'), $subInactive => !request()->routeIs('planning.fg-parts.*')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.fg-parts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.fg-parts.*')])></span>
                                    <span class="flex-1">FG Part</span>
                                </a>
                                <a href="{{ route('planning.wip-parts.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.wip-parts.*'), $subInactive => !request()->routeIs('planning.wip-parts.*')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.wip-parts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.wip-parts.*')])></span>
                                    <span class="flex-1">WIP Part</span>
                                </a>
                                <a href="{{ route('planning.rm-parts.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.rm-parts.*'), $subInactive => !request()->routeIs('planning.rm-parts.*')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.rm-parts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.rm-parts.*')])></span>
                                    <span class="flex-1">RM Part</span>
                                </a>
                                <a href="{{ route('planning.customer-parts.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.customer-parts.*'), $subInactive => !request()->routeIs('planning.customer-parts.*')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.customer-parts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.customer-parts.*')])></span>
                                    <span class="flex-1">Customer Part Mapping</span>
                                </a>
                                <a href="{{ route('planning.planning-imports.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.planning-imports.*'), $subInactive => !request()->routeIs('planning.planning-imports.*')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.planning-imports.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.planning-imports.*')])></span>
                                    <span class="flex-1">Customer Planning</span>
                                </a>
                                <a href="{{ route('planning.customer-pos.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.customer-pos.*'), $subInactive => !request()->routeIs('planning.customer-pos.*')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.customer-pos.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.customer-pos.*')])></span>
                                    <span class="flex-1">Customer PO</span>
                                </a>
                                <a href="{{ route('planning.forecasts.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.forecasts.*'), $subInactive => !request()->routeIs('planning.forecasts.*')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.forecasts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.forecasts.*')])></span>
                                    <span class="flex-1">Forecast (Part GCI)</span>
                                </a>
                                <a href="{{ route('planning.mps.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.mps.*'), $subInactive => !request()->routeIs('planning.mps.*')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.mps.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.mps.*')])></span>
                                    <span class="flex-1">MPS</span>
                                </a>
                                <a href="{{ route('planning.boms.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.boms.index'), $subInactive => !request()->routeIs('planning.boms.index')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.boms.index'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.boms.index')])></span>
                                    <span class="flex-1">BOM GCI</span>
                                </a>
                                <a href="{{ route('planning.boms.explosion-search') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.boms.explosion*'), $subInactive => !request()->routeIs('planning.boms.explosion*')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-blue-600' => request()->routeIs('planning.boms.explosion*'), 'bg-slate-300 group-hover:bg-blue-400' => !request()->routeIs('planning.boms.explosion*')])></span>
                                    <span class="flex-1">🌳 BOM Explosion</span>
                                </a>
                                <a href="{{ route('planning.mrp.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.mrp.*'), $subInactive => !request()->routeIs('planning.mrp.*')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.mrp.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.mrp.*')])></span>
                                    <span class="flex-1">MRP</span>
                                </a>
                            </div>
                        </details>
                    </div>
                </div>
            @endcan

            @can('manage_purchasing')
                <div>
                    <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Purchasing
                    </div>
                    <div class="space-y-1">
                        <details class="group" {{ $purchasingActive ? 'open' : '' }}>
                            <summary class="list-none cursor-pointer">
                                <div @class([$navLinkBase, $navActive => $purchasingActive, $navInactive => !$purchasingActive])>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                    <span class="ml-3 flex-1">Purchasing</span>
                                    <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                    </svg>
                                </div>
                            </summary>
                            <div class="relative mt-2 ml-4 pl-4 space-y-1">
                                <div
                                    class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent">
                                </div>

                                <a href="{{ route('purchasing.purchase-requests.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('purchasing.purchase-requests.*'), $subInactive => !request()->routeIs('purchasing.purchase-requests.*')])
                                    @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('purchasing.purchase-requests.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('purchasing.purchase-requests.*')])></span>
                                    <span class="flex-1">Purchase Requests</span>
                                </a>
                                <a href="{{ route('purchasing.purchase-orders.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('purchasing.purchase-orders.*'), $subInactive => !request()->routeIs('purchasing.purchase-orders.*')])
                                    @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('purchasing.purchase-orders.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('purchasing.purchase-orders.*')])></span>
                                    <span class="flex-1">Purchase Orders</span>
                                </a>
                            </div>
                        </details>
                    </div>
                </div>
            @endcan

            @php
                $productionActive = request()->routeIs('production.*') || request()->routeIs('warehouse.production-load.*');
            @endphp
            @can('view_production')
                <div>
                    <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Production
                    </div>
                    <div class="space-y-1">
                        <details class="group" {{ $productionActive ? 'open' : '' }}>
                            <summary class="list-none cursor-pointer" title="Production">
                                <div @class([$navLinkBase, $navActive => $productionActive, $navInactive => !$productionActive])>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <span class="ml-3 flex-1">Production</span>
                                    <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                    </svg>
                                </div>
                            </summary>
                            <div class="relative mt-2 ml-4 pl-4 space-y-1">
                                <div
                                    class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent">
                                </div>

                                <a href="{{ route('production.orders.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.orders.*'), $subInactive => !request()->routeIs('production.orders.*')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.orders.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.orders.*')])></span>
                                    <span class="flex-1">Production Orders</span>
                                </a>
                                <a href="{{ route('production.work-orders.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.work-orders.*'), $subInactive => !request()->routeIs('production.work-orders.*')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.work-orders.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.work-orders.*')])></span>
                                    <span class="flex-1">Work Order & Kanban Release</span>
                                </a>
                                <a href="{{ route('production.material-availability.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.material-availability.*'), $subInactive => !request()->routeIs('production.material-availability.*')])
                                    @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.material-availability.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.material-availability.*')])></span>
                                    <span class="flex-1">Material Availability</span>
                                </a>
                                <a href="{{ route('production.start-production.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.start-production.*'), $subInactive => !request()->routeIs('production.start-production.*')])
                                    @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.start-production.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.start-production.*')])></span>
                                    <span class="flex-1">Start Production</span>
                                </a>
                                <a href="{{ route('production.qc-inspection.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.qc-inspection.*'), $subInactive => !request()->routeIs('production.qc-inspection.*')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.qc-inspection.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.qc-inspection.*')])></span>
                                    <span class="flex-1">QC Inspection</span>
                                </a>
                                <a href="{{ route('production.mass-production.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.mass-production.*'), $subInactive => !request()->routeIs('production.mass-production.*')])
                                    @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.mass-production.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.mass-production.*')])></span>
                                    <span class="flex-1">Mass Production</span>
                                </a>
                                <a href="{{ route('production.in-process-inspection.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.in-process-inspection.*'), $subInactive => !request()->routeIs('production.in-process-inspection.*')])
                                    @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.in-process-inspection.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.in-process-inspection.*')])></span>
                                    <span class="flex-1">In-Process Inspection</span>
                                </a>
                                <a href="{{ route('production.finish-production.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.finish-production.*'), $subInactive => !request()->routeIs('production.finish-production.*')])
                                    @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.finish-production.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.finish-production.*')])></span>
                                    <span class="flex-1">Finish Production</span>
                                </a>
                                <a href="{{ route('production.final-inspection.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.final-inspection.*'), $subInactive => !request()->routeIs('production.final-inspection.*')])
                                    @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.final-inspection.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.final-inspection.*')])></span>
                                    <span class="flex-1">Final Inspection → Kanban Update & Inventory</span>
                                </a>
                            </div>
                        </details>
                    </div>
                    <div
                        class="mt-4 rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2 text-[11px] text-slate-500">
                        <div class="mb-1 font-semibold text-[10px] uppercase tracking-wider text-slate-400">Production Flow
                        </div>
                        <ol class="list-decimal list-inside space-y-0.5 text-[12px] text-slate-600">
                            <li>Production Orders</li>
                            <li>Work Order &amp; Kanban Release</li>
                            <li>Material Availability</li>
                            <li>Start Production</li>
                            <li>QC Inspection</li>
                            <li>Mass Production</li>
                            <li>In-Process Inspection</li>
                            <li>Finish Production</li>
                            <li>Final Inspection → Kanban Update &amp; Inventory</li>
                        </ol>
                    </div>
                </div>
            @endcan

            @can('manage_incoming')
                <div>
                    <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Incoming</div>
                    <div class="space-y-1">
                        <a href="{{ route('departures.create') }}" @class([$navLinkBase, $navActive => request()->routeIs('departures.create'), $navInactive => !request()->routeIs('departures.create')]) @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="ml-3">Create Departure</span>
                        </a>

                        <a href="{{ route('departures.index') }}" @class([
                            $navLinkBase,
                            $navActive => request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit'),
                            $navInactive => !(request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit')),
                        ])
                            @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                            <span class="ml-3">Departure List</span>
                        </a>

                        <a href="{{ route('local-pos.create') }}" @class([$navLinkBase, $navActive => request()->routeIs('local-pos.create'), $navInactive => !request()->routeIs('local-pos.create')])
                            @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 11h10M7 15h6" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                            </svg>
                            <span class="ml-3">Create Local PO</span>
                        </a>

                        <a href="{{ route('local-pos.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('local-pos.*') && !request()->routeIs('local-pos.create'), $navInactive => !(request()->routeIs('local-pos.*') && !request()->routeIs('local-pos.create'))])
                            @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                            <span class="ml-3">Local PO List</span>
                        </a>

                        <a href="{{ route('receives.index') }}" @class([
                            $navLinkBase,
                            $navActive => request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*'),
                            $navInactive => !(request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*')),
                        ])
                            @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20 13V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6m16 0v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-4m16 0H4" />
                            </svg>
                            <span class="ml-3">Process Receives</span>
                        </a>

                        <a href="{{ route('receives.completed') }}" @class([
                            $navLinkBase,
                            $navActive => request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice'),
                            $navInactive => !(request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice')),
                        ]) @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span class="ml-3">Completed Receives</span>
                        </a>
                    </div>
                </div>
            @endcan

            @can('manage_outgoing')
                <div>
                    <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Outgoing</div>
                    <details class="group" {{ $outgoingModuleActive ? 'open' : '' }}>
                        <summary class="list-none cursor-pointer">
                            <div @class([$navLinkBase, $navActive => $outgoingModuleActive, $navInactive => !$outgoingModuleActive])>
                                <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 7h14l4 4v10a2 2 0 0 1-2 2H3V7Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 11h10M7 15h10M7 19h6" />
                                </svg>
                                <span class="ml-3 flex-1">Outgoing</span>
                                <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                </svg>
                            </div>
                        </summary>

                        <div class="mt-2">
                            <div class="relative ml-4 pl-4 space-y-1">
                                <div
                                    class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent">
                                </div>

                                <a href="{{ route('outgoing.daily-planning') }}" @class([$subLinkBase, $subActive => request()->routeIs('outgoing.daily-planning'), $subInactive => !request()->routeIs('outgoing.daily-planning')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.daily-planning'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.daily-planning')])></span>
                                    <span class="flex-1">Customers Daily Planning</span>
                                </a>

                                <a href="{{ route('outgoing.delivery-requirements') }}" @class([$subLinkBase, $subActive => request()->routeIs('outgoing.delivery-requirements'), $subInactive => !request()->routeIs('outgoing.delivery-requirements')])
                                    @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.delivery-requirements'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.delivery-requirements')])></span>
                                    <span class="flex-1">Delivery Requirements</span>
                                </a>
                                <a href="{{ route('outgoing.stock-at-customers') }}" @class([$subLinkBase, $subActive => request()->routeIs('outgoing.stock-at-customers'), $subInactive => !request()->routeIs('outgoing.stock-at-customers')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.stock-at-customers'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.stock-at-customers')])></span>
                                    <span class="flex-1">Stock at Customers</span>
                                </a>
                                <a href="{{ route('outgoing.delivery-plan') }}" @class([$subLinkBase, $subActive => request()->routeIs('outgoing.delivery-plan'), $subInactive => !request()->routeIs('outgoing.delivery-plan')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.delivery-plan'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.delivery-plan')])></span>
                                    <span class="flex-1">Delivery Plan &amp; Arrangement</span>
                                </a>
                                <a href="{{ route('outgoing.delivery-notes.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('outgoing.delivery-notes.*'), $subInactive => !request()->routeIs('outgoing.delivery-notes.*')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.delivery-notes.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.delivery-notes.*')])></span>
                                    <span class="flex-1 text-indigo-600 font-bold">Delivery Notes (Surat Jalan)</span>
                                </a>
                                <a href="{{ route('outgoing.product-mapping') }}" @class([$subLinkBase, $subActive => request()->routeIs('outgoing.product-mapping'), $subInactive => !request()->routeIs('outgoing.product-mapping')]) @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-emerald-600' => request()->routeIs('outgoing.product-mapping'), 'bg-slate-300 group-hover:bg-emerald-400' => !request()->routeIs('outgoing.product-mapping')])></span>
                                    <span class="flex-1 font-bold text-emerald-700">🔍 Product & Where-Used</span>
                                </a>
                                <a href="{{ route('outgoing.standard-packings.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('outgoing.standard-packings.*'), $subInactive => !request()->routeIs('outgoing.standard-packings.*')])
                                    @click="mobileSidebarOpen = false">
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.standard-packings.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.standard-packings.*')])></span>
                                    <span class="flex-1">Standard Packing</span>
                                </a>
                            </div>
                        </div>
                    </details>
                </div>
            @endcan

            @can('manage_inventory')
                <div>
                    <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Inventory</div>
                    <div class="space-y-1">
                        <a href="{{ route('inventory.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('inventory.index'), $navInactive => !request()->routeIs('inventory.index')])
                            @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 6V4h10v2" />
                            </svg>
                            <span class="ml-3 flex-1">Inventory</span>
                        </a>
                        <a href="{{ route('inventory.receives') }}" @class([$navLinkBase, $navActive => request()->routeIs('inventory.receives'), $navInactive => !request()->routeIs('inventory.receives')]) @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                            <span class="ml-3 flex-1">Inventory Receives</span>
                        </a>
                        <a href="{{ route('inventory.transfers.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('inventory.transfers.*'), $navInactive => !request()->routeIs('inventory.transfers.*')]) @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 11h8M8 15h8" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 3h5v5" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 3l-6 6" />
                            </svg>
                            <span class="ml-3 flex-1">Inventory Transfers</span>
                        </a>
                        {{-- GCI Inventory hidden --}}
                    </div>
                </div>
            @endcan

            @can('manage_inventory')
                <div>
                    <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Warehouse</div>
                    <div class="space-y-1">
                        <a href="{{ route('logistics.dashboard') }}" @class([$navLinkBase, $navActive => $logisticsActive, $navInactive => !$logisticsActive]) @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h3l3 8 4-16 3 8h5" />
                            </svg>
                            <span class="ml-3 flex-1">Logistics Dashboard</span>
                        </a>
                        <a href="{{ route('warehouse.labels.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.labels.*'), $navInactive => !request()->routeIs('warehouse.labels.*')]) @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 0 1 0 2.828l-7 7a2 2 0 0 1-2.828 0l-7-7A1.994 1.994 0 0 1 3 12V7a4 4 0 0 1 4-4z" />
                            </svg>
                            <span class="ml-3 flex-1">Barcode Labels</span>
                        </a>
                        <a href="{{ route('warehouse.qc.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.qc.*'), $navInactive => !request()->routeIs('warehouse.qc.*')])
                            @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h3m2.25-12.75 1.5 1.5M6.75 4.5l1.5 1.5M6 2h12a2 2 0 0 1 2 2v18H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" />
                            </svg>
                            <span class="ml-3 flex-1">QC Queue</span>
                        </a>
                        <a href="{{ route('warehouse.putaway.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.putaway.*'), $navInactive => !request()->routeIs('warehouse.putaway.*')]) @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m9-9H3" />
                            </svg>
                            <span class="ml-3 flex-1">Putaway Queue</span>
                        </a>
                        <a href="{{ route('inventory.locations.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('inventory.locations.*'), $navInactive => !request()->routeIs('inventory.locations.*')]) @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11Z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                            </svg>
                            <span class="ml-3 flex-1">Warehouse Locations</span>
                        </a>
                        <a href="{{ route('warehouse.trollies.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.trollies.*'), $navInactive => !request()->routeIs('warehouse.trollies.*')]) @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="ml-3 flex-1">Trollies</span>
                        </a>
                        <a href="{{ route('warehouse.bin-transfers.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.bin-transfers.*'), $navInactive => !request()->routeIs('warehouse.bin-transfers.*')]) @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 11h10M7 15h10" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 19l-2-2 2-2" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 15l2 2-2 2" />
                            </svg>
                            <span class="ml-3 flex-1">Bin to Bin</span>
                        </a>
                        <a href="{{ route('warehouse.stock.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.stock.*'), $navInactive => !request()->routeIs('warehouse.stock.*')]) @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7v10M12 7v10M17 7v10" />
                            </svg>
                            <span class="ml-3 flex-1">Stock by Location</span>
                        </a>
                        <a href="{{ route('warehouse.stock-adjustments.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.stock-adjustments.*'), $navInactive => !request()->routeIs('warehouse.stock-adjustments.*')]) @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12h12" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16" />
                            </svg>
                            <span class="ml-3 flex-1">Stock Adjustments</span>
                        </a>
                        <a href="{{ route('warehouse.stock-opname.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.stock-opname.*'), $navInactive => !request()->routeIs('warehouse.stock-opname.*')]) @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <span class="ml-3 flex-1">Stock Opname</span>
                        </a>
                        <a href="{{ route('warehouse.stock.reconcile') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.stock.reconcile'), $navInactive => !request()->routeIs('warehouse.stock.reconcile')]) @click="mobileSidebarOpen = false">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 11h10M7 15h6" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 15l2 2-2 2" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 19l-2-2 2-2" />
                            </svg>
                            <span class="ml-3 flex-1">Reconcile Stock</span>
                        </a>
                    </div>
                </div>
            @endcan
        </nav>

        <div class="border-t border-slate-200 px-4 py-4">
            <a href="{{ route('profile.edit') }}" class="{{ $navLinkBase }} {{ $navInactive }}"
                @click="mobileSidebarOpen = false">
                <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
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
    class="hidden md:flex sticky top-0 z-30 h-screen shrink-0 flex-col border-r border-slate-200 bg-white transition-all duration-200 overflow-hidden"
    :class="sidebarCollapsed ? 'w-20' : 'w-72'" @mouseenter="expandSidebar()" @mouseleave="collapseSidebar()"
    @click="if ($event.target.closest('a')) collapseSidebar()">
    <div class="px-4 pt-6">
        <div class="flex items-center rounded-2xl border border-slate-200 bg-white shadow-sm px-4 py-4"
            :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
            <div
                class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white shadow-sm">
                <span class="text-sm font-bold tracking-wide">GCI</span>
            </div>
            <div x-show="!sidebarCollapsed" x-cloak>
                <div class="text-sm font-semibold text-slate-900 leading-5">Geum Cheon Indo</div>
                <div class="text-xs text-slate-500">Smart Application System</div>
            </div>
        </div>
    </div>

    <nav class="flex-1 min-h-0 overflow-y-auto px-4 pb-6 pt-6 space-y-6">
        <div class="space-y-1">
            <a href="{{ route('dashboard') }}" title="Dashboard" @class([$navLinkBase, $navActive => request()->routeIs('dashboard'), $navInactive => !request()->routeIs('dashboard')])
                :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5V21h6v-6h6v6h6v-7.5L12 3 3 10.5" />
                </svg>
                <span x-show="!sidebarCollapsed" x-cloak>Dashboard</span>
            </a>
        </div>

        @can('manage_planning')
            <div>
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                    x-show="!sidebarCollapsed" x-cloak>Master Data</div>
                <div class="space-y-1">
                    <a x-show="sidebarCollapsed" x-cloak href="{{ route('vendors.index') }}" title="Vendor List"
                        @class([$navLinkBase, $navActive => $vendorsActive, $navInactive => !$vendorsActive])
                        :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 0 0-3-3.87" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 3.13a4 4 0 0 1 0 7.75" />
                        </svg>
                    </a>

                    <details x-show="!sidebarCollapsed" x-cloak class="group" {{ $vendorsActive ? 'open' : '' }}>
                        <summary class="list-none cursor-pointer" title="Vendors"
                            :class="sidebarCollapsed ? 'flex justify-center' : ''">
                            <div @class([$navLinkBase, $navActive => $vendorsActive, $navInactive => !$vendorsActive])
                                :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M7.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                                <span x-show="!sidebarCollapsed" x-cloak class="flex-1">Vendor</span>
                                <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2" x-show="!sidebarCollapsed" x-cloak>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                </svg>
                            </div>
                        </summary>
                        <div class="mt-2">
                            <div class="relative ml-4 pl-4 space-y-1">
                                <div
                                    class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent">
                                </div>

                                <a href="{{ route('vendors.create') }}" @class([$subLinkBase, $subActive => request()->routeIs('vendors.create'), $subInactive => !request()->routeIs('vendors.create')])>
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('vendors.create'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('vendors.create')])></span>
                                    <span class="flex-1">Create Vendor</span>
                                </a>
                                <a href="{{ route('vendors.index') }}" @class([
                                    $subLinkBase,
                                    $subActive => request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'),
                                    $subInactive => !(request()->routeIs('vendors.index') || request()->routeIs('vendors.edit')),
                                ])>
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'), 'bg-slate-300 group-hover:bg-indigo-400' => !(request()->routeIs('vendors.index') || request()->routeIs('vendors.edit'))])></span>
                                    <span class="flex-1">Vendor List</span>
                                </a>
                            </div>
                        </div>
                    </details>

                    <a x-show="sidebarCollapsed" x-cloak href="{{ route('parts.index') }}" title="Existing Part List"
                        @class([$navLinkBase, $navActive => $partsActive, $navInactive => !$partsActive])
                        :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.29 7L12 12l8.71-5" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12" />
                        </svg>
                    </a>

                    <details x-show="!sidebarCollapsed" x-cloak class="group" {{ $partsActive ? 'open' : '' }}>
                        <summary class="list-none cursor-pointer" title="Parts"
                            :class="sidebarCollapsed ? 'flex justify-center' : ''">
                            <div @class([$navLinkBase, $navActive => $partsActive, $navInactive => !$partsActive])
                                :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.29 7L12 12l8.71-5" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 22V12" />
                                </svg>
                                <span x-show="!sidebarCollapsed" x-cloak class="flex-1">Part</span>
                                <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2" x-show="!sidebarCollapsed" x-cloak>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                </svg>
                            </div>
                        </summary>
                        <div class="mt-2">
                            <div class="relative ml-4 pl-4 space-y-1">
                                <div
                                    class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent">
                                </div>

                                <a href="{{ route('parts.create') }}" @class([$subLinkBase, $subActive => request()->routeIs('parts.create'), $subInactive => !request()->routeIs('parts.create')])>
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('parts.create'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('parts.create')])></span>
                                    <span class="flex-1">Register Part</span>
                                </a>
                                <a href="{{ route('parts.index') }}" @class([
                                    $subLinkBase,
                                    $subActive => request()->routeIs('parts.index') || request()->routeIs('parts.edit'),
                                    $subInactive => !(request()->routeIs('parts.index') || request()->routeIs('parts.edit')),
                                ])>
                                    <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('parts.index') || request()->routeIs('parts.edit'), 'bg-slate-300 group-hover:bg-indigo-400' => !(request()->routeIs('parts.index') || request()->routeIs('parts.edit'))])></span>
                                    <span class="flex-1">Existing Part List</span>
                                </a>
                            </div>
                        </div>
                    </details>

                    <a href="{{ route('truckings.index') }}" title="Truckings" @class([$navLinkBase, $navActive => request()->routeIs('truckings.*'), $navInactive => !request()->routeIs('truckings.*')])
                        :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h11v10H3V7Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4l3 3v4h-7v-7Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 21a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                        </svg>
                        <span x-show="!sidebarCollapsed" x-cloak>Truckings</span>
                    </a>
                </div>
            </div>
        @endcan

        @can('view_planning')
            <div>
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                    x-show="!sidebarCollapsed" x-cloak>Planning</div>

                <details class="group" {{ request()->routeIs('planning.*') ? 'open' : '' }}
                    x-effect="if (sidebarCollapsed) $el.removeAttribute('open')">
                    <summary class="list-none cursor-pointer" title="Planning"
                        :class="sidebarCollapsed ? 'flex justify-center' : ''">
                        <div @class([$navLinkBase, $navActive => request()->routeIs('planning.*'), $navInactive => !request()->routeIs('planning.*')]) :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-cloak class="flex-1">Planning</span>
                            <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2" x-show="!sidebarCollapsed" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </summary>

                    <div class="mt-2" x-show="!sidebarCollapsed" x-cloak>
                        <div class="relative ml-4 pl-4 space-y-1">
                            <div
                                class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent">
                            </div>

                            <a href="{{ route('planning.customers.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.customers.*'), $subInactive => !request()->routeIs('planning.customers.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.customers.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.customers.*')])></span>
                                <span class="flex-1">Customers</span>
                            </a>
                            <a href="{{ route('planning.fg-parts.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.fg-parts.*'), $subInactive => !request()->routeIs('planning.fg-parts.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.fg-parts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.fg-parts.*')])></span>
                                <span class="flex-1">FG Part</span>
                            </a>
                            <a href="{{ route('planning.wip-parts.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.wip-parts.*'), $subInactive => !request()->routeIs('planning.wip-parts.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.wip-parts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.wip-parts.*')])></span>
                                <span class="flex-1">WIP Part</span>
                            </a>
                            <a href="{{ route('planning.rm-parts.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.rm-parts.*'), $subInactive => !request()->routeIs('planning.rm-parts.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.rm-parts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.rm-parts.*')])></span>
                                <span class="flex-1">RM Part</span>
                            </a>
                            <a href="{{ route('planning.customer-parts.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.customer-parts.*'), $subInactive => !request()->routeIs('planning.customer-parts.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.customer-parts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.customer-parts.*')])></span>
                                <span class="flex-1">Customer Part Mapping</span>
                            </a>
                            <a href="{{ route('planning.planning-imports.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.planning-imports.*'), $subInactive => !request()->routeIs('planning.planning-imports.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.planning-imports.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.planning-imports.*')])></span>
                                <span class="flex-1">Customer Planning</span>
                            </a>
                            <a href="{{ route('planning.customer-pos.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.customer-pos.*'), $subInactive => !request()->routeIs('planning.customer-pos.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.customer-pos.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.customer-pos.*')])></span>
                                <span class="flex-1">Customer PO</span>
                            </a>
                            <a href="{{ route('planning.forecasts.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.forecasts.*'), $subInactive => !request()->routeIs('planning.forecasts.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.forecasts.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.forecasts.*')])></span>
                                <span class="flex-1">Forecast (Part GCI)</span>
                            </a>
                            <a href="{{ route('planning.mps.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.mps.*'), $subInactive => !request()->routeIs('planning.mps.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.mps.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.mps.*')])></span>
                                <span class="flex-1">MPS</span>
                            </a>
                            <a href="{{ route('planning.boms.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.boms.*'), $subInactive => !request()->routeIs('planning.boms.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.boms.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.boms.*')])></span>
                                <span class="flex-1">BOM GCI</span>
                            </a>
                            <a href="{{ route('planning.mrp.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('planning.mrp.*'), $subInactive => !request()->routeIs('planning.mrp.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('planning.mrp.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('planning.mrp.*')])></span>
                                <span class="flex-1">MRP</span>
                            </a>
                        </div>
                    </div>
                </details>
            </div>
        @endcan

        @can('manage_purchasing')
            <div>
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                    x-show="!sidebarCollapsed" x-cloak>Purchasing</div>

                <details class="group" {{ $purchasingActive ? 'open' : '' }}
                    x-effect="if (sidebarCollapsed) $el.removeAttribute('open')">
                    <summary class="list-none cursor-pointer" title="Purchasing"
                        :class="sidebarCollapsed ? 'flex justify-center' : ''">
                        <div @class([$navLinkBase, $navActive => $purchasingActive, $navInactive => !$purchasingActive])
                            :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-cloak class="flex-1">Purchasing</span>
                            <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2" x-show="!sidebarCollapsed" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </summary>

                    <div class="mt-2" x-show="!sidebarCollapsed" x-cloak>
                        <div class="relative ml-4 pl-4 space-y-1">
                            <div
                                class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent">
                            </div>

                            <a href="{{ route('purchasing.purchase-requests.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('purchasing.purchase-requests.*'), $subInactive => !request()->routeIs('purchasing.purchase-requests.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('purchasing.purchase-requests.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('purchasing.purchase-requests.*')])></span>
                                <span class="flex-1">Purchase Requests</span>
                            </a>
                            <a href="{{ route('purchasing.purchase-orders.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('purchasing.purchase-orders.*'), $subInactive => !request()->routeIs('purchasing.purchase-orders.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('purchasing.purchase-orders.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('purchasing.purchase-orders.*')])></span>
                                <span class="flex-1">Purchase Orders</span>
                            </a>
                        </div>
                    </div>
                </details>
            </div>
        @endcan

        @can('view_production')
            <div>
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                    x-show="!sidebarCollapsed" x-cloak>Production</div>

                <details class="group" {{ $productionActive ? 'open' : '' }}
                    x-effect="if (sidebarCollapsed) $el.removeAttribute('open')">
                    <summary class="list-none cursor-pointer" title="Production"
                        :class="sidebarCollapsed ? 'flex justify-center' : ''">
                        <div @class([$navLinkBase, $navActive => $productionActive, $navInactive => !$productionActive])
                            :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-cloak class="flex-1">Production</span>
                            <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2" x-show="!sidebarCollapsed" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </summary>

                    <div class="mt-2" x-show="!sidebarCollapsed" x-cloak>
                        <div class="relative ml-4 pl-4 space-y-1">
                            <div
                                class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent">
                            </div>

                            <a href="{{ route('production.orders.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.orders.*'), $subInactive => !request()->routeIs('production.orders.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.orders.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.orders.*')])></span>
                                <span class="flex-1">Production Orders</span>
                            </a>
                            <a href="{{ route('production.work-orders.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.work-orders.*'), $subInactive => !request()->routeIs('production.work-orders.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.work-orders.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.work-orders.*')])></span>
                                <span class="flex-1">Work Order & Kanban Release</span>
                            </a>
                            <a href="{{ route('production.material-availability.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.material-availability.*'), $subInactive => !request()->routeIs('production.material-availability.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.material-availability.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.material-availability.*')])></span>
                                <span class="flex-1">Material Availability</span>
                            </a>
                            <a href="{{ route('production.start-production.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.start-production.*'), $subInactive => !request()->routeIs('production.start-production.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.start-production.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.start-production.*')])></span>
                                <span class="flex-1">Start Production</span>
                            </a>
                            <a href="{{ route('production.qc-inspection.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.qc-inspection.*'), $subInactive => !request()->routeIs('production.qc-inspection.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.qc-inspection.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.qc-inspection.*')])></span>
                                <span class="flex-1">QC Inspection</span>
                            </a>
                            <a href="{{ route('production.mass-production.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.mass-production.*'), $subInactive => !request()->routeIs('production.mass-production.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.mass-production.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.mass-production.*')])></span>
                                <span class="flex-1">Mass Production</span>
                            </a>
                            <a href="{{ route('production.in-process-inspection.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.in-process-inspection.*'), $subInactive => !request()->routeIs('production.in-process-inspection.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.in-process-inspection.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.in-process-inspection.*')])></span>
                                <span class="flex-1">In-Process Inspection</span>
                            </a>
                            <a href="{{ route('production.finish-production.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.finish-production.*'), $subInactive => !request()->routeIs('production.finish-production.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.finish-production.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.finish-production.*')])></span>
                                <span class="flex-1">Finish Production</span>
                            </a>
                            <a href="{{ route('production.final-inspection.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('production.final-inspection.*'), $subInactive => !request()->routeIs('production.final-inspection.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('production.final-inspection.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('production.final-inspection.*')])></span>
                                <span class="flex-1">Final Inspection → Kanban Update & Inventory</span>
                            </a>
                        </div>
                    </div>
                </details>
            </div>
        @endcan

        @can('manage_incoming')
            <div>
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                    x-show="!sidebarCollapsed" x-cloak>Incoming</div>

                <details class="group" {{ $incomingModuleActive ? 'open' : '' }}
                    x-effect="if (sidebarCollapsed) $el.removeAttribute('open')">
                    <summary class="list-none cursor-pointer" title="Incoming Material"
                        :class="sidebarCollapsed ? 'flex justify-center' : ''">
                        <div @class([$navLinkBase, $navActive => $incomingModuleActive, $navInactive => !$incomingModuleActive]) :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 7.5V6a2 2 0 0 1 2-2h5l2 2h6a2 2 0 0 1 2 2v9.5a2 2 0 0 1-2 2h-6l-2 2H5a2 2 0 0 1-2-2V7.5Z" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-cloak class="flex-1">Incoming</span>
                            <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2" x-show="!sidebarCollapsed" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </summary>

                    <div class="mt-2" x-show="!sidebarCollapsed" x-cloak>
                        <div class="relative ml-4 pl-4 space-y-1">
                            <div
                                class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent">
                            </div>
                            <a href="{{ route('departures.create') }}" @class([$subLinkBase, $subActive => request()->routeIs('departures.create'), $subInactive => !request()->routeIs('departures.create')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('departures.create'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('departures.create')])></span>
                                <span class="flex-1">Create Departure</span>
                            </a>
                            <a href="{{ route('departures.index') }}" @class([
                                $subLinkBase,
                                $subActive => request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit'),
                                $subInactive => !(request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit')),
                            ])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit'), 'bg-slate-300 group-hover:bg-indigo-400' => !(request()->routeIs('departures.index') || request()->routeIs('departures.show') || request()->routeIs('departures.edit'))])></span>
                                <span class="flex-1">Departure List</span>
                            </a>
                            <a href="{{ route('local-pos.create') }}" @class([$subLinkBase, $subActive => request()->routeIs('local-pos.create'), $subInactive => !request()->routeIs('local-pos.create')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('local-pos.create'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('local-pos.create')])></span>
                                <span class="flex-1">Create Local PO</span>
                            </a>
                            <a href="{{ route('local-pos.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('local-pos.index'), $subInactive => !request()->routeIs('local-pos.index')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('local-pos.index'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('local-pos.index')])></span>
                                <span class="flex-1">Local PO List</span>
                            </a>
                            <a href="{{ route('receives.index') }}" @class([
                                $subLinkBase,
                                $subActive => request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*'),
                                $subInactive => !(request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*')),
                            ])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !(request()->routeIs('receives.index') || request()->routeIs('receives.create') || request()->routeIs('receives.invoice.*'))])></span>
                                <span class="flex-1">Process Receives</span>
                            </a>
                            <a href="{{ route('receives.completed') }}" @class([
                                $subLinkBase,
                                $subActive => request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice'),
                                $subInactive => !(request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice')),
                            ])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice'), 'bg-slate-300 group-hover:bg-indigo-400' => !(request()->routeIs('receives.completed') || request()->routeIs('receives.completed.invoice'))])></span>
                                <span class="flex-1">Completed Receives</span>
                            </a>
                        </div>
                    </div>
                </details>
            </div>
        @endcan

        @can('manage_outgoing')
            <div>
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                    x-show="!sidebarCollapsed" x-cloak>Outgoing</div>

                <details class="group" {{ $outgoingModuleActive ? 'open' : '' }}
                    x-effect="if (sidebarCollapsed) $el.removeAttribute('open')">
                    <summary class="list-none cursor-pointer" title="Outgoing"
                        :class="sidebarCollapsed ? 'flex justify-center' : ''">
                        <div @class([$navLinkBase, $navActive => $outgoingModuleActive, $navInactive => !$outgoingModuleActive]) :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 7h14l4 4v10a2 2 0 0 1-2 2H3V7Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 11h10M7 15h10M7 19h6" />
                            </svg>
                            <span x-show="!sidebarCollapsed" x-cloak class="flex-1">Outgoing</span>
                            <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2" x-show="!sidebarCollapsed" x-cloak>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </div>
                    </summary>

                    <div class="mt-2" x-show="!sidebarCollapsed" x-cloak>
                        <div class="relative ml-4 pl-4 space-y-1">
                            <div
                                class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent">
                            </div>
                            <a href="{{ route('outgoing.daily-planning') }}" @class([$subLinkBase, $subActive => request()->routeIs('outgoing.daily-planning'), $subInactive => !request()->routeIs('outgoing.daily-planning')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.daily-planning'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.daily-planning')])></span>
                                <span class="flex-1">Customers Daily Planning</span>
                            </a>
                            <a href="{{ route('outgoing.delivery-requirements') }}" @class([$subLinkBase, $subActive => request()->routeIs('outgoing.delivery-requirements'), $subInactive => !request()->routeIs('outgoing.delivery-requirements')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.delivery-requirements'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.delivery-requirements')])></span>
                                <span class="flex-1">Delivery Requirements</span>
                            </a>
                            <a href="{{ route('outgoing.stock-at-customers') }}" @class([$subLinkBase, $subActive => request()->routeIs('outgoing.stock-at-customers'), $subInactive => !request()->routeIs('outgoing.stock-at-customers')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.stock-at-customers'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.stock-at-customers')])></span>
                                <span class="flex-1">Stock at Customers</span>
                            </a>
                            <a href="{{ route('outgoing.delivery-plan') }}" @class([$subLinkBase, $subActive => request()->routeIs('outgoing.delivery-plan'), $subInactive => !request()->routeIs('outgoing.delivery-plan')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.delivery-plan'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.delivery-plan')])></span>
                                <span class="flex-1">Delivery Plan &amp; Arrangement</span>
                            </a>
                            <a href="{{ route('outgoing.sales-orders.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('outgoing.sales-orders.*'), $subInactive => !request()->routeIs('outgoing.sales-orders.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.sales-orders.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.sales-orders.*')])></span>
                                <span class="flex-1 font-bold text-slate-800">PO Outgoing (Sales Orders)</span>
                            </a>
                            <a href="{{ route('outgoing.delivery-notes.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('outgoing.delivery-notes.*'), $subInactive => !request()->routeIs('outgoing.delivery-notes.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.delivery-notes.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.delivery-notes.*')])></span>
                                <span class="flex-1 text-indigo-600 font-bold">Delivery Notes (Surat Jalan)</span>
                            </a>
                            <a href="{{ route('outgoing.product-mapping') }}" @class([$subLinkBase, $subActive => request()->routeIs('outgoing.product-mapping'), $subInactive => !request()->routeIs('outgoing.product-mapping')])>
                                <span @class([$subDotBase, 'bg-emerald-600' => request()->routeIs('outgoing.product-mapping'), 'bg-slate-300 group-hover:bg-emerald-400' => !request()->routeIs('outgoing.product-mapping')])></span>
                                <span class="flex-1 font-bold text-emerald-700">🔍 Product & Where-Used Mapping</span>
                            </a>
                            <a href="{{ route('outgoing.standard-packings.index') }}" @class([$subLinkBase, $subActive => request()->routeIs('outgoing.standard-packings.*'), $subInactive => !request()->routeIs('outgoing.standard-packings.*')])>
                                <span @class([$subDotBase, 'bg-indigo-600' => request()->routeIs('outgoing.standard-packings.*'), 'bg-slate-300 group-hover:bg-indigo-400' => !request()->routeIs('outgoing.standard-packings.*')])></span>
                                <span class="flex-1">Standard Packing</span>
                            </a>
                        </div>
                    </div>
                </details>
            </div>
        @endcan

        @can('manage_inventory')
            <div>
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                    x-show="!sidebarCollapsed" x-cloak>Inventory</div>
                <div class="space-y-1" x-show="!sidebarCollapsed" x-cloak>
                    <a href="{{ route('inventory.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('inventory.index'), $navInactive => !request()->routeIs('inventory.index')])>
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 6V4h10v2" />
                        </svg>
                        <span class="ml-3 flex-1">Inventory</span>
                    </a>
                    <a href="{{ route('inventory.receives') }}" @class([$navLinkBase, $navActive => request()->routeIs('inventory.receives'), $navInactive => !request()->routeIs('inventory.receives')])>
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                        <span class="ml-3 flex-1">Inventory Receives</span>
                    </a>
                    <a href="{{ route('inventory.transfers.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('inventory.transfers.*'), $navInactive => !request()->routeIs('inventory.transfers.*')])>
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8M8 11h8M8 15h8" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 3h5v5" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 3l-6 6" />
                        </svg>
                        <span class="ml-3 flex-1">Inventory Transfers</span>
                    </a>
                    {{-- GCI Inventory hidden --}}
                </div>
            </div>
        @endcan

        @can('manage_inventory')
            <div>
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                    x-show="!sidebarCollapsed" x-cloak>Warehouse</div>
                <div class="space-y-1" x-show="!sidebarCollapsed" x-cloak>
                    <a href="{{ route('logistics.dashboard') }}" @class([$navLinkBase, $navActive => $logisticsActive, $navInactive => !$logisticsActive])>
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h3l3 8 4-16 3 8h5" />
                        </svg>
                        <span class="ml-3 flex-1">Logistics Dashboard</span>
                    </a>
                    <a href="{{ route('warehouse.labels.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.labels.*'), $navInactive => !request()->routeIs('warehouse.labels.*')])>
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 0 1 0 2.828l-7 7a2 2 0 0 1-2.828 0l-7-7A1.994 1.994 0 0 1 3 12V7a4 4 0 0 1 4-4z" />
                        </svg>
                        <span class="ml-3 flex-1">Barcode Labels</span>
                    </a>
                    <a href="{{ route('warehouse.qc.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.qc.*'), $navInactive => !request()->routeIs('warehouse.qc.*')])>
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h6m-6 4h3m2.25-12.75 1.5 1.5M6.75 4.5l1.5 1.5M6 2h12a2 2 0 0 1 2 2v18H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" />
                        </svg>
                        <span class="ml-3 flex-1">QC Queue</span>
                    </a>
                    <a href="{{ route('warehouse.putaway.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.putaway.*'), $navInactive => !request()->routeIs('warehouse.putaway.*')])>
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m9-9H3" />
                        </svg>
                        <span class="ml-3 flex-1">Putaway Queue</span>
                    </a>
                    <a href="{{ route('inventory.locations.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('inventory.locations.*'), $navInactive => !request()->routeIs('inventory.locations.*')])>
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                        </svg>
                        <span class="ml-3 flex-1">Warehouse Locations</span>
                    </a>
                    <a href="{{ route('warehouse.trollies.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.trollies.*'), $navInactive => !request()->routeIs('warehouse.trollies.*')])>
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="ml-3 flex-1">Trollies</span>
                    </a>
                    <a href="{{ route('warehouse.bin-transfers.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.bin-transfers.*'), $navInactive => !request()->routeIs('warehouse.bin-transfers.*')])>
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 11h10M7 15h10" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 19l-2-2 2-2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 15l2 2-2 2" />
                        </svg>
                        <span class="ml-3 flex-1">Bin to Bin</span>
                    </a>
                    <a href="{{ route('warehouse.stock.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.stock.*'), $navInactive => !request()->routeIs('warehouse.stock.*')])>
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7v10M12 7v10M17 7v10" />
                        </svg>
                        <span class="ml-3 flex-1">Stock by Location</span>
                    </a>
                    <a href="{{ route('warehouse.stock-adjustments.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.stock-adjustments.*'), $navInactive => !request()->routeIs('warehouse.stock-adjustments.*')])>
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12h12" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16" />
                        </svg>
                        <span class="ml-3 flex-1">Stock Adjustments</span>
                    </a>
                    <a href="{{ route('warehouse.stock-opname.index') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.stock-opname.*'), $navInactive => !request()->routeIs('warehouse.stock-opname.*')])>
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span class="ml-3 flex-1">Stock Opname</span>
                    </a>
                    <a href="{{ route('warehouse.stock.reconcile') }}" @class([$navLinkBase, $navActive => request()->routeIs('warehouse.stock.reconcile'), $navInactive => !request()->routeIs('warehouse.stock.reconcile')])>
                        <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 11h10M7 15h6" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 15l2 2-2 2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 19l-2-2 2-2" />
                        </svg>
                        <span class="ml-3 flex-1">Reconcile Stock</span>
                    </a>
                </div>
            </div>
        @endcan
    </nav>

    <div class="px-4 pb-5">
        <a href="{{ route('profile.edit') }}" title="Profile" class="{{ $navLinkBase }} {{ $navInactive }}"
            :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
            <svg xmlns="http://www.w3.org/2000/svg" class="{{ $navIconBase }}" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 0 0-16 0" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 13a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" />
            </svg>
            <span x-show="!sidebarCollapsed" x-cloak>Profile</span>
        </a>
        <div class="mt-3 px-2 text-xs text-slate-400" x-show="!sidebarCollapsed" x-cloak>© {{ date('Y') }} Geum Cheon
            Indo</div>
    </div>
</aside>