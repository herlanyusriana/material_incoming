@props([
    'title',
    'subtitle' => null,
    'badge' => null,
    'badgeColor' => 'indigo',
    'breadcrumbs' => [],
])

<div class="mb-6">
    <x-breadcrumb :items="$breadcrumbs" />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            @if($badge)
                <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-{{ $badgeColor }}-600 mb-1">
                    <span class="h-1.5 w-1.5 rounded-full bg-{{ $badgeColor }}-500 animate-pulse"></span>
                    {{ $badge }}
                </div>
            @endif
            <h1 class="text-xl font-black text-slate-900 leading-tight">{{ $title }}</h1>
            @if($subtitle)
                <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>

        @if(isset($actions))
            <div class="flex items-center gap-2 flex-shrink-0">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
