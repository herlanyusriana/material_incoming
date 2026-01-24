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
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                @php
                    $displayRequirements = collect($requirements)
                        ->filter(fn ($r) => $r->customer && $r->gci_part)
                        ->values();
                @endphp

                <form action="{{ route('outgoing.generate-so-bulk') }}" method="POST" onsubmit="return confirm('Generate Delivery Plan + Delivery Notes from selected requirements?')">
                    @csrf
                    <div class="flex flex-wrap items-end justify-between gap-3 px-4 py-4 border-b border-slate-200 bg-slate-50">
                        <div class="flex flex-wrap items-end gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Truck</label>
                                <select name="truck_id" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">Select truck...</option>
                                    @foreach(($trucks ?? []) as $t)
                                        <option value="{{ $t->id }}">{{ $t->plate_no }}{{ $t->type ? ' • ' . $t->type : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Driver</label>
                                <select name="driver_id" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">Select driver...</option>
                                    @foreach(($drivers ?? []) as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }}{{ $d->phone ? ' • ' . $d->phone : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="text-xs text-slate-500 max-w-xl">
                                Pilih beberapa baris (multi-select). Sistem akan membuat <span class="font-semibold">1 Delivery Note per customer</span> untuk tanggal yang sama, lalu assign ke Delivery Plan (truck+driver).
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100" onclick="toggleAllReq(true)">
                                Select all
                            </button>
                            <button type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100" onclick="toggleAllReq(false)">
                                Clear
                            </button>
                            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">
                                Generate SO
                            </button>
                        </div>
                    </div>

                    <table class="w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider w-12">
                                    <input type="checkbox" class="rounded border-slate-300" onclick="toggleAllReq(this.checked)">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Customer</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Seq</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Part Details</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Total Qty</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Std Packing</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Load (Packs)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($displayRequirements as $idx => $req)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" class="req-checkbox rounded border-slate-300" name="selected[]" value="{{ $idx }}">
                                        <input type="hidden" name="lines[{{ $idx }}][date]" value="{{ $req->date->toDateString() }}">
                                        <input type="hidden" name="lines[{{ $idx }}][customer_id]" value="{{ $req->customer->id }}">
                                        <input type="hidden" name="lines[{{ $idx }}][gci_part_id]" value="{{ $req->gci_part->id }}">
                                        <input type="hidden" name="lines[{{ $idx }}][qty]" value="{{ $req->total_qty }}">
                                        @foreach(($req->source_row_ids ?? []) as $rowId)
                                            <input type="hidden" name="lines[{{ $idx }}][row_ids][]" value="{{ $rowId }}">
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-700">
                                        {{ $req->date->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-slate-900">{{ $req->customer->name }}</div>
                                        <div class="text-[10px] text-slate-500 font-bold uppercase">{{ $req->customer->code ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-sm text-center">
                                        {{ $req->sequence ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-slate-600 font-medium">{{ $req->gci_part->part_name ?? '-' }}</div>
                                        <div class="text-xs text-indigo-600 font-mono">{{ $req->gci_part->part_no }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-900">
                                        {{ number_format($req->total_qty) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600">
                                        {{ number_format($req->packing_std) }} {{ $req->uom }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-indigo-700">
                                        {{ number_format($req->packing_load) }} Packs
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                            <p class="font-medium text-slate-900">No delivery requirements found.</p>
                                            <p class="text-sm">Try adjusting the date range or check Daily Planning.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleAllReq(checked) {
            document.querySelectorAll('.req-checkbox').forEach((el) => {
                el.checked = !!checked;
            });
        }
    </script>
@endsection
