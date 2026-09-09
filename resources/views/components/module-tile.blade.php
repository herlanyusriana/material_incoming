@props([
    'color' => 'indigo',
    'icon' => 'cube',
    'title',
    'subtitle' => null,
    'count' => null,
    'alerts' => null,
    'href' => null,
])

@php
    // Literal class strings so Tailwind JIT keeps them.
    $palettes = [
        'indigo' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-600', 'ring' => 'ring-indigo-100', 'badge' => 'bg-indigo-100 text-indigo-700'],
        'violet' => ['bg' => 'bg-violet-50', 'text' => 'text-violet-600', 'ring' => 'ring-violet-100', 'badge' => 'bg-violet-100 text-violet-700'],
        'sky' => ['bg' => 'bg-sky-50', 'text' => 'text-sky-600', 'ring' => 'ring-sky-100', 'badge' => 'bg-sky-100 text-sky-700'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'ring' => 'ring-emerald-100', 'badge' => 'bg-emerald-100 text-emerald-700'],
        'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-600', 'ring' => 'ring-amber-100', 'badge' => 'bg-amber-100 text-amber-700'],
        'orange' => ['bg' => 'bg-orange-50', 'text' => 'text-orange-600', 'ring' => 'ring-orange-100', 'badge' => 'bg-orange-100 text-orange-700'],
        'teal' => ['bg' => 'bg-teal-50', 'text' => 'text-teal-600', 'ring' => 'ring-teal-100', 'badge' => 'bg-teal-100 text-teal-700'],
        'rose' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-600', 'ring' => 'ring-rose-100', 'badge' => 'bg-rose-100 text-rose-700'],
    ];

    $p = $palettes[$color] ?? $palettes['indigo'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'group relative flex min-h-[6.5rem] flex-col justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500']) }}>
    <div class="flex items-start justify-between gap-2">
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg ring-1 {{ $p['bg'] }} {{ $p['text'] }} {{ $p['ring'] }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
        @if (!is_null($alerts))
            <span title="{{ __('launcher.alerts', ['count' => $alerts]) }}"
                class="inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full px-1 text-[11px] font-semibold {{ $alerts > 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-400' }}">
                {{ $alerts > 0 ? $alerts : '' }}
            </span>
        @endif
    </div>
    <div class="mt-3">
        <p class="text-sm font-semibold leading-snug text-slate-800 group-hover:text-slate-950">{{ $title }}</p>
        @if ($subtitle)
            <p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>
        @endif
        @if (!is_null($count))
            <p class="mt-1 text-[11px] font-medium uppercase tracking-wide {{ $p['text'] }}">{{ __('launcher.menu_count', ['count' => $count]) }}</p>
        @endif
    </div>
</{{ $tag }}>
