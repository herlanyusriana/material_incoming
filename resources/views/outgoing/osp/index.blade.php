@extends('outgoing.layout')

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
                <div class="text-xs font-bold uppercase tracking-wider text-blue-600">Received</div>
                <div class="mt-1 text-2xl font-black text-blue-700">{{ $stats->received }}</div>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-amber-600">In Progress</div>
                <div class="mt-1 text-2xl font-black text-amber-700">{{ $stats->in_progress }}</div>
            </div>
            <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-indigo-600">Ready</div>
                <div class="mt-1 text-2xl font-black text-indigo-700">{{ $stats->ready }}</div>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
                <div class="text-xs font-bold uppercase tracking-wider text-emerald-600">Shipped</div>
                <div class="mt-1 text-2xl font-black text-emerald-700">{{ $stats->shipped }}</div>
            </div>
        </div>

        {{-- Filters + Create --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <form action="{{ route('outgoing.osp.index') }}" method="GET" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Status</label>
                        <select name="status" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach (['received','in_progress','ready','shipped','cancelled'] as $s)
                                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Customer</label>
                        <select name="customer_id" class="rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" @selected(request('customer_id') == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                        class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">
                        Filter
                    </button>
                </form>

                <a href="{{ route('outgoing.osp.create') }}"
                    class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-bold text-white hover:bg-indigo-700 text-center">
                    + New OSP Order
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
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Customer</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Part</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Material Rcvd</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Assembled</th>
                            <th class="px-4 py-3 text-right font-bold text-slate-700">Shipped</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Rcvd Date</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Status</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($orders as $order)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-mono font-bold text-indigo-600">
                                    <a href="{{ route('outgoing.osp.show', $order) }}">{{ $order->order_no }}</a>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $order->customer->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $order->gciPart->part_name ?? '-' }}
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $order->gciPart->part_no ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format($order->qty_received_material) }}</td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format($order->qty_assembled) }}</td>
                                <td class="px-4 py-3 text-right font-mono">{{ number_format($order->qty_shipped) }}</td>
                                <td class="px-4 py-3 text-center text-slate-600">{{ $order->received_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-center">
                                    @php
                                        $statusColors = [
                                            'received' => 'bg-blue-100 text-blue-700',
                                            'in_progress' => 'bg-amber-100 text-amber-700',
                                            'ready' => 'bg-indigo-100 text-indigo-700',
                                            'shipped' => 'bg-emerald-100 text-emerald-700',
                                            'cancelled' => 'bg-red-100 text-red-700',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $statusColors[$order->status] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('outgoing.osp.show', $order) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold text-xs">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-slate-400">No OSP orders found.</td>
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
