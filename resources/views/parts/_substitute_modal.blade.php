{{-- Substitute edit modal (SUB tab) --}}
<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="subListEditOpen" x-cloak @keydown.escape.window="subListEditOpen = false">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
            <div class="text-sm font-semibold text-slate-900">{{ __('master.parts.substitute_modal.title') }}</div>
            <button type="button" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50" @click="subListEditOpen = false">&#10005;</button>
        </div>
        <form :action="subListEditAction" method="POST" class="px-5 py-4 space-y-3">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-slate-600">{{ __('master.parts.substitute_modal.fg') }}</label>
                    <input type="text" class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 text-sm" x-model="subListForm.fg_part_no" readonly>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">{{ __('master.parts.substitute_modal.component_rm') }}</label>
                    <input type="text" class="mt-1 w-full rounded-lg border-slate-200 bg-slate-50 text-sm" x-model="subListForm.component_part_no" readonly>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-slate-600">{{ __('master.parts.substitute_modal.sub_rm') }} <span class="text-red-600">*</span></label>
                    <select name="substitute_part_id" required class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subListForm.substitute_part_id">
                        <option value="">{{ __('master.parts.substitute_modal.select_rm') }}</option>
                        @foreach (($rmParts ?? collect()) as $rm)
                            <option value="{{ $rm->id }}">{{ $rm->part_no }} - {{ $rm->part_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">{{ __('master.parts.substitute_modal.status') }}</label>
                    <select name="status" required class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subListForm.status">
                        <option value="active">{{ __('master.parts.substitute_modal.status_active') }}</option>
                        <option value="inactive">{{ __('master.parts.substitute_modal.status_inactive') }}</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-slate-600">{{ __('master.parts.substitute_modal.ratio') }}</label>
                    <input type="number" step="0.0001" min="0.0001" name="ratio" required class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subListForm.ratio">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">{{ __('master.parts.substitute_modal.priority') }}</label>
                    <input type="number" min="1" name="priority" required class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subListForm.priority">
                </div>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600">{{ __('master.parts.substitute_modal.notes') }}</label>
                <input type="text" name="notes" class="mt-1 w-full rounded-lg border-slate-200 text-sm" x-model="subListForm.notes">
            </div>
            <div class="flex justify-end gap-2 pt-1 border-t border-slate-100">
                <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-medium" @click="subListEditOpen = false">{{ __('master.parts.substitute_modal.cancel') }}</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">{{ __('master.parts.substitute_modal.update') }}</button>
            </div>
        </form>
    </div>
</div>
