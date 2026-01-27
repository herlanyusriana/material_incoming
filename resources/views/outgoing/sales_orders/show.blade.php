@extends('outgoing.layout')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-sm text-slate-500">Sales Order</div>
                    <div class="text-2xl font-black text-slate-900">{{ $salesOrder->so_no }}</div>
                    <div class="mt-1 text-sm text-slate-600">
                        {{ $salesOrder->customer?->name ?? '-' }} • {{ $salesOrder->so_date?->format('d M Y') ?? '-' }} •
                        <span class="font-semibold">{{ $salesOrder->status }}</span>
                    </div>
                </div>
                <div class="text-sm text-slate-600">
                    Trip: {{ $salesOrder->plan?->id ? ('DP' . str_pad((string) $salesOrder->plan->id, 3, '0', STR_PAD_LEFT)) : '-' }}
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-700">Items</div>
            </div>

            <form method="POST" action="{{ route('outgoing.sales-orders.ship', $salesOrder) }}">
                @csrf
                <div class="overflow-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">Del Class</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">Trolley</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">Part No</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">Part Name</th>
                                <th class="px-4 py-3 text-right font-bold text-slate-700">Ordered</th>
                                <th class="px-4 py-3 text-right font-bold text-slate-700">Shipped</th>
                                <th class="px-4 py-3 text-right font-bold text-slate-700">Ship Now</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($salesOrder->items as $item)
                                @php
                                    $ordered = (float) $item->qty_ordered;
                                    $shipped = (float) ($item->qty_shipped ?? 0);
                                    $remaining = max(0, $ordered - $shipped);
                                    $std = $item->part?->standardPacking;
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-semibold text-slate-700">{{ $std?->delivery_class ?? '-' }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-700">{{ $std?->trolley_type ?? '-' }}</td>
                                    <td class="px-4 py-3 font-mono font-bold text-indigo-700">{{ $item->part?->part_no ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $item->part?->part_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-900">{{ number_format($ordered, 0) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700">{{ number_format($shipped, 0) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <input
                                            type="number"
                                            step="0.0001"
                                            min="0"
                                            max="{{ $remaining }}"
                                            name="items[{{ $item->id }}][qty]"
                                            value="{{ $remaining > 0 ? $remaining : 0 }}"
                                            class="w-28 rounded-lg border-slate-300 text-sm text-right focus:border-indigo-500 focus:ring-indigo-500"
                                            {{ $remaining <= 0 ? 'disabled' : '' }}
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 flex justify-end">
                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">
                        Ship (Create DN)
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

