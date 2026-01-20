<x-app-layout>
    <x-slot name="header">
        Local PO
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
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
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Search</label>
                            <input name="q" value="{{ $q }}" class="mt-1 rounded-xl border-slate-200" placeholder="PO No / ARR-...">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Vendor</label>
                            <select name="vendor_id" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                @foreach ($vendors as $v)
                                    <option value="{{ $v->id }}" @selected((string) $vendorId === (string) $v->id)>{{ $v->vendor_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                    </form>

                    <a href="{{ route('local-pos.create') }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">
                        + Create Local PO
                    </a>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">PO No</th>
                                <th class="px-4 py-3 text-left font-semibold">PO Date</th>
                                <th class="px-4 py-3 text-left font-semibold">Vendor</th>
                                <th class="px-4 py-3 text-left font-semibold">Surat Jalan</th>
                                <th class="px-4 py-3 text-left font-semibold">Invoice</th>
                                <th class="px-4 py-3 text-left font-semibold">Packing List</th>
                                <th class="px-4 py-3 text-right font-semibold">Items</th>
                                <th class="px-4 py-3 text-right font-semibold">Remaining</th>
                                <th class="px-4 py-3 text-right font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($localPos as $po)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900">{{ $po->invoice_no }}</div>
                                        <div class="text-xs text-slate-500">{{ $po->arrival_no }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $po->invoice_date?->format('Y-m-d') ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900">{{ $po->vendor?->vendor_name ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">LOCAL</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($po->delivery_note_file_url)
                                            <a href="{{ $po->delivery_note_file_url }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm">View</a>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($po->invoice_file_url)
                                            <a href="{{ $po->invoice_file_url }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm">View</a>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($po->packing_list_file_url)
                                            <a href="{{ $po->packing_list_file_url }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm">View</a>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format((int) ($po->items_count ?? 0)) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format((float) ($po->remaining_qty ?? 0), 0) }}</td>
                                    <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('receives.invoice.create', $po) }}" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold">
                                            Receive
                                        </a>
                                        <form action="{{ route('local-pos.destroy', $po) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Local PO ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-100 hover:bg-red-200 text-red-700 text-xs font-semibold transition-colors">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-slate-500">Belum ada Local PO.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $localPos->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
