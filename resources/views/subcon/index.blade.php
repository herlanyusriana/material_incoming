@extends('subcon.layout')

@section('content')
    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Total</div>
                <div class="mt-1 text-2xl font-black text-slate-900">{{ $stats->total }}</div>
            </div>
            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-blue-600">Sent</div>
                <div class="mt-1 text-2xl font-black text-blue-700">{{ $stats->sent }}</div>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-amber-600">Partial</div>
                <div class="mt-1 text-2xl font-black text-amber-700">{{ $stats->partial }}</div>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-emerald-600">Completed</div>
                <div class="mt-1 text-2xl font-black text-emerald-700">{{ $stats->completed }}</div>
            </div>
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-rose-600">Outstanding Qty</div>
                <div class="mt-1 text-2xl font-black text-rose-700">{{ number_format($stats->total_outstanding) }}</div>
            </div>
        </div>

        {{-- Filters + Create Button --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <form action="{{ route('subcon.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Status</label>
                        <select name="status" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach (['draft','sent','partial','completed','cancelled'] as $s)
                                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Vendor</label>
                        <select name="vendor_id" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->vendor_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    </div>
                    <button type="submit"
                        class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
                        Filter
                    </button>
                </form>

                <a href="{{ route('subcon.create') }}"
                    class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-bold text-white hover:bg-indigo-700 text-center">
                    + New Subcon Order
                </a>
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Order No</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Vendor</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Part</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Process</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Sent</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Received</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Outstanding</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Sent Date</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Status</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($orders as $order)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono font-bold text-indigo-600">
                                    <a href="{{ route('subcon.show', $order) }}">{{ $order->order_no }}</a>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $order->vendor->vendor_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $order->gciPart->part_name ?? '-' }}
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $order->gciPart->part_no ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                        {{ ucfirst($order->process_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format($order->qty_sent) }}</td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format($order->qty_received) }}</td>
                                <td class="px-4 py-3 text-right font-mono font-bold {{ $order->qty_outstanding > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                    {{ number_format($order->qty_outstanding) }}
                                </td>
                                <td class="px-4 py-3 text-center text-slate-600">{{ $order->sent_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $statusColors = [
                                            'draft' => 'bg-slate-100 text-slate-700',
                                            'sent' => 'bg-blue-100 text-blue-700',
                                            'partial' => 'bg-amber-100 text-amber-700',
                                            'completed' => 'bg-emerald-100 text-emerald-700',
                                            'cancelled' => 'bg-red-100 text-red-700',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $statusColors[$order->status] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('subcon.show', $order) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold text-xs">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-slate-400">No subcon orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="px-4 py-3 border-t border-slate-200">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
