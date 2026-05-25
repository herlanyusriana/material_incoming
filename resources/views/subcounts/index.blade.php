<x-app-layout>
    <x-slot name="header">Subcount APK</x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900">Hasil Foto Timbang Subcount</h1>
                        <p class="mt-1 text-sm text-slate-500">Data yang dikirim dari APK berdasarkan WH Send Subcon, lengkap dengan foto packaging kosong dan barang + packaging.</p>
                    </div>
                    <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-800">
                        APK: <span class="font-mono">POST /api/subcounts</span>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Subcount</div>
                    <div class="mt-1 text-2xl font-black text-slate-900">{{ number_format($summary['total']) }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Foto Timbang</div>
                    <div class="mt-1 text-2xl font-black text-slate-900">{{ number_format($summary['records']) }}</div>
                </div>
                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wider text-blue-700">Total Net</div>
                    <div class="mt-1 text-2xl font-black text-blue-800">{{ number_format($summary['net'], 3) }} kg</div>
                </div>
            </div>

            <form method="GET" action="{{ route('subcounts.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="grid gap-3 lg:grid-cols-[1fr_180px_180px_auto_auto]">
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Search</label>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="WH Send, vendor, part, operator..." class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wider text-slate-500">To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-end">
                        <button class="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800">Filter</button>
                    </div>
                    <div class="flex items-end">
                        <a href="{{ route('subcounts.index') }}" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-bold text-slate-700 hover:bg-slate-50">Reset</a>
                    </div>
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">Foto</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">WH Send</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">Subcount</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">Part</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">Operator</th>
                                <th class="px-4 py-3 text-right font-bold text-slate-700">Timbang</th>
                                <th class="px-4 py-3 text-right font-bold text-slate-700">Net Kg</th>
                                <th class="px-4 py-3 text-center font-bold text-slate-700">Received</th>
                                <th class="px-4 py-3 text-center font-bold text-slate-700">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($subcounts as $subcount)
                                @php
                                    $latestRecord = $subcount->latestRecord;
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            @foreach ([$latestRecord?->packaging_photo_path, $latestRecord?->gross_photo_path] as $photoPath)
                                                @if ($photoPath)
                                                    <img src="{{ Storage::disk('public')->url($photoPath) }}" alt="Foto timbang" class="h-14 w-14 rounded-xl border border-slate-200 object-cover">
                                                @else
                                                    <div class="flex h-14 w-14 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-xs font-bold text-slate-300">-</div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-mono font-bold text-slate-900">{{ $subcount->subcon_order_no ?? $subcount->subconOrder?->order_no ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $subcount->subconOrder?->vendor?->vendor_name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-mono font-bold text-indigo-700">{{ $subcount->subcount_no }}</div>
                                        <div class="max-w-[220px] truncate text-xs font-semibold text-slate-500">{{ $subcount->title }}</div>
                                    </td>
                                    <td class="max-w-[280px] px-4 py-3 text-slate-600">
                                        <div class="truncate">{{ $subcount->part_info ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $subcount->operator_name ?: '-' }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ number_format($subcount->records_count) }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-blue-700">{{ number_format((float) $subcount->total_net_weight_kg, 3) }}</td>
                                    <td class="px-4 py-3 text-center text-slate-500">{{ $subcount->received_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('subcounts.show', $subcount) }}" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100">Detail Foto</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-10 text-center text-slate-400">Belum ada hasil subcount dari APK.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($subcounts->hasPages())
                    <div class="border-t border-slate-200 px-4 py-3">
                        {{ $subcounts->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
