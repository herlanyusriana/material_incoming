@extends('outgoing.layout')

@section('content')
<div class="min-h-screen bg-slate-50" x-data="deliveryPlanUI()">
    {{-- Header --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Delivery Plan & Arrangement</h1>
                    <p class="text-slate-600 mt-1">Manage delivery schedules with PO details</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <a href="{{ route('outgoing.trucks.index') }}" class="px-4 py-2 border border-slate-300 bg-white text-slate-700 rounded-lg hover:bg-slate-50 transition-colors font-semibold">
                        Manage Trucks
                    </a>
                    <a href="{{ route('outgoing.drivers.index') }}" class="px-4 py-2 border border-slate-300 bg-white text-slate-700 rounded-lg hover:bg-slate-50 transition-colors font-semibold">
                        Manage Drivers
                    </a>
                    <input
                        type="date"
                        x-model="selectedDate"
                        class="px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700"
                    />
                    <button 
                        @click="openCreateModal()"
                        class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2 font-semibold"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                             <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Plan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-6">
        <div class="grid grid-cols-12 gap-6">
            {{-- Left Panel - Delivery Plans --}}
            <div class="col-span-8 space-y-4">
                <template x-for="plan in deliveryPlans" :key="plan.id">
                    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm transition-all duration-200 hover:shadow-md">
                        {{-- Plan Header --}}
                        <div class="p-4 bg-slate-50 border-b border-slate-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-indigo-600 flex items-center justify-center shadow-sm">
                                        <span class="text-white font-bold text-lg" x-text="'#' + plan.sequence"></span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-bold text-slate-900" x-text="plan.id"></h3>
                                            <span 
                                                class="px-3 py-1 rounded-full text-xs font-semibold border"
                                                :class="getStatusColor(plan.status)"
                                                x-text="getStatusText(plan.status)"
                                            ></span>
                                        </div>
                                        <div class="flex items-center gap-4 mt-1 text-sm text-slate-500 font-medium">
                                            <div class="flex items-center gap-1.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <span x-text="plan.estimatedDeparture + ' - ' + plan.estimatedReturn"></span>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <span x-text="plan.stops.length + (plan.stops.length > 1 ? ' stops' : ' stop')"></span>
                                            </div>
                                            <div class="flex items-center gap-1.5 px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded text-[10px] font-bold uppercase tracking-wider">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                                <span x-text="formatNumber(plan.cargo_summary.total_qty) + ' PCS • ' + plan.cargo_summary.total_items + ' Items'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button 
                                    @click="togglePlan(plan.id)"
                                    class="p-2 hover:bg-slate-200 rounded-lg transition-colors"
                                >
                                    <svg 
                                        xmlns="http://www.w3.org/2000/svg" 
                                        class="h-5 w-5 text-slate-500 transition-transform duration-200"
                                        :class="expandedPlan === plan.id ? 'rotate-180' : ''"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Truck & Driver --}}
                            <div class="grid grid-cols-2 gap-3 mt-4">
                                <div class="p-3 bg-white rounded-lg border border-slate-200 shadow-sm">
                                    <div class="flex items-center gap-2 mb-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 00-1 1v1H12" />
                                        </svg>
                                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Truck</span>
                                    </div>
                                    <template x-if="getTruck(plan.truckId)">
                                        <div>
                                            <div class="font-bold text-slate-900" x-text="getTruck(plan.truckId).plateNo"></div>
                                            <div class="text-xs text-slate-500 font-medium" x-text="getTruck(plan.truckId).type + ' • ' + getTruck(plan.truckId).capacity"></div>
                                        </div>
                                    </template>
                                    <template x-if="!getTruck(plan.truckId)">
                                        <div class="text-sm text-orange-600 font-bold bg-orange-50 px-2 py-1 rounded inline-block mt-1">Not assigned</div>
                                    </template>
                                </div>

                                <div class="p-3 bg-white rounded-lg border border-slate-200 shadow-sm">
                                    <div class="flex items-center gap-2 mb-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Driver</span>
                                    </div>
                                    <template x-if="getDriver(plan.driverId)">
                                        <div>
                                            <div class="font-bold text-slate-900" x-text="getDriver(plan.driverId).name"></div>
                                            <div class="text-xs text-slate-500 font-medium" x-text="getDriver(plan.driverId).phone"></div>
                                        </div>
                                    </template>
                                    <template x-if="!getDriver(plan.driverId)">
                                        <div class="text-sm text-orange-600 font-bold bg-orange-50 px-2 py-1 rounded inline-block mt-1">Not assigned</div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Delivery Stops - Expanded --}}
                        <div x-show="expandedPlan === plan.id" x-collapse>
                            <div class="divide-y divide-slate-100 bg-white">
                                <template x-for="(stop, stopIndex) in plan.stops" :key="stop.id">
                                    <div class="p-5 hover:bg-slate-50/50 transition-colors">
                                        {{-- Stop Header --}}
                                        <div class="flex items-start gap-4 mb-4">
                                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600 text-white font-bold text-sm flex-shrink-0 shadow-sm">
                                                <span x-text="stopIndex + 1"></span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div>
                                                        <div class="font-bold text-slate-900 text-lg" x-text="stop.customer"></div>
                                                        <div class="text-xs font-bold text-slate-500 uppercase tracking-wide mt-1" x-text="stop.customerCode"></div>
                                                        <div class="text-sm text-slate-600 flex items-start gap-1.5 mt-1.5">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            </svg>
                                                            <span x-text="stop.address"></span>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-3">
                                                        <div class="flex items-center gap-1.5 text-sm font-medium text-slate-600 bg-slate-100 px-3 py-1 rounded-lg">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            <span>ETA: <span x-text="stop.estimatedTime"></span></span>
                                                        </div>
                                                        <span 
                                                            class="px-2.5 py-1 rounded-lg text-xs font-bold border"
                                                            :class="getStatusColor(stop.status)"
                                                            x-text="getStatusText(stop.status)"
                                                        ></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Delivery Orders --}}
                                        <div class="ml-12 space-y-3">
                                            <template x-for="order in stop.deliveryOrders" :key="order.id">
                                                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm ring-1 ring-slate-100">
                                                    {{-- DO Header --}}
                                                    <div class="flex items-center justify-between mb-3 pb-3 border-b border-slate-100">
                                                        <div class="flex items-center gap-3">
                                                            <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <div class="font-bold text-slate-900" x-text="order.id"></div>
                                                                <div class="text-xs text-slate-500 mt-0.5 font-medium">
                                                                    PO: <span class="text-slate-700" x-text="order.poNumber"></span> • Date: <span x-text="order.poDate"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- Products Table --}}
                                                    <div class="space-y-2">
                                                        <div class="grid grid-cols-12 gap-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider pb-1 px-1">
                                                            <div class="col-span-3">Part Number</div>
                                                            <div class="col-span-5">Product Name</div>
                                                            <div class="col-span-2 text-right">Quantity</div>
                                                            <div class="col-span-2 text-right">Weight</div>
                                                        </div>
                                                        <template x-for="(product, idx) in order.products" :key="idx">
                                                            <div class="grid grid-cols-12 gap-2 text-sm bg-slate-50/50 rounded-lg p-2 border border-slate-100 items-center">
                                                                <div class="col-span-3 font-mono font-bold text-indigo-700 text-xs" x-text="product.partNo"></div>
                                                                <div class="col-span-5 text-slate-700 font-medium text-xs truncate" x-text="product.partName" :title="product.partName"></div>
                                                                <div class="col-span-2 text-right font-bold text-slate-900 text-xs" x-text="formatNumber(product.quantity) + ' ' + product.unit"></div>
                                                                <div class="col-span-2 text-right text-slate-500 font-medium text-xs" x-text="product.weight"></div>
                                                            </div>
                                                        </template>
                                                    </div>

                                                    {{-- DO Summary --}}
                                                    <div class="mt-3 pt-3 border-t border-slate-100 flex justify-between text-xs font-medium">
                                                        <div class="text-slate-500">
                                                            Total Items: <span class="font-bold text-slate-900" x-text="order.products.length"></span>
                                                        </div>
                                                        <div class="text-slate-500">
                                                            Total Qty: <span class="font-bold text-slate-900" x-text="order.products.reduce((sum, p) => sum + p.quantity, 0) + ' pcs'"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Right Panel - Resources & Summary --}}
            <div class="col-span-4 space-y-6">
                {{-- Summary Stats --}}
                <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                    <h3 class="font-bold text-slate-900 mb-4 text-base">Today's Summary</h3>
                    <div class="space-y-4">
                        <div class="flex items-end justify-between">
                            <div>
                                <div class="text-3xl font-bold text-slate-900 leading-none" x-text="deliveryPlans.length"></div>
                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide mt-1">Total Deliveries</div>
                            </div>
                            <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-green-50 rounded-xl border border-green-100">
                                <div class="text-2xl font-bold text-green-700 leading-none" x-text="deliveryPlans.filter(p => p.status === 'completed').length"></div>
                                <div class="text-[10px] font-bold text-green-600 uppercase tracking-wide mt-1">Completed</div>
                            </div>
                            <div class="p-3 bg-blue-50 rounded-xl border border-blue-100">
                                <div class="text-2xl font-bold text-blue-700 leading-none" x-text="deliveryPlans.filter(p => p.status === 'in-progress').length"></div>
                                <div class="text-[10px] font-bold text-blue-600 uppercase tracking-wide mt-1">In Progress</div>
                            </div>
                            <div class="p-3 bg-purple-50 rounded-xl border border-purple-100">
                                <div class="text-2xl font-bold text-purple-700 leading-none" x-text="deliveryPlans.filter(p => p.status === 'scheduled').length"></div>
                                <div class="text-[10px] font-bold text-purple-600 uppercase tracking-wide mt-1">Scheduled</div>
                            </div>
                            <div class="p-3 bg-orange-50 rounded-xl border border-orange-100">
                                <div class="text-2xl font-bold text-orange-700 leading-none" x-text="deliveryPlans.filter(p => p.status === 'unassigned').length"></div>
                                <div class="text-[10px] font-bold text-orange-600 uppercase tracking-wide mt-1">Unassigned</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Available Trucks --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                        <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Available Trucks</h3>
                    </div>
                    <div class="p-3 space-y-2">
                        <template x-for="truck in trucks.filter(t => t.status === 'available')" :key="truck.id">
                            <div 
                                class="p-3 border border-slate-200 rounded-lg group hover:border-indigo-500 hover:bg-indigo-50 transition-all cursor-pointer bg-white"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-slate-100 rounded-lg text-slate-500 group-hover:bg-white group-hover:text-indigo-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 00-1 1v1H12" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 text-sm" x-text="truck.plateNo"></div>
                                            <div class="text-xs text-slate-500 font-medium" x-text="truck.type"></div>
                                        </div>
                                    </div>
                                    <div class="text-xs font-bold text-slate-600 bg-slate-100 px-2 py-1 rounded" x-text="truck.capacity"></div>
                                </div>
                            </div>
                        </template>
                        <template x-if="trucks.filter(t => t.status === 'available').length === 0">
                            <div class="text-center py-4 text-sm text-slate-400 italic">No trucks available</div>
                        </template>
                    </div>
                </div>

                {{-- Available Drivers --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                        <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Available Drivers</h3>
                    </div>
                    <div class="p-3 space-y-2">
                        <template x-for="driver in drivers.filter(d => d.status === 'available')" :key="driver.id">
                            <div 
                                class="p-3 border border-slate-200 rounded-lg group hover:border-indigo-500 hover:bg-indigo-50 transition-all cursor-pointer bg-white"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 group-hover:bg-white group-hover:text-indigo-600 transition-colors flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-bold text-slate-900 text-sm truncate" x-text="driver.name"></div>
                                        <div class="text-xs text-slate-500 font-medium flex items-center gap-1.5">
                                            <span x-text="driver.license"></span>
                                            <span class="text-slate-300">•</span>
                                            <span x-text="driver.phone"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="drivers.filter(d => d.status === 'available').length === 0">
                            <div class="text-center py-4 text-sm text-slate-400 italic">No drivers available</div>
                        </template>
                    </div>
                </div>

                {{-- Awaiting Assignment (SOs) --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Awaiting Assignment</h3>
                        <span class="bg-indigo-600 text-white text-[10px] px-2 py-0.5 rounded-full font-bold" x-text="unassignedSos.length"></span>
                    </div>
                    <div class="p-3 space-y-3 max-h-[500px] overflow-y-auto">
                        <template x-for="so in unassignedSos" :key="so.id">
                            <div class="p-4 border border-slate-200 rounded-xl bg-white shadow-sm hover:border-indigo-500 transition-all group">
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm" x-text="so.dn_no"></div>
                                        <div class="text-xs font-bold text-indigo-600 mt-0.5" x-text="so.customer"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-xs font-bold text-slate-900" x-text="formatNumber(so.totalQty) + ' PCS'"></div>
                                        <div class="text-[10px] text-slate-500 font-medium" x-text="so.itemCount + ' Items'"></div>
                                    </div>
                                </div>
                                <div class="flex gap-2 min-w-0">
                                    <select 
                                        class="flex-1 text-[10px] font-bold py-1 px-2 border border-slate-200 rounded-lg bg-slate-50 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                        x-model="selectedPlans[so.id]"
                                    >
                                        <option value="">Assign to Trip...</option>
                                        <template x-for="plan in deliveryPlans" :key="plan.id">
                                            <option :value="plan.id" x-text="'Trip #' + plan.sequence + ' (' + plan.id + ')'"></option>
                                        </template>
                                    </select>
                                    <button 
                                        @click="assignSo(so.id)"
                                        :disabled="!selectedPlans[so.id] || isAssigning"
                                        class="px-3 py-1 bg-indigo-600 text-white text-[10px] font-bold rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                    >
                                        Go
                                    </button>
                                </div>
                            </div>
                        </template>
                        <template x-if="unassignedSos.length === 0">
                            <div class="text-center py-8">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-200 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="text-xs text-slate-400 italic">All SOs are assigned</div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- Create Plan Modal --}}
    <div
        x-show="showCreateModal"
        style="display: none;"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div
                x-show="showCreateModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                @click="showCreateModal = false"
                aria-hidden="true"
            ></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div
                x-show="showCreateModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
            >
                <form action="{{ route('outgoing.delivery-plan.store') }}" method="POST">
                    @csrf
                    <div>
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100">
                            <svg class="h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Create New Delivery Plan</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Initialize a new delivery trip. You can assign a truck and driver now or later.</p>
                            </div>
                        </div>
                        
                        <div class="mt-5 space-y-4">
                            <div>
                                <label for="plan_date" class="block text-sm font-medium text-gray-700">Plan Date</label>
                                <input type="date" name="plan_date" id="plan_date" x-model="createForm.date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2">
                            </div>

                            <div>
                                <label for="truck_id" class="block text-sm font-medium text-gray-700">Truck (Optional)</label>
                                <select name="truck_id" id="truck_id" x-model="createForm.truckId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2 bg-white">
                                    <option value="">-- Select Truck --</option>
                                    <template x-for="truck in trucks" :key="truck.id">
                                        <option :value="truck.id" x-text="truck.plateNo + ' (' + truck.type + ')'"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label for="driver_id" class="block text-sm font-medium text-gray-700">Driver (Optional)</label>
                                <select name="driver_id" id="driver_id" x-model="createForm.driverId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm border p-2 bg-white">
                                    <option value="">-- Select Driver --</option>
                                    <template x-for="driver in drivers" :key="driver.id">
                                        <option :value="driver.id" x-text="driver.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                            Create
                        </button>
                        <button type="button" @click="showCreateModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('deliveryPlanUI', () => ({
            selectedDate: @json($selectedDate),
            expandedPlan: null,
            trucks: @json($trucks),
            drivers: @json($drivers),
            deliveryPlans: @json($deliveryPlans),
            unassignedSos: @json($unassignedSos),
            
            showCreateModal: false,
            isAssigning: false,
            selectedPlans: {},
            createForm: {
                date: '',
                truckId: '',
                driverId: ''
            },
            
            init() {
                this.$watch('selectedDate', (value) => {
                    if (value !== @json($selectedDate)) {
                        window.location.href = '?date=' + value;
                    }
                });
            },

            async assignSo(soId) {
                const planIdStr = this.selectedPlans[soId];
                if (!planIdStr) return;
                
                // Extract numeric ID from 'DP001' format if necessary, 
                // but our backend expects the ID. Let's look up the actual ID.
                const plan = this.deliveryPlans.find(p => p.id === planIdStr);
                if (!plan) return;

                this.isAssigning = true;
                try {
                    const response = await fetch('{{ route('outgoing.delivery-plan.assign-so') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            delivery_note_id: soId,
                            delivery_plan_id: planIdStr.replace('DP', '').replace(/^0+/, '')
                        })
                    });

                    if (response.ok) {
                        window.location.reload();
                    } else {
                        alert('Assignment failed. Please try again.');
                    }
                } catch (error) {
                    console.error('Error assigning SO:', error);
                    alert('An error occurred.');
                } finally {
                    this.isAssigning = false;
                }
            },

            openCreateModal() {
                this.createForm.date = this.selectedDate;
                this.createForm.truckId = '';
                this.createForm.driverId = '';
                this.showCreateModal = true;
            },

            getTruck(id) {
                return this.trucks.find(t => t.id === id);
            },
            getDriver(id) {
                return this.drivers.find(d => d.id === id);
            },
            togglePlan(id) {
                this.expandedPlan = this.expandedPlan === id ? null : id;
            },
            getStatusColor(status) {
                switch(status) {
                    case 'completed': return 'bg-green-100 text-green-700 border-green-200';
                    case 'in-progress': return 'bg-blue-100 text-blue-700 border-blue-200';
                    case 'scheduled': return 'bg-purple-100 text-purple-700 border-purple-200';
                    case 'pending': return 'bg-slate-100 text-slate-700 border-slate-200';
                    case 'unassigned': return 'bg-orange-100 text-orange-700 border-orange-200';
                    default: return 'bg-slate-100 text-slate-700 border-slate-200';
                }
            },
            getStatusText(status) {
                switch(status) {
                    case 'completed': return 'Completed';
                    case 'in-progress': return 'In Progress';
                    case 'scheduled': return 'Scheduled';
                    case 'pending': return 'Pending';
                    case 'unassigned': return 'Unassigned';
                    default: return status;
                }
            },
            formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(num);
            }
        }));
    });
</script>
@endsection
