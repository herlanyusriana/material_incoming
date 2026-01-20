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
                                        {{-- View Details --}}
                                        <a href="{{ route('local-pos.show', $po) }}" class="p-1.5 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="View Details">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </a>

                                        {{-- Edit PO --}}
                                        <a href="{{ route('local-pos.edit', $po) }}" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit PO">
                                             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>

                                        <a href="{{ route('receives.invoice.create', $po) }}" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold">
                                            Receive
                                        </a>
                                        <form action="{{ route('local-pos.destroy', $po) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Local PO ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete PO">
                                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
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
