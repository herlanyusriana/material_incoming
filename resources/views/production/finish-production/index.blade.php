<x-app-layout>
    <x-slot name="header">
        Finish Production
    </x-slot>

    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-slate-800">Orders Ready to Finish</h2>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <form method="GET" class="bg-white border rounded-xl shadow-sm p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-600">Search</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Order number / Part" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('production.finish-production.index') }}" class="px-3 py-2 rounded-lg border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                    <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">Apply</button>
                </div>
            </div>
        </form>

        <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Order #</th>
                        <th class="px-6 py-4 font-semibold">Part</th>
                        <th class="px-6 py-4 font-semibold">Qty Planned</th>
                        <th class="px-6 py-4 font-semibold">Qty Produced</th>
                        <th class="px-6 py-4 font-semibold">Start Time</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $order->production_order_number }}</td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900">{{ $order->part->part_no }}</div>
                                <div class="text-xs text-slate-500">{{ $order->part->part_name }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-slate-700">{{ number_format($order->qty_planned) }}</td>
                            <td class="px-6 py-4 font-mono text-slate-700">{{ number_format($order->qty_actual) }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $order->start_time ? \Carbon\Carbon::parse($order->start_time)->format('d M Y H:i') : '-' }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('production.finish-production.show', $order) }}" class="text-indigo-600 hover:text-indigo-900 font-medium text-xs uppercase tracking-wide">
                                    Finish
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">
                                No orders ready to finish.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-4 border-t bg-slate-50">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
