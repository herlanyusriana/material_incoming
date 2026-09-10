<x-app-layout>
    <x-slot name="header">{{ __('wo_tracking.header') }}</x-slot>

    <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-4">
        @if (session('success'))<div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>@endif

        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-wrap items-center gap-3">
            <h1 class="text-lg font-bold text-slate-900 flex-1">{{ __('wo_tracking.title') }}</h1>
            <form class="flex items-center gap-2">
                <input type="text" name="q" value="{{ $q }}" placeholder="{{ __('wo_tracking.search_ph') }}" class="rounded-xl border-slate-200 text-sm">
                <select name="status" class="rounded-xl border-slate-200 text-sm">
                    <option value="">{{ __('wo_tracking.all_status') }}</option>
                    @foreach (['PLANNED', 'RELEASED', 'IN_PRODUCTION', 'CLOSED', 'CANCELLED'] as $s)
                        <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">{{ __('wo_tracking.filter') }}</button>
            </form>
            <a href="{{ route('production.wo-tracking.create') }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">{{ __('wo_tracking.new_wo') }}</a>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_wo') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_part') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_target') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_actual') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_status') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($wos as $wo)
                        @php
                            $badge = ['PLANNED' => 'bg-slate-100 text-slate-700', 'RELEASED' => 'bg-sky-100 text-sky-700', 'IN_PRODUCTION' => 'bg-amber-100 text-amber-700', 'CLOSED' => 'bg-emerald-100 text-emerald-700', 'CANCELLED' => 'bg-rose-100 text-rose-700'][$wo->status] ?? 'bg-slate-100 text-slate-600';
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-bold text-indigo-600">
                                <a href="{{ route('production.wo-tracking.show', $wo) }}">{{ $wo->work_order_no }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">{{ $wo->gciPart?->part_no }}</div>
                                <div class="text-xs text-slate-400">{{ $wo->gciPart?->part_name }}</div>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format((float) $wo->qty_target, 0) }} {{ $wo->gciPart?->uom }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format((float) $wo->qty_actual, 0) }}</td>
                            <td class="px-4 py-3 text-center"><span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase {{ $badge }}">{{ $wo->status }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('production.wo-tracking.show', $wo) }}" class="text-sm font-semibold text-indigo-600 hover:underline">{{ __('wo_tracking.open') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">{{ __('wo_tracking.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-4 py-3 bg-slate-50 border-t border-slate-100">{{ $wos->links() }}</div>
        </div>
    </div>
</x-app-layout>
