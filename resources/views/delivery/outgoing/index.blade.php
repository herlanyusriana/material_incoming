@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">Delivery Outgoing Management</h1>
        <p class="text-slate-600 mt-2">Manage outgoing deliveries for finished goods to customers</p>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex flex-wrap gap-3">
                <form method="GET" class="flex flex-wrap gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Customer</label>
                        <select name="customer_id" class="rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Customers</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                        <select name="status" class="rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Statuses</option>
                            <option value="prepared" {{ request('status') == 'prepared' ? 'selected' : '' }}>Prepared</option>
                            <option value="assigned" {{ request('status') == 'assigned' ? 'selected' : '' }}>Assigned</option>
                            <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                            <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" 
                               class="rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" 
                               class="rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    
                    <div class="self-end">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
            
            <div>
                <a href="{{ route('delivery.outgoing.create') }}" class="px-5 py-2.5 rounded-2xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition-all flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Delivery
                </a>
            </div>
        </div>
        
        <!-- Stats Summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6 bg-slate-25">
            <div class="text-center">
                <div class="text-2xl font-bold text-slate-900">{{ $deliveryNotes->total() }}</div>
                <div class="text-xs text-slate-500">Total Deliveries</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-indigo-600">{{ $deliveryNotes->where('status', 'prepared')->count() }}</div>
                <div class="text-xs text-slate-500">Prepared</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-amber-600">{{ $deliveryNotes->where('status', 'assigned')->count() }}</div>
                <div class="text-xs text-slate-500">Assigned</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-emerald-600">{{ $deliveryNotes->where('status', 'delivered')->count() }}</div>
                <div class="text-xs text-slate-500">Delivered</div>
            </div>
        </div>
    </div>

    <!-- Delivery Notes Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Delivery #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Truck</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($deliveryNotes as $dn)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-slate-900">{{ $dn->delivery_no }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-slate-900">{{ $dn->customer->name ?? 'N/A' }}</div>
                                <div class="text-sm text-slate-500">{{ $dn->customer->address ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-slate-900">{{ $dn->truck->name ?? 'Unassigned' }}</div>
                                <div class="text-sm text-slate-500">{{ $dn->truck->vehicle_number ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                {{ $dn->delivery_date ? $dn->delivery_date->format('d M Y') : 'Not set' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'prepared' => 'bg-blue-100 text-blue-800',
                                        'assigned' => 'bg-amber-100 text-amber-800',
                                        'in_transit' => 'bg-yellow-100 text-yellow-800',
                                        'delivered' => 'bg-emerald-100 text-emerald-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                    ];
                                    $statusColor = $statusColors[$dn->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $statusColor }}">
                                    {{ ucfirst(str_replace('_', ' ', $dn->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                {{ $dn->items->count() }} items
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="{{ route('delivery.outgoing.show', $dn) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                    <a href="{{ route('delivery.outgoing.edit', $dn) }}" class="text-amber-600 hover:text-amber-900">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                No delivery notes found. <a href="{{ route('delivery.outgoing.create') }}" class="text-indigo-600 hover:underline">Create one?</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
            {{ $deliveryNotes->links() }}
        </div>
    </div>
</div>
@endsection