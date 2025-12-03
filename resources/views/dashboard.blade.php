<x-app-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Summary Cards -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-gradient-to-br from-blue-50 to-white border border-slate-200 rounded-2xl shadow-lg shadow-blue-500/10 p-5 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-slate-600 font-semibold">Total Arrivals</div>
                            <div class="mt-2 text-3xl font-bold text-blue-600">{{ number_format($summary['total_arrivals']) }}</div>
                            <div class="text-xs text-slate-500 mt-1">All shipments</div>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-white border border-slate-200 rounded-2xl shadow-lg shadow-green-500/10 p-5 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-slate-600 font-semibold">Total Receives</div>
                            <div class="mt-2 text-3xl font-bold text-green-600">{{ number_format($summary['total_receives']) }}</div>
                            <div class="text-xs text-slate-500 mt-1">Processed items</div>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg shadow-green-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-orange-50 to-white border border-slate-200 rounded-2xl shadow-lg shadow-orange-500/10 p-5 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-slate-600 font-semibold">Pending Items</div>
                            <div class="mt-2 text-3xl font-bold text-orange-600">{{ number_format($summary['pending_items']) }}</div>
                            <div class="text-xs text-slate-500 mt-1">Need processing</div>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-purple-50 to-white border border-slate-200 rounded-2xl shadow-lg shadow-purple-500/10 p-5 hover:shadow-xl transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-slate-600 font-semibold">Today Receives</div>
                            <div class="mt-2 text-3xl font-bold text-purple-600">{{ number_format($summary['today_receives']) }}</div>
                            <div class="text-xs text-slate-500 mt-1">Processed today</div>
                        </div>
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-purple-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid lg:grid-cols-3 gap-4">
                <!-- QC Status -->
                <div class="bg-white border border-slate-200 rounded-2xl shadow-lg p-6">
                    <div class="pb-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">QC Status</h3>
                        <p class="text-sm text-slate-600">Recent quality checks</p>
                    </div>
                    <div class="mt-4 space-y-3">
                        @php
                            $statuses = ['pass' => 'Pass', 'fail' => 'Fail', 'hold' => 'Hold'];
                            $colors = ['pass' => 'bg-green-100 text-green-700', 'fail' => 'bg-red-100 text-red-700', 'hold' => 'bg-amber-100 text-amber-700'];
                        @endphp
                        @foreach ($statuses as $key => $label)
                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                                <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
                                <span class="px-3 py-1 text-sm font-bold rounded-full {{ $colors[$key] }}">
                                    {{ $statusCounts[$key] ?? 0 }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Recent Receives -->
                <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Recent Receives</h3>
                            <p class="text-sm text-slate-600">Latest 5 processed items</p>
                        </div>
                        <a href="{{ route('receives.completed') }}" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors">
                            View All
                        </a>
                    </div>
                    <div class="mt-4 space-y-2">
                        @forelse ($recentReceives as $receive)
                            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                                <div class="flex-1">
                                    <div class="font-semibold text-slate-900 text-sm">{{ $receive->tag }}</div>
                                    <div class="text-xs text-slate-600">{{ $receive->arrivalItem->part->part_no }} - {{ $receive->arrivalItem->arrival->vendor->vendor_name }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-slate-900">{{ number_format($receive->qty) }}</div>
                                    @php
                                        $qcColor = match ($receive->qc_status) {
                                            'pass' => 'bg-green-100 text-green-700',
                                            'fail' => 'bg-red-100 text-red-700',
                                            default => 'bg-amber-100 text-amber-700',
                                        };
                                    @endphp
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $qcColor }}">
                                        {{ ucfirst($receive->qc_status) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 text-center py-8">No receives yet</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Arrivals List -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between pb-4 border-b border-slate-200">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Arrival Records</h3>
                        <p class="text-sm text-slate-600">Inbound shipments with pricing breakdowns</p>
                    </div>
                    <a href="{{ route('arrivals.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        New Arrival
                    </a>
                </div>

                <div class="mt-5 space-y-4">
                    @forelse ($arrivals as $arrival)
                        @php
                            $totalItems = $arrival->items->count();
                            $totalValue = $arrival->items->sum('total_price');
                            $totalQty = $arrival->items->sum('qty_goods');
                            $totalReceived = $arrival->items->sum(function($item) {
                                return $item->receives->sum('qty');
                            });
                            $progress = $totalQty > 0 ? round(($totalReceived / $totalQty) * 100) : 0;
                        @endphp
                        <div class="border border-slate-200 rounded-xl p-5 hover:shadow-md transition-shadow bg-gradient-to-r from-white to-slate-50">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h4 class="text-lg font-bold text-slate-900">{{ $arrival->arrival_no }}</h4>
                                        <span class="text-xs text-slate-500">by {{ $arrival->creator->name ?? 'System' }}</span>
                                    </div>
                                    <div class="grid md:grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <span class="text-slate-600">Vendor:</span>
                                            <span class="font-semibold text-slate-900">{{ $arrival->vendor->vendor_name }}</span>
                                        </div>
                                        <div>
                                            <span class="text-slate-600">Invoice:</span>
                                            <span class="font-medium text-slate-900">{{ $arrival->invoice_no }}</span>
                                        </div>
                                        <div>
                                            <span class="text-slate-600">Items:</span>
                                            <span class="font-medium text-slate-900">{{ $totalItems }} item{{ $totalItems != 1 ? 's' : '' }}</span>
                                            <span class="text-slate-500">({{ number_format($totalQty) }} pcs total)</span>
                                        </div>
                                        <div>
                                            <span class="text-slate-600">Total Value:</span>
                                            <span class="font-bold text-blue-600">{{ $arrival->currency }} {{ number_format($totalValue, 2) }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    <div class="mt-3">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="font-semibold text-slate-700">Received Progress</span>
                                            <span class="font-bold text-blue-600">{{ $totalReceived }} / {{ number_format($totalQty) }}</span>
                                        </div>
                                        <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $progress == 100 ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ $progress }}%"></div>
                                        </div>
                                        <div class="text-xs text-slate-600 mt-1">{{ $progress }}% complete</div>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <span class="text-xs text-slate-500">{{ $arrival->invoice_date->format('d M Y') }}</span>
                                    <a href="{{ route('arrivals.show', $arrival) }}" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors text-center">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <div class="text-slate-400 mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900 mb-2">No Arrivals Yet</h3>
                            <p class="text-sm text-slate-600 mb-4">Start by creating your first arrival record.</p>
                        </div>
                    @endforelse
                </div>

                @if($arrivals->hasPages())
                    <div class="mt-6 pt-4 border-t border-slate-200">
                        {{ $arrivals->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
