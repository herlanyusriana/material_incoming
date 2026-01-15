<x-app-layout>
    <x-slot name="header">
        Planning • Forecast (Part GCI)
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

            <div class="bg-white shadow-lg border border-slate-200 rounded-2xl p-6 space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <form method="GET" class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Minggu (YYYY-WW)</label>
                            <input name="minggu" value="{{ $minggu }}" class="mt-1 rounded-xl border-slate-200" placeholder="All weeks">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Part GCI</label>
                            <select name="part_id" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                @foreach ($parts as $p)
                                    <option value="{{ $p->id }}" @selected((string) $partId === (string) $p->id)>{{ $p->part_no }} — {{ $p->part_name ?? '-' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                    </form>

                    <form method="POST" action="{{ route('planning.forecasts.generate') }}">
                        @csrf
                        <input type="hidden" name="minggu" value="{{ $minggu }}">
                        <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Generate Forecast</button>
                    </form>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Minggu</th>
                                <th class="px-4 py-3 text-left font-semibold">Part GCI</th>
                                <th class="px-4 py-3 text-right font-semibold">Planning Qty</th>
                                <th class="px-4 py-3 text-right font-semibold">Open PO Qty</th>
                                <th class="px-4 py-3 text-right font-semibold">Forecast Qty</th>
                                <th class="px-4 py-3 text-left font-semibold">Source</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($forecasts as $f)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-mono text-xs">{{ $f->minggu }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold">{{ $f->part->part_no ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $f->part->part_name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format((float) $f->planning_qty, 3) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format((float) $f->po_qty, 3) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs font-semibold">{{ number_format((float) $f->qty, 3) }}</td>
                                    <td class="px-4 py-3 text-xs uppercase tracking-wide text-slate-600">{{ $f->source }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">No forecasts. Click Generate.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $forecasts->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
