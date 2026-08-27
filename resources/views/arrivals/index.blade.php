<x-app-layout>
    <x-page-header 
        title="Departure Records" 
        subtitle="Manage import arrivals and process receives for each departure."
        :breadcrumbs="[
            ['label' => 'Incoming Material', 'url' => '#'],
            ['label' => 'Import List']
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('departures.create') }}" class="gci-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>New Departure</span>
            </a>
        </x-slot>
    </x-page-header>

    @if (session('status'))
        <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm font-medium text-emerald-800 animate-fade-in-up">
            {{ session('status') }}
        </div>
    @endif

    <x-data-table 
        searchable="true" 
        searchPlaceholder="Cari Invoice atau Vendor..."
        searchName="search"
        :searchValue="request('search')"
    >
        <x-slot name="head">
            <th class="px-4 py-3 text-left font-semibold">Invoice</th>
            <th class="px-4 py-3 text-left font-semibold">Vendor</th>
            <th class="px-4 py-3 text-left font-semibold">ETD</th>
            <th class="px-4 py-3 text-left font-semibold">ETA JKT</th>
            <th class="px-4 py-3 text-left font-semibold">ETA GCI</th>
            <th class="px-4 py-3 text-left font-semibold">Items</th>
            <th class="px-4 py-3 text-left font-semibold">Total Value</th>
            <th class="px-4 py-3 text-center font-semibold w-32">Actions</th>
        </x-slot>

        @forelse ($departures as $arrival)
            @php
                $totalItems = $arrival->items->count();
                $totalValue = $arrival->items->sum('total_price');
                $totalQtyExpected = $arrival->items->sum('qty_goods');
                $isReceiveComplete = (bool) ($arrival->receive_complete ?? false);
            @endphp
            <tr>
                <td>
                    <div class="font-bold text-slate-900 flex items-center gap-2">
                        {{ $arrival->invoice_no }}
                        @if ($isReceiveComplete)
                            <span class="gci-badge-success">Complete</span>
                        @endif
                        @if ($arrival->purchaseOrder)
                            <span class="gci-badge-info" title="Auto-generated from PO">PO: {{ $arrival->purchaseOrder->po_number }}</span>
                        @endif
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        {{ $arrival->invoice_date?->format('d M Y') ?? '-' }}
                    </div>
                </td>
                <td class="font-medium text-slate-700">{{ $arrival->vendor->vendor_name ?? '-' }}</td>
                <td class="text-xs font-mono text-slate-600">{{ $arrival->ETD?->format('Y-m-d') ?? '-' }}</td>
                <td class="text-xs font-mono text-slate-600">{{ $arrival->ETA?->format('Y-m-d') ?? '-' }}</td>
                <td class="text-xs font-mono text-slate-600">{{ $arrival->ETA_GCI?->format('Y-m-d') ?? '-' }}</td>
                <td>
                    <div class="font-semibold text-slate-900">{{ $totalItems }} item{{ $totalItems != 1 ? 's' : '' }}</div>
                    <div class="text-xs text-slate-500">{{ number_format($totalQtyExpected) }} pcs</div>
                </td>
                <td>
                    <div class="font-semibold text-slate-900">
                        {{ $arrival->currency }} {{ number_format(round((float) $totalValue, 2, PHP_ROUND_HALF_UP), 2) }}
                    </div>
                </td>
                <td>
                    <div class="flex items-center justify-center gap-1.5">
                        <a href="{{ route('departures.show', $arrival) }}" class="gci-btn-icon bg-indigo-50 text-indigo-600 hover:bg-indigo-100" title="View Details">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75A3.75 3.75 0 1 0 12 8.25a3.75 3.75 0 0 0 0 7.5Z" />
                            </svg>
                        </a>
                        <a href="{{ route('departures.edit', $arrival) }}" class="gci-btn-icon bg-slate-100 text-slate-600 hover:bg-slate-200" title="Edit">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 7.125 16.862 4.487" />
                            </svg>
                        </a>
                        <a href="{{ route('departures.invoice', $arrival) }}" target="_blank" class="gci-btn-icon bg-slate-700 text-white hover:bg-slate-800" title="Print Invoice">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2Zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10Z" />
                            </svg>
                        </a>
                        <form action="{{ route('departures.destroy', $arrival) }}" method="POST" onsubmit="return confirm('Yakin hapus departure ini?');" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="gci-btn-icon bg-red-50 text-red-600 hover:bg-red-100" title="Delete">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12m-9 4v5m6-5v5M9 7l.867-2.6A1 1 0 0 1 10.81 3.5h2.38a1 1 0 0 1 .943.9L15 7m-9 0h12v12a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V7Z" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <x-slot name="empty">
                <div class="gci-empty">
                    <div class="gci-empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                    </div>
                    <div class="gci-empty-title">No departures recorded yet</div>
                    <div class="gci-empty-text mb-4">Create your first departure to get started.</div>
                    <a href="{{ route('departures.create') }}" class="gci-btn-secondary">
                        Create Departure
                    </a>
                </div>
            </x-slot>
        @endforelse

        <x-slot name="pagination">
            {{ $departures->links() }}
        </x-slot>
    </x-data-table>
</x-app-layout>