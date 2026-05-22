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
                                <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Packaging ID</div>
                                <h2 class="mt-1 text-xl font-black text-slate-900">{{ $record->packaging_id }}</h2>
                                <div class="mt-1 text-sm font-semibold text-slate-500">{{ $record->packaging_type ?? '-' }}</div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                <div class="rounded-xl bg-slate-50 px-3 py-2">
                                    <div class="font-bold text-slate-500">Packaging</div>
                                    <div class="mt-1 font-mono text-sm font-black text-slate-900">{{ number_format((float) $record->packaging_weight_kg, 3) }}</div>
                                </div>
                                <div class="rounded-xl bg-slate-50 px-3 py-2">
                                    <div class="font-bold text-slate-500">Gross</div>
                                    <div class="mt-1 font-mono text-sm font-black text-slate-900">{{ number_format((float) $record->gross_weight_kg, 3) }}</div>
                                </div>
                                <div class="rounded-xl bg-blue-50 px-3 py-2">
                                    <div class="font-bold text-blue-700">Net</div>
                                    <div class="mt-1 font-mono text-sm font-black text-blue-800">{{ number_format((float) $record->net_item_weight_kg, 3) }}</div>
                                </div>
                            </div>
                        </div>

                        @if ($record->description)
                            <p class="mt-4 text-sm text-slate-600">{{ $record->description }}</p>
                        @endif

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <figure class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                @if ($record->packaging_photo_path)
                                    <img src="{{ Storage::disk('public')->url($record->packaging_photo_path) }}" alt="Packaging kosong" class="aspect-[4/3] w-full object-cover">
                                @endif
                                <figcaption class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">Foto realtime packaging kosong</figcaption>
                            </figure>
                            <figure class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                @if ($record->gross_photo_path)
                                    <img src="{{ Storage::disk('public')->url($record->gross_photo_path) }}" alt="Barang dan packaging" class="aspect-[4/3] w-full object-cover">
                                @endif
                                <figcaption class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">Foto realtime barang + packaging</figcaption>
                            </figure>
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
