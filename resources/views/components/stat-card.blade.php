@props(['label', 'value' => null, 'hint' => null, 'trend' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-4 shadow-sm']) }}>
    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
    <p class="mt-1.5 text-[26px] font-bold leading-none tabular-nums text-slate-900">
        {{ is_null($value) ? '—' : number_format((float) $value, 0, ',', '.') }}
    </p>
    @if ($hint)
        <p class="mt-1.5 text-xs text-slate-400">{{ $hint }}</p>
    @endif
</div>
