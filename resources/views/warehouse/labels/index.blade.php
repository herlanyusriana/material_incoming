<x-app-layout>
    <x-slot name="header">
        {{ __('warehouse.labels.index.header') }}
    </x-slot>

    <div class="space-y-6">
        <!-- Search and Filter -->
        <div class="bg-white border rounded-xl shadow-sm p-6">
            <form method="GET" class="flex gap-4">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('warehouse.labels.index.search_ph') }}"
                    class="flex-1 rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" />
                <select name="policy" class="rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('warehouse.labels.index.all_policy') }}</option>
                    <option value="line_stock" @selected(($policy ?? '') === 'line_stock')>{{ __('warehouse.labels.index.line_stock_policy') }}</option>
                </select>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    {{ __('warehouse.labels.index.search') }}
                </button>
            </form>
        </div>

        <!-- Parts List with Checkboxes -->
        <form method="POST" action="{{ route('warehouse.labels.bulk') }}" target="_blank">
            @csrf
            <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b bg-slate-50 flex justify-between items-center gap-4">
                    <h3 class="font-semibold text-slate-900">{{ __('warehouse.labels.index.select_parts') }}</h3>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2">
                            <label for="global-batch" class="text-xs font-bold text-slate-500 uppercase">{{ __('warehouse.labels.index.batch_no') }}</label>
                            <input type="text" id="global-batch" name="batch" placeholder="{{ __('warehouse.labels.index.batch_ph') }}"
                                class="w-32 px-3 py-1.5 text-sm rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-semibold shadow-sm">
                            {{ __('warehouse.labels.index.print_selected') }}
                        </button>
                    </div>
                </div>

                <table class="w-full text-sm">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b">
                        <tr>
                            <th class="px-6 py-4 text-left">
                                <input type="checkbox" id="select-all" class="rounded border-slate-300">
                            </th>
                            <th class="px-6 py-4 text-left font-semibold">{{ __('warehouse.labels.index.th_part_no') }}</th>
                            <th class="px-6 py-4 text-left font-semibold">{{ __('warehouse.labels.index.th_barcode') }}</th>
                            <th class="px-6 py-4 text-left font-semibold">{{ __('warehouse.labels.index.th_part_name') }}</th>
                            <th class="px-6 py-4 text-left font-semibold">{{ __('warehouse.labels.index.th_classification') }}</th>
                            <th class="px-6 py-4 text-left font-semibold">{{ __('warehouse.labels.index.th_policy') }}</th>
                            <th class="px-6 py-4 text-right">{{ __('warehouse.labels.index.th_action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($parts as $part)
                            @php /** @var \App\Models\GciPart $part */ @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-4">
                                    <input type="checkbox" name="part_ids[]" value="{{ $part->id }}"
                                        class="part-checkbox rounded border-slate-300">
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-900">{{ $part->part_no }}</td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-600">{{ $part->generateBarcode() }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $part->part_name }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                {{ $part->classification === 'FG' ? 'bg-green-100 text-green-700' : '' }}
                                                {{ $part->classification === 'WIP' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                                {{ $part->classification === 'RM' ? 'bg-blue-100 text-blue-700' : '' }}
                                            ">
                                        {{ $part->classification }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600">
                                    {{ $part->consumption_policy === 'backflush_line_stock' ? __('warehouse.labels.index.line_stock_policy') : ($part->consumption_policy ?: '-') }}
                                </td>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <a href="{{ route('warehouse.labels.part', $part) }}" target="_blank"
                                        class="text-indigo-600 hover:text-indigo-900 font-medium text-xs uppercase">
                                        {{ __('warehouse.labels.index.print_single') }}
                                    </a>
                                    @if($part->consumption_policy === 'backflush_line_stock')
                                        <a href="{{ route('warehouse.labels.line-stock', $part) }}" target="_blank"
                                            class="text-emerald-700 hover:text-emerald-900 font-medium text-xs uppercase">
                                            {{ __('warehouse.labels.index.line_stock_qr') }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500 italic">
                                    {{ __('warehouse.labels.index.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="px-6 py-4 border-t bg-slate-50">
                    {{ $parts->links() }}
                </div>
            </div>
        </form>
    </div>

    <script>
        // Select all checkbox functionality
        document.getElementById('select-all').addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.part-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        // Add batch to single print links
        document.querySelectorAll('a[href*="/labels/part/"]').forEach(link => {
            link.addEventListener('click', function (e) {
                const batch = document.getElementById('global-batch').value;
                if (batch) {
                    const url = new URL(this.href);
                    url.searchParams.set('batch', batch);
                    this.href = url.toString();
                }
            });
        });
    </script>
</x-app-layout>
