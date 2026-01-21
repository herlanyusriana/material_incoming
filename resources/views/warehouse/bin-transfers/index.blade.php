<x-app-layout>
    <x-slot name="header">
        Warehouse • Bin Transfers
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800 mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-slate-900">Bin Transfer History</h2>
                    <a href="{{ route('warehouse.bin-transfers.create') }}"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition-colors">
                        + New Transfer
                    </a>
                </div>

                {{-- Filters --}}
                <form method="GET" class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Part</label>
                            <select name="part_id" class="w-full rounded-lg border-slate-300 text-sm">
                                <option value="">All Parts</option>
                                @foreach($parts as $part)
                                    <option value="{{ $part->id }}" {{ request('part_id') == $part->id ? 'selected' : '' }}>
                                        {{ $part->part_no }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Location</label>
                            <select name="location" class="w-full rounded-lg border-slate-300 text-sm">
                                <option value="">All Locations</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->location_code }}" {{ request('location') == $location->location_code ? 'selected' : '' }}>
                                        {{ $location->location_code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">From Date</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}"
                                class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">To Date</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}"
                                class="w-full rounded-lg border-slate-300 text-sm">
                        </div>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button type="submit"
                            class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-semibold">
                            Apply Filters
                        </button>
                        <a href="{{ route('warehouse.bin-transfers.index') }}"
                            class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700">
                            Clear
                        </a>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">Part</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">From Location
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-slate-700 uppercase">→</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">To Location
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-slate-700 uppercase">Qty</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-slate-700 uppercase">By</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-slate-700 uppercase">Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            @forelse($transfers as $transfer)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-sm text-slate-900">
                                        {{ $transfer->transfer_date->format('Y-m-d') }}
                                        <div class="text-xs text-slate-500">{{ $transfer->created_at->format('H:i') }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-semibold text-slate-900">{{ $transfer->part->part_no }}
                                        </div>
                                        <div class="text-xs text-slate-500">{{ $transfer->part->part_name_gci }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-700">
                                            {{ $transfer->from_location_code }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-slate-400">
                                        →
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-700">
                                            {{ $transfer->to_location_code }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span
                                            class="text-sm font-bold text-indigo-600">{{ formatNumber($transfer->qty) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ $transfer->creator->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('warehouse.bin-transfers.show', $transfer) }}"
                                                class="text-indigo-600 hover:text-indigo-900 font-semibold text-sm">
                                                View
                                            </a>
                                            <span class="text-slate-300">|</span>
                                            <a href="{{ route('warehouse.bin-transfers.label', $transfer) }}"
                                                target="_blank"
                                                class="text-green-600 hover:text-green-900 font-semibold text-sm">
                                                🖨️ Label
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-slate-500">
                                        No bin transfers yet. Create your first transfer to move materials between
                                        locations.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transfers->hasPages())
                    <div class="px-6 py-4 border-t border-slate-200">
                        {{ $transfers->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>