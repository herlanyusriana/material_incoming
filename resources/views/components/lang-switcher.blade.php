@props(['current' => null])

@php
    $current = $current || app()->getLocale();
    $languages = ['id' => '🇮🇩 ID', 'en' => '🇬🇧 EN', 'ko' => '🇰🇷 KO'];
@endphp

<div x-data="{ open: false }" @click.outside="open = false" class="relative">
    <button type="button" @click="open = !open"
        class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-medium text-slate-600 shadow-sm transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
        <x-icon name="globe" class="h-4 w-4" />
        {{ strtoupper($current) }}
        <x-icon name="chevron-down" class="h-3 w-3 opacity-60" />
    </button>
    <div x-show="open" x-transition.origin.top.right
        class="absolute right-0 z-40 mt-1.5 w-36 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg"
        style="display: none;">
        @foreach ($languages as $code => $label)
            <a href="{{ route('locale.set', $code) }}"
                class="flex items-center justify-between px-3 py-1.5 text-sm {{ $code === $current ? 'bg-indigo-50 font-semibold text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}">
                {{ $label }}
                @if ($code === $current)
                    <span class="text-indigo-500">✓</span>
                @endif
            </a>
        @endforeach
    </div>
</div>
