@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ openCreate: false, openEditId: null }">
    <x-page-header
        :title="__('material_price.title')"
        :subtitle="__('material_price.subtitle')"
        :breadcrumbs="[
            ['label' => __('modules.purchasing'), 'url' => route('module.dashboard', 'purchasing')],
            ['label' => __('material_price.title')],
        ]"
    >
        <x-slot:actions>
            <button type="button" @click="openCreate = !openCreate"
                class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                {{ __('material_price.add') }}
            </button>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
        {{-- Create form --}}
        <div x-show="openCreate" x-cloak class="border-b border-slate-100 bg-slate-50 px-6 py-5">
            <form method="POST" action="{{ route('purchasing.material-prices.store') }}" class="grid gap-3 lg:grid-cols-6">
                @csrf
                <div class="lg:col-span-2">
                    <label for="new_gci_part_id" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.part') }}</label>
                    <select id="new_gci_part_id" name="gci_part_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                        <option value="">—</option>
                        @foreach($parts as $part)
                            <option value="{{ $part->id }}" @selected(old('gci_part_id') == $part->id)>{{ $part->part_no }} — {{ $part->part_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="new_price_type" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.type') }}</label>
                    <select id="new_price_type" name="price_type" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                        @foreach($priceTypes as $value => $labelKey)
                            <option value="{{ $value }}" @selected(old('price_type') === $value)>{{ __($labelKey) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="new_vendor_id" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.vendor') }}</label>
                    <select id="new_vendor_id" name="vendor_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        <option value="">{{ __('material_price.general') }}</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected(old('vendor_id') == $vendor->id)>{{ $vendor->vendor_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="new_currency" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.currency') }}</label>
                    <input id="new_currency" type="text" name="currency" value="{{ old('currency', 'IDR') }}" maxlength="10" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                </div>
                <div>
                    <label for="new_price" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.price') }}</label>
                    <input id="new_price" type="number" name="price" step="0.001" min="0" value="{{ old('price') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm tabular-nums" required>
                </div>
                <div>
                    <label for="new_uom" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.uom') }}</label>
                    <input id="new_uom" type="text" name="uom" value="{{ old('uom') }}" maxlength="20" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                </div>
                <div>
                    <label for="new_min_qty" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.min_qty') }}</label>
                    <input id="new_min_qty" type="number" name="min_qty" step="0.001" min="0" value="{{ old('min_qty') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm tabular-nums">
                </div>
                <div>
                    <label for="new_effective_from" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.effective_from') }}</label>
                    <input id="new_effective_from" type="date" name="effective_from" value="{{ old('effective_from', now()->toDateString()) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                </div>
                <div>
                    <label for="new_effective_to" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.effective_to') }}</label>
                    <input id="new_effective_to" type="date" name="effective_to" value="{{ old('effective_to') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                </div>
                <div>
                    <label for="new_status" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.status') }}</label>
                    <select id="new_status" name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                        <option value="active" @selected(old('status', 'active') === 'active')>{{ __('material_price.active') }}</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>{{ __('material_price.inactive') }}</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label for="new_notes" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.notes') }}</label>
                    <input id="new_notes" type="text" name="notes" value="{{ old('notes') }}" maxlength="1000" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                </div>
                <div class="lg:col-span-6 flex items-center justify-end gap-2">
                    <button type="button" @click="openCreate = false" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">{{ __('material_price.cancel') }}</button>
                    <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('material_price.save') }}</button>
                </div>
            </form>
        </div>

        {{-- Filters --}}
        <div class="border-b border-slate-100 px-6 py-4">
            <form method="GET" class="grid gap-3 lg:grid-cols-5">
                <label for="search" class="sr-only">{{ __('material_price.search') }}</label>
                <input id="search" name="search" value="{{ $filters['search'] }}" class="rounded-xl border-slate-200 text-sm lg:col-span-2" placeholder="{{ __('material_price.search_placeholder') }}">
                <select name="vendor_id" class="rounded-xl border-slate-200 text-sm">
                    <option value="">{{ __('material_price.all_vendors') }}</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected($filters['vendorId'] === $vendor->id)>{{ $vendor->vendor_name }}</option>
                    @endforeach
                </select>
                <select name="status" class="rounded-xl border-slate-200 text-sm">
                    <option value="">{{ __('material_price.all_status') }}</option>
                    <option value="active" @selected($filters['status'] === 'active')>{{ __('material_price.active') }}</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>{{ __('material_price.inactive') }}</option>
                </select>
                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex h-10 flex-1 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('material_price.search') }}</button>
                    <a href="{{ route('purchasing.material-prices.index') }}" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('material_price.reset') }}</a>
                </div>
            </form>
            <div class="mt-3 text-xs text-slate-500">{{ __('material_price.note_source') }}</div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">{{ __('material_price.part') }}</th>
                        <th class="px-4 py-3 font-semibold">{{ __('material_price.type') }}</th>
                        <th class="px-4 py-3 font-semibold">{{ __('material_price.vendor') }}</th>
                        <th class="px-4 py-3 font-semibold">{{ __('material_price.price') }}</th>
                        <th class="px-4 py-3 font-semibold">{{ __('material_price.effective_from') }}</th>
                        <th class="px-4 py-3 font-semibold">{{ __('material_price.status') }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ __('material_price.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($prices as $price)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">{{ $price->gciPart?->part_no }}</div>
                                <div class="text-xs text-slate-500">{{ $price->gciPart?->part_name }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ __($priceTypes[$price->price_type] ?? $price->price_type) }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $price->vendor?->vendor_name ?: __('material_price.general') }}</td>
                            <td class="px-4 py-3 tabular-nums">
                                <div class="font-semibold text-slate-900">{{ $price->currency }} {{ number_format((float) $price->price, 3) }}</div>
                                <div class="text-xs text-slate-500">{{ $price->uom ?: '-' }}@if($price->min_qty) | min {{ number_format((float) $price->min_qty, 3) }}@endif</div>
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                <div>{{ $price->effective_from?->format('d M Y') ?: '-' }}</div>
                                <div class="text-xs text-slate-500">→ {{ $price->effective_to?->format('d M Y') ?: '∞' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $price->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ __('material_price.' . $price->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        @click="openEditId = openEditId === {{ $price->id }} ? null : {{ $price->id }}">
                                        {{ __('material_price.update') }}
                                    </button>
                                    <form action="{{ route('purchasing.material-prices.destroy', $price) }}" method="POST" onsubmit="return confirm('{{ __('material_price.confirm_delete') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">{{ __('material_price.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr x-show="openEditId === {{ $price->id }}" x-cloak>
                            <td colspan="7" class="bg-slate-50 px-4 py-4">
                                <form action="{{ route('purchasing.material-prices.update', $price) }}" method="POST" class="grid gap-3 lg:grid-cols-6">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="gci_part_id" value="{{ $price->gci_part_id }}">
                                    <div>
                                        <label for="edit_price_type_{{ $price->id }}" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.type') }}</label>
                                        <select id="edit_price_type_{{ $price->id }}" name="price_type" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                            @foreach($priceTypes as $value => $labelKey)
                                                <option value="{{ $value }}" @selected($price->price_type === $value)>{{ __($labelKey) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="edit_vendor_id_{{ $price->id }}" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.vendor') }}</label>
                                        <select id="edit_vendor_id_{{ $price->id }}" name="vendor_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                            <option value="">{{ __('material_price.general') }}</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->id }}" @selected($price->vendor_id === $vendor->id)>{{ $vendor->vendor_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="edit_currency_{{ $price->id }}" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.currency') }}</label>
                                        <input id="edit_currency_{{ $price->id }}" type="text" name="currency" value="{{ $price->currency }}" maxlength="10" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                    </div>
                                    <div>
                                        <label for="edit_price_{{ $price->id }}" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.price') }}</label>
                                        <input id="edit_price_{{ $price->id }}" type="number" name="price" step="0.001" min="0" value="{{ $price->price }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm tabular-nums" required>
                                    </div>
                                    <div>
                                        <label for="edit_uom_{{ $price->id }}" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.uom') }}</label>
                                        <input id="edit_uom_{{ $price->id }}" type="text" name="uom" value="{{ $price->uom }}" maxlength="20" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                    </div>
                                    <div>
                                        <label for="edit_min_qty_{{ $price->id }}" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.min_qty') }}</label>
                                        <input id="edit_min_qty_{{ $price->id }}" type="number" name="min_qty" step="0.001" min="0" value="{{ $price->min_qty }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm tabular-nums">
                                    </div>
                                    <div>
                                        <label for="edit_effective_from_{{ $price->id }}" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.effective_from') }}</label>
                                        <input id="edit_effective_from_{{ $price->id }}" type="date" name="effective_from" value="{{ $price->effective_from?->toDateString() }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                    </div>
                                    <div>
                                        <label for="edit_effective_to_{{ $price->id }}" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.effective_to') }}</label>
                                        <input id="edit_effective_to_{{ $price->id }}" type="date" name="effective_to" value="{{ $price->effective_to?->toDateString() }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                    </div>
                                    <div>
                                        <label for="edit_status_{{ $price->id }}" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.status') }}</label>
                                        <select id="edit_status_{{ $price->id }}" name="status" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
                                            <option value="active" @selected($price->status === 'active')>{{ __('material_price.active') }}</option>
                                            <option value="inactive" @selected($price->status === 'inactive')>{{ __('material_price.inactive') }}</option>
                                        </select>
                                    </div>
                                    <div class="lg:col-span-3">
                                        <label for="edit_notes_{{ $price->id }}" class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ __('material_price.notes') }}</label>
                                        <input id="edit_notes_{{ $price->id }}" type="text" name="notes" value="{{ $price->notes }}" maxlength="1000" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                                    </div>
                                    <div class="lg:col-span-6 flex items-center justify-end gap-2">
                                        <button type="button" @click="openEditId = null" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-white">{{ __('material_price.cancel') }}</button>
                                        <button class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">{{ __('material_price.update') }}</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ __('material_price.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-6 py-4">{{ $prices->links() }}</div>
    </div>
</div>
@endsection
