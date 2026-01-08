<x-app-layout>
    <x-slot name="header">
        Edit Departure Item
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 space-y-2">
                    <div class="font-semibold">Silakan periksa kembali kolom yang ditandai.</div>
                    <ul class="list-disc list-inside space-y-1 text-red-800">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Edit Item</h1>
                    <p class="text-sm text-slate-600">
                        Invoice {{ $arrival->invoice_no }} • Part {{ $item->part->part_no }}
                    </p>
                </div>
                <a href="{{ route('departures.show', $arrival) }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                    Back
                </a>
            </div>

            <form method="POST" action="{{ route('departure-items.update', $item) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <div class="text-xs font-semibold text-slate-500">Part</div>
                            <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800">
                                <div class="font-semibold">{{ $item->part->part_no }}</div>
                                <div class="text-xs text-slate-500">{{ $item->part->part_name_vendor }}</div>
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="material_group" class="text-sm font-medium text-slate-700">Material Group</label>
                            <input type="text" id="material_group" name="material_group" value="{{ old('material_group', $item->material_group) }}" class="mt-1 w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Opsional">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="size" class="text-sm font-medium text-slate-700">Size</label>
                            <input type="text" id="size" name="size" value="{{ old('size', $item->size) }}" class="mt-1 w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="e.g. 0.25 x 557 x 1203">
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <h2 class="text-xs font-semibold tracking-wide text-slate-600 uppercase mb-3">Detail Part</h2>

                        <div class="space-y-3">
                            <div class="sm:flex sm:items-center sm:gap-4">
                                <label for="qty_goods" class="text-xs font-semibold text-slate-500 sm:w-44">Qty Goods</label>
                                <input type="number" id="qty_goods" name="qty_goods" value="{{ old('qty_goods', $item->qty_goods) }}" min="1" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm sm:mt-0 sm:flex-1" required>
                            </div>

                            <div class="sm:flex sm:items-center sm:gap-4">
                                <label for="unit_goods" class="text-xs font-semibold text-slate-500 sm:w-44">Unit Code</label>
	                                <select id="unit_goods" name="unit_goods" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm sm:mt-0 sm:flex-1">
	                                    @php $unitGoods = old('unit_goods', $item->unit_goods); @endphp
	                                    <option value="">Pilih satuan</option>
	                                    <option value="PCS" {{ strtoupper((string) $unitGoods) === 'PCS' ? 'selected' : '' }}>PCS</option>
	                                    <option value="COIL" {{ strtoupper((string) $unitGoods) === 'COIL' ? 'selected' : '' }}>COIL</option>
	                                    <option value="SHEET" {{ strtoupper((string) $unitGoods) === 'SHEET' ? 'selected' : '' }}>SHEET</option>
	                                    <option value="SET" {{ strtoupper((string) $unitGoods) === 'SET' ? 'selected' : '' }}>SET</option>
	                                </select>
	                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <h2 class="text-xs font-semibold tracking-wide text-slate-600 uppercase mb-3">Detail Packaging</h2>

                        <div class="space-y-3">
                            <div class="sm:flex sm:items-center sm:gap-4">
                                <label for="unit_bundle" class="text-xs font-semibold text-slate-500 sm:w-44">Jenis Package</label>
	                                @php $unitBundle = old('unit_bundle', $item->unit_bundle); @endphp
	                                <select id="unit_bundle" name="unit_bundle" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm sm:mt-0 sm:flex-1">
	                                    <option value="">Pilih</option>
	                                    <option value="BUNDLE" {{ strtoupper((string) $unitBundle) === 'BUNDLE' ? 'selected' : '' }}>BUNDLE</option>
	                                    <option value="PALLET" {{ strtoupper((string) $unitBundle) === 'PALLET' ? 'selected' : '' }}>PALLET</option>
	                                    <option value="BOX" {{ strtoupper((string) $unitBundle) === 'BOX' ? 'selected' : '' }}>BOX</option>
	                                </select>
	                            </div>

                            <div class="sm:flex sm:items-center sm:gap-4">
                                <label for="qty_bundle" class="text-xs font-semibold text-slate-500 sm:w-44">Qty Package</label>
                                <input type="number" id="qty_bundle" name="qty_bundle" value="{{ old('qty_bundle', $item->qty_bundle) }}" min="0" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm sm:mt-0 sm:flex-1">
                            </div>

                            <div class="sm:flex sm:items-center sm:gap-4">
                                <label class="text-xs font-semibold text-slate-500 sm:w-44">Net Weight (KGM)</label>
                                <div class="mt-1 flex w-full items-center gap-2 sm:mt-0 sm:flex-1">
                                    <input type="text" inputmode="decimal" id="weight_nett" name="weight_nett" value="{{ old('weight_nett', $item->weight_nett) }}" class="w-full rounded-lg border-slate-300 bg-white text-sm" required>
                                    <span class="text-xs font-semibold text-slate-500 w-[56px] text-right">KGM</span>
                                </div>
                            </div>

                            <div class="sm:flex sm:items-center sm:gap-4">
                                <label class="text-xs font-semibold text-slate-500 sm:w-44">Gross Weight (KGM)</label>
                                <div class="mt-1 flex w-full items-center gap-2 sm:mt-0 sm:flex-1">
                                    <input type="text" inputmode="decimal" name="weight_gross" value="{{ old('weight_gross', $item->weight_gross) }}" class="w-full rounded-lg border-slate-300 bg-white text-sm" required>
                                    <span class="text-xs font-semibold text-slate-500 w-[56px] text-right">KGM</span>
                                </div>
                            </div>

                            <div class="sm:flex sm:items-start sm:gap-4">
                                <label for="total_amount" class="text-xs font-semibold text-slate-500 sm:w-44 sm:pt-2">Total Price</label>
                                <div class="mt-1 w-full sm:mt-0 sm:flex-1">
                                    <input type="text" inputmode="decimal" id="total_amount" name="total_amount" value="{{ old('total_amount', $item->total_price) }}" class="w-full rounded-lg border-blue-300 bg-white text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    <div class="mt-1 text-[11px] text-slate-500">Unit price akan dihitung otomatis = Total / Qty Goods</div>
                                </div>
                            </div>

                            <div class="sm:flex sm:items-center sm:gap-4">
                                <label class="text-xs font-semibold text-slate-500 sm:w-44">Price (auto)</label>
                                <input type="text" id="price_display" class="mt-1 w-full rounded-lg border-slate-200 bg-slate-100 text-sm sm:mt-0 sm:flex-1" value="" readonly>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="text-sm font-medium text-slate-700">Notes</label>
                        <textarea id="notes" name="notes" rows="3" class="mt-1 w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm">{{ old('notes', $item->notes) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('departures.show', $arrival) }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium text-slate-700 hover:bg-slate-50">
                            Cancel
                        </a>
                        <button type="submit" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">
                            Save Item
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const qtyEl = document.getElementById('qty_goods');
        const totalEl = document.getElementById('total_amount');
        const unitEl = document.getElementById('unit_goods');
        const nettEl = document.getElementById('weight_nett');
        const priceEl = document.getElementById('price_display');

        const toCents = (value) => {
            const raw = String(value ?? '').trim().replace(/,/g, '.');
            if (!raw) return 0;
            const parts = raw.split('.');
            const whole = (parts[0] || '0').replace(/[^\d]/g, '') || '0';
            const frac = ((parts[1] || '').replace(/[^\d]/g, '') + '00').slice(0, 2);
            return (parseInt(whole, 10) * 100) + parseInt(frac, 10);
        };

        const formatMilli = (milli) => {
            const neg = milli < 0;
            milli = Math.abs(milli);
            let s = String(milli);
            if (s.length <= 3) s = s.padStart(4, '0');
            let intPart = s.slice(0, -3).replace(/^0+/, '');
            if (!intPart) intPart = '0';
            const frac = s.slice(-3);
            return (neg ? '-' : '') + intPart + '.' + frac;
        };

        const recalc = () => {
            if (!qtyEl || !totalEl || !priceEl) return;
            const qty = parseInt(String(qtyEl.value || '0').trim() || '0', 10);
            const cents = toCents(totalEl.value);
            const unit = String(unitEl?.value ?? '').trim().toUpperCase();
            const weightCenti = toCents(nettEl?.value);
            if (weightCenti > 0) {
                const milli = Math.floor((cents * 1000) / weightCenti);
                priceEl.value = formatMilli(milli);
                return;
            }
            const milli = qty > 0 ? Math.floor((cents * 10) / qty) : 0;
            priceEl.value = formatMilli(milli);
        };

        [qtyEl, totalEl, unitEl, nettEl].forEach((el) => {
            if (!el) return;
            el.addEventListener('input', recalc);
            el.addEventListener('change', recalc);
        });

        recalc();
    });
</script>

</x-app-layout>
