<x-app-layout>
    <x-slot name="header">{{ __('wo_tracking.header') }}</x-slot>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-4"
         x-data="{ res: null, resQty: '' }">

        @if (session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
        @endif

        {{-- Toolbar --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-wrap items-center gap-3">
            <h1 class="text-lg font-bold text-slate-900 flex-1">{{ __('wo_tracking.title') }}</h1>
            <form class="flex items-center gap-2" method="GET">
                <input type="text" name="q" value="{{ $q }}" placeholder="{{ __('wo_tracking.search_ph') }}" class="rounded-xl border-slate-200 text-sm">
                <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">{{ __('wo_tracking.search_btn') }}</button>
            </form>
            <a href="{{ route('production.wo-tracking.create') }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">{{ __('wo_tracking.new_wo') }}</a>
        </div>

        {{-- Daftar WO --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_fg_part') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_wo_no') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_date') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_wo_qty') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_result_qty') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_sisa_qty') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_invoice_asal') }}</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($wos as $wo)
                            @php
                                $target = (float) $wo->qty_target;
                                $result = (float) $wo->qty_actual;
                                $sisa = round(max(0, $target - $result), 4);
                                $closed = in_array($wo->status, ['CLOSED', 'CANCELLED'], true);
                            @endphp
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-slate-800">{{ $wo->gciPart?->part_no ?? '—' }}</div>
                                    <div class="text-xs text-slate-400">{{ $wo->gciPart?->part_name }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-bold text-indigo-600 whitespace-nowrap">{{ $wo->work_order_no }}</div>
                                    @if ($closed)
                                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                            {{ $wo->status === 'CLOSED' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600' }}">
                                            {{ $wo->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $wo->start_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-semibold">{{ number_format($target, 0) }} <span class="text-xs text-slate-400">{{ $wo->gciPart?->uom }}</span></td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($result, 0) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-bold {{ $sisa <= 0 ? 'text-emerald-600' : 'text-amber-600' }}">{{ number_format($sisa, 0) }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-slate-700">{{ $wo->rm_invoice_no ?? '—' }}</div>
                                    @if ($wo->rm_tag)
                                        <div class="text-xs text-slate-400 font-mono">{{ $wo->rm_tag }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if (! $closed)
                                        <button type="button"
                                                class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold"
                                                @click="res = { id: {{ $wo->id }}, no: '{{ $wo->work_order_no }}', sisa: {{ $sisa }}, action: '{{ route('production.wo-tracking.result', $wo) }}' }; resQty = {{ $sisa > 0 ? $sisa : 1 }};">
                                            {{ __('wo_tracking.result_btn') }}
                                        </button>
                                        <a href="{{ route('production.wo-tracking.edit', $wo) }}" class="ml-1 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold">{{ __('wo_tracking.edit_btn') }}</a>
                                    @endif
                                    <form action="{{ route('production.wo-tracking.destroy', $wo) }}" method="POST" class="inline"
                                          onsubmit="return confirm(@js(__('wo_tracking.confirm_delete')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-1 px-3 py-1.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-600 text-xs font-bold">{{ __('wo_tracking.delete_btn') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-10 text-center text-slate-400">{{ __('wo_tracking.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 bg-slate-50 border-t border-slate-100">{{ $wos->links() }}</div>
        </div>

        {{-- Modal posting hasil --}}
        <div x-show="res" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/50" @click="res = null"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6" role="dialog" aria-modal="true">
                <h3 class="text-base font-bold text-slate-900">{{ __('wo_tracking.result_title') }} <span class="text-indigo-600" x-text="res?.no"></span></h3>
                <p class="text-sm text-slate-500 mt-1">{{ __('wo_tracking.result_hint') }}</p>
                <form method="POST" :action="res?.action" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">{{ __('wo_tracking.qty_result') }}</label>
                        <input type="number" name="qty_result" x-model="resQty" step="0.0001" min="0.0001" required
                               class="w-full rounded-xl border-slate-300 text-sm" autofocus>
                    </div>
                    <div class="flex justify-end gap-3 pt-1">
                        <button type="button" class="px-5 py-2 rounded-xl bg-slate-100 text-slate-700 text-sm font-bold" @click="res = null">{{ __('wo_tracking.cancel') }}</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold">{{ __('wo_tracking.post_result') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
