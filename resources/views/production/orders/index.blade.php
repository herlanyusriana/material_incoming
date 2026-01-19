<x-app-layout>
    <x-slot name="header">
        Production Orders
    </x-slot>

    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-slate-800">Order List</h2>
            <a href="{{ route('production.orders.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                New Production Order
            </a>
        </div>

        <div class="bg-white border rounded-lg shadow-sm">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b">
                    <tr>
                        <th class="px-6 py-3">Order #</th>
                        <th class="px-6 py-3">Part</th>
                        <th class="px-6 py-3">Plan Date</th>
                        <th class="px-6 py-3">Qty</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Stage</th>
                        <th class="px-6 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-medium">{{ $order->production_order_number }}</td>
                            <td class="px-6 py-4">
                                {{ $order->part->part_no }} <br>
                                <span class="text-xs text-gray-500">{{ $order->part->part_name }}</span>
                            </td>
                            <td class="px-6 py-4">{{ $order->plan_date ? \Carbon\Carbon::parse($order->plan_date)->format('d M Y') : '-' }}</td>
                            <td class="px-6 py-4">{{ number_format($order->qty_planned) }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $colors = [
                                        'planned' => 'bg-gray-100 text-gray-800',
                                        'released' => 'bg-blue-100 text-blue-800',
                                        'material_check_fail' => 'bg-red-100 text-red-800',
                                        'in_production' => 'bg-yellow-100 text-yellow-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                        'hold' => 'bg-orange-100 text-orange-800',
                                    ];
                                    $class = $colors[$order->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="px-2 py-1 rounded text-xs font-semibold {{ $class }}">
                                    {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                {{ strtoupper(str_replace('_', ' ', $order->workflow_stage)) }}
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('production.orders.show', $order) }}" class="text-blue-600 hover:text-blue-900 font-medium">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                No production orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-4">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
