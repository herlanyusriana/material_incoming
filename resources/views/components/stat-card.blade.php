@props([
    'label',
    'value',
    'subtitle' => null,
    'icon' => null,
    'color' => 'indigo',
    'trend' => null,
    'trendUp' => true,
])

@php
    $colorMap = [
        'indigo'  => ['bg' => 'from-indigo-500 to-indigo-600', 'text' => 'text-indigo-600', 'light' => 'bg-indigo-50'],
        'emerald' => ['bg' => 'from-emerald-500 to-emerald-600', 'text' => 'text-emerald-600', 'light' => 'bg-emerald-50'],
        'amber'   => ['bg' => 'from-amber-500 to-amber-600', 'text' => 'text-amber-600', 'light' => 'bg-amber-50'],
        'rose'    => ['bg' => 'from-rose-500 to-rose-600', 'text' => 'text-rose-600', 'light' => 'bg-rose-50'],
        'violet'  => ['bg' => 'from-violet-500 to-violet-600', 'text' => 'text-violet-600', 'light' => 'bg-violet-50'],
        'sky'     => ['bg' => 'from-sky-500 to-sky-600', 'text' => 'text-sky-600', 'light' => 'bg-sky-50'],
    ];
    $c = $colorMap[$color] ?? $colorMap['indigo'];
@endphp

<div {{ $attributes->merge(['class' => 'group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-0.5']) }}>
    <div class="flex items-start justify-between">
        <div class="flex-1 min-w-0">
            <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold truncate">{{ $label }}</div>
            <div class="mt-2 text-3xl font-black text-slate-900 tabular-nums">{{ $value }}</div>
            @if($subtitle)
                <div class="text-xs text-slate-500 mt-1">{{ $subtitle }}</div>
            @endif
            @if($trend)
                <div class="mt-2 inline-flex items-center gap-1 text-xs font-semibold {{ $trendUp ? 'text-emerald-600' : 'text-rose-600' }}">
                    @if($trendUp)
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25"/></svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5l15 15m0 0V8.25m0 11.25H8.25"/></svg>
                    @endif
                    {{ $trend }}
                </div>
            @endif
        </div>
        @if($icon)
            <div class="w-11 h-11 bg-gradient-to-br {{ $c['bg'] }} rounded-xl flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                </svg>
            </div>
        @endif
    </div>
</div>
