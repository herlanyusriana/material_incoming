<x-app-layout>
    <x-slot name="header">
        Planning • MPS
    </x-slot>

    <div class="py-6" x-data="planningMps()">
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
                    <form method="GET" class="flex items-end gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Period (YYYY-MM)</label>
                            <input name="period" value="{{ $period }}" class="mt-1 rounded-xl border-slate-200" placeholder="2026-01">
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Load</button>
                    </form>

                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('planning.mps.generate') }}">
                            @csrf
                            <input type="hidden" name="period" value="{{ $period }}">
                            <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Generate</button>
                        </form>
                        <form method="POST" action="{{ route('planning.mps.approve') }}" onsubmit="return confirm('Approve all draft MPS rows for this period?')">
                            @csrf
                            <input type="hidden" name="period" value="{{ $period }}">
                            <button class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">Approve</button>
                        </form>
                    </div>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Product</th>
                                <th class="px-4 py-3 text-right font-semibold">Forecast</th>
                                <th class="px-4 py-3 text-right font-semibold">Open Orders</th>
                                <th class="px-4 py-3 text-right font-semibold">Planned Qty</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($rows as $r)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold">{{ $r->product->code ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $r->product->name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format((float) $r->forecast_qty, 3) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format((float) $r->open_order_qty, 3) }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format((float) $r->planned_qty, 3) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold {{ $r->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                            {{ strtoupper($r->status) }}
                                        </span>
                                        @if ($r->status === 'approved' && $r->approved_at)
                                            <div class="text-xs text-slate-500 mt-1">Approved {{ $r->approved_at->format('Y-m-d H:i') }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($r->status !== 'approved')
                                            <button type="button" class="text-indigo-600 hover:text-indigo-800 font-semibold" @click="openEdit(@js($r))">Edit</button>
                                        @else
                                            <span class="text-xs text-slate-400">Locked</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">No MPS rows. Click Generate.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="modalOpen" x-cloak @keydown.escape.window="close()">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900">Edit Planned Qty</div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">✕</button>
                </div>

                <form :action="formAction" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">

                    <div class="text-sm text-slate-700">
                        <div class="font-semibold" x-text="form.productLabel"></div>
                        <div class="text-xs text-slate-500">Period: {{ $period }}</div>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Planned Qty</label>
                        <input type="number" step="0.001" min="0" name="planned_qty" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.planned_qty">
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function planningMps() {
                return {
                    modalOpen: false,
                    formAction: '',
                    form: { id: null, planned_qty: '0', productLabel: '' },
                    openEdit(r) {
                        this.formAction = @js(url('/planning/mps')) + '/' + r.id;
                        const code = r.product?.code ?? '-';
                        const name = r.product?.name ?? '-';
                        this.form = { id: r.id, planned_qty: r.planned_qty, productLabel: `${code} — ${name}` };
                        this.modalOpen = true;
                    },
                    close() { this.modalOpen = false; },
                }
            }
        </script>
    </div>
</x-app-layout>

