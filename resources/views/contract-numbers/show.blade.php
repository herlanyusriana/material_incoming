<x-app-layout>
    <x-slot name="header">
        Detail Contract Number
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 px-6 py-6 text-white">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <a href="{{ route('contract-numbers.index') }}" class="text-xs font-bold uppercase tracking-widest text-slate-300 hover:text-white">
                            Back to Contract Numbers
                        </a>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <h1 class="text-3xl font-black">{{ $contract->contract_no }}</h1>
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $contract->status === 'active' ? 'bg-emerald-400/20 text-emerald-100 ring-1 ring-emerald-300/40' : 'bg-slate-400/20 text-slate-200 ring-1 ring-slate-300/40' }}">
                                {{ strtoupper($contract->status) }}
                            </span>
                        </div>
                        <p class="mt-2 max-w-3xl text-sm text-slate-300">{{ $contract->description ?: 'Tidak ada deskripsi kontrak.' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('contract-numbers.edit', $contract) }}" class="rounded-xl bg-white px-4 py-2 text-sm font-black text-slate-900 hover:bg-slate-100">
                            Edit Contract
                        </a>
                        <form action="{{ route('contract-numbers.destroy', $contract) }}" method="POST" onsubmit="return confirm('Hapus nomor kontrak beserta itemnya secara permanen?')">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-xl border border-rose-300/40 bg-rose-500/10 px-4 py-2 text-sm font-bold text-rose-100 hover:bg-rose-500/20">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-300">Vendor</div>
                        <div class="mt-1 truncate text-sm font-black">{{ $contract->vendor?->vendor_name ?? '-' }}</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-300">Effective</div>
                        <div class="mt-1 text-sm font-black">{{ $contract->effective_from?->format('d M Y') ?: '-' }}</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-300">Expired</div>
                        <div class="mt-1 text-sm font-black">{{ $contract->effective_to?->format('d M Y') ?: 'OPEN' }}</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-300">Items</div>
                        <div class="mt-1 font-mono text-lg font-black">{{ number_format($contract->items->count()) }}</div>
                    </div>
                    <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-300">Updated By</div>
                        <div class="mt-1 truncate text-sm font-black">{{ $contract->updater?->name ?? $contract->creator?->name ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="mb-4">
                    <h2 class="text-lg font-black text-slate-900">Detail Item Kontrak</h2>
                    <p class="text-sm text-slate-500">Monitoring target, pemakaian, NG, sisa efektif kontrak, dan alarm per part.</p>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">WIP Part</th>
                                <th class="px-4 py-3">RM Part</th>
                                <th class="px-4 py-3">Process</th>
                                <th class="px-4 py-3 text-center">UOM</th>
                                <th class="px-4 py-3 text-right">Target</th>
                                <th class="px-4 py-3 text-right">Sent</th>
                                <th class="px-4 py-3 text-right">NG</th>
                                <th class="px-4 py-3 text-right">Remain Efektif</th>
                                <th class="px-4 py-3 text-right">Alarm</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($contract->items as $item)
                                @php
                                    $remaining = (float) $item->remaining_qty;
                                    $alarm = $item->warning_limit_qty !== null ? (float) $item->warning_limit_qty : null;
                                    $isAlarm = $alarm !== null && $remaining <= $alarm;
                                    $uom = $item->bomItem?->consumptionUom?->code
                                        ?? $item->bomItem?->consumption_uom
                                        ?? $item->rmPart?->uom
                                        ?? $item->bomItem?->wipUom?->code
                                        ?? $item->bomItem?->wip_uom
                                        ?? $item->gciPart?->uom
                                        ?? 'PCS';
                                @endphp
                                <tr class="{{ $isAlarm ? 'bg-amber-50/70' : '' }}">
                                    <td class="px-4 py-3">
                                        <div class="font-mono font-black text-slate-900">{{ $item->gciPart?->part_no ?? '-' }}</div>
                                        <div class="max-w-[260px] truncate text-xs text-slate-500">{{ $item->gciPart?->part_name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-mono font-black text-indigo-700">{{ $item->rmPart?->part_no ?? '-' }}</div>
                                        <div class="max-w-[260px] truncate text-xs text-slate-500">{{ $item->rmPart?->part_name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-xs font-bold text-slate-600">{{ $item->process_type ?: '-' }}</td>
                                    <td class="px-4 py-3 text-center font-mono text-xs font-black text-slate-600">{{ $uom }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-black text-slate-900">{{ number_format((float) $item->target_qty, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-blue-700">{{ number_format((float) $item->sent_qty, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-bold {{ (float) $item->rejected_qty > 0 ? 'text-rose-700' : 'text-slate-400' }}">{{ number_format((float) $item->rejected_qty, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-black {{ $isAlarm ? 'text-amber-700' : 'text-emerald-700' }}">{{ number_format($remaining, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs font-bold text-slate-500">{{ $alarm !== null ? number_format($alarm, 2) : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-400">Belum ada item pada kontrak ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($contract->notes)
                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="text-xs font-black uppercase tracking-wide text-slate-500">Notes</div>
                        <div class="mt-1 text-sm text-slate-700">{{ $contract->notes }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
