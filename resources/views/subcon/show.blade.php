@extends('subcon.layout')

@section('content')
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
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">WH Send Location</div>
                    <div class="mt-1 text-slate-900 font-semibold">{{ $subconOrder->send_location_code ?? '-' }}</div>
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
                        <div class="mt-1 text-xl font-black text-blue-800">{{ number_format($subconOrder->qty_sent) }}</div>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-4 text-center">
                        <div class="text-xs font-bold text-emerald-600 uppercase">Qty Received</div>
                        <div class="mt-1 text-xl font-black text-emerald-800">{{ number_format($subconOrder->qty_received) }}</div>
                    </div>
                    <div class="rounded-lg bg-red-50 p-4 text-center">
                        <div class="text-xs font-bold text-red-600 uppercase">Qty Rejected</div>
                        <div class="mt-1 text-xl font-black text-red-800">{{ number_format($subconOrder->qty_rejected) }}</div>
                    </div>
                    <div class="rounded-lg bg-amber-50 p-4 text-center">
                        <div class="text-xs font-bold text-amber-600 uppercase">Outstanding</div>
                        <div class="mt-1 text-xl font-black text-amber-800">{{ number_format($subconOrder->qty_outstanding) }}</div>
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
                <form action="{{ route('subcon.receive', $subconOrder) }}" method="POST" class="space-y-4 max-w-xl">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Qty Good <span class="text-red-500">*</span></label>
                            <input type="number" name="qty_good" step="0.0001" min="0" value="{{ old('qty_good', 0) }}" required
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Qty Rejected</label>
                            <input type="number" name="qty_rejected" step="0.0001" min="0" value="{{ old('qty_rejected', 0) }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Received Date <span class="text-red-500">*</span></label>
                        <input type="date" name="received_date" required value="{{ old('received_date', now()->toDateString()) }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 max-w-xs" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">WH Receive Good Location</label>
                            <input type="text" name="receive_location_code" value="{{ old('receive_location_code', $subconOrder->gciPart->default_location ?? '') }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <div class="mt-1 text-xs text-slate-500">Wajib jika Qty Good lebih dari nol. Lokasi ini untuk WIP hasil vendor.</div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">WH Reject Location</label>
                            <input type="text" name="reject_location_code" value="{{ old('reject_location_code') }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <div class="mt-1 text-xs text-slate-500">Gunakan lokasi NG / reject / rework jika Qty Rejected lebih dari nol.</div>
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
                                <td class="px-4 py-3 text-right font-mono text-emerald-700 font-bold">{{ number_format($rec->qty_good) }}</td>
                                <td class="px-4 py-3 text-right font-mono {{ $rec->qty_rejected > 0 ? 'text-red-600' : 'text-slate-400' }}">{{ number_format($rec->qty_rejected) }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="font-semibold">{{ $rec->receive_location_code ?? '-' }}</div>
                                    <div class="text-xs text-slate-400">{{ $rec->posted_to_wh_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="font-semibold">{{ $rec->reject_location_code ?? '-' }}</div>
                                    <div class="text-xs text-slate-400">{{ $rec->reject_posted_to_wh_at?->format('d/m/Y H:i') ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $rec->notes ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $rec->creator->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

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
                                    {{ number_format((float) $move->qty_change, 4) }}
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
@endsection
