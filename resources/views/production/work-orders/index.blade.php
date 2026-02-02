<x-app-layout>
    <x-slot name="header">
        Work Order & Kanban Release
    </x-slot>

    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-slate-800">Work Orders Ready for Kanban Release</h2>
            <form method="POST" action="{{ route('production.work-orders.bulk-release') }}" id="bulkReleaseForm">
                @csrf
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-sm transition-colors">
                    Release Selected to Kanban
                </button>
            </form>
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold text-slate-600">Status</label>
                    <select name="status" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                        <option value="">All</option>
                        <option value="planned" @selected($status === 'planned')>Planned</option>
                        <option value="kanban_released" @selected($status === 'kanban_released')>Kanban Released</option>
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold text-slate-600">Search</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Order number / Part" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('production.work-orders.index') }}" class="px-3 py-2 rounded-lg border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                    <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">Apply</button>
                </div>
            </div>
        </form>

        <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b">
                    <tr>
                        <th class="px-6 py-4">
                            <input type="checkbox" id="selectAll" class="rounded border-slate-300">
                        </th>
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
                            <td class="px-6 py-4">
                                @if($order->status === 'planned')
                                    <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" form="bulkReleaseForm" class="order-checkbox rounded border-slate-300">
                                @endif
                            </td>
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
                                        'planned' => 'bg-slate-100 text-slate-700',
                                        'kanban_released' => 'bg-indigo-100 text-indigo-700',
                                    ];
                                    $class = $colors[$order->status] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $class }}">
                                    {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($order->status === 'planned')
                                    <form method="POST" action="{{ route('production.work-orders.release', $order) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-indigo-600 hover:text-indigo-900 font-medium text-xs uppercase tracking-wide">
                                            Release to Kanban
                                        </button>
                                    </form>
                                @else
                                    <span class="text-slate-400 text-xs">Released</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500 italic">
                                No work orders found.
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

    @push('scripts')
    <script>
        document.getElementById('selectAll')?.addEventListener('change', function() {
            document.querySelectorAll('.order-checkbox').forEach(cb => cb.checked = this.checked);
        });
    </script>
    @endpush
</x-app-layout>
