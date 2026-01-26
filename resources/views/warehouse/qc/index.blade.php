<x-app-layout>
    <x-slot name="header">
        Warehouse • QC Queue
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
                        <h2 class="text-xl font-bold text-slate-900">QC Queue</h2>
                        <div class="text-xs text-slate-500">Items yang belum PASS (hold/reject)</div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('warehouse.putaway.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-sm font-semibold hover:bg-slate-50">Putaway Queue</a>
                    </div>
                </div>

                <form method="GET" class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Search</label>
                            <input type="text" name="search" value="{{ $search }}" placeholder="tag / arrival / part"
                                class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">QC Status</label>
                            <select name="status" class="w-full rounded-lg border-slate-300 text-sm">
                                <option value="" {{ $status === '' ? 'selected' : '' }}>All (hold/reject/fail)</option>
                                <option value="hold" {{ $status === 'hold' ? 'selected' : '' }}>HOLD</option>
                                <option value="reject" {{ $status === 'reject' ? 'selected' : '' }}>REJECT</option>
                                <option value="fail" {{ $status === 'fail' ? 'selected' : '' }}>FAIL</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Per Page</label>
                            <select name="per_page" class="w-full rounded-lg border-slate-300 text-sm">
                                @foreach([25,50,100,200] as $opt)
                                    <option value="{{ $opt }}" {{ (int) $perPage === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-semibold">
                                Apply
                            </button>
                            <a href="{{ route('warehouse.qc.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700">
                                Clear
                            </a>
                        </div>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Arrival</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Part</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Tag</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">QC Note</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @forelse($rows as $r)
                                @php
                                    $partNo = $r->arrivalItem?->part?->part_no ?? '-';
                                    $arrivalNo = $r->arrivalItem?->arrival?->arrival_no ?? '-';
                                    $statusLabel = strtoupper((string) $r->qc_status);
                                    $statusClass = match (strtolower((string) $r->qc_status)) {
                                        'hold' => 'bg-amber-100 text-amber-800',
                                        'reject', 'fail' => 'bg-rose-100 text-rose-800',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <tr class="hover:bg-slate-50">
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
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-1 text-xs font-bold rounded {{ $statusClass }}">{{ $statusLabel }}</span>
                                        @if($r->qc_updated_at)
                                            <div class="text-[11px] text-slate-500 mt-1">
                                                {{ $r->qc_updated_at->format('Y-m-d H:i') }} • {{ $r->qcUpdater?->name ?? '-' }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <div class="max-w-[18rem] truncate" title="{{ $r->qc_note ?? '' }}">
                                            {{ $r->qc_note ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('warehouse.qc.update', $r) }}" class="flex flex-col gap-2">
                                            @csrf
                                            <div class="flex items-center gap-2">
                                                <select name="qc_status" class="rounded-lg border-slate-300 text-sm">
                                                    <option value="pass">PASS</option>
                                                    <option value="hold" {{ strtolower((string) $r->qc_status) === 'hold' ? 'selected' : '' }}>HOLD</option>
                                                    <option value="reject" {{ in_array(strtolower((string) $r->qc_status), ['reject', 'fail'], true) ? 'selected' : '' }}>REJECT</option>
                                                </select>
                                                <button type="submit" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">
                                                    Update
                                                </button>
                                            </div>
                                            <input name="qc_note" value="{{ $r->qc_note ?? '' }}" placeholder="QC note (optional)"
                                                class="w-72 rounded-lg border-slate-300 text-sm">
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-slate-600">
                                        No QC tasks.
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
</x-app-layout>

