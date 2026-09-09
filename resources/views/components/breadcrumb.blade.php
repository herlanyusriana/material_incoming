@props(['items' => []])

@if (count($items))
    <nav aria-label="breadcrumb" class="flex items-center gap-1.5 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1 rounded px-1 font-medium text-slate-500 transition hover:text-indigo-600">
            <x-icon name="home" class="h-3.5 w-3.5" />
            {{ __('nav.home') }}
        </a>
        @foreach ($items as $item)
            <x-icon name="chevron-right" class="h-3 w-3 shrink-0 text-slate-300" />
            @if (!empty($item['url']))
                <a href="{{ $item['url'] }}" class="truncate rounded px-1 font-medium text-slate-500 transition hover:text-indigo-600">{{ $item['label'] }}</a>
            @else
                <span class="truncate font-semibold text-slate-700">{{ $item['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif
