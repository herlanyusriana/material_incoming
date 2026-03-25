<x-app-layout>
    <x-slot name="header">
        WH Supply to Production
    </x-slot>

    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">WH Supply to Production</h1>
            <p class="mt-1 text-sm text-slate-500">Daftar WO yang sudah punya material request tambahan atau BOM request, termasuk status supply dan serah terimanya.</p>
        </div>

        <form method="GET" class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600">Month</label>
                    <input type="month" name="month" value="{{ $month }}" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600">Supply Status</label>
                    <select name="supply_status" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                        <option value="">All</option>
                        <option value="pending" @selected($supplyStatus === 'pending')>Pending Supply</option>
                        <option value="supplied" @selected($supplyStatus === 'supplied')>Supplied</option>
                        <option value="handed_over" @selected($supplyStatus === 'handed_over')>Handed Over</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600">Search</label>
                    <input type="text" name="q" value="{{ $q }}" placeholder="WO / transaction / part"
                        class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <a href="{{ route('production.warehouse-supply.index') }}"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4 font-semibold">WO</th>
                        <th class="px-6 py-4 font-semibold">Part</th>
                        <th class="px-6 py-4 font-semibold">Plan Date</th>
                        <th class="px-6 py-4 font-semibold">Material Request</th>
                        <th class="px-6 py-4 font-semibold">WH Supply</th>
                        <th class="px-6 py-4 font-semibold">Serah Terima</th>
                        <th class="px-6 py-4 text-right font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900">{{ $order->production_order_number }}</div>
                                <div class="text-xs text-slate-500">{{ $order->transaction_no ?: '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900">{{ $order->part?->part_no ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $order->part?->part_name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-700">{{ $order->plan_date ? \Carbon\Carbon::parse($order->plan_date)->format('d M Y') : '-' }}</td>
                            <td class="px-6 py-4">
                                @if($order->material_requested_at)
                                    <div class="text-sm font-medium text-slate-900">{{ $order->material_requested_at->format('d M Y H:i') }}</div>
                                    <div class="text-xs text-slate-500">{{ $order->material_request_lines ? count($order->material_request_lines) : 0 }} item</div>
                                @else
                                    <span class="text-xs italic text-slate-400">Belum dibuat</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($order->material_issued_at)
                                    <div class="text-sm font-medium text-emerald-700">{{ $order->material_issued_at->format('d M Y H:i') }}</div>
                                    <div class="text-xs text-slate-500">{{ $order->materialIssuer?->name ?? '-' }}</div>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Pending</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($order->material_handed_over_at)
                                    <div class="text-sm font-medium text-blue-700">{{ $order->material_handed_over_at->format('d M Y H:i') }}</div>
                                    <div class="text-xs text-slate-500">{{ $order->materialHandoverUser?->name ?? '-' }}</div>
                                @else
                                    <span class="text-xs italic text-slate-400">Belum serah terima</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('production.orders.show', $order) }}"
                                    class="text-xs font-semibold uppercase tracking-wide text-indigo-600 hover:text-indigo-900">
                                    Open WO
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">Belum ada data WH Supply to Production.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t bg-slate-50 px-6 py-4">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
