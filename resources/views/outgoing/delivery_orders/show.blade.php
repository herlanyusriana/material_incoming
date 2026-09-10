@extends('layouts.app')

@section('content')
<div class="p-6">
    <!-- Breadcrumb -->
    <div class="mb-6">
        <nav class="text-sm text-slate-500" aria-label="{{ __('outgoing.common.breadcrumb') }}">
            <ol class="inline-flex items-center space-x-2">
                <li><a href="{{ route('dashboard') }}" class="hover:text-slate-700">{{ __('outgoing.delivery_orders.show.crumb_dashboard') }}</a></li>
                <li class="text-slate-900">/</li>
                <li><a href="{{ route('outgoing.delivery-plan') }}" class="hover:text-slate-700">{{ __('outgoing.delivery_orders.show.crumb_outgoing') }}</a></li>
                <li class="text-slate-900">/</li>
                <li><a href="{{ route('outgoing.delivery-orders.index') }}" class="hover:text-slate-700">{{ __('outgoing.delivery_orders.show.crumb_list') }}</a></li>
                <li class="text-slate-900">/</li>
                <li class="text-slate-900">{{ __('outgoing.delivery_orders.show.crumb_details') }}</li>
            </ol>
        </nav>
    </div>

    <div class="grid grid-cols-3 gap-6">
        <!-- Left: DO Info -->
        <div class="col-span-2">
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">{{ __('outgoing.delivery_orders.show.title') }}</h2>
                    <div class="flex gap-2">
                        <a href="{{ route('outgoing.delivery-orders.edit', $deliveryOrder) }}" 
                            class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-all text-xs uppercase tracking-wider">{{ __('outgoing.delivery_orders.show.edit_do') }}</a>
                        <form action="{{ route('outgoing.delivery-orders.destroy', $deliveryOrder) }}" method="POST" class="inline" onsubmit="return confirm(@js(__('outgoing.delivery_orders.show.confirm_delete')))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 rounded-xl bg-red-50 border border-red-200 text-red-600 font-bold hover:bg-red-100 transition-all text-xs uppercase tracking-wider">{{ __('outgoing.delivery_orders.show.delete') }}</button>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('outgoing.delivery_orders.show.label_do_number') }}</div>
                        <div class="text-lg font-bold text-slate-900 mt-1">{{ $deliveryOrder->do_no }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('outgoing.delivery_orders.show.label_do_date') }}</div>
                        <div class="text-lg font-bold text-slate-900 mt-1">{{ $deliveryOrder->do_date->format('d M Y') }}</div>
                    </div>
                    <div class="col-span-2">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('outgoing.delivery_orders.show.label_customer') }}</div>
                        <div class="text-lg font-bold text-slate-900 mt-1">{{ $deliveryOrder->customer ?? '-' }}</div>
                    </div>
                </div>

                @if($deliveryOrder->notes)
                <div class="mt-6 pt-6 border-t border-slate-200">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ __('outgoing.delivery_orders.show.label_notes') }}</div>
                    <div class="text-slate-700 mt-2">{{ $deliveryOrder->notes }}</div>
                </div>
                @endif
            </div>

            <!-- Items -->
            <div class="bg-white rounded-xl border border-slate-200 p-6 mt-6">
                <h3 class="text-lg font-bold text-slate-900 tracking-tight mb-4">{{ __('outgoing.delivery_orders.show.items_title') }}</h3>
                
                @if($deliveryOrder->items->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200">
                                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">{{ __('outgoing.delivery_orders.show.th_item') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">{{ __('outgoing.delivery_orders.show.th_qty') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">{{ __('outgoing.delivery_orders.show.th_price') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">{{ __('outgoing.delivery_orders.show.th_total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($deliveryOrder->items as $item)
                                <tr class="border-b border-slate-100 hover:bg-slate-50">
                                    <td class="px-4 py-3">{{ $item->description ?? '-' }}</td>
                                    <td class="px-4 py-3 tabular-nums">{{ $item->quantity ?? '-' }}</td>
                                    <td class="px-4 py-3 tabular-nums">{{ $item->unit_price ? 'Rp ' . number_format($item->unit_price, 0, ',', '.') : '-' }}</td>
                                    <td class="px-4 py-3 text-right font-bold tabular-nums">{{ (($item->quantity ?? 0) * ($item->unit_price ?? 0)) > 0 ? 'Rp ' . number_format(($item->quantity ?? 0) * ($item->unit_price ?? 0), 0, ',', '.') : '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8 text-slate-500">
                        {{ __('outgoing.delivery_orders.show.empty_items') }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Summary -->
        <div>
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-900 tracking-tight mb-4">{{ __('outgoing.delivery_orders.show.summary') }}</h3>
                
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-slate-600">{{ __('outgoing.delivery_orders.show.created') }}</span>
                        <span class="font-bold text-slate-900">{{ $deliveryOrder->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">{{ __('outgoing.delivery_orders.show.updated') }}</span>
                        <span class="font-bold text-slate-900">{{ $deliveryOrder->updated_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="pt-4 border-t border-slate-200">
                        <div class="flex justify-between">
                            <span class="font-bold text-slate-900">{{ __('outgoing.delivery_orders.show.total_items') }}</span>
                            <span class="font-bold text-lg text-indigo-600 tabular-nums">{{ $deliveryOrder->items->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <a href="{{ route('outgoing.delivery-orders.index') }}" 
                class="mt-6 w-full block text-center px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200 transition-all text-sm uppercase tracking-wider">
                {{ __('outgoing.delivery_orders.show.back') }}
            </a>
        </div>
    </div>
</div>
@endsection
