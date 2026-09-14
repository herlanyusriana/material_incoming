@php $wo = $wo ?? null; @endphp
<style>[x-cloak]{display:none !important;}</style>

<div class="py-6 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <form method="POST"
              action="{{ $wo ? route('production.wo-tracking.update', $wo) : route('production.wo-tracking.store') }}"
              class="space-y-5"
              x-data="woManualForm({
                  fgId: {{ $wo?->gci_part_id ?? 'null' }},
                  rmId: {{ $wo?->main_rm_part_id ?? 'null' }},
                  invoice: @js(old('rm_invoice_no', $wo?->rm_invoice_no ?? '')),
                  tag: @js(old('rm_tag', $wo?->rm_tag ?? ''))
              })">
            @csrf
            @if ($wo) @method('PUT') @endif

            {{-- 1. FG Part --}}
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('wo_tracking.fg_part') }} <span class="text-rose-500">*</span></label>
                <input type="text" list="fg-list" x-model="fgText" @input="pickFg()" autocomplete="off" required
                       placeholder="{{ __('wo_tracking.fg_part_ph') }}" class="w-full rounded-xl border-slate-300 text-sm">
                <input type="hidden" name="gci_part_id" :value="fgId">
                <datalist id="fg-list">
                    <template x-for="p in fgParts" :key="p.id"><option :value="p.part_no"></option></template>
                </datalist>
                <p class="text-xs text-slate-400 mt-1" x-text="fgName"></p>
                @error('gci_part_id')<p class="text-xs font-semibold text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- 2+3. Tanggal + WO Quantity --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('wo_tracking.date_label') }} <span class="text-rose-500">*</span></label>
                    <input type="date" name="start_date" value="{{ old('start_date', ($wo?->start_date ?? now())->format('Y-m-d')) }}" required class="w-full rounded-xl border-slate-300 text-sm">
                    @error('start_date')<p class="text-xs font-semibold text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('wo_tracking.wo_qty') }} <span class="text-rose-500">*</span></label>
                    <input type="number" name="qty_target" value="{{ old('qty_target', $wo?->qty_target) }}" step="0.0001" min="0.0001" required class="w-full rounded-xl border-slate-300 text-sm">
                    @error('qty_target')<p class="text-xs font-semibold text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- 4. Main RM Part (auto dari BOM, bisa diganti) --}}
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('wo_tracking.main_rm_part') }}</label>
                <input type="text" list="rm-list" x-model="rmText" @input="pickRm()" autocomplete="off"
                       placeholder="{{ __('wo_tracking.main_rm_ph') }}" class="w-full rounded-xl border-slate-300 text-sm">
                <input type="hidden" name="main_rm_part_id" :value="rmId">
                <datalist id="rm-list">
                    <template x-for="p in rmParts" :key="p.id"><option :value="p.part_no"></option></template>
                </datalist>
                <p class="text-xs text-slate-400 mt-1"><span x-text="rmName"></span> · {{ __('wo_tracking.rm_auto_hint') }}</p>
                @error('main_rm_part_id')<p class="text-xs font-semibold text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- 5+6. RM Invoice + RM Tag (dropdown dari incoming) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('wo_tracking.rm_invoice') }}</label>
                    <select x-model="invoice" @change="onInvoice()" :disabled="invoices.length === 0"
                            class="w-full rounded-xl border-slate-300 text-sm disabled:bg-slate-50 disabled:text-slate-400">
                        <option value="">{{ __('wo_tracking.please_select') }}</option>
                        <template x-for="i in invoices" :key="i"><option :value="i" x-text="i"></option></template>
                    </select>
                    <input type="hidden" name="rm_invoice_no" :value="invoice">
                    <p x-show="rmId && invoices.length === 0" x-cloak class="text-xs text-amber-600 mt-1">{{ __('wo_tracking.no_incoming') }}</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('wo_tracking.rm_tag') }}</label>
                    <select x-model="tag" :disabled="tags.length === 0"
                            class="w-full rounded-xl border-slate-300 text-sm font-mono disabled:bg-slate-50 disabled:text-slate-400">
                        <option value="">{{ __('wo_tracking.please_select') }}</option>
                        <template x-for="t in tags" :key="t"><option :value="t" x-text="t"></option></template>
                    </select>
                    <input type="hidden" name="rm_tag" :value="tag">
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold">
                    {{ $wo ? __('wo_tracking.update_btn') : __('wo_tracking.submit') }}
                </button>
                <a href="{{ route('production.wo-tracking.index') }}" class="px-6 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-sm font-bold">{{ __('wo_tracking.cancel') }}</a>
            </div>
        </form>
    </div>
</div>

<script>
    function woManualForm(init) {
        return {
            master: null,
            fgParts: [],
            rmParts: [],
            fgId: init.fgId ?? '',
            rmId: init.rmId ?? '',
            fgText: '',
            rmText: '',
            invoice: init.invoice || '',
            tag: init.tag || '',
            invoices: [],
            tags: [],
            rmEdited: Boolean(init.rmId),

            async init() {
                try {
                    const res = await fetch('{{ route('production.wo-tracking.master-data') }}');
                    this.master = await res.json();
                    this.fgParts = this.master.fg_parts || [];
                    this.rmParts = this.master.rm_parts || [];
                } catch (e) { /* list tetap kosong; validasi server yang bicara */ }
                const fg = this.byId(this.fgParts, this.fgId);
                if (fg) this.fgText = fg.part_no;
                const rm = this.byId(this.rmParts, this.rmId);
                if (rm) this.rmText = rm.part_no;
                this.buildInvoices();
                this.buildTags();
            },

            byId(list, id) {
                if (!id) return null;
                return list.find(p => String(p.id) === String(id)) || null;
            },
            byNo(list, no) {
                const t = (no || '').trim();
                if (!t) return null;
                return list.find(p => p.part_no === t) || null;
            },

            get fgName() { const f = this.byId(this.fgParts, this.fgId); return f ? f.part_name : ''; },
            get rmName() { const r = this.byId(this.rmParts, this.rmId); return r ? r.part_name : ''; },

            pickFg() {
                const f = this.byNo(this.fgParts, this.fgText);
                const newId = f ? f.id : '';
                if (String(newId) === String(this.fgId)) return;
                this.fgId = newId;
                if (f && !this.rmEdited) {
                    this.rmId = (this.master && this.master.main_rm) ? (this.master.main_rm[String(f.id)] || '') : '';
                    const rm = this.byId(this.rmParts, this.rmId);
                    this.rmText = rm ? rm.part_no : '';
                    this.rmEdited = false;
                    this.buildInvoices();
                }
            },

            pickRm() {
                const r = this.byNo(this.rmParts, this.rmText);
                const newId = r ? r.id : '';
                if (String(newId) === String(this.rmId)) return;
                this.rmEdited = true;
                this.rmId = newId;
                this.buildInvoices();
                this.buildTags();
            },

            buildInvoices() {
                this.invoices = [];
                if (this.master && this.rmId && this.master.stock) {
                    const inv = this.master.stock[String(this.rmId)];
                    this.invoices = inv ? Object.keys(inv) : [];
                }
                if (this.invoices.length && !this.invoices.includes(this.invoice)) this.invoice = '';
            },

            buildTags() {
                this.tags = [];
                if (this.master && this.rmId && this.invoice && this.master.stock) {
                    this.tags = (this.master.stock[String(this.rmId)] || {})[this.invoice] || [];
                }
                if (this.tags.length && !this.tags.includes(this.tag)) this.tag = '';
            },

            onInvoice() { this.buildTags(); },
        };
    }
</script>
