<x-app-layout>
    <x-slot name="header">
        Material Request {{ $materialRequest->request_no }}
    </x-slot>

    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $materialRequest->request_no }}</h1>
                <p class="mt-1 text-sm text-slate-500">Detail pengajuan tambahan material dari production ke warehouse.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('production.material-request.index') }}"
                    class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Back to List
                </a>
                <a href="{{ route('production.material-request.create', ['production_order_id' => $materialRequest->production_order_id]) }}"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    New Request
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border bg-white p-6 shadow-sm lg:col-span-1">
                <h2 class="text-lg font-semibold text-slate-900">Header</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="text-slate-500">Request Date</dt>
                        <dd class="font-medium text-slate-900">{{ $materialRequest->request_date?->format('d M Y') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Status</dt>
                        <dd class="font-medium text-slate-900">{{ strtoupper(str_replace('_', ' ', $materialRequest->status)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Requested By</dt>
                        <dd class="font-medium text-slate-900">{{ $materialRequest->requester?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">WO Reference</dt>
                        <dd class="font-medium text-slate-900">
                            @if($materialRequest->productionOrder)
                                {{ $materialRequest->productionOrder->production_order_number }}
                                <div class="text-xs text-slate-500">{{ $materialRequest->productionOrder->part?->part_no ?? '-' }} - {{ $materialRequest->productionOrder->part?->part_name ?? '-' }}</div>
                            @else
                                Manual request
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Reason</dt>
                        <dd class="font-medium text-slate-900">{{ $materialRequest->reason }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Notes</dt>
                        <dd class="font-medium text-slate-900">{{ $materialRequest->notes ?: '-' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="overflow-hidden rounded-2xl border bg-white shadow-sm lg:col-span-2">
                <div class="border-b px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">Requested Items</h2>
                </div>
                <table class="w-full text-left text-sm">
                    <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Part</th>
                            <th class="px-6 py-4 text-right font-semibold">Qty Request</th>
                            <th class="px-6 py-4 text-right font-semibold">Qty Issued</th>
                            <th class="px-6 py-4 text-right font-semibold">Stock On Hand</th>
                            <th class="px-6 py-4 text-right font-semibold">Stock On Order</th>
                            <th class="px-6 py-4 text-center font-semibold">UOM</th>
                            <th class="px-6 py-4 font-semibold">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($materialRequest->items as $item)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">{{ $item->part_no }}</div>
                                    <div class="text-xs text-slate-500">{{ $item->part_name }}</div>
                                </td>
                                <td class="px-6 py-4 text-right font-mono text-slate-700">{{ number_format($item->qty_requested, 4) }}</td>
                                <td class="px-6 py-4 text-right font-mono text-slate-700">{{ number_format($item->qty_issued, 4) }}</td>
                                <td class="px-6 py-4 text-right font-mono text-slate-700">{{ number_format($item->stock_on_hand, 4) }}</td>
                                <td class="px-6 py-4 text-right font-mono text-slate-700">{{ number_format($item->stock_on_order, 4) }}</td>
                                <td class="px-6 py-4 text-center text-slate-700">{{ $item->uom ?: '-' }}</td>
                                <td class="px-6 py-4 text-slate-700">{{ $item->notes ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
