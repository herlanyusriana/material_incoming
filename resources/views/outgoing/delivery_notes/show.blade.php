@extends('outgoing.layout')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                <div class="flex items-center gap-3">
                    <a href="{{ route('outgoing.delivery-notes.index') }}" class="h-10 w-10 flex items-center justify-center rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-500">
                        ←
                    </a>
                    <div>
                        <h1 class="text-2xl font-black text-slate-900">{{ $deliveryNote->dn_no }}</h1>
                        <p class="text-sm text-slate-500 font-semibold">{{ $deliveryNote->customer->name }} • {{ $deliveryNote->delivery_date->format('d M Y') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @php
                        $statusClasses = match ($deliveryNote->status) {
                            'draft' => 'bg-slate-100 text-slate-700',
                            'kitting' => 'bg-fuchsia-100 text-fuchsia-700',
                            'ready_to_pick' => 'bg-violet-100 text-violet-700',
                            'picking' => 'bg-amber-100 text-amber-700',
                            'ready_to_ship' => 'bg-blue-100 text-blue-700',
                            'shipped' => 'bg-emerald-100 text-emerald-700',
                            'cancelled' => 'bg-red-100 text-red-700',
                            default => 'bg-slate-100 text-slate-700',
                        };
                    @endphp
                    <span class="px-4 py-2 rounded-xl text-sm font-black uppercase tracking-wider {{ $statusClasses }}">
                        {{ $deliveryNote->status }}
                    </span>

                    @if ($deliveryNote->status === 'draft')
                        <form action="{{ route('outgoing.delivery-notes.start-kitting', $deliveryNote) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-6 py-2 rounded-xl bg-fuchsia-600 text-white font-black hover:bg-fuchsia-700 shadow-lg shadow-fuchsia-100 transition-all active:scale-95">
                                START KITTING
                            </button>
                        </form>
                    @elseif ($deliveryNote->status === 'kitting')
                        <form action="{{ route('outgoing.delivery-notes.complete-kitting', $deliveryNote) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-6 py-2 rounded-xl bg-violet-600 text-white font-black hover:bg-violet-700 shadow-lg shadow-violet-100 transition-all active:scale-95">
                                COMPLETE KITTING
                            </button>
                        </form>
                    @elseif ($deliveryNote->status === 'ready_to_pick')
                        <form action="{{ route('outgoing.delivery-notes.start-picking', $deliveryNote) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-6 py-2 rounded-xl bg-amber-600 text-white font-black hover:bg-amber-700 shadow-lg shadow-amber-100 transition-all active:scale-95">
                                START PICKING
                            </button>
                        </form>
                    @elseif ($deliveryNote->status === 'picking')
                        <form action="{{ route('outgoing.delivery-notes.complete-picking', $deliveryNote) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-6 py-2 rounded-xl bg-blue-600 text-white font-black hover:bg-blue-700 shadow-lg shadow-blue-100 transition-all active:scale-95">
                                COMPLETE PICKING
                            </button>
                        </form>
                    @elseif ($deliveryNote->status === 'ready_to_ship')
                        <form action="{{ route('outgoing.delivery-notes.ship', $deliveryNote) }}" method="POST" onsubmit="return confirm('SHIP this delivery note? Inventory will be deducted.')">
                            @csrf
                            <button type="submit" class="px-6 py-2 rounded-xl bg-emerald-600 text-white font-black hover:bg-emerald-700 shadow-lg shadow-emerald-100 transition-all active:scale-95">
                                SHIP NOW
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Customer</p>
                    <p class="font-bold text-slate-900">{{ $deliveryNote->customer->name }}</p>
                    <p class="text-xs text-slate-600">{{ $deliveryNote->customer->code }}</p>
                </div>
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Delivery Date</p>
                    <p class="font-bold text-slate-900">{{ $deliveryNote->delivery_date->format('l, d F Y') }}</p>
                </div>
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Notes</p>
                    <p class="font-bold text-slate-900">{{ $deliveryNote->notes ?: '-' }}</p>
                </div>
            </div>

            <div class="space-y-4">
                <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                    <span class="w-2 h-6 bg-indigo-600 rounded-full"></span>
                    Items to Ship
                </h2>

                <div class="border border-slate-100 rounded-2xl overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-5 py-3 text-left font-bold text-slate-600">Part Number</th>
                                <th class="px-5 py-3 text-left font-bold text-slate-600">Part Name</th>
                                <th class="px-5 py-3 text-right font-bold text-slate-600">Quantity</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($deliveryNote->items as $item)
                                <tr>
                                    <td class="px-5 py-4 font-black text-slate-900">{{ $item->part->part_no }}</td>
                                    <td class="px-5 py-4 text-slate-600 font-semibold">{{ $item->part->part_name }}</td>
                                    <td class="px-5 py-4 text-right font-black text-indigo-600 text-lg">{{ number_format($item->qty) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-indigo-50 font-black">
                            <tr>
                                <td colspan="2" class="px-5 py-4 text-right text-indigo-900 uppercase tracking-wider">Total Quantity</td>
                                <td class="px-5 py-4 text-right text-indigo-900 text-xl">{{ number_format($deliveryNote->items->sum('qty')) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if ($deliveryNote->status === 'shipped')
                <div class="mt-8 p-6 bg-emerald-50 rounded-2xl border border-emerald-100 flex items-center gap-4">
                    <div class="h-12 w-12 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xl">
                        ✓
                    </div>
                    <div>
                        <p class="font-black text-emerald-900">Delivery Confirmed</p>
                        <p class="text-sm text-emerald-700">Stock has been deducted from FG Inventory for all items in this delivery note.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
