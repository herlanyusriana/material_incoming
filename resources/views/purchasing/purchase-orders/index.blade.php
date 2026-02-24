<x-app-layout>
    <x-slot name="header">
        Purchasing • Purchase Orders
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div
                    class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 flex items-center gap-3 shadow-sm">
                    <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div
                    class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800 flex items-center gap-3 shadow-sm">
                    <svg class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-xl border border-slate-200 rounded-3xl overflow-hidden">
                <div
                    class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4 bg-slate-50/50">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Purchase Order List</h2>
                        <p class="text-sm text-slate-500 mt-1">Official purchase orders sent to vendors.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">
                                    PO Number</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">
                                    Vendor</th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">
                                    Total Amount</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-widest">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">
                                    Release Date</th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            @forelse ($orders as $order)
                                <tr class="hover:bg-slate-50/80 transition-colors group">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-indigo-600 group-hover:text-indigo-700">
                                            {{ $order->po_number }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $order->items->count() }} items
                                            included</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-slate-700">{{ $order->vendor?->vendor_name }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $order->vendor?->vendor_code }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm font-bold text-slate-900">
                                            {{ number_format($order->total_amount, 2) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @php
                                            $statusClasses = [
                                                'Pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                'Approved' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                                'Released' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                                'Rejected' => 'bg-rose-100 text-rose-700 border-rose-200',
                                                'Closed' => 'bg-slate-100 text-slate-700 border-slate-200',
                                                'Cancelled' => 'bg-slate-100 text-slate-700 border-slate-200',
                                            ];
                                            $currentClass = $statusClasses[$order->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                        @endphp
                                        <span
                                            class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border {{ $currentClass }}">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                        {{ $order->released_at ? $order->released_at->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('purchasing.purchase-orders.show', $order) }}"
                                                class="p-2 rounded-xl text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all"
                                                title="View Detail">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('purchasing.purchase-orders.print', $order) }}"
                                                target="_blank"
                                                class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all"
                                                title="Print PO">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="h-12 w-12 text-slate-200" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            <span class="font-semibold">No purchase orders found.</span>
                                            <p class="text-xs">Convert an approved Purchase Request to get started.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($orders->hasPages())
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>