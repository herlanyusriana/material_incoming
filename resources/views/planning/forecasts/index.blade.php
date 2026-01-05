<x-app-layout>
    <x-slot name="header">
        Planning • Forecast
    </x-slot>

    <div class="py-6" x-data="planningForecasts()">
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
                            <label class="text-xs font-semibold text-slate-600">Period (YYYY-MM)</label>
                            <input name="period" value="{{ $period }}" class="mt-1 rounded-xl border-slate-200" placeholder="2026-01">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-slate-600">Product</label>
                            <select name="product_id" class="mt-1 rounded-xl border-slate-200">
                                <option value="">All</option>
                                @foreach ($products as $p)
                                    <option value="{{ $p->id }}" @selected((string) $productId === (string) $p->id)>{{ $p->code }} — {{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                    </form>

                    <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold" @click="openCreate()">
                        Add / Update Forecast
                    </button>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="min-w-full text-sm divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                <th class="px-4 py-3 text-left font-semibold">Product</th>
                                <th class="px-4 py-3 text-left font-semibold">Period</th>
                                <th class="px-4 py-3 text-right font-semibold">Qty</th>
                                <th class="px-4 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($forecasts as $f)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="font-semibold">{{ $f->product->code ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $f->product->name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ $f->period }}</td>
                                    <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format((float) $f->qty, 3) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" class="text-indigo-600 hover:text-indigo-800 font-semibold" @click="openEdit(@js($f))">Edit</button>
                                        <form action="{{ route('planning.forecasts.destroy', $f) }}" method="POST" class="inline" onsubmit="return confirm('Delete forecast?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="ml-3 text-red-600 hover:text-red-800 font-semibold" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-slate-500">No forecasts</td>
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

        <!-- Modal -->
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="modalOpen" x-cloak @keydown.escape.window="close()">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900" x-text="mode === 'create' ? 'Add/Update Forecast' : 'Edit Forecast'"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">✕</button>
                </div>

                <form :action="formAction" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <template x-if="mode === 'create'">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-semibold text-slate-700">Product</label>
                                <select name="product_id" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.product_id">
                                    <option value="" disabled>Select product</option>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-semibold text-slate-700">Period</label>
                                <input name="period" class="mt-1 w-full rounded-xl border-slate-200" placeholder="YYYY-MM" required x-model="form.period">
                            </div>
                        </div>
                    </template>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Qty</label>
                        <input type="number" step="0.001" min="0" name="qty" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.qty">
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function planningForecasts() {
                return {
                    modalOpen: false,
                    mode: 'create',
                    formAction: @js(route('planning.forecasts.store')),
                    form: { id: null, product_id: '', period: @js($period), qty: '0' },
                    openCreate() {
                        this.mode = 'create';
                        this.formAction = @js(route('planning.forecasts.store'));
                        this.form = { id: null, product_id: '', period: @js($period), qty: '0' };
                        this.modalOpen = true;
                    },
                    openEdit(f) {
                        this.mode = 'edit';
                        this.formAction = @js(url('/planning/forecasts')) + '/' + f.id;
                        this.form = { id: f.id, product_id: f.product_id, period: f.period, qty: f.qty };
                        this.modalOpen = true;
                    },
                    close() { this.modalOpen = false; },
                }
            }
        </script>
    </div>
</x-app-layout>

