@extends('layouts.app')

@section('content')
<div class="p-6">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="text-sm text-slate-500" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-2">
                <li><a href="{{ route('dashboard') }}" class="hover:text-slate-700">Dashboard</a></li>
                <li class="text-slate-900">/</li>
                <li><a href="{{ route('outgoing.delivery-plan') }}" class="hover:text-slate-700">Outgoing</a></li>
                <li class="text-slate-900">/</li>
                <li><a href="{{ route('outgoing.delivery-orders.index') }}" class="hover:text-slate-700">Delivery Orders</a></li>
                <li class="text-slate-900">/</li>
                <li class="text-slate-900">New</li>
            </ol>
        </nav>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight mb-6">Create Delivery Order</h2>
        
        <form action="{{ route('outgoing.delivery-orders.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- DO Number -->
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">DO
                    Number</label>
                <input type="text" name="do_no" value="{{ old('do_no') }}"
                    placeholder="E.g. DO/2026/001"
                    class="mt-2 w-full rounded-xl border-slate-200 @error('do_no') border-red-500 @enderror focus:ring-indigo-500 focus:border-indigo-500">
                @error('do_no')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- DO Date -->
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">DO
                    Date</label>
                <input type="date" name="do_date" value="{{ old('do_date', now()->format('Y-m-d')) }}"
                    class="mt-2 w-full rounded-xl border-slate-200 @error('do_date') border-red-500 @enderror focus:ring-indigo-500 focus:border-indigo-500">
                @error('do_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Customer -->
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Customer (Optional)</label>
                <input type="text" name="customer" value="{{ old('customer') }}"
                    placeholder="Enter customer name"
                    class="mt-2 w-full rounded-xl border-slate-200 @error('customer') border-red-500 @enderror focus:ring-indigo-500 focus:border-indigo-500">
                @error('customer')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Notes -->
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Notes</label>
                <textarea name="notes" rows="4"
                    placeholder="Additional notes for this delivery order"
                    class="mt-2 w-full rounded-xl border-slate-200 @error('notes') border-red-500 @enderror focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                @error('notes')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit -->
            <div class="flex gap-3 pt-4">
                <button type="submit"
                    class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-all text-sm uppercase tracking-wider">Create
                    Delivery Order</button>
                <a href="{{ route('outgoing.delivery-orders.index') }}"
                    class="px-6 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition-all text-sm uppercase tracking-wider">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
