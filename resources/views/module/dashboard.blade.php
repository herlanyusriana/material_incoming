@extends('layouts.app')

@section('content')
    @php
        $items = collect($items);
        $liveCount = $items->filter(fn ($i) => ! empty($i['route']))->count();
        $charts = $charts ?? (isset($chart) && $chart ? [$chart] : []);
        $gradients = [
            'indigo' => 'from-indigo-600 to-violet-600',
            'violet' => 'from-violet-600 to-purple-600',
            'sky' => 'from-sky-500 to-indigo-600',
            'emerald' => 'from-emerald-500 to-teal-600',
            'amber' => 'from-amber-500 to-orange-600',
            'orange' => 'from-orange-500 to-rose-500',
            'teal' => 'from-teal-500 to-emerald-600',
            'rose' => 'from-rose-500 to-pink-600',
        ];
        $gradient = $gradients[$module['color'] ?? 'indigo'] ?? $gradients['indigo'];
    @endphp

    <x-breadcrumb :items="[['label' => __($module['label'])]]" />

    {{-- Hero --}}
    <div class="mt-3 overflow-hidden rounded-2xl border border-border bg-background shadow-sm">
        <div class="bg-gradient-to-r {{ $gradient }} px-5 py-5 text-white sm:px-6">
            <div class="flex flex-wrap items-center gap-4">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white/20 ring-1 ring-white/30 backdrop-blur">
                    <x-icon :name="$module['icon']" class="h-6 w-6" />
                </span>
                <div class="min-w-0 flex-1">
                    <h1 class="truncate text-xl font-bold tracking-tight">{{ __($module['label']) }}</h1>
                    <p class="mt-0.5 text-xs text-white/80">{{ __('launcher.menu_count', ['count' => $liveCount]) }} · {{ __('module.kpi_overview') }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider ring-1 ring-white/30">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white"></span>
                    Live
                </span>
            </div>
        </div>
    </div>

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
                    :href="$item['route'] ? route($item['route']) : null"
                    :disabled="! $item['route']" />
            @endforeach
        </div>
    </div>
@endsection

