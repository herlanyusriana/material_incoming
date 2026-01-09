<x-app-layout>
    <x-slot name="header">
        Planning • Customer Planning Import
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="text-sm text-slate-600">Upload customer planning Excel. Week format: YYYY-WW (ISO week).</div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('planning.planning-imports.template') }}" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Template Weekly</a>
                        <a href="{{ route('planning.planning-imports.template-monthly') }}" class="px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 font-semibold">Template Monthly</a>
                        @if ($importId)
                            <a href="{{ route('planning.planning-imports.export', $importId) }}" class="px-4 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 font-semibold">Export Rows</a>
                        @endif
                    </div>
                </div>

                <form action="{{ route('planning.planning-imports.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Customer</label>
                        <select name="customer_id" class="mt-1 rounded-xl border-slate-200" required>
                            <option value="" disabled selected>Select customer</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">File</label>
                        <input type="file" name="file" class="mt-1 rounded-xl border-slate-200" required>
                    </div>
                    <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Upload</button>
                </form>
            </div>

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="text-sm font-semibold text-slate-900">Imports</div>
                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">ID</th>
                                <th class="px-4 py-3 text-left font-semibold">Customer</th>
                                <th class="px-4 py-3 text-left font-semibold">File</th>
                                <th class="px-4 py-3 text-right font-semibold">Rows</th>
                                <th class="px-4 py-3 text-right font-semibold">Accepted</th>
                                <th class="px-4 py-3 text-right font-semibold">Rejected</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($imports as $imp)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-mono text-xs">#{{ $imp->id }}</td>
                                    <td class="px-4 py-3">{{ $imp->customer->code ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $imp->file_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">{{ $imp->total_rows }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs text-emerald-600">{{ $imp->accepted_rows }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs text-red-600">{{ $imp->rejected_rows }}</td>
                                    <td class="px-4 py-3">{{ strtoupper($imp->status) }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 font-semibold text-xs" href="{{ route('planning.planning-imports.export', $imp) }}">Export</a>
                                        <a class="inline-flex items-center px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs" href="{{ route('planning.planning-imports.index', ['import_id' => $imp->id]) }}">View Rows</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-slate-500">No imports</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $imports->links() }}
                </div>
            </div>

            @if ($rows)
                <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                    <div class="text-sm font-semibold text-slate-900">Imported Rows (Import #{{ $importId }})</div>

                    @if (($unmappedCustomerParts?->count() ?? 0) > 0)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="text-sm font-semibold text-amber-900">Customer Part belum dimapping</div>
                                <div class="text-xs text-amber-800">{{ $unmappedCustomerParts->count() }} item</div>
                            </div>
                            <div class="mt-3 overflow-x-auto border border-amber-200 rounded-xl bg-white">
                                <table class="min-w-full text-sm divide-y divide-amber-200">
                                    <thead class="bg-amber-50">
                                        <tr class="text-amber-900 text-xs uppercase tracking-wider">
                                            <th class="px-4 py-2 text-left font-semibold">Customer Part No</th>
                                            <th class="px-4 py-2 text-right font-semibold">Rows</th>
                                            <th class="px-4 py-2 text-right font-semibold">Total Qty</th>
                                            <th class="px-4 py-2 text-right font-semibold">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-amber-100">
                                        @foreach ($unmappedCustomerParts as $u)
                                            <tr>
                                                <td class="px-4 py-2 font-semibold">{{ $u->customer_part_no }}</td>
                                                <td class="px-4 py-2 text-right font-mono text-xs">{{ (int) $u->rows_count }}</td>
                                                <td class="px-4 py-2 text-right font-mono text-xs">{{ number_format((float) $u->total_qty, 3) }}</td>
                                                <td class="px-4 py-2 text-right">
                                                    <a
                                                        class="text-indigo-600 hover:text-indigo-800 font-semibold"
                                                        href="{{ route('planning.customer-parts.index', ['customer_id' => $importCustomerId, 'prefill_customer_part_no' => $u->customer_part_no]) }}"
                                                    >
                                                        Map Sekarang
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-2 text-xs text-amber-800">
                                Klik “Map Sekarang” untuk buka Customer Part Mapping (customer + part no sudah terisi).
                            </div>
                        </div>
                    @endif

                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left font-semibold">Customer Part</th>
                                    <th class="px-4 py-3 text-left font-semibold">Minggu</th>
                                    <th class="px-4 py-3 text-right font-semibold">Qty</th>
                                    <th class="px-4 py-3 text-left font-semibold">Auto Part GCI</th>
                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                                    <th class="px-4 py-3 text-left font-semibold">Error</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($rows as $row)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-semibold">{{ $row->customer_part_no }}</td>
                                        <td class="px-4 py-3 font-mono text-xs">{{ $row->minggu }}</td>
                                        <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format((float) $row->qty, 3) }}</td>
                                        <td class="px-4 py-3 text-slate-700 text-xs">
                                            @php($translated = $translatedByRowId[$row->id] ?? [])
                                            @if (!empty($translated))
                                                <div class="space-y-1">
                                                    @foreach ($translated as $t)
                                                        <div class="flex items-baseline justify-between gap-3">
                                                            <div class="truncate">
                                                                <span class="font-semibold">{{ $t['part_no'] }}</span>
                                                                <span class="text-slate-500">{{ $t['part_name'] ?? '' }}</span>
                                                            </div>
                                                            <div class="font-mono text-[11px] text-slate-600">
                                                                {{ number_format((float) $t['demand_qty'], 3) }}
                                                                <span class="text-slate-400">(×{{ number_format((float) $t['usage_qty'], 3) }})</span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif ($row->part)
                                                <span class="font-semibold">{{ $row->part->part_no }}</span>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                        @php
                                            $statusClass = match ($row->row_status) {
                                                'accepted' => 'bg-emerald-100 text-emerald-800',
                                                    'unknown_mapping' => 'bg-amber-100 text-amber-800',
                                                    default => 'bg-red-100 text-red-800',
                                                };
                                            @endphp

                                        </td>
                                        <td class="px-4 py-3 text-slate-500">{{ $row->error_message ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">No rows found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $rows->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
