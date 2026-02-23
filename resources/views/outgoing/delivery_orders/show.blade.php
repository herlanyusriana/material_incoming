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
                <li class="text-slate-900">Details</li>
            </ol>
        </nav>
    </div>

    <div class="grid grid-cols-3 gap-6">
        <!-- Left: DO Info -->
        <div class="col-span-2">
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Delivery Order Details</h2>
                    <div class="flex gap-2">
                        <a href="{{ route('outgoing.delivery-orders.edit', $deliveryOrder) }}" 
                            class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all text-xs uppercase tracking-wider">Edit DO</a>
                        <form action="{{ route('outgoing.delivery-orders.destroy', $deliveryOrder) }}" method="POST" class="inline" onsubmit="return confirm('Delete this DO?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 rounded-xl bg-red-50 border border-red-200 text-red-600 font-bold hover:bg-red-100 transition-all text-xs uppercase tracking-wider">Delete</button>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">DO Number</div>
                        <div class="text-lg font-bold text-slate-900 mt-1">{{ $deliveryOrder->do_no }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">DO Date</div>
                        <div class="text-lg font-bold text-slate-900 mt-1">{{ $deliveryOrder->do_date->format('d M Y') }}</div>
                    </div>
                    <div class="col-span-2">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Customer</div>
                        <div class="text-lg font-bold text-slate-900 mt-1">{{ $deliveryOrder->customer ?? '-' }}</div>
                    </div>
                </div>

                @if($deliveryOrder->notes)
                <div class="mt-6 pt-6 border-t border-slate-200">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Notes</div>
                    <div class="text-slate-700 mt-2">{{ $deliveryOrder->notes }}</div>
                </div>
                @endif
            </div>

            <!-- Items -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 mt-6">
                <h3 class="text-lg font-bold text-slate-900 tracking-tight mb-4">Delivery Order Items</h3>
                
                @if($deliveryOrder->items->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200">
                                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">Item Description</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">Quantity</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">Unit Price</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($deliveryOrder->items as $item)
                                <tr class="border-b border-slate-100 hover:bg-slate-50">
                                    <td class="px-4 py-3">{{ $item->description ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $item->quantity ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ $item->unit_price ? 'Rp ' . number_format($item->unit_price, 0, ',', '.') : '-' }}</td>
                                    <td class="px-4 py-3 text-right font-bold">{{ (($item->quantity ?? 0) * ($item->unit_price ?? 0)) > 0 ? 'Rp ' . number_format(($item->quantity ?? 0) * ($item->unit_price ?? 0), 0, ',', '.') : '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8 text-slate-500">
                        No items in this delivery order.
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Summary -->
        <div>
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-900 tracking-tight mb-4">Summary</h3>
                
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Created</span>
                        <span class="font-bold text-slate-900">{{ $deliveryOrder->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Updated</span>
                        <span class="font-bold text-slate-900">{{ $deliveryOrder->updated_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="pt-4 border-t border-slate-200">
                        <div class="flex justify-between">
                            <span class="font-bold text-slate-900">Total Items</span>
                            <span class="font-bold text-lg text-indigo-600">{{ $deliveryOrder->items->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <a href="{{ route('outgoing.delivery-orders.index') }}" 
                class="mt-6 w-full block text-center px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200 transition-all text-sm uppercase tracking-wider">
                Back to List
            </a>
        </div>
    </div>
</div>
@endsection
