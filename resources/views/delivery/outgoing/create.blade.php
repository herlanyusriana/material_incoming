@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">Create Delivery Note</h1>
        <p class="text-slate-600 mt-2">Create a new delivery note by selecting sales orders ready for delivery</p>
    </div>

    <form method="POST" action="{{ route('delivery.outgoing.store') }}" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-6">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Customer *</label>
                <select name="customer_id" id="customerSelect" required class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select a customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ $customerId == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
                @error('customer_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Truck (Optional)</label>
                <select name="truck_id" class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select a truck (optional)</option>
                    @foreach($trucks as $truck)
                        <option value="{{ $truck->id }}">{{ $truck->name }} - {{ $truck->vehicle_number }}</option>
                    @endforeach
                </select>
                @error('truck_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Driver (Optional)</label>
                <select name="driver_id" class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select a driver (optional)</option>
                    @foreach(\App\Models\User::where('role', 'driver')->get() as $driver)
                        <option value="{{ $driver->id }}">{{ $driver->name }} ({{ $driver->username }})</option>
                    @endforeach
                </select>
                @error('driver_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Date (Optional)</label>
                <input type="date" name="delivery_date" value="{{ old('delivery_date') }}" 
                       class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">
                @error('delivery_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Delivery Orders Ready for Delivery</h2>
            
            <div class="bg-slate-50 rounded-lg border border-slate-200 p-4">
                <div class="flex justify-between items-center mb-4">
                    <p class="text-sm text-slate-600">Select delivery orders to include in this delivery note</p>
                    <p class="text-sm text-slate-600"><span id="selectedCount">0</span> selected</p>
                </div>
                
                <div class="space-y-3 max-h-96 overflow-y-auto">
                    @forelse($salesOrders as $so)
                        <div class="border border-slate-200 rounded-lg p-4 bg-white hover:bg-slate-25 transition-colors">
                            <div class="flex items-start">
                                <input type="checkbox" 
                                       name="sales_order_ids[]" 
                                       value="{{ $so->id }}" 
                                       id="so_{{ $so->id }}"
                                       class="mt-1 h-4 w-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 sales-order-checkbox">
                                <label for="so_{{ $so->id }}" class="ml-3 flex-1">
                                    <div class="flex justify-between">
                                        <div>
                                            <div class="font-medium text-slate-900">{{ $so->order_number }}</div>
                                            <div class="text-sm text-slate-500">{{ $so->customer->name ?? 'N/A' }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-medium text-slate-900">{{ $so->items->count() }} items</div>
                                            <div class="text-sm text-slate-500">{{ $so->status }}</div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                        @foreach($so->items as $item)
                                            <div class="text-xs bg-slate-100 rounded px-2 py-1">
                                                {{ $item->part->part_no ?? 'N/A' }} - {{ $item->quantity }} {{ $item->unit }}
                                            </div>
                                        @endforeach
                                    </div>
                                </label>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-slate-500">
                            <p>No delivery orders ready for delivery found for the selected customer.</p>
                            <p class="mt-2">Delivery orders must be marked as 'completed' to appear here.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        
        <div class="flex justify-end space-x-4">
            <a href="{{ route('delivery.outgoing.index') }}" class="px-5 py-2.5 rounded-2xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200 transition-all">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2.5 rounded-2xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition-all disabled:opacity-50" 
                    id="createBtn" disabled>
                Create Delivery Note
            </button>
        </div>
    </form>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.sales-order-checkbox');
            const createBtn = document.getElementById('createBtn');
            const selectedCountEl = document.getElementById('selectedCount');
            
            // Update selected count and enable/disable button
            function updateSelectionState() {
                const checkedCount = document.querySelectorAll('.sales-order-checkbox:checked').length;
                selectedCountEl.textContent = checkedCount;
                createBtn.disabled = checkedCount === 0;
            }
            
            // Add event listeners to checkboxes
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectionState);
            });
            
            // Initialize state
            updateSelectionState();
            
            // Add customer change listener to reload orders
            document.getElementById('customerSelect').addEventListener('change', function() {
                const customerId = this.value;
                if(customerId) {
                    // Reload the page with the selected customer
                    window.location.href = '{{ route('delivery.outgoing.create') }}?customer_id=' + customerId;
                }
            });
        });
    </script>
</div>
@endsection