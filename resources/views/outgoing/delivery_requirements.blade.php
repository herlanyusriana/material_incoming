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
                <table class="w-full text-sm divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">GCI Part Name</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">GCI Part No</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Customer Part No</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Quantity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($requirements as $req)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-700">
                                    {{ $req->date->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $req->gci_part->name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-600">
                                    {{ $req->gci_part->part_no ?? '-' }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-600">
                                    {{ $req->customer_part_no ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-indigo-700">
                                    {{ number_format($req->total_qty) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">
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
            </div>
        </div>
    </div>
@endsection
