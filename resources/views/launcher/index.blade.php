@extends('layouts.app')

@section('content')
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ __('nav.welcome') }}, {{ auth()->user()->name }} 👋</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ __('launcher.title') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('launcher.subtitle') }}</p>
    </div>

    @if (count($modules) === 0)
        <x-empty-state
            icon="shield"
            :title="__('launcher.no_access_title')"
            :text="__('launcher.no_access_text')" />
    @else
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
            @foreach ($modules as $key => $module)
                <x-module-tile
                    :href="route('module.dashboard', $key)"
                    :color="$module['color']"
                    :icon="$module['icon']"
                    :title="__($module['label'])"
                    :count="count($module['visible_items'])" />
            @endforeach
        </div>
    @endif
@endsection
