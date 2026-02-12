@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Edit Delivery Note: {{ $deliveryNote->delivery_no }}</h1>
                <p class="text-slate-600 mt-2">Update details for delivery to {{ $deliveryNote->customer->name ?? 'N/A' }}</p>
            </div>
            
            <div>
                <a href="{{ route('delivery.outgoing.show', $deliveryNote) }}" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700">
                    Back to Details
                </a>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('delivery.outgoing.update', $deliveryNote) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Customer *</label>
                <select name="customer_id" required class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500" disabled>
                    <option value="{{ $deliveryNote->customer->id }}">{{ $deliveryNote->customer->name }}</option>
                </select>
                <p class="mt-1 text-sm text-slate-500">Customer cannot be changed after creation</p>
                @error('customer_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Truck</label>
                <select name="truck_id" class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select a truck (optional)</option>
                    @foreach($trucks as $truck)
                        <option value="{{ $truck->id }}" {{ $deliveryNote->truck_id == $truck->id ? 'selected' : '' }}>
                            {{ $truck->name }} - {{ $truck->vehicle_number }}
                        </option>
                    @endforeach
                </select>
                @error('truck_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Driver</label>
                <select name="driver_id" class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select a driver (optional)</option>
                    @foreach(\App\Models\User::where('role', 'driver')->get() as $driver)
                        <option value="{{ $driver->id }}" {{ $deliveryNote->driver_id == $driver->id ? 'selected' : '' }}>
                            {{ $driver->name }} ({{ $driver->username }})
                        </option>
                    @endforeach
                </select>
                @error('driver_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Status *</label>
                <select name="status" required class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="prepared" {{ $deliveryNote->status === 'prepared' ? 'selected' : '' }}>Prepared</option>
                    <option value="assigned" {{ $deliveryNote->status === 'assigned' ? 'selected' : '' }}>Assigned</option>
                    <option value="in_transit" {{ $deliveryNote->status === 'in_transit' ? 'selected' : '' }}>In Transit</option>
                    <option value="delivered" {{ $deliveryNote->status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="cancelled" {{ $deliveryNote->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Date</label>
                <input type="date" name="delivery_date" value="{{ old('delivery_date', $deliveryNote->delivery_date) }}" 
                       class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                @error('delivery_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $deliveryNote->notes) }}</textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <div class="flex justify-end space-x-4">
            <a href="{{ route('delivery.outgoing.show', $deliveryNote) }}" class="px-5 py-2.5 rounded-2xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200 transition-all">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-2xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition-all">
                Update Delivery Note
            </button>
        </div>
    </form>
</div>
@endsection