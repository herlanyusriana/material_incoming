<x-app-layout>
    <x-slot name="header">
        Arrivals
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Arrival Records</h3>
                        <p class="text-sm text-slate-600 mt-1">View details and process receives for each arrival.</p>
                    </div>
                    <a href="{{ route('arrivals.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>New Arrival</span>
                    </a>
                </div>
                
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Arrival No</th>
                                <th class="px-4 py-3 text-left font-semibold">Invoice</th>
                                <th class="px-4 py-3 text-left font-semibold">Vendor</th>
                                <th class="px-4 py-3 text-left font-semibold">Items</th>
                                <th class="px-4 py-3 text-left font-semibold">Total Value</th>
                                <th class="px-4 py-3 text-left font-semibold">Received</th>
                                <th class="px-4 py-3 text-left font-semibold">Date</th>
                                <th class="px-4 py-3 text-center font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($arrivals as $arrival)
                                @php
                                    $totalItems = $arrival->items->count();
                                    $totalValue = $arrival->items->sum('total_price');
                                    $totalQtyExpected = $arrival->items->sum('qty_goods');
                                    $totalQtyReceived = $arrival->items->sum(fn($item) => $item->receives->sum('qty'));
                                    $receiveProgress = $totalQtyExpected > 0 ? round(($totalQtyReceived / $totalQtyExpected) * 100) : 0;
                                @endphp
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $arrival->arrival_no }}</div>
                                        <div class="text-xs text-slate-500">{{ $arrival->creator->name ?? 'System' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">
                                        <div class="font-medium">{{ $arrival->invoice_no }}</div>
                                        <div class="text-xs text-slate-500">{{ $arrival->invoice_date?->format('d M Y') }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">{{ $arrival->vendor->vendor_name ?? '-' }}</td>
                                    <td class="px-4 py-4 text-slate-700">
                                        <div class="font-semibold">{{ $totalItems }} item{{ $totalItems != 1 ? 's' : '' }}</div>
                                        <div class="text-xs text-slate-500">{{ number_format($totalQtyExpected) }} pcs total</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $arrival->currency }} {{ number_format($totalValue, 2) }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-slate-200 rounded-full h-2 max-w-[80px]">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $receiveProgress }}%"></div>
                                            </div>
                                            <span class="text-xs font-semibold text-slate-700 min-w-[35px]">{{ $receiveProgress }}%</span>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-1">{{ number_format($totalQtyReceived) }} / {{ number_format($totalQtyExpected) }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">{{ $arrival->created_at->format('d M Y') }}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-center">
                                            <a href="{{ route('arrivals.show', $arrival) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                                View Details
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center">
                                        <div class="text-slate-500 mb-2">No arrivals recorded yet.</div>
                                        <a href="{{ route('arrivals.create') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Create your first arrival →</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $arrivals->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
