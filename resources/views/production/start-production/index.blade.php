<x-app-layout>
    <x-slot name="header">
        Start Production
    </x-slot>

    @php
        $summary = $summary ?? ['total' => 0, 'shortage' => 0, 'missing_bom' => 0, 'machine_mismatch' => 0];
    @endphp

    <div class="space-y-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-800">Orders Ready to Start Production</h2>
                <p class="text-sm text-slate-500">Daftar ini menampilkan kesiapan BOM, shortage material, dan arahan mesin supaya operator lebih cepat ambil keputusan.</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                Bypass start tanpa supply WH sedang aktif. WO yang shortage tetap bisa terlihat supaya risiko lapangan tidak tertutup.
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">WO Tampil</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ $summary['total'] }}</div>
            </div>
            <div class="rounded-xl border border-red-100 bg-red-50 p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-red-500">Shortage RM</div>
                <div class="mt-1 text-2xl font-bold text-red-700">{{ $summary['shortage'] }}</div>
            </div>
            <div class="rounded-xl border border-amber-100 bg-amber-50 p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-amber-600">BOM Belum Siap</div>
                <div class="mt-1 text-2xl font-bold text-amber-700">{{ $summary['missing_bom'] }}</div>
            </div>
            <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-blue-600">Mismatch Mesin</div>
                <div class="mt-1 text-2xl font-bold text-blue-700">{{ $summary['machine_mismatch'] }}</div>
            </div>
        </div>

        <form method="GET" class="bg-white border rounded-xl shadow-sm p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-600">Search</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Order number / Part" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('production.start-production.index') }}" class="px-3 py-2 rounded-lg border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                    <button class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">Apply</button>
                </div>
            </div>
        </form>

        <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-sm text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Order #</th>
                            <th class="px-6 py-4 font-semibold">Part</th>
                            <th class="px-6 py-4 font-semibold">Process / Machine</th>
                            <th class="px-6 py-4 font-semibold">BOM Readiness</th>
                            <th class="px-6 py-4 font-semibold">Material</th>
                            <th class="px-6 py-4 font-semibold">Plan Date</th>
                            <th class="px-6 py-4 font-semibold">Qty Planned</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($orders as $order)
                            @php($readiness = $order->start_readiness ?? [])
                            <tr class="hover:bg-slate-50 transition-colors align-top">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">{{ $order->production_order_number }}</div>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $order->status === 'released' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ strtoupper(str_replace('_', ' ', $order->status)) }}
                                        </span>
                                        @if(($readiness['shortage_count'] ?? 0) > 0)
                                            <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700">
                                                {{ $readiness['shortage_count'] }} shortage
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900">{{ $order->part->part_no }}</div>
                                    <div class="text-xs text-slate-500">{{ $order->part->part_name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-slate-900">{{ $order->process_name ?: '-' }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $order->machine?->name ?: '-' }}{{ $order->die_name ? ' • ' . $order->die_name : '' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($readiness['has_bom'] ?? false)
                                        <div class="text-xs font-semibold text-emerald-700">BOM aktif {{ $readiness['bom_line_count'] ?? 0 }} line</div>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold {{ ($readiness['process_matched'] ?? false) ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ ($readiness['process_matched'] ?? false) ? 'Process OK' : 'Process cek BOM' }}
                                            </span>
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold {{ ($readiness['machine_matched'] ?? false) ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">
                                                {{ ($readiness['machine_matched'] ?? false) ? 'Machine OK' : 'Machine cek BOM' }}
                                            </span>
                                        </div>
                                        @if(!empty($readiness['recommended_machines'] ?? []))
                                            <div class="mt-1 text-[11px] text-slate-500">
                                                Rekomendasi: {{ implode(', ', array_slice($readiness['recommended_machines'], 0, 2)) }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="text-xs font-semibold text-red-700">Belum ada BOM aktif</div>
                                        <div class="mt-1 text-[11px] text-slate-500">Routing dan kebutuhan material perlu dicek planning.</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($readiness['has_material_request'] ?? false)
                                        <div class="text-xs font-semibold {{ ($readiness['shortage_count'] ?? 0) > 0 ? 'text-red-700' : 'text-emerald-700' }}">
                                            {{ $readiness['material_line_count'] ?? 0 }} item material
                                        </div>
                                        <div class="mt-1 text-[11px] text-slate-500">
                                            @if(($readiness['shortage_count'] ?? 0) > 0)
                                                Shortage: {{ $readiness['first_shortage_part_no'] ?: '-' }}
                                            @elseif($readiness['material_handed_over'] ?? false)
                                                Sudah serah terima ke line
                                            @elseif($readiness['material_issued'] ?? false)
                                                Sudah di-issue WH
                                            @else
                                                Request sudah dibuat
                                            @endif
                                        </div>
                                    @else
                                        <div class="text-xs font-semibold text-amber-700">Belum ada material request</div>
                                        <div class="mt-1 text-[11px] text-slate-500">Refresh material dari BOM untuk traceability RM yang lebih rapi.</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $order->plan_date ? \Carbon\Carbon::parse($order->plan_date)->format('d M Y') : '-' }}</td>
                                <td class="px-6 py-4 font-mono text-slate-700">{{ number_format($order->qty_planned) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('production.orders.show', $order) }}" class="px-3 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            Detail
                                        </a>
                                        <form method="POST" action="{{ route('production.start-production.start', $order) }}" class="inline">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Start production for this order?')" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-xs font-semibold">
                                                Start
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-slate-500 italic">
                                    No orders ready to start production.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t bg-slate-50">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
