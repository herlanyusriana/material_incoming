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
                    <input
                        type="date"
                        x-model="selectedDate"
                        class="px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-700"
                    />
                    <button class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2 font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
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
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('deliveryPlanUI', () => ({
            selectedDate: '2024-01-24',
            expandedPlan: 'DP001',
            trucks: [
                { id: 'T001', plateNo: 'B 1234 ABC', type: 'Box Truck', capacity: '5 Ton', status: 'available' },
                { id: 'T002', plateNo: 'B 5678 DEF', type: 'Flatbed', capacity: '8 Ton', status: 'available' },
                { id: 'T003', plateNo: 'B 9012 GHI', type: 'Box Truck', capacity: '5 Ton', status: 'in-use' },
            ],
            drivers: [
                { id: 'D001', name: 'Budi Santoso', phone: '0812-3456-7890', status: 'available', license: 'SIM B1' },
                { id: 'D002', name: 'Ahmad Yani', phone: '0813-4567-8901', status: 'available', license: 'SIM B2' },
                { id: 'D003', name: 'Slamet Riyadi', phone: '0814-5678-9012', status: 'on-delivery', license: 'SIM B1' },
            ],
            deliveryPlans: [
                {
                    id: 'DP001',
                    sequence: 1,
                    truckId: 'T003',
                    driverId: 'D003',
                    status: 'in-progress',
                    estimatedDeparture: '08:00',
                    estimatedReturn: '12:00',
                    stops: [
                        {
                            id: 'STOP-001',
                            customer: 'PT Toyota Manufacturing',
                            customerCode: 'CUST-TOY-001',
                            address: 'Jl. Industri No. 45, Karawang Barat',
                            estimatedTime: '09:00',
                            status: 'completed',
                            deliveryOrders: [
                                {
                                    id: 'DO-2024-001',
                                    poNumber: 'PO/TOY/2024/0115',
                                    poDate: '2024-01-15',
                                    products: [
                                        { partNo: 'TY-BRK-9001-A', partName: 'Brake Pad Front Assembly', quantity: 500, unit: 'PCS', weight: '250 kg' },
                                        { partNo: 'TY-BRK-9001-B', partName: 'Brake Pad Rear Assembly', quantity: 500, unit: 'PCS', weight: '250 kg' }
                                    ]
                                }
                            ]
                        },
                        {
                            id: 'STOP-002',
                            customer: 'PT Honda Precision Parts',
                            customerCode: 'CUST-HON-002',
                            address: 'Jl. Raya Bekasi KM 28, Bekasi',
                            estimatedTime: '10:30',
                            status: 'in-progress',
                            deliveryOrders: [
                                {
                                    id: 'DO-2024-002',
                                    poNumber: 'PO/HON/2024/0118',
                                    poDate: '2024-01-18',
                                    products: [
                                        { partNo: 'HD-SPK-4521', partName: 'Spark Plug NGK Standard', quantity: 1000, unit: 'PCS', weight: '50 kg' },
                                        { partNo: 'HD-FLT-7788', partName: 'Oil Filter Premium', quantity: 500, unit: 'PCS', weight: '75 kg' }
                                    ]
                                }
                            ]
                        }
                    ]
                },
                {
                    id: 'DP002',
                    sequence: 2,
                    truckId: 'T001',
                    driverId: 'D001',
                    status: 'scheduled',
                    estimatedDeparture: '13:00',
                    estimatedReturn: '17:00',
                    stops: [
                        {
                            id: 'STOP-003',
                            customer: 'PT Yamaha Indonesia Motor',
                            customerCode: 'CUST-YAM-003',
                            address: 'Jl. Industri Raya No. 12, Cikarang',
                            estimatedTime: '14:00',
                            status: 'pending',
                            deliveryOrders: [
                                {
                                    id: 'DO-2024-003',
                                    poNumber: 'PO/YAM/2024/0120',
                                    poDate: '2024-01-20',
                                    products: [
                                        { partNo: 'YM-TIRE-1625-F', partName: 'Front Tire 16/25 Complete', quantity: 200, unit: 'PCS', weight: '400 kg' },
                                        { partNo: 'YM-TIRE-1625-R', partName: 'Rear Tire 16/25 Complete', quantity: 200, unit: 'PCS', weight: '450 kg' }
                                    ]
                                },
                                {
                                    id: 'DO-2024-004',
                                    poNumber: 'PO/YAM/2024/0121',
                                    poDate: '2024-01-21',
                                    products: [
                                        { partNo: 'YM-CHN-428', partName: 'Drive Chain 428 Standard', quantity: 300, unit: 'PCS', weight: '180 kg' }
                                    ]
                                }
                            ]
                        },
                        {
                            id: 'STOP-004',
                            customer: 'PT Suzuki Indomobil Motor',
                            customerCode: 'CUST-SUZ-004',
                            address: 'Jl. Raya Cakung No. 88, Jakarta Timur',
                            estimatedTime: '15:30',
                            status: 'pending',
                            deliveryOrders: [
                                {
                                    id: 'DO-2024-005',
                                    poNumber: 'PO/SUZ/2024/0119',
                                    poDate: '2024-01-19',
                                    products: [
                                        { partNo: 'SZ-ENG-5566', partName: 'Engine Oil SAE 10W-40', quantity: 100, unit: 'LITER', weight: '90 kg' },
                                        { partNo: 'SZ-BLT-9988', partName: 'V-Belt Standard', quantity: 200, unit: 'PCS', weight: '60 kg' }
                                    ]
                                }
                            ]
                        }
                    ]
                },
                {
                    id: 'DP003',
                    sequence: 3,
                    truckId: null,
                    driverId: null,
                    status: 'unassigned',
                    estimatedDeparture: '14:00',
                    estimatedReturn: '18:00',
                    stops: [
                        {
                            id: 'STOP-005',
                            customer: 'PT Astra Daihatsu Motor',
                            customerCode: 'CUST-DAI-005',
                            address: 'Jl. Gaya Motor III No. 5, Sunter',
                            estimatedTime: '15:00',
                            status: 'pending',
                            deliveryOrders: [
                                {
                                    id: 'DO-2024-006',
                                    poNumber: 'PO/DAI/2024/0122',
                                    poDate: '2024-01-22',
                                    products: [
                                        { partNo: 'DH-BRK-7788-F', partName: 'Front Brake Disc 14 inch', quantity: 400, unit: 'PCS', weight: '600 kg' },
                                        { partNo: 'DH-BRK-7788-R', partName: 'Rear Brake Disc 13 inch', quantity: 400, unit: 'PCS', weight: '520 kg' },
                                        { partNo: 'DH-PAD-1122', partName: 'Brake Pad Set Complete', quantity: 400, unit: 'SET', weight: '200 kg' }
                                    ]
                                }
                            ]
                        }
                    ]
                }
            ],
            
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
