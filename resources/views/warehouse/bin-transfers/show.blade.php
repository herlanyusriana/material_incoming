<x-app-layout>
    <x-slot name="header">
        Warehouse • Bin Transfer Details
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-slate-900">Transfer #{{ $binTransfer->id }}</h2>
                    <div class="flex gap-3">
                        <a href="{{ route('warehouse.bin-transfers.label', $binTransfer) }}" target="_blank"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors">
                            🖨️ Print Label
                        </a>
                        <a href="{{ route('warehouse.bin-transfers.index') }}"
                            class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            ← Back to List
                        </a>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Transfer Info --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-500 uppercase mb-3">Transfer Information</h3>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-xs text-slate-500">Transfer Date</dt>
                                    <dd class="text-sm font-semibold text-slate-900">
                                        {{ $binTransfer->transfer_date->format('Y-m-d') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Created At</dt>
                                    <dd class="text-sm text-slate-700">
                                        {{ $binTransfer->created_at->format('Y-m-d H:i:s') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Created By</dt>
                                    <dd class="text-sm text-slate-700">{{ $binTransfer->creator->name ?? '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Status</dt>
                                    <dd>
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            {{ $binTransfer->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                            {{ ucfirst($binTransfer->status) }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-slate-500 uppercase mb-3">Part Details</h3>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-xs text-slate-500">Part Number</dt>
                                    <dd class="text-sm font-semibold text-slate-900">{{ $binTransfer->part->part_no }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Part Name</dt>
                                    <dd class="text-sm text-slate-700">{{ $binTransfer->part->part_name_gci }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-slate-500">Quantity Transferred</dt>
                                    <dd class="text-lg font-bold text-indigo-600">{{ formatNumber($binTransfer->qty) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {{-- Movement Visualization --}}
                    <div class="border-t border-slate-200 pt-6">
                        <h3 class="text-sm font-semibold text-slate-500 uppercase mb-4">Movement</h3>
                        <div class="flex items-center justify-between">
                            {{-- From Location --}}
                            <div class="flex-1 text-center">
                                <div
                                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mb-3">
                                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div class="text-xs text-slate-500 mb-1">From</div>
                                <div class="text-lg font-bold text-slate-900">{{ $binTransfer->from_location_code }}
                                </div>
                                <div class="text-xs text-slate-500 mt-2">Current Stock:
                                    {{ formatNumber($currentFromStock) }}
                                </div>
                            </div>

                            {{-- Arrow --}}
                            <div class="flex-shrink-0 px-8">
                                <svg class="w-12 h-12 text-slate-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </div>

                            {{-- To Location --}}
                            <div class="flex-1 text-center">
                                <div
                                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-3">
                                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div class="text-xs text-slate-500 mb-1">To</div>
                                <div class="text-lg font-bold text-slate-900">{{ $binTransfer->to_location_code }}</div>
                                <div class="text-xs text-slate-500 mt-2">Current Stock:
                                    {{ formatNumber($currentToStock) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Notes --}}
                    @if($binTransfer->notes)
                        <div class="border-t border-slate-200 pt-6">
                            <h3 class="text-sm font-semibold text-slate-500 uppercase mb-2">Notes</h3>
                            <p class="text-sm text-slate-700 bg-slate-50 rounded-lg p-4">{{ $binTransfer->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>