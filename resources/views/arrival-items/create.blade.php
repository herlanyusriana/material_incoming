<x-app-layout>
    <x-slot name="header">
        Add Item — {{ $arrival->invoice_no ?? 'Departure' }}
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    <div class="font-semibold mb-1">Ada error:</div>
                    <ul class="list-disc ml-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-center justify-between">
                <a href="{{ route('departures.show', $arrival) }}" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 font-medium transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    <span>Back</span>
                </a>
            </div>

            <form method="POST" action="{{ route('departure-items.store', $arrival) }}" class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-6">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="material_group" class="text-sm font-medium text-slate-700">Material Group</label>
                        <input type="text" id="material_group" name="material_group" value="{{ old('material_group') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Optional">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="part_id" class="text-sm font-medium text-slate-700">Part</label>
                        <select id="part_id" name="part_id" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm" required>
                            <option value="">Select part</option>
                            @foreach ($parts as $p)
                                <option value="{{ $p->id }}" @selected((string) old('part_id') === (string) $p->id)>{{ $p->part_no }} — {{ $p->part_name_gci }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="size" class="text-sm font-medium text-slate-700">Size</label>
                        <input type="text" id="size" name="size" value="{{ old('size') }}" class="mt-1 w-full rounded-lg border-slate-300 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="e.g. 0.25 x 557 x 1203">
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <h2 class="text-xs font-semibold tracking-wide text-slate-600 uppercase mb-3">Detail Part</h2>

                    <div class="space-y-3">
                        <div class="sm:flex sm:items-center sm:gap-4">
                            <label for="qty_goods" class="text-xs font-semibold text-slate-500 sm:w-44">Qty Goods</label>
                            <input type="number" id="qty_goods" name="qty_goods" value="{{ old('qty_goods') }}" min="1" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm sm:mt-0 sm:flex-1" required>
                        </div>

                        <div class="sm:flex sm:items-center sm:gap-4">
                            <label for="unit_goods" class="text-xs font-semibold text-slate-500 sm:w-44">Unit Code</label>
                            <select id="unit_goods" name="unit_goods" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm sm:mt-0 sm:flex-1">
                                @php $unitGoods = old('unit_goods'); @endphp
                                <option value="">Pilih satuan</option>
                                <option value="EA" {{ strtoupper((string) $unitGoods) === 'EA' ? 'selected' : '' }}>EA</option>
                                <option value="ROLL" {{ strtoupper((string) $unitGoods) === 'ROLL' ? 'selected' : '' }}>ROLL</option>
                                <option value="KGM" {{ strtoupper((string) $unitGoods) === 'KGM' ? 'selected' : '' }}>KGM</option>
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
                            @php $unitBundle = old('unit_bundle'); @endphp
                            <select id="unit_bundle" name="unit_bundle" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm sm:mt-0 sm:flex-1">
                                <option value="">Pilih</option>
                                <option value="BUNDLE" {{ strtoupper((string) $unitBundle) === 'BUNDLE' ? 'selected' : '' }}>BUNDLE</option>
                                <option value="PALLET" {{ strtoupper((string) $unitBundle) === 'PALLET' ? 'selected' : '' }}>PALLET</option>
                                <option value="BOX" {{ strtoupper((string) $unitBundle) === 'BOX' ? 'selected' : '' }}>BOX</option>
                                <option value="BAG" {{ strtoupper((string) $unitBundle) === 'BAG' ? 'selected' : '' }}>BAG</option>
                                <option value="ROLL" {{ strtoupper((string) $unitBundle) === 'ROLL' ? 'selected' : '' }}>ROLL</option>
                            </select>
                        </div>

                        <div class="sm:flex sm:items-center sm:gap-4">
                            <label for="qty_bundle" class="text-xs font-semibold text-slate-500 sm:w-44">Qty Package</label>
                            <input type="number" id="qty_bundle" name="qty_bundle" value="{{ old('qty_bundle') }}" min="0" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm sm:mt-0 sm:flex-1">
                        </div>

                        <div class="sm:flex sm:items-center sm:gap-4">
                            <label class="text-xs font-semibold text-slate-500 sm:w-44">Net Weight (KGM)</label>
                            <div class="mt-1 flex w-full items-center gap-2 sm:mt-0 sm:flex-1">
                                <input type="text" inputmode="decimal" id="weight_nett" name="weight_nett" value="{{ old('weight_nett') }}" class="w-full rounded-lg border-slate-300 bg-white text-sm" required>
                                <span class="text-xs font-semibold text-slate-500 w-[56px] text-right">KGM</span>
                            </div>
                        </div>

                        <div class="sm:flex sm:items-center sm:gap-4">
                            <label class="text-xs font-semibold text-slate-500 sm:w-44">Gross Weight (KGM)</label>
                            <div class="mt-1 flex w-full items-center gap-2 sm:mt-0 sm:flex-1">
                                <input type="text" inputmode="decimal" name="weight_gross" value="{{ old('weight_gross') }}" class="w-full rounded-lg border-slate-300 bg-white text-sm" required>
                                <span class="text-xs font-semibold text-slate-500 w-[56px] text-right">KGM</span>
                            </div>
                        </div>

                        <div class="sm:flex sm:items-start sm:gap-4">
                            <div class="sm:w-44"></div>
                            <p class="weight-warning mt-1 hidden text-xs font-semibold text-red-600 sm:flex-1">
                                Net weight harus lebih kecil atau sama dengan gross weight.
                            </p>
                        </div>

                        <div class="sm:flex sm:items-start sm:gap-4">
                            <label for="total_amount" class="text-xs font-semibold text-slate-500 sm:w-44 sm:pt-2">Total Price</label>
                            <div class="mt-1 w-full sm:mt-0 sm:flex-1">
                                <input type="text" inputmode="decimal" id="total_amount" name="total_amount" value="{{ old('total_amount') }}" class="w-full rounded-lg border-blue-300 bg-white text-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <input type="hidden" name="price" id="price_display" value="">
                                <div class="mt-1 text-[11px] text-slate-500">Price otomatis = Total / Net Weight (KGM)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="notes" class="text-sm font-medium text-slate-700">Notes</label>
                    <textarea id="notes" name="notes" rows="3" class="mt-1 w-full rounded-lg border-slate-300 text-sm">{{ old('notes') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('departures.show', $arrival) }}" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 font-semibold">Cancel</a>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const nettEl = document.getElementById('weight_nett');
            const grossEl = document.querySelector('input[name="weight_gross"]');
            const warningEl = document.querySelector('.weight-warning');
            const totalEl = document.getElementById('total_amount');
            const qtyEl = document.getElementById('qty_goods');
            const priceEl = document.getElementById('price_display');

            const parse = (value) => {
                const raw = String(value ?? '').trim().replace(/,/g, '.');
                if (!raw) return null;
                const n = Number.parseFloat(raw);
                return Number.isFinite(n) ? n : null;
            };

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

            const validateWeights = () => {
                const nett = parse(nettEl?.value);
                const gross = parse(grossEl?.value);
                const ok = !(nett !== null && gross !== null && nett > gross);
                if (warningEl) warningEl.classList.toggle('hidden', ok);
                return ok;
            };

            const recalcPrice = () => {
                if (!priceEl || !totalEl) return;
                const totalCents = toCents(totalEl.value);
                const weightCenti = toCents(nettEl?.value);
                if (weightCenti > 0) {
                    priceEl.value = formatMilli(Math.floor((totalCents * 1000) / weightCenti));
                    return;
                }
                const qty = parseInt(String(qtyEl?.value ?? '0').trim() || '0', 10);
                priceEl.value = formatMilli(qty > 0 ? Math.floor((totalCents * 10) / qty) : 0);
            };

            [nettEl, grossEl].forEach((el) => {
                if (!el) return;
                el.addEventListener('input', validateWeights);
                el.addEventListener('change', validateWeights);
            });

            [nettEl, totalEl, qtyEl].forEach((el) => {
                if (!el) return;
                el.addEventListener('input', recalcPrice);
                el.addEventListener('change', recalcPrice);
            });

            validateWeights();
            recalcPrice();
        });
    </script>
</x-app-layout>
