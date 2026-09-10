<x-app-layout>
    <x-slot name="header">
        {{ __('warehouse.stock.adjustments_index.header') }}
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex flex-wrap gap-3 items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-slate-900">{{ __('warehouse.stock.adjustments_index.title') }}</div>
                        <div class="text-sm text-slate-500">{{ __('warehouse.stock.adjustments_index.subtitle') }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('warehouse.stock.index') }}" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50">
                            {{ __('warehouse.stock.adjustments_index.stock_by_location') }}
                        </a>
                        @if($canCreateAdjustment ?? false)
                            <a href="{{ route('warehouse.stock-adjustments.create') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700">
                                {{ __('warehouse.stock.adjustments_index.new_adjustment') }}
                            </a>
                        @endif
                    </div>
                </div>

                <form method="GET" class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('warehouse.stock.adjustments_index.search_label') }}</label>
                            <input name="search" value="{{ $search }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="{{ __('warehouse.stock.adjustments_index.search_ph') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('warehouse.stock.adjustments_index.location_label') }}</label>
                            <input name="location" value="{{ $location }}" class="w-full rounded-lg border-slate-300 text-sm uppercase" placeholder="{{ __('warehouse.stock.adjustments_index.location_ph') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('warehouse.stock.adjustments_index.from') }}</label>
                            <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('warehouse.stock.adjustments_index.to') }}</label>
                            <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('warehouse.stock.adjustments_index.rows') }}</label>
                            <select name="per_page" class="w-full rounded-lg border-slate-300 text-sm">
                                @foreach([25,50,100,200] as $n)
                                    <option value="{{ $n }}" @selected((int) $perPage === $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-semibold">{{ __('warehouse.stock.adjustments_index.apply') }}</button>
                        <a href="{{ route('warehouse.stock-adjustments.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700">{{ __('warehouse.stock.adjustments_index.clear') }}</a>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">{{ __('warehouse.stock.adjustments_index.th_when') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">{{ __('warehouse.stock.adjustments_index.th_location') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">{{ __('warehouse.stock.adjustments_index.th_batch') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">{{ __('warehouse.stock.adjustments_index.th_part') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">{{ __('warehouse.stock.adjustments_index.th_event') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-700 uppercase">{{ __('warehouse.stock.adjustments_index.th_before') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-700 uppercase">{{ __('warehouse.stock.adjustments_index.th_after') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-700 uppercase">{{ __('warehouse.stock.adjustments_index.th_change') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">{{ __('warehouse.stock.adjustments_index.th_by') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">{{ __('warehouse.stock.adjustments_index.th_reason') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @forelse($adjustments as $adj)
                                @php($delta = (float) ($adj->qty_change ?? 0))
                                @php($fromLoc = $adj->from_location_code ?? $adj->location_code)
                                @php($toLoc = $adj->to_location_code ?? $adj->location_code)
                                @php($fromBatch = $adj->from_batch_no ?? $adj->batch_no)
                                @php($toBatch = $adj->to_batch_no ?? $adj->batch_no)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ $adj->adjusted_at?->format('Y-m-d H:i') ?? $adj->created_at?->format('Y-m-d H:i') ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 font-mono font-semibold text-slate-900">
                                        {{ $fromLoc }}
                                        @if($toLoc && $toLoc !== $fromLoc)
                                            <span class="mx-1 text-slate-400 font-normal">→</span>
                                            <span class="text-indigo-700">{{ $toLoc }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-mono text-sm text-slate-700">
                                        @php($fromLabel = $fromBatch ? $fromBatch : __('warehouse.stock.adjustments_index.all_label'))
                                        @php($toLabel = $toBatch ? $toBatch : __('warehouse.stock.adjustments_index.all_label'))
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">{{ $fromLabel }}</span>
                                        @if($toLabel !== $fromLabel)
                                            <span class="mx-1 text-slate-400">→</span>
                                            <span class="px-2 py-1 bg-indigo-100 text-indigo-800 rounded">{{ $toLabel }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900">{{ $adj->part?->part_no ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $adj->part?->part_name_gci ?? ($adj->part?->part_name_vendor ?? '') }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-xs font-semibold text-slate-700">
                                        {{ $adj->event_type ? strtoupper(str_replace('_', ' ', $adj->event_type)) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-sm">{{ formatNumber((float) ($adj->qty_before ?? 0)) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-sm">{{ formatNumber((float) ($adj->qty_after ?? 0)) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-sm {{ $delta == 0 ? 'text-slate-400' : ($delta > 0 ? 'text-emerald-700 font-bold' : 'text-red-700 font-bold') }}">
                                        {{ formatNumber($delta) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">{{ $adj->creator?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-600">{{ $adj->reason ?? '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-slate-500">{{ __('warehouse.stock.adjustments_index.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($adjustments->hasPages())
                    <div class="px-6 py-4 border-t border-slate-200">
                        {{ $adjustments->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
