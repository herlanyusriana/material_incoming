@props(['config' => [], 'height' => 'h-64', 'title' => null, 'subtitle' => null, 'switchable' => false])

@php
    $heading = $title ?? $attributes->get('title');
    $base = array_merge(['responsive' => true, 'maintainAspectRatio' => false], $config);
    $encoded = json_encode($base, JSON_HEX_APOS | JSON_HEX_QUOT);
    $id = 'chart-' . substr(md5($encoded . mt_rand()), 0, 8);
    $hasData = ! empty($base['data']['datasets'] ?? []) && ! empty($base['data']['labels'] ?? []);
@endphp

<div {{ $attributes->except('title')->merge(['class' => 'gci-card p-4']) }}
    @if ($switchable && ($base['type'] ?? '') === 'bar')
        x-data="{ type: 'bar', chart: null, raw: null }"
        x-init="raw = JSON.parse($el.querySelector('canvas').dataset.chart); chart = new window.Chart($el.querySelector('canvas'), raw);"
    @endif>
    @if ($heading || $subtitle || $switchable)
        <div class="mb-3 flex items-start justify-between gap-3">
            <div>
                @if ($heading)
                    <h3 class="text-sm font-semibold text-foreground">{{ $heading }}</h3>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-xs text-muted-foreground">{{ $subtitle }}</p>
                @endif
            </div>
            @if ($switchable && ($base['type'] ?? '') === 'bar')
                <div class="flex shrink-0 overflow-hidden rounded-lg border border-border text-xs font-semibold" role="tablist" aria-label="Jenis grafik">
                    <button type="button" role="tab" :aria-selected="type === 'bar'" @click="type = 'bar'; chart.config.type = 'bar'; chart.update();"
                        :class="type === 'bar' ? 'bg-primary text-white' : 'bg-background text-muted-foreground hover:bg-muted'"
                        class="px-2.5 py-1.5 transition-colors">Bar</button>
                    <button type="button" role="tab" :aria-selected="type === 'line'" @click="type = 'line'; chart.config.type = 'line'; chart.data.datasets.forEach(d => { d.fill = true; d.tension = 0.4; }); chart.update();"
                        :class="type === 'line' ? 'bg-primary text-white' : 'bg-background text-muted-foreground hover:bg-muted'"
                        class="px-2.5 py-1.5 transition-colors">Line</button>
                </div>
            @endif
        </div>
    @endif
    <div class="{{ $height }}">
        @if ($hasData)
            <canvas id="{{ $id }}" data-chart='{{ $encoded }}' role="img" aria-label="{{ $heading ?? 'Grafik data' }}"></canvas>
        @else
            <div class="flex h-full flex-col items-center justify-center gap-2 text-center">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                    <x-icon name="chart-bar" class="h-5 w-5" />
                </span>
                <p class="text-sm font-semibold text-foreground">Belum ada data</p>
                <p class="text-xs text-muted-foreground">Data akan muncul otomatis setelah ada transaksi.</p>
            </div>
        @endif
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const grid = getComputedStyle(document.documentElement).getPropertyValue('--color-border').trim();
                document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
                    if (canvas.closest('[x-data]')) return; // handled by Alpine (switchable)
                    const cfg = JSON.parse(canvas.dataset.chart);
                    cfg.options = cfg.options || {};
                    cfg.options.plugins = cfg.options.plugins || {};
                    const dsCount = (cfg.data?.datasets?.length ?? 0);
                    const isPie = ['doughnut', 'pie', 'polarArea'].includes(cfg.type);
                    if (!cfg.options.plugins.legend) {
                        cfg.options.plugins.legend = { display: isPie || dsCount > 1, position: 'bottom', labels: { boxWidth: 12, padding: 16, usePointStyle: true } };
                    }
                    if (!isPie) {
                        cfg.options.scales = cfg.options.scales || {};
                        cfg.options.scales.x = Object.assign({ grid: { display: false } }, cfg.options.scales.x || {});
                        cfg.options.scales.y = Object.assign({ beginAtZero: true, grid: { color: 'rgba(100,116,139,0.12)' }, ticks: { precision: 0 } }, cfg.options.scales.y || {});
                    }
                    cfg.options.plugins.tooltip = Object.assign({ mode: 'index', intersect: false }, cfg.options.plugins.tooltip || {});
                    new window.Chart(canvas, cfg);
                });
            });
        </script>
    @endpush
@endonce

