<x-app-layout>
    <x-slot name="header">
        Planning • MPS
    </x-slot>

    <div class="py-6" x-data="planningMps()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @php
                $viewMode = $view ?? 'calendar';
                $weeksCountValue = (int) ($weeksCount ?? 4);
                $weeksValue = $weeks ?? [];
                $searchValue = $q ?? '';
            @endphp

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
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <a
                            href="{{ route('planning.mps.index', array_merge(request()->query(), ['view' => 'calendar'])) }}"
                            class="px-4 py-2 rounded-xl font-semibold border {{ $viewMode === 'calendar' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50' }}"
                        >
                            Calendar View
                        </a>
                        <a
                            href="{{ route('planning.mps.index', array_merge(request()->query(), ['view' => 'list'])) }}"
                            class="px-4 py-2 rounded-xl font-semibold border {{ $viewMode === 'list' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50' }}"
                        >
                            List View
                        </a>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <form method="GET" class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="view" value="{{ $viewMode }}">

                            <input
                                name="minggu"
                                value="{{ $minggu }}"
                                class="rounded-xl border-slate-200"
                                placeholder="2026-W01"
                                title="Minggu (YYYY-WW)"
                            >

                            @if($viewMode === 'calendar')
                                <select name="weeks" class="rounded-xl border-slate-200" title="Weeks">
                                    @foreach([4,6,8,12] as $n)
                                        <option value="{{ $n }}" @selected($weeksCountValue === $n)>{{ $n }} weeks</option>
                                    @endforeach
                                </select>
                            @endif

                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">⌕</span>
                                <input
                                    name="q"
                                    value="{{ $searchValue }}"
                                    class="rounded-xl border-slate-200 pl-10"
                                    placeholder="Search part..."
                                >
                            </div>

                            <button class="px-4 py-2 rounded-xl bg-slate-900 text-white font-semibold">Load</button>
                        </form>

                        @if($viewMode === 'calendar')
                            <form method="POST" action="{{ route('planning.mps.generate-range') }}">
                                @csrf
                                <input type="hidden" name="minggu" value="{{ $minggu }}">
                                <input type="hidden" name="weeks" value="{{ $weeksCountValue }}">
                                <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Generate Range</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('planning.mps.generate') }}">
                                @csrf
                                <input type="hidden" name="minggu" value="{{ $minggu }}">
                                <button class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold">Generate</button>
                            </form>
                            <button
                                type="submit"
                                form="approve-form"
                                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold"
                                onclick="return confirm('Approve selected MPS rows?')"
                            >
                                Approve Selected
                            </button>
                        @endif
                    </div>
                </div>

                @if($viewMode === 'calendar')
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Part GCI</th>
                                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Part Name</th>
                                    @foreach($weeksValue as $w)
                                        @php
                                            preg_match('/^(\d{4})-W(\d{2})$/', $w, $wm);
                                            $label = $wm ? ('W' . $wm[2] . '-' . $wm[1]) : $w;
                                        @endphp
                                        <th class="px-4 py-3 text-center font-semibold whitespace-nowrap">{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse(($parts ?? []) as $p)
                                    @php $byWeek = $p->mps->keyBy('minggu'); @endphp
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3 font-mono text-xs font-semibold whitespace-nowrap">{{ $p->part_no }}</td>
                                        <td class="px-4 py-3 text-slate-700 whitespace-nowrap">{{ $p->part_name }}</td>
                                        @foreach($weeksValue as $w)
                                            @php $cell = $byWeek->get($w); @endphp
                                            <td class="px-4 py-3 text-center">
                                                @if($cell)
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center justify-center min-w-[46px] px-3 py-1 rounded-full text-xs font-semibold {{ $cell->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800 hover:bg-indigo-200' }}"
                                                        @click="openCell(@js(['part_id' => $p->id, 'part_no' => $p->part_no, 'part_name' => $p->part_name, 'minggu' => $w, 'planned_qty' => $cell->planned_qty, 'status' => $cell->status]))"
                                                    >
                                                        {{ number_format((float) $cell->planned_qty, 0) }}
                                                    </button>
                                                @else
                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-full border border-slate-200 text-slate-400 hover:text-slate-600 hover:border-slate-300"
                                                        @click="openCell(@js(['part_id' => $p->id, 'part_no' => $p->part_no, 'part_name' => $p->part_name, 'minggu' => $w, 'planned_qty' => 0, 'status' => 'draft']))"
                                                    >
                                                        +
                                                    </button>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 2 + count($weeksValue) }}" class="px-4 py-8 text-center text-slate-500">No parts found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(($parts ?? null) && method_exists($parts, 'links'))
                        <div class="pt-2">
                            {{ $parts->links() }}
                        </div>
                    @endif
                @else
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <form id="approve-form" method="POST" action="{{ route('planning.mps.approve') }}">
                            @csrf
                            <input type="hidden" name="minggu" value="{{ $minggu }}">
                        </form>

                        <table class="min-w-full text-sm divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr class="text-slate-600 text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left font-semibold">
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" class="rounded border-slate-300" x-model="selectAll" @change="toggleAll()">
                                            <span>Select</span>
                                        </label>
                                    </th>
                                    <th class="px-4 py-3 text-left font-semibold">Part GCI</th>
                                    <th class="px-4 py-3 text-right font-semibold">Forecast Qty</th>
                                    <th class="px-4 py-3 text-right font-semibold">Planned Qty</th>
                                    <th class="px-4 py-3 text-left font-semibold">Status</th>
                                    <th class="px-4 py-3 text-right font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse (($rows ?? []) as $r)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-4 py-3">
                                            @if ($r->status !== 'approved')
                                                <input
                                                    type="checkbox"
                                                    name="mps_ids[]"
                                                    value="{{ $r->id }}"
                                                    form="approve-form"
                                                    class="rounded border-slate-300"
                                                    x-model="selected"
                                                >
                                            @else
                                                <span class="text-xs text-slate-300">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold">{{ $r->part->part_no ?? '-' }}</div>
                                            <div class="text-xs text-slate-500">{{ $r->part->part_name ?? '-' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-xs">{{ number_format((float) $r->forecast_qty, 3) }}</td>
                                        <td class="px-4 py-3 text-right font-mono text-xs font-semibold">{{ number_format((float) $r->planned_qty, 3) }}</td>
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
                @endif
            </div>
        </div>

        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm px-4" x-show="modalOpen" x-cloak @keydown.escape.window="close()">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                    <div class="text-sm font-semibold text-slate-900" x-text="modalTitle"></div>
                    <button type="button" class="w-9 h-9 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">✕</button>
                </div>

                <form :action="formAction" method="POST" class="px-5 py-4 space-y-4">
                    @csrf
                    <template x-if="formMethod === 'PUT'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <template x-if="formMethod === 'POST'">
                        <input type="hidden" name="part_id" :value="form.part_id">
                    </template>

                    <input type="hidden" name="minggu" :value="form.minggu">

                    <div class="text-sm text-slate-700">
                        <div class="font-semibold" x-text="form.partLabel"></div>
                        <div class="text-xs text-slate-500">Minggu: <span x-text="form.minggu"></span></div>
                        <template x-if="form.status === 'approved'">
                            <div class="text-xs text-emerald-700 mt-1">Approved (locked)</div>
                        </template>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-700">Planned Qty</label>
                        <input type="number" step="0.001" min="0" name="planned_qty" class="mt-1 w-full rounded-xl border-slate-200" required x-model="form.planned_qty" :disabled="form.status === 'approved'">
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50" @click="close()">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold" :disabled="form.status === 'approved'">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function planningMps() {
                return {
                    modalOpen: false,
                    formAction: '',
                    formMethod: 'PUT',
                    modalTitle: 'Edit Planned Qty',
                    form: { id: null, part_id: null, planned_qty: '0', partLabel: '', minggu: '', status: 'draft' },
                    selectAll: false,
                    selected: [],
                    toggleAll() {
                        const checkboxes = document.querySelectorAll('input[name=\"mps_ids[]\"]');
                        this.selected = this.selectAll
                            ? Array.from(checkboxes).map((c) => c.value)
                            : [];
                    },
                    openEdit(r) {
                        this.formAction = @js(url('/planning/mps')) + '/' + r.id;
                        this.formMethod = 'PUT';
                        this.modalTitle = 'Edit Planned Qty';
                        const code = r.part?.part_no ?? '-';
                        const name = r.part?.part_name ?? '-';
                        this.form = {
                            id: r.id,
                            part_id: r.part_id,
                            planned_qty: r.planned_qty,
                            partLabel: `${code} — ${name}`,
                            minggu: r.minggu ?? @js($minggu),
                            status: r.status ?? 'draft'
                        };
                        this.modalOpen = true;
                    },
                    openCell(payload) {
                        this.formAction = @js(route('planning.mps.upsert'));
                        this.formMethod = 'POST';
                        this.modalTitle = 'Set Planned Qty';
                        this.form = {
                            id: null,
                            part_id: payload.part_id,
                            planned_qty: payload.planned_qty,
                            partLabel: `${payload.part_no} — ${payload.part_name}`,
                            minggu: payload.minggu,
                            status: payload.status ?? 'draft'
                        };
                        this.modalOpen = true;
                    },
                    close() { this.modalOpen = false; },
                }
            }
        </script>
    </div>
</x-app-layout>
