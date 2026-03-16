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

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-700 flex items-center justify-center text-white font-black">
                        DN
                    </div>
                    <div>
                        <div class="text-2xl md:text-3xl font-black text-slate-900">Delivery Notes (Surat Jalan)</div>
                        <div class="mt-1 text-sm text-slate-600">List and manage outgoing delivery documents</div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('outgoing.delivery-notes.create') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 transition-all active:scale-95">
                        <span class="mr-2">＋</span> New Delivery Note
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-5 py-4 text-left font-bold text-slate-700">DN Number</th>
                            <th class="px-5 py-4 text-left font-bold text-slate-700">Transaction No</th>
                            <th class="px-5 py-4 text-left font-bold text-slate-700">Customer</th>
                            <th class="px-5 py-4 text-left font-bold text-slate-700">Delivery Date</th>
                            <th class="px-5 py-4 text-left font-bold text-slate-700">Items</th>
                            <th class="px-5 py-4 text-left font-bold text-slate-700">Status</th>
                            <th class="px-5 py-4 text-right font-bold text-slate-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($deliveryNotes as $dn)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-5 py-4 font-black text-slate-900">{{ $dn->dn_no }}</td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($dn->transaction_no)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 tracking-wide">{{ $dn->transaction_no }}</span>
                                    @else
                                        <span class="text-slate-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-600 font-semibold">{{ $dn->customer->name }}</td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div>{{ $dn->delivery_date->format('d M Y') }}</div>
                                    <div class="text-[10px] text-slate-400">Created {{ $dn->created_at->format('d M Y H:i') }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($dn->items->take(2) as $item)
                                            <span class="px-2 py-0.5 bg-slate-100 rounded text-[10px] font-bold uppercase tracking-wider text-slate-600">
                                                {{ $item->part->part_no }} ({{ number_format($item->qty) }})
                                            </span>
                                        @endforeach
                                        @if ($dn->items->count() > 2)
                                            <span class="px-2 py-0.5 bg-slate-100 rounded text-[10px] font-bold text-slate-400">
                                                +{{ $dn->items->count() - 2 }} more
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    @php
                                        $statusClasses = match ($dn->status) {
                                            'draft' => 'bg-slate-100 text-slate-700 border-slate-300',
                                            'kitting' => 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
                                            'ready_to_pick' => 'bg-violet-50 text-violet-700 border-violet-200',
                                            'picking' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'ready_to_ship' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'shipped' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'cancelled' => 'bg-red-50 text-red-700 border-red-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-300',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $statusClasses }} uppercase tracking-wider">
                                        {{ str_replace('_', ' ', $dn->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('outgoing.delivery-notes.show', $dn) }}"
                                        class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-xs uppercase tracking-wider hover:bg-indigo-100 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500 italic">No Delivery Notes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 border-t border-slate-100 bg-slate-50">
                {{ $deliveryNotes->links() }}
            </div>
        </div>
    </div>
@endsection
