@props(['config' => [], 'height' => 'h-64'])

@php
    $config = json_encode(array_merge(['responsive' => true, 'maintainAspectRatio' => false], $config), JSON_HEX_APOS | JSON_HEX_QUOT);
    $id = 'chart-' . substr(md5($config . mt_rand()), 0, 8);
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-4 shadow-sm']) }}>
    @if ($attributes->get('title'))
        <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ $attributes->get('title') }}</h3>
    @endif
    <div class="{{ $height }}">
        <canvas id="{{ $id }}" data-chart='{{ $config }}'></canvas>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
                    const cfg = JSON.parse(canvas.dataset.chart);
                    const dark = document.documentElement.classList.contains('dark');
                    cfg.options = cfg.options || {};
                    cfg.options.plugins = cfg.options.plugins || {};
                    cfg.options.plugins.legend = cfg.options.plugins.legend || { display: false };
                    new window.Chart(canvas, cfg);
                });
            });
        </script>
    @endpush
@endonce
