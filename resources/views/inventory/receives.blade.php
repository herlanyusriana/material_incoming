<x-app-layout>
    <x-slot name="header">
        Inventory (Receives)
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
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Part</label>
                            <select name="part_id" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                @foreach ($parts as $p)
                                    <option value="{{ $p->id }}" @selected((string) $partId === (string) $p->id)>{{ $p->part_no }} — {{ $p->part_name_gci }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Status</label>
                            <select name="qc_status" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                <option value="pass" @selected($qcStatus === 'pass')>Good</option>
                                <option value="reject" @selected($qcStatus === 'reject')>No Good</option>
                            </select>
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                    </form>

                    <a href="{{ route('inventory.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold">
                        Inventory Summary
                    </a>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">No</th>
                                <th class="px-4 py-3 text-left font-semibold">Classification</th>
                                <th class="px-4 py-3 text-left font-semibold">Part Number</th>
                                <th class="px-4 py-3 text-left font-semibold">Description</th>
                                <th class="px-4 py-3 text-left font-semibold">Model</th>
                                <th class="px-4 py-3 text-left font-semibold">UOM</th>
                                <th class="px-4 py-3 text-left font-semibold">Storage Location</th>
                                <th class="px-4 py-3 text-left font-semibold">Tag #</th>
                                <th class="px-4 py-3 text-right font-semibold">Bundle Qty</th>
                                <th class="px-4 py-3 text-right font-semibold">Quantity</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($receives as $idx => $r)
                                @php
                                    $part = $r->arrivalItem?->part;
                                    $arrivalItem = $r->arrivalItem;
                                    $arrival = $arrivalItem?->arrival;
                                    $classification = strtoupper(trim((string) ($arrivalItem?->material_group ?? 'INCOMING')));
                                    $statusLabel = $r->qc_status === 'pass' ? 'Good' : 'No Good';
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-slate-600">{{ $receives->firstItem() + $idx }}</td>
                                    <td class="px-4 py-3">{{ $classification !== '' ? $classification : 'INCOMING' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900">{{ $part?->part_no ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $arrival?->invoice_no ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">{{ $part?->part_name_gci ?? ($part?->part_name_vendor ?? '-') }}</td>
                                    <td class="px-4 py-3">{{ $arrivalItem?->size ?? '-' }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ strtoupper((string) ($r->qty_unit ?? '-')) }}</td>
                                    <td class="px-4 py-3">{{ $r->location_code ?? '-' }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $r->tag ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">
                                        {{ number_format((float) ($r->bundle_qty ?? 0), 0) }} {{ strtoupper((string) ($r->bundle_unit ?? '')) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format((float) ($r->qty ?? 0), 0) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold {{ $r->qc_status === 'pass' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-4 py-6 text-center text-slate-500">Belum ada receive.</td>
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

