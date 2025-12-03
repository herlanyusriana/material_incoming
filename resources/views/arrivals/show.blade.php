<x-app-layout>
    <x-slot name="header">
        Arrival {{ $arrival->arrival_no }}
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Back Button -->
            <div>
                <a href="{{ route('arrivals.index') }}" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 font-medium transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    <span>Back to Arrivals</span>
                </a>
            </div>
            
            <!-- Arrival Information -->
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="pb-3 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">Shipment Information</h3>
                    <p class="text-sm text-slate-600 mt-1">Vendor {{ $arrival->vendor->vendor_name ?? '-' }} • Invoice {{ $arrival->invoice_no }}</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Invoice:</span>
                        <span class="text-slate-900">{{ $arrival->invoice_no }} ({{ $arrival->invoice_date->format('Y-m-d') }})</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Vendor:</span>
                        <span class="text-slate-900">{{ $arrival->vendor->vendor_name ?? '-' }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Created by:</span>
                        <span class="text-slate-900">{{ $arrival->creator->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Vessel:</span>
                        <span class="text-slate-900">{{ $arrival->vessel }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Trucking:</span>
                        <span class="text-slate-900">{{ $arrival->trucking_company }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">ETD:</span>
                        <span class="text-slate-900">{{ $arrival->ETD }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Bill of Lading:</span>
                        <span class="text-slate-900">{{ $arrival->bill_of_lading }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">HS Code:</span>
                        <span class="text-slate-900">{{ $arrival->hs_code }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Port of Loading:</span>
                        <span class="text-slate-900">{{ $arrival->port_of_loading }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Currency:</span>
                        <span class="text-slate-900">{{ $arrival->currency }}</span>
                    </div>
                </div>
                @if ($arrival->notes)
                    <div class="flex items-start gap-2 pt-2 border-t border-slate-200 text-sm">
                        <span class="font-semibold text-slate-700 min-w-[100px]">Notes:</span>
                        <span class="text-slate-900">{{ $arrival->notes }}</span>
                    </div>
                @endif
            </div>

            <!-- Items Table -->
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="pb-3 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">Arrival Items</h3>
                    <p class="text-sm text-slate-600 mt-1">Parts and receiving details</p>
                </div>
                
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Part</th>
                                <th class="px-4 py-3 text-left font-semibold">Size</th>
                                <th class="px-4 py-3 text-left font-semibold">Qty Bundle</th>
                                <th class="px-4 py-3 text-left font-semibold">Qty Goods</th>
                                <th class="px-4 py-3 text-left font-semibold">Nett (kg)</th>
                                <th class="px-4 py-3 text-left font-semibold">Gross (kg)</th>
                                <th class="px-4 py-3 text-left font-semibold">Price</th>
                                <th class="px-4 py-3 text-left font-semibold">Total</th>
                                <th class="px-4 py-3 text-left font-semibold">Received</th>
                                <th class="px-4 py-3 text-center font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($arrival->items as $item)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-4 text-slate-800">
                                        <div class="font-semibold">{{ $item->part->part_no }}</div>
                                        <div class="text-xs text-slate-500">{{ $item->part->part_name_vendor }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700 font-mono text-xs">{{ $item->size ?? '-' }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ $item->qty_bundle }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ $item->qty_goods }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ number_format($item->weight_nett, 2) }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ number_format($item->weight_gross, 2) }}</td>
                                    <td class="px-4 py-4 text-slate-700">{{ number_format($item->price, 2) }}</td>
                                    <td class="px-4 py-4 text-slate-800 font-semibold">{{ number_format($item->total_price, 2) }}</td>
                                    <td class="px-4 py-4">
                                        <div class="text-slate-800 font-semibold">{{ number_format($item->receives->sum('qty')) }}</div>
                                        <div class="text-xs text-slate-500">{{ $item->receives->count() }} receive{{ $item->receives->count() != 1 ? 's' : '' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-center">
                                            <a href="{{ route('receives.create', $item) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                                Receive
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
