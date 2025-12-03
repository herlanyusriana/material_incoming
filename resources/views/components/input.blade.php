@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => null,
    'placeholder' => '',
    'hint' => null,
    'required' => false,
    'preserveOld' => true,
])

@php
    $inputId = $attributes->get('id') ?? $name;
    $fieldValue = $preserveOld ? old($name, $value) : ($value ?? '');
@endphp

<div class="space-y-1">
    @if ($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-700">{{ $label }}@if($required)<span class="text-red-500">*</span>@endif</label>
    @endif
    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $fieldValue }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm']) }}
    />
    @if ($hint)
        <p class="text-xs text-gray-500">{{ $hint }}</p>
    @endif
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
