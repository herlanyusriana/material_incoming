<x-app-layout>
    <x-slot name="header">Subcount</x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900">Subcount Receive</h1>
                        <p class="mt-1 text-sm text-slate-500">Hasil subcount yang dikirim dari APK, lengkap dengan foto realtime timbangan.</p>
                    </div>
                    <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-800">
                        API: <span class="font-mono">POST /api/subcounts</span>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">WH Send</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">No Subcount</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">Judul</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">Part / Job / Lot</th>
                                <th class="px-4 py-3 text-left font-bold text-slate-700">Operator</th>
                                <th class="px-4 py-3 text-right font-bold text-slate-700">Records</th>
                                <th class="px-4 py-3 text-right font-bold text-slate-700">Net Kg</th>
                                <th class="px-4 py-3 text-center font-bold text-slate-700">Received</th>
                                <th class="px-4 py-3 text-center font-bold text-slate-700">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($subcounts as $subcount)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="font-mono font-bold text-slate-900">{{ $subcount->subcon_order_no ?? $subcount->subconOrder?->order_no ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $subcount->subconOrder?->vendor?->vendor_name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-mono font-bold text-indigo-700">{{ $subcount->subcount_no }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $subcount->title }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $subcount->part_info ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $subcount->operator_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right font-mono">{{ number_format($subcount->records_count) }}</td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-blue-700">{{ number_format((float) $subcount->total_net_weight_kg, 3) }}</td>
                                    <td class="px-4 py-3 text-center text-slate-500">{{ $subcount->received_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('subcounts.show', $subcount) }}" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100">Detail</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-10 text-center text-slate-400">Belum ada subcount dari APK.</td>
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
