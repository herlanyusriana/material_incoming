<x-app-layout>
    <x-slot name="header">{{ $woTracking->work_order_no }} · {{ $woTracking->gciPart?->part_no }}</x-slot>

    @php
        $badge = ['PLANNED' => 'bg-slate-100 text-slate-700', 'RELEASED' => 'bg-sky-100 text-sky-700', 'IN_PRODUCTION' => 'bg-amber-100 text-amber-700', 'CLOSED' => 'bg-emerald-100 text-emerald-700', 'CANCELLED' => 'bg-rose-100 text-rose-700'][$woTracking->status] ?? 'bg-slate-100';
        $locked = in_array($woTracking->status, ['CLOSED', 'CANCELLED'], true);
    @endphp

    <div class="py-6 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-5">
        @if (session('success'))<div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
        @if (session('error'))<div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>@endif

        {{-- Header WO --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl font-black text-slate-900">{{ $woTracking->work_order_no }}</h1>
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase {{ $badge }}">{{ $woTracking->status }}</span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">
                        {{ $woTracking->gciPart?->part_no }} — {{ $woTracking->gciPart?->part_name }} ·
                        {{ __('wo_tracking.target') }} <b class="text-slate-800 tabular-nums">{{ number_format((float) $woTracking->qty_target, 0) }} {{ $woTracking->gciPart?->uom }}</b> ·
                        {{ __('wo_tracking.actual') }} <b class="text-slate-800 tabular-nums">{{ number_format((float) $woTracking->qty_actual, 0) }}</b>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if (! $locked)
                        <form action="{{ route('production.wo-tracking.release', $woTracking) }}" method="POST" onsubmit="return confirm('@js(__('wo_tracking.release_confirm'))')">
                            @csrf
                            <button class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold uppercase">{{ __('wo_tracking.release') }}</button>
                        </form>
                        <form action="{{ route('production.wo-tracking.cancel', $woTracking) }}" method="POST" onsubmit="return confirm('@js(__('wo_tracking.cancel_confirm'))')">
                            @csrf
                            <button class="px-4 py-2 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 text-xs font-bold uppercase">{{ __('wo_tracking.cancel') }}</button>
                        </form>
                    @endif
                    @if ($woTracking->status !== 'CLOSED')
                        <form action="{{ route('production.wo-tracking.close', $woTracking) }}" method="POST" onsubmit="return confirm('@js(__('wo_tracking.close_confirm'))')">
                            @csrf
                            <button class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase">{{ __('wo_tracking.close') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Scan & alokasi (Fase B) --}}
        @if (! $locked)
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-widest text-slate-500 mb-3">{{ __('wo_tracking.scan_title') }}</h2>
            <div class="flex flex-wrap gap-2">
                <input id="scanTag" type="text" class="flex-1 min-w-64 rounded-xl border-slate-300 font-mono" placeholder="{{ __('wo_tracking.scan_ph') }}" autofocus autocomplete="off">
                <button id="scanBtn" class="px-5 py-2 rounded-xl bg-slate-900 text-white text-sm font-bold">{{ __('wo_tracking.scan_btn') }}</button>
            </div>
            <div id="scanResult" class="hidden mt-4 rounded-xl border border-indigo-200 bg-indigo-50/50 p-4">
                <div id="scanInfo" class="text-sm text-slate-700"></div>
                <form id="allocForm" method="POST" class="hidden mt-3 flex flex-wrap items-end gap-2">
                    @csrf
                    <input type="hidden" name="tag" id="allocTag">
                    <input type="hidden" name="location_code" id="allocLoc">
                    <div class="flex-1 min-w-40">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('wo_tracking.alloc_qty') }}</label>
                        <input type="number" name="qty" id="allocQty" step="0.0001" min="0" class="w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <button id="allocBtn" class="px-5 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold">{{ __('wo_tracking.alloc_btn') }}</button>
                </form>
                <p id="allocWarn" class="hidden mt-2 text-sm font-semibold text-amber-700"></p>
            </div>
        </div>
        @endif

        {{-- Kebutuhan & saran --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <h2 class="px-5 pt-5 text-sm font-bold uppercase tracking-widest text-slate-500">{{ __('wo_tracking.requirements_title') }}</h2>
            <div class="p-5 grid gap-4 md:grid-cols-2">
                @forelse ($woTracking->requirements as $requirement)
                    @php $s = $suggestions[$requirement->id] ?? null; $allocated = $allocatedByPart[$requirement->gci_part_id] ?? 0; @endphp
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-bold text-slate-800">Substitute material · FIFO</div>
                                <div class="text-xs text-slate-400">Main BOM disembunyikan; kandidat hanya substitute aktif yang punya stok</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-black text-slate-900 tabular-nums">{{ number_format((float) $requirement->required_qty, 2) }} <span class="text-xs text-slate-400">{{ $requirement->uom }}</span></div>
                                <div class="text-[11px] font-bold uppercase {{ $allocated + 0.0001 >= $requirement->required_qty ? 'text-emerald-600' : 'text-amber-600' }}">
                                    {{ __('wo_tracking.allocated') }} {{ number_format($allocated, 2) }}
                                </div>
                            </div>
                        </div>
                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-1">{{ __('wo_tracking.policy') }}: {{ $requirement->consumption_policy }}</div>

                        @if ($s && $s['picks']->isNotEmpty())
                            <div class="mt-3 rounded-lg bg-slate-50 border border-slate-100 p-3 text-xs">
                                <div class="font-bold text-slate-500 uppercase tracking-wider mb-1">{{ __('wo_tracking.suggestion') }}</div>
                                @foreach ($s['picks'] as $pick)
                                    <div class="flex justify-between py-0.5 font-mono">
                                        <span>{{ $pick['part_no'] ?? $pick['gci_part_id'] }} · {{ $pick['tag'] }} <span class="text-slate-400">@ {{ $pick['location_code'] }}</span></span>
                                        <span class="tabular-nums">{{ number_format($pick['qty'], 2) }}{{ $pick['split'] ? ' (' . __('wo_tracking.split') . ')' : '' }}</span>
                                    </div>
                                @endforeach
                                @if ($s['shortfall'] > 0)
                                    <div class="mt-1 text-rose-600 font-bold">{{ __('wo_tracking.shortfall') }}: {{ number_format($s['shortfall'], 2) }} {{ $s['uom'] }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-400 col-span-2">{{ __('wo_tracking.no_requirements') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Alokasi (tracking terkunci) --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <h2 class="px-5 pt-5 text-sm font-bold uppercase tracking-widest text-slate-500">{{ __('wo_tracking.allocations_title') }}</h2>
            <table class="min-w-full divide-y divide-slate-200 text-sm mt-2">
                <thead class="bg-slate-50"><tr>
                    <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_tag') }}</th>
                    <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_location') }}</th>
                    <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_component') }}</th>
                    <th class="px-4 py-2.5 text-right text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_reserved') }}</th>
                    <th class="px-4 py-2.5 text-right text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_consumed') }}</th>
                    <th class="px-4 py-2.5 text-right text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_returned') }}</th>
                    <th class="px-4 py-2.5 text-center text-xs font-bold text-slate-500 uppercase">{{ __('wo_tracking.th_status') }}</th>
                    <th class="px-4 py-2.5"></th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($woTracking->allocations as $a)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-2.5 font-mono text-xs">{{ $a->tag }}</td>
                            <td class="px-4 py-2.5">{{ $a->location_code }}</td>
                            <td class="px-4 py-2.5">{{ $a->gciPart?->part_no }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ number_format((float) $a->qty_reserved, 2) }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums text-rose-600">{{ number_format((float) $a->qty_consumed, 2) }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums text-sky-600">{{ number_format((float) $a->qty_returned, 2) }}</td>
                            <td class="px-4 py-2.5 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ ['RESERVED' => 'bg-amber-100 text-amber-700', 'CONSUMED' => 'bg-rose-100 text-rose-700', 'RETURNED' => 'bg-sky-100 text-sky-700', 'CANCELLED' => 'bg-slate-100'][$a->status] }}">{{ $a->status }}</span></td>
                            <td class="px-4 py-2.5 text-right">
                                @if ($a->status === 'RESERVED' && ! $locked)
                                    <form action="{{ route('production.wo-tracking.deallocate', [$woTracking, $a]) }}" method="POST" onsubmit="return confirm('@js(__('wo_tracking.dealloc_confirm'))')">
                                        @csrf
                                        <button class="text-xs font-bold text-sky-600 hover:underline">{{ __('wo_tracking.return_btn') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">{{ __('wo_tracking.no_allocations') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Input hasil (Fase C) --}}
        @if (! $locked)
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-widest text-slate-500 mb-3">{{ __('wo_tracking.result_title') }}</h2>
            <form action="{{ route('production.wo-tracking.result', $woTracking) }}" method="POST" class="flex flex-wrap items-end gap-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('wo_tracking.qty_good') }}</label>
                    <input type="number" name="qty_good" step="0.0001" min="0" required class="rounded-xl border-slate-300 text-sm w-40">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('wo_tracking.qty_ng') }}</label>
                    <input type="number" name="qty_ng" step="0.0001" min="0" value="0" class="rounded-xl border-slate-300 text-sm w-32">
                </div>
                <div class="flex-1 min-w-48">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">{{ __('wo_tracking.notes') }}</label>
                    <input type="text" name="notes" class="w-full rounded-xl border-slate-300 text-sm">
                </div>
                <button class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold">{{ __('wo_tracking.post_result') }}</button>
            </form>
            <p class="mt-2 text-xs text-slate-400">{{ __('wo_tracking.result_hint') }}</p>
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
        const token = '{{ csrf_token() }}';
        const locateUrl = '{{ route('production.wo-tracking.locate-tag', $woTracking) }}';
        const allocForm = document.getElementById('allocForm');
        const scanInput = document.getElementById('scanTag');
        const resultBox = document.getElementById('scanResult');
        const infoBox = document.getElementById('scanInfo');
        const warnBox = document.getElementById('allocWarn');
        const woOpen = {{ in_array($woTracking->status, ['PLANNED', 'RELEASED', 'IN_PRODUCTION'], true) ? 'true' : 'false' }};

        async function locate(tag) {
            if (!tag) return;
            const fd = new FormData();
            fd.append('tag', tag);
            fd.append('_token', token);
            const res = await fetch(locateUrl, { method: 'POST', body: fd });
            const data = await res.json();
            resultBox.classList.remove('hidden');
            allocForm.classList.add('hidden');
            warnBox.classList.add('hidden');

            if (!data.found) {
                infoBox.innerHTML = '<b class="text-rose-600">' + @js(__('wo_tracking.tag_not_found')) + '</b>';
                return;
            }

            const locs = (data.locations || []).map((l) => `${l.location_code}: ${l.qty_on_hand}`).join(' · ');
            infoBox.innerHTML = `<b>${data.part_no ?? data.tag}</b> — ${data.part_name ?? ''} (${data.uom ?? '-'})<br>
                <span class="text-xs text-slate-500">${locs}</span>
                ${(data.reserved_by || []).map((r) => `<div class="mt-1 text-xs font-bold text-amber-700">{{ __('wo_tracking.reserved_by') }} ${r.work_order_no} (${r.qty_reserved})</div>`).join('')}`;

            if (!data.requirement_id) {
                warnBox.textContent = @js(__('wo_tracking.not_for_this_wo'));
                warnBox.classList.remove('hidden');
                return;
            }

            const sug = data.suggestion;
            const first = (sug?.picks || [])[0];
            allocForm.dataset.action = '{{ url("production/wo-tracking") }}/{{ $woTracking->id }}/requirements/' + data.requirement_id + '/allocate';
            allocForm.setAttribute('action', allocForm.dataset.action);
            document.getElementById('allocTag').value = data.tag;
            document.getElementById('allocLoc').value = first ? (first.location_code || '') : '';
            const suggestQty = first ? first.qty : 0;
            document.getElementById('allocQty').value = suggestQty > 0 ? suggestQty : '';
            if (!woOpen) {
                warnBox.textContent = @js(__('wo_tracking.wo_locked'));
                warnBox.classList.remove('hidden');
            } else {
                allocForm.classList.remove('hidden');
            }
        }

        document.getElementById('scanBtn').addEventListener('click', () => locate(scanInput.value.trim()));
        scanInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); locate(scanInput.value.trim()); scanInput.select(); } });
    </script>
    @endpush
</x-app-layout>
