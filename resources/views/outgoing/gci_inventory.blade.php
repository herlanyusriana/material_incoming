@extends('outgoing.layout')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-emerald-600 to-emerald-700 flex items-center justify-center text-white font-black">
                        FG
                    </div>
                    <div>
                        <div class="text-2xl md:text-3xl font-black text-slate-900">Finished Goods Inventory</div>
                        <div class="mt-1 text-sm text-slate-600">Stock on hand for shipping to customers</div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('inventory.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Raw Material Inventory
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-5 py-4 text-left font-bold text-slate-700">Part Number</th>
                        <th class="px-5 py-4 text-left font-bold text-slate-700">Part Name</th>
                        <th class="px-5 py-4 text-left font-bold text-slate-700">Location</th>
                        <th class="px-5 py-4 text-right font-bold text-slate-700">On Hand Qty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($inventory as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4 font-black text-slate-900">{{ $item->part->part_no }}</td>
                            <td class="px-5 py-4 text-slate-600 font-semibold">{{ $item->part->part_name }}</td>
                            <td class="px-5 py-4 text-slate-500">{{ $item->location ?: 'Main Warehouse' }}</td>
                            <td class="px-5 py-4 text-right">
                                <span @class([
                                    'px-3 py-1 rounded-lg font-black text-lg',
                                    'text-emerald-600' => $item->qty_on_hand > 0,
                                    'text-red-500' => $item->qty_on_hand <= 0,
                                ])>
                                    {{ number_format($item->qty_on_hand) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-slate-500 italic">No FG Inventory data recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
