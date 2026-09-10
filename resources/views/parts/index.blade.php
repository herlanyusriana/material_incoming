<x-app-layout>
    <x-slot name="header">{{ __('master.parts.index.title') }}</x-slot>

    @php
        $activeTab = $classification ?? 'RM';
        $tabQuery = array_filter([
            'status' => $status ?? '',
            'q' => $search ?? '',
            'vendor_id' => $vendorId ?? null,
            'vendor_part_name' => $vendorPartName ?? '',
            'consumption_policy' => $consumptionPolicy ?? '',
            'policy_confirmation' => $policyConfirmation ?? '',
        ], fn ($value) => $value !== null && $value !== '');
    @endphp

    <div class="space-y-4" x-data="partsMaster()" x-cloak>

        {{-- Tabs --}}
        <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-xl p-1 w-fit">
            @foreach (['RM' => __('master.parts.index.tab_rm'), 'FG' => __('master.parts.index.tab_fg'), 'WIP' => __('master.parts.index.tab_wip'), 'SUB' => __('master.parts.index.tab_sub')] as $key => $label)
                <a href="{{ route('parts.index', array_merge(['classification' => $key], $tabQuery)) }}"
                    class="px-4 py-1.5 text-sm font-medium rounded-lg transition-colors {{ $activeTab === $key ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Toolbar --}}
        <div class="bg-white border border-slate-200 rounded-xl divide-y divide-slate-100">
            <div class="p-3">
                <form method="GET" action="{{ route('parts.index') }}" x-ref="filterForm" class="flex flex-wrap items-end gap-3">
                    <input type="hidden" name="classification" value="{{ $activeTab }}">
                    <label class="flex flex-col gap-1">
                        <span class="text-xs font-medium text-slate-500">{{ __('master.parts.index.filter_status') }}</span>
                        <select name="status" class="rounded-lg border-slate-300 text-sm py-2" @change="$refs.filterForm.requestSubmit()">
                            <option value="">{{ __('master.parts.index.filter_all_status') }}</option>
                            <option value="active" @selected(($status ?? '') === 'active')>{{ __('master.parts.index.filter_active') }}</option>
                            <option value="inactive" @selected(($status ?? '') === 'inactive')>{{ __('master.parts.index.filter_inactive') }}</option>
                        </select>
                    </label>
                    @if ($activeTab === 'RM')
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-medium text-slate-500">{{ __('master.parts.index.filter_vendor') }}</span>
                            <select name="vendor_id" class="rounded-lg border-slate-300 text-sm py-2 max-w-44" @change="$refs.filterForm.requestSubmit()">
                                <option value="">{{ __('master.parts.index.filter_all_vendor') }}</option>
                                @foreach ($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected((int) ($vendorId ?? 0) === (int) $vendor->id)>{{ $vendor->vendor_name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-medium text-slate-500">{{ __('master.parts.index.filter_vendor_part_name') }}</span>
                            <input type="text" name="vendor_part_name" value="{{ $vendorPartName ?? '' }}" placeholder="{{ __('master.parts.index.filter_vendor_part_placeholder') }}"
                                class="rounded-lg border-slate-300 text-sm py-2 w-44">
                        </label>
                    @endif
                    @if ($activeTab !== 'SUB')
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-medium text-slate-500">{{ __('master.parts.index.filter_policy') }}</span>
                            <select name="consumption_policy" class="rounded-lg border-slate-300 text-sm py-2" @change="$refs.filterForm.requestSubmit()">
                                <option value="">{{ __('master.parts.index.filter_all_policy') }}</option>
                                <option value="direct_issue" @selected(($consumptionPolicy ?? '') === 'direct_issue')>{{ __('master.parts.index.policy_direct') }}</option>
                                <option value="backflush_return" @selected(($consumptionPolicy ?? '') === 'backflush_return')>{{ __('master.parts.index.policy_return') }}</option>
                                <option value="backflush_line_stock" @selected(($consumptionPolicy ?? '') === 'backflush_line_stock')>{{ __('master.parts.index.policy_line') }}</option>
                            </select>
                        </label>
                        <label class="flex flex-col gap-1">
                            <span class="text-xs font-medium text-slate-500">{{ __('master.parts.index.filter_confirmation') }}</span>
                            <select name="policy_confirmation" class="rounded-lg border-slate-300 text-sm py-2" @change="$refs.filterForm.requestSubmit()">
                                <option value="">{{ __('master.parts.index.filter_all_confirmation') }}</option>
                                <option value="confirmed" @selected(($policyConfirmation ?? '') === 'confirmed')>{{ __('master.parts.index.filter_confirmed') }}</option>
                                <option value="unconfirmed" @selected(($policyConfirmation ?? '') === 'unconfirmed')>{{ __('master.parts.index.filter_unconfirmed') }}</option>
                            </select>
                        </label>
                    @endif
                    <label class="flex-1 min-w-44">
                        <span class="sr-only">{{ __('master.parts.index.search_sr') }}</span>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 17a6 6 0 100-12 6 6 0 000 12z"/></svg>
                            <input type="search" name="q" value="{{ $search ?? '' }}" placeholder="{{ __('master.parts.index.search_placeholder') }}"
                                class="rounded-lg border-slate-300 text-sm py-2 pl-9 pr-3 w-full">
                        </div>
                    </label>
                    <button type="submit" class="px-3.5 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-sm font-medium text-slate-700">{{ __('master.parts.index.filter_button') }}</button>
                </form>
            </div>

            <div class="flex flex-wrap items-center gap-2 px-3 py-2.5">
                @if ($activeTab !== 'SUB')
                    <span class="text-xs text-slate-400 mr-1">{{ __('master.parts.index.actions') }}</span>
                    <a href="{{ route('parts.export', ['classification' => $activeTab]) }}"
                        class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-sm font-medium text-slate-700">{{ __('master.parts.index.export') }}</a>
                    <button type="button" @click="importOpen = true"
                        class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-sm font-medium text-slate-700">{{ __('master.parts.index.import') }}</button>
                    <button type="button" @click="openCreateSubcountPart()"
                        class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-sm font-medium text-slate-700">{{ __('master.parts.index.add_subcount') }}</button>
                    <div class="flex-1"></div>
                    <button type="button" @click="openCreatePart()"
                        class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">{{ __('master.parts.index.add_part') }}</button>
                @else
                    <span class="text-xs text-slate-400 mr-1">{{ __('master.parts.index.actions') }}</span>
                    <a href="{{ route('planning.boms.substitutes.export') }}"
                        class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-sm font-medium text-slate-700">{{ __('master.parts.index.export') }}</a>
                    <a href="{{ route('planning.boms.substitutes.template') }}"
                        class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-sm font-medium text-slate-700">{{ __('master.parts.index.template') }}</a>
                    <div class="flex-1"></div>
                    <button type="button" @click="subImportOpen = true"
                        class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">{{ __('master.parts.index.import_substitute') }}</button>
                @endif
            </div>
        </div>

        {{-- Bulk policy (muncul saat ada part dipilih) --}}
        @if ($activeTab !== 'SUB')
            <form method="POST" action="{{ route('parts.bulk-policy') }}" x-show="selectedPartIds.length" x-cloak
                class="flex flex-wrap items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5"
                @submit="if (!selectedPartIds.length) $event.preventDefault()">
                @csrf
                <input type="hidden" name="classification" value="{{ $activeTab }}">
                <input type="hidden" name="status" value="{{ $status ?? '' }}">
                <input type="hidden" name="q" value="{{ $search ?? '' }}">
                <input type="hidden" name="vendor_id" value="{{ $vendorId ?? '' }}">
                <input type="hidden" name="vendor_part_name" value="{{ $vendorPartName ?? '' }}">
                <input type="hidden" name="consumption_policy_filter" value="{{ $consumptionPolicy ?? '' }}">
                <input type="hidden" name="policy_confirmation" value="{{ $policyConfirmation ?? '' }}">
                <template x-for="id in selectedPartIds" :key="'bulk-' + id">
                    <input type="hidden" name="part_ids[]" :value="id">
                </template>
                <span class="text-sm text-slate-700" x-text="@js(__('master.parts.index.bulk_text', ['count' => '__COUNT__'])).replace('__COUNT__', selectedPartIds.length)"></span>
                <select name="consumption_policy" class="rounded-lg border-slate-200 text-sm">
                    <option value="direct_issue">{{ __('master.parts.index.policy_direct') }}</option>
                    <option value="backflush_return" selected>{{ __('master.parts.index.policy_return') }}</option>
                    <option value="backflush_line_stock">{{ __('master.parts.index.policy_line') }}</option>
                </select>
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold">{{ __('master.parts.index.bulk_apply') }}</button>
            </form>
        @endif

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Tables --}}
        @if ($activeTab === 'RM')
            @include('parts._table_rm')
        @elseif ($activeTab === 'FG')
            @include('parts._table_fg')
        @elseif ($activeTab === 'WIP')
            @include('parts._table_wip')
        @else
            @include('parts._table_sub')
        @endif

        @include('parts._part_modal')
        @include('parts._substitute_modal')
        @include('parts._import_modals')
    </div>

    @push('scripts')
        <script>
            window.__PARTS_MASTER__ = {
                activeTab: @js($activeTab),
                visiblePartIds: @js(($activeTab !== 'SUB' && isset($parts)) ? $parts->pluck('id')->map(fn ($id) => (string) $id)->values()->all() : []),
                routes: {
                    store: @js(route('parts.store')),
                    parts: @js(url('/parts')),
                    substitutes: @js(url('/planning/gci-part-substitutes')),
                    vendors: @js(url('/vendors')),
                },
                maps: {
                    partVendor: @js($partVendorMap ?? []),
                    substitutesFor: @js($partSubstitutesMap ?? []),
                    asSubstitute: @js($partAsSubstituteMap ?? []),
                    rmFg: @js($rmFgMap ?? []),
                    fgPartsWithBom: @js(($fgPartsWithBom ?? collect())->map(fn ($p) => ['id' => (string) $p->id, 'part_no' => $p->part_no, 'part_name' => $p->part_name])->values()->all()),
                },
                options: {
                    subcountParents: @js(($subcountParentParts ?? collect())->values()->map(fn ($p) => ['id' => (string) $p->id, 'part_no' => $p->part_no, 'part_name' => $p->part_name, 'classification' => $p->classification])->all()),
                    subcountSources: @js(($subcountSourceParts ?? collect())->values()->map(fn ($p) => ['id' => (string) $p->id, 'part_no' => $p->part_no, 'part_name' => $p->part_name, 'classification' => $p->classification])->all()),
                },
            };
        </script>
        <script src="{{ asset('js/parts/parts-master.js') }}"></script>
    @endpush
</x-app-layout>
