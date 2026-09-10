{{-- Create / Edit part modal --}}
<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4"
    x-show="open" x-cloak @keydown.escape.window="close()">

    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 sticky top-0 bg-white z-10">
            <div class="text-sm font-semibold text-slate-900" x-text="mode === 'create' ? '{{ __('planning.gci_parts.form.form_add') }}' : '{{ __('planning.gci_parts.form.form_edit') }}'"></div>
            <button type="button" class="w-8 h-8 rounded-lg border border-slate-200 hover:bg-slate-50" @click="close()">&#10005;</button>
        </div>

        <form :action="formAction" method="POST" class="px-5 py-4 space-y-4" x-ref="form">
            @csrf
            <input type="hidden" name="confirm_duplicate" value="0" x-ref="confirmDuplicate">
            <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PUT"></template>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-semibold text-slate-700">{{ __('planning.gci_parts.form.part_no') }} <span class="text-red-600">*</span></label>
                    <input name="part_no" required maxlength="100" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.part_no">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">{{ __('planning.gci_parts.form.type') }} <span class="text-red-600">*</span></label>
                    <select name="classification" required class="mt-1 w-full rounded-xl border-slate-200" x-model="form.classification" @change="onClassificationChange()">
                        <option value="FG">{{ __('planning.gci_parts.form.opt_fg') }}</option>
                        <option value="WIP">{{ __('planning.gci_parts.form.opt_wip') }}</option>
                        <option value="RM">{{ __('planning.gci_parts.form.opt_rm') }}</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-sm font-semibold text-slate-700">{{ __('planning.gci_parts.form.part_name') }}</label>
                <input name="part_name" maxlength="255" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.part_name" placeholder="{{ __('planning.gci_parts.form.part_name_ph') }}">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-sm font-semibold text-slate-700">{{ __('planning.gci_parts.form.size') }}</label>
                    <input name="size" maxlength="100" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.size" placeholder="e.g. 100x50x2mm">
                </div>
                <div x-show="form.classification !== 'RM'" x-cloak>
                    <label class="text-sm font-semibold text-slate-700">{{ __('planning.gci_parts.form.model') }}</label>
                    <input name="model" maxlength="255" class="mt-1 w-full rounded-xl border-slate-200" x-model="form.model">
                </div>
            </div>

            {{-- RM: destination FG + vendors (both OPTIONAL — link can be added later) --}}
            <template x-if="form.classification === 'RM'">
                <div class="space-y-4 rounded-xl bg-slate-50 border border-slate-200 p-3">
                    <div class="rounded-lg bg-slate-100 border border-slate-200 px-3 py-2 text-[11px] text-slate-500 leading-snug">
                        {{ __('planning.gci_parts.form.rm_note_before') }} <span class="font-semibold text-slate-700">{{ __('planning.gci_parts.form.rm_note_optional') }}</span> — part tetap bisa dibuat sekarang.
                        Link ke BOM/vendor bisa dilengkapi nanti {{ __('planning.gci_parts.form.rm_note_middle') }} <span class="font-semibold text-indigo-600">{{ __('planning.gci_parts.form.rm_note_edit') }}</span> {{ __('planning.gci_parts.form.rm_note_after') }}
                    </div>

                    <div x-data="{ q: '' }">
                        <label class="text-sm font-semibold text-slate-700">{{ __('planning.gci_parts.form.fg_dest') }} <span class="font-normal text-slate-400">{{ __('planning.gci_parts.form.optional') }}</span></label>
                        <input type="text" x-model="q" placeholder="{{ __('planning.gci_parts.form.search_fg_ph') }}" class="mt-1 w-full rounded-lg border-slate-200 text-sm px-2 py-1.5">
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
                                <div class="px-3 py-3 text-xs text-slate-400 text-center">{{ __('planning.gci_parts.form.no_fg') }}</div>
                            @endif
                        </div>
                    </div>

                    <div x-data="{ q: '' }">
                        <label class="text-sm font-semibold text-slate-700">{{ __('planning.gci_parts.form.vendor_label') }} <span class="font-normal text-slate-400">{{ __('planning.gci_parts.form.optional') }}</span></label>
                        <input type="text" x-model="q" placeholder="{{ __('planning.gci_parts.form.search_vendor_ph') }}" class="mt-1 w-full rounded-lg border-slate-200 text-sm px-2 py-1.5">
                        <div class="mt-2 max-h-40 overflow-y-auto divide-y divide-slate-100 bg-white rounded-lg border border-slate-200">
                            @foreach ($vendors as $v)
                                <label class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 cursor-pointer text-sm"
                                    x-show="!q || '{{ strtolower($v->vendor_name) }}'.includes(q.toLowerCase())">
                                    <input type="checkbox" name="vendor_ids[]" value="{{ $v->id }}" class="rounded border-slate-300 text-indigo-600"
                                        :checked="form.vendor_ids.includes({{ $v->id }})">
                                    <span class="font-semibold text-indigo-700 truncate">{{ $v->vendor_name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">
                            {{ __('planning.gci_parts.form.vendor_note_before') }}
                            <a href="{{ route('parts.index', ['classification' => 'RM']) }}" class="font-semibold text-indigo-600 hover:underline">Parts Master</a>
                            {{ __('planning.gci_parts.form.vendor_note_after') }}
                        </p>
                    </div>
                </div>
            </template>

            {{-- FG: customers --}}
            <template x-if="form.classification === 'FG'">
                <div>
                    <label class="text-sm font-semibold text-slate-700">{{ __('planning.gci_parts.form.customer_label') }}</label>
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
                    <label class="text-sm font-semibold text-slate-700">{{ __('planning.gci_parts.form.status') }}</label>
                    <select name="status" required class="mt-1 w-full rounded-xl border-slate-200" x-model="form.status">
                        <option value="active">{{ __('planning.gci_parts.index.active') }}</option>
                        <option value="inactive">{{ __('planning.gci_parts.index.inactive') }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">{{ __('planning.gci_parts.form.policy_label') }}</label>
                    <input type="hidden" name="consumption_policy" :value="form.consumption_policy">
                    <div class="mt-1 rounded-xl bg-slate-50 border border-slate-200 px-3 py-2 text-sm text-slate-600">
                        {{ __('planning.gci_parts.form.policy_note') }} <a href="{{ route('parts.index', ['classification' => 'RM']) }}" class="font-semibold text-indigo-600 hover:underline">Parts Master</a>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-1 border-t border-slate-100">
                <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm font-medium" @click="close()">{{ __('planning.gci_parts.index.cancel') }}</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">{{ __('planning.gci_parts.index.save') }}</button>
            </div>
        </form>
    </div>
</div>
