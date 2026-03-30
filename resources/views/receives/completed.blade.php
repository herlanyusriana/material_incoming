<x-app-layout>
    <x-slot name="header">
        Completed Receives
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="text-sm text-slate-600">
                    Flow: Create Departure → Process Receives → Completed Receives.
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="px-2 py-1 rounded-lg bg-slate-100 text-slate-700 font-semibold">Total:
                        {{ number_format($summary['total_receives'] ?? 0) }}</span>
                    <span class="px-2 py-1 rounded-lg bg-slate-100 text-slate-700 font-semibold">Today:
                        {{ number_format($summary['today'] ?? 0) }}</span>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Invoice List</h3>
                        <p class="text-sm text-slate-600">Invoice yang sudah complete receive.</p>
                    </div>
                    <a href="{{ route('departures.index') }}"
                        class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-lg transition-colors">Departure
                        List</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Transaction No</th>
                                <th class="px-4 py-3 text-left font-semibold">Vendor</th>
                                <th class="px-4 py-3 text-left font-semibold">Invoice No</th>
                                <th class="px-4 py-3 text-right font-semibold">Tags</th>
                                <th class="px-4 py-3 text-right font-semibold">Qty</th>
                                <th class="px-4 py-3 text-center font-semibold">QC</th>
                                <th class="px-4 py-3 text-left font-semibold">Invoice Date</th>
                                <th class="px-4 py-3 text-left font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($arrivals as $arrival)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @if ($arrival->transaction_no)
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 tracking-wide">{{ $arrival->transaction_no }}</span>
                                        @else
                                            <span class="text-slate-400 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $arrival->vendor->vendor_name ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-slate-800 font-semibold whitespace-nowrap">
                                        {{ $arrival->invoice_no }}</td>
                                    <td class="px-4 py-4 text-right text-slate-800 font-semibold">
                                        {{ number_format($arrival->receives_count ?? 0) }}</td>
                                    <td class="px-4 py-4 text-right text-slate-800 font-semibold">
                                        {{ number_format($arrival->total_qty ?? 0) }}</td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Pass
                                            {{ $arrival->pass_count ?? 0 }}</span>
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 ml-2">Fail
                                            {{ $arrival->fail_count ?? 0 }}</span>
                                    </td>
                                    <td class="px-4 py-4 text-slate-700 whitespace-nowrap">
                                        {{ $arrival->invoice_date ? \Carbon\Carbon::parse($arrival->invoice_date)->format('Y-m-d') : '-' }}
                                    </td>
                                    <td class="px-4 py-4 text-sm">
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('receives.completed.invoice', $arrival->id) }}"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-600 transition hover:bg-indigo-100 hover:text-indigo-700"
                                                title="Detail">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                </svg>
                                            </a>
                                            @if (strtolower((string) ($arrival->vendor?->vendor_type ?? '')) !== 'local')
                                                <a href="{{ route('departures.edit', $arrival->id) }}"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-600 transition hover:bg-amber-100 hover:text-amber-700"
                                                    title="Edit No PEN / AJU">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="m16.862 3.487 1.65-1.65a2.121 2.121 0 1 1 3 3l-9.193 9.193a4.5 4.5 0 0 1-1.897 1.13L6 16l.84-4.422a4.5 4.5 0 0 1 1.13-1.897l8.892-8.894Z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                    </svg>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-slate-500">No completed receives yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-slate-200">
                    {{ $arrivals->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
