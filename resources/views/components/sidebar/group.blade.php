@props([
    'label',
    'icon',
    'active' => false,
    'open' => false,
])

@php
    $navLinkBase = 'group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150';
    $navActive = 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/20';
    $navInactive = 'text-slate-600 hover:bg-indigo-50 hover:text-slate-950';
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
    <div x-show="!sidebarCollapsed" x-cloak class="relative mt-2 ml-4 space-y-1 border-l border-indigo-100 pl-4">
        {{ $slot }}
    </div>
</details>
