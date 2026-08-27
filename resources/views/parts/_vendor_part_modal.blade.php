{{-- Vendor part modal (RM) --}}
<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="vpModal" x-cloak @keydown.escape.window="vpModal = false">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
            <div class="text-sm font-semibold text-slate-900" x-text="vpMode === 'create' ? 'Add Vendor Part' : 'Edit Vendor Part'"></div>
            <button type="button" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50" @click="vpModal = false">&#10005;</button>
        </div>
        <form :action="vpAction" method="POST" class="px-5 py-4 space-y-4">
            @csrf
            <template x-if="vpMode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

            <div>
                <label class="text-sm font-semibold text-slate-700">Vendor <span class="text-red-600">*</span></label>
                <select name="vendor_id" required class="mt-1 w-full rounded-xl border-slate-200" x-model="vpForm.vendor_id" @change="loadVendorPartNames(vpForm.vendor_id)">
                    <option value="">Pilih vendor...</option>
                    @foreach ($vendors as $v)
                        <option value="{{ $v->id }}">{{ $v->vendor_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-semibold text-slate-700">Vendor Part No</label>
                    <input name="vendor_part_no" class="mt-1 w-full rounded-xl border-slate-200" x-model="vpForm.vendor_part_no">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Register No</label>
                    <input name="register_no" class="mt-1 w-full rounded-xl border-slate-200" x-model="vpForm.register_no">
                </div>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">Vendor Part Name</label>
                <select class="mt-1 w-full rounded-xl border-slate-200" x-model="vpForm.vendor_part_name_selected" @change="applyVendorPartNameSelection()" :disabled="!vpForm.vendor_id || vpNameLoading">
                    <option value="">Pilih nama part vendor...</option>
                    <template x-for="name in vpNameOptions" :key="name">
                        <option :value="name" x-text="name"></option>
                    </template>
                    <option value="__other__">Lainnya...</option>
                </select>
                <input type="hidden" name="vendor_part_name" :value="vpForm.vendor_part_name">
                <div x-show="vpForm.vendor_part_name_selected === '__other__' || (!vpNameOptions.length && vpForm.vendor_id)" x-cloak class="mt-2">
                    <input class="w-full rounded-xl border-slate-200" placeholder="Isi nama part vendor manual" x-model="vpForm.vendor_part_name">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-semibold text-slate-700">UOM</label>
                    <input name="uom" class="mt-1 w-full rounded-xl border-slate-200" placeholder="PCS" x-model="vpForm.uom">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">HS Code</label>
                    <input name="hs_code" class="mt-1 w-full rounded-xl border-slate-200" x-model="vpForm.hs_code">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-semibold text-slate-700">Quality Inspection</label>
                    <select name="quality_inspection" class="mt-1 w-full rounded-xl border-slate-200" x-model="vpForm.quality_inspection">
                        <option value="">No</option>
                        <option value="YES">Yes</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Status</label>
                    <select name="status" class="mt-1 w-full rounded-xl border-slate-200" x-model="vpForm.status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <p class="text-xs text-slate-400">Harga diatur di <a href="{{ route('pricing.index') }}" class="font-semibold text-indigo-600 hover:underline">Pricing Master</a>.</p>

            <div class="flex justify-end gap-2 pt-1 border-t border-slate-100">
                <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-medium" @click="vpModal = false">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Simpan</button>
            </div>
        </form>
    </div>
</div>
