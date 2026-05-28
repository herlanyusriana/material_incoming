<x-app-layout>
    <x-slot name="header">Subcount Detail</x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <a href="{{ route('subcounts.index') }}" class="text-xs font-bold uppercase tracking-wider text-slate-400 hover:text-slate-700">&larr; Back</a>
                        <h1 class="mt-2 text-2xl font-black text-slate-900">{{ $subcount->subcount_no }}</h1>
                        <p class="mt-1 text-sm text-slate-500">{{ $subcount->title }}</p>
                    </div>
                    <div class="rounded-xl bg-blue-50 px-4 py-3 text-right">
                        <div class="text-xs font-bold uppercase tracking-wider text-blue-700">Total Net</div>
                        <div class="text-2xl font-black text-blue-800">{{ number_format((float) $subcount->total_net_weight_kg, 3) }} kg</div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">WH Send</div>
                    <div class="mt-1 font-mono font-bold text-slate-900">{{ $subcount->subcon_order_no ?? $subcount->subconOrder?->order_no ?? '-' }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ $subcount->subconOrder?->vendor?->vendor_name ?? '-' }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Part / Job / Lot</div>
                    <div class="mt-1 font-bold text-slate-900">{{ $subcount->part_info ?? '-' }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Operator</div>
                    <div class="mt-1 font-bold text-slate-900">{{ $subcount->operator_name ?? '-' }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Records</div>
                    <div class="mt-1 font-bold text-slate-900">{{ number_format($subcount->records->count()) }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Received</div>
                    <div class="mt-1 font-bold text-slate-900">{{ $subcount->received_at?->format('d/m/Y H:i') ?? '-' }}</div>
                </div>
            </div>

            @if ($subcount->description)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-700 shadow-sm">
                    {{ $subcount->description }}
                </div>
            @endif

            <div class="grid gap-4">
                @forelse ($subcount->records as $record)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Packaging</div>
                                <h2 class="mt-1 text-xl font-black text-slate-900">{{ number_format((int) $record->packaging_qty) }} {{ strtoupper($record->packaging_type ?? '-') }}</h2>
                                <div class="mt-1 text-sm font-semibold text-slate-500">{{ $record->packaging_id }}</div>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-center text-xs">
                                <div class="rounded-xl bg-slate-50 px-3 py-2">
                                    <div class="font-bold text-slate-500">Brutto</div>
                                    <div class="mt-1 font-mono text-sm font-black text-slate-900">{{ number_format((float) $record->gross_weight_kg, 3) }}</div>
                                </div>
                                <div @class([
                                    'rounded-xl px-3 py-2',
                                    'bg-blue-50' => (float) $record->net_item_weight_kg > 0,
                                    'bg-amber-50' => (float) $record->net_item_weight_kg <= 0,
                                ])>
                                    <div @class([
                                        'font-bold',
                                        'text-blue-700' => (float) $record->net_item_weight_kg > 0,
                                        'text-amber-700' => (float) $record->net_item_weight_kg <= 0,
                                    ])>Netto</div>
                                    <div class="mt-1 font-mono text-sm font-black text-blue-800">{{ number_format((float) $record->net_item_weight_kg, 3) }}</div>
                                </div>
                            </div>
                        </div>

                        @if ($record->description)
                            <p class="mt-4 text-sm text-slate-600">{{ $record->description }}</p>
                        @endif

                        <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px]">
                            <figure class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                @if ($record->gross_photo_path)
                                    <img src="{{ Storage::disk('public')->url($record->gross_photo_path) }}" alt="Foto brutto" class="aspect-[4/3] w-full object-cover">
                                @endif
                                <figcaption class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">Foto realtime brutto</figcaption>
                            </figure>
                            <form method="POST" action="{{ route('subcounts.records.netto', $record) }}" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                @csrf
                                @method('PUT')
                                <div class="text-sm font-black text-slate-900">Input Netto Web</div>
                                <p class="mt-1 text-xs text-slate-500">Netto harus lebih kecil dari brutto. Kalau lebih besar, barang perlu timbang ulang.</p>
                                <div class="mt-4">
                                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">Netto (kg)</label>
                                    <input type="number" step="0.001" min="0" max="{{ max(0, (float) $record->gross_weight_kg - 0.001) }}" name="net_item_weight_kg" value="{{ old('net_item_weight_kg', (float) $record->net_item_weight_kg > 0 ? (float) $record->net_item_weight_kg : '') }}" class="mt-1 w-full rounded-xl border-slate-300 text-sm font-bold focus:border-indigo-500 focus:ring-indigo-500" required>
                                </div>
                                @error('net_item_weight_kg')
                                    <div class="mt-2 rounded-lg bg-red-50 px-3 py-2 text-xs font-bold text-red-700">{{ $message }}</div>
                                @enderror
                                <button class="mt-4 w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-black text-white hover:bg-slate-800">Simpan Netto</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-400 shadow-sm">
                        Belum ada record timbang packaging.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
