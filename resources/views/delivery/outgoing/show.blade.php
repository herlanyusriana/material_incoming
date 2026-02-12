@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Delivery Note: {{ $deliveryNote->dn_no }}</h1>
                <p class="text-slate-600 mt-2">Details for delivery to {{ $deliveryNote->customer->name ?? 'N/A' }}</p>
            </div>
            
            <div class="flex space-x-3">
                <a href="{{ route('delivery.outgoing.edit', $deliveryNote) }}" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">
                    Edit
                </a>
                <a href="{{ route('delivery.outgoing.index') }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700">
                    Back to List
                </a>
            </div>
        </div>
    </div>

    <!-- Delivery Information -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Delivery Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-slate-500">Delivery Number</p>
                    <p class="font-medium text-slate-900">{{ $deliveryNote->delivery_no }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-slate-500">Status</p>
                    <p>
                        @php
                            $statusColors = [
                                'prepared' => 'bg-blue-100 text-blue-800',
                                'assigned' => 'bg-amber-100 text-amber-800',
                                'in_transit' => 'bg-yellow-100 text-yellow-800',
                                'delivered' => 'bg-emerald-100 text-emerald-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                            ];
                            $statusColor = $statusColors[$deliveryNote->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $statusColor }}">
                            {{ ucfirst(str_replace('_', ' ', $deliveryNote->status)) }}
                        </span>
                    </p>
                </div>
                
                <div>
                    <p class="text-sm text-slate-500">Customer</p>
                    <p class="font-medium text-slate-900">{{ $deliveryNote->customer->name ?? 'N/A' }}</p>
                    <p class="text-sm text-slate-500">{{ $deliveryNote->customer->address ?? 'N/A' }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-slate-500">Truck</p>
                    <p class="font-medium text-slate-900">{{ $deliveryNote->truck->name ?? 'Unassigned' }}</p>
                    <p class="text-sm text-slate-500">{{ $deliveryNote->truck->vehicle_number ?? '' }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-slate-500">Driver</p>
                    <p class="font-medium text-slate-900">{{ $deliveryNote->driver->name ?? 'Unassigned' }}</p>
                    <p class="text-sm text-slate-500">{{ $deliveryNote->driver->phone ?? '' }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-slate-500">Delivery Date</p>
                    <p class="font-medium text-slate-900">{{ $deliveryNote->delivery_date ? $deliveryNote->delivery_date->format('d M Y') : 'Not set' }}</p>
                </div>
                
                <div>
                    <p class="text-sm text-slate-500">Created At</p>
                    <p class="font-medium text-slate-900">{{ $deliveryNote->created_at->format('d M Y H:i') }}</p>
                </div>
                
                <div class="md:col-span-2">
                    <p class="text-sm text-slate-500">Notes</p>
                    <p class="font-medium text-slate-900">{{ $deliveryNote->notes ?: 'No notes' }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Quick Actions</h2>
            
            <div class="space-y-3">
                <!-- Status Update Form -->
                <form method="POST" action="{{ route('delivery.outgoing.update-status', $deliveryNote) }}" class="space-y-3">
                    @csrf
                    @method('POST')
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Update Status</label>
                        <select name="status" class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="prepared" {{ $deliveryNote->status === 'prepared' ? 'selected' : '' }}>Prepared</option>
                            <option value="assigned" {{ $deliveryNote->status === 'assigned' ? 'selected' : '' }}>Assigned</option>
                            <option value="in_transit" {{ $deliveryNote->status === 'in_transit' ? 'selected' : '' }}>In Transit</option>
                            <option value="delivered" {{ $deliveryNote->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="cancelled" {{ $deliveryNote->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Update Status
                    </button>
                </form>
                
                <!-- Truck Assignment Form -->
                <form method="POST" action="{{ route('delivery.outgoing.assign-truck', $deliveryNote) }}" class="space-y-3">
                    @csrf
                    @method('POST')
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Assign Truck</label>
                        <select name="truck_id" class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select a truck</option>
                            @foreach(\App\Models\Trucking::all() as $truck)
                                <option value="{{ $truck->id }}" {{ $deliveryNote->truck_id == $truck->id ? 'selected' : '' }}>
                                    {{ $truck->name }} - {{ $truck->vehicle_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                        Assign Truck
                    </button>
                </form>
                
                <!-- Driver Assignment Form -->
                <form method="POST" action="{{ route('delivery.outgoing.assign-driver', $deliveryNote) }}" class="space-y-3">
                    @csrf
                    @method('POST')
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Assign Driver</label>
                        <select name="driver_id" class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select a driver</option>
                            @foreach(\App\Models\User::where('role', 'driver')->get() as $driver)
                                <option value="{{ $driver->id }}" {{ $deliveryNote->driver_id == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->name }} ({{ $driver->username }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Assign Driver
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Items Section -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h2 class="text-lg font-semibold text-slate-900">Delivery Items</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Part Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Part Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Sales Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Unit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Notes</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($deliveryNote->items as $item)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                                {{ $item->part->part_no ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                {{ $item->part->part_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                {{ $item->salesOrder->order_number ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                {{ number_format($item->quantity, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                {{ $item->unit }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">
                                {{ $item->notes ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                No items found in this delivery note.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection