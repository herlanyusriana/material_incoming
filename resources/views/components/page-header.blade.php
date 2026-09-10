@props([
    'title',
    'subtitle' => null,
    'badge' => null,
    'badgeColor' => 'indigo',
    'breadcrumbs' => [],
])

    <div class="mb-8">
    <x-breadcrumb :items="$breadcrumbs" />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            @if($badge)
                <div class="mb-2 inline-flex items-center rounded-md bg-{{ $badgeColor }}-50 px-2 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-{{ $badgeColor }}-700 ring-1 ring-inset ring-{{ $badgeColor }}-100">
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
