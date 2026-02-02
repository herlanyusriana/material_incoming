<x-app-layout>
    <x-slot name="header">
        Finish Production - {{ $order->production_order_number }}
    </x-slot>

    <div class="space-y-6">
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

        <div class="bg-white border rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Production Order Summary</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Order Number</label>
                    <p class="text-sm font-medium">{{ $order->production_order_number }}</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Part</label>
                    <p class="text-sm font-medium">{{ $order->part->part_no }}</p>
                    <p class="text-xs text-slate-500">{{ $order->part->part_name }}</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Qty Planned</label>
                    <p class="text-sm font-medium">{{ number_format($order->qty_planned) }}</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Current Qty Produced</label>
                    <p class="text-sm font-medium">{{ number_format($order->qty_actual) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white border rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Finish Production</h3>
            <form method="POST" action="{{ route('production.finish-production.finish', $order) }}">
                @csrf
                
                <div class="space-y-4">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-yellow-800">
                            <strong>Important:</strong> Finishing production will move this order to Final Inspection stage. 
                            Please confirm the actual quantity produced.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Final Quantity Produced *</label>
                        <input type="number" name="qty_actual" value="{{ $order->qty_actual }}" step="1" min="0" class="w-full rounded-lg border-slate-200" required>
                        <p class="text-xs text-slate-500 mt-1">Enter the total quantity produced for this order</p>
                    </div>

                    <div class="flex justify-between items-center pt-4">
                        <a href="{{ route('production.finish-production.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">
                            Back to List
                        </a>
                        <button type="submit" onclick="return confirm('Are you sure you want to finish this production order?')" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 shadow-sm font-semibold">
                            Finish Production
                        </button>
                    </div>
                </div>
            </form>
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
                            <th class="px-4 py-2 text-left">Notes</th>
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
                                <td class="px-4 py-2 text-xs">{{ $inspection->notes ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
