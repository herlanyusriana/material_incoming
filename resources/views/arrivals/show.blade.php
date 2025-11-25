<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Arrival {{ $arrival->arrival_no }}</h2>
                <p class="text-sm text-gray-500">Vendor {{ $arrival->vendor->vendor_name ?? '-' }} • Invoice {{ $arrival->invoice_no }}</p>
            </div>
            <a href="{{ route('arrivals.index') }}" class="text-blue-600 hover:text-blue-800">Back</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow sm:rounded-lg p-6 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm text-gray-700">
                    <div><span class="font-semibold">Invoice:</span> {{ $arrival->invoice_no }} ({{ $arrival->invoice_date->format('Y-m-d') }})</div>
                    <div><span class="font-semibold">Vendor:</span> {{ $arrival->vendor->vendor_name ?? '-' }}</div>
                    <div><span class="font-semibold">Created by:</span> {{ $arrival->creator->name ?? '-' }}</div>
                    <div><span class="font-semibold">Vessel:</span> {{ $arrival->vessel }}</div>
                    <div><span class="font-semibold">Trucking:</span> {{ $arrival->trucking_company }}</div>
                    <div><span class="font-semibold">ETD:</span> {{ $arrival->ETD }}</div>
                    <div><span class="font-semibold">Bill of Lading:</span> {{ $arrival->bill_of_lading }}</div>
                    <div><span class="font-semibold">HS Code:</span> {{ $arrival->hs_code }}</div>
                    <div><span class="font-semibold">Port of Loading:</span> {{ $arrival->port_of_loading }}</div>
                    <div><span class="font-semibold">Currency:</span> {{ $arrival->currency }}</div>
                </div>
                @if ($arrival->notes)
                    <div class="text-sm text-gray-700"><span class="font-semibold">Notes:</span> {{ $arrival->notes }}</div>
                @endif
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6 overflow-x-auto">
                <table class="min-w-full text-sm divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600">Part</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600">Qty Bundle</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600">Qty Goods</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600">Nett</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600">Gross</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600">Price</th>
                            <th class="px-3 py-2 text-left font-semibold text-gray-600">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($arrival->items as $item)
                            <tr>
                                <td class="px-3 py-2 text-gray-800">{{ $item->part->part_no }} — {{ $item->part->part_name_vendor }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ $item->qty_bundle }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ $item->qty_goods }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ number_format($item->weight_nett, 2) }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ number_format($item->weight_gross, 2) }}</td>
                                <td class="px-3 py-2 text-gray-700">{{ number_format($item->price, 2) }}</td>
                                <td class="px-3 py-2 text-gray-800 font-semibold">{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
