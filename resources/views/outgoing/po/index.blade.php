@extends('outgoing.layout')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex items-start gap-3">
                    <div
                        class="h-12 w-12 rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-white font-black text-sm shadow-lg">
                        PO
                    </div>
                    <div>
                        <div class="text-2xl md:text-3xl font-black text-slate-900">Customer PO</div>
                        <div class="mt-1 text-sm text-slate-500">Purchase Orders dari customer</div>
                    </div>
                </div>
                <a href="{{ route('outgoing.customer-po.create') }}"
                    class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 flex items-center gap-2 w-fit shadow-md shadow-indigo-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create PO
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <div class="text-xs font-semibold text-slate-500 mb-1">Search</div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="PO No or Customer..."
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 w-60">
                </div>
                <div>
                    <div class="text-xs font-semibold text-slate-500 mb-1">Status</div>
                    <select name="status"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">
                        <option value="">All</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <button
                    class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">Filter</button>
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            @if($pos->isEmpty())
                <div class="p-12 text-center">
                    <div class="text-slate-400 text-sm italic">No Customer PO found.</div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">PO
                                    No.</th>
                                <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Customer</th>
                                <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Release Date</th>
                                <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Items</th>
                                <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Total Qty</th>
                                <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Status</th>
                                <th class="px-4 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pos as $po)
                                <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                                    <td class="px-4 py-3 font-bold text-indigo-700 font-mono">{{ $po->po_no }}</td>
                                    <td class="px-4 py-3 text-slate-900 font-semibold">{{ $po->customer->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center text-slate-600">{{ $po->po_release_date->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[10px] font-bold">
                                            {{ $po->items->count() }} items
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-900">{{ number_format($po->total_qty) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @php
                                            $statusColor = match ($po->status) {
                                                'draft' => 'bg-slate-100 text-slate-600',
                                                'confirmed' => 'bg-blue-100 text-blue-700',
                                                'completed' => 'bg-green-100 text-green-700',
                                                'cancelled' => 'bg-red-100 text-red-600',
                                                default => 'bg-slate-100 text-slate-600',
                                            };
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $statusColor }}">
                                            {{ $po->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('outgoing.customer-po.show', $po) }}"
                                            class="text-indigo-600 hover:text-indigo-800 font-bold text-[11px] hover:underline">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($pos->hasPages())
                    <div class="px-4 py-3 border-t border-slate-200">
                        {{ $pos->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection