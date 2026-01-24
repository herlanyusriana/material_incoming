<x-app-layout>
    <x-slot name="header">
        Planning • MRP
    </x-slot>

    <div class="py-6">
        <div class="max-w-[98%] mx-auto px-2 space-y-6">
            @php
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

	            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4" x-data="{ tab: 'buy', viewMode: 'daily' }">
	                {{-- Control Bar --}}
	                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex items-end gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Month</label>
                            <input type="month" name="month" value="{{ $month }}" class="mt-1 rounded-xl border-slate-200">
                            <div class="text-[11px] text-slate-500 mt-1" x-show="viewMode === 'month'" x-cloak>
                                Demand Jan–Dec untuk tahun {{ substr($month, 0, 4) }}
                            </div>
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Load View</button>
                    </form>

	                    <form method="POST" action="{{ route('planning.mrp.generate-range') }}" class="flex items-center gap-3">
	                        @csrf
                            <input type="hidden" name="month" value="{{ $month }}">
	                        <input type="hidden" name="start_minggu" value="{{ $startWeek }}">
	                        <input type="hidden" name="weeks_count" value="{{ $weeksCount }}">
	                        <span class="text-xs text-slate-500 italic mr-2">Run MRP for {{ $weeksCount }} weeks ({{ $startWeek }} ...)</span>
                         
                        <label class="flex items-center gap-2 cursor-pointer bg-slate-50 px-3 py-2 rounded-lg border border-slate-200">
                            <input type="checkbox" name="include_saturday" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-semibold text-slate-600">Include Sat</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer bg-slate-50 px-3 py-2 rounded-lg border border-slate-200">
                            <input type="checkbox" name="generate_production_orders" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" checked>
                            <span class="text-sm font-semibold text-slate-600">Auto Create Prod Order</span>
                        </label>

                        <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold shadow-sm transition-colors">
                            Run MRP
                        </button>
                         <a href="{{ route('planning.mrp.history') }}" class="px-4 py-2 rounded-lg font-semibold border bg-white border-slate-200 text-slate-700 hover:bg-slate-50">
                            History
                        </a>
                         <button form="clear-form" type="submit" class="px-4 py-2 rounded-lg font-semibold border bg-red-50 border-red-200 text-red-600 hover:bg-red-100">
                            Clear
                        </button>
	                    </form>
	                     <form method="POST" action="{{ route('planning.mrp.clear') }}" id="clear-form" onsubmit="return confirm('Clear ALL MRP data?');" class="hidden">
	                        @csrf
	                        @method('DELETE')
	                    </form>
	                </div>

	                <div class="flex flex-wrap items-center justify-between gap-3">
	                    <div class="flex items-center gap-2">
	                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border"
	                            :class="tab === 'buy' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
	                            @click="tab = 'buy'">
	                            Purchase (BUY)
	                        </button>
	                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border"
	                            :class="tab === 'make' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
	                            @click="tab = 'make'">
	                            Production (MAKE)
	                        </button>
	                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border"
	                            :class="tab === 'all' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
	                            @click="tab = 'all'">
	                            All
	                        </button>
	                    </div>
	                    <div class="flex items-center gap-2">
	                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border"
	                            :class="viewMode === 'daily' ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
	                            @click="viewMode = 'daily'">
	                            Daily (1-31)
	                        </button>
	                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border"
	                            :class="viewMode === 'summary' ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
	                            @click="viewMode = 'summary'">
	                            Summary
	                        </button>
	                        <button type="button" class="px-3 py-1.5 rounded-lg text-xs font-bold border"
	                            :class="viewMode === 'month' ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
	                            @click="viewMode = 'month'">
	                            Demand by Month
	                        </button>
	                    </div>
	                    <div class="text-xs text-slate-500">
	                        Daily = tanggal 1–31. Summary = demand/incoming/stock/net req. Demand by Month = demand per bulan (Jan–Dec).
	                    </div>
	                </div>

	                @if(!empty($mrpDataMake) && empty($mrpDataBuy))
	                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
	                        Demand/incoming komponen BUY belum muncul karena MRP Purchase Plan masih kosong. Biasanya ini karena BOM belum ter-relasi ke master part: `bom_items.component_part_id` masih kosong atau `component_part_no` belum ada di `gci_parts.part_no`.
	                    </div>
	                @endif

                @if(empty($mrpData))
                    <div class="rounded-xl border border-dashed border-slate-200 p-12 text-center text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="text-lg font-medium text-slate-900">No Data Available</h3>
                        <p class="text-slate-500 mt-1">Run MRP first or check if you have Parts/Inventory data.</p>
                    </div>
	                @else
	                    <div class="space-y-6" x-show="(tab === 'buy' || tab === 'all') && viewMode === 'daily'" x-cloak>
	                        @include('planning.mrp.partials.table', ['mrpRows' => $mrpDataBuy ?? [], 'modeLabel' => 'Purchase Planning (BUY) • Daily', 'showPoAction' => true, 'showIncoming' => true])
	                    </div>
	                    <div class="space-y-6" x-show="(tab === 'buy' || tab === 'all') && viewMode === 'summary'" x-cloak>
	                        @include('planning.mrp.partials.table_monthly', ['mrpRows' => $mrpDataBuy ?? [], 'modeLabel' => 'Purchase Planning (BUY) • Summary', 'showPoAction' => true, 'showIncoming' => true])
	                    </div>
	                    <div class="space-y-6" x-show="(tab === 'buy' || tab === 'all') && viewMode === 'month'" x-cloak>
	                        @include('planning.mrp.partials.table_month_columns', ['mrpRows' => $mrpDataBuy ?? [], 'modeLabel' => 'Purchase Planning (BUY) • Demand per Month', 'showPoAction' => true, 'months' => $months ?? [], 'monthLabels' => $monthLabels ?? []])
	                    </div>

	                    <div class="space-y-6" x-show="(tab === 'make' || tab === 'all') && viewMode === 'daily'" x-cloak>
	                        @include('planning.mrp.partials.table', ['mrpRows' => $mrpDataMake ?? [], 'modeLabel' => 'Production Planning (MAKE) • Daily', 'showPoAction' => false, 'showIncoming' => false])
	                    </div>
	                    <div class="space-y-6" x-show="(tab === 'make' || tab === 'all') && viewMode === 'summary'" x-cloak>
	                        @include('planning.mrp.partials.table_monthly', ['mrpRows' => $mrpDataMake ?? [], 'modeLabel' => 'Production Planning (MAKE) • Summary', 'showPoAction' => false, 'showIncoming' => false])
	                    </div>
	                    <div class="space-y-6" x-show="(tab === 'make' || tab === 'all') && viewMode === 'month'" x-cloak>
	                        @include('planning.mrp.partials.table_month_columns', ['mrpRows' => $mrpDataMake ?? [], 'modeLabel' => 'Production Planning (MAKE) • Demand per Month', 'showPoAction' => false, 'months' => $months ?? [], 'monthLabels' => $monthLabels ?? []])
	                    </div>
	                @endif
	            </div>
        </div>
    </div>
</x-app-layout>
