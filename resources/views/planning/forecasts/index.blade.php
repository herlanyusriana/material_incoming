<x-app-layout>
    <x-slot name="header">
        Planning • Forecast (Part GCI)
    </x-slot>

    <x-page-header
        title="Forecast — Part GCI"
        subtitle="Perencanaan demand per part GCI per periode. Update via Upload PLAN atau komit dari preview."
        :breadcrumbs="[
            ['label' => 'Planning', 'url' => null],
            ['label' => 'Forecast']
        ]"
    >
        <x-slot name="actions">
            <form method="POST" action="{{ route('planning.forecasts.preview-plan') }}"
                enctype="multipart/form-data" id="upload-plan-form" class="flex items-center gap-2">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                    onchange="this.form.submit()" class="hidden" id="plan-file-input">
                <label for="plan-file-input"
                    class="cursor-pointer px-4 py-2 rounded-xl font-semibold bg-slate-900 text-white hover:bg-slate-800 inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    <span>Upload PLAN (Excel)</span>
                </label>
            </form>

            <a href="{{ route('planning.forecasts.history') }}"
                class="px-4 py-2 rounded-xl font-semibold border bg-white border-slate-200 text-slate-700 hover:bg-slate-50 inline-flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span>History</span>
            </a>

            <form method="POST" action="{{ route('planning.forecasts.clear') }}"
                onsubmit="return confirm('Are you sure you want to clear ALL Forecast data? This cannot be undone!');"
                class="inline">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-4 py-2 rounded-xl font-semibold border bg-red-600 border-red-600 text-white hover:bg-red-700 inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                    <span>Clear All</span>
                </button>
            </form>
        </x-slot>
    </x-page-header>

    <div class="space-y-6">
        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <div class="font-bold">Cek lagi inputnya:</div>
                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $familyColors = [
                'Comp Base' => 'bg-indigo-100 text-indigo-700',
                'Back Plate' => 'bg-sky-100 text-sky-700',
                'Reinforce' => 'bg-emerald-100 text-emerald-700',
                'Tray Drip' => 'bg-amber-100 text-amber-700',
                'Small Part' => 'bg-slate-100 text-slate-600',
                'NON LG' => 'bg-rose-100 text-rose-700',
            ];
        @endphp

        {{-- KPI cards --}}
        <div class="grid gap-4 sm:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Total Forecast</div>
                <div class="mt-2 text-3xl font-black text-slate-900">{{ number_format((int) ($kpi->total_rows ?? 0)) }}</div>
                <div class="text-xs text-slate-400 mt-1">Baris part-periode</div>
            </div>
            <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wider text-indigo-700 font-semibold">Planning Qty</div>
                <div class="mt-2 text-3xl font-black text-indigo-900">{{ formatNumber((float) ($kpi->total_planning ?? 0)) }}</div>
                <div class="text-xs text-indigo-600/70 mt-1">Total planning</div>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wider text-amber-700 font-semibold">Open PO Qty</div>
                <div class="mt-2 text-3xl font-black text-amber-900">{{ formatNumber((float) ($kpi->total_po ?? 0)) }}</div>
                <div class="text-xs text-amber-600/70 mt-1">Open purchase order</div>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <div class="text-xs uppercase tracking-wider text-emerald-700 font-semibold">Forecast Qty</div>
                <div class="mt-2 text-3xl font-black text-emerald-900">{{ formatNumber((float) ($kpi->total_qty ?? 0)) }}</div>
                <div class="text-xs text-emerald-600/70 mt-1">Max(planning, PO)</div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Period (YYYY-MM)</label>
                    <input name="period" value="{{ $period }}"
                        class="mt-1 rounded-xl border-slate-200 @error('period') border-red-500 @enderror"
                        placeholder="All periods">
                    @error('period')
                        <div class="text-[10px] text-red-500 mt-1 font-semibold">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Part GCI</label>
                    <select name="part_id" class="mt-1 rounded-xl border-slate-200">
                        <option value="">All</option>
                        @foreach ($parts as $p)
                            <option value="{{ $p->id }}" @selected((string) $partId === (string) $p->id)>
                                {{ $p->part_no }} — {{ $p->part_name ?? '-' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Family</label>
                    <select name="family" class="mt-1 rounded-xl border-slate-200">
                        <option value="">All</option>
                        @foreach ($families as $f)
                            <option value="{{ $f }}" @selected($family === $f)>{{ $f }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Filter</button>
                    @if ($period !== '' || $partId !== '' || $family !== '')
                        <a href="{{ route('planning.forecasts.index') }}"
                            class="px-4 py-2 rounded-xl border bg-white border-slate-200 text-slate-700 hover:bg-slate-50">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-slate-600 text-xs uppercase tracking-wider">
                            <th class="px-4 py-3 text-left font-semibold">Period</th>
                            <th class="px-4 py-3 text-left font-semibold">Part GCI</th>
                            <th class="px-4 py-3 text-left font-semibold">Family</th>
                            <th class="px-4 py-3 text-right font-semibold">Planning Qty</th>
                            <th class="px-4 py-3 text-right font-semibold">Open PO Qty</th>
                            <th class="px-4 py-3 text-right font-semibold">Forecast Qty</th>
                            <th class="px-4 py-3 text-left font-semibold">Source</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($forecasts as $f)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 font-mono text-xs">{{ $f->period }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold">{{ $f->part->part_no ?? '-' }}</div>
                                    <div class="text-xs text-slate-500">{{ $f->part->part_name ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $familyColors[$f->family] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $f->family }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-xs">{{ formatNumber($f->planning_qty) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-xs">{{ formatNumber($f->po_qty) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-xs font-semibold">{{ formatNumber($f->qty) }}</td>
                                <td class="px-4 py-3 text-xs uppercase tracking-wide text-slate-600">{{ $f->source }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7m16 0v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5m16 0h-2.586a1 1 0 0 0-.707.293l-2.414 2.414a1 1 0 0 1-.707.293h-3.172a1 1 0 0 1-.707-.293l-2.414-2.414A1 1 0 0 0 6.586 13H4" />
                                        </svg>
                                        <span class="text-sm font-medium">Tidak ada forecast</span>
                                        <span class="text-xs">Upload PLAN atau ubah filter untuk melihat data.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination bar --}}
            <div class="border-t border-slate-200 px-4 py-3 bg-slate-50/50 flex flex-wrap items-center justify-between gap-2">
                <div class="text-xs text-slate-500">
                    Menampilkan <span class="font-semibold text-slate-700">{{ $forecasts->firstItem() ?? 0 }}</span> -
                    <span class="font-semibold text-slate-700">{{ $forecasts->lastItem() ?? 0 }}</span>
                    dari <span class="font-semibold text-slate-700">{{ $forecasts->total() }}</span> baris
                </div>
                <div class="flex gap-1">
                    {{ $forecasts->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
