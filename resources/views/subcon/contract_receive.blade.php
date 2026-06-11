@extends('subcon.layout')

@section('content')
    <div class="space-y-6">
        @if (session('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">Receive Kontrak {{ $contractNo }}</h1>
                    <div class="mt-1 text-sm text-slate-600">{{ $vendor?->vendor_name ?? '-' }} · {{ $orders->count() }} item outstanding</div>
                </div>
                <a href="{{ route('subcon.receive-index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-800">&larr; Back</a>
            </div>
        </div>

        <form action="{{ route('subcon.contract-receive.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" x-data="contractReceiveForm()">
            @csrf
            <input type="hidden" name="contract_no" value="{{ $contractNo }}">
            <input type="hidden" name="vendor_id" value="{{ $vendorId }}">

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Received Date <span class="text-red-500">*</span></label>
                        <input type="date" name="received_date" value="{{ old('received_date', now()->format('Y-m-d')) }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">WH Good Location</label>
                        <input type="text" name="receive_location_code" value="{{ old('receive_location_code') }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Kosongkan kalau tidak perlu catat stok ke lokasi">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">WH Reject Location</label>
                        <input type="text" name="reject_location_code" value="{{ old('reject_location_code') }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Kosongkan untuk lokasi reject default">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Upload SJ</label>
                        <input type="file" name="sj_file" accept=".pdf,image/jpeg,image/png"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Upload Invoice</label>
                        <input type="file" name="invoice_file" accept=".pdf,image/jpeg,image/png"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="mb-1 block text-sm font-bold text-slate-700">Notes</label>
                    <textarea name="notes" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Item Dalam Kontrak</h2>
                        <div class="mt-1 text-sm text-slate-500">Isi item yang diterima. Item lain bisa dibiarkan 0.</div>
                    </div>
                    <button type="button" @click="fillAll()" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                        Full Semua Outstanding
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">Order No</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">RM Part</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">WIP Part</th>
                                <th class="px-4 py-3 text-right font-bold text-slate-700">Outstanding</th>
                                <th class="px-4 py-3 text-right font-bold text-slate-700">Qty Good</th>
                                <th class="px-4 py-3 text-right font-bold text-slate-700">Weight Good</th>
                                <th class="px-4 py-3 text-right font-bold text-slate-700">Qty NG</th>
                                <th class="px-4 py-3 text-right font-bold text-slate-700">Weight NG</th>
                                <th class="px-4 py-3 text-center font-bold text-slate-700">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($orders as $i => $order)
                                @php
                                    $netWeight = (float) ($order->rmPart?->net_weight ?? 0);
                                    $uom = $order->bomItem?->consumptionUom?->code
                                        ?? $order->bomItem?->consumption_uom
                                        ?? $order->rmPart?->uom
                                        ?? $order->bomItem?->wipUom?->code
                                        ?? $order->bomItem?->wip_uom
                                        ?? $order->gciPart?->uom
                                        ?? 'PCS';
                                @endphp
                                <tr class="hover:bg-slate-50" data-receive-row data-outstanding="{{ (int) $order->qty_outstanding }}" data-net-weight="{{ $netWeight }}">
                                    <td class="px-4 py-3 font-mono font-bold text-indigo-600">
                                        <a href="{{ route('subcon.show', $order) }}">{{ $order->order_no }}</a>
                                        <input type="hidden" name="items[{{ $i }}][subcon_order_id]" value="{{ $order->id }}">
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $order->rmPart->part_name ?? '-' }}
                                        <div class="font-mono text-[10px] text-slate-400">{{ $order->rmPart->part_no ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $order->gciPart->part_name ?? '-' }}
                                        <div class="font-mono text-[10px] text-slate-400">{{ $order->gciPart->part_no ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-rose-600">
                                        {{ number_format($order->qty_outstanding) }} <span class="text-[10px]">{{ $uom }}</span>
                                        <div class="text-[10px] text-rose-400">{{ number_format((float) $order->qty_outstanding * $netWeight, 2) }} kg</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" min="0" step="1" max="{{ (int) $order->qty_outstanding }}" name="items[{{ $i }}][qty_good]" value="{{ old("items.$i.qty_good", 0) }}"
                                            x-on:input="syncGood($el.closest('tr'))"
                                            class="w-28 rounded-lg border-emerald-300 text-right text-sm font-mono focus:border-emerald-500 focus:ring-emerald-500" data-qty-good>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" min="0" step="0.0001" name="items[{{ $i }}][weight_kgm]" value="{{ old("items.$i.weight_kgm", 0) }}"
                                            class="w-32 rounded-lg border-emerald-300 text-right text-sm font-mono focus:border-emerald-500 focus:ring-emerald-500" data-weight-good>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" min="0" step="1" max="{{ (int) $order->qty_outstanding }}" name="items[{{ $i }}][qty_rejected]" value="{{ old("items.$i.qty_rejected", 0) }}"
                                            x-on:input="syncRejected($el.closest('tr'))"
                                            class="w-28 rounded-lg border-rose-300 text-right text-sm font-mono focus:border-rose-500 focus:ring-rose-500" data-qty-rejected>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" min="0" step="0.0001" name="items[{{ $i }}][weight_rejected_kgm]" value="{{ old("items.$i.weight_rejected_kgm", 0) }}"
                                            class="w-32 rounded-lg border-rose-300 text-right text-sm font-mono focus:border-rose-500 focus:ring-rose-500" data-weight-rejected>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button type="button" @click="fillRow($el.closest('tr'))" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100">
                                            Full Item
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('subcon.receive-index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-indigo-700" onclick="return confirm('Simpan receive untuk item kontrak yang terisi?')">
                    Simpan Receive Kontrak
                </button>
            </div>
        </form>
    </div>

    <script>
        function contractReceiveForm() {
            const setNumber = (input, value) => {
                if (input) input.value = Number(value || 0).toFixed(4).replace(/\.?0+$/, '');
            };

            return {
                fillRow(row) {
                    const outstanding = Number(row.dataset.outstanding || 0);
                    const netWeight = Number(row.dataset.netWeight || 0);
                    setNumber(row.querySelector('[data-qty-good]'), outstanding);
                    setNumber(row.querySelector('[data-weight-good]'), outstanding * netWeight);
                    setNumber(row.querySelector('[data-qty-rejected]'), 0);
                    setNumber(row.querySelector('[data-weight-rejected]'), 0);
                },
                fillAll() {
                    document.querySelectorAll('[data-receive-row]').forEach((row) => this.fillRow(row));
                },
                syncGood(row) {
                    const qty = Number(row.querySelector('[data-qty-good]')?.value || 0);
                    const netWeight = Number(row.dataset.netWeight || 0);
                    setNumber(row.querySelector('[data-weight-good]'), qty * netWeight);
                },
                syncRejected(row) {
                    const qty = Number(row.querySelector('[data-qty-rejected]')?.value || 0);
                    const netWeight = Number(row.dataset.netWeight || 0);
                    setNumber(row.querySelector('[data-weight-rejected]'), qty * netWeight);
                },
            };
        }
    </script>
@endsection
