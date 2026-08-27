{{-- Substitutes manager — shown when editing an RM part --}}
<template x-if="mode === 'edit' && form.classification === 'RM'">
    <div class="rounded-xl border border-orange-200 bg-orange-50/50 overflow-hidden">
        <button type="button" class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-orange-800 hover:bg-orange-100/60"
            @click="subsOpen = !subsOpen">
            <span>Substitutes</span>
            <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-orange-200 text-orange-900 text-[10px] font-bold"
                x-text="(form.substitutes_for || []).length"></span>
        </button>

        <div x-show="subsOpen" x-cloak class="px-3 pb-3 space-y-3">
            {{-- Existing substitutes --}}
            <template x-if="(form.substitutes_for || []).length">
                <table class="w-full text-xs divide-y divide-slate-100 bg-white rounded-lg border border-slate-200">
                    <thead>
                        <tr class="text-slate-400 uppercase tracking-wider text-[10px]">
                            <th class="text-left py-2 px-2">FG</th>
                            <th class="text-left py-2 px-2">Substitute</th>
                            <th class="text-right py-2 px-2">Ratio</th>
                            <th class="text-right py-2 px-2">Prio</th>
                            <th class="py-2 px-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="s in form.substitutes_for" :key="s.id">
                            <tr class="hover:bg-slate-50">
                                <td class="py-1.5 px-2 font-mono text-slate-600" x-text="s.fg_part_no"></td>
                                <td class="py-1.5 px-2"><span class="font-mono font-semibold text-indigo-700" x-text="s.substitute_part_no"></span></td>
                                <td class="py-1.5 px-2 text-right font-mono" x-text="s.ratio"></td>
                                <td class="py-1.5 px-2 text-right text-slate-500" x-text="'#' + s.priority"></td>
                                <td class="py-1.5 px-2 text-right whitespace-nowrap">
                                    <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-800" @click="editSub(s)">Edit</button>
                                    <button type="button" class="ml-2 font-semibold text-red-600 hover:text-red-800" @click="deleteSub(s)">Hapus</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </template>
            <template x-if="!(form.substitutes_for || []).length">
                <div class="text-xs text-slate-400 italic py-1">Belum ada substitute.</div>
            </template>

            {{-- Add / edit substitute --}}
            <form :action="subFormAction" method="POST" class="space-y-2 rounded-lg border border-orange-200 bg-white p-3">
                @csrf
                <template x-if="subEditId"><input type="hidden" name="_method" value="PUT"></template>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">FG (BOM) <span class="text-red-600">*</span></label>
                        <select name="fg_part_id" required class="mt-0.5 w-full rounded-lg border-slate-200 text-sm" x-model="subForm.fg_part_id" :disabled="!!subEditId">
                            <option value="">-- Pilih FG --</option>
                            @foreach ($fgPartsWithBom as $fg)
                                <option value="{{ $fg->id }}">{{ $fg->part_no }} - {{ $fg->part_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Substitute RM <span class="text-red-600">*</span></label>
                        <select name="substitute_part_id" required class="mt-0.5 w-full rounded-lg border-slate-200 text-sm" x-model="subForm.substitute_part_id">
                            <option value="">-- Pilih RM --</option>
                            @foreach ($rmParts as $rm)
                                <option value="{{ $rm->id }}" x-show="form.id != {{ $rm->id }}">{{ $rm->part_no }} - {{ $rm->part_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-2">
                    <input type="number" name="ratio" step="0.001" min="0.001" placeholder="Ratio" class="w-full rounded-lg border-slate-200 text-sm" x-model="subForm.ratio">
                    <input type="number" name="priority" min="1" placeholder="Priority" class="w-full rounded-lg border-slate-200 text-sm" x-model="subForm.priority">
                    <select name="status" class="w-full rounded-lg border-slate-200 text-sm" x-model="subForm.status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <input type="text" name="notes" maxlength="255" placeholder="Catatan (opsional)" class="w-full rounded-lg border-slate-200 text-sm" x-model="subForm.notes">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" x-show="subEditId" @click="cancelSubEdit()"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-semibold">Batal</button>
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold"
                        x-text="subEditId ? 'Update' : '+ Tambah'"></button>
                </div>
            </form>
        </div>
    </div>
</template>
