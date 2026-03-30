<x-app-layout>
    <x-slot name="header">
        {{ ($customsOnly ?? false) ? 'Edit Dokumen Import' : 'Edit Departure' }}
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
                    <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                        {{ ($customsOnly ?? false) ? 'Dokumen Import' : 'Departure ' . ($arrival->invoice_no ?? 'Edit') }}
                        @if (!($customsOnly ?? false) && $arrival->purchaseOrder)
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-100 text-indigo-700"
                                title="Auto-generated from PO">
                                PO: {{ $arrival->purchaseOrder->po_number }}
                            </span>
                        @endif
                    </h2>
                    <p class="text-sm text-slate-500">
                        {{ ($customsOnly ?? false)
                            ? 'Isi atau koreksi No PEN, Tanggal No PEN, dan No AJU untuk invoice import yang sudah complete receive.'
                            : 'Edit informasi utama departure. Items & receive records tidak diubah dari halaman ini.' }}
                    </p>
                </div>

                <form method="POST" action="{{ route('departures.update', $arrival) }}" class="space-y-6"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @if ($customsOnly ?? false)
                        <input type="hidden" name="customs_only" value="1">
                    @endif

                    @if ($customsOnly ?? false)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-slate-700">Invoice No.</label>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                    {{ $arrival->invoice_no ?? '-' }}
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-slate-700">Vendor</label>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                    {{ $arrival->vendor->vendor_name ?? '-' }}
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-sm font-medium text-slate-700">Invoice Date</label>
                                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                    {{ optional($arrival->invoice_date)->format('Y-m-d') ?? '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @else
                        <div class="space-y-1">
                            <label for="invoice_no" class="text-sm font-medium text-slate-700">Invoice No.</label>
                            <input type="text" id="invoice_no" name="invoice_no"
                                value="{{ old('invoice_no', $arrival->invoice_no) }}"
                                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                required>
                        </div>

                        <div class="space-y-1">
                            <label for="invoice_date" class="text-sm font-medium text-slate-700">Invoice Date</label>
                            <input type="date" id="invoice_date" name="invoice_date"
                                value="{{ old('invoice_date', optional($arrival->invoice_date)->format('Y-m-d')) }}"
                                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                required>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-1">
                                <label for="etd" class="text-sm font-medium text-slate-700">ETD</label>
                                <input type="date" id="etd" name="etd"
                                    value="{{ old('etd', optional($arrival->ETD)->format('Y-m-d')) }}"
                                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <div class="space-y-1">
                                <label for="eta" class="text-sm font-medium text-slate-700">ETA JKT</label>
                                <input type="date" id="eta" name="eta"
                                    value="{{ old('eta', optional($arrival->ETA)->format('Y-m-d')) }}"
                                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <div class="space-y-1">
                                <label for="eta_gci" class="text-sm font-medium text-slate-700">ETA GCI</label>
                                <input type="date" id="eta_gci" name="eta_gci"
                                    value="{{ old('eta_gci', optional($arrival->ETA_GCI)->format('Y-m-d')) }}"
                                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                @error('eta_gci') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-1">
                                <label for="vessel" class="text-sm font-medium text-slate-700">Vessel</label>
                                <input type="text" id="vessel" name="vessel" value="{{ old('vessel', $arrival->vessel) }}"
                                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <div class="space-y-1">
                                <label for="bl_no" class="text-sm font-medium text-slate-700">Bill of Lading</label>
                                <input type="text" id="bl_no" name="bl_no"
                                    value="{{ old('bl_no', $arrival->bill_of_lading) }}"
                                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                @error('bl_no') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="space-y-1">
                                <label for="price_term" class="text-sm font-medium text-slate-700">Price Term</label>
                                <input type="text" id="price_term" name="price_term"
                                    value="{{ old('price_term', $arrival->price_term) }}" placeholder="FOB / CIF / EXW"
                                    class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                @error('price_term') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @endif
                        <div class="space-y-1">
                            <label for="pen_no" class="text-sm font-medium text-slate-700">Nomor PEN</label>
                            <input type="text" id="pen_no" name="pen_no"
                                value="{{ old('pen_no', $arrival->pen_no) }}"
                                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @error('pen_no') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1">
                            <label for="pen_date" class="text-sm font-medium text-slate-700">Tanggal No PEN</label>
                            <input type="date" id="pen_date" name="pen_date"
                                value="{{ old('pen_date', optional($arrival->pen_date)->format('Y-m-d')) }}"
                                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @error('pen_date') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1">
                            <label for="aju_no" class="text-sm font-medium text-slate-700">Nomor AJU</label>
                            <input type="text" id="aju_no" name="aju_no"
                                value="{{ old('aju_no', $arrival->aju_no) }}"
                                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @error('aju_no') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    @if (!($customsOnly ?? false))
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <label for="bl_status" class="text-sm font-medium text-slate-700">Bill of Lading
                                Status</label>
                            <select id="bl_status" name="bl_status"
                                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">-</option>
                                <option value="draft" {{ old('bl_status', $arrival->bill_of_lading_status) === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="surrender" {{ old('bl_status', $arrival->bill_of_lading_status) === 'surrender' ? 'selected' : '' }}>Surrender
                                </option>
                            </select>
                            @error('bl_status') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1 md:col-span-2">
                            <label for="bl_file" class="text-sm font-medium text-slate-700">Upload Bill of Lading
                                (PDF/JPG/PNG)</label>
                            <input type="file" id="bl_file" name="bl_file" accept="application/pdf,image/*"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            <div class="text-xs text-slate-500">
                                @if ($arrival->bill_of_lading_file_url)
                                    File sekarang:
                                    <a class="text-blue-600 hover:underline" href="{{ $arrival->bill_of_lading_file_url }}"
                                        target="_blank" rel="noopener">Download / View</a>
                                @else
                                    Belum ada file.
                                @endif
                            </div>
                            @error('bl_file') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="rounded-lg border border-blue-100 bg-blue-50/50 p-4 space-y-2">
                        <div class="flex items-center gap-2 text-blue-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span class="text-xs font-bold uppercase tracking-wider">Automated HS Code</span>
                        </div>
                        <p class="text-xs text-blue-600 leading-relaxed">
                            HS Code akan di-generate otomatis berdasarkan Master Part dari setiap item yang Anda
                            tambahkan ke departure ini. Sistem akan menggabungkan nomor HS yang sama menjadi satu.
                        </p>
                    </div>

                    <div class="space-y-1">
                        <label for="currency" class="text-sm font-medium text-slate-700">Currency</label>
                        <input type="text" id="currency" name="currency"
                            value="{{ old('currency', $arrival->currency) }}"
                            class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="port_of_loading" class="text-sm font-medium text-slate-700">Port of
                                Loading</label>
                            <input type="text" id="port_of_loading" name="port_of_loading"
                                value="{{ old('port_of_loading', $arrival->port_of_loading) }}"
                                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>
                        <div class="space-y-1">
                            <label for="seal_code" class="text-sm font-medium text-slate-700">Seal Code</label>
                            <input type="text" id="seal_code" name="seal_code"
                                value="{{ old('seal_code', $arrival->seal_code) }}"
                                class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
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
                        <label for="container_numbers" class="text-sm font-medium text-slate-700">Container
                            Numbers</label>
                        <textarea id="container_numbers" name="container_numbers" rows="4"
                            class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="1 baris = 1 container\nFormat: CONTAINER_NO [SEAL_CODE]">{{ $containerPrefill }}</textarea>
                        <p class="text-xs text-slate-500">Contoh: <span class="font-semibold">MSKU1234567 SEAL001</span>
                            (seal optional). Kalau seal kosong, akan pakai <span class="font-semibold">Seal Code</span>
                            di atas.</p>
                        @error('container_numbers') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="notes" class="text-sm font-medium text-slate-700">Notes</label>
                        <textarea id="notes" name="notes" rows="3"
                            class="w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">{{ old('notes', $arrival->notes) }}</textarea>
                    </div>
                    @endif

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ ($customsOnly ?? false) ? route('receives.completed.invoice', $arrival) : route('departures.show', $arrival) }}"
                            class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">Back</a>
                        <button type="submit"
                            class="px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">Save
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
