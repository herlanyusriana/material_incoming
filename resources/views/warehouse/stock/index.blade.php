<x-app-layout>
    <x-slot name="header">
        Warehouse • Stock by Location
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex flex-wrap gap-3 items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-slate-900">Stock per Location</div>
                        <div class="text-sm text-slate-500">Source: <span class="font-mono">location_inventory</span></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('warehouse.stock.reconcile') }}" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50">
                            Reconcile
                        </a>
                        <a href="{{ route('warehouse.stock-adjustments.index') }}" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50">
                            Adjustments
                        </a>
                    </div>
                </div>

                <form method="GET" class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Search part / location</label>
                            <input name="search" value="{{ $search }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="PART NO / name / RACK-A1">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Location</label>
                            <input name="location" value="{{ $location }}" class="w-full rounded-lg border-slate-300 text-sm uppercase" placeholder="RACK-A1">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Rows</label>
                            <select name="per_page" class="w-full rounded-lg border-slate-300 text-sm">
                                @foreach([25,50,100,200] as $n)
                                    <option value="{{ $n }}" @selected((int) $perPage === $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="hidden" name="only_positive" value="0">
                                <input type="checkbox" name="only_positive" value="1" class="rounded border-slate-300" @checked($onlyPositive)>
                                Only qty &gt; 0
                            </label>
                        </div>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-semibold">Apply</button>
                        <a href="{{ route('warehouse.stock.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700">Clear</a>
                    </div>
                </form>

                <div class="px-6 py-4 border-b border-slate-200 bg-white">
                    <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                        <div class="text-slate-600">
                            Total rows: <span class="font-semibold text-slate-900">{{ $records->total() }}</span>
                        </div>
                        <div class="text-slate-600">
                            Grand total qty: <span class="font-semibold text-slate-900">{{ formatNumber($grandTotal) }}</span>
                        </div>
                    </div>
                    @if(!empty($totalsByLocation))
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($totalsByLocation as $loc => $qty)
                                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-700">
                                    <span class="font-mono font-semibold">{{ $loc }}</span>
                                    <span class="text-slate-400">•</span>
                                    <span class="font-semibold">{{ formatNumber((float) $qty) }}</span>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Location</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Part</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Name</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-700 uppercase">Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Updated</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @forelse($records as $rec)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="font-mono font-semibold text-slate-900">{{ $rec->location_code }}</div>
                                        @if($rec->location)
                                            <div class="text-xs text-slate-500">
                                                @php
                                                    $meta = [];
                                                    if ($rec->location->class) $meta[] = 'Class ' . $rec->location->class;
                                                    if ($rec->location->zone) $meta[] = 'Zone ' . $rec->location->zone;
                                                @endphp
                                                {{ implode(' • ', $meta) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-slate-900">{{ $rec->part?->part_no ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">Part ID: {{ $rec->part_id }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ $rec->part?->part_name_gci ?? ($rec->part?->part_name_vendor ?? '-') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-mono font-bold text-indigo-700">{{ formatNumber((float) $rec->qty_on_hand) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ $rec->updated_at?->format('Y-m-d H:i') ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">No records.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($records->hasPages())
                    <div class="px-6 py-4 border-t border-slate-200">
                        {{ $records->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

