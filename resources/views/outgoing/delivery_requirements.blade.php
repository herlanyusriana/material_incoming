@extends('outgoing.layout')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900">Delivery Requirement</h1>
                    <div class="mt-2 flex items-center gap-2 text-sm text-slate-600">
                        <span class="font-bold uppercase tracking-wider text-slate-400">Delivery Date:</span>
                        <span class="font-bold text-slate-900">{{ $dateFrom->format('d M Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-4 items-end border-t border-slate-100 pt-6">
                <form action="{{ route('outgoing.delivery-requirements') }}" method="GET"
                    class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Date</label>
                        <input type="date" name="date_from" value="{{ $dateFrom->toDateString() }}"
                            onchange="document.getElementById('date_to').value = this.value"
                            class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <input type="hidden" name="date_to" id="date_to" value="{{ $dateFrom->toDateString() }}">
                    </div>
                    <button type="submit"
                        class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
                        View
                    </button>
                </form>
                <a href="{{ route('outgoing.delivery-requirements.export', ['date_from' => $dateFrom->toDateString(), 'date_to' => $dateTo->toDateString()]) }}"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Excel
                </a>
            </div>
        </div>

        <form action="{{ route('outgoing.generate-so-bulk') }}" method="POST">
            @csrf
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 border-b border-slate-200 p-4 flex items-center justify-between">
                    <div class="text-sm font-bold text-slate-700">Selected Requirements</div>
                    <button type="submit"
                        class="rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 shadow-sm transition-all focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Generate SO for Selected
                    </button>
                </div>
                <div class="overflow-x-auto">
                    @php
                        $displayRequirements = $requirements;

                        $lastCustomer = null;
                    @endphp
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b-2 border-slate-900">
                                <th class="px-4 py-3 text-left min-w-[40px]">
                                    <input type="checkbox" id="selectAll"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        onclick="toggleAll(this)">
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[100px]">
                                    Delivery Date</th>

                                <th
                                    class="px-3 py-3 text-center text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[80px]">
                                    Category</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[100px]">
                                    FG Part Tag</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[90px]">
                                    FG Part No.</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[80px]">
                                    Model</th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[90px]">
                                    Cust. Stock</th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[90px]">
                                    Daily Plan</th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-bold text-slate-900 uppercase tracking-wider border-x border-slate-200 min-w-[90px]">
                                    Requirement</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @forelse ($displayRequirements as $idx => $req)
                                <tr class="hover:bg-slate-50 transition-colors border-b border-slate-100">
                                    <td class="px-4 py-3 text-center border-x border-slate-100">
                                        <input type="checkbox" name="selected[]" value="{{ $idx }}"
                                            class="row-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <input type="hidden" name="lines[{{ $idx }}][date]"
                                            value="{{ $req->date->toDateString() }}">
                                        <input type="hidden" name="lines[{{ $idx }}][customer_id]"
                                            value="{{ $req->customer?->id }}">
                                        <input type="hidden" name="lines[{{ $idx }}][gci_part_id]"
                                            value="{{ $req->gci_part?->id }}">
                                        <input type="hidden" name="lines[{{ $idx }}][qty]"
                                            value="{{ $req->delivery_pack_qty ?? $req->total_qty }}">
                                        @foreach($req->source_row_ids as $rid)
                                            <input type="hidden" name="lines[{{ $idx }}][row_ids][]" value="{{ $rid }}">
                                        @endforeach
                                    </td>

                                    <td class="px-4 py-3 text-xs font-bold text-slate-600 border-x border-slate-100">
                                        {{ $req->date->format('d/m/Y') }}
                                    </td>



                                    <td
                                        class="px-3 py-3 text-center text-xs font-bold text-slate-700 border-x border-slate-100">
                                        @if(($req->source ?? '') === 'po')
                                            <span class="inline-block bg-amber-100 text-amber-700 text-[10px] font-bold px-1.5 py-0.5 rounded">PO</span>
                                        @endif
                                        {{ $req->gci_part?->standardPacking?->delivery_class ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-xs text-slate-700 border-x border-slate-100">
                                        {{ $req->customer_part_name ?? '-' }}
                                        @if($req->unmapped ?? false)
                                            <span
                                                class="ml-1 text-[10px] font-bold text-red-600 bg-red-100 px-1 py-0.5 rounded">UNMAPPED</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 font-mono text-xs font-bold text-slate-700 border-x border-slate-100">
                                        {{ $req->gci_part?->part_no ?? '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-xs text-slate-600 border-x border-slate-100">
                                        {{ $req->gci_part?->model ?? '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-right font-medium text-slate-500 border-x border-slate-100">
                                        {{ number_format($req->stock_at_customer ?? 0) }}
                                    </td>

                                    <td class="px-4 py-3 text-right font-bold text-slate-900 border-x border-slate-100">
                                        {{ number_format($req->gross_qty ?? 0) }}
                                        @if(($req->source ?? '') === 'po' && ($req->po_no ?? null))
                                            <div class="text-[10px] text-amber-600 font-semibold">{{ $req->po_no }}</div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-right border-x border-slate-100">
                                        <div class="font-bold text-indigo-700">{{ number_format($req->total_qty) }}</div>
                                        @if(($req->delivery_pack_qty ?? $req->total_qty) > $req->total_qty)
                                            <div class="text-[10px] text-slate-400 font-semibold">
                                                Pack: {{ number_format($req->delivery_pack_qty) }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-6 py-20 text-center text-slate-500 bg-white">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-16 h-16 text-slate-200 mb-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                                </path>
                                            </svg>
                                            <p class="text-lg font-bold text-slate-400 uppercase tracking-widest">No delivery
                                                requirements found</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($displayRequirements->isNotEmpty())
                            <tfoot class="bg-slate-50 font-bold border-t-2 border-slate-900">
                                <tr>
                                    <td colspan="8"
                                        class="px-4 py-4 text-right uppercase text-xs tracking-widest text-slate-500">Totals
                                    </td>
                                    <td class="px-4 py-4 text-right text-slate-500">
                                        {{ number_format($displayRequirements->sum('stock_at_customer')) }}
                                    </td>
                                    <td class="px-4 py-4 text-right text-slate-900">
                                        {{ number_format($displayRequirements->sum('gross_qty')) }}
                                    </td>
                                    <td class="px-4 py-4 text-right text-indigo-700">
                                        {{ number_format($displayRequirements->sum('total_qty')) }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
                @if($requirements->hasPages())
                    <div class="px-6 py-4 border-t border-slate-100">
                        {{ $requirements->links() }}
                    </div>
                @endif
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