@props(['items' => []])

@if(count($items) > 0)
<nav aria-label="Breadcrumb" class="mb-4">
    <ol class="flex items-center gap-1.5 text-sm text-slate-500">
        <li>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 hover:text-indigo-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5V21h6v-6h6v6h6v-7.5L12 3 3 10.5" />
                </svg>
                <span class="sr-only">Dashboard</span>
            </a>
        </li>
        @foreach($items as $item)
            <li class="flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
                </svg>
                @if(!$loop->last && isset($item['url']))
                    <a href="{{ $item['url'] }}" class="hover:text-indigo-600 transition-colors font-medium">{{ $item['label'] }}</a>
                @else
                    <span class="text-slate-900 font-semibold">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif
