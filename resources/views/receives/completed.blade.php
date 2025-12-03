<x-app-layout>
    <x-slot name="header">
        Completed Receives & Summary
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-gradient-to-br from-blue-50 to-white border border-slate-200 rounded-2xl shadow-lg shadow-blue-500/10 p-5">
                    <div class="text-xs uppercase tracking-wider text-slate-600 font-semibold">Total Receives</div>
                    <div class="mt-2 text-3xl font-bold text-blue-600">{{ number_format($summary['total_receives']) }}</div>
                    <div class="text-xs text-slate-500 mt-1">All time records</div>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-white border border-slate-200 rounded-2xl shadow-lg shadow-green-500/10 p-5">
                    <div class="text-xs uppercase tracking-wider text-slate-600 font-semibold">Total Qty</div>
                    <div class="mt-2 text-3xl font-bold text-green-600">{{ number_format($summary['total_qty']) }}</div>
                    <div class="text-xs text-slate-500 mt-1">Units received</div>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-white border border-slate-200 rounded-2xl shadow-lg shadow-purple-500/10 p-5">
                    <div class="text-xs uppercase tracking-wider text-slate-600 font-semibold">Total Weight</div>
                    <div class="mt-2 text-3xl font-bold text-purple-600">{{ number_format($summary['total_weight'] ?? 0, 2) }} kg</div>
                    <div class="text-xs text-slate-500 mt-1">Captured weight</div>
                </div>
                <div class="bg-gradient-to-br from-orange-50 to-white border border-slate-200 rounded-2xl shadow-lg shadow-orange-500/10 p-5">
                    <div class="text-xs uppercase tracking-wider text-slate-600 font-semibold">Received Today</div>
                    <div class="mt-2 text-3xl font-bold text-orange-600">{{ number_format($summary['today']) }}</div>
                    <div class="text-xs text-slate-500 mt-1">Fresh entries</div>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-4">
                <div class="bg-white border border-slate-200 rounded-2xl shadow-lg p-6 lg:col-span-2">
                    <div class="flex items-center justify-between mb-4 pb-4 border-b border-slate-200">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">QC Breakdown</h3>
                            <p class="text-sm text-slate-600">Distribution of inspection results.</p>
                        </div>
                        <a href="{{ route('arrivals.index') }}" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">Go to Arrivals</a>
                    </div>
                    <div class="grid sm:grid-cols-3 gap-4">
                        @php
                            $statuses = ['pass' => 'Pass', 'fail' => 'Fail', 'hold' => 'Hold'];
                            $colors = ['pass' => 'from-green-50 to-white border-green-200 text-green-600', 'fail' => 'from-red-50 to-white border-red-200 text-red-600', 'hold' => 'from-amber-50 to-white border-amber-200 text-amber-600'];
                        @endphp
                        @foreach ($statuses as $key => $label)
                            <div class="bg-gradient-to-br {{ $colors[$key] }} border rounded-xl p-4 shadow-sm">
                                <div class="text-xs uppercase tracking-wider text-slate-600 font-semibold">{{ $label }}</div>
                                <div class="text-3xl font-bold mt-2">{{ $statusCounts[$key] ?? 0 }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl shadow-lg p-6">
                    <div class="pb-4 border-b border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900">Top Vendors</h3>
                        <p class="text-sm text-slate-600">Highest receive counts.</p>
                    </div>
                    <div class="space-y-3 mt-4">
                        @forelse ($topVendors as $vendor)
                            <div class="flex items-center justify-between text-sm p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ $vendor->vendor_name }}</div>
                                    <div class="text-slate-600 text-xs">{{ number_format($vendor->total_qty ?? 0) }} units</div>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-lg bg-blue-100 text-blue-700 text-xs font-semibold">
                                    {{ number_format($vendor->total_receives) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 text-center py-4">No vendors yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-5 pb-4 border-b border-slate-200">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Receive Records</h3>
                        <p class="text-sm text-slate-600">Latest receipts with QC status.</p>
                    </div>
                    <div class="text-sm text-slate-500">
                        Receive items from the Arrival detail page.
                    </div>
                </div>
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Tag</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Part</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Vendor</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Qty</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Location</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">QC</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">ATA</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($receives as $receive)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-4 font-semibold text-slate-900">{{ $receive->tag }}</td>
                                    <td class="px-4 py-4 text-slate-800">
                                        {{ $receive->arrivalItem->part->part_no }}
                                        <div class="text-xs text-slate-500">{{ $receive->arrivalItem->part->part_name_vendor }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">
                                        {{ $receive->arrivalItem->arrival->vendor->vendor_name ?? '-' }}
                                        <div class="text-xs text-slate-500">{{ $receive->arrivalItem->arrival->arrival_no }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-800 font-semibold">{{ number_format($receive->qty) }}</td>
                                    <td class="px-4 py-4 text-slate-700 font-mono text-xs">{{ $receive->location_code ?? '-' }}</td>
                                    <td class="px-4 py-4">
                                        @php
                                            $statusColor = match ($receive->qc_status) {
                                                'pass' => 'bg-green-100 text-green-700',
                                                'fail' => 'bg-red-100 text-red-700',
                                                default => 'bg-amber-100 text-amber-700',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusColor }}">
                                            {{ ucfirst($receive->qc_status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">{{ $receive->ata_date?->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-4 text-sm">
                                        <a href="{{ route('receives.label', $receive) }}" target="_blank" class="text-blue-600 hover:text-blue-700 font-medium">Print label</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-slate-500">No receive records yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $receives->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
