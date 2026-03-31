<x-app-layout>
    <x-slot name="header">
        Rekap No PEN / No AJU
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-xl font-bold text-slate-900">Rekap Dokumen Import</h3>
                    <p class="text-sm text-slate-600">Pantau invoice import beserta No PEN, tanggal PEN, dan No AJU dalam satu halaman.</p>
                </div>
                <a href="{{ route('receives.import-documents.export', request()->query()) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 17.25A2.25 2.25 0 0 0 6.25 19.5h11.5A2.25 2.25 0 0 0 20 17.25" />
                    </svg>
                    Export Excel
                </a>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Invoice</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($summary['total_invoices'] ?? 0) }}</div>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Sudah Ada No PEN</div>
                    <div class="mt-2 text-2xl font-bold text-emerald-900">{{ number_format($summary['with_pen'] ?? 0) }}</div>
                </div>
                <div class="rounded-2xl border border-indigo-200 bg-indigo-50 px-5 py-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wider text-indigo-700">Sudah Ada No AJU</div>
                    <div class="mt-2 text-2xl font-bold text-indigo-900">{{ number_format($summary['with_aju'] ?? 0) }}</div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
                <div class="border-b border-slate-200 px-6 py-4">
                    <form method="GET" action="{{ route('receives.import-documents') }}" class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr)_180px_180px_auto]">
                        <div>
                            <label for="q" class="sr-only">Cari</label>
                            <input type="text" id="q" name="q" value="{{ $q ?? '' }}"
                                placeholder="Cari transaction no, invoice, vendor, no PEN, no AJU..."
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="date_from" class="sr-only">Invoice Date From</label>
                            <input type="date" id="date_from" name="date_from" value="{{ $dateFrom ?? '' }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="date_to" class="sr-only">Invoice Date To</label>
                            <input type="date" id="date_to" name="date_to" value="{{ $dateTo ?? '' }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                                Filter
                            </button>
                            @if (($q ?? '') !== '' || ($dateFrom ?? '') !== '' || ($dateTo ?? '') !== '')
                                <a href="{{ route('receives.import-documents') }}"
                                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-600">
                                <th class="px-4 py-3">Transaction No</th>
                                <th class="px-4 py-3">Vendor</th>
                                <th class="px-4 py-3">Invoice No</th>
                                <th class="px-4 py-3">Invoice Date</th>
                                <th class="px-4 py-3">No PEN</th>
                                <th class="px-4 py-3">Tanggal PEN</th>
                                <th class="px-4 py-3">No AJU</th>
                                <th class="px-4 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($arrivals as $arrival)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @if ($arrival->transaction_no)
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold tracking-wide text-emerald-800">{{ $arrival->transaction_no }}</span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 font-semibold text-slate-900">{{ $arrival->vendor->vendor_name ?? '-' }}</td>
                                    <td class="px-4 py-4 font-semibold text-slate-800 whitespace-nowrap">{{ $arrival->invoice_no ?: '-' }}</td>
                                    <td class="px-4 py-4 text-slate-700 whitespace-nowrap">{{ optional($arrival->invoice_date)->format('Y-m-d') ?: '-' }}</td>
                                    <td class="px-4 py-4 text-slate-700 whitespace-nowrap">{{ $arrival->pen_no ?: '-' }}</td>
                                    <td class="px-4 py-4 text-slate-700 whitespace-nowrap">{{ optional($arrival->pen_date)->format('Y-m-d') ?: '-' }}</td>
                                    <td class="px-4 py-4 text-slate-700 whitespace-nowrap">{{ $arrival->aju_no ?: '-' }}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('receives.completed.invoice', $arrival->id) }}"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-600 transition hover:bg-indigo-100 hover:text-indigo-700"
                                                title="Detail Invoice">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('departures.edit', ['departure' => $arrival->id, 'customs_only' => 1]) }}"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-600 transition hover:bg-amber-100 hover:text-amber-700"
                                                title="Edit No PEN / AJU">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 3.487 1.65-1.65a2.121 2.121 0 1 1 3 3l-9.193 9.193a4.5 4.5 0 0 1-1.897 1.13L6 16l.84-4.422a4.5 4.5 0 0 1 1.13-1.897l8.892-8.894Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-slate-500">Belum ada data invoice import.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $arrivals->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
