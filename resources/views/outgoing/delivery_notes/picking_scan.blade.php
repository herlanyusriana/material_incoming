@extends('outgoing.layout')

@section('content')
    <div class="max-w-5xl mx-auto space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('outgoing.delivery-notes.show', $deliveryNote) }}" class="h-10 w-10 flex items-center justify-center rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-500">
                        ←
                    </a>
                    <div>
                        <h1 class="text-2xl font-black text-slate-900">Picking Scan • {{ $deliveryNote->dn_no }}</h1>
                        <p class="text-sm text-slate-500 font-semibold">{{ $deliveryNote->customer->name }} • {{ $deliveryNote->delivery_date->format('d M Y') }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if ($deliveryNote->status === 'ready_to_pick')
                        <form action="{{ route('outgoing.delivery-notes.start-picking', $deliveryNote) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-xl bg-amber-600 text-white font-black hover:bg-amber-700">
                                START PICKING
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('outgoing.delivery-notes.show', $deliveryNote) }}" class="px-4 py-2 rounded-xl border border-slate-200 font-black text-slate-700 hover:bg-slate-50">
                        Back to DN
                    </a>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <div class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-1">Scan Location</div>
                    <input id="scan-location" type="text" placeholder="Scan location code"
                        class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <div class="text-xs text-slate-500 mt-1">Harus sama dengan kitting location.</div>
                </div>
                <div>
                    <div class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-1">Scan Part</div>
                    <input id="scan-part" type="text" placeholder="Scan part no"
                        class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <div class="text-xs text-slate-500 mt-1">Scan part setelah lokasi.</div>
                </div>
                <div>
                    <div class="text-[11px] font-black uppercase tracking-widest text-slate-400 mb-1">Qty</div>
                    <input id="scan-qty" type="number" step="0.0001" min="0.0001" value="1"
                        class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <div class="text-xs text-slate-500 mt-1">Default 1.</div>
                </div>
            </div>

            <form id="scan-form" action="{{ route('outgoing.delivery-notes.picking-scan.store', $deliveryNote) }}" method="POST" class="hidden">
                @csrf
                <input type="hidden" name="location_code" id="form-location">
                <input type="hidden" name="part_no" id="form-part">
                <input type="hidden" name="qty" id="form-qty">
            </form>

            <div class="text-sm text-slate-700">
                Status: <span class="font-black">{{ strtoupper($deliveryNote->status) }}</span>
                • Tips: scanner biasanya otomatis “enter”; cukup scan berurutan.
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <div class="text-lg font-black text-slate-900">Items</div>
                <div class="text-sm font-semibold text-slate-600">
                    Picked: {{ number_format((float) $deliveryNote->items->sum('picked_qty'), 4) }} / {{ number_format((float) $deliveryNote->items->sum('qty'), 4) }}
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-3 text-left font-bold text-slate-600">Part</th>
                            <th class="px-5 py-3 text-left font-bold text-slate-600">Kitting Location</th>
                            <th class="px-5 py-3 text-right font-bold text-slate-600">Required</th>
                            <th class="px-5 py-3 text-right font-bold text-slate-600">Picked</th>
                            <th class="px-5 py-3 text-right font-bold text-slate-600">Remaining</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($deliveryNote->items as $item)
                            @php
                                $required = (float) $item->qty;
                                $picked = (float) ($item->picked_qty ?? 0);
                                $remaining = max(0, $required - $picked);
                            @endphp
                            <tr class="{{ $remaining <= 0 ? 'bg-emerald-50' : '' }}">
                                <td class="px-5 py-4 font-black text-slate-900">
                                    {{ $item->part->part_no }}
                                    <div class="text-xs text-slate-500 font-semibold">{{ $item->part->part_name }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    <span class="font-mono text-xs">{{ $item->kitting_location_code ?: '-' }}</span>
                                </td>
                                <td class="px-5 py-4 text-right font-black text-slate-900">{{ number_format($required, 4) }}</td>
                                <td class="px-5 py-4 text-right font-black text-indigo-700">{{ number_format($picked, 4) }}</td>
                                <td class="px-5 py-4 text-right font-black {{ $remaining <= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ number_format($remaining, 4) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const elLoc = document.getElementById('scan-location');
            const elPart = document.getElementById('scan-part');
            const elQty = document.getElementById('scan-qty');
            const fLoc = document.getElementById('form-location');
            const fPart = document.getElementById('form-part');
            const fQty = document.getElementById('form-qty');
            const form = document.getElementById('scan-form');

            function norm(v) {
                return String(v || '').trim().toUpperCase();
            }

            function submitIfReady() {
                const loc = norm(elLoc.value);
                const part = norm(elPart.value);
                const qty = String(elQty.value || '1').trim();
                if (!loc || !part) return;

                fLoc.value = loc;
                fPart.value = part;
                fQty.value = qty;

                form.submit();
            }

            elLoc.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    elPart.focus();
                }
            });

            elPart.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitIfReady();
                }
            });

            window.addEventListener('load', () => {
                elLoc.focus();
            });
        })();
    </script>
@endsection

