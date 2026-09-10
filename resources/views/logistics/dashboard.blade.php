<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">
                {{ __('dashboard.logistics_title') }}
            </h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('receives.index') }}" class="px-3 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">{{ __('dashboard.receiving') }}</a>
                <a href="{{ route('warehouse.qc.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold hover:bg-slate-50">{{ __('dashboard.qc_queue') }}</a>
                <a href="{{ route('warehouse.putaway.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold hover:bg-slate-50">{{ __('dashboard.putaway_queue') }}</a>
                <a href="{{ route('inventory.locations.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold hover:bg-slate-50">{{ __('dashboard.locations') }}</a>
                <a href="{{ route('warehouse.stock.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold hover:bg-slate-50">{{ __('dashboard.stock_by_location') }}</a>
                <a href="{{ route('warehouse.bin-transfers.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold hover:bg-slate-50">{{ __('dashboard.bin_to_bin') }}</a>
                <a href="{{ route('warehouse.batch-transfers.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold hover:bg-slate-50">{{ __('dashboard.batch_to_batch') }}</a>
                <a href="{{ route('warehouse.stock-adjustments.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold hover:bg-slate-50">{{ __('dashboard.adjustments') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @php
                    $qcPass = (int) ($qcCounts['pass'] ?? 0);
                    $qcReject = (int) ($qcCounts['reject'] ?? $qcCounts['fail'] ?? 0);
                    $qcHold = (int) ($qcCounts['hold'] ?? 0);
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">{{ __('dashboard.pending_inbound') }}</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900 tabular-nums">{{ $pendingArrivals->count() }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ __('dashboard.pending_inbound_desc') }}</div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">{{ __('dashboard.qc_pass') }}</div>
                    <div class="mt-2 text-2xl font-bold text-emerald-700 tabular-nums">{{ $qcPass }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ __('dashboard.qc_pass_desc') }}</div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">{{ __('dashboard.qc_hold') }}</div>
                    <div class="mt-2 text-2xl font-bold text-amber-700 tabular-nums">{{ $qcHold }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ __('dashboard.qc_hold_desc') }}</div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4">
                    <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">{{ __('dashboard.qc_reject') }}</div>
                    <div class="mt-2 text-2xl font-bold text-rose-700 tabular-nums">{{ $qcReject }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ __('dashboard.qc_reject_desc') }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div class="text-sm font-semibold text-slate-900">{{ __('dashboard.pending_top') }}</div>
                        <a href="{{ route('receives.index') }}" class="text-xs font-semibold text-indigo-700 hover:text-indigo-900">{{ __('dashboard.open_receiving') }}</a>
                    </div>
                    <div class="p-5">
                        @if($pendingArrivals->isEmpty())
                            <div class="text-sm text-slate-600">{{ __('dashboard.no_pending') }}</div>
                        @else
                            <div class="space-y-3">
                                @foreach($pendingArrivals as $arrival)
                                    <div class="flex items-start justify-between gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">{{ $arrival->arrival_no }}</div>
                                            <div class="text-xs text-slate-500">
                                                Vendor: {{ $arrival->vendor?->vendor_name ?? '-' }} • Status: {{ $arrival->status }}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-xs text-slate-500">{{ __('dashboard.remaining') }}</div>
                                            <div class="text-sm font-bold text-slate-900 tabular-nums">{{ number_format((float) $arrival->remaining_qty, 3) }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div class="text-sm font-semibold text-slate-900">{{ __('dashboard.top_locations') }}</div>
                        <a href="{{ route('warehouse.stock.index') }}" class="text-xs font-semibold text-indigo-700 hover:text-indigo-900">{{ __('dashboard.open_stock') }}</a>
                    </div>
                    <div class="p-5">
                        @if($topLocations->isEmpty())
                            <div class="text-sm text-slate-600">{{ __('dashboard.no_stock') }}</div>
                        @else
                            <div class="space-y-2">
                                @foreach($topLocations as $row)
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm font-semibold text-slate-800">{{ $row->location_code }}</div>
                                        <div class="text-sm font-bold text-slate-900 tabular-nums">{{ number_format((float) $row->total_qty, 4) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div class="text-sm font-semibold text-slate-900">{{ __('dashboard.recent_receives') }}</div>
                        <a href="{{ route('inventory.receives') }}" class="text-xs font-semibold text-indigo-700 hover:text-indigo-900">{{ __('dashboard.open') }}</a>
                    </div>
                    <div class="p-5 space-y-3">
                        @forelse($recentReceives as $r)
                            <div class="text-sm">
                                <div class="flex items-center justify-between">
                                    <div class="font-semibold text-slate-900">{{ $r->arrivalItem?->part?->part_no ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $r->created_at?->format('Y-m-d H:i') }}</div>
                                </div>
                                <div class="text-xs text-slate-500">
                                    Qty: <span class="tabular-nums">{{ $r->qty }}</span> • QC: {{ $r->qc_status }} • Invoice: {{ $r->arrivalItem?->arrival?->arrival_no ?? '-' }}
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-slate-600">{{ __('dashboard.no_receives') }}</div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                        <div class="text-sm font-semibold text-slate-900">{{ __('dashboard.recent_bin') }}</div>
                        <a href="{{ route('warehouse.bin-transfers.index') }}" class="text-xs font-semibold text-indigo-700 hover:text-indigo-900">{{ __('dashboard.open') }}</a>
                    </div>
                    <div class="p-5 space-y-3">
                        @forelse($recentBinTransfers as $t)
                            <div class="text-sm">
                                <div class="flex items-center justify-between">
                                    <div class="font-semibold text-slate-900">{{ $t->part?->part_no ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $t->created_at?->format('Y-m-d H:i') }}</div>
                                </div>
                                <div class="text-xs text-slate-500">
                                    {{ $t->from_location_code ?? '-' }} → {{ $t->to_location_code ?? '-' }} • Qty: <span class="tabular-nums">{{ number_format((float) $t->qty, 4) }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-slate-600">{{ __('dashboard.no_bin') }}</div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
