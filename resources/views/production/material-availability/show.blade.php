<x-app-layout>
    <x-slot name="header">
        Material Availability - {{ $order->production_order_number }}
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white border rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Production Order Details</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Order Number</label>
                    <p class="text-sm font-medium">{{ $order->production_order_number }}</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Part</label>
                    <p class="text-sm font-medium">{{ $order->part->part_no }} - {{ $order->part->part_name }}</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Qty Planned</label>
                    <p class="text-sm font-medium">{{ number_format($order->qty_planned) }}</p>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Status</label>
                    <p class="text-sm font-medium">{{ strtoupper(str_replace('_', ' ', $order->status)) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white border rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Material Requirements (BOM)</h3>
            @if(count($materials) > 0)
                <table class="w-full text-sm">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Part No</th>
                            <th class="px-4 py-3 text-left">Part Name</th>
                            <th class="px-4 py-3 text-center">UOM</th>
                            <th class="px-4 py-3 text-right">Required</th>
                            <th class="px-4 py-3 text-right">Available</th>
                            <th class="px-4 py-3 text-right">Shortage</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($materials as $material)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-medium">{{ $material['part_no'] }}</td>
                                <td class="px-4 py-3">{{ $material['part_name'] }}</td>
                                <td class="px-4 py-3 text-center">{{ $material['uom'] }}</td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format($material['required'], 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format($material['available'], 2) }}</td>
                                <td class="px-4 py-3 text-right font-mono {{ $material['shortage'] > 0 ? 'text-red-600 font-bold' : '' }}">
                                    {{ number_format($material['shortage'], 2) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($material['status'] === 'available')
                                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">✓ OK</span>
                                    @else
                                        <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-semibold">✗ SHORTAGE</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-6 flex justify-between items-center">
                    <a href="{{ route('production.material-availability.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50">
                        Back to List
                    </a>
                    <form method="POST" action="{{ route('production.material-availability.check', $order) }}">
                        @csrf
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-sm">
                            Run Material Check
                        </button>
                    </form>
                </div>
            @else
                <div class="text-center py-8 text-slate-500">
                    <p>No BOM found for this part.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
