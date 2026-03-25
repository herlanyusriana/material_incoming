<x-app-layout>
    <x-slot name="header">
        Production Material Request
    </x-slot>

    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Production Material Request</h1>
                <p class="mt-1 text-sm text-slate-500">Pengajuan tambahan material dari production ke warehouse di luar kebutuhan standar WO/BOM.</p>
            </div>
            <a href="{{ route('production.material-request.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                New Material Request
            </a>
        </div>

        <form method="GET" class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600">Month</label>
                    <input type="month" name="month" value="{{ $month }}"
                        class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600">Status</label>
                    <select name="status" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                        <option value="">All</option>
                        @foreach(['requested', 'issued_partial', 'issued_complete', 'cancelled'] as $option)
                            <option value="{{ $option }}" @selected($status === $option)>{{ strtoupper(str_replace('_', ' ', $option)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600">Search</label>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Request no / WO / part / reason"
                        class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <a href="{{ route('production.material-request.index') }}"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Request No</th>
                        <th class="px-6 py-4 font-semibold">Request Date</th>
                        <th class="px-6 py-4 font-semibold">WO Reference</th>
                        <th class="px-6 py-4 font-semibold">Reason</th>
                        <th class="px-6 py-4 font-semibold">Items</th>
                        <th class="px-6 py-4 font-semibold">Requested By</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
                        <th class="px-6 py-4 text-right font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($requests as $requestItem)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900">{{ $requestItem->request_no }}</div>
                                <div class="text-xs text-slate-500">Created {{ $requestItem->created_at?->format('d M Y H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-700">{{ $requestItem->request_date?->format('d M Y') ?? '-' }}</td>
                            <td class="px-6 py-4">
                                @if($requestItem->productionOrder)
                                    <div class="font-medium text-slate-900">{{ $requestItem->productionOrder->production_order_number }}</div>
                                    <div class="text-xs text-slate-500">{{ $requestItem->productionOrder->part?->part_no ?? '-' }}</div>
                                @else
                                    <span class="text-xs italic text-slate-400">Manual request</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-700">{{ $requestItem->reason }}</td>
                            <td class="px-6 py-4 text-slate-700">{{ number_format($requestItem->items_count) }}</td>
                            <td class="px-6 py-4 text-slate-700">{{ $requestItem->requester?->name ?? '-' }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $statusClass = match($requestItem->status) {
                                        'issued_complete' => 'bg-emerald-100 text-emerald-700',
                                        'issued_partial' => 'bg-amber-100 text-amber-700',
                                        'cancelled' => 'bg-rose-100 text-rose-700',
                                        default => 'bg-indigo-100 text-indigo-700',
                                    };
                                @endphp
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ strtoupper(str_replace('_', ' ', $requestItem->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('production.material-request.show', $requestItem) }}"
                                    class="text-xs font-semibold uppercase tracking-wide text-indigo-600 hover:text-indigo-900">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                Belum ada material request tambahan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t bg-slate-50 px-6 py-4">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
