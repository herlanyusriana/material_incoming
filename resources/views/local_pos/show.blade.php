<x-app-layout>
    <x-slot name="header">
        Local PO Details — {{ $arrival->invoice_no }}
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white border border-slate-200 rounded-2xl shadow-lg p-6">
                <div class="flex items-start justify-between border-b border-slate-200 pb-6 mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Local PO Information</h3>
                        <p class="text-sm text-slate-600 mt-1">Vendor: {{ $arrival->vendor->vendor_name }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('local-pos.edit', $arrival) }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                            Edit PO
                        </a>
                        <a href="{{ route('local-pos.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                            Back to List
                        </a>
                    </div>
                </div>

                <dl class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                    <div>
                        <dt class="font-semibold text-slate-500">PO Number</dt>
                        <dd class="mt-1 text-slate-900 font-medium">{{ $arrival->invoice_no }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">PO Date</dt>
                        <dd class="mt-1 text-slate-900 font-medium">{{ $arrival->invoice_date?->format('d M Y') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Currency</dt>
                        <dd class="mt-1 text-slate-900 font-medium">{{ $arrival->currency }}</dd>
                    </div>
                    @if($arrival->notes)
                        <div class="md:col-span-3">
                            <dt class="font-semibold text-slate-500">Notes</dt>
                            <dd class="mt-1 text-slate-900">{{ $arrival->notes }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Ordered Items</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-white">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-6 py-3 text-left font-semibold">Part No / Name</th>
                                <th class="px-6 py-3 text-left font-semibold">Size</th>
                                <th class="px-6 py-3 text-right font-semibold">Ordered Qty</th>
                                <th class="px-6 py-3 text-right font-semibold">Price/UOM</th>
                                <th class="px-6 py-3 text-right font-semibold">Total Price</th>
                                <th class="px-6 py-3 text-right font-semibold">Received</th>
                                <th class="px-6 py-3 text-right font-semibold">Remaining</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach ($arrival->items as $item)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-900">{{ $item->part->part_no }}</div>
                                        <div class="text-xs text-slate-500">{{ $item->part->part_name_vendor }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs text-slate-700">{{ $item->size ?? '-' }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-semibold text-slate-900">{{ number_format($item->qty_goods) }}</span>
                                        <span class="text-xs text-slate-500">{{ $item->unit_goods }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @if($item->price > 0)
                                            <span class="text-slate-900">{{ number_format($item->price, 2) }}</span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                         @if($item->total_price > 0)
                                            <span class="font-semibold text-slate-900">{{ number_format($item->total_price, 2) }}</span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-semibold text-emerald-700">{{ number_format($item->received_qty) }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="font-semibold {{ $item->remaining_qty > 0 ? 'text-amber-700' : 'text-slate-400' }}">
                                            {{ number_format($item->remaining_qty) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-slate-50">
                            <tr>
                                <td colspan="4" class="px-6 py-3 text-right font-bold text-slate-700">TOTAL ESTIMATED</td>
                                <td class="px-6 py-3 text-right font-bold text-slate-900">{{ number_format($arrival->items->sum('total_price'), 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
