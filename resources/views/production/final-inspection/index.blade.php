<x-app-layout>
    <x-slot name="header">
        Final Inspection & Kanban Update
    </x-slot>

    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-slate-800">Final Inspections</h2>
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
                    <label class="block text-xs font-semibold text-slate-600">Status</label>
                    <select name="status" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                        <option value="">All</option>
                        <option value="pending" @selected($status === 'pending')>Pending</option>
                        <option value="pass" @selected($status === 'pass')>Pass</option>
                        <option value="fail" @selected($status === 'fail')>Fail</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('production.final-inspection.index') }}" class="px-3 py-2 rounded-lg border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
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
                        <th class="px-6 py-4 font-semibold">Qty Produced</th>
                        <th class="px-6 py-4 font-semibold">Inspection Status</th>
                        <th class="px-6 py-4 font-semibold">Kanban Status</th>
                        <th class="px-6 py-4 font-semibold">Inspector</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($inspections as $inspection)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $inspection->productionOrder->production_order_number }}</td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900">{{ $inspection->productionOrder->part->part_no }}</div>
                                <div class="text-xs text-slate-500">{{ $inspection->productionOrder->part->part_name }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-slate-700">{{ number_format($inspection->productionOrder->qty_actual) }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'pass' => 'bg-green-100 text-green-700',
                                        'fail' => 'bg-red-100 text-red-700',
                                    ];
                                    $class = $colors[$inspection->status] ?? 'bg-slate-100 text-slate-700';
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $class }}">
                                    {{ strtoupper($inspection->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($inspection->productionOrder->kanban_updated_at)
                                    <span class="px-2.5 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">UPDATED</span>
                                @else
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-700 rounded-full text-xs font-semibold">PENDING</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $inspection->inspector?->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('production.final-inspection.show', $inspection) }}" class="text-indigo-600 hover:text-indigo-900 font-medium text-xs uppercase tracking-wide">
                                    {{ $inspection->status === 'pending' ? 'Inspect' : 'View / Update Kanban' }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500 italic">
                                No final inspections found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-4 border-t bg-slate-50">
                {{ $inspections->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
