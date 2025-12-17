<x-app-layout>
    <x-slot name="header">
        Completed Receives — {{ $arrival->invoice_no }}
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-slate-600">{{ $arrival->vendor->vendor_name ?? '-' }} • {{ $arrival->arrival_no }}</div>
                    <h3 class="text-lg font-bold text-slate-900">Receive Records</h3>
                </div>
                <a href="{{ route('receives.completed') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-lg transition-colors">
                    Back
                </a>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Tag</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-600 text-xs uppercase tracking-wider">Part</th>
                                <th class="px-4 py-3 text-right font-semibold text-slate-600 text-xs uppercase tracking-wider">Qty</th>
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
                                    <td class="px-4 py-4 text-right text-slate-800 font-semibold">{{ number_format($receive->qty) }}</td>
                                    <td class="px-4 py-4">
                                        @php
                                            $statusColor = match ($receive->qc_status) {
                                                'pass' => 'bg-green-100 text-green-700',
                                                'fail', 'reject' => 'bg-red-100 text-red-700',
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
                                    <td colspan="6" class="px-4 py-12 text-center text-slate-500">No receive records.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                {{ $receives->links() }}
            </div>
        </div>
    </div>
</x-app-layout>

