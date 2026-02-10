@extends('outgoing.layout')

@section('content')
    <div class="space-y-6" x-data="inputJig()">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">Input JIG Capacity</h1>
                    <div class="mt-1 text-sm text-slate-600">
                        Manage production line UPH and daily Jig quantities.
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-4 items-end border-t border-slate-100 pt-6">
                <form action="{{ route('outgoing.input-jig') }}" method="GET" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">From</label>
                        <input type="date" name="date_from" value="{{ $dateFrom->toDateString() }}"
                            class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">To</label>
                        <input type="date" name="date_to" value="{{ $dateTo->toDateString() }}"
                            class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <button type="submit"
                        class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
                        View
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                {{-- Table follows requested structure --}}
                <table class="w-full text-sm divide-y divide-slate-200 border-collapse">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-slate-700 w-24 border-r border-slate-200 sticky left-0 z-10 bg-slate-50">Line</th>
                            <th class="px-2 py-3 text-center font-bold text-slate-700 bg-yellow-100 w-20 border-r border-slate-200">UpH</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700 w-48 border-r border-slate-200 sticky left-24 z-10 bg-slate-50">Customer Part Name</th>
                            @foreach ($days as $index => $d)
                                <th class="px-2 py-2 text-center font-bold text-slate-700 bg-yellow-100 border-r border-slate-200 min-w-[80px]">
                                    <div class="text-[10px] text-slate-500 font-normal">H{!! $index > 0 ? '+' . $index : '' !!}</div>
                                    <div>{{ $d->format('d/m') }}</div>
                                </th>
                            @endforeach
                            <th class="px-2 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        {{-- Grouping visual logic not strict, just sorted by line --}}
                        @php $lastLine = null; @endphp
                        @foreach ($settings as $item)
                            @php 
                                $isNewLine = $item->line !== $lastLine; 
                                $lastLine = $item->line;
                                
                                // Map plans by date
                                $plans = $item->plans->keyBy(fn($p) => $p->plan_date->format('Y-m-d'));
                            @endphp
                            
                            @if($isNewLine && !$loop->first)
                                <tr class="bg-slate-100 h-2"><td colspan="{{ 4 + count($days) }}"></td></tr>
                            @endif

                            <tr class="hover:bg-slate-50 group">
                                <td class="px-4 py-3 font-bold text-slate-800 bg-white group-hover:bg-slate-50 border-r border-slate-100 sticky left-0 z-10">
                                    {{ $isNewLine ? $item->line : '' }}
                                </td>
                                <td class="p-0 border-r border-slate-200 bg-yellow-50">
                                    <input type="number"
                                        value="{{ $item->uph }}"
                                        data-line="{{ $item->line }}"
                                        class="uph-input w-full h-full border-0 bg-transparent text-center font-bold text-slate-900 focus:ring-1 focus:ring-indigo-500 p-2"
                                        @change="updateUph('{{ $item->id }}', $event.target.value, '{{ $item->line }}')">
                                </td>
                                <td class="px-4 py-3 text-slate-600 bg-white group-hover:bg-slate-50 border-r border-slate-100 sticky left-24 z-10">
                                    {{ $item->customerPart->customer_part_name ?? '-' }}
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $item->customerPart->customer_part_no ?? '' }}</div>
                                </td>
                                @foreach ($days as $d)
                                    @php
                                        $dKey = $d->format('Y-m-d');
                                        $qty = $plans->get($dKey)?->jig_qty;
                                    @endphp
                                    <td class="p-0 border-r border-slate-200 bg-yellow-50 relative">
                                        <input type="number"
                                            value="{{ $qty }}"
                                            class="w-full h-full border-0 bg-transparent text-center text-slate-700 focus:ring-1 focus:ring-indigo-500 p-2 placeholder-slate-300"
                                            placeholder=""
                                            @change="updatePlan('{{ $item->id }}', '{{ $dKey }}', $event.target.value)">
                                    </td>
                                @endforeach
                                <td class="px-2">
                                    <form action="{{ route('outgoing.input-jig.delete', $item->id) }}" method="POST" onsubmit="return confirm('Delete row?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-slate-400 hover:text-red-500">&times;</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        
                        {{-- Add Row Form --}}
                         <tr class="bg-slate-50 border-t-2 border-slate-200">
                            <form action="{{ route('outgoing.input-jig.store') }}" method="POST">
                                @csrf
                                <td class="p-2 border-r border-slate-200 sticky left-0 z-10 bg-slate-50">
                                    <input type="text" name="line" placeholder="Line" class="w-full text-xs rounded border-slate-300" required>
                                </td>
                                <td class="p-2 border-r border-slate-200 bg-slate-50 text-center text-xs text-slate-400">
                                    (Auto)
                                </td>
                                <td class="p-2 border-r border-slate-200 sticky left-24 z-10 bg-slate-50">
                                    <div x-data="{
                                        open: false,
                                        search: '',
                                        selectedId: '',
                                        selectedName: '',
                                        top: 0,
                                        left: 0,
                                        width: 0,
                                        options: [
                                            @foreach($customerParts as $p)
                                            {
                                                id: '{{ $p->id }}',
                                                name: `{{ $p->customer_part_name ?? '' }} {{ $p->case_name ? '- ' . $p->case_name : '' }}`
                                            },
                                            @endforeach
                                        ],
                                        get filteredOptions() {
                                            if (this.search === '') return this.options;
                                            return this.options.filter(option => option.name.toLowerCase().includes(this.search.toLowerCase()));
                                        },
                                        init() {
                                            this.$watch('open', value => {
                                                if (value) {
                                                    const rect = this.$refs.trigger.getBoundingClientRect();
                                                    this.top = rect.bottom;
                                                    this.left = rect.left;
                                                    this.width = rect.width;
                                                    
                                                    // Adjust if falls off screen
                                                    if (window.innerHeight - this.top < 240) {
                                                         this.top = rect.top - 240; // Flip up
                                                    }
                                                }
                                            });
                                        },
                                        select(option) {
                                            this.selectedId = option.id;
                                            this.selectedName = option.name;
                                            this.open = false;
                                            this.search = '';
                                        }
                                    }" class="relative w-full"
                                      @scroll.window="open = false" 
                                      @resize.window="open = false">
                                        <input type="hidden" name="customer_part_id" x-model="selectedId" required>
                                        
                                        <div x-ref="trigger" @click="open = !open" 
                                             class="w-full text-xs rounded border border-slate-300 bg-white px-2 py-1.5 flex justify-between items-center cursor-pointer min-h-[28px]">
                                            <span x-text="selectedName || 'Select Part'" :class="{'text-slate-400': !selectedName, 'text-slate-900': selectedName}" class="truncate block max-w-[150px]"></span>
                                            <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>

                                        <div x-show="open" @click.away="open = false" 
                                             class="fixed z-[9999] bg-white border border-slate-200 rounded-md shadow-lg max-h-60 overflow-y-auto"
                                             :style="`top: ${top}px; left: ${left}px; width: ${Math.max(width, 300)}px;`"
                                             x-cloak>
                                            <div class="sticky top-0 bg-white p-2 border-b border-slate-100">
                                                <input x-model="search" type="text" 
                                                       class="w-full text-xs rounded border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" 
                                                       placeholder="Search...">
                                            </div>
                                            <ul class="py-1">
                                                <template x-for="option in filteredOptions" :key="option.id">
                                                    <li @click="select(option)" 
                                                        class="px-3 py-2 text-xs hover:bg-slate-100 cursor-pointer text-slate-700 truncate"
                                                        :class="{'bg-indigo-50 text-indigo-700': selectedId == option.id}"
                                                        x-text="option.name">
                                                    </li>
                                                </template>
                                                <li x-show="filteredOptions.length === 0" class="px-3 py-2 text-xs text-slate-400 text-center">
                                                    No results found
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                                <td colspan="{{ count($days) + 1 }}" class="p-2 bg-slate-50">
                                    <button class="px-3 py-1 bg-slate-800 text-white text-xs font-bold rounded hover:bg-slate-900">+ Add Row</button>
                                </td>
                            </form>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function inputJig() {
            return {
                async updateUph(id, value, line) {
                    try {
                        await axios.post(`/outgoing/input-jig/${id}/uph`, { uph: value });
                        // Sync all UPH inputs on the same line visually
                        document.querySelectorAll(`.uph-input[data-line="${line}"]`).forEach(inp => {
                            inp.value = value;
                        });
                    } catch (e) {
                        alert('Failed to update UPH');
                    }
                },
                async updatePlan(id, date, value) {
                    try {
                        await axios.post(`/outgoing/input-jig/${id}/plan`, { date: date, qty: value });
                    } catch (e) {
                         alert('Failed to update value');
                    }
                }
            }
        }
    </script>
@endsection
