<x-app-layout>
    <x-slot name="header">
        Mass Production - {{ $order->production_order_number }}
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white border rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">Order Details</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Order Number</label>
                        <p class="text-sm font-medium">{{ $order->production_order_number }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Part</label>
                        <p class="text-sm font-medium">{{ $order->part->part_no }} - {{ $order->part->part_name }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Process</label>
                            <p class="text-sm font-medium">{{ $order->process_name ?: '-' }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Machine</label>
                            <p class="text-sm font-medium">{{ $order->machine_name ?: '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">Production Progress</h3>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Qty Planned</label>
                            <p class="text-2xl font-bold text-slate-900">{{ number_format($order->qty_planned) }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Qty Produced</label>
                            <p class="text-2xl font-bold text-indigo-600">{{ number_format($order->qty_actual) }}</p>
                        </div>
                    </div>
                    @php
                        $progress = $order->qty_planned > 0 ? ($order->qty_actual / $order->qty_planned * 100) : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-xs font-semibold text-slate-600">Progress</span>
                            <span class="text-xs font-semibold text-slate-600">{{ number_format($progress, 1) }}%</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-3">
                            <div class="bg-indigo-600 h-3 rounded-full transition-all" style="width: {{ min($progress, 100) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Update Production Progress</h3>
            <form method="POST" action="{{ route('production.mass-production.update-progress', $order) }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Quantity Produced *</label>
                        <input type="number" name="qty_produced" value="{{ $order->qty_actual }}" step="1" min="0" class="w-full rounded-lg border-slate-200" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Notes</label>
                        <input type="text" name="notes" value="{{ $order->production_notes }}" class="w-full rounded-lg border-slate-200" placeholder="Optional notes">
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-sm font-semibold">
                        Update Progress
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white border rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Actions</h3>
            <div class="flex gap-3">
                <form method="POST" action="{{ route('production.mass-production.request-inspection', $order) }}">
                    @csrf
                    <button type="submit" class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 shadow-sm font-semibold">
                        Request In-Process Inspection
                    </button>
                </form>
                <a href="{{ route('production.finish-production.index') }}" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 shadow-sm font-semibold">
                    Go to Finish Production
                </a>
            </div>
        </div>

        @if($order->inspections->count() > 0)
            <div class="bg-white border rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">Inspection History</h3>
                <table class="w-full text-sm">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Type</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Inspector</th>
                            <th class="px-4 py-2 text-left">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($order->inspections as $inspection)
                            <tr>
                                <td class="px-4 py-2">{{ strtoupper(str_replace('_', ' ', $inspection->type)) }}</td>
                                <td class="px-4 py-2">
                                    @php
                                        $colors = ['pending' => 'bg-yellow-100 text-yellow-700', 'pass' => 'bg-green-100 text-green-700', 'fail' => 'bg-red-100 text-red-700'];
                                        $class = $colors[$inspection->status] ?? 'bg-slate-100 text-slate-700';
                                    @endphp
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $class }}">{{ strtoupper($inspection->status) }}</span>
                                </td>
                                <td class="px-4 py-2">{{ $inspection->inspector?->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $inspection->inspected_at?->format('d M Y H:i') ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
