@props([
    'label',
    'icon',
    'active' => false,
    'open' => false,
])

@php
    $navLinkBase = 'group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200';
    $navActive = 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-sm shadow-indigo-600/20';
    $navInactive = 'text-slate-600 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-violet-50 hover:text-slate-900';
@endphp

<details class="group" {{ ($active || $open) ? 'open' : '' }}>
    <summary class="list-none cursor-pointer">
        <div @class([$navLinkBase, $navActive => $active, $navInactive => !$active])
             :class="sidebarCollapsed ? 'justify-center' : 'gap-3'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
            </svg>
            <span x-show="!sidebarCollapsed" x-cloak class="flex-1 truncate">{{ $label }}</span>
            <svg x-show="!sidebarCollapsed" x-cloak class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180 group-open:text-indigo-600 shrink-0"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
            </svg>
        </div>
    </summary>
    <div x-show="!sidebarCollapsed" x-cloak class="relative mt-2 ml-4 pl-4 space-y-1">
        <div class="absolute left-1 top-2 bottom-2 w-px bg-gradient-to-b from-indigo-300 via-indigo-200 to-transparent"></div>
        {{ $slot }}
    </div>
</details>
