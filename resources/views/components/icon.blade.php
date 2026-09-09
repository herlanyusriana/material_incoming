@props(['name', 'class' => 'h-6 w-6'])

<svg {{ $attributes->merge(['class' => $class]) }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
    @foreach (explode(' M', \App\Support\Icon::path($name)) as $segment)
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ (str_starts_with($segment, 'M') ? '' : 'M') . $segment }}" />
    @endforeach
</svg>
