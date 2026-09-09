@extends('layouts.app')

@section('content')
    @php
        $items = collect($items);
        $liveCount = $items->filter(fn ($i) => ! empty($i['route']))->count();
    @endphp

    <x-breadcrumb :items="[['label' => __($module['label'])]]" />

    <div class="mt-3 flex items-center gap-3">
        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white shadow-sm">
            <x-icon :name="$module['icon']" class="h-5 w-5" />
        </span>
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ __($module['label']) }}</h1>
            <p class="text-xs text-slate-500">{{ __('launcher.menu_count', ['count' => $liveCount]) }}</p>
        </div>
    </div>

    {{-- KPI row --}}
    @if (count($stats))
        <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <x-stat-card :label="$stat['label']" :value="$stat['value']" :hint="$stat['hint'] ?? null" />
            @endforeach
        </div>
    @endif

    {{-- Chart + attention --}}
    @if ($chart || $attention)
        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
            @if ($chart)
                <div class="lg:col-span-2">
                    <x-chart :title="$chart['title']" :config="$chart['config']" />
                </div>
            @endif
            @if ($attention)
                <div @class(['lg:col-span-1', 'lg:col-span-2' => ! $chart])>
                    <x-attention-table
                        :title="$attention['title']"
                        :columns="$attention['columns']"
                        :rows="$attention['rows']" />
                </div>
            @endif
        </div>
    @endif

    {{-- Menu tiles --}}
    <div class="mt-8">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('module.menus') }}</h2>
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
