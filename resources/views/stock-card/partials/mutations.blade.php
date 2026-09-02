@php
    // Map movement_type → badge kelas & label Indonesia agar gudang langsung paham
    // arahnya masuk/keluar tanpa harus hafal kode sistem.
    $movementMap = [
        'RECEIVE' => ['label' => 'Terima', 'dir' => 'in', 'class' => 'bg-emerald-100 text-emerald-700'],
        'DELIVERY' => ['label' => 'Kirim', 'dir' => 'out', 'class' => 'bg-rose-100 text-rose-700'],
        'ADJUSTMENT' => ['label' => 'Penyesuaian', 'dir' => 'adj', 'class' => 'bg-amber-100 text-amber-700'],
        'ISSUE' => ['label' => 'Issue', 'dir' => 'out', 'class' => 'bg-rose-100 text-rose-700'],
        'RETURN' => ['label' => 'Retur', 'dir' => 'in', 'class' => 'bg-emerald-100 text-emerald-700'],
        'TRANSFER' => ['label' => 'Transfer', 'dir' => 'adj', 'class' => 'bg-sky-100 text-sky-700'],
        'MOVE' => ['label' => 'Pindah', 'dir' => 'adj', 'class' => 'bg-sky-100 text-sky-700'],
        'supply_to_department' => ['label' => 'Supply ke Dept', 'dir' => 'out', 'class' => 'bg-rose-100 text-rose-700'],
        'consume_direct_issue' => ['label' => 'Issue Langsung', 'dir' => 'out', 'class' => 'bg-rose-100 text-rose-700'],
        'consume_production' => ['label' => 'Konsumsi Produksi', 'dir' => 'out', 'class' => 'bg-rose-100 text-rose-700'],
        'return_to_wh' => ['label' => 'Retur ke Gudang', 'dir' => 'in', 'class' => 'bg-emerald-100 text-emerald-700'],
    ];
@endphp

@if ($movements->isEmpty())
    <div class="text-center py-10">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7m16 0v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5m16 0h-2.586a1 1 0 0 0-.707.293l-2.414 2.414a1 1 0 0 1-.707.293h-3.172a1 1 0 0 1-.707-.293l-2.414-2.414A1 1 0 0 0 6.586 13H4" />
        </svg>
        <div class="text-sm font-semibold text-slate-500">Belum ada mutasi tercatat</div>
        <div class="text-xs text-slate-400 mt-0.5">Untuk part ini, saldo belum terganggu oleh transaksi masuk/keluar.</div>
    </div>
@else
    <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full text-sm divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2.5 text-left font-bold text-slate-500 uppercase tracking-wider text-[11px]">Waktu</th>
                    <th class="px-3 py-2.5 text-left font-bold text-slate-500 uppercase tracking-wider text-[11px]">Jenis</th>
                    <th class="px-3 py-2.5 text-center font-bold text-slate-500 uppercase tracking-wider text-[11px]">Qty</th>
                    <th class="px-3 py-2.5 text-left font-bold text-slate-500 uppercase tracking-wider text-[11px]">Lokasi</th>
                    <th class="px-3 py-2.5 text-left font-bold text-slate-500 uppercase tracking-wider text-[11px]">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($movements as $mv)
                    @php
                        $m = $movementMap[$mv->movement_type] ?? ['label' => $mv->movement_type, 'dir' => 'adj', 'class' => 'bg-slate-100 text-slate-600'];
                        $locationStr = trim(($mv->from_location_code ?? '') . ' → ' . ($mv->to_location_code ?? ''), ' →');
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-3 py-2.5 whitespace-nowrap text-xs text-slate-500 font-mono">
                            {{ $mv->moved_at ? $mv->moved_at->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td class="px-3 py-2.5">
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-bold {{ $m['class'] }}">
                                @if ($m['dir'] === 'in')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m-6 6 6-6 6 6" /></svg>
                                @elseif ($m['dir'] === 'out')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m6-6-6 6-6-6" /></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4 4 4m6 0v12m0 0 4-4m-4 4-4-4" /></svg>
                                @endif
                                {{ $m['label'] }}
                            </span>
                        </td>
                        <td class="px-3 py-2.5 text-right font-mono font-bold {{ $m['dir'] === 'in' ? 'text-emerald-600' : ($m['dir'] === 'out' ? 'text-rose-600' : 'text-slate-700') }}">
                            {{ $m['dir'] === 'in' ? '+' : ($m['dir'] === 'out' ? '−' : '') }}{{ number_format((float) $mv->qty, 0, ',', '.') }}
                        </td>
                        <td class="px-3 py-2.5 text-xs text-slate-500 font-mono {{ $locationStr === '' ? 'text-slate-300' : '' }}">
                            {{ $locationStr === '' ? '-' : $locationStr }}
                        </td>
                        <td class="px-3 py-2.5 text-xs text-slate-500 max-w-[220px] truncate">
                            {{ $mv->notes ?: '-' }}
                            @if ($mv->tag_number)
                                <span class="text-slate-400">(Tag: {{ $mv->tag_number }})</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
