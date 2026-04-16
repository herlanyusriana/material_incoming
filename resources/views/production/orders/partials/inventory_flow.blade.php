@php
    $inventoryFlow = $inventoryFlow ?? ['supplies' => [], 'returns' => []];
    $flowSupplies = collect($inventoryFlow['supplies'] ?? []);
    $flowReturns = collect($inventoryFlow['returns'] ?? []);
    $totalSupplied = (float) $flowSupplies->sum('qty_supply');
    $totalConsumed = (float) $flowSupplies->sum('qty_consumed');
    $totalReturned = (float) $flowSupplies->sum('qty_returned');
    $totalRemaining = (float) $flowSupplies->sum('qty_remaining');

    $policyLabels = [
        'direct_issue' => 'Pakai Habis',
        'backflush_return' => 'Balik Sisa',
        'backflush_line_stock' => 'Simpan di Line',
    ];

    $statusLabels = [
        'supplied' => ['label' => 'Siap Pakai', 'class' => 'bg-blue-100 text-blue-700'],
        'partial' => ['label' => 'Sebagian Jalan', 'class' => 'bg-amber-100 text-amber-700'],
        'consumed' => ['label' => 'Habis', 'class' => 'bg-emerald-100 text-emerald-700'],
        'returned' => ['label' => 'Balik', 'class' => 'bg-slate-100 text-slate-700'],
        'closed' => ['label' => 'Selesai', 'class' => 'bg-emerald-100 text-emerald-700'],
    ];
@endphp

<div class="bg-white border rounded-lg shadow-sm p-6">
    <div class="flex items-start justify-between gap-4 mb-4">
        <div>
            <h3 class="text-lg font-semibold">Supply, Pakai, Balik</h3>
            <p class="text-sm text-slate-500">Monitor material yang sudah disupply ke produksi, yang sudah terpakai, dan yang dibalikkan.</p>
        </div>
        <div class="text-right text-xs text-slate-500">
            Supply tag<br>
            <span class="font-semibold text-slate-700">{{ $flowSupplies->count() }}</span>
        </div>
    </div>

    @if($flowSupplies->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-600">
            Belum ada data supply material untuk WO ini.
        </div>
    @else
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-3">
                <div class="text-[11px] uppercase tracking-wide text-blue-600">Total Supply</div>
                <div class="mt-1 text-lg font-bold text-blue-800">{{ number_format($totalSupplied, 4) }}</div>
            </div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                <div class="text-[11px] uppercase tracking-wide text-emerald-600">Sudah Dipakai</div>
                <div class="mt-1 text-lg font-bold text-emerald-800">{{ number_format($totalConsumed, 4) }}</div>
            </div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                <div class="text-[11px] uppercase tracking-wide text-amber-600">Sudah Dibalikkan</div>
                <div class="mt-1 text-lg font-bold text-amber-800">{{ number_format($totalReturned, 4) }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="text-[11px] uppercase tracking-wide text-slate-500">Sisa Di Produksi</div>
                <div class="mt-1 text-lg font-bold text-slate-900">{{ number_format($totalRemaining, 4) }}</div>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 overflow-hidden mb-5">
            <div class="px-4 py-3 bg-slate-50 border-b">
                <h4 class="font-semibold text-slate-900">Detail Supply per Tag</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Tag</th>
                            <th class="px-4 py-3 text-left">Part</th>
                            <th class="px-4 py-3 text-left">Policy</th>
                            <th class="px-4 py-3 text-left">Lokasi</th>
                            <th class="px-4 py-3 text-right">Supply</th>
                            <th class="px-4 py-3 text-right">Pakai</th>
                            <th class="px-4 py-3 text-right">Balik</th>
                            <th class="px-4 py-3 text-right">Sisa</th>
                            <th class="px-4 py-3 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($flowSupplies as $line)
                            @php
                                $statusMeta = $statusLabels[$line['status'] ?? 'supplied'] ?? ['label' => strtoupper((string) ($line['status'] ?? '-')), 'class' => 'bg-slate-100 text-slate-700'];
                            @endphp
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $line['tag_number'] ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $line['supplied_at'] ? \Carbon\Carbon::parse($line['supplied_at'])->format('d M Y H:i') : '-' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-900">{{ $line['part_no'] ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $line['part_name'] ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                        {{ $policyLabels[$line['policy'] ?? ''] ?? strtoupper((string) ($line['policy'] ?? '-')) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    <div>Dari: {{ $line['source_location_code'] ?: '-' }}</div>
                                    <div>Ke: {{ $line['target_location_code'] ?: '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format((float) ($line['qty_supply'] ?? 0), 4) }}<div class="text-[11px] font-normal text-slate-400">{{ $line['uom'] ?: '-' }}</div></td>
                                <td class="px-4 py-3 text-right font-semibold text-emerald-700">{{ number_format((float) ($line['qty_consumed'] ?? 0), 4) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-amber-700">{{ number_format((float) ($line['qty_returned'] ?? 0), 4) }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ (float) ($line['qty_remaining'] ?? 0) > 0 ? 'text-rose-700' : 'text-slate-500' }}">{{ number_format((float) ($line['qty_remaining'] ?? 0), 4) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusMeta['class'] }}">
                                        {{ $statusMeta['label'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b">
                <h4 class="font-semibold text-slate-900">History Balik Material</h4>
            </div>
            @if($flowReturns->isEmpty())
                <div class="p-4 text-sm text-slate-500">Belum ada material yang dibalikkan dari WO ini.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 text-left">Waktu</th>
                                <th class="px-4 py-3 text-left">Tag</th>
                                <th class="px-4 py-3 text-left">Lokasi</th>
                                <th class="px-4 py-3 text-right">Qty Balik</th>
                                <th class="px-4 py-3 text-left">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($flowReturns as $return)
                                <tr>
                                    <td class="px-4 py-3 text-slate-700">{{ $return['returned_at'] ? \Carbon\Carbon::parse($return['returned_at'])->format('d M Y H:i') : '-' }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $return['tag_number'] ?: '-' }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        <div>Dari: {{ $return['from_location_code'] ?: '-' }}</div>
                                        <div>Ke: {{ $return['to_location_code'] ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-amber-700">{{ number_format((float) ($return['qty_return'] ?? 0), 4) }}<div class="text-[11px] font-normal text-slate-400">{{ $return['uom'] ?: '-' }}</div></td>
                                    <td class="px-4 py-3 text-xs text-slate-600">
                                        @php
                                            $notes = collect($return['notes'] ?? [])->filter(fn ($value) => filled($value))->values();
                                        @endphp
                                        {{ $notes->isNotEmpty() ? $notes->implode(' | ') : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
