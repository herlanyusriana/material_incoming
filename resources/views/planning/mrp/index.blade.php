<x-app-layout>
    <x-slot name="header">
        {{ __('planning.mrp.index.header') }}
    </x-slot>

    <!-- MRP-Incoming Integration Banner -->
    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-100 rounded-xl p-4 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-indigo-800">{{ __('planning.mrp.index.banner_title') }}</h3>
                <p class="text-sm text-indigo-600 mt-1">{{ __('planning.mrp.index.banner_hint') }}</p>
            </div>
            <a href="{{ route('planning.mrp.integration-dashboard') }}"
                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                {{ __('planning.mrp.index.banner_btn') }}
            </a>
        </div>
    </div>

    <div class="py-6">
        <div class="w-full mx-auto px-0 sm:px-2 space-y-6">
            @php
                $familyColors = [
                    'Comp Base' => 'bg-indigo-100 text-indigo-700',
                    'Back Plate' => 'bg-sky-100 text-sky-700',
                    'Reinforce' => 'bg-emerald-100 text-emerald-700',
                    'Tray Drip' => 'bg-amber-100 text-amber-700',
                    'Small Part' => 'bg-slate-100 text-slate-600',
                    'NON LG' => 'bg-rose-100 text-rose-700',
                ];
                $month = $period ?? request('month') ?? now()->format('Y-m');
                $startOfMonth = \Carbon\Carbon::parse($month . '-01')->startOfDay();
                $endOfMonth = $startOfMonth->copy()->endOfMonth();

                // Calculate weeks for form
                $weeks = [];
                $current = $startOfMonth->copy();
                while ($current->lte($endOfMonth)) {
                    $w = $current->format('o-\\WW');
                    if (!in_array($w, $weeks)) {
                        $weeks[] = $w;
                    }
                    $current->addDay();
                }
                $startWeek = $weeks[0] ?? now()->format('o-\\WW');
                $weeksCount = count($weeks);
            @endphp

            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4"
                x-data="{ tab: 'buy', viewMode: 'summary' }">
                {{-- Control Bar --}}
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex items-end gap-3">
                        <div>
                            <label for="month-filter" class="text-xs font-semibold text-slate-600">{{ __('planning.mrp.index.month_label') }}</label>
                            <input id="month-filter" type="month" name="month" value="{{ $month }}"
                                max="{{ now()->format('Y-m') }}"
                                class="mt-1 rounded-xl border-slate-200">
                            <div class="text-[11px] text-slate-500 mt-1" x-show="viewMode === 'month'" x-cloak>
                                {{ __('planning.mrp.index.demand_hint', ['year' => substr($month, 0, 4)]) }}
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">{{ __('planning.mrp.index.family_label') }}</label>
                            <select name="family" class="mt-1 rounded-xl border-slate-200">
                                <option value="">{{ __('planning.mrp.index.filter_all') }}</option>
                                @foreach ($families ?? [] as $f)
                                    <option value="{{ $f }}" @selected(($family ?? '') === $f)>{{ $f }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">{{ __('planning.mrp.index.load') }}</button>
                    </form>

                    <form method="POST" action="{{ route('planning.mrp.generate-range') }}"
                        class="flex items-center gap-3">
                        @csrf
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="start_minggu" value="{{ $startWeek }}">
                        <input type="hidden" name="weeks_count" value="{{ $weeksCount }}">
                        <span class="text-xs text-slate-500 italic mr-2">{{ __('planning.mrp.index.run_hint', ['count' => $weeksCount, 'week' => $startWeek]) }}</span>

                        <label
                            class="flex items-center gap-2 cursor-pointer bg-slate-50 px-3 py-2 rounded-lg border border-slate-200">
                            <input type="checkbox" name="include_saturday" value="1"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-semibold text-slate-600">{{ __('planning.mrp.index.include_sat') }}</span>
                        </label>

                        <label
                            class="flex items-center gap-2 cursor-pointer bg-slate-50 px-3 py-2 rounded-lg border border-slate-200">
                            <input type="checkbox" name="generate_production_orders" value="1"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" checked>
                            <span class="text-sm font-semibold text-slate-600">{{ __('planning.mrp.index.auto_prod') }}</span>
                        </label>

                        <button
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold shadow-sm transition-colors">
                            {{ __('planning.mrp.index.run') }}
                        </button>
                        <a href="{{ route('planning.mrp.history') }}"
                            class="px-4 py-2 rounded-lg font-semibold border bg-white border-slate-200 text-slate-700 hover:bg-slate-50">
                            {{ __('planning.mrp.index.history') }}
                        </a>
                        <button form="clear-form" type="submit"
                            class="px-4 py-2 rounded-lg font-semibold border bg-red-50 border-red-200 text-red-600 hover:bg-red-100">
                            {{ __('planning.mrp.index.clear') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('planning.mrp.clear') }}" id="clear-form"
                        onsubmit='return confirm(@js(__("planning.mrp.index.confirm_clear")));' class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border"
                            :class="tab === 'buy' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                            @click="tab = 'buy'">
                            {{ __('planning.mrp.index.tab_buy') }}
                        </button>
                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border"
                            :class="tab === 'make' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                            @click="tab = 'make'">
                            {{ __('planning.mrp.index.tab_make') }}
                        </button>
                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border"
                            :class="tab === 'all' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                            @click="tab = 'all'">
                            {{ __('planning.mrp.index.tab_all') }}
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border"
                            :class="viewMode === 'summary' ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                            @click="viewMode = 'summary'">
                            {{ __('planning.mrp.index.view_summary') }}
                        </button>
                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border"
                            :class="viewMode === 'month' ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
                            @click="viewMode = 'month'">
                            {{ __('planning.mrp.index.view_month') }}
                        </button>
                    </div>
                    <div class="text-xs text-slate-500">
                        {{ __('planning.mrp.index.view_hint') }}
                    </div>
                </div>

                @if(!empty($mrpDataMake) && empty($mrpDataBuy))
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        {{ __('planning.mrp.index.warn_empty_buy') }}
                    </div>
                @endif

                @if(empty($mrpData))
                    <div class="rounded-xl border border-dashed border-slate-200 p-12 text-center text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-4 text-slate-300" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="text-lg font-medium text-slate-900">{{ __('planning.mrp.index.empty_title') }}</h3>
                        <p class="text-slate-500 mt-1">{{ __('planning.mrp.index.empty_hint') }}</p>
                    </div>
                @else
                    <div class="space-y-6" x-show="(tab === 'buy' || tab === 'all') && viewMode === 'daily'" x-cloak>
                        @include('planning.mrp.partials.table', ['mrpRows' => $mrpDataBuyPage?->items() ?? [], 'modeLabel' => __('planning.mrp.index.mode_buy_daily'), 'showPoAction' => true, 'showIncoming' => true, 'month' => $month])
                    </div>
                    <div class="space-y-6" x-show="(tab === 'buy' || tab === 'all') && viewMode === 'summary'" x-cloak>
                        @include('planning.mrp.partials.table_monthly', ['mrpRows' => $mrpDataBuyPage?->items() ?? [], 'modeLabel' => __('planning.mrp.index.mode_buy_summary'), 'showPoAction' => true, 'showIncoming' => true])
                    </div>
                    <div class="space-y-6" x-show="(tab === 'buy' || tab === 'all') && viewMode === 'month'" x-cloak>
                        @include('planning.mrp.partials.table_month_columns', ['mrpRows' => $mrpDataBuyPage?->items() ?? [], 'modeLabel' => __('planning.mrp.index.mode_buy_month'), 'showPoAction' => true, 'months' => $months ?? [], 'monthLabels' => $monthLabels ?? []])
                    </div>

                    <div class="space-y-6" x-show="(tab === 'make' || tab === 'all') && viewMode === 'daily'" x-cloak>
                        @include('planning.mrp.partials.table', ['mrpRows' => $mrpDataMakePage?->items() ?? [], 'modeLabel' => __('planning.mrp.index.mode_make_daily'), 'showPoAction' => false, 'showIncoming' => false, 'month' => $month])
                    </div>
                    <div class="space-y-6" x-show="(tab === 'make' || tab === 'all') && viewMode === 'summary'" x-cloak>
                        @include('planning.mrp.partials.table_monthly', ['mrpRows' => $mrpDataMakePage?->items() ?? [], 'modeLabel' => __('planning.mrp.index.mode_make_summary'), 'showPoAction' => false, 'showIncoming' => false])
                    </div>
                    <div class="space-y-6" x-show="(tab === 'make' || tab === 'all') && viewMode === 'month'" x-cloak>
                        @include('planning.mrp.partials.table_month_columns', ['mrpRows' => $mrpDataMakePage?->items() ?? [], 'modeLabel' => __('planning.mrp.index.mode_make_month'), 'showPoAction' => false, 'months' => $months ?? [], 'monthLabels' => $monthLabels ?? []])
                    </div>

                    @if($mrpDataBuyPage?->hasPages())
                        <div x-show="tab === 'buy' || tab === 'all'" x-cloak class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <span class="text-xs font-medium text-slate-600">Showing {{ $mrpDataBuyPage->firstItem() }}–{{ $mrpDataBuyPage->lastItem() }} of {{ $mrpDataBuyPage->total() }} purchase requirements</span>
                            <nav aria-label="MRP purchase requirements pagination">{{ $mrpDataBuyPage->links() }}</nav>
                        </div>
                    @endif
                    @if($mrpDataMakePage?->hasPages())
                        <div x-show="tab === 'make' || tab === 'all'" x-cloak class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <span class="text-xs font-medium text-slate-600">Showing {{ $mrpDataMakePage->firstItem() }}–{{ $mrpDataMakePage->lastItem() }} of {{ $mrpDataMakePage->total() }} production requirements</span>
                            <nav aria-label="MRP production requirements pagination">{{ $mrpDataMakePage->links() }}</nav>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
