@props(['label'])

<div>
    <div x-show="!sidebarCollapsed" x-cloak class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
        {{ $label }}
    </div>
    <div x-show="sidebarCollapsed" x-cloak class="w-8 h-px bg-slate-200 mx-auto mb-2"></div>
    <div class="space-y-1">
        {{ $slot }}
    </div>
</div>
