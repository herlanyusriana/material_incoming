@php
    $wo = $wo ?? null;
    $initialDate = old('start_date', ($wo?->start_date ?? now())->format('Y-m-d'));
    $initialQty = old('qty_target', $wo?->qty_target ?? '');
@endphp

<div class="mx-auto w-full max-w-[1440px] px-4 py-6 sm:px-6 lg:px-8"
     x-data="manualWoAllocationForm({ fgId: @js(old('gci_part_id', $wo?->gci_part_id)), startDate: @js($initialDate), qty: @js($initialQty), serverErrors: @js($errors->toArray()) })">
    <form method="POST" action="{{ $wo ? route('production.wo-tracking.update', $wo) : route('production.wo-tracking.store') }}" @submit="submitting = true" class="space-y-5">
        @csrf
        @if ($wo) @method('PUT') @endif

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col justify-between gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">{{ $wo ? 'Edit Work Order' : 'Buat Work Order' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Pilih FG dan quantity. Kebutuhan BOM serta tag material dihitung otomatis.</p>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">BOM → Substitute → FIFO</span>
            </div>

            @if ($errors->any())
                <div class="mx-5 mt-5 rounded-xl border border-red-200 bg-red-50 p-4 sm:mx-6" role="alert" tabindex="-1">
                    <p class="font-semibold text-red-800">Data belum dapat disimpan.</p>
                    <p class="mt-1 text-sm text-red-700">Periksa field yang ditandai lalu coba kembali.</p>
                </div>
            @endif

            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-12">
                <div class="sm:col-span-2 lg:col-span-6">
                    <label for="fg-part" class="mb-1.5 block text-sm font-semibold text-slate-700">Part FG <span class="text-red-600">*</span></label>
                    <input id="fg-part" type="text" list="fg-list" x-model="fgText" @input.debounce.250ms="pickFg()" autocomplete="off" required placeholder="Cari part number FG..." class="h-11 w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <input type="hidden" name="gci_part_id" :value="fgId">
                    <datalist id="fg-list">
                        <template x-for="part in fgParts" :key="part.id"><option :value="part.part_no" :label="`${part.part_name || '-'} · ${part.model || 'Tanpa model'}`"></option></template>
                    </datalist>
                    <p class="mt-1.5 min-h-5 text-sm text-slate-500" x-text="selectedFg?.part_name || 'Pilih FG dari Part Master.'"></p>
                    @error('gci_part_id')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Model FG</label>
                    <output class="flex h-11 w-full items-center rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-700" x-text="selectedFg?.model || '-'">-</output>
                </div>

                <div class="lg:col-span-2">
                    <label for="wo-date" class="mb-1.5 block text-sm font-semibold text-slate-700">Tanggal <span class="text-red-600">*</span></label>
                    <input id="wo-date" type="date" name="start_date" x-model="startDate" @change="queuePreview()" required class="h-11 w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('start_date')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="lg:col-span-2">
                    <label for="wo-qty" class="mb-1.5 block text-sm font-semibold text-slate-700">WO Quantity <span class="text-red-600">*</span></label>
                    <input id="wo-qty" type="number" name="qty_target" x-model="qty" @input.debounce.400ms="queuePreview()" step="0.0001" min="0.0001" required inputmode="decimal" placeholder="0" class="h-11 w-full rounded-xl border-slate-300 text-sm tabular-nums focus:border-blue-500 focus:ring-blue-500">
                    @error('qty_target')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="allocation-heading">
            <div class="flex flex-col justify-between gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                <div>
                    <h2 id="allocation-heading" class="text-lg font-bold text-slate-900">Material Allocation</h2>
                    <p class="mt-1 text-sm text-slate-500">Hanya substitute aktif yang mempunyai stok. Tag dipilih dari penerimaan tertua.</p>
                </div>
                <div class="flex items-center gap-2" aria-live="polite">
                    <span x-show="loading" class="inline-flex items-center gap-2 text-sm font-medium text-blue-700">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z"/></svg> Menghitung
                    </span>
                    <span x-show="preview && !loading" x-cloak class="rounded-full px-3 py-1 text-xs font-semibold" :class="preview?.has_shortfall ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'" x-text="preview?.has_shortfall ? 'Ada kekurangan stok' : 'Material tersedia'"></span>
                </div>
            </div>

            <div x-show="previewError" x-cloak class="m-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:m-6" role="alert">
                <p class="font-semibold">Preview material gagal dimuat.</p><p class="mt-1" x-text="previewError"></p>
                <button type="button" @click="loadPreview()" class="mt-3 h-10 rounded-lg border border-red-300 bg-white px-4 font-semibold hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600">Coba lagi</button>
            </div>

            <div x-show="!canPreview && !previewError" class="px-5 py-12 text-center sm:px-6">
                <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3 3.75 7.5 12 12l8.25-4.5ZM3.75 12 12 16.5l8.25-4.5M3.75 16.5 12 21l8.25-4.5"/></svg>
                <p class="mt-3 font-semibold text-slate-700">Lengkapi FG, tanggal, dan quantity</p><p class="mt-1 text-sm text-slate-500">Material allocation akan muncul otomatis.</p>
            </div>

            <div x-show="preview && preview.requirements.length === 0" x-cloak class="px-5 py-10 text-center sm:px-6">
                <p class="font-semibold text-amber-800">BOM aktif tidak mempunyai kebutuhan material.</p><p class="mt-1 text-sm text-slate-500">Periksa relasi BOM FG sebelum membuat WO.</p>
            </div>

            <div x-show="preview && preview.requirements.length > 0" x-cloak>
                <div class="hidden lg:block">
                    <div class="grid grid-cols-[1.1fr_1.45fr_.65fr_1.45fr_.55fr] gap-4 border-b border-slate-200 bg-slate-50 px-6 py-3 text-xs font-bold uppercase tracking-wide text-slate-500">
                        <div>Kebutuhan BOM</div><div>Substitute berstok</div><div>Required</div><div>Rekomendasi FIFO</div><div>Status</div>
                    </div>
                    <template x-for="row in preview.requirements" :key="row.bom_item_id">
                        <div class="grid grid-cols-[1.1fr_1.45fr_.65fr_1.45fr_.55fr] gap-4 border-b border-slate-100 px-6 py-4 last:border-b-0">
                            <div class="min-w-0"><p class="font-mono text-sm font-bold text-slate-900" x-text="row.generic_part_no"></p><p class="mt-1 text-xs text-slate-500">Material utama hanya referensi BOM.</p></div>
                            <div class="min-w-0">
                                <select :name="`substitute_choices[${row.bom_item_id}]`" x-model.number="choices[row.bom_item_id]" @change="loadPreview()" :disabled="row.substitutes.length === 0" class="h-11 w-full rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 disabled:bg-slate-100">
                                    <option value="" disabled x-text="row.substitutes.length ? 'Pilih substitute' : 'Tidak ada substitute berstok'"></option>
                                    <template x-for="part in row.substitutes" :key="part.id"><option :value="part.id" x-text="`${part.part_no} · ${formatQty(part.available_qty)} ${part.uom}`"></option></template>
                                </select>
                                <template x-if="selectedSubstitute(row)"><p class="mt-2 text-xs leading-5 text-slate-500"><span x-text="selectedSubstitute(row).part_name || '-'" class="font-medium text-slate-700"></span><br><span x-text="`${selectedSubstitute(row).model || '-'} · ${selectedSubstitute(row).size || '-'} · ${selectedSubstitute(row).supplier || 'Supplier tidak tercatat'}`"></span></p></template>
                                <p x-show="fieldError(row.bom_item_id)" class="mt-1 text-xs font-medium text-red-600" x-text="fieldError(row.bom_item_id)"></p>
                            </div>
                            <div class="text-sm font-bold tabular-nums text-slate-900"><span x-text="formatQty(row.required_qty)"></span> <span class="text-xs font-medium text-slate-500" x-text="row.uom"></span></div>
                            <div>
                                <p class="mb-2 text-xs font-semibold text-slate-500">Rekomendasi FIFO</p>
                                <template x-for="pick in row.picks" :key="`${pick.tag}-${pick.location_code}`"><div class="mb-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs last:mb-0"><div class="flex justify-between gap-2"><span class="font-mono font-bold text-slate-800" x-text="pick.tag"></span><span class="font-bold tabular-nums" x-text="`${formatQty(pick.qty)} ${row.uom}`"></span></div><div class="mt-1 text-slate-500" x-text="`${pick.supplier || '-'} · ${pick.invoice_no || '-'} · ${pick.location_code}`"></div></div></template>
                                <p x-show="row.picks.length === 0" class="text-xs text-slate-500">Belum ada tag tersedia.</p>
                            </div>
                            <div><span x-show="row.shortfall <= 0" class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Cukup</span><span x-show="row.shortfall > 0" class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800" x-text="`Kurang ${formatQty(row.shortfall)}`"></span></div>
                        </div>
                    </template>
                </div>

                <div class="space-y-3 p-4 lg:hidden">
                    <template x-for="row in preview.requirements" :key="row.bom_item_id">
                        <article class="rounded-xl border border-slate-200 p-4">
                            <div class="flex items-start justify-between gap-3"><div><p class="font-mono text-sm font-bold text-slate-900" x-text="row.generic_part_no"></p><p class="mt-1 text-xs text-slate-500" x-text="`Butuh ${formatQty(row.required_qty)} ${row.uom}`"></p></div><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="row.shortfall > 0 ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'" x-text="row.shortfall > 0 ? `Kurang ${formatQty(row.shortfall)}` : 'Cukup'"></span></div>
                            <label :for="`sub-${row.bom_item_id}`" class="mt-4 block text-sm font-semibold text-slate-700">Substitute berstok</label>
                            <select :id="`sub-${row.bom_item_id}`" :name="`substitute_choices[${row.bom_item_id}]`" x-model.number="choices[row.bom_item_id]" @change="loadPreview()" :disabled="row.substitutes.length === 0" class="mt-1.5 h-11 w-full rounded-xl border-slate-300 text-sm disabled:bg-slate-100"><option value="" disabled x-text="row.substitutes.length ? 'Pilih substitute' : 'Tidak ada substitute berstok'"></option><template x-for="part in row.substitutes" :key="part.id"><option :value="part.id" x-text="`${part.part_no} · ${formatQty(part.available_qty)} ${part.uom}`"></option></template></select>
                            <template x-if="selectedSubstitute(row)"><p class="mt-2 text-xs leading-5 text-slate-500" x-text="`${selectedSubstitute(row).part_name || '-'} · ${selectedSubstitute(row).model || '-'} · ${selectedSubstitute(row).size || '-'} · ${selectedSubstitute(row).supplier || 'Supplier tidak tercatat'}`"></p></template>
                            <p class="mt-4 text-xs font-semibold text-slate-500">Rekomendasi FIFO</p>
                            <template x-for="pick in row.picks" :key="`${pick.tag}-${pick.location_code}`"><div class="mt-2 rounded-lg bg-slate-50 p-3 text-xs"><div class="flex justify-between gap-2"><span class="font-mono font-bold" x-text="pick.tag"></span><span class="font-bold" x-text="`${formatQty(pick.qty)} ${row.uom}`"></span></div><p class="mt-1 text-slate-500" x-text="`${pick.supplier || '-'} · ${pick.invoice_no || '-'} · ${pick.location_code}`"></p></div></template>
                        </article>
                    </template>
                </div>
            </div>
        </section>

        @if ($wo)<p class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">Perubahan FG atau quantity tidak mengubah reservasi material yang sudah tercatat.</p>@endif

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('production.wo-tracking.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-6 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600">Batal</a>
            <button type="submit" :disabled="submitting || loading || !preview || preview.requirements.length === 0" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 text-sm font-bold text-white hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                <svg x-show="submitting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M12 3a9 9 0 0 1 9 9h-3a6 6 0 0 0-6-6V3Z"/></svg>
                <span x-text="submitting ? 'Menyimpan...' : @js($wo ? 'Update WO' : 'Buat WO & Reservasi Material')"></span>
            </button>
        </div>
    </form>
</div>

<script>
    function manualWoAllocationForm(init) {
        return {
            fgParts: [], fgId: init.fgId || '', fgText: '', startDate: init.startDate, qty: init.qty,
            preview: null, choices: {}, loading: false, submitting: false, previewError: '', serverErrors: init.serverErrors || {}, requestNo: 0,
            async init() {
                try {
                    const response = await fetch(@js(route('production.wo-tracking.master-data')), { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error('Part Master tidak dapat dimuat.');
                    const master = await response.json(); this.fgParts = master.fg_parts || [];
                    if (this.selectedFg) this.fgText = this.selectedFg.part_no;
                    await this.loadPreview();
                } catch (error) { this.previewError = error.message || 'Terjadi gangguan jaringan.'; }
            },
            get selectedFg() { return this.fgParts.find(part => String(part.id) === String(this.fgId)) || null; },
            get canPreview() { return Boolean(this.fgId && this.startDate && Number(this.qty) > 0); },
            pickFg() {
                const value = String(this.fgText || '').trim().toUpperCase();
                const selected = this.fgParts.find(part => String(part.part_no || '').toUpperCase() === value);
                this.fgId = selected ? selected.id : ''; this.choices = {}; this.preview = null; this.queuePreview();
            },
            queuePreview() { this.preview = null; this.loadPreview(); },
            async loadPreview() {
                if (!this.canPreview) return;
                const currentRequest = ++this.requestNo; this.loading = true; this.previewError = '';
                const query = new URLSearchParams({ gci_part_id: this.fgId, start_date: this.startDate, qty_target: this.qty });
                Object.entries(this.choices).forEach(([bomItemId, partId]) => { if (partId) query.append(`substitute_choices[${bomItemId}]`, partId); });
                try {
                    const response = await fetch(`${@js(route('production.wo-tracking.allocation-preview'))}?${query}`, { headers: { Accept: 'application/json' } });
                    if (!response.ok) throw new Error(response.status === 422 ? 'FG, tanggal, atau quantity belum valid.' : 'Server tidak dapat menghitung material.');
                    const data = await response.json(); if (currentRequest !== this.requestNo) return;
                    this.preview = data; data.requirements.forEach(row => { this.choices[row.bom_item_id] = row.selected_substitute_id || ''; });
                } catch (error) { if (currentRequest === this.requestNo) this.previewError = error.message || 'Terjadi gangguan jaringan.'; }
                finally { if (currentRequest === this.requestNo) this.loading = false; }
            },
            selectedSubstitute(row) { return row.substitutes.find(part => String(part.id) === String(this.choices[row.bom_item_id])) || null; },
            fieldError(bomItemId) { return (this.serverErrors[`substitute_choices.${bomItemId}`] || [])[0] || ''; },
            formatQty(value) { return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 }).format(Number(value || 0)); },
        };
    }
</script>
