{{-- Substitutes section dalam part modal (RM edit only) --}}
<template x-if="partMode === 'edit' && partForm.classification === 'RM'">
    <div class="rounded-xl border border-orange-200 bg-orange-50/50 overflow-hidden">
        <button type="button" class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-orange-800 hover:bg-orange-100/60" @click="subsOpen = !subsOpen">
            <span>{{ __('master.parts.part_substitutes.title') }}</span>
            <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full bg-orange-200 text-orange-900 text-[10px] font-bold" x-text="(partForm.substitutes_for || []).length"></span>
        </button>

        <div x-show="subsOpen" x-cloak class="px-3 pb-3 space-y-3">
            <template x-if="(partForm.substitutes_for || []).length">
                <table class="w-full text-xs divide-y divide-slate-100 bg-white rounded-lg border border-slate-200">
                    <thead>
                        <tr class="text-slate-400 uppercase tracking-wider text-[10px]">
                            <th class="text-left py-2 px-2">{{ __('master.parts.part_substitutes.th_fg') }}</th>
                            <th class="text-left py-2 px-2">{{ __('master.parts.part_substitutes.th_substitute') }}</th>
                            <th class="text-center py-2 px-2">{{ __('master.parts.part_substitutes.th_ratio') }}</th>
                            <th class="text-center py-2 px-2">{{ __('master.parts.part_substitutes.th_prio') }}</th>
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
                                    <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-800" @click="editSub(s)">{{ __('master.parts.part_substitutes.edit') }}</button>
                                    <button type="button" class="ml-2 font-semibold text-red-600 hover:text-red-800" @click="deleteSub(s)">{{ __('master.parts.part_substitutes.delete') }}</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </template>
            <template x-if="!(partForm.substitutes_for || []).length">
                <div class="text-xs text-slate-400 italic py-1">{{ __('master.parts.part_substitutes.empty') }}</div>
            </template>

            <template x-if="(partForm.as_substitute || []).length">
                <div class="rounded-lg border border-slate-200 bg-white p-2 text-xs text-slate-600">
                    <div class="font-semibold text-slate-400 uppercase tracking-wider text-[10px] mb-1">{{ __('master.parts.part_substitutes.used_as') }}</div>
                    <template x-for="s in partForm.as_substitute" :key="s.id">
                        <div class="py-0.5"><span class="font-mono" x-text="s.fg_part_no"></span> &rarr; <span class="font-mono font-semibold text-indigo-700" x-text="s.original_rm_part_no"></span></div>
                    </template>
                </div>
            </template>

            <form x-show="partForm.id" :action="subFormAction" method="POST" class="space-y-2 rounded-lg border border-orange-200 bg-white p-3">
                @csrf
                <template x-if="subEditId"><input type="hidden" name="_method" value="PUT"></template>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">{{ __('master.parts.part_substitutes.fg_bom') }} <span class="text-red-600">*</span></label>
                        <select name="fg_part_id" required class="mt-0.5 w-full rounded-lg border-slate-200 text-sm" x-model="subForm.fg_part_id" :disabled="!!subEditId">
                            <option value="">{{ __('master.parts.part_substitutes.fg_select') }}</option>
                            <template x-for="fg in subFgOptions" :key="fg.id">
                                <option :value="String(fg.id)" x-text="fg.part_no + ' - ' + (fg.part_name || '')"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">{{ __('master.parts.part_substitutes.sub_rm') }} <span class="text-red-600">*</span></label>
                        <select name="substitute_part_id" required class="mt-0.5 w-full rounded-lg border-slate-200 text-sm" x-model="subForm.substitute_part_id">
                            <option value="">{{ __('master.parts.part_substitutes.rm_select') }}</option>
                            @foreach (($rmParts ?? collect()) as $rm)
                                <option value="{{ $rm->id }}" x-show="String({{ $rm->id }}) !== String(partForm.id || '')">{{ $rm->part_no }} - {{ $rm->part_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <input type="number" name="ratio" step="0.0001" min="0.0001" placeholder="{{ __('master.parts.part_substitutes.ratio_placeholder') }}" class="w-full rounded-lg border-slate-200 text-sm" x-model="subForm.ratio">
                    <input type="number" name="priority" min="1" placeholder="{{ __('master.parts.part_substitutes.priority_placeholder') }}" class="w-full rounded-lg border-slate-200 text-sm" x-model="subForm.priority">
                    <select name="status" class="w-full rounded-lg border-slate-200 text-sm" x-model="subForm.status">
                        <option value="active">{{ __('master.parts.index.filter_active') }}</option>
                        <option value="inactive">{{ __('master.parts.index.filter_inactive') }}</option>
                    </select>
                    <input type="text" name="notes" maxlength="255" placeholder="{{ __('master.parts.part_substitutes.notes_placeholder') }}" class="w-full rounded-lg border-slate-200 text-sm" x-model="subForm.notes">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" x-show="subEditId" @click="cancelSubEdit()" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-xs font-semibold">{{ __('master.parts.part_substitutes.cancel') }}</button>
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold" x-text="subEditId ? '{{ __('master.parts.part_substitutes.update') }}' : '{{ __('master.parts.part_substitutes.add') }}'"></button>
                </div>
            </form>
        </div>
    </div>
</template>
