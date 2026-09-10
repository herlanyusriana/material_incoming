@props(['label', 'value' => null, 'hint' => null, 'subtitle' => null, 'trend' => null, 'color' => 'indigo', 'icon' => null])

@php
    $accents = [
        'indigo' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-600', 'ring' => 'ring-indigo-100'],
        'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-600', 'ring' => 'ring-emerald-100'],
        'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-600', 'ring' => 'ring-amber-100'],
        'red' => ['bg' => 'bg-red-50', 'text' => 'text-red-600', 'ring' => 'ring-red-100'],
        'sky' => ['bg' => 'bg-sky-50', 'text' => 'text-sky-600', 'ring' => 'ring-sky-100'],
        'violet' => ['bg' => 'bg-violet-50', 'text' => 'text-violet-600', 'ring' => 'ring-violet-100'],
        'orange' => ['bg' => 'bg-orange-50', 'text' => 'text-orange-600', 'ring' => 'ring-orange-100'],
        'teal' => ['bg' => 'bg-teal-50', 'text' => 'text-teal-600', 'ring' => 'ring-teal-100'],
        'rose' => ['bg' => 'bg-rose-50', 'text' => 'text-rose-600', 'ring' => 'ring-rose-100'],
        'slate' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'ring' => 'ring-slate-200'],
    ];
    $accent = $accents[$color] ?? $accents['indigo'];
    $display = $subtitle ?? $hint;
    $numeric = is_numeric($value) ? (float) $value : null;
@endphp

<div {{ $attributes->merge(['class' => 'gci-card-hover p-4']) }}
    @if (! is_null($numeric))
        x-data="{ current: 0, target: {{ $numeric }} }"
        x-init="const t0 = performance.now(), dur = 800; const step = (t) => { const p = Math.min(1, (t - t0) / dur); current = target * p * (2 - p); if (p < 1) requestAnimationFrame(step); else current = target; }; requestAnimationFrame(step);"
    @endif>
    <div class="flex items-start justify-between gap-3">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">{{ $label }}</p>
        @if ($icon)
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $accent['bg'] }} {{ $accent['text'] }} ring-1 {{ $accent['ring'] }}">
                <x-icon :name="$icon" class="h-4 w-4" />
            </span>
        @endif
    </div>
    <p class="mt-1.5 text-[26px] font-bold leading-none tabular-nums text-foreground">
        @if (is_null($value))
            N/A
        @elseif (! is_null($numeric))
            <span x-text="Math.round(current).toLocaleString('id-ID')">{{ number_format($numeric, 0, ',', '.') }}</span>
        @else
            {{ $value }}
        @endif
    </p>
    @if ($display)
        <p class="mt-1.5 text-xs text-muted-foreground">{{ $display }}</p>
    @endif
    @if ($trend)
        <p class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold {{ str_starts_with((string) $trend, '-') ? 'text-red-600' : 'text-emerald-600' }}">
            {{ $trend }}
        </p>
    @endif
</div>
