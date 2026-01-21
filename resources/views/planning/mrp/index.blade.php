<x-app-layout>
    <x-slot name="header">
        Planning • MRP (Daily View)
    </x-slot>

    <div class="py-6">
        <div class="max-w-[98%] mx-auto px-2 space-y-6">
            @php
                $month = $month ?? now()->format('Y-m');
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

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                {{-- Control Bar --}}
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex items-end gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Month</label>
                            <input type="month" name="month" value="{{ $month }}" class="mt-1 rounded-xl border-slate-200">
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Load View</button>
                    </form>

                    <form method="POST" action="{{ route('planning.mrp.generate-range') }}" class="flex items-center gap-3">
                        @csrf
                        <input type="hidden" name="start_minggu" value="{{ $startWeek }}">
                        <input type="hidden" name="weeks_count" value="{{ $weeksCount }}">
                        <span class="text-xs text-slate-500 italic mr-2">Run MRP for {{ $weeksCount }} weeks ({{ $startWeek }} ...)</span>
                         
                        <label class="flex items-center gap-2 cursor-pointer bg-slate-50 px-3 py-2 rounded-lg border border-slate-200">
                            <input type="checkbox" name="include_saturday" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm font-semibold text-slate-600">Include Sat</span>
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

                @if(empty($mrpData))
                    <div class="rounded-xl border border-dashed border-slate-200 p-12 text-center text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="text-lg font-medium text-slate-900">No Data Available</h3>
                        <p class="text-slate-500 mt-1">Run MRP first or check if you have Parts/Inventory data.</p>
                    </div>
                @else
                    {{-- MRP Table Form --}}
                    <form action="{{ route('planning.mrp.generate-po') }}" method="POST" id="po-form">
                        @csrf
                        
                        <div class="flex justify-end mb-2">
                            <button class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-sm flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                  <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                                </svg>
                                Generate PO from Selection
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto border border-slate-300 rounded-xl shadow-sm">
                            <div class="inline-block min-w-full align-middle">
                                <div class="relative overflow-hidden">
                                     {{-- Fixed Height Container for sticky headers if needed --}}
                                    <table class="min-w-full divide-y divide-slate-300 border-collapse table-fixed w-max">
                                        <thead class="bg-indigo-900 text-white">
                                            <tr>
                                                <th scope="col" class="sticky left-0 z-20 bg-indigo-900 px-3 py-2 text-left text-xs font-bold uppercase w-8">No.</th>
                                                <th scope="col" class="sticky left-8 z-20 bg-indigo-900 px-3 py-2 text-left text-xs font-bold uppercase w-32 border-l border-indigo-700">Part No</th>
                                                <th scope="col" class="sticky left-40 z-20 bg-indigo-900 px-3 py-2 text-left text-xs font-bold uppercase w-48 border-l border-indigo-700">Name / Spec</th>
                                                <th scope="col" class="sticky left-80 z-20 bg-indigo-900 px-3 py-2 text-left text-xs font-bold uppercase w-24 border-l border-indigo-700">Item</th>
                                                <th scope="col" class="sticky left-[26rem] z-20 bg-indigo-900 px-3 py-2 text-right text-xs font-bold uppercase w-24 border-l border-indigo-700 border-r-2 border-r-indigo-400">Stock</th>
                                                
                                                @foreach ($dates as $date)
                                                    <th scope="col" class="px-1 py-1 text-center text-[10px] font-semibold w-12 border-l border-indigo-800">
                                                        <div>{{ date('d', strtotime($date)) }}</div>
                                                        <div class="text-[9px] opacity-70">{{ date('D', strtotime($date)) }}</div>
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 bg-white">
                                            @foreach ($mrpData as $index => $row)
                                                @php 
                                                    $part = $row['part']; 
                                                    $bgClass = $loop->even ? 'bg-slate-50' : 'bg-white';
                                                @endphp
                                                {{-- Row 1: Plan --}}
                                                <tr class="{{ $bgClass }} group">
                                                    <td rowspan="4" class="sticky left-0 z-10 {{ $bgClass }} px-2 py-1 text-xs text-center border-r border-slate-200 font-mono text-slate-500">{{ $index + 1 }}</td>
                                                    <td rowspan="4" class="sticky left-8 z-10 {{ $bgClass }} px-2 py-1 text-xs font-bold text-slate-900 border-r border-slate-200 break-words whitespace-normal align-top">
                                                        {{ $part->part_no }}
                                                        @if($part->classification)
                                                            <span class="inline-block px-1.5 py-0.5 text-[9px] font-bold rounded mt-1
                                                                {{ $part->classification === 'FG' ? 'bg-green-100 text-green-700' : '' }}
                                                                {{ $part->classification === 'RM' ? 'bg-blue-100 text-blue-700' : '' }}
                                                                {{ $part->classification === 'WIP' ? 'bg-yellow-100 text-yellow-700' : '' }}">
                                                                {{ $part->classification }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td rowspan="4" class="sticky left-40 z-10 {{ $bgClass }} px-2 py-1 text-xs text-slate-700 border-r border-slate-200 break-words whitespace-normal align-top">
                                                        {{ $part->part_name }}
                                                        @if($part->model)
                                                            <div class="text-[10px] text-slate-500 mt-1">{{ $part->model }}</div>
                                                        @endif
                                                    </td>
                                                    
                                                    {{-- PLAN Row --}}
                                                    <td class="sticky left-80 z-10 {{ $bgClass }} px-2 py-1 text-[10px] font-bold text-indigo-700 border-r border-slate-200">Plan</td>
                                                    <td class="sticky left-[26rem] z-10 {{ $bgClass }} px-2 py-1 text-right text-xs text-slate-400 border-r-2 border-slate-300">-</td>
                                                    
                                                    @foreach ($dates as $date)
                                                        @php $val = $row['days'][$date]['demand'] ?? 0; @endphp
                                                        <td class="px-1 py-1 text-center text-xs border-l border-slate-100 {{ $val > 0 ? 'bg-indigo-50 font-semibold text-indigo-700' : 'text-slate-300' }}">
                                                            {{ $val > 0 ? formatNumber($val) : '-' }}
                                                        </td>
                                                    @endforeach
                                                </tr>
                                                
                                                {{-- Row 2: Incoming --}}
                                                <tr class="{{ $bgClass }}">
                                                    <td class="sticky left-80 z-10 {{ $bgClass }} px-2 py-1 text-[10px] font-semibold text-emerald-700 border-r border-slate-200">Incoming</td>
                                                    <td class="sticky left-[26rem] z-10 {{ $bgClass }} px-2 py-1 text-right text-xs text-slate-400 border-r-2 border-slate-300">-</td>
                                                    
                                                    @foreach ($dates as $date)
                                                        @php $val = $row['days'][$date]['incoming'] ?? 0; @endphp
                                                        <td class="px-1 py-1 text-center text-xs border-l border-slate-100 {{ $val > 0 ? 'bg-emerald-50 font-bold text-emerald-700' : 'text-slate-300' }}">
                                                            {{ $val > 0 ? formatNumber($val) : '-' }}
                                                        </td>
                                                    @endforeach
                                                </tr>

                                                {{-- Row 3: Stock --}}
                                                <tr class="{{ $bgClass }}">
                                                    <td class="sticky left-80 z-10 {{ $bgClass }} px-2 py-1 text-[10px] font-semibold text-slate-900 border-r border-slate-200">Stock</td>
                                                    <td class="sticky left-[26rem] z-10 {{ $bgClass }} px-2 py-1 text-right text-xs font-bold text-slate-800 border-r-2 border-slate-300 bg-yellow-50">
                                                        {{ formatNumber($row['initial_stock']) }}
                                                    </td>
                                                    
                                                    @foreach ($dates as $date)
                                                        @php 
                                                            $val = $row['days'][$date]['projected_stock'] ?? 0; 
                                                            $stockClass = $val < 0 ? 'text-red-600 font-bold bg-red-50' : 'text-slate-700';
                                                        @endphp
                                                        <td class="px-1 py-1 text-center text-xs border-l border-slate-100 {{ $stockClass }}">
                                                            {{ formatNumber($val) }}
                                                        </td>
                                                    @endforeach
                                                </tr>
                                                
                                                 {{-- Row 4: Net Req / PO --}}
                                                <tr class="{{ $bgClass }} border-b-2 border-slate-300">
                                                    <td class="sticky left-80 z-10 {{ $bgClass }} px-2 py-1 text-[10px] font-bold text-red-700 border-r border-slate-200">Net Req</td>
                                                    <td class="sticky left-[26rem] z-10 {{ $bgClass }} px-2 py-1 text-right text-xs text-slate-400 border-r-2 border-slate-300">-</td>
                                                    
                                                    @foreach ($dates as $date)
                                                        {{-- Planned Order Rec matches logic from Controller --}}
                                                        @php 
                                                            $val = $row['days'][$date]['planned_order_rec'] ?? 0; 
                                                        @endphp
                                                        <td class="px-1 py-1 text-center border-l border-slate-100">
                                                            @if($val > 0)
                                                                <div class="flex flex-col items-center justify-center gap-1">
                                                                    <span class="text-xs font-bold text-red-600">{{ formatNumber($val) }}</span>
                                                                    {{-- Input for PO Generation --}}
                                                                     <input type="checkbox" name="items[{{ $part->id }}]" value="{{ $val }}" class="h-3 w-3 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" title="Create PO for {{ $val }} items">
                                                                </div>
                                                            @else
                                                                <span class="text-slate-300 text-xs">-</span>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
