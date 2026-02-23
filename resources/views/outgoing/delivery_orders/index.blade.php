<x-app-layout>
    <x-slot name="header">
        Outgoing • Delivery Orders
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 flex items-center gap-3 shadow-sm">
                    <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800 flex items-center gap-3 shadow-sm">
                    <svg class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-xl border border-slate-200 rounded-3xl overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4 bg-slate-50/50">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Delivery Order List</h2>
                        <p class="text-sm text-slate-500 mt-1">Manage outgoing delivery orders.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <form action="{{ route('outgoing.delivery-orders.index') }}" method="GET" class="flex items-center gap-2">
                            <input type="text" name="q" value="{{ $q }}" placeholder="Search DO No..." class="rounded-xl border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <select name="customer_id" class="rounded-xl border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Customers</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" @selected($customerId == $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="p-2 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </form>
                        <a href="{{ route('outgoing.delivery-orders.create') }}" class="px-5 py-2.5 rounded-2xl bg-slate-900 text-white font-bold hover:bg-slate-800 transition-all flex items-center gap-2 shadow-md uppercase text-xs tracking-wider">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            New Delivery Order
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">DO Number</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">Customer</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">DO Date</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-widest">Status</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">Items</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            @forelse ($orders as $order)
                                <tr class="hover:bg-slate-50/80 transition-colors group">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-indigo-600 group-hover:text-indigo-700">{{ $order->do_no }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-slate-700">{{ $order->customer?->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                        {{ $order->do_date ? $order->do_date->format('M d, Y') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @php
                                            $statusClasses = [
                                                'draft' => 'bg-slate-100 text-slate-700 border-slate-200',
                                                'assigned' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                'partial_shipped' => 'bg-blue-100 text-blue-700 border-blue-200',
                                                'shipped' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                            ];
                                            $currentClass = $statusClasses[$order->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                        @endphp
                                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border {{ $currentClass }}">
                                            {{ str_replace('_', ' ', $order->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm font-bold text-slate-900">{{ number_format($order->items->count()) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('outgoing.delivery-orders.show', $order) }}" class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all" title="View Detail">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            @if($order->status === 'draft')
                                                <a href="{{ route('outgoing.delivery-orders.edit', $order) }}" class="p-2 rounded-xl text-slate-400 hover:text-amber-600 hover:bg-amber-50 transition-all" title="Edit">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </a>
                                                <form action="{{ route('outgoing.delivery-orders.destroy', $order) }}" method="POST" class="inline" onsubmit="return confirm('Confirm delete?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all" title="Delete">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="h-12 w-12 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span class="font-semibold">No delivery orders found.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($orders->hasPages())
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
