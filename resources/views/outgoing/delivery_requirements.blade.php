@extends('outgoing.layout')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900">Delivery Requirements</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        Aggregated delivery requirements based on Daily Planning.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-4 items-end border-t border-slate-100 pt-6">
                <form action="{{ route('outgoing.delivery-requirements') }}" method="GET" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">From</label>
                        <input
                            type="date"
                            name="date_from"
                            value="{{ $dateFrom->toDateString() }}"
                            class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">To</label>
                        <input
                            type="date"
                            name="date_to"
                            value="{{ $dateTo->toDateString() }}"
                            class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
                        View
                    </button>
                </form>
                
                @if(isset($sortBy))
                    <div class="text-xs text-slate-500">
                        Sorted by: <span class="font-semibold text-slate-700">{{ ucfirst($sortBy) }}</span>
                        ({{ $sortDir === 'asc' ? '↑ Ascending' : '↓ Descending' }})
                    </div>
                @endif
            </div>
        </div>

        <form action="{{ route('outgoing.generate-so-bulk') }}" method="POST">
            @csrf
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 border-b border-slate-200 p-4 flex items-center justify-between">
                    <div class="text-sm font-bold text-slate-700">Selected Requirements</div>
                    <button type="submit" class="rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 shadow-sm transition-all focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Generate SO for Selected
                    </button>
                </div>
                <div class="overflow-x-auto">
                    @php
                        $displayRequirements = collect($requirements)
                            ->filter(fn ($r) => $r->customer && $r->gci_part)
                            ->values();
                    @endphp
                    <table class="w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left w-10">
                                    <input type="checkbox" id="selectAll" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" onclick="toggleAll(this)">
                                </th>
                                @php
                                    $sortDir = $sortDir ?? 'asc';
                                    $sortBy = $sortBy ?? 'date';
                                    $makeSort = function($column) use ($sortBy, $sortDir, $dateFrom, $dateTo) {
                                        $newDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';
                                        return route('outgoing.delivery-requirements', [
                                            'date_from' => $dateFrom->toDateString(),
                                            'date_to' => $dateTo->toDateString(),
                                            'sort_by' => $column,
                                            'sort_dir' => $newDir
                                        ]);
                                    };
                                    $sortIcon = function($column) use ($sortBy, $sortDir) {
                                        if ($sortBy !== $column) return '↕';
                                        return $sortDir === 'asc' ? '↑' : '↓';
                                    };
                                @endphp
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">#</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Del Class</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    <a href="{{ $makeSort('date') }}" class="text-slate-500 hover:text-slate-700">
                                        Date {!! $sortIcon('date') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    <a href="{{ $makeSort('customer') }}" class="text-slate-500 hover:text-slate-700">
                                        Customer {!! $sortIcon('customer') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    <a href="{{ $makeSort('sequence') }}" class="text-slate-500 hover:text-slate-700">
                                        Seq {!! $sortIcon('sequence') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    <a href="{{ $makeSort('part') }}" class="text-slate-500 hover:text-slate-700">
                                        Part Details {!! $sortIcon('part') !!}
                                    </a>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Total Qty</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Delivery Pack Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($displayRequirements as $idx => $req)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" name="selected[]" value="{{ $idx }}" class="row-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <input type="hidden" name="lines[{{ $idx }}][date]" value="{{ $req->date->toDateString() }}">
                                        <input type="hidden" name="lines[{{ $idx }}][customer_id]" value="{{ $req->customer->id }}">
                                        <input type="hidden" name="lines[{{ $idx }}][gci_part_id]" value="{{ $req->gci_part->id }}">
                                        <input type="hidden" name="lines[{{ $idx }}][qty]" value="{{ $req->delivery_pack_qty ?? $req->total_qty }}">
                                        @foreach($req->source_row_ids as $rid)
                                            <input type="hidden" name="lines[{{ $idx }}][row_ids][]" value="{{ $rid }}">
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 font-semibold">{{ $idx + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="text-slate-700 font-bold">{{ $req->gci_part?->standardPacking?->delivery_class ?? '-' }}</div>
                                        <div class="text-[10px] text-slate-500 uppercase font-bold">{{ $req->gci_part?->standardPacking?->trolley_type ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-700">
                                        {{ $req->date->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-900 font-bold">
                                        {{ $req->customer->name }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-sm text-center">
                                        @if(isset($req->sequences_consolidated) && count($req->sequences_consolidated) > 1)
                                            <span class="inline-flex items-center gap-1">
                                                <span class="font-bold">{{ $req->sequence }}</span>
                                                <span class="text-[10px] text-slate-500" title="Consolidated from sequences: {{ implode(', ', $req->sequences_consolidated) }}">
                                                    ({{ count($req->sequences_consolidated) }}×)
                                                </span>
                                            </span>
                                        @else
                                            {{ $req->sequence ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-slate-600 font-medium">{{ $req->gci_part->part_name ?? '-' }}</div>
                                        <div class="text-xs text-indigo-600 font-mono">{{ $req->gci_part->part_no }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-900">
                                        {{ number_format($req->total_qty) }}
                                        @if(($req->stock_at_customer ?? 0) > 0 || ($req->stock_used ?? 0) > 0)
                                            <div class="mt-0.5 text-[10px] font-semibold text-slate-500">
                                                Gross {{ number_format($req->gross_qty ?? 0) }} • Stock@Cust {{ number_format($req->stock_at_customer ?? 0) }} • Used {{ number_format($req->stock_used ?? 0) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-indigo-700">
                                        <div>{{ number_format($req->delivery_pack_qty ?? $req->total_qty) }} {{ $req->uom }}</div>
                                        <div class="text-[10px] text-slate-500 font-semibold mt-0.5">
                                            {{ number_format($req->packing_load ?? 0) }} Packs × {{ number_format($req->packing_std) }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                            <p class="font-medium text-slate-900">No delivery requirements found.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>

    <script>
        function toggleAll(el) {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(c => c.checked = el.checked);
        }
    </script>
@endsection
    </div>

@endsection
