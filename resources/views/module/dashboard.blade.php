@extends('layouts.app')

@section('content')
    @php
        $categories = $categories ?? [];
        $tools = $tools ?? [];
        $items = collect($items);
        $liveCount = $items->filter(fn ($i) => ! empty($i['route']))->count();
        $charts = $charts ?? (isset($chart) && $chart ? [$chart] : []);
    @endphp

    <x-breadcrumb :items="[['label' => __($module['label'])]]" />

    {{-- Hero --}}
    <div class="mt-3 overflow-hidden rounded-2xl border border-border bg-background shadow-sm">
        <div class="relative bg-slate-950 px-5 py-5 text-white sm:px-6">
            <div class="absolute inset-y-0 left-0 w-1 bg-indigo-500"></div>
            <div class="flex flex-wrap items-center gap-4">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                    <x-icon :name="$module['icon']" class="h-6 w-6" />
                </span>
                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-xl font-bold tracking-tight">{{ __($module['label']) }}</h1>
                    <p class="mt-0.5 text-xs text-slate-300">{{ __('launcher.menu_count', ['count' => $liveCount]) }} · {{ __('module.kpi_overview') }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider ring-1 ring-white/20">
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
                    Live
                </span>
            </div>
        </div>
    </div>

    @php
        $itemHref = function (array $item) use ($categories, $activeCategory) {
            if (empty($item['route'])) {
                return null;
            }
            $params = [];
            if (! empty($item['category_param']) && isset($categories[$activeCategory]['classification'])) {
                $params[$item['category_param']] = $categories[$activeCategory]['classification'];
            }

            return route($item['route'], $params);
        };
    @endphp

    {{-- Tab kategori (FG / RM / WIP) --}}
    @if (count($categories))
        <div class="mt-4 flex flex-wrap items-center gap-2" role="tablist" aria-label="{{ __($module['label']) }}">
            @foreach ($categories as $catKey => $cat)
                <a href="{{ route('module.dashboard', ['module' => $module['key'], 'cat' => $catKey]) }}"
                   role="tab"
                   aria-selected="{{ $catKey === $activeCategory ? 'true' : 'false' }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border px-4 py-2 text-sm font-semibold transition-all {{ $catKey === $activeCategory ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm' : 'border-border bg-background text-muted-foreground hover:bg-slate-50' }}">
                    {{ __($cat['label']) }}
                </a>
            @endforeach
        </div>
    @endif

    {{-- KPI row --}}
    @if (count($stats))
        <div class="mt-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{{ __('module.overview') }}</h2>
        </div>
        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $i => $stat)
                <x-stat-card
                    :label="$stat['label']"
                    :value="$stat['value']"
                    :hint="$stat['hint'] ?? null"
                    :trend="$stat['trend'] ?? null"
                    :color="$stat['color'] ?? ($module['color'] ?? 'indigo')"
                    :icon="$stat['icon'] ?? null"
                    class="animate-fade-in-up delay-{{ min($i + 1, 5) }}" />
            @endforeach
        </div>
    @endif

    {{-- Charts + attention --}}
    @if (count($charts) || $attention)
        <div class="mt-6 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{{ __('module.analytics') }}</h2>
        </div>
        <div class="mt-3 grid grid-cols-1 gap-4 {{ count($charts) > 1 ? 'lg:grid-cols-2' : '' }}">
            @foreach ($charts as $c)
                <x-chart
                    :title="$c['title'] ?? null"
                    :subtitle="$c['subtitle'] ?? null"
                    :config="$c['config'] ?? []"
                    :switchable="$c['switchable'] ?? false"
                    :height="$c['height'] ?? 'h-64'" />
            @endforeach
            @if ($attention && count($charts) <= 1)
                <x-attention-table
                    :title="$attention['title']"
                    :columns="$attention['columns']"
                    :rows="$attention['rows']" />
            @endif
        </div>
        @if ($attention && count($charts) > 1)
            <div class="mt-4">
                <x-attention-table
                    :title="$attention['title']"
                    :columns="$attention['columns']"
                    :rows="$attention['rows']" />
            </div>
        @endif
    @endif

    {{-- Menu tiles --}}
    <div class="mt-8">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{{ __('module.menus') }}</h2>
            <span class="text-xs text-muted-foreground">{{ __('module.quick_nav') }}</span>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach ($items as $item)
                <x-menu-tile
                    :color="$module['color']"
                    :icon="$item['icon'] ?? 'cube'"
                    :title="__($item['label'])"
                    :href="$itemHref($item)"
                    :disabled="! $item['route']" />
            @endforeach
        </div>

        @if (count($tools))
            <div class="mt-6 flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">{{ __('module.tools') }}</h2>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($tools as $item)
                    <x-menu-tile
                        :color="$module['color']"
                        :icon="$item['icon'] ?? 'cube'"
                        :title="__($item['label'])"
                        :href="$itemHref($item)"
                        :disabled="! $item['route']" />
                @endforeach
            </div>
        @endif
    </div>
@endsection
