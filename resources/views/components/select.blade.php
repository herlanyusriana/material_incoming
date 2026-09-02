@props([
    'label' => null,
    'name',
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'required' => false,
    'preserveOld' => true,
])

@php
    $selectId = $attributes->get('id') ?? $name;
    $selectedValue = $preserveOld ? old($name, $value) : ($value ?? '');
@endphp

<div class="space-y-1">
    @if ($label)
        <label for="{{ $selectId }}" class="block text-sm font-medium text-slate-700">{{ $label }}@if($required)<span class="text-red-500">*</span>@endif</label>
    @endif
    <select
        id="{{ $selectId }}"
        name="{{ $name }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-xl border-slate-200 shadow-sm placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm transition-colors']) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $selectedValue === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
    @if ($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
