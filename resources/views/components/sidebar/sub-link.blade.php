@props([
    'href',
    'active' => false,
    'badge' => null,
    'badgeColor' => 'indigo',
])

@php
    $subLinkBase = 'group flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold transition-all duration-200';
    $subActive = 'bg-gradient-to-r from-indigo-50 to-violet-50 text-indigo-700 ring-1 ring-indigo-100';
    $subInactive = 'text-slate-600 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-violet-50 hover:text-slate-900';
@endphp

<a href="{{ $href }}"
    @class([$subLinkBase, $subActive => $active, $subInactive => !$active])
>
    <span @class([
        'h-1.5 w-1.5 rounded-full shrink-0',
        'bg-indigo-600' => $active,
        'bg-slate-300 group-hover:bg-indigo-400' => !$active,
    ])></span>
    <span class="flex-1 truncate">{{ $slot }}</span>
    @if($badge !== null)
        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-{{ $badgeColor }}-100 text-{{ $badgeColor }}-700">
            {{ $badge }}
        </span>
    @endif
</a>
