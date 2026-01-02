<x-app-layout>
    <x-slot name="header">
        Edit Departure
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 space-y-2">
                    <div class="font-semibold">Periksa kembali kolom tanggal di bawah.</div>
                    <ul class="list-disc list-inside space-y-1 text-red-800">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 space-y-6">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Departure {{ $arrival->invoice_no ?? 'Edit' }}</h2>
                    <p class="text-sm text-slate-500">Edit informasi utama departure. Items & receive records tidak diubah dari halaman ini.</p>
                </div>

                <form method="POST" action="{{ route('departures.update', $arrival) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="space-y-1">
                        <label for="invoice_no" class="text-sm font-medium text-slate-700">Invoice No.</label>
                        <input type="text" id="invoice_no" name="invoice_no" value="{{ old('invoice_no', $arrival->invoice_no) }}" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                    </div>

                    <div class="space-y-1">
                        <label for="invoice_date" class="text-sm font-medium text-slate-700">Invoice Date</label>
                        <input type="date" id="invoice_date" name="invoice_date" value="{{ old('invoice_date', optional($arrival->invoice_date)->format('Y-m-d')) }}" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="etd" class="text-sm font-medium text-slate-700">ETD</label>
                            <input type="date" id="etd" name="etd" value="{{ old('etd', optional($arrival->ETD)->format('Y-m-d')) }}" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div class="space-y-1">
                            <label for="eta" class="text-sm font-medium text-slate-700">ETA</label>
                            <input type="date" id="eta" name="eta" value="{{ old('eta', optional($arrival->ETA)->format('Y-m-d')) }}" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
	                        <div class="space-y-1">
	                            <label for="vessel" class="text-sm font-medium text-slate-700">Vessel</label>
	                            <input type="text" id="vessel" name="vessel" value="{{ old('vessel', $arrival->vessel) }}" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
	                        </div>
	                        <div class="space-y-1">
	                            <label for="bl_no" class="text-sm font-medium text-slate-700">Bill of Lading</label>
	                            <input type="text" id="bl_no" name="bl_no" value="{{ old('bl_no', $arrival->bill_of_lading) }}" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                @error('bl_no') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
	                        </div>
                            <div class="space-y-1">
                                <label for="price_term" class="text-sm font-medium text-slate-700">Price Term</label>
                                <input type="text" id="price_term" name="price_term" value="{{ old('price_term', $arrival->price_term) }}" placeholder="FOB / CIF / EXW" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                @error('price_term') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
	                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="hs_codes" class="text-sm font-medium text-slate-700">HS Code (bisa lebih dari 1)</label>
                            <textarea id="hs_codes" name="hs_codes" rows="2" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Pisahkan dengan enter atau koma (contoh: 7208.90.00, 7210.70.00)">{{ old('hs_codes', $arrival->hs_codes ?: $arrival->hs_code) }}</textarea>
                            @error('hs_codes') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            @error('hs_code') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1">
                            <label for="currency" class="text-sm font-medium text-slate-700">Currency</label>
                            <input type="text" id="currency" name="currency" value="{{ old('currency', $arrival->currency) }}" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="port_of_loading" class="text-sm font-medium text-slate-700">Port of Loading</label>
                            <input type="text" id="port_of_loading" name="port_of_loading" value="{{ old('port_of_loading', $arrival->port_of_loading) }}" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div class="space-y-1">
                            <label for="seal_code" class="text-sm font-medium text-slate-700">Seal Code</label>
                            <input type="text" id="seal_code" name="seal_code" value="{{ old('seal_code', $arrival->seal_code) }}" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                    </div>

                    @php
                        $containerPrefill = old('container_numbers');
                        if ($containerPrefill === null) {
                            if ($arrival->relationLoaded('containers') && $arrival->containers->count()) {
                                $containerPrefill = $arrival->containers
                                    ->map(function ($c) {
                                        $no = trim((string) ($c->container_no ?? ''));
                                        $seal = trim((string) ($c->seal_code ?? ''));
                                        return trim($no . ($seal !== '' ? (' ' . $seal) : ''));
                                    })
                                    ->filter()
                                    ->implode("\n");
                            } else {
                                $containerPrefill = (string) ($arrival->container_numbers ?? '');
                            }
                        }
                    @endphp
                    <div class="space-y-1">
                        <label for="container_numbers" class="text-sm font-medium text-slate-700">Container Numbers</label>
                        <textarea id="container_numbers" name="container_numbers" rows="4" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="1 baris = 1 container\nFormat: CONTAINER_NO [SEAL_CODE]">{{ $containerPrefill }}</textarea>
                        <p class="text-xs text-slate-500">Contoh: <span class="font-semibold">MSKU1234567 SEAL001</span> (seal optional). Kalau seal kosong, akan pakai <span class="font-semibold">Seal Code</span> di atas.</p>
                        @error('container_numbers') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="notes" class="text-sm font-medium text-slate-700">Notes</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">{{ old('notes', $arrival->notes) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('departures.show', $arrival) }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
                        <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
