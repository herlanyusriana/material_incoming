{{-- Part create/edit modal (termasuk substitutes RM & subcount FG/WIP) --}}
<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="partModal" x-cloak @keydown.escape.window="partModal = false">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 sticky top-0 bg-white z-10">
            <div class="text-sm font-semibold text-slate-900" x-text="partMode === 'create' ? 'Add Part' : 'Edit Part'"></div>
            <button type="button" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50" @click="partModal = false">&#10005;</button>
        </div>

        <form :action="partAction" method="POST" class="px-5 py-4 space-y-5" x-ref="partForm">
            @csrf
            <template x-if="partMode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

            {{-- Identitas (selalu tampil) --}}
            <section>
                <h3 class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2">Identitas Part</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="part_no" class="text-sm font-semibold text-slate-700">Part No <span class="text-red-600">*</span></label>
                        <input id="part_no" name="part_no" required class="mt-1 w-full rounded-lg border-slate-300" x-model="partForm.part_no">
                    </div>
                    <div>
                        <label for="classification" class="text-sm font-semibold text-slate-700">Tipe <span class="text-red-600">*</span></label>
                        <select id="classification" name="classification" required class="mt-1 w-full rounded-lg border-slate-300" x-model="partForm.classification">
                            <option value="FG">FG</option>
                            <option value="WIP">WIP</option>
                            <option value="RM">RM</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <label for="part_name" class="text-sm font-semibold text-slate-700">Part Name</label>
                    <input id="part_name" name="part_name" class="mt-1 w-full rounded-lg border-slate-300" x-model="partForm.part_name">
                </div>
                <div class="grid grid-cols-2 gap-3 mt-3">
                    <div>
                        <label for="size" class="text-sm font-semibold text-slate-700">Size</label>
                        <input id="size" name="size" class="mt-1 w-full rounded-lg border-slate-300" placeholder="e.g. 100x50x2mm" x-model="partForm.size">
                    </div>
                    <div x-show="partForm.classification !== 'RM'" x-cloak>
                        <label for="model" class="text-sm font-semibold text-slate-700">Model</label>
                        <input id="model" name="model" class="mt-1 w-full rounded-lg border-slate-300" x-model="partForm.model">
                    </div>
                </div>
            </section>

            {{-- Kebijakan & status (selalu tampil, ringkas) --}}
            <section>
                <h3 class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2">Material & Status</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="consumption_policy" class="text-sm font-semibold text-slate-700">Material Policy</label>
                        <select id="consumption_policy" name="consumption_policy" class="mt-1 w-full rounded-lg border-slate-300" x-model="partForm.consumption_policy">
                            <option value="direct_issue">Pakai Habis</option>
                            <option value="backflush_return">Balik Sisa</option>
                            <option value="backflush_line_stock">Simpan di Line</option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="text-sm font-semibold text-slate-700">Status</label>
                        <select id="status" name="status" class="mt-1 w-full rounded-lg border-slate-300" x-model="partForm.status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </section>

            {{-- RM: Vendor (collapsible, progressive disclosure) --}}
            <template x-if="partForm.classification === 'RM'">
                <section x-data="{ open: false, q: '' }" class="rounded-xl border border-slate-200 overflow-hidden">
                    <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-3.5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <span>Vendor</span>
                        <span class="inline-flex items-center gap-2">
                            <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold" x-text="(partForm.vendor_ids || []).length"></span>
                            <svg :class="open && 'rotate-180'" class="h-4 w-4 text-slate-400 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div x-show="open" x-cloak class="px-3.5 pb-3 space-y-2">
                        <label class="sr-only">Cari vendor</label>
                        <input type="text" x-model="q" placeholder="Cari vendor..." class="w-full rounded-lg border-slate-300 text-sm px-2.5 py-1.5">
                        <div class="max-h-40 overflow-y-auto divide-y divide-slate-100 rounded-lg border border-slate-200">
                            @foreach ($vendors as $v)
                                <label class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 cursor-pointer text-sm" x-show="!q || vendorMatches(@js($v->vendor_name), q)">
                                    <input type="checkbox" name="vendor_ids[]" value="{{ $v->id }}" class="rounded border-slate-300 text-indigo-600" :checked="partForm.vendor_ids.includes({{ $v->id }})">
                                    <span class="text-slate-700">{{ $v->vendor_name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-[11px] text-slate-400">Tandai vendor yang memasok part ini.</p>
                    </div>
                </section>
            </template>

            {{-- FG/WIP: Customer (collapsible) --}}
            <template x-if="partForm.classification === 'FG' || partForm.classification === 'WIP'">
                <section x-data="{ open: false }" class="rounded-xl border border-slate-200 overflow-hidden">
                    <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-3.5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <span>Customer</span>
                        <span class="inline-flex items-center gap-2">
                            <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold" x-text="(partForm.customer_ids || []).length"></span>
                            <svg :class="open && 'rotate-180'" class="h-4 w-4 text-slate-400 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div x-show="open" x-cloak class="px-3.5 pb-3">
                        <div class="max-h-40 overflow-y-auto divide-y divide-slate-100 rounded-lg border border-slate-200">
                            @foreach ($customers as $c)
                                <label class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 cursor-pointer text-sm">
                                    <input type="checkbox" name="customer_ids[]" value="{{ $c->id }}" class="rounded border-slate-300 text-indigo-600" :checked="(partForm.customer_ids || []).includes({{ $c->id }})">
                                    <span class="font-mono font-semibold text-indigo-700">{{ $c->code }}</span>
                                    <span class="text-slate-500 truncate">{{ $c->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-[11px] text-slate-400">Tandai customer yang memakai part ini.</p>
                    </div>
                </section>
            </template>

            {{-- FG/WIP: Subcount (collapsible) --}}
            <template x-if="partForm.classification === 'FG' || partForm.classification === 'WIP'">
                <section x-data="{ open: false }" class="rounded-xl border border-slate-200 overflow-hidden">
                    <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-3.5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <span>Subcount</span>
                        <span class="inline-flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold" :class="partForm.subcount_enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400'" x-text="partForm.subcount_enabled ? 'AKTIF' : 'NONAKTIF'"></span>
                            <svg :class="open && 'rotate-180'" class="h-4 w-4 text-slate-400 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div x-show="open" x-cloak class="px-3.5 pb-3 space-y-3">
                        <label class="flex items-center justify-between text-sm">
                            <span class="font-semibold text-slate-700">Aktifkan subcount</span>
                            <span class="inline-flex items-center gap-2 text-slate-600">
                                <input type="hidden" name="subcount_enabled" value="0">
                                <input type="checkbox" name="subcount_enabled" value="1" class="rounded border-slate-300 text-indigo-600" x-model="partForm.subcount_enabled">
                                Aktif
                            </span>
                        </label>
                        <div class="grid grid-cols-2 gap-3" x-show="partForm.subcount_enabled" x-cloak>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">FG / Parent</label>
                                <select name="subcount_fg_part_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm" x-model="partForm.subcount_fg_part_id">
                                    <option value="">Part ini sebagai parent</option>
                                    <template x-for="p in filteredSubcountParentOptions" :key="p.id">
                                        <option :value="p.id" x-text="`${p.part_no} - ${p.part_name || ''} (${p.classification})`"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label for="subcount_rm_part_id" class="text-xs font-semibold text-slate-600">RM / Asal</label>
                                <select id="subcount_rm_part_id" name="subcount_rm_part_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm" x-model="partForm.subcount_rm_part_id">
                                    <option value="">Part ini sendiri</option>
                                    <template x-for="p in filteredSubcountSourceOptions" :key="p.id">
                                        <option :value="p.id" x-text="`${p.part_no} - ${p.part_name || ''} (${p.classification})`"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label for="subcount_uom" class="text-xs font-semibold text-slate-600">UOM</label>
                                <input id="subcount_uom" name="subcount_uom" class="mt-1 w-full rounded-lg border-slate-300 text-sm" x-model="partForm.subcount_uom">
                            </div>
                            <div>
                                <label for="subcount_process_type" class="text-xs font-semibold text-slate-600">Process</label>
                                <input id="subcount_process_type" name="subcount_process_type" class="mt-1 w-full rounded-lg border-slate-300 text-sm" x-model="partForm.subcount_process_type">
                            </div>
                        </div>
                    </div>
                </section>
            </template>

            {{-- RM edit: substitutes (accordion bawaan) --}}
            @include('parts._part_substitutes')

            <div class="flex justify-end gap-2 pt-1 border-t border-slate-100">
                <button type="button" class="px-4 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-sm font-medium" @click="partModal = false">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Simpan</button>
            </div>
        </form>
    </div>
</div>
