@extends('subcon.layout')

@section('content')
    @php
        $subconUom = $subconOrder->bomItem?->consumptionUom?->code
            ?? $subconOrder->bomItem?->consumption_uom
            ?? $subconOrder->rmPart?->uom
            ?? $subconOrder->bomItem?->wipUom?->code
            ?? $subconOrder->bomItem?->wip_uom
            ?? $subconOrder->gciPart?->uom
            ?? 'PCS';
    @endphp
    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">{{ $subconOrder->order_no }}</h1>
                    <div class="mt-1 text-sm text-slate-600">Subcon Order Detail</div>
                </div>
                <div class="flex gap-2 items-center">
                    @php
                        $statusColors = [
                            'draft' => 'bg-slate-100 text-slate-700',
                            'sent' => 'bg-blue-100 text-blue-700',
                            'partial' => 'bg-amber-100 text-amber-700',
                            'completed' => 'bg-emerald-100 text-emerald-700',
                            'cancelled' => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-bold {{ $statusColors[$subconOrder->status] ?? '' }}">
                        {{ ucfirst($subconOrder->status) }}
                    </span>
                    <a href="{{ route('subcon.print-sj', $subconOrder) }}" target="_blank"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Print SJ</a>
                    <a href="{{ route('subcon.print-pl', $subconOrder) }}" target="_blank"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Print PL</a>
                    <a href="{{ route('subcon.print-invoice', $subconOrder) }}" target="_blank"
                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Print Invoice</a>
                    <a href="{{ route('subcon.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Back</a>
                </div>
            </div>
        </div>

        {{-- Order Details --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Order Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Nomor Kontrak</div>
                    <div class="mt-1 text-slate-900 font-semibold">{{ $subconOrder->contract_no ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Vendor</div>
                    <div class="mt-1 text-slate-900 font-semibold">{{ $subconOrder->vendor->vendor_name ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">RM Part (WH Send)</div>
                    <div class="mt-1 text-slate-900 font-semibold">{{ $subconOrder->rmPart->part_name ?? '-' }}</div>
                    <div class="text-xs text-slate-400 font-mono">{{ $subconOrder->rmPart->part_no ?? '' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">WIP Part</div>
                    <div class="mt-1 text-slate-900 font-semibold">{{ $subconOrder->gciPart->part_name ?? '-' }}</div>
                    <div class="text-xs text-slate-400 font-mono">{{ $subconOrder->gciPart->part_no ?? '' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Process</div>
                    <div class="mt-1 text-slate-900 font-semibold">{{ ucfirst($subconOrder->process_type) }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Sent Date</div>
                    <div class="mt-1 text-slate-900">{{ $subconOrder->sent_date->format('d/m/Y') }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Expected Return</div>
                    <div class="mt-1 text-slate-900">{{ $subconOrder->expected_return_date?->format('d/m/Y') ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Created By</div>
                    <div class="mt-1 text-slate-900">{{ $subconOrder->creator->name ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Stock Sent Posted</div>
                    <div class="mt-1 text-slate-900">
                        {{ $subconOrder->sent_posted_at?->format('d/m/Y H:i') ?? '-' }}
                        @if($subconOrder->sender)
                            <span class="text-slate-500">by {{ $subconOrder->sender->name }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Qty Summary --}}
            <div class="mt-6 pt-6 border-t border-slate-200">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="rounded-lg bg-blue-50 p-4 text-center">
                        <div class="text-xs font-bold text-blue-600 uppercase">Qty Sent</div>
                        <div class="mt-1 text-xl font-black text-blue-800">{{ number_format($subconOrder->qty_sent) }} <span class="text-xs text-blue-600">{{ $subconUom }}</span></div>
                        <div class="text-[10px] text-blue-500 font-bold">({{ number_format((float)$subconOrder->qty_sent * (float)($subconOrder->rmPart->net_weight ?? 0), 2) }} kg)</div>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-4 text-center">
                        <div class="text-xs font-bold text-emerald-600 uppercase">Qty Received</div>
                        <div class="mt-1 text-xl font-black text-emerald-800">{{ number_format($subconOrder->qty_received) }} <span class="text-xs text-emerald-600">{{ $subconUom }}</span></div>
                        <div class="text-[10px] text-emerald-500 font-bold">({{ number_format((float)$subconOrder->qty_received * (float)($subconOrder->gciPart->net_weight ?? 0), 2) }} kg)</div>
                    </div>
                    <div class="rounded-lg bg-red-50 p-4 text-center">
                        <div class="text-xs font-bold text-red-600 uppercase">Qty Rejected</div>
                        <div class="mt-1 text-xl font-black text-red-800">{{ number_format($subconOrder->qty_rejected) }} <span class="text-xs text-red-600">{{ $subconUom }}</span></div>
                        <div class="text-[10px] text-red-500 font-bold">({{ number_format((float)$subconOrder->qty_rejected * (float)($subconOrder->gciPart->net_weight ?? 0), 2) }} kg)</div>
                    </div>
                    <div class="rounded-lg bg-amber-50 p-4 text-center">
                        <div class="text-xs font-bold text-amber-600 uppercase">Outstanding</div>
                        <div class="mt-1 text-xl font-black text-amber-800">{{ number_format($subconOrder->qty_outstanding) }} <span class="text-xs text-amber-600">{{ $subconUom }}</span></div>
                        <div class="text-[10px] text-amber-500 font-bold">({{ number_format((float)$subconOrder->qty_outstanding * (float)($subconOrder->rmPart->net_weight ?? 0), 2) }} kg)</div>
                    </div>
                </div>
            </div>

            @if ($subconOrder->notes)
                <div class="mt-4 pt-4 border-t border-slate-200">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Notes</div>
                    <div class="mt-1 text-sm text-slate-700">{{ $subconOrder->notes }}</div>
                </div>
            @endif
        </div>

        {{-- Receive Form --}}
        @if (!in_array($subconOrder->status, ['completed', 'cancelled']))
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-bold text-slate-900 mb-4">Record Receive</h2>
                <form action="{{ route('subcon.receive', $subconOrder) }}" method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="return confirmSubconNgReceive(this);">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Qty Good <span class="text-red-500">*</span></label>
                            <input type="number" step="0.0001" min="0" name="qty_good" value="{{ old('qty_good') }}"
                                class="w-full rounded-lg border-emerald-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" required />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Weight Good (KGM) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.0001" min="0" name="weight_kgm" value="{{ old('weight_kgm') }}"
                                class="w-full rounded-lg border-emerald-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" required />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Qty Rejected</label>
                            <input type="number" step="0.0001" min="0" name="qty_rejected" value="{{ old('qty_rejected') }}"
                                class="w-full rounded-lg border-rose-300 text-sm focus:border-rose-500 focus:ring-rose-500" />
                            <div class="mt-1 text-xs font-semibold text-rose-600">NG akan mengurangi remain efektif kontrak/SKEP.</div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Weight Rejected (KGM)</label>
                            <input type="number" step="0.0001" min="0" name="weight_rejected_kgm" value="{{ old('weight_rejected_kgm') }}"
                                class="w-full rounded-lg border-rose-300 text-sm focus:border-rose-500 focus:ring-rose-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Received Date <span class="text-red-500">*</span></label>
                            <input type="date" name="received_date" value="{{ old('received_date', now()->format('Y-m-d')) }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Upload Surat Jalan (SJ)</label>
                            <input type="file" name="sj_file" accept=".pdf,image/jpeg,image/png"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Upload Invoice</label>
                            <input type="file" name="invoice_file" accept=".pdf,image/jpeg,image/png"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">WH Receive Good Location</label>
                            <input type="text" name="receive_location_code" value="{{ old('receive_location_code', $subconOrder->gciPart->default_location ?? '') }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <div class="mt-1 text-xs text-slate-500">Opsional. Default: Bypass constraint lokasi.</div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">WH Reject Location</label>
                            <input type="text" name="reject_location_code" value="{{ old('reject_location_code') }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <div class="mt-1 text-xs text-slate-500">Opsional. Default: Bypass constraint lokasi.</div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Notes</label>
                        <textarea name="notes" rows="2"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Optional"></textarea>
                    </div>
                    <button type="submit"
                        class="rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">
                        Record Receive
                    </button>
                </form>
            </div>
        @endif

        {{-- Receive History --}}
        @if ($subconOrder->receives->isNotEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">Receive History</h2>
                </div>
                <table class="w-full text-sm divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">#</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Date</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Qty Good</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Qty Rejected</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">WH Good Receive</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">WH Reject Receive</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Notes</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($subconOrder->receives as $i => $rec)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-600">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 text-center text-slate-700">{{ $rec->received_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-right font-mono text-emerald-700 font-bold">
                                    {{ number_format($rec->qty_good) }} <span class="text-[10px]">{{ $subconUom }}</span>
                                    <div class="text-[10px] text-emerald-500">({{ number_format($rec->weight_kgm, 2) }} kg)</div>
                                </td>
                                <td class="px-4 py-3 text-right font-mono {{ $rec->qty_rejected > 0 ? 'text-red-600' : 'text-slate-400' }}">
                                    {{ number_format($rec->qty_rejected) }} <span class="text-[10px]">{{ $subconUom }}</span>
                                    <div class="text-[10px] text-red-400">({{ number_format($rec->weight_rejected_kgm, 2) }} kg)</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="font-semibold">{{ $rec->receive_location_code ?? '-' }}</div>
                                    <div class="text-xs text-slate-400">{{ $rec->posted_to_wh_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="font-semibold">{{ $rec->reject_location_code ?? '-' }}</div>
                                    <div class="text-xs text-slate-400">{{ $rec->reject_posted_to_wh_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="flex flex-col gap-1">
                                        <div>{{ $rec->notes ?? '-' }}</div>
                                        <div class="flex gap-2">
                                            @if($rec->sj_file_path)
                                                <a href="{{ Storage::url($rec->sj_file_path) }}" target="_blank" class="text-xs text-indigo-600 hover:underline">View SJ</a>
                                            @endif
                                            @if($rec->invoice_file_path)
                                                <a href="{{ Storage::url($rec->invoice_file_path) }}" target="_blank" class="text-xs text-indigo-600 hover:underline">View Invoice</a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="flex flex-col items-start gap-1">
                                        <span>{{ $rec->creator->name ?? '-' }}</span>
                                        <div class="flex gap-2 mt-1">
                                            <a href="{{ route('subcon.receive.print-label', $rec) }}" target="_blank" class="rounded border border-slate-300 bg-white px-2 py-0.5 text-[10px] font-bold text-slate-600 hover:bg-slate-50">Label</a>
                                            <a href="{{ route('subcon.receive.print-pl', $rec) }}" target="_blank" class="rounded border border-slate-300 bg-white px-2 py-0.5 text-[10px] font-bold text-slate-600 hover:bg-slate-50">PL</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Reject History --}}
        <div class="bg-white rounded-2xl shadow-sm border border-rose-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-rose-100 bg-rose-50/60">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-rose-950">Reject History</h2>
                        <div class="mt-1 text-sm text-rose-700">Catatan part NG dari vendor subcon yang masuk ke lokasi reject.</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-black uppercase tracking-wider text-rose-600">Total Reject</div>
                        <div class="text-xl font-black text-rose-800">
                            {{ number_format((float) $subconOrder->qty_rejected, 4) }}
                            <span class="text-xs">{{ $subconUom }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">#</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Receive Date</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Qty Reject</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Weight KGM</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Reject Location</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Posted WH</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Notes / Docs</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse (($rejectReceives ?? collect()) as $i => $rec)
                            <tr class="hover:bg-rose-50/40">
                                <td class="px-4 py-3 text-slate-600">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 text-center text-slate-700">{{ $rec->received_date?->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-right font-mono font-black text-rose-700">
                                    {{ number_format((float) $rec->qty_rejected, 4) }}
                                    <span class="text-[10px]">{{ $subconUom }}</span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-rose-600">
                                    {{ number_format((float) $rec->weight_rejected_kgm, 4) }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $rec->reject_location_code ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $rec->reject_posted_to_wh_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div>{{ $rec->notes ?? '-' }}</div>
                                    <div class="mt-1 flex gap-2">
                                        @if($rec->sj_file_path)
                                            <a href="{{ Storage::url($rec->sj_file_path) }}" target="_blank" class="text-xs font-semibold text-indigo-600 hover:underline">SJ</a>
                                        @endif
                                        @if($rec->invoice_file_path)
                                            <a href="{{ Storage::url($rec->invoice_file_path) }}" target="_blank" class="text-xs font-semibold text-indigo-600 hover:underline">Invoice</a>
                                        @endif
                                        <a href="{{ route('subcon.receive.print-pl', $rec) }}" target="_blank" class="text-xs font-semibold text-slate-600 hover:underline">PL</a>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $rec->creator->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-slate-400">Belum ada reject untuk order ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if(isset($traceability) && $traceability->isNotEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">Traceability / Movement History</h2>
                </div>
                <table class="w-full text-sm divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Nomor Kontrak</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Time</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Type</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Location</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Qty Change</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($traceability as $move)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-700 font-semibold">{{ $subconOrder->contract_no ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $move->adjusted_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{
                                        match($move->transaction_type ?? '') {
                                            'SUBCON_SEND' => 'bg-amber-100 text-amber-700',
                                            'SUBCON_REJECT_RECEIVE' => 'bg-rose-100 text-rose-700',
                                            default => 'bg-emerald-100 text-emerald-700',
                                        }
                                    }}">
                                        {{ $move->transaction_type ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $move->location_code ?? '-' }}</td>
                                <td class="px-4 py-3 text-right font-mono {{ (float) $move->qty_change < 0 ? 'text-red-600' : 'text-emerald-700' }}">
                                    {{ number_format((float) $move->qty_change, 4) }} <span class="text-[10px]">{{ $subconUom }}</span>
                                    @if ($move->weight_kgm !== null)
                                        <div class="text-[10px] font-bold opacity-80 mt-1">({{ number_format(abs($move->weight_kgm), 2) }} kg)</div>
                                    @else
                                        @php
                                            $weightFactor = ($move->transaction_type === 'SUBCON_SEND') 
                                                ? ($subconOrder->rmPart->net_weight ?? 0) 
                                                : ($subconOrder->gciPart->net_weight ?? 0);
                                            $weightVal = abs((float)$move->qty_change) * (float)$weightFactor;
                                        @endphp
                                        <div class="text-[10px] opacity-70 italic">({{ number_format($weightVal, 2) }} kg*)</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $move->creator->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Cancel Button --}}
        @if (!in_array($subconOrder->status, ['completed', 'cancelled']))
            <div class="flex justify-end">
                <form action="{{ route('subcon.cancel', $subconOrder) }}" method="POST"
                    onsubmit="return confirm('Are you sure you want to cancel this order?');">
                    @csrf
                    <button type="submit"
                        class="rounded-lg bg-red-100 px-5 py-2 text-sm font-bold text-red-700 hover:bg-red-200">
                        Cancel Order
                    </button>
                </form>
            </div>
        @endif
    </div>

    <script>
        function confirmSubconNgReceive(form) {
            const rejectedInput = form.querySelector('[name="qty_rejected"]');
            const rejectedQty = Number(rejectedInput?.value || 0);

            if (rejectedQty <= 0) {
                return true;
            }

            return confirm(`Qty Rejected/NG ${rejectedQty} akan mengurangi remain efektif kontrak/SKEP. Lanjut simpan receive ini?`);
        }
    </script>
@endsection
