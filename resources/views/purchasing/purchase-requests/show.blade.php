<x-app-layout>
    <x-slot name="header">
        {{ __('purchasing.requests.show.header') }}
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center justify-between">
                <a href="{{ route('purchasing.purchase-requests.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors uppercase tracking-widest">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ __('purchasing.requests.show.back') }}
                </a>

                <div class="flex items-center gap-3">
                    @if ($purchaseRequest->status === 'Pending')
                        <form action="{{ route('purchasing.purchase-requests.approve', $purchaseRequest) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-6 py-2.5 rounded-2xl bg-emerald-600 text-white font-bold hover:bg-emerald-700 transition-all shadow-lg uppercase text-xs tracking-wider flex items-center gap-2">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ __('purchasing.requests.show.approve') }}
                            </button>
                        </form>
                    @endif

                    @if ($purchaseRequest->status === 'Approved')
                        <a href="{{ route('purchasing.purchase-orders.create', ['pr_id' => $purchaseRequest->id]) }}" class="px-6 py-2.5 rounded-2xl bg-indigo-600 text-white font-bold hover:bg-indigo-700 transition-all shadow-lg uppercase text-xs tracking-wider flex items-center gap-2">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            {{ __('purchasing.requests.show.convert') }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="bg-white shadow-xl border border-slate-200 rounded-3xl overflow-hidden">
                <div class="p-8 border-b border-slate-100 bg-slate-50/30">
                    <div class="flex flex-wrap items-center justify-between gap-6">
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ $purchaseRequest->pr_number }}</h2>
                                @php
                                    $statusClasses = [
                                        'Pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                        'Approved' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                        'Rejected' => 'bg-rose-100 text-rose-700 border-rose-200',
                                        'Converted' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                        'Cancelled' => 'bg-slate-100 text-slate-700 border-slate-200',
                                    ];
                                    $currentClass = $statusClasses[$purchaseRequest->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                @endphp
                                <span class="px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest border-2 {{ $currentClass }}">
                                    {{ $purchaseRequest->status }}
                                </span>
                            </div>
                            <p class="text-slate-500 font-medium">{{ __('purchasing.requests.show.requested_on') }} {{ $purchaseRequest->created_at->format('l, M d, Y') }} {{ __('purchasing.requests.show.requested_by') }} <span class="text-slate-900 font-bold border-b border-slate-200">{{ $purchaseRequest->requester?->name }}</span></p>
                        </div>
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col items-center justify-center min-w-[180px]">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ __('purchasing.requests.show.total_label') }}</span>
                            <span class="text-2xl font-black text-indigo-600 tabular-nums">{{ number_format($purchaseRequest->total_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-8">
                    <div class="space-y-8">
                        <div>
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-4 flex items-center gap-2">
                                <span class="h-px w-8 bg-slate-200"></span>
                                {{ __('purchasing.requests.show.lines_title') }}
                            </h3>
                            <div class="overflow-hidden border border-slate-200 rounded-2xl">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">{{ __('purchasing.requests.show.th_part_no') }}</th>
                                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-widest">{{ __('purchasing.requests.show.th_desc') }}</th>
                                            <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">{{ __('purchasing.requests.show.th_qty') }}</th>
                                            <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">{{ __('purchasing.requests.show.th_price') }}</th>
                                            <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">{{ __('purchasing.requests.show.th_subtotal') }}</th>
                                            <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-widest">{{ __('purchasing.requests.show.th_date') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-100">
                                        @foreach ($purchaseRequest->items as $item)
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900 font-mono">
                                                    {{ $item->part?->part_no }}
                                                </td>
                                                <td class="px-6 py-4 text-sm text-slate-600">
                                                    {{ $item->part?->part_name }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-indigo-600 font-mono">
                                                    {{ number_format($item->qty, 4) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-slate-600 font-mono">
                                                    {{ number_format($item->unit_price, 2) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-slate-900 font-mono">
                                                    {{ number_format($item->subtotal, 2) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-slate-500">
                                                    {{ $item->required_date ? \Carbon\Carbon::parse($item->required_date)->format('M d, Y') : '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if ($purchaseRequest->notes)
                            <div>
                                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-3 flex items-center gap-2">
                                    <span class="h-px w-8 bg-slate-200"></span>
                                    {{ __('purchasing.requests.show.notes_title') }}
                                </h3>
                                <div class="bg-indigo-50/50 p-6 rounded-2xl border border-indigo-100 text-slate-700 text-sm italic leading-relaxed">
                                    {{ $purchaseRequest->notes }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
