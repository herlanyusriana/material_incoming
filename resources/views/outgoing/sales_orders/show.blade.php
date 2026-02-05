<x-app-layout>
    <x-slot name="header">
        Outgoing • Sales Order Details
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

            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Left: SO Info -->
                <div class="lg:w-2/3 space-y-6">
                    <div class="bg-white shadow-xl border border-slate-200 rounded-3xl overflow-hidden">
                        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 tracking-tight">{{ $salesOrder->so_no }}</h2>
                                <p class="text-sm text-slate-500 mt-1">Status: <span class="uppercase font-bold text-indigo-600">{{ str_replace('_', ' ', $salesOrder->status) }}</span></p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($salesOrder->status === 'draft')
                                    <a href="{{ route('outgoing.sales-orders.edit', $salesOrder) }}" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all text-xs uppercase tracking-wider">Edit SO</a>
                                @endif
                                <a href="{{ route('outgoing.sales-orders.index') }}" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all text-xs uppercase tracking-wider">Back to List</a>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                                <div>
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Customer</div>
                                    <div class="text-sm font-bold text-slate-700 mt-1">{{ $salesOrder->customer?->name }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">SO Date</div>
                                    <div class="text-sm font-bold text-slate-700 mt-1">{{ $salesOrder->so_date ? $salesOrder->so_date->format('d M Y') : '-' }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Created Date</div>
                                    <div class="text-sm font-bold text-slate-700 mt-1">{{ $salesOrder->created_at->format('d M Y') }}</div>
                                </div>
                                <div>
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Trip Ref</div>
                                    <div class="text-sm font-bold text-slate-700 mt-1">
                                        {{ $salesOrder->plan?->id ? ('DP' . str_pad((string) $salesOrder->plan->id, 3, '0', STR_PAD_LEFT)) : '-' }}
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Order Items</h3>
                                <div class="overflow-x-auto border border-slate-200 rounded-2xl">
                                    <table class="min-w-full divide-y divide-slate-200">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">Part</th>
                                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">Ordered</th>
                                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">Shipped</th>
                                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">Remaining</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-slate-100">
                                            @foreach($salesOrder->items as $item)
                                                @php
                                                    $ordered = (float) $item->qty_ordered;
                                                    $shipped = (float) ($item->qty_shipped ?? 0);
                                                    $remaining = max(0, $ordered - $shipped);
                                                @endphp
                                                <tr class="hover:bg-slate-50/50 transition-colors">
                                                    <td class="px-4 py-3">
                                                        <div class="text-sm font-bold text-slate-700">{{ $item->part?->part_no }}</div>
                                                        <div class="text-xs text-slate-500">{{ $item->part?->part_name }}</div>
                                                    </td>
                                                    <td class="px-4 py-3 text-right font-bold text-slate-900">{{ number_format($ordered, 0) }}</td>
                                                    <td class="px-4 py-3 text-right text-slate-600">{{ number_format($shipped, 0) }}</td>
                                                    <td class="px-4 py-3 text-right font-bold {{ $remaining > 0 ? 'text-indigo-600' : 'text-emerald-600' }}">
                                                        {{ number_format($remaining, 0) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            @if($salesOrder->notes)
                                <div class="mt-8 p-4 bg-slate-50 rounded-2xl border border-slate-200">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Notes</div>
                                    <div class="text-sm text-slate-700">{{ $salesOrder->notes }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Right: Actions & Shipment -->
                <div class="lg:w-1/3 space-y-6">
                    @if($salesOrder->status !== 'shipped')
                        <div class="bg-white shadow-xl border border-slate-200 rounded-3xl overflow-hidden">
                            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                                <h2 class="text-lg font-bold text-slate-900 tracking-tight">Post Shipment</h2>
                            </div>
                            <form action="{{ route('outgoing.sales-orders.ship', $salesOrder) }}" method="POST" class="p-6 space-y-4">
                                @csrf
                                <p class="text-xs text-slate-500">Select quantities to ship and generate Delivery Note (Surat Jalan).</p>
                                
                                @foreach($salesOrder->items as $item)
                                    @php
                                        $ordered = (float) $item->qty_ordered;
                                        $shipped = (float) ($item->qty_shipped ?? 0);
                                        $remaining = max(0, $ordered - $shipped);
                                    @endphp
                                    @if($remaining > 0)
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ $item->part?->part_no }} (Max: {{ number_format($remaining) }})</label>
                                            <input type="number" step="0.0001" min="0" max="{{ $remaining }}" name="items[{{ $item->id }}][qty]" value="{{ $remaining }}" class="w-full rounded-xl border-slate-200 text-sm font-bold text-right focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                    @endif
                                @endforeach

                                <button type="submit" class="w-full py-3 rounded-2xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition-all shadow-lg uppercase text-xs tracking-wider mt-4">Generate DN & Ship</button>
                            </form>
                        </div>
                    @endif

                    @if($salesOrder->deliveryNotes->isNotEmpty())
                        <div class="bg-white shadow-xl border border-slate-200 rounded-3xl overflow-hidden">
                            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                                <h2 class="text-lg font-bold text-slate-900 tracking-tight">Delivery History</h2>
                            </div>
                            <div class="px-6 divide-y divide-slate-100">
                                @foreach($salesOrder->deliveryNotes as $dn)
                                    <div class="py-4 flex items-center justify-between">
                                        <div>
                                            <div class="text-sm font-bold text-slate-700">{{ $dn->dn_no }}</div>
                                            <div class="text-[10px] text-slate-400 font-mono">{{ $dn->delivery_date->format('d M Y') }}</div>
                                        </div>
                                        <a href="{{ route('outgoing.delivery-notes.show', $dn) }}" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
