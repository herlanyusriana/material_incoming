@props([
    'activeCount' => 0,
    'applyLabel' => 'Apply',
    'formId' => null,
    'moreLabel' => 'More filters',
    'resetUrl' => null,
])

<div {{ $attributes->merge(['class' => 'filter-bar rounded-2xl border border-slate-200 bg-white shadow-sm']) }}
    x-data="{ advancedOpen: {{ $activeCount > 0 ? 'true' : 'false' }} }">
    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="flex min-w-0 flex-1 flex-wrap items-end gap-3">
            {{ $primary }}
        </div>
        <div class="flex shrink-0 items-center gap-2">
            @isset($advanced)
                <button type="button"
                    class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                    :aria-expanded="advancedOpen.toString()"
                    @click="advancedOpen = !advancedOpen">
                    <span>{{ $moreLabel }}</span>
                    @if ($activeCount > 0)
                        <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-indigo-100 px-1.5 py-0.5 text-[11px] font-bold text-indigo-700">{{ $activeCount }}</span>
                    @endif
                    <svg class="h-4 w-4 transition-transform" :class="advancedOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" /></svg>
                </button>
            @endif
            @if ($resetUrl)
                <a href="{{ $resetUrl }}" class="inline-flex min-h-10 items-center rounded-xl px-3 text-sm font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-800">Reset</a>
            @endif
            @if ($formId)
                <button type="submit" form="{{ $formId }}" class="inline-flex min-h-10 items-center rounded-xl bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/20">{{ $applyLabel }}</button>
            @endif
        </div>
    </div>
    @isset($advanced)
        <div x-show="advancedOpen" x-cloak class="border-t border-slate-100 bg-slate-50/70 p-4">
            <div class="flex flex-wrap items-end gap-3">
                {{ $advanced }}
            </div>
        </div>
    @endif
    @isset($active)
        <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 px-4 py-3">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Active</span>
            {{ $active }}
        </div>
    @endif
</div>
