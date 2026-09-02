{{-- Create / Edit part modal --}}
<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
    x-show="open" x-cloak @keydown.escape.window="close()">

    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 sticky top-0 bg-white z-10">
            <div class="text-sm font-semibold text-slate-900" x-text="mode === 'create' ? 'Tambah Part GCI' : 'Edit Part GCI'"></div>
            <button type="button" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50" @click="close()">&#10005;</button>
        </div>

        <form :action="formAction" method="POST" class="px-5 py-4 space-y-4" x-ref="form">
            @csrf
            <input type="hidden" name="confirm_duplicate" value="0" x-ref="confirmDuplicate">
            <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-semibold text-slate-700">Part No <span class="text-red-600">*</span></label>
                    <input name="part_no" required maxlength="100" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.part_no">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Tipe <span class="text-red-600">*</span></label>
                    <select name="classification" required class="mt-1 w-full rounded-xl border-slate-200" x-model="form.classification" @change="onClassificationChange()">
                        <option value="FG">FG (Finished Goods)</option>
                        <option value="WIP">WIP (Work in Progress)</option>
                        <option value="RM">RM (Raw Materials)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Part Name</label>
                <input name="part_name" maxlength="255" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.part_name" placeholder="Kosongkan = otomatis pakai Part No">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-semibold text-slate-700">Size</label>
                    <input name="size" maxlength="100" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.size" placeholder="e.g. 100x50x2mm">
                </div>
                <div x-show="form.classification !== 'RM'" x-cloak>
                    <label class="text-sm font-semibold text-slate-700">Model</label>
                    <input name="model" maxlength="255" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.model">
                </div>
            </div>

            {{-- RM: destination FG + vendors (both OPTIONAL — link can be added later) --}}
            <template x-if="form.classification === 'RM'">
                <div class="space-y-4 rounded-xl bg-slate-50 border border-slate-200 p-3">
                    <div class="rounded-lg bg-slate-100 border border-slate-200 px-3 py-2 text-[11px] text-slate-500 leading-snug">
                        Keduanya <span class="font-semibold text-slate-700">opsional</span> — part tetap bisa dibuat sekarang.
                        Link ke BOM/vendor bisa dilengkapi nanti lewat tombol <span class="font-semibold text-indigo-600">Edit</span> di halaman ini atau dari Parts Master.
                    </div>

                    <div x-data="{ q: '' }">
                        <label class="text-sm font-semibold text-slate-700">FG Destination (BOM) <span class="font-normal text-slate-400">— opsional</span></label>
                        <input type="text" x-model="q" placeholder="Cari FG..." class="mt-1 w-full rounded-lg border-slate-200 text-sm px-2 py-1.5">
                        <div class="mt-2 max-h-40 overflow-y-auto divide-y divide-slate-100 bg-white rounded-lg border border-slate-200">
                            @foreach ($fgPartsWithBom as $fg)
                                <label class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 cursor-pointer text-sm"
                                    x-show="!q || '{{ strtolower($fg->part_no . ' ' . $fg->part_name) }}'.includes(q.toLowerCase())">
                                    <input type="checkbox" name="destination_fg_ids[]" value="{{ $fg->id }}" class="rounded border-slate-300 text-indigo-600"
                                        :checked="form.destination_fg_ids.includes({{ $fg->id }})">
                                    <span class="font-mono font-semibold text-indigo-700">{{ $fg->part_no }}</span>
                                    <span class="text-slate-500 truncate">{{ $fg->part_name }}</span>
                                </label>
                            @endforeach
                            @if ($fgPartsWithBom->isEmpty())
                                <div class="px-3 py-3 text-xs text-slate-400 text-center">Belum ada FG yang punya BOM. Part tetap bisa dibuat; link nanti dari halaman BOM.</div>
                            @endif
                        </div>
                    </div>

                    <div x-data="{ q: '' }">
                        <label class="text-sm font-semibold text-slate-700">Vendor <span class="font-normal text-slate-400">— opsional</span></label>
                        <input type="text" x-model="q" placeholder="Cari vendor..." class="mt-1 w-full rounded-lg border-slate-200 text-sm px-2 py-1.5">
                        <div class="mt-2 max-h-40 overflow-y-auto divide-y divide-slate-100 bg-white rounded-lg border border-slate-200">
                            @foreach ($vendors as $v)
                                <label class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 cursor-pointer text-sm"
                                    x-show="!q || '{{ strtolower($v->code . ' ' . $v->name) }}'.includes(q.toLowerCase())">
                                    <input type="checkbox" name="vendor_ids[]" value="{{ $v->id }}" class="rounded border-slate-300 text-indigo-600"
                                        :checked="form.vendor_ids.includes({{ $v->id }})">
                                    <span class="font-mono font-semibold text-indigo-700">{{ $v->code }}</span>
                                    <span class="text-slate-500 truncate">{{ $v->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">
                            Part No vendor, harga, MOQ &amp; lead time dikelola di
                            <a href="{{ route('parts.index', ['classification' => 'RM']) }}" class="font-semibold text-indigo-600 hover:underline">Parts Master</a>
                            (per vendor). Di sini cukup pilih vendor saja.
                        </p>
                    </div>
                </div>
            </template>

            {{-- FG: customers --}}
            <template x-if="form.classification === 'FG'">
                <div>
                    <label class="text-sm font-semibold text-slate-700">Customer</label>
                    <div class="mt-1 max-h-40 overflow-y-auto divide-y divide-slate-100 rounded-lg border border-slate-200">
                        @foreach ($customers as $c)
                            <label class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 cursor-pointer text-sm">
                                <input type="checkbox" name="customer_ids[]" value="{{ $c->id }}" class="rounded border-slate-300 text-indigo-600"
                                    :checked="form.customer_ids.includes({{ $c->id }})">
                                <span class="font-mono font-semibold text-indigo-700">{{ $c->code }}</span>
                                <span class="text-slate-500 truncate">{{ $c->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </template>

            {{-- RM edit: substitutes --}}
            @include('planning.gci_parts._substitutes')

            <div class="grid grid-cols-2 gap-3 items-end">
                <div>
                    <label class="text-sm font-semibold text-slate-700">Status</label>
                    <select name="status" required class="mt-1 w-full rounded-xl border-slate-200" x-model="form.status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Material Policy</label>
                    <input type="hidden" name="consumption_policy" :value="form.consumption_policy">
                    <div class="mt-1 rounded-xl bg-slate-50 border border-slate-200 px-3 py-2 text-sm text-slate-600">
                        Dikelola dari <a href="{{ route('parts.index', ['classification' => 'RM']) }}" class="font-semibold text-indigo-600 hover:underline">Parts Master</a>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-1 border-t border-slate-100">
                <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-medium" @click="close()">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Simpan</button>
            </div>
        </form>
    </div>
</div>
