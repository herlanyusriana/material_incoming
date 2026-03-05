<x-app-layout>
    <x-slot name="header">M01 Work Orders</x-slot>

    <div class="space-y-4">
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-800">Work Order List</h2>
            <a href="{{ route('production.work-orders.create') }}"
                class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                + Create WO
            </a>
        </div>

        <form method="GET" class="rounded-xl border bg-white p-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div>
                    <label class="text-xs font-semibold text-slate-600">Status</label>
                    <select name="status" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                        <option value="">All</option>
                        @foreach (['open' => 'Open', 'in_progress' => 'In Progress', 'qc' => 'QC', 'closed' => 'Closed'] as $k => $v)
                            <option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Source</label>
                    <select name="source_type" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                        <option value="">All</option>
                        <option value="manual" @selected($sourceType === 'manual')>Manual</option>
                        <option value="mrp" @selected($sourceType === 'mrp')>MRP</option>
                        <option value="outgoing_daily" @selected($sourceType === 'outgoing_daily')>Daily Planning Outgoing</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Search</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="WO no / FG part"
                        class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
                <a href="{{ route('production.work-orders.index') }}"
                    class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
            </div>
        </form>

        <form method="POST" action="{{ route('production.work-orders.generate') }}" class="rounded-xl border bg-white p-4">
            @csrf
            <input type="hidden" name="source_type" value="manual">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-800">Quick Generate (Manual)</h3>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500">Checklist beberapa FG, isi Qty + Date, lalu generate jadi 1 WO</span>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                        Generate 1 WO (Multi FG)
                    </button>
                </div>
            </div>
            <div class="max-h-80 overflow-y-auto rounded-lg border border-slate-200">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2 text-center">Pick</th>
                            <th class="px-3 py-2 text-left">FG Part</th>
                            <th class="px-3 py-2 text-left">Qty Plan</th>
                            <th class="px-3 py-2 text-left">Plan Date</th>
                            <th class="px-3 py-2 text-left">Priority</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($fgPartsQuick as $fg)
                            @php($i = $loop->index)
                            <tr>
                                <td class="px-3 py-2 text-center">
                                    <input type="checkbox" name="lines[{{ $i }}][enabled]" value="1" class="rounded border-slate-300 text-indigo-600">
                                    <input type="hidden" name="lines[{{ $i }}][fg_part_id]" value="{{ $fg->id }}">
                                </td>
                                <td class="px-3 py-2">
                                    <div class="font-medium text-slate-900">{{ $fg->part_no }}</div>
                                    <div class="text-xs text-slate-500">{{ $fg->part_name }}</div>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" name="lines[{{ $i }}][qty_plan]" min="0.0001" step="0.0001"
                                        class="w-28 rounded-lg border-slate-200 text-sm" placeholder="Qty">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="date" name="lines[{{ $i }}][plan_date]" value="{{ now()->format('Y-m-d') }}"
                                        class="w-40 rounded-lg border-slate-200 text-sm">
                                </td>
                                <td class="px-3 py-2">
                                    <select name="lines[{{ $i }}][priority]" class="w-24 rounded-lg border-slate-200 text-sm">
                                        @foreach([1,2,3,4,5] as $prio)
                                            <option value="{{ $prio }}" @selected($prio===3)>{{ $prio }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-8 text-center text-slate-500">
                                    Tidak ada FG aktif dengan BOM aktif.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left">WO</th>
                        <th class="px-4 py-3 text-left">FG</th>
                        <th class="px-4 py-3 text-left">Plan</th>
                        <th class="px-4 py-3 text-left">Source</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($orders as $wo)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $wo->wo_no }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $wo->fgPart?->part_no }}</div>
                                <div class="text-xs text-slate-500">{{ $wo->fgPart?->part_name }}</div>
                                @if(collect(data_get($wo->source_payload_json, 'lines', []))->count() > 1)
                                    <div class="mt-1 inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700">
                                        MULTI FG ({{ collect(data_get($wo->source_payload_json, 'lines', []))->count() }})
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div>{{ optional($wo->plan_date)->format('Y-m-d') }}</div>
                                <div class="font-mono text-xs text-slate-600">{{ number_format((float) $wo->qty_plan, 2) }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700">
                                    {{ strtoupper(str_replace('_', ' ', $wo->source_type)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                                    {{ strtoupper(str_replace('_', ' ', $wo->status)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('production.work-orders.show', $wo) }}"
                                    class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-slate-500">No work orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t bg-slate-50 px-4 py-3">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
