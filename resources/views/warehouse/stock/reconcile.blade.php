<x-app-layout>
    <x-slot name="header">
        {{ __('warehouse.stock.reconcile.header') }}
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex flex-wrap gap-3 items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-slate-900">{{ __('warehouse.stock.reconcile.title') }}</div>
                        <div class="text-sm text-slate-500">
                            <span class="font-mono">inventories.on_hand</span> {{ __('warehouse.stock.reconcile.compared_to') }}
                            <span class="font-mono">SUM(location_inventory.qty_on_hand)</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('warehouse.stock.index') }}" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50">
                            {{ __('warehouse.stock.reconcile.stock_by_location') }}
                        </a>
                        @php
                            $canCreateAdjustment = in_array(strtolower((string) (auth()->user()?->role ?? '')), ['admin', 'ppic'], true);
                        @endphp
                        @if($canCreateAdjustment)
                            <a href="{{ route('warehouse.stock-adjustments.create') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700">
                                {{ __('warehouse.stock.reconcile.adjustment') }}
                            </a>
                        @endif
                    </div>
                </div>

                <form method="GET" class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('warehouse.stock.reconcile.search_label') }}</label>
                            <input name="search" value="{{ $search }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="{{ __('warehouse.stock.reconcile.search_ph') }}">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('warehouse.stock.reconcile.rows') }}</label>
                            <select name="per_page" class="w-full rounded-lg border-slate-300 text-sm">
                                @foreach([25,50,100,200] as $n)
                                    <option value="{{ $n }}" @selected((int) $perPage === $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="hidden" name="only_diff" value="0">
                                <input type="checkbox" name="only_diff" value="1" class="rounded border-slate-300" @checked($onlyDiff)>
                                {{ __('warehouse.stock.reconcile.only_mismatch') }}
                            </label>
                        </div>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-semibold">{{ __('warehouse.stock.reconcile.apply') }}</button>
                        <a href="{{ route('warehouse.stock.reconcile') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700">{{ __('warehouse.stock.reconcile.clear') }}</a>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">{{ __('warehouse.stock.reconcile.th_part') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-700 uppercase">{{ __('warehouse.stock.reconcile.th_inventory') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-700 uppercase">{{ __('warehouse.stock.reconcile.th_locations') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-700 uppercase">{{ __('warehouse.stock.reconcile.th_diff') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @forelse($rows as $row)
                                @php
                                    $inv = (float) ($row->on_hand ?? 0);
                                    $loc = (float) ($row->loc_qty ?? 0);
                                    $diff = (float) ($row->diff_qty ?? ($inv - $loc));
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900">{{ $row->gciPart?->part_no ?? ($row->part?->part_no ?? '-') }}</div>
                                        <div class="text-xs text-slate-500">{{ $row->gciPart?->part_name ?? ($row->part?->part_name_gci ?? ($row->part?->part_name_vendor ?? '')) }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-sm text-slate-900">{{ formatNumber($inv) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-sm text-slate-900">{{ formatNumber($loc) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-sm {{ $diff == 0 ? 'text-slate-400' : ($diff > 0 ? 'text-amber-700 font-bold' : 'text-red-700 font-bold') }}">
                                        {{ formatNumber($diff) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-slate-500">{{ __('warehouse.stock.reconcile.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($rows->hasPages())
                    <div class="px-6 py-4 border-t border-slate-200">
                        {{ $rows->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
