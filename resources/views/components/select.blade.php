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
        <label for="{{ $selectId }}" class="block text-sm font-medium text-gray-700">{{ $label }}@if($required)<span class="text-red-500">*</span>@endif</label>
    @endif
    <select
        id="{{ $selectId }}"
        name="{{ $name }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm']) }}
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
        <p class="text-xs text-gray-500">{{ $hint }}</p>
    @endif
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
