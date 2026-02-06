@extends('outgoing.layout')

@section('content')
    <div class="space-y-6" x-data="dailyPlanning()">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <div class="h-12 w-12 rounded-xl bg-slate-900 flex items-center justify-center text-white font-black">
                        DP
                    </div>
                    <div>
                        <div class="text-2xl md:text-3xl font-black text-slate-900">Daily Planning</div>
                        <div class="mt-1 text-sm text-slate-600">
                            @if($plan)
                                Plan #{{ $plan->id }} • {{ $dateFrom->format('d M Y') }} - {{ $dateTo->format('d M Y') }}
                                <span
                                    class="ml-2 px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-[10px] font-bold uppercase tracking-wider border border-indigo-100 italic">
                                    Last Update: {{ $plan->updated_at->format('d M Y H:i') }}
                                </span>
                            @else
                                Viewing Period: {{ $dateFrom->format('d M Y') }} - {{ $dateTo->format('d M Y') }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if(!$plan)
                        <form action="{{ route('outgoing.daily-planning.create') }}" method="POST">
                            @csrf
                            <input type="hidden" name="date_from" value="{{ $dateFrom->toDateString() }}">
                            <input type="hidden" name="date_to" value="{{ $dateTo->toDateString() }}">
                            <button
                                class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 shadow-sm">
                                + Create New Plan
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('outgoing.daily-planning.template', ['date_from' => $dateFrom->toDateString(), 'date_to' => $dateTo->toDateString()]) }}"
                        class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Download Template
                    </a>

                    <form action="{{ route('outgoing.daily-planning.import') }}" method="POST" enctype="multipart/form-data"
                        class="flex items-center gap-2">
                        @csrf
                        <label
                            class="cursor-pointer inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <span>Import Excel</span>
                            <input type="file" name="file" accept=".xlsx,.xls,.csv" class="hidden"
                                onchange="this.form.submit()">
                        </label>
                    </form>
                </div>
            </div>

            @if(isset($unmappedCount) && $unmappedCount > 0)
                <div class="mt-6 rounded-xl border border-yellow-200 bg-yellow-50 p-4" x-data="{ showDetails: false }">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <h3 class="text-sm font-bold text-yellow-800">Unmapped Parts Detected</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>There are <span class="font-bold">{{ $unmappedCount }}</span> parts in this plan that are not
                                    mapped to any GCI Part. These parts will not appear in Delivery Requirements correctly until
                                    mapped.</p>
                                <div class="mt-3 flex gap-3">
                                    <button @click="showDetails = true"
                                        class="font-bold text-yellow-800 underline hover:text-yellow-900">
                                        View Detail Unmapped Parts
                                    </button>
                                    <span class="text-yellow-400">|</span>
                                    <a href="{{ route('outgoing.product-mapping') }}"
                                        class="font-bold text-yellow-800 underline hover:text-yellow-900">
                                        Go to Product Mapping to resolve this &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal for Unmapped Details -->
                    <div x-show="showDetails" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
                        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showDetails = false"></div>
                        <div
                            class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col max-h-[80vh]">
                            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-yellow-50">
                                <h3 class="text-lg font-bold text-yellow-900">Unmapped Parts List</h3>
                                <button @click="showDetails = false" class="text-slate-400 hover:text-slate-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="p-6 overflow-y-auto">
                                <p class="text-sm text-slate-500 mb-4">The following parts were found in the uploaded plan but
                                    do not match any GCI FG Part (or mapped customer part).</p>
                                <div class="border border-slate-200 rounded-lg overflow-hidden">
                                    <table class="min-w-full divide-y divide-slate-200">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Part
                                                    No (from Excel)</th>
                                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Line
                                                </th>
                                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase">
                                                    Total Qty Plan</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-slate-100">
                                            @php
                                                // Calculate unmapped details on the fly mostly to avoid controller bloat,
                                                // or we can pass it from controller. Since we only have access to $plan here:
                                                $unmappedRows = $plan ? $plan->rows()->whereNull('gci_part_id')->with('cells')->get() : collect();
                                            @endphp
                                            @foreach($unmappedRows as $uRow)
                                                @php
                                                    $sumQty = $uRow->cells->sum('qty');
                                                    if ($sumQty <= 0)
                                                        continue;
                                                @endphp
                                                <tr class="hover:bg-slate-50">
                                                    <td class="px-4 py-3 font-mono text-sm font-bold text-slate-700">
                                                        {{ $uRow->part_no }}</td>
                                                    <td class="px-4 py-3 text-sm text-slate-600">{{ $uRow->production_line }}</td>
                                                    <td class="px-4 py-3 text-sm text-right font-medium text-slate-900">
                                                        {{ number_format($sumQty) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end">
                                <button @click="showDetails = false"
                                    class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-4 items-end border-t border-slate-100 pt-6">
                <form action="{{ route('outgoing.daily-planning') }}" method="GET" class="flex flex-wrap items-end gap-3">
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

                    @if($plan)
                        <div class="w-full md:w-auto md:ml-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Search</label>
                            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Line / Part No"
                                class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Rows</label>
                            <select name="per_page"
                                class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach([25, 50, 100, 200] as $n)
                                    <option value="{{ $n }}" @selected(($perPage ?? 50) === $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider w-24 border-r border-slate-200 sticky left-0 z-10 bg-slate-50">
                                Line</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider w-64 border-r border-slate-200 sticky left-24 z-10 bg-slate-50">
                                Customer Part Name</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider w-48 border-r border-slate-200 sticky left-88 z-10 bg-slate-50">
                                Customer Part No</th>
                            @foreach ($days as $index => $d)
                                <th
                                    class="px-2 py-2 text-center text-xs font-bold text-slate-700 border-r border-slate-200 min-w-[80px]">
                                    <div class="text-[10px] text-slate-400 font-normal">H{!! $index > 0 ? '+' . $index : '' !!}
                                    </div>
                                    <div>{{ $d->format('d/m') }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($rows as $row)
                            @php
                                $cellMap = $row->cells->keyBy(fn($c) => $c->plan_date->format('Y-m-d'));
                                $totalQty = 0;
                                foreach ($days as $d) {
                                    $k = $d->format('Y-m-d');
                                    $totalQty += (int) ($cellMap->get($k)?->qty ?? 0);
                                }
                            @endphp
                            <tr class="hover:bg-slate-50 group">
                                <td
                                    class="px-4 py-3 text-xs text-slate-600 bg-white group-hover:bg-slate-50 sticky left-0 z-10 border-r border-slate-100">
                                    {{ $row->production_line }}
                                </td>
                                <td
                                    class="px-4 py-3 text-xs text-slate-600 bg-white group-hover:bg-slate-50 sticky left-24 z-10 border-r border-slate-100">
                                    {{ $row->customerPart->customer_part_name ?? $row->part_name ?? '-' }}
                                    @if(isset($row->customerPart->case_name))
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $row->customerPart->case_name }}</div>
                                    @endif
                                </td>
                                <td
                                    class="px-4 py-3 font-mono text-xs font-bold text-indigo-700 bg-white group-hover:bg-slate-50 sticky left-88 z-10 border-r border-slate-100">
                                    {{ $row->customerPart->customer_part_no ?? $row->part_no }}
                                </td>
                                @foreach ($days as $d)
                                    @php
                                        $key = $d->format('Y-m-d');
                                        $cell = $cellMap->get($key);
                                        $qty = $cell?->qty;
                                    @endphp
                                    <td class="p-0 border-r border-slate-200 relative">
                                        <input type="number"
                                            class="w-full h-full border-0 bg-transparent text-center text-xs font-semibold text-slate-700 focus:ring-1 focus:ring-indigo-500 p-2 placeholder-slate-200"
                                            value="{{ $qty }}" placeholder="0"
                                            @change="updateCell('{{ $row->id }}', '{{ $key }}', 'qty', $event.target.value)">
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 3 + count($days) }}" class="px-6 py-12 text-center text-slate-500">
                                    @if($plan)
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                                </path>
                                            </svg>
                                            <p class="font-medium text-slate-900">Plan is empty.</p>
                                            <p class="text-sm">Add rows manually or import from Excel.</p>
                                        </div>
                                    @else
                                        <p>No plan found for this period. Click <strong>Create New Plan</strong> to start.</p>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-slate-50 font-bold sticky bottom-0 z-20 shadow-[0_-1px_3px_rgba(0,0,0,0.1)]">
                        <tr>
                            <td colspan="3"
                                class="px-4 py-3 text-right text-xs text-slate-500 uppercase tracking-wider border-r border-slate-200 sticky left-0 z-20 bg-slate-50">
                                Daily Total
                            </td>
                            @foreach ($days as $idx => $d)
                                @php
                                    $k = $d->format('Y-m-d');
                                    $t = $totalsByDate[$k] ?? 0;
                                @endphp
                                <td
                                    class="px-2 py-2 text-center text-xs text-indigo-700 border-r border-slate-200 bg-indigo-50/50">
                                    {{ number_format($t) }}
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($plan)
                <div class="border-t border-slate-200 p-4 bg-slate-50">
                    @if($rows instanceof \Illuminate\Pagination\AbstractPaginator)
                        <div class="mb-3">
                            {{ $rows->onEachSide(1)->links() }}
                        </div>
                    @endif
                    <form action="{{ route('outgoing.daily-planning.row', $plan->id) }}" method="POST"
                        class="flex gap-2 max-w-2xl">
                        @csrf
                        <input type="text" name="production_line" placeholder="Line (e.g. L1)"
                            class="w-24 rounded-lg border-slate-300 text-sm" required>
                        <input type="text" name="part_no" placeholder="Part No (e.g. 123-ABC)"
                            class="flex-1 rounded-lg border-slate-300 text-sm" required>
                        <button
                            class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 shadow-sm">
                            + Add Row
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <script>
        function dailyPlanning() {
            return {
                async updateCell(rowId, date, field, value) {
                    try {
                        const response = await fetch('{{ route('outgoing.daily-planning.cell') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ row_id: rowId, date, field, value })
                        });

                        if (response.ok) {
                            // Optional: green flash or toast
                            console.log('Saved');
                        } else {
                            alert('Failed to save');
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }
            }
        }
    </script>
@endsection