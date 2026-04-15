<x-app-layout>
    <x-slot name="header">
        WH Supply to Production
    </x-slot>

    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">WH Supply to Production</h1>
            <p class="mt-1 text-sm text-slate-500">Pantau tektokan material dari gudang ke line: material request, scan tag, posting supply WH, lalu serah terima ke production.</p>
        </div>

        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 shadow-sm">
            <div class="font-semibold">Backup via Web aktif</div>
            <p class="mt-1">Kalau APK gudang sedang bermasalah, tim tetap bisa lanjut dari web ini: `Post WH Supply` untuk potong stok berdasarkan alokasi material request, lalu `Terima Line` untuk catat serah terima ke produksi.</p>
        </div>

        <form method="GET" class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600">Month</label>
                    <input type="month" name="month" value="{{ $month }}" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600">Supply Status</label>
                    <select name="supply_status" class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                        <option value="">All</option>
                        <option value="pending" @selected($supplyStatus === 'pending')>Pending Supply</option>
                        <option value="supplied" @selected($supplyStatus === 'supplied')>Supplied</option>
                        <option value="handed_over" @selected($supplyStatus === 'handed_over')>Handed Over</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600">Search</label>
                    <input type="text" name="q" value="{{ $q }}" placeholder="WO / transaction / part"
                        class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                </div>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <a href="{{ route('production.warehouse-supply.index') }}"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Apply</button>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4 font-semibold">WO</th>
                        <th class="px-6 py-4 font-semibold">Part</th>
                        <th class="px-6 py-4 font-semibold">Plan Date</th>
                        <th class="px-6 py-4 font-semibold">Material Request</th>
                        <th class="px-6 py-4 font-semibold">Tahap Material</th>
                        <th class="px-6 py-4 font-semibold">WH Supply</th>
                        <th class="px-6 py-4 font-semibold">Serah Terima to Production</th>
                        <th class="px-6 py-4 text-right font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        @php
                            $woLabel = $order->production_order_number ?: ($order->transaction_no ?: ('WO#' . $order->id));
                            $scanCount = is_array($order->material_issue_lines) ? count($order->material_issue_lines) : 0;
                            $hasRequest = !empty($order->material_request_lines) || !is_null($order->material_requested_at);
                            $requestItemCount = is_array($order->material_request_lines) ? count($order->material_request_lines) : 0;
                            $stageLabel = 'Belum ada material request';
                            $stageClass = 'bg-slate-100 text-slate-700';

                            if ($order->material_handed_over_at) {
                                $stageLabel = 'Sudah diterima line';
                                $stageClass = 'bg-blue-100 text-blue-700';
                            } elseif ($order->material_issued_at) {
                                $stageLabel = 'Supply WH sudah diposting';
                                $stageClass = 'bg-emerald-100 text-emerald-700';
                            } elseif ($scanCount > 0) {
                                $stageLabel = 'Tag sudah discan, siap posting supply';
                                $stageClass = 'bg-amber-100 text-amber-700';
                            } elseif ($hasRequest) {
                                $stageLabel = 'Menunggu scan tag gudang';
                                $stageClass = 'bg-orange-100 text-orange-700';
                            }
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900">{{ $woLabel }}</div>
                                <div class="text-xs text-slate-500">
                                    @if($order->production_order_number && $order->transaction_no)
                                        {{ $order->transaction_no }}
                                    @elseif($order->production_order_number)
                                        Transaction No belum ada
                                    @else
                                        {{ $order->transaction_no ? 'Transaction No: ' . $order->transaction_no : 'Fallback WO dari ID order' }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-semibold text-slate-900">{{ $order->part?->part_no ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $order->part?->part_name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-700">{{ $order->plan_date ? \Carbon\Carbon::parse($order->plan_date)->format('d M Y') : '-' }}</td>
                            <td class="px-6 py-4">
                                @if($order->material_requested_at)
                                    <div class="text-sm font-medium text-slate-900">{{ $order->material_requested_at->format('d M Y H:i') }}</div>
                                    <div class="text-xs text-slate-500">{{ $order->material_request_lines ? count($order->material_request_lines) : 0 }} item</div>
                                @else
                                    <span class="text-xs italic text-slate-400">Belum dibuat</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $stageClass }}">
                                    {{ $stageLabel }}
                                </span>
                                <div class="mt-2 text-xs text-slate-500">
                                    Wajib scan: {{ $requestItemCount }} item RM
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    Sudah discan: {{ $scanCount }} tag
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($order->material_issued_at)
                                    <div class="text-sm font-medium text-emerald-700">{{ $order->material_issued_at->format('d M Y H:i') }}</div>
                                    <div class="text-xs text-slate-500">{{ $order->materialIssuer?->name ?? '-' }}</div>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Belum diposting</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($order->material_handed_over_at)
                                    <div class="text-sm font-medium text-blue-700">{{ $order->material_handed_over_at->format('d M Y H:i') }}</div>
                                    <div class="text-xs text-slate-500">{{ $order->materialHandoverUser?->name ?? '-' }}</div>
                                @else
                                    <span class="text-xs italic text-slate-400">Belum diterima line</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex flex-col items-end gap-2">
                                    @if(!$order->material_issued_at && $hasRequest)
                                        <form action="{{ route('production.orders.material-issue', $order) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                                                onclick="return confirm('Post WH Supply dari web untuk WO ini? Stok lokasi akan dipotong sesuai alokasi material request.');">
                                                Post WH Supply
                                            </button>
                                        </form>
                                    @endif

                                    @if($order->material_issued_at && !$order->material_handed_over_at)
                                        <form action="{{ route('production.orders.material-handover', $order) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center rounded-lg border border-blue-300 bg-white px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50"
                                                onclick="return confirm('Catat material WO ini sudah diterima line produksi?');">
                                                Terima Line
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('production.orders.show', $order) }}"
                                        class="text-xs font-semibold uppercase tracking-wide text-indigo-600 hover:text-indigo-900">
                                        Open WO
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">Belum ada data WH Supply to Production.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t bg-slate-50 px-6 py-4">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
