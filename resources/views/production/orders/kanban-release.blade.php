@extends('layouts.app')

@section('header')
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <nav class="mb-2 flex items-center gap-2 text-xs font-semibold text-slate-500">
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600">Dashboard</a>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <a href="{{ route('production.orders.index') }}" class="hover:text-indigo-600">Production</a>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-900">Release Kanban</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Release Kanban</h1>
            <p class="mt-1 text-sm text-slate-500">Daftar Work Order (PLANNED) yang siap dirilis ke lantai produksi.</p>
        </div>
    </div>
@endsection

@section('content')
    <div class="space-y-6">
        {{-- Filter & Actions Card --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <form action="{{ route('production.kanban-release.index') }}" method="GET" class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="flex-1 max-w-md">
                    <label for="q" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Search WO / Part</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="q" id="q" value="{{ $q }}"
                            class="block w-full rounded-xl border-slate-200 pl-10 text-sm transition-all focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10"
                            placeholder="Ketik No WO atau Part...">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all hover:bg-slate-800">
                        Filter
                    </button>
                    @if($q)
                        <a href="{{ route('production.kanban-release.index') }}" class="text-sm font-semibold text-slate-500 hover:text-indigo-600">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Bulk Action & Results --}}
        <form action="{{ route('production.kanban-release.bulk') }}" method="POST" id="bulk-form">
            @csrf
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50/50 px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800">Planned Work Orders</h3>
                    </div>
                    <div id="selection-actions" class="hidden animate-in fade-in slide-in-from-right-4 duration-300">
                        <button type="button" onclick="confirmBulkRelease()"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-sm font-bold text-white shadow-md shadow-indigo-200 transition-all hover:scale-[1.02] active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Release Selected (<span id="selected-count">0</span>)
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                            <tr>
                                <th class="w-12 px-5 py-4 text-center">
                                    <input type="checkbox" id="select-all" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 shadow-sm">
                                </th>
                                <th class="px-5 py-4">Work Order</th>
                                <th class="px-5 py-4">Part Details</th>
                                <th class="px-5 py-4">Machine / Process</th>
                                <th class="px-5 py-4 text-center">Plan Date</th>
                                <th class="px-5 py-4 text-right">Qty Planned</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 italic-last-row">
                            @forelse($orders as $order)
                                <tr class="hover:bg-slate-50/80 transition-colors group">
                                    <td class="px-5 py-4 text-center">
                                        <input type="checkbox" name="ids[]" value="{{ $order->id }}" class="order-checkbox h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 shadow-sm transition-all group-hover:scale-110">
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-mono text-sm font-bold text-slate-900 tracking-tight">{{ $order->production_order_number }}</span>
                                            <span class="text-[10px] uppercase font-bold text-slate-400 mt-0.5 tracking-wider">{{ $order->transaction_no }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-indigo-600 leading-tight">{{ $order->part?->part_no }}</span>
                                            <span class="text-xs text-slate-500 mt-0.5 line-clamp-1 max-w-[200px]">{{ $order->part?->part_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-1.5 focus:outline-none">
                                                <div class="h-1.5 w-1.5 rounded-full bg-slate-300 group-hover:bg-indigo-400 transition-colors"></div>
                                                <span class="font-semibold text-slate-700">{{ $order->machine?->name ?? 'No Machine' }}</span>
                                            </div>
                                            <span class="text-xs text-slate-400 font-medium pl-3 italic">{{ $order->process_name ?: 'No Process' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="inline-flex flex-col items-center rounded-lg bg-slate-100 px-2 py-1 min-w-[70px]">
                                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter leading-none mb-0.5">{{ \Carbon\Carbon::parse($order->plan_date)->format('M') }}</span>
                                            <span class="text-sm font-black text-slate-800 leading-none">{{ \Carbon\Carbon::parse($order->plan_date)->format('d') }}</span>
                                            <span class="text-[9px] font-bold text-slate-400 leading-none mt-0.5">{{ \Carbon\Carbon::parse($order->plan_date)->format('Y') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <span class="font-mono font-black text-slate-900">{{ number_format($order->qty_planned) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="rounded-full bg-slate-50 p-4 border border-slate-100 transform transition-transform group-hover:scale-110">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                                                </svg>
                                            </div>
                                            <h3 class="mt-4 text-sm font-bold text-slate-800">Tidak ada WO Planned</h3>
                                            <p class="mt-1 text-xs text-slate-500">Semua Work Order telah dirilis atau belum dibuat.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($orders->hasPages())
                    <div class="border-t border-slate-200 bg-slate-50/50 px-5 py-4">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.order-checkbox');
            const selectionActions = document.getElementById('selection-actions');
            const selectedCountDisplay = document.getElementById('selected-count');

            function updateSelectionState() {
                const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
                selectedCountDisplay.textContent = checkedCount;

                if (checkedCount > 0) {
                    selectionActions.classList.remove('hidden');
                } else {
                    selectionActions.classList.add('hidden');
                }

                selectAll.checked = checkedCount === checkboxes.length && checkboxes.length > 0;
            }

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                    updateSelectionState();
                });
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateSelectionState);
            });
        });

        function confirmBulkRelease() {
            const count = document.querySelectorAll('.order-checkbox:checked').length;
            if (confirm(`Apakah Anda yakin ingin merilis ${count} Work Order yang dipilih ke Kanban?`)) {
                document.getElementById('bulk-form').submit();
            }
        }
    </script>
    @endpush
@endsection
