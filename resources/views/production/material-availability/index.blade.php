<x-app-layout>
    <x-slot name="header">
        Material Availability Check
    </x-slot>

    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-slate-800">Orders Pending Material Check</h2>
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

        @if(session('warning'))
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg">
                {{ session('warning') }}
            </div>
        @endif

        @if(session('materials'))
            <div class="bg-white border rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">Material Check Results</h3>
                <table class="w-full text-sm">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Part No</th>
                            <th class="px-4 py-2 text-left">Part Name</th>
                            <th class="px-4 py-2 text-right">Required</th>
                            <th class="px-4 py-2 text-right">Available</th>
                            <th class="px-4 py-2 text-right">Shortage</th>
                            <th class="px-4 py-2 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach(session('materials') as $material)
                            <tr>
                                <td class="px-4 py-2">{{ $material['part_no'] }}</td>
                                <td class="px-4 py-2">{{ $material['part_name'] }}</td>
                                <td class="px-4 py-2 text-right font-mono">{{ number_format($material['required'], 2) }}</td>
                                <td class="px-4 py-2 text-right font-mono">{{ number_format($material['available'], 2) }}</td>
                                <td class="px-4 py-2 text-right font-mono">{{ number_format($material['shortage'], 2) }}</td>
                                <td class="px-4 py-2 text-center">
                                    @if($material['status'] === 'available')
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">OK</span>
                                    @else
                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">SHORTAGE</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <form method="GET" class="bg-white border rounded-xl shadow-sm p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold text-slate-600">Status</label>
                    <select name="status" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                        <option value="">All</option>
                        <option value="kanban_released" @selected($status === 'kanban_released')>Kanban Released</option>
                        <option value="material_hold" @selected($status === 'material_hold')>Material Hold</option>
                        <option value="resource_hold" @selected($status === 'resource_hold')>Resource Hold</option>
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold text-slate-600">Search</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Order number / Part" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('production.material-availability.index') }}" class="px-3 py-2 rounded-lg border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
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
                        <th class="px-6 py-4 font-semibold">Plan Date</th>
                        <th class="px-6 py-4 font-semibold">Qty Planned</th>
                        <th class="px-6 py-4 font-semibold">Status</th>
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
                            <td class="px-6 py-4 text-slate-600">{{ $order->plan_date ? \Carbon\Carbon::parse($order->plan_date)->format('d M Y') : '-' }}</td>
                            <td class="px-6 py-4 font-mono text-slate-700">{{ number_format($order->qty_planned) }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $colors = [
                                        'kanban_released' => 'bg-indigo-100 text-indigo-700',
                                        'material_hold' => 'bg-red-100 text-red-700',
                                        'resource_hold' => 'bg-orange-100 text-orange-700',
                                    ];
                                    $class = $colors[$order->status] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $class }}">
                                    {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('production.material-availability.show', $order) }}" class="text-blue-600 hover:text-blue-900 font-medium text-xs uppercase tracking-wide">
                                    View Details
                                </a>
                                <form method="POST" action="{{ route('production.material-availability.check', $order) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-indigo-600 hover:text-indigo-900 font-medium text-xs uppercase tracking-wide">
                                        Check Material
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">
                                No orders pending material check.
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
