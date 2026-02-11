@extends('outgoing.layout')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-3">
                    <div
                        class="h-12 w-12 rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-white font-black text-sm shadow-lg">
                        PO
                    </div>
                    <div>
                        <div class="text-2xl md:text-3xl font-black text-slate-900">{{ $outgoingPo->po_no }}</div>
                        <div class="mt-1 text-sm text-slate-500 flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-slate-700">{{ $outgoingPo->customer->name ?? '-' }}</span>
                            <span class="text-slate-300">•</span>
                            <span>Released: {{ $outgoingPo->po_release_date->format('d M Y') }}</span>
                            <span class="text-slate-300">•</span>
                            @php
                                $statusColor = match ($outgoingPo->status) {
                                    'draft' => 'bg-slate-100 text-slate-600 border-slate-200',
                                    'confirmed' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'completed' => 'bg-green-100 text-green-700 border-green-200',
                                    'cancelled' => 'bg-red-100 text-red-600 border-red-200',
                                    default => 'bg-slate-100 text-slate-600 border-slate-200',
                                };
                            @endphp
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $statusColor }}">
                                {{ $outgoingPo->status }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if($outgoingPo->status === 'draft')
                        <form method="POST" action="{{ route('outgoing.customer-po.confirm', $outgoingPo) }}" class="inline">
                            @csrf
                            <button class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-700">
                                ✓ Confirm
                            </button>
                        </form>
                        <form method="POST" action="{{ route('outgoing.customer-po.cancel', $outgoingPo) }}" class="inline"
                            onsubmit="return confirm('Cancel this PO?')">
                            @csrf
                            <button
                                class="rounded-xl border border-red-300 bg-white px-4 py-2 text-xs font-bold text-red-600 hover:bg-red-50">
                                ✕ Cancel
                            </button>
                        </form>
                    @elseif($outgoingPo->status === 'confirmed')
                        <form method="POST" action="{{ route('outgoing.customer-po.complete', $outgoingPo) }}" class="inline">
                            @csrf
                            <button class="rounded-xl bg-green-600 px-4 py-2 text-xs font-bold text-white hover:bg-green-700">
                                ✓ Complete
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('outgoing.customer-po.index') }}"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                        ← Back
                    </a>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <div class="text-2xl font-black text-slate-900">{{ $outgoingPo->items->count() }}</div>
                <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mt-1">Total Items</div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <div class="text-2xl font-black text-indigo-700">{{ number_format($outgoingPo->total_qty) }}</div>
                <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mt-1">Total Qty</div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <div class="text-2xl font-black text-emerald-700">{{ number_format($outgoingPo->total_amount, 0) }}</div>
                <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mt-1">Total Amount</div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
                <div class="text-2xl font-black text-slate-700">{{ $outgoingPo->creator->name ?? '-' }}</div>
                <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mt-1">Created By</div>
            </div>
        </div>

        @if($outgoingPo->notes)
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                <span class="font-bold">Notes:</span> {{ $outgoingPo->notes }}
            </div>
        @endif

        {{-- Items Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100">
                <div class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    PO Items
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th
                                class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500 w-12">
                                No</th>
                            <th
                                class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-amber-700 bg-amber-50">
                                Vendor Part Name</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Part Name</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Model</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Part No</th>
                            <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Qty</th>
                            <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Harga PO</th>
                            <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Subtotal</th>
                            <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                Delivery Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($outgoingPo->items as $idx => $item)
                            <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                                <td class="px-4 py-3 text-center text-slate-500 font-semibold">{{ $idx + 1 }}</td>
                                <td class="px-4 py-3 font-bold text-amber-800 bg-amber-50/30">{{ $item->vendor_part_name }}</td>
                                <td class="px-4 py-3 text-slate-900 font-semibold">{{ $item->part->part_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $item->part->model ?? '-' }}</td>
                                <td class="px-4 py-3 text-indigo-700 font-mono font-bold">{{ $item->part->part_no ?? '-' }}</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-900">{{ number_format($item->qty) }}</td>
                                <td class="px-4 py-3 text-right text-slate-700 font-semibold">
                                    {{ number_format($item->price, 0) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-emerald-700">
                                    {{ number_format($item->subtotal, 0) }}</td>
                                <td class="px-4 py-3 text-center text-slate-600">
                                    {{ $item->delivery_date ? $item->delivery_date->format('d M Y') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50 border-t-2 border-slate-300">
                            <td colspan="5" class="px-4 py-3 text-right font-black text-slate-700 text-xs uppercase">Grand
                                Total</td>
                            <td class="px-4 py-3 text-right font-black text-slate-900">
                                {{ number_format($outgoingPo->total_qty) }}</td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3 text-right font-black text-emerald-700">
                                {{ number_format($outgoingPo->total_amount, 0) }}</td>
                            <td class="px-4 py-3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection