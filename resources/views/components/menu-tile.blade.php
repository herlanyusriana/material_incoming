@props([
    'color' => 'indigo',
    'icon' => 'cube',
    'title',
    'href' => null,
    'disabled' => false,
])

@php
    $accents = [
        'indigo' => 'text-indigo-600', 'violet' => 'text-violet-600', 'sky' => 'text-sky-600',
        'emerald' => 'text-emerald-600', 'amber' => 'text-amber-600', 'orange' => 'text-orange-600',
        'teal' => 'text-teal-600', 'rose' => 'text-rose-600',
    ];

    $accent = $accents[$color] ?? $accents['indigo'];
    $tag = ($href && !$disabled) ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($tag === 'a') href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'group flex min-h-[4.5rem] items-center gap-3 rounded-xl border p-3.5 text-left shadow-sm transition duration-200 '
        . ($disabled
            ? 'cursor-not-allowed border-dashed border-slate-200 bg-slate-50 opacity-70'
            : 'border-slate-200/90 bg-white hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500')]) }}>
    <span class="shrink-0 {{ $disabled ? 'text-slate-300' : $accent }}">
        <x-icon :name="$icon" class="h-5 w-5" />
    </span>
    <span class="min-w-0 flex-1">
        <span class="block text-sm font-medium leading-snug {{ $disabled ? 'text-slate-400' : 'text-slate-700 group-hover:text-slate-950' }}">{{ $title }}</span>
        @if ($disabled)
            <span class="mt-1 inline-block rounded-full bg-slate-200/70 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ __('module.coming_soon') }}</span>
        @endif
    </span>
    @if (!$disabled)
        <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-slate-500" />
    @endif
</{{ $tag }}>
