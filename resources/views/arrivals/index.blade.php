<x-app-layout>
    <x-slot name="header">
        Departures
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
                        <h3 class="text-lg font-bold text-slate-900">Departure Records</h3>
                        <p class="text-sm text-slate-600 mt-1">View details and process receives for each departure.</p>
                    </div>
                    <a href="{{ route('departures.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>New Departure</span>
                    </a>
                </div>
                
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
	                        <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
	                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
	                                <th class="px-4 py-3 text-left font-semibold">Invoice</th>
	                                <th class="px-4 py-3 text-left font-semibold">Vendor</th>
	                                <th class="px-4 py-3 text-left font-semibold">ETD</th>
	                                <th class="px-4 py-3 text-left font-semibold">ETA JKT</th>
	                                <th class="px-4 py-3 text-left font-semibold">ETA GCI</th>
	                                <th class="px-4 py-3 text-left font-semibold">Items</th>
	                                <th class="px-4 py-3 text-left font-semibold">Total Value</th>
	                                <th class="px-4 py-3 text-center font-semibold">Actions</th>
	                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($departures as $arrival)
                                @php
                                    $totalItems = $arrival->items->count();
                                    $totalValue = $arrival->items->sum('total_price');
                                    $totalQtyExpected = $arrival->items->sum('qty_goods');
                                    $isReceiveComplete = (bool) ($arrival->receive_complete ?? false);
                                @endphp
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-4 text-slate-700">
                                        <div class="font-medium flex items-center gap-2">
                                            <span>{{ $arrival->invoice_no }}</span>
                                            @if ($isReceiveComplete)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700">
                                                    Complete
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-slate-500">{{ $arrival->invoice_date?->format('d M Y') }}</div>
                                    </td>
	                                    <td class="px-4 py-4 text-slate-700">{{ $arrival->vendor->vendor_name ?? '-' }}</td>
	                                    <td class="px-4 py-4 text-slate-700 text-xs font-mono">{{ $arrival->ETD?->format('Y-m-d') ?? '-' }}</td>
	                                    <td class="px-4 py-4 text-slate-700 text-xs font-mono">{{ $arrival->ETA?->format('Y-m-d') ?? '-' }}</td>
	                                    <td class="px-4 py-4 text-slate-700 text-xs font-mono">{{ $arrival->ETA_GCI?->format('Y-m-d') ?? '-' }}</td>
	                                    <td class="px-4 py-4 text-slate-700">
	                                        <div class="font-semibold">{{ $totalItems }} item{{ $totalItems != 1 ? 's' : '' }}</div>
	                                        <div class="text-xs text-slate-500">{{ number_format($totalQtyExpected) }} pcs total</div>
	                                    </td>
	                                    <td class="px-4 py-4">
	                                        <div class="font-semibold text-slate-900">{{ $arrival->currency }} {{ number_format(round((float) $totalValue, 2, PHP_ROUND_HALF_UP), 2) }}</div>
	                                    </td>
	                                    <td class="px-4 py-4">
	                                        <div class="flex justify-center gap-2">
	                                            <a href="{{ route('departures.show', $arrival) }}" class="inline-flex items-center justify-center w-10 h-10 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors" title="View Details" aria-label="View details">
	                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
	                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z" />
	                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75A3.75 3.75 0 1 0 12 8.25a3.75 3.75 0 0 0 0 7.5Z" />
	                                                </svg>
	                                            </a>
	                                            <a href="{{ route('departures.edit', $arrival) }}" class="inline-flex items-center justify-center w-10 h-10 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg transition-colors" title="Edit" aria-label="Edit departure">
	                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
	                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
	                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125 16.862 4.487" />
	                                                </svg>
	                                            </a>
	                                            <a href="{{ route('departures.invoice', $arrival) }}" target="_blank" class="inline-flex items-center justify-center w-10 h-10 bg-slate-600 hover:bg-slate-700 text-white rounded-lg transition-colors" title="Print Invoice">
	                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
	                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2Zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10Z" />
	                                                </svg>
	                                            </a>
                                            <form action="{{ route('departures.destroy', $arrival) }}" method="POST" onsubmit="return confirm('Yakin hapus departure ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center justify-center w-10 h-10 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors" aria-label="Delete departure">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12m-9 4v5m6-5v5M9 7l.867-2.6A1 1 0 0 1 10.81 3.5h2.38a1 1 0 0 1 .943.9L15 7m-9 0h12v12a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V7Z" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
	                                    <td colspan="8" class="px-4 py-12 text-center">
	                                        <div class="text-slate-500 mb-2">No departures recorded yet.</div>
	                                        <a href="{{ route('departures.create') }}" class="text-blue-600 hover:text-blue-700 text-sm font-medium">Create your first departure →</a>
	                                    </td>
	                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $departures->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
