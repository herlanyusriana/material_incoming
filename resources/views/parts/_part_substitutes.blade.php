{{-- Substitutes section dalam part modal (RM edit only) --}}
<template x-if="partMode === 'edit' && partForm.classification === 'RM'">
    <div class="rounded-xl border border-orange-200 bg-orange-50/50 overflow-hidden">
        <button type="button" class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-orange-800 hover:bg-orange-100/60" @click="subsOpen = !subsOpen">
            <span>Substitutes</span>
            <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-orange-200 text-orange-900 text-[10px] font-bold" x-text="(partForm.substitutes_for || []).length"></span>
        </button>

        <div x-show="subsOpen" x-cloak class="px-3 pb-3 space-y-3">
            <template x-if="(partForm.substitutes_for || []).length">
                <table class="w-full text-xs divide-y divide-slate-100 bg-white rounded-lg border border-slate-200">
                    <thead>
                        <tr class="text-slate-400 uppercase tracking-wider text-[10px]">
                            <th class="text-left py-2 px-2">FG</th>
                            <th class="text-left py-2 px-2">Substitute</th>
                            <th class="text-center py-2 px-2">Ratio</th>
                            <th class="text-center py-2 px-2">Prio</th>
                            <th class="py-2 px-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="s in partForm.substitutes_for" :key="s.id">
                            <tr class="hover:bg-slate-50">
                                <td class="py-1.5 px-2 font-mono text-slate-600" x-text="s.fg_part_no"></td>
                                <td class="py-1.5 px-2 font-mono font-semibold text-indigo-700" x-text="s.substitute_part_no"></td>
                                <td class="py-1.5 px-2 text-center font-mono" x-text="s.ratio"></td>
                                <td class="py-1.5 px-2 text-center text-slate-500" x-text="'#' + s.priority"></td>
                                <td class="py-1.5 px-2 text-right whitespace-nowrap">
                                    <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-800" @click="editSub(s)">Edit</button>
                                    <button type="button" class="ml-2 font-semibold text-red-600 hover:text-red-800" @click="deleteSub(s)">Hapus</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </template>
            <template x-if="!(partForm.substitutes_for || []).length">
                <div class="text-xs text-slate-400 italic py-1">Belum ada substitute. Management substitute bersifat per BOM FG.</div>
            </template>

            <template x-if="(partForm.as_substitute || []).length">
                <div class="rounded-lg border border-slate-200 bg-white p-2 text-xs text-slate-600">
                    <div class="font-semibold text-slate-400 uppercase tracking-wider text-[10px] mb-1">Dipakai sebagai substitute untuk</div>
                    <template x-for="s in partForm.as_substitute" :key="s.id">
                        <div class="py-0.5"><span class="font-mono" x-text="s.fg_part_no"></span> &rarr; <span class="font-mono font-semibold text-indigo-700" x-text="s.original_rm_part_no"></span></div>
                    </template>
                </div>
            </template>

            <form x-show="partForm.id" :action="subFormAction" method="POST" class="space-y-2 rounded-lg border border-orange-200 bg-white p-3">
                @csrf
                <template x-if="subEditId"><input type="hidden" name="_method" value="PUT"></template>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">FG (BOM) <span class="text-red-600">*</span></label>
                        <select name="fg_part_id" required class="mt-0.5 w-full rounded-lg border-slate-200 text-sm" x-model="subForm.fg_part_id" :disabled="!!subEditId">
                            <option value="">-- Pilih FG --</option>
                            <template x-for="fg in subFgOptions" :key="fg.id">
                                <option :value="String(fg.id)" x-text="fg.part_no + ' - ' + (fg.part_name || '')"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Substitute RM <span class="text-red-600">*</span></label>
                        <select name="substitute_part_id" required class="mt-0.5 w-full rounded-lg border-slate-200 text-sm" x-model="subForm.substitute_part_id">
                            <option value="">-- Pilih RM --</option>
                            @foreach (($rmParts ?? collect()) as $rm)
                                <option value="{{ $rm->id }}" x-show="String({{ $rm->id }}) !== String(partForm.id || '')">{{ $rm->part_no }} - {{ $rm->part_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-2">
                    <input type="number" name="ratio" step="0.0001" min="0.0001" placeholder="Ratio" class="w-full rounded-lg border-slate-200 text-sm" x-model="subForm.ratio">
                    <input type="number" name="priority" min="1" placeholder="Priority" class="w-full rounded-lg border-slate-200 text-sm" x-model="subForm.priority">
                    <select name="status" class="w-full rounded-lg border-slate-200 text-sm" x-model="subForm.status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <input type="text" name="notes" maxlength="255" placeholder="Catatan" class="w-full rounded-lg border-slate-200 text-sm" x-model="subForm.notes">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" x-show="subEditId" @click="cancelSubEdit()" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-semibold">Batal</button>
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold" x-text="subEditId ? 'Update' : '+ Tambah'"></button>
                </div>
            </form>
        </div>
    </div>
</template>
