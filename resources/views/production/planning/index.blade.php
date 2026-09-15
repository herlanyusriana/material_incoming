@extends('layouts.app')

@section('title', 'Production Plan')

@section('content')
    <div x-data="productionPlanBoard({
            startDate: @js($planDate->toDateString()),
            days: {{ $planningDays }},
            rows: @js($boardRows->map(fn ($row) => [
                'partId' => $row['part']?->id,
                'partNo' => $row['part']?->part_no,
                'partName' => $row['part']?->part_name,
                'machine' => $row['machine_name'],
                'wipNo' => $row['wip_part_no'],
                'wipName' => $row['wip_part_name'],
                'orders' => $row['orders'],
            ])->values())
        })" @keydown.escape.window="closeDialogs()"
        class="min-h-[calc(100vh-5rem)] w-full bg-slate-50 px-3 py-4 sm:px-5 lg:px-7 lg:py-6">
        <div class="mx-auto w-full max-w-[1920px]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <header class="border-b border-slate-200 px-4 py-4 sm:px-6">
                    <div class="grid gap-4 xl:grid-cols-[minmax(240px,0.8fr)_minmax(320px,1.4fr)_auto] xl:items-center">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-600">Production</p>
                            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Production Plan</h1>
                            <p class="mt-1 text-sm text-slate-500">Susun prioritas dan target produksi per hari kalender.</p>
                        </div>

                        <label class="relative block">
                            <span class="sr-only">Cari mesin, FG, atau WIP</span>
                            <svg aria-hidden="true" class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.4a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" />
                            </svg>
                            <input type="search" x-model.debounce.150ms="search" data-no-uppercase
                                class="h-11 w-full rounded-xl border-slate-300 bg-white pl-11 pr-4 text-sm text-slate-800 shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                                placeholder="Cari mesin, FG, atau WIP...">
                        </label>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-end">
                            <form action="{{ route('production.planning.index') }}" method="GET" class="grid grid-cols-[minmax(150px,1fr)_110px] gap-2 sm:flex">
                                <label class="block">
                                    <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Start Date</span>
                                    <input type="date" name="date" value="{{ $planDate->toDateString() }}" onchange="this.form.submit()"
                                        class="h-11 w-full rounded-xl border-slate-300 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Day Plan</span>
                                    <select name="days" onchange="this.form.submit()" class="h-11 w-full rounded-xl border-slate-300 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                        @foreach([1, 2, 3] as $dayCount)
                                            <option value="{{ $dayCount }}" @selected($planningDays === $dayCount)>{{ $dayCount }} Hari</option>
                                        @endforeach
                                    </select>
                                </label>
                            </form>

                            <button type="button" @click="openAddPart()"
                                class="inline-flex h-11 min-w-max items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2 active:bg-blue-800">
                                <svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg>
                                WO Baru
                            </button>
                        </div>
                    </div>
                </header>

                <div class="hidden lg:block">
                    <table class="w-full table-fixed border-collapse text-left text-sm">
                        <caption class="sr-only">Production plan dari {{ $planDate->format('d M Y') }} selama {{ $planningDays }} hari</caption>
                        <colgroup>
                            <col class="w-[12%]"><col class="w-[6%]"><col class="w-[20%]"><col class="w-[19%]"><col class="w-[8%]">
                            @foreach($boardDates as $date)<col class="w-[8%]">@endforeach
                            <col class="w-[11%]">
                        </colgroup>
                        <thead class="border-b border-slate-200 bg-slate-50/90 text-xs font-semibold uppercase tracking-wide text-slate-600">
                            <tr>
                                <th scope="col" class="px-4 py-3">Nama Mesin</th>
                                <th scope="col" class="px-2 py-3 text-center">Urutan</th>
                                <th scope="col" class="px-3 py-3">FG Part</th>
                                <th scope="col" class="px-3 py-3">WIP Part</th>
                                <th scope="col" class="px-2 py-3 text-right">Avail WO</th>
                                @foreach($boardDates as $date)
                                    <th scope="col" class="px-2 py-3 text-center normal-case tracking-normal">
                                        <span class="block text-xs font-bold text-slate-800">{{ $loop->first ? 'D' : 'D+' . $loop->index }}</span>
                                        <span class="mt-0.5 block text-[10px] font-medium text-slate-500">{{ $date->format('d M Y') }}</span>
                                    </th>
                                @endforeach
                                <th scope="col" class="px-3 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($boardRows as $row)
                                @php($part = $row['part'])
                                <tr x-show="matchesRow({{ $loop->index }})" class="group hover:bg-blue-50/35">
                                    <td class="px-4 py-3 align-middle">
                                        <p class="break-words font-semibold leading-5 text-slate-800">{{ $row['machine_name'] }}</p>
                                        @if($row['process_name'])<p class="mt-0.5 truncate text-xs text-slate-500" title="{{ $row['process_name'] }}">{{ $row['process_name'] }}</p>@endif
                                    </td>
                                    <td class="px-2 py-3 text-center align-middle"><span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2 font-mono text-sm font-bold tabular-nums text-slate-700">{{ $loop->iteration }}</span></td>
                                    <td class="px-3 py-3 align-middle">
                                        <p class="break-words font-semibold leading-5 text-slate-900">{{ $part?->part_name ?: '-' }}</p>
                                        <p class="mt-0.5 break-all font-mono text-xs font-semibold text-blue-700">{{ $part?->part_no ?: '-' }}</p>
                                        @if($part?->model)<p class="mt-0.5 truncate text-xs text-slate-500">{{ $part->model }}</p>@endif
                                    </td>
                                    <td class="px-3 py-3 align-middle"><p class="break-words font-medium leading-5 text-slate-700">{{ $row['wip_part_name'] }}</p><p class="mt-0.5 break-all font-mono text-xs text-slate-500">{{ $row['wip_part_no'] }}</p></td>
                                    <td class="px-2 py-3 text-right align-middle font-mono font-semibold tabular-nums text-slate-700">{{ number_format($row['available_wo_qty'], 0) }}</td>
                                    @foreach($boardDates as $date)
                                        @php($dateKey = $date->toDateString())
                                        <td class="px-1.5 py-3 align-middle">
                                            <label class="block">
                                                <span class="sr-only">Target {{ $part?->part_no }} tanggal {{ $date->format('d M Y') }}</span>
                                                <input type="number" min="0" step="1" value="{{ $row['daily_quantities'][$dateKey] > 0 ? (float) $row['daily_quantities'][$dateKey] : '' }}"
                                                    @change="saveDaily($event, {{ $part?->id }}, '{{ $dateKey }}')" @keydown.enter.prevent="$event.target.blur()"
                                                    :aria-invalid="saveState['{{ $part?->id }}:{{ $dateKey }}'] === 'error'"
                                                    class="h-10 w-full min-w-0 rounded-lg border-slate-300 bg-white px-2 text-right font-mono text-sm font-semibold tabular-nums text-slate-800 shadow-sm placeholder:text-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" placeholder="0">
                                                <span class="mt-1 block min-h-3 text-center text-[10px]" :class="saveState['{{ $part?->id }}:{{ $dateKey }}'] === 'error' ? 'text-red-600' : 'text-emerald-600'" x-text="saveLabel('{{ $part?->id }}:{{ $dateKey }}')"></span>
                                            </label>
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-3 align-middle">
                                        <div class="flex items-center justify-center gap-1">
                                            <div class="flex flex-col">
                                                <button type="button" @click="movePart({{ $part?->id }}, 'up')" aria-label="Naikkan urutan {{ $part?->part_no }}" title="Naikkan urutan" class="inline-flex h-7 w-8 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"><svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 15 6-6 6 6" /></svg></button>
                                                <button type="button" @click="movePart({{ $part?->id }}, 'down')" aria-label="Turunkan urutan {{ $part?->part_no }}" title="Turunkan urutan" class="inline-flex h-7 w-8 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"><svg aria-hidden="true" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m18 9-6 6-6-6" /></svg></button>
                                            </div>
                                            <button type="button" @click="openOrders({{ $loop->index }})" aria-label="Lihat detail WO {{ $part?->part_no }}" title="Lihat detail WO" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-blue-100 hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"><svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" /><circle cx="12" cy="12" r="2.25" /></svg></button>
                                            <button type="button" @click="generateWo({{ $part?->id }}, @js($part?->part_no))" aria-label="Buat WO {{ $part?->part_no }}" title="Buat WO dari target harian" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-blue-700 transition hover:bg-blue-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"><svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg></button>
                                            <button type="button" @click="removePart({{ $part?->id }}, @js($part?->part_no))" aria-label="Hapus {{ $part?->part_no }} dari plan" title="Hapus dari plan" class="ml-1 inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-400 transition hover:bg-red-50 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600"><svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9 .5 9m3.5-9-.5 9M4.5 6.75h15m-9-3h3a1.5 1.5 0 0 1 1.5 1.5v1.5h-6v-1.5a1.5 1.5 0 0 1 1.5-1.5Zm-4.5 3 .75 12A1.5 1.5 0 0 0 7.75 21h8.5a1.5 1.5 0 0 0 1.5-1.5l.75-12" /></svg></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ 6 + $planningDays }}" class="px-6 py-16 text-center"><p class="font-semibold text-slate-700">Belum ada part dalam production plan.</p><p class="mt-1 text-sm text-slate-500">Klik “WO Baru” untuk memilih FG dari Part Master.</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-slate-200 lg:hidden">
                    @forelse($boardRows as $row)
                        @php($part = $row['part'])
                        <article x-show="matchesRow({{ $loop->index }})" class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0"><p class="text-xs font-semibold uppercase tracking-wide text-blue-600">{{ $row['machine_name'] }}</p><h2 class="mt-1 break-words font-semibold text-slate-900">{{ $part?->part_name ?: '-' }}</h2><p class="mt-0.5 break-all font-mono text-xs font-semibold text-slate-600">{{ $part?->part_no }}</p></div>
                                <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-slate-100 px-2 font-mono text-sm font-bold text-slate-700">{{ $loop->iteration }}</span>
                            </div>
                            <div class="mt-3 rounded-xl bg-slate-50 p-3 text-sm"><p class="font-medium text-slate-700">{{ $row['wip_part_name'] }}</p><p class="mt-0.5 break-all font-mono text-xs text-slate-500">{{ $row['wip_part_no'] }}</p><p class="mt-2 text-xs text-slate-500">Avail WO <span class="font-mono font-bold text-slate-800">{{ number_format($row['available_wo_qty'], 0) }}</span></p></div>
                            <div class="mt-3 grid gap-2" style="grid-template-columns: repeat({{ $planningDays }}, minmax(0, 1fr));">
                                @foreach($boardDates as $date)
                                    @php($dateKey = $date->toDateString())
                                    <label><span class="mb-1 block text-center text-xs font-bold text-slate-700">{{ $loop->first ? 'D' : 'D+' . $loop->index }}</span><span class="mb-1 block text-center text-[10px] text-slate-500">{{ $date->format('d M') }}</span><input type="number" min="0" step="1" value="{{ $row['daily_quantities'][$dateKey] > 0 ? (float) $row['daily_quantities'][$dateKey] : '' }}" @change="saveDaily($event, {{ $part?->id }}, '{{ $dateKey }}')" @keydown.enter.prevent="$event.target.blur()" class="h-11 w-full rounded-lg border-slate-300 px-2 text-right font-mono font-semibold shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" placeholder="0"></label>
                                @endforeach
                            </div>
                            <div class="mt-4 flex items-center justify-between gap-2 border-t border-slate-100 pt-3">
                                <div class="flex gap-1"><button type="button" @click="movePart({{ $part?->id }}, 'up')" aria-label="Naikkan urutan" class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-slate-200 text-slate-600"><svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 15 6-6 6 6" /></svg></button><button type="button" @click="movePart({{ $part?->id }}, 'down')" aria-label="Turunkan urutan" class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-slate-200 text-slate-600"><svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m18 9-6 6-6-6" /></svg></button></div>
                                <div class="flex gap-1"><button type="button" @click="openOrders({{ $loop->index }})" aria-label="Lihat detail WO" class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-slate-200 text-slate-600"><svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" /><circle cx="12" cy="12" r="2.25" /></svg></button><button type="button" @click="generateWo({{ $part?->id }}, @js($part?->part_no))" aria-label="Buat WO" class="inline-flex h-11 w-11 items-center justify-center rounded-lg bg-blue-600 text-white"><svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg></button><button type="button" @click="removePart({{ $part?->id }}, @js($part?->part_no))" aria-label="Hapus dari plan" class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-red-200 text-red-600"><svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9 .5 9m3.5-9-.5 9M4.5 6.75h15m-9-3h3a1.5 1.5 0 0 1 1.5 1.5v1.5h-6v-1.5a1.5 1.5 0 0 1 1.5-1.5Zm-4.5 3 .75 12A1.5 1.5 0 0 0 7.75 21h8.5a1.5 1.5 0 0 0 1.5-1.5l.75-12" /></svg></button></div>
                            </div>
                        </article>
                    @empty
                        <div class="px-6 py-14 text-center"><p class="font-semibold text-slate-700">Belum ada part dalam production plan.</p></div>
                    @endforelse
                </div>

                <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 bg-slate-50/70 px-4 py-3 text-xs text-slate-500 sm:px-6"><span>{{ $boardRows->count() }} part · {{ $planningDays }} hari kalender</span><span>Input tersimpan saat keluar dari kolom atau menekan Enter.</span></footer>
            </section>
        </div>

        <div x-show="feedback.message" x-transition.opacity role="status" aria-live="polite" class="fixed right-4 top-20 z-[70] max-w-sm rounded-xl border px-4 py-3 text-sm font-medium shadow-lg" :class="feedback.type === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'" x-text="feedback.message"></div>

        <div x-show="showAddPartModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="add-plan-title">
            <div @click.outside="showAddPartModal = false" class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-200 px-5 py-4"><div><h2 id="add-plan-title" class="text-lg font-bold text-slate-900">Tambah FG ke Plan</h2><p class="mt-1 text-sm text-slate-500">Pilih Part Master, lalu isi target D sampai D+2.</p></div><button type="button" @click="showAddPartModal = false" aria-label="Tutup dialog" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"><svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg></button></div>
                <div class="p-5">
                    <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Cari FG</span><input x-ref="partSearch" type="search" x-model="partSearch" @input.debounce.250ms="searchParts()" data-no-uppercase class="h-11 w-full rounded-xl border-slate-300 px-3 text-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20" placeholder="Nomor part, nama, atau model..."></label>
                    <div class="mt-3 max-h-80 overflow-y-auto rounded-xl border border-slate-200"><template x-if="partSearch.length >= 2 && partResults.length === 0 && !partLoading"><p class="px-4 py-8 text-center text-sm text-slate-500">FG tidak ditemukan.</p></template><template x-if="partLoading"><p class="px-4 py-8 text-center text-sm text-slate-500">Mencari Part Master...</p></template><template x-for="part in partResults" :key="part.id"><button type="button" @click="selectPart(part)" class="flex w-full items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 text-left transition last:border-0 hover:bg-blue-50 focus:outline-none focus-visible:bg-blue-50"><span class="min-w-0"><span class="block break-words text-sm font-semibold text-slate-900" x-text="part.part_name || part.part_no"></span><span class="mt-0.5 block break-all font-mono text-xs text-slate-500" x-text="part.part_no"></span></span><svg aria-hidden="true" class="h-5 w-5 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M12 5v14M5 12h14" /></svg></button></template></div>
                </div>
            </div>
        </div>

        <div x-show="showOrdersModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="wo-detail-title">
            <div @click.outside="showOrdersModal = false" class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-200 px-5 py-4"><div><h2 id="wo-detail-title" class="text-lg font-bold text-slate-900">Detail Work Order</h2><p class="mt-1 text-sm text-slate-500" x-text="selectedRow ? selectedRow.partNo + ' · ' + (selectedRow.partName || '') : ''"></p></div><button type="button" @click="showOrdersModal = false" aria-label="Tutup detail WO" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100"><svg aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg></button></div>
                <div class="max-h-[65vh] overflow-y-auto p-5"><template x-if="selectedRow && selectedRow.orders.length === 0"><div class="rounded-xl bg-slate-50 px-4 py-8 text-center"><p class="font-semibold text-slate-700">Belum ada WO.</p><p class="mt-1 text-sm text-slate-500">Isi target harian lalu gunakan tombol tambah pada baris.</p></div></template><div class="space-y-2"><template x-for="order in (selectedRow ? selectedRow.orders : [])" :key="order.id"><a :href="order.url" class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 px-4 py-3 transition hover:border-blue-300 hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"><span><span class="block font-mono text-sm font-bold text-blue-700" x-text="order.number"></span><span class="mt-0.5 block text-xs text-slate-500" x-text="order.plan_date + ' · ' + order.status"></span></span><span class="font-mono text-sm font-bold tabular-nums text-slate-800" x-text="formatNumber(order.qty)"></span></a></template></div></div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function productionPlanBoard(config) {
                return {
                    ...config, search: '', saveState: {}, pendingSaves: new Map(),
                    feedback: { message: '', type: 'success' }, feedbackTimer: null,
                    showAddPartModal: false, showOrdersModal: false, selectedRow: null,
                    partSearch: '', partResults: [], partLoading: false,
                    matchesRow(index) { const q = this.search.trim().toLowerCase(); if (!q) return true; const row = this.rows[index] || {}; return [row.machine, row.partNo, row.partName, row.wipNo, row.wipName].filter(Boolean).join(' ').toLowerCase().includes(q); },
                    saveLabel(key) { return { saving: 'Menyimpan…', saved: 'Tersimpan', error: 'Gagal' }[this.saveState[key]] || ''; },
                    async saveDaily(event, partId, planDate) {
                        const input = event.target, key = `${partId}:${planDate}`, qty = input.value === '' ? 0 : Number(input.value);
                        if (!Number.isFinite(qty) || qty < 0) { this.saveState[key] = 'error'; this.notify('Jumlah harus nol atau lebih.', 'error'); return; }
                        this.saveState[key] = 'saving';
                        const request = fetch(@js(route('production.planning.day-quantity')), { method: 'PUT', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) }, body: JSON.stringify({ gci_part_id: partId, plan_date: planDate, qty }) })
                            .then(async response => { if (!response.ok) throw new Error((await response.json()).message || 'Gagal menyimpan target.'); this.saveState[key] = 'saved'; window.setTimeout(() => { if (this.saveState[key] === 'saved') this.saveState[key] = ''; }, 1800); })
                            .catch(error => { this.saveState[key] = 'error'; this.notify(error.message, 'error'); throw error; }).finally(() => this.pendingSaves.delete(key));
                        this.pendingSaves.set(key, request); return request;
                    },
                    async movePart(partId, direction) { try { await this.waitForSaves(); const response = await fetch(@js(route('production.planning.window-move')), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) }, body: JSON.stringify({ gci_part_id: partId, start_date: this.startDate, days: this.days, direction }) }); if (!response.ok) throw new Error('Urutan gagal diperbarui.'); window.location.reload(); } catch (error) { this.notify(error.message, 'error'); } },
                    async removePart(partId, partNo) { if (!window.confirm(`Hapus ${partNo} dari plan ${this.days} hari ini? WO yang sudah dibuat tetap tersimpan.`)) return; try { await this.waitForSaves(); const response = await fetch(@js(route('production.planning.window-delete')), { method: 'DELETE', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) }, body: JSON.stringify({ gci_part_id: partId, start_date: this.startDate, days: this.days }) }); if (!response.ok) throw new Error('Part gagal dihapus dari plan.'); window.location.reload(); } catch (error) { this.notify(error.message, 'error'); } },
                    async generateWo(partId, partNo) { try { await this.waitForSaves(); } catch (_) { return; } if (!window.confirm(`Buat WO ${partNo} dari seluruh target harian yang belum dibuat?`)) return; const form = document.createElement('form'); form.method = 'POST'; form.action = @js(route('production.planning.window-wo')); const values = { _token: @js(csrf_token()), gci_part_id: partId, start_date: this.startDate, days: this.days }; Object.entries(values).forEach(([name, value]) => { const input = document.createElement('input'); input.type = 'hidden'; input.name = name; input.value = value; form.appendChild(input); }); document.body.appendChild(form); form.submit(); },
                    openOrders(index) { this.selectedRow = this.rows[index]; this.showOrdersModal = true; },
                    openAddPart() { this.showAddPartModal = true; this.$nextTick(() => this.$refs.partSearch?.focus()); },
                    closeDialogs() { this.showAddPartModal = false; this.showOrdersModal = false; },
                    async searchParts() { if (this.partSearch.trim().length < 2) { this.partResults = []; return; } this.partLoading = true; try { const url = new URL(@js(route('gci-parts.search')), window.location.origin); url.searchParams.set('q', this.partSearch.trim()); url.searchParams.set('classification', 'FG'); const response = await fetch(url, { headers: { 'Accept': 'application/json' } }); if (!response.ok) throw new Error('Part Master gagal dimuat.'); const data = await response.json(); this.partResults = (data.data || data || []).filter(part => !part.classification || part.classification === 'FG'); } catch (error) { this.partResults = []; this.notify(error.message, 'error'); } finally { this.partLoading = false; } },
                    async selectPart(part) { try { const response = await fetch(@js(route('production.planning.day-quantity')), { method: 'PUT', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) }, body: JSON.stringify({ gci_part_id: part.id, plan_date: this.startDate, qty: 0 }) }); if (!response.ok) throw new Error('Part gagal ditambahkan ke plan.'); window.location.reload(); } catch (error) { this.notify(error.message, 'error'); } },
                    async waitForSaves() { await Promise.all([...this.pendingSaves.values()]); },
                    formatNumber(value) { return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(value || 0)); },
                    notify(message, type = 'success') { this.feedback = { message, type }; window.clearTimeout(this.feedbackTimer); this.feedbackTimer = window.setTimeout(() => this.feedback.message = '', 4000); },
                };
            }
        </script>
    @endpush
@endsection
