<x-app-layout>
    <x-slot name="header">
        Warehouse • Putaway Queue
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Putaway Queue</h2>
                        <div class="text-xs text-slate-500">QC PASS tapi belum ada lokasi gudang</div>
                    </div>

                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search tag / arrival / part"
                            class="w-64 rounded-lg border-slate-300 text-sm">
                        <select name="per_page" class="rounded-lg border-slate-300 text-sm">
                            @foreach([25,50,100,200] as $opt)
                                <option value="{{ $opt }}" {{ (int) $perPage === $opt ? 'selected' : '' }}>{{ $opt }}/page</option>
                            @endforeach
                        </select>
                        <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-semibold">
                            Filter
                        </button>
                        <a href="{{ route('warehouse.putaway.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700">
                            Clear
                        </a>
                    </form>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                    <form id="bulk-putaway-form" method="POST" action="{{ route('warehouse.putaway.bulk') }}" class="flex flex-col md:flex-row md:items-end gap-3">
                        @csrf
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Bulk Location</label>
                            <input name="location_code" list="locs" placeholder="Location code (scan/ketik)"
                                class="w-full md:w-64 rounded-lg border-slate-300 text-sm" required>
                            <div class="mt-1 text-xs text-slate-500">Pilih beberapa row di bawah, lalu klik Bulk Putaway.</div>
                        </div>
                        <div>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold">
                                Bulk Putaway Selected
                            </button>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">
                                    <label class="inline-flex items-center gap-2">
                                        <input id="check-all" type="checkbox" class="rounded border-slate-300">
                                        Select
                                    </label>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Arrival</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Part</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Tag</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-700 uppercase">Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Putaway</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @forelse($rows as $r)
                                @php
                                    $partNo = $r->arrivalItem?->part?->part_no ?? '-';
                                    $arrivalNo = $r->arrivalItem?->arrival?->arrival_no ?? '-';
                                    $qtyUnit = strtoupper(trim((string) ($r->qty_unit ?? '')));
                                    $qtyVal = $qtyUnit === 'COIL' ? (float) ($r->net_weight ?? 0) : (float) ($r->qty ?? 0);
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <input form="bulk-putaway-form" type="checkbox" name="receive_ids[]" value="{{ $r->id }}"
                                            class="row-check rounded border-slate-300">
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-900">
                                        {{ $r->ata_date ? $r->ata_date->format('Y-m-d') : $r->created_at?->format('Y-m-d') }}
                                        <div class="text-xs text-slate-500">{{ $r->created_at?->format('H:i') }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-900">
                                        <div class="font-semibold">{{ $arrivalNo }}</div>
                                        <div class="text-xs text-slate-500">{{ $r->arrivalItem?->arrival?->vendor?->vendor_name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-semibold text-slate-900">{{ $partNo }}</div>
                                        <div class="text-xs text-slate-500">{{ $r->arrivalItem?->part?->part_name_gci ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-900">
                                        <span class="font-mono text-xs">{{ $r->tag ?? '-' }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="text-sm font-bold text-indigo-700">{{ number_format($qtyVal, 4) }}</div>
                                        <div class="text-xs text-slate-500">{{ $qtyUnit !== '' ? $qtyUnit : '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('warehouse.putaway.store', $r) }}" class="flex items-center gap-2">
                                            @csrf
                                            <input name="location_code" list="locs" placeholder="Location code"
                                                class="w-44 rounded-lg border-slate-300 text-sm" required>
                                            <button type="submit" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">
                                                Save
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-slate-600">
                                        No putaway tasks.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-slate-200">
                    {{ $rows->links() }}
                </div>
            </div>
        </div>
    </div>

    <datalist id="locs">
        @foreach($locationCodes as $code)
            <option value="{{ $code }}"></option>
        @endforeach
    </datalist>

    <script>
        (function () {
            const checkAll = document.getElementById('check-all');
            if (!checkAll) return;
            checkAll.addEventListener('change', function () {
                document.querySelectorAll('.row-check').forEach((el) => {
                    el.checked = checkAll.checked;
                });
            });
        })();
    </script>
</x-app-layout>
