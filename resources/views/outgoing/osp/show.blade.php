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

        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">{{ $ospOrder->order_no }}</h1>
                    <div class="mt-1 text-sm text-slate-600">OSP Order Detail</div>
                </div>
                <div class="flex gap-2 items-center">
                    @php
                        $statusColors = [
                            'received' => 'bg-blue-100 text-blue-700',
                            'in_progress' => 'bg-amber-100 text-amber-700',
                            'ready' => 'bg-indigo-100 text-indigo-700',
                            'shipped' => 'bg-emerald-100 text-emerald-700',
                            'cancelled' => 'bg-red-100 text-red-700',
                        ];
                    @endphp
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-bold {{ $statusColors[$ospOrder->status] ?? '' }}">
                        {{ ucfirst(str_replace('_', ' ', $ospOrder->status)) }}
                    </span>
                    <a href="{{ route('outgoing.osp.index') }}" class="text-sm text-slate-500 hover:text-slate-800">&larr; Back</a>
                </div>
            </div>
        </div>

        {{-- Order Info --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-slate-900 mb-4">Order Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Customer</div>
                    <div class="mt-1 text-slate-900 font-semibold">{{ $ospOrder->customer->name ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Part</div>
                    <div class="mt-1 text-slate-900 font-semibold">{{ $ospOrder->gciPart->part_name ?? '-' }}</div>
                    <div class="text-xs text-slate-400 font-mono">{{ $ospOrder->gciPart->part_no ?? '' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Received Date</div>
                    <div class="mt-1 text-slate-900">{{ $ospOrder->received_date->format('d/m/Y') }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Target Ship Date</div>
                    <div class="mt-1 text-slate-900">{{ $ospOrder->target_ship_date?->format('d/m/Y') ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Shipped Date</div>
                    <div class="mt-1 text-slate-900">{{ $ospOrder->shipped_date?->format('d/m/Y') ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Created By</div>
                    <div class="mt-1 text-slate-900">{{ $ospOrder->creator->name ?? '-' }}</div>
                </div>
            </div>

            {{-- Qty Summary --}}
            <div class="mt-6 pt-6 border-t border-slate-200">
                <div class="grid grid-cols-3 gap-4">
                    <div class="rounded-lg bg-blue-50 p-4 text-center">
                        <div class="text-xs font-bold text-blue-600 uppercase">Material Received</div>
                        <div class="mt-1 text-xl font-black text-blue-800">{{ number_format($ospOrder->qty_received_material) }}</div>
                    </div>
                    <div class="rounded-lg bg-amber-50 p-4 text-center">
                        <div class="text-xs font-bold text-amber-600 uppercase">Assembled</div>
                        <div class="mt-1 text-xl font-black text-amber-800">{{ number_format($ospOrder->qty_assembled) }}</div>
                    </div>
                    <div class="rounded-lg bg-emerald-50 p-4 text-center">
                        <div class="text-xs font-bold text-emerald-600 uppercase">Shipped</div>
                        <div class="mt-1 text-xl font-black text-emerald-800">{{ number_format($ospOrder->qty_shipped) }}</div>
                    </div>
                </div>
            </div>

            @if ($ospOrder->notes)
                <div class="mt-4 pt-4 border-t border-slate-200">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Notes</div>
                    <div class="mt-1 text-sm text-slate-700">{{ $ospOrder->notes }}</div>
                </div>
            @endif
        </div>

        {{-- Update Progress --}}
        @if (!in_array($ospOrder->status, ['shipped', 'cancelled']))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Assembly Progress --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-lg font-bold text-slate-900 mb-4">Update Assembly Progress</h2>
                    <form action="{{ route('outgoing.osp.progress', $ospOrder) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Qty Assembled</label>
                            <input type="number" name="qty_assembled" step="0.0001" min="0" required
                                value="{{ $ospOrder->qty_assembled }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                            <div class="mt-1 text-xs text-slate-400">Max: {{ number_format($ospOrder->qty_received_material) }} (material received)</div>
                        </div>
                        <button type="submit"
                            class="rounded-lg bg-amber-600 px-5 py-2 text-sm font-bold text-white hover:bg-amber-700">
                            Update Progress
                        </button>
                    </form>
                </div>

                {{-- Ship --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-lg font-bold text-slate-900 mb-4">Ship to Customer</h2>
                    <form action="{{ route('outgoing.osp.ship', $ospOrder) }}" method="POST" class="space-y-4"
                        onsubmit="return confirm('Mark this order as shipped?');">
                        @csrf
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Qty Shipped</label>
                            <input type="number" name="qty_shipped" step="0.0001" min="0.0001" required
                                value="{{ $ospOrder->qty_assembled ?: '' }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Shipped Date</label>
                            <input type="date" name="shipped_date" required value="{{ now()->toDateString() }}"
                                class="w-full rounded-lg border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <button type="submit"
                            class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-bold text-white hover:bg-emerald-700">
                            Mark as Shipped
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
