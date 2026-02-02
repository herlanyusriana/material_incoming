<x-app-layout>
    <x-slot name="header">
        Start Production
    </x-slot>

    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-slate-800">Orders Ready to Start Production</h2>
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
                    <a href="{{ route('production.start-production.index') }}" class="px-3 py-2 rounded-lg border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
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
                        <th class="px-6 py-4 font-semibold">Process / Machine</th>
                        <th class="px-6 py-4 font-semibold">Plan Date</th>
                        <th class="px-6 py-4 font-semibold">Qty Planned</th>
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
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-slate-900">{{ $order->process_name ?: '-' }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $order->machine_name ?: '-' }}{{ $order->die_name ? ' • ' . $order->die_name : '' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $order->plan_date ? \Carbon\Carbon::parse($order->plan_date)->format('d M Y') : '-' }}</td>
                            <td class="px-6 py-4 font-mono text-slate-700">{{ number_format($order->qty_planned) }}</td>
                            <td class="px-6 py-4 text-right">
                                <form method="POST" action="{{ route('production.start-production.start', $order) }}" class="inline">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Start production for this order?')" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-xs font-semibold">
                                        START PRODUCTION
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">
                                No orders ready to start production.
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
