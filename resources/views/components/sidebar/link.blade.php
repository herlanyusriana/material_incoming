@props([
    'href',
    'icon',
    'active' => false,
    'badge' => null,
    'badgeColor' => 'indigo',
])

@php
    $navLinkBase = 'group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150';
    $navActive = 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/20';
    $navInactive = 'text-slate-600 hover:bg-indigo-50 hover:text-slate-950';
@endphp

<a href="{{ $href }}"
    title="{{ $slot }}"
    @class([$navLinkBase, $navActive => $active, $navInactive => !$active])
    :class="sidebarCollapsed ? 'justify-center' : 'gap-3'"
    @if(isset($mobileSidebar)) @click="mobileSidebarOpen = false" @endif
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
    </svg>
    <span x-show="!sidebarCollapsed" x-cloak class="flex-1 truncate">{{ $slot }}</span>
    @if($badge !== null)
        <span x-show="!sidebarCollapsed" x-cloak class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-{{ $badgeColor }}-100 text-{{ $badgeColor }}-700">
            {{ $badge }}
        </span>
    @endif
</a>
