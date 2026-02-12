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
                                <td class="px-5 py-4 text-slate-600 font-semibold">{{ $dn->customer->name }}</td>
                                <td class="px-5 py-4 text-slate-600">{{ $dn->delivery_date->format('d M Y') }}</td>
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
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('outgoing.delivery-notes.print', $dn) }}" target="_blank" class="text-emerald-600 hover:text-emerald-900 font-bold text-xs uppercase tracking-tighter">Print</a>
                                        <a href="{{ route('outgoing.delivery-notes.show', $dn) }}" class="text-indigo-600 hover:text-indigo-900 font-bold text-xs uppercase tracking-tighter">Details</a>
                                        
                                        @if ($dn->status === 'draft')
                                            <form action="{{ route('outgoing.delivery-notes.start-kitting', $dn) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="bg-fuchsia-600 text-white px-3 py-1 rounded text-[10px] font-bold uppercase hover:bg-fuchsia-700 transition-colors">Start Kitting</button>
                                            </form>
                                            <form action="{{ route('outgoing.delivery-notes.destroy', $dn) }}" method="POST" onsubmit="return confirm('Delete this Delivery Note?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 font-bold ml-2 text-xs uppercase tracking-tighter">Delete</button>
                                            </form>
                                        @elseif ($dn->status === 'kitting')
                                            <form action="{{ route('outgoing.delivery-notes.complete-kitting', $dn) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="bg-violet-600 text-white px-3 py-1 rounded text-[10px] font-bold uppercase hover:bg-violet-700 transition-colors">Complete Kitting</button>
                                            </form>
                                        @elseif ($dn->status === 'ready_to_pick')
                                            <form action="{{ route('outgoing.delivery-notes.start-picking', $dn) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="bg-amber-600 text-white px-3 py-1 rounded text-[10px] font-bold uppercase hover:bg-amber-700 transition-colors">Start Picking</button>
                                            </form>
                                        @elseif ($dn->status === 'picking')
                                            <form action="{{ route('outgoing.delivery-notes.complete-picking', $dn) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-[10px] font-bold uppercase hover:bg-blue-700 transition-colors">Complete Picking</button>
                                            </form>
                                        @elseif ($dn->status === 'ready_to_ship')
                                            <form action="{{ route('outgoing.delivery-notes.ship', $dn) }}" method="POST" onsubmit="return confirm('Confirm shipping? Inventory will be deducted.')">
                                                @csrf
                                                <button type="submit" class="bg-emerald-600 text-white px-3 py-1 rounded text-[10px] font-bold uppercase hover:bg-emerald-700 transition-colors">Ship Now</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500 italic">No Delivery Notes found.</td>
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
