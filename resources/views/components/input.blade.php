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
        <label for="{{ $inputId }}" class="block text-sm font-medium text-slate-700">{{ $label }}@if($required)<span class="text-red-500">*</span>@endif</label>
    @endif
    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $fieldValue }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-xl border-slate-200 shadow-sm placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-sm transition-colors']) }}
    />
    @if ($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
