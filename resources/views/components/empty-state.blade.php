@props(['title' => null, 'text' => null, 'icon' => 'sparkles'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/50 px-6 py-10 text-center']) }}>
    <x-icon :name="$icon" class="h-8 w-8 text-slate-300" />
    @if ($title)
        <p class="mt-3 text-sm font-semibold text-slate-700">{{ $title }}</p>
    @endif
    <p class="mt-1 max-w-sm text-sm text-slate-400">{{ $text ?? __('module.empty') }}</p>
    {{ $slot }}
</div>
