<x-app-layout>
    <x-slot name="header">
        Planning • MPS
    </x-slot>

    <div class="py-6" x-data="planningMps()">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @php
                $viewMode = $view ?? 'calendar';
                $weeksCountValue = (int) ($weeksCount ?? 4);
                $weeksValue = $weeks ?? [];
                $searchValue = $q ?? '';
                $classificationValue = strtoupper(trim((string) ($classification ?? 'FG')));
                $classificationValue = $classificationValue === '' ? 'ALL' : $classificationValue;
                $hideEmptyValue = $hideEmpty ?? true;
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
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <a
                            href="{{ route('planning.mps.index', array_merge(request()->query(), ['view' => 'calendar'])) }}"
                            class="px-4 py-2 rounded-xl font-semibold border {{ $viewMode === 'calendar' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50' }}"
                        >
                            Weekly View
                        </a>
                        <a
                            href="{{ route('planning.mps.index', array_merge(request()->query(), ['view' => 'monthly'])) }}"
                            class="px-4 py-2 rounded-xl font-semibold border {{ $viewMode === 'monthly' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50' }}"
                        >
                            Monthly View
                        </a>
                        <a
                            href="{{ route('planning.mps.index', array_merge(request()->query(), ['view' => 'list'])) }}"
                            class="px-4 py-2 rounded-xl font-semibold border {{ $viewMode === 'list' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50' }}"
                        >
                            List View
                        </a>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <a href="{{ route('planning.mps.export', request()->query()) }}" class="px-4 py-2 rounded-xl font-semibold border bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100 transition-colors flex items-center gap-2">
                            📥 Export Excel
                        </a>
                        <a href="{{ route('planning.mps.history') }}" class="px-4 py-2 rounded-xl font-semibold border bg-white border-slate-200 text-slate-700 hover:bg-slate-50">
                            📊 History
                        </a>
                        <form method="POST" action="{{ route('planning.mps.clear') }}" onsubmit="return confirm('Are you sure you want to clear ALL MPS data? This cannot be undone!');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 rounded-xl font-semibold border bg-red-600 border-red-600 text-white hover:bg-red-700">
                                🗑️ Clear All
                            </button>
                        </form>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <form method="GET" class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="view" value="{{ $viewMode }}">

                            <input
                                name="minggu"
                                value="{{ $minggu }}"
                                class="rounded-xl border-slate-200"
                                placeholder="{{ $viewMode === 'monthly' ? 'YYYY-MM' : ($viewMode === 'list' ? 'All Weeks' : '2026-W01') }}"
                                title="Period"
                            >

                            @if($viewMode === 'calendar' || $viewMode === 'monthly')
                                <select name="weeks" class="rounded-xl border-slate-200" title="{{ $viewMode === 'monthly' ? 'Months' : 'Weeks' }}">
                                    @foreach([4,6,8,12,16,24,26,40,52] as $n)
                                        <option value="{{ $n }}" @selected($weeksCountValue === $n)>{{ $n }} {{ $viewMode === 'monthly' ? 'months' : 'weeks' }}</option>
                                    @endforeach
                                </select>
                            @endif

                            <select name="classification" class="rounded-xl border-slate-200" title="Classification">
                                <option value="ALL" @selected($classificationValue === 'ALL')>All</option>
                                <option value="FG" @selected($classificationValue === 'FG')>FG</option>
                                <option value="WIP" @selected($classificationValue === 'WIP')>WIP</option>
                                <option value="RM" @selected($classificationValue === 'RM')>RM</option>
                            </select>

                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">⌕</span>
                                <input
                                    name="q"
                                    value="{{ $searchValue }}"
                                    class="rounded-xl border-slate-200 pl-10"
                                    placeholder="Search part..."
                                >
                            </div>

                            <div class="flex items-center gap-2 bg-slate-50 px-3 py-2 rounded-xl border border-slate-200">
                                <label class="text-xs font-semibold text-slate-600 flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="hide_empty" value="on" @checked($hideEmptyValue) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>Hide Empty</span>
                                </label>
                            </div>

                            <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold shadow-sm hover:bg-slate-800">Load</button>
                        </form>

                        @if($viewMode === 'calendar')
                            <form method="POST" action="{{ route('planning.mps.generate-range') }}">
                                @csrf
                                <input type="hidden" name="minggu" value="{{ $minggu }}">
                                <input type="hidden" name="weeks" value="{{ $weeksCountValue }}">
                                <input type="hidden" name="hide_empty" value="{{ $hideEmptyValue ? 'on' : 'off' }}">
                                <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-sm">Generate Range (Refresh Forecast)</button>
                            </form>
                        @elseif($viewMode === 'monthly')
                            <!-- Monthly actions if any -->
                        @else
                            <form method="POST" action="{{ route('planning.mps.generate') }}">
                                @csrf
                                <input type="hidden" name="minggu" value="{{ $minggu }}">
                                <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Generate (Refresh Forecast)</button>
                            </form>
                            <button
                                type="submit"
                                form="approve-form"
                                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold"
                                onclick="return confirm('Approve selected MPS rows?')"
                            >
                                Approve Selected
                            </button>
                        @endif
                    </div>
                </div>

                @if($viewMode === 'calendar' || $viewMode === 'monthly')
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="min-w-[980px] w-full table-fixed text-sm divide-y divide-slate-200">
                            <colgroup>
                                <col class="w-44">
                                <col class="w-80">
                                <col class="w-48">
                                @if($viewMode === 'calendar')
                                    @foreach($weeksValue as $w)
                                        <col class="w-32">
                                    @endforeach
                                @else
                                    @foreach($months as $m)
                                        <col class="w-32">
                                    @endforeach
                                @endif
                            </colgroup>
                            <thead class="bg-slate-50">
                                <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Part GCI</th>
                                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Part Name</th>
                                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Model</th>
                                    @if($viewMode === 'calendar')
                                        @foreach($weeksValue as $w)
                                            @php
                                                preg_match('/^(\d{4})-W(\d{2})$/', $w, $wm);
                                                $label = $wm ? ('W' . $wm[2] . '-' . $wm[1]) : $w;
                                            @endphp
                                            <th class="px-4 py-3 text-center font-semibold whitespace-nowrap">{{ $label }}</th>
                                        @endforeach
                                    @else
                                        @foreach($months as $m)
                                            <th class="px-4 py-3 text-center font-semibold whitespace-nowrap">{{ \Carbon\Carbon::parse($m . '-01')->format('M Y') }}</th>
                                        @endforeach
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse(($parts ?? []) as $p)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-mono text-xs font-semibold whitespace-nowrap overflow-hidden text-ellipsis">{{ $p->part_no }}</td>
                                        <td class="px-4 py-3 text-slate-700 whitespace-nowrap overflow-hidden text-ellipsis">{{ $p->part_name }}</td>
                                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap overflow-hidden text-ellipsis">{{ $p->model }}</td>
                                        
                                        @if($viewMode === 'calendar')
                                            @php $byWeek = $p->mps->keyBy('minggu'); @endphp
                                            @foreach($weeksValue as $w)
                                                @php $cell = $byWeek->get($w); @endphp
                                                <td class="px-4 py-3 text-center">
                                                    @if($cell)
                                                        <button
                                                            type="button"
                                                            class="inline-flex items-center justify-center w-full px-3 py-1 rounded-full text-xs font-semibold {{ $cell->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800 hover:bg-indigo-200' }}"
                                                            @click="openCell(@js(['part_id' => $p->id, 'part_no' => $p->part_no, 'part_name' => $p->part_name, 'minggu' => $w, 'planned_qty' => $cell->planned_qty, 'status' => $cell->status]))"
                                                        >
                                                            {{ formatNumber($cell->planned_qty) }}
                                                        </button>
                                                    @else
                                                        <button
                                                            type="button"
                                                            class="inline-flex items-center justify-center w-full h-8 rounded-full border border-slate-200 text-slate-400 hover:text-slate-600 hover:border-slate-300"
                                                            @click="openCell(@js(['part_id' => $p->id, 'part_no' => $p->part_no, 'part_name' => $p->part_name, 'minggu' => $w, 'planned_qty' => 0, 'status' => 'draft']))"
                                                        >
                                                            +
                                                        </button>
                                                    @endif
                                                </td>
                                            @endforeach
                                        @else
                                            <!-- Monthly -->
                                            @foreach($months as $m)
                                                @php
                                                    // Filter MPS records belonging to this month
                                                    // We can replicate controller logic or use simplified check (startswith YYYY-MM if week matches?)
                                                    // But our weeks are YYYY-Wxx.
                                                    // We need to know which weeks belong to $m
                                                    // Using Carbon again in View is heavy but acceptable for 25 items x 4 months = 100 iterations.
                                                    // Better: Controller passed mapping, but filtering collection is fine too.
                                                    
                                                    // Simplified: Just sum all MPS whose week falls in this month
                                                    // We can use the collection filter.
                                                    $monthSum = $p->mps->filter(function($item) use ($m) {
                                                        // Convert item->minggu (2026-W05) to Month.
                                                        // A bit heavy.
                                                        // Let's rely on string comparison if possible? No.
                                                        // Use Carbon.
                                                        $y = (int) substr($item->minggu, 0, 4);
                                                        $w = (int) substr($item->minggu, 6, 2);
                                                        $d = \Carbon\Carbon::now()->setISODate($y, $w, 1); // Monday
                                                        return $d->format('Y-m') === $m;
                                                    })->sum('planned_qty');
                                                    
                                                @endphp
                                                <td class="px-4 py-3 text-center">
                                                     <button
                                                        type="button"
                                                        class="inline-flex items-center justify-center w-full px-3 py-1 rounded-full text-xs font-semibold bg-white border border-slate-300 text-slate-700 hover:bg-slate-50"
                                                        @click="openCell(@js(['part_id' => $p->id, 'part_no' => $p->part_no, 'part_name' => $p->part_name, 'minggu' => $m, 'planned_qty' => $monthSum, 'status' => 'draft']))"
                                                        title="Click to set monthly plan"
                                                    >
                                                        {{ formatNumber($monthSum) }}
                                                    </button>
                                                </td>
                                            @endforeach
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 2 + count($viewMode === 'monthly' ? $months : $weeksValue) }}" class="px-4 py-8 text-center text-slate-500">No parts found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(($parts ?? null) && method_exists($parts, 'links'))
                        <div class="pt-2">
                            {{ $parts->links() }}
                        </div>
                    @endif
                @else
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <form id="approve-form" method="POST" action="{{ route('planning.mps.approve') }}">
                            @csrf
                            <input type="hidden" name="minggu" value="{{ $minggu }}">
                        </form>

                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left font-semibold">
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" class="rounded border-slate-300" x-model="selectAll" @change="toggleAll()">
                                            <span>Select</span>
                                        </label>
                                    </th>
                                    <th class="px-4 py-3 text-left font-semibold">Minggu</th>
                                    <th class="px-4 py-3 text-left font-semibold">Part GCI</th>
                                    <th class="px-4 py-3 text-right font-semibold">Forecast Qty</th>
                                    <th class="px-4 py-3 text-right font-semibold">Planned Qty</th>
                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse (($rows ?? []) as $r)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3">
                                            @if ($r->status !== 'approved')
                                                <input
                                                    type="checkbox"
                                                    name="mps_ids[]"
                                                    value="{{ $r->id }}"
                                                    form="approve-form"
                                                    class="rounded border-slate-300"
                                                    x-model="selected"
                                                >
                                            @else
                                                <span class="text-xs text-slate-300">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-mono text-xs">{{ $r->minggu }}</td>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold">{{ $r->part?->part_no ?? '-' }}</div>
                                            <div class="text-xs text-slate-600">{{ $r->part?->model ?? '-' }}</div>
                                            <div class="text-xs text-slate-500">{{ $r->part?->part_name ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-xs">{{ formatNumber($r->forecast_qty) }}</td>
                                        <td class="px-4 py-3 text-right font-mono text-xs font-semibold">{{ formatNumber($r->planned_qty) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $r->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                                {{ strtoupper($r->status) }}
                                            </span>
                                            @if ($r->status === 'approved' && $r->approved_at)
                                                <div class="text-xs text-slate-500 mt-1">Approved {{ $r->approved_at->format('Y-m-d H:i') }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if ($r->status !== 'approved')
                                                <button type="button" class="text-indigo-600 hover:text-indigo-800 font-semibold" @click="openEdit(@js($r))">Edit</button>
                                            @else
                                                <span class="text-xs text-slate-400">Locked</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">No MPS rows. Click Generate.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="text-xs text-slate-500 px-1">
                Flow: Mapping → (Planning/PO) → Forecast (auto) → MPS (draft) → Approve → MRP. Tombol Generate di sini otomatis refresh Forecast dulu sebelum bikin/refresh MPS.
            </div>
        </div>



        <script>
            function planningMps() {
                return {
                    // Slide-over State
                    slideOverOpen: false, 
                    isLoading: false,
                    slideOverContent: '',
                    
                    selectAll: false,
                    selected: [],
                    toggleAll() {
                        const checkboxes = document.querySelectorAll('input[name="mps_ids[]"]');
                        this.selected = this.selectAll
                            ? Array.from(checkboxes).map((c) => c.value)
                            : [];
                    },
                    
                    async openCell(payload) {
                        this.slideOverOpen = true;
                        this.isLoading = true;
                        this.slideOverContent = '';
                        
                        // Construct URL for detail
                        // payload has part_id, minggu
                        const url = `{{ route('planning.mps.detail') }}?part_id=${payload.part_id}&minggu=${payload.minggu}`;
                        
                        try {
                            const response = await fetch(url, {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            const html = await response.text();
                            this.slideOverContent = html;
                        } catch (error) {
                            console.error('Error:', error);
                            this.slideOverContent = '<div class="p-4 text-red-500">Error loading details.</div>';
                        } finally {
                            this.isLoading = false;
                        }
                    },
                    
                    async openEdit(r) {
                        // For list view - r is the row object
                        this.slideOverOpen = true;
                        this.isLoading = true;
                        this.slideOverContent = '';
                        
                        const url = `{{ route('planning.mps.detail') }}?part_id=${r.part_id}&minggu=${r.minggu}`;
                         try {
                            const response = await fetch(url, {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            const html = await response.text();
                            this.slideOverContent = html;
                        } catch (error) {
                            console.error('Error:', error);
                            this.slideOverContent = '<div class="p-4 text-red-500">Error loading details.</div>';
                        } finally {
                            this.isLoading = false;
                        }
                    },
                    
                    close() { this.slideOverOpen = false; },
                }
            }
        </script>
        
        <!-- Slide-over -->
        <div class="relative z-50" aria-labelledby="slide-over-title" role="dialog" aria-modal="true" x-show="slideOverOpen" style="display: none;">
             <!-- Background backdrop -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 x-show="slideOverOpen"
                 x-transition:enter="ease-in-out duration-500"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in-out duration-500"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="slideOverOpen = false"></div>
        
            <div class="fixed inset-0 overflow-hidden pointer-events-none">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                        <div class="pointer-events-auto w-screen max-w-md"
                             x-show="slideOverOpen"
                             x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
                             x-transition:enter-start="translate-x-full"
                             x-transition:enter-end="translate-x-0"
                             x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
                             x-transition:leave-start="translate-x-0"
                             x-transition:leave-end="translate-x-full">
                            
                            <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                                <div class="px-4 py-6 sm:px-6 bg-slate-50 border-b">
                                    <div class="flex items-start justify-between">
                                        <h2 class="text-lg font-semibold text-slate-900" id="slide-over-title">MPS Detail</h2>
                                        <div class="ml-3 flex h-7 items-center">
                                            <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" @click="slideOverOpen = false">
                                                <span class="sr-only">Close panel</span>
                                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="relative flex-1 px-4 py-6 sm:px-6">
                                    <!-- Content -->
                                    <div x-show="isLoading" class="flex justify-center py-12">
                                        <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    <div x-show="!isLoading" x-html="slideOverContent"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</x-app-layout>
