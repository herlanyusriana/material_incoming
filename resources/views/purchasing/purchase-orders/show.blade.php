<x-app-layout>
    <x-slot name="header">
        Purchasing • PO Detail
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center justify-between">
                <a href="{{ route('purchasing.purchase-orders.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors uppercase tracking-widest">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to List
                </a>

                <div class="flex items-center gap-3">
                    <a href="{{ route('purchasing.purchase-orders.print', $purchaseOrder) }}" target="_blank" class="px-6 py-2.5 rounded-2xl bg-white border-2 border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all uppercase text-xs tracking-wider flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print PO
                    </a>

                    @if ($purchaseOrder->status === 'Pending')
                        <form action="{{ route('purchasing.purchase-orders.approve', $purchaseOrder) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-6 py-2.5 rounded-2xl bg-emerald-600 text-white font-bold hover:bg-emerald-700 transition-all shadow-lg uppercase text-xs tracking-wider flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Approve PO
                            </button>
                        </form>
                    @endif

                    @if ($purchaseOrder->status === 'Approved')
                        <form action="{{ route('purchasing.purchase-orders.release', $purchaseOrder) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-6 py-2.5 rounded-2xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition-all shadow-lg uppercase text-xs tracking-wider flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                                Release to Vendor
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="bg-white shadow-xl border border-slate-200 rounded-3xl overflow-hidden">
                <div class="p-8 border-b border-slate-100 bg-slate-50/30">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="md:col-span-2 space-y-4">
                            <div class="flex items-center gap-3">
                                <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ $purchaseOrder->po_number }}</h2>
                                @php
                                    $statusClasses = [
                                        'Pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                        'Approved' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                        'Released' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                        'Rejected' => 'bg-rose-100 text-rose-700 border-rose-200',
                                        'Closed' => 'bg-slate-100 text-slate-700 border-slate-200',
                                    ];
                                    $currentClass = $statusClasses[$purchaseOrder->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                @endphp
                                <span class="px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest border-2 {{ $currentClass }}">
                                    {{ $purchaseOrder->status }}
                                </span>
                            </div>
                            
                            <div class="flex flex-wrap gap-8 pt-2">
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Vendor</span>
                                    <div class="text-sm font-bold text-slate-900 italic underline decoration-indigo-200">{{ $purchaseOrder->vendor?->vendor_name }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">{{ $purchaseOrder->vendor?->vendor_code }}</div>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Created At</span>
                                    <div class="text-sm font-bold text-slate-700">{{ $purchaseOrder->created_at->format('M d, Y') }}</div>
                                </div>
                                @if ($purchaseOrder->released_at)
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Released At</span>
                                        <div class="text-sm font-bold text-emerald-600">{{ $purchaseOrder->released_at->format('M d, Y H:i') }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="bg-slate-900 p-6 rounded-2xl shadow-xl flex flex-col items-center justify-center text-center">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total PO Value</span>
                            <span class="text-3xl font-black text-white font-mono">{{ number_format($purchaseOrder->total_amount, 2) }}</span>
                            <span class="text-[10px] text-slate-500 mt-2 font-medium italic">Incl. all items & taxes if any</span>
                        </div>
                    </div>
                </div>

                <div class="p-8">
                    <div class="space-y-8">
                        <div>
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                                <span class="h-px w-8 bg-slate-200"></span>
                                Order Particulars
                            </h3>
                            <div class="overflow-hidden border border-slate-200 rounded-2xl">
                                <table class="min-w-full divide-y divide-slate-200 font-mono">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-black text-slate-500 uppercase tracking-widest">Item Description</th>
                                            <th class="px-6 py-4 text-right text-xs font-black text-slate-500 uppercase tracking-widest">Qty</th>
                                            <th class="px-6 py-4 text-right text-xs font-black text-slate-500 uppercase tracking-widest">Unit Price</th>
                                            <th class="px-6 py-4 text-right text-xs font-black text-slate-500 uppercase tracking-widest">Line Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-100">
                                        @foreach ($purchaseOrder->items as $item)
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="px-6 py-4 text-sm">
                                                    <div class="font-bold text-slate-900">{{ $item->part?->part_no }}</div>
                                                    <div class="text-[10px] text-slate-500 font-sans tracking-tight">{{ $item->part?->part_name }}</div>
                                                    @if ($item->vendorPart)
                                                        <div class="text-[10px] text-indigo-600 font-mono mt-1">Vendor Part: {{ $item->vendorPart->part_no }}</div>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-indigo-600">
                                                    {{ number_format($item->qty, 4) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-slate-600">
                                                    {{ number_format($item->unit_price, 2) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-slate-900">
                                                    {{ number_format($item->subtotal, 2) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-slate-50 text-sm font-black">
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-right text-slate-500 uppercase tracking-widest text-xs">Grand Total</td>
                                            <td class="px-6 py-4 text-right text-slate-900 text-lg">{{ number_format($purchaseOrder->total_amount, 2) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-3 flex items-center gap-2">
                                    <span class="h-px w-8 bg-slate-200"></span>
                                    Administrative Info
                                </h3>
                                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200 space-y-3">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-slate-500 font-bold uppercase tracking-wider">Approved By</span>
                                        <span class="text-slate-900 font-black">{{ $purchaseOrder->approvedBy?->name ?? '—' }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs">
                                        <span class="text-slate-500 font-bold uppercase tracking-wider">Approval Date</span>
                                        <span class="text-slate-900 font-black">{{ $purchaseOrder->approved_at ? $purchaseOrder->approved_at->format('M d, Y H:i') : '—' }}</span>
                                    </div>
                                    <div class="h-px bg-slate-200 my-2"></div>
                                    <div class="flex justify-between text-xs">
                                        <span class="text-slate-500 font-bold uppercase tracking-wider">Released By</span>
                                        <span class="text-slate-900 font-black">{{ $purchaseOrder->releasedBy?->name ?? '—' }}</span>
                                    </div>
                                </div>
                            </div>
                            @if ($purchaseOrder->notes)
                                <div>
                                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-3 flex items-center gap-2">
                                        <span class="h-px w-8 bg-slate-200"></span>
                                        Order Notes
                                    </h3>
                                    <div class="bg-indigo-50/50 p-6 rounded-2xl border border-indigo-100 text-slate-700 text-sm italic leading-relaxed">
                                        {{ $purchaseOrder->notes }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
