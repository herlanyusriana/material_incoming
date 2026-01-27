@extends('outgoing.layout')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900">Delivery Plan</h1>
                    <p class="mt-1 text-sm text-slate-600">Sheet view (by delivery class + sequence).</p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-end">
                    <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50" onclick="toggleCustomize()">
                        Customize
                    </button>
                    <form method="GET" action="{{ route('outgoing.delivery-plan') }}" class="flex items-end gap-2">
                    <div>
                        <div class="text-xs font-semibold text-slate-500 mb-1">Date</div>
                        <input type="date" name="date" value="{{ $selectedDate }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">
                    </div>
                    <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">View</button>
                    </form>
                </div>
            </div>
        </div>

        <div id="customizePanel" class="hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="text-lg font-black text-slate-900">Customize</div>
                    <div class="text-sm text-slate-600">Tampilan ini disimpan di browser (local).</div>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input id="optShowStock" type="checkbox" class="rounded border-slate-300">
                        Show Stock@Customer
                    </label>
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input id="optShowJig" type="checkbox" class="rounded border-slate-300">
                        Show JIG
                    </label>
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <span>Max Seq</span>
                        <input id="optMaxSeq" type="number" min="1" max="{{ count($sequences) }}" class="w-20 rounded-lg border-slate-300 text-sm text-right">
                    </label>
                    <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white hover:bg-slate-800" onclick="applyCustomize()">
                        Apply
                    </button>
                    <button type="button" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50" onclick="resetCustomize()">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm font-bold text-slate-900">Trips (Assign Truck/Driver)</div>
                <form method="POST" action="{{ route('outgoing.delivery-plan.store') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="plan_date" value="{{ $selectedDate }}">
                    <button class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white hover:bg-indigo-700">
                        + Create Trip
                    </button>
                </form>
            </div>
            <div class="overflow-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-white border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider w-16">Seq</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Truck</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Driver</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider w-24">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse(($plans ?? []) as $p)
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-3 font-black text-slate-900">#{{ $p->sequence }}</td>
                                <td class="px-6 py-3 text-slate-700">
                                    {{ $p->truck?->plate_no ?? '-' }}
                                    @if($p->truck?->type)
                                        <span class="text-xs text-slate-500">• {{ $p->truck->type }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-slate-700">
                                    {{ $p->driver?->name ?? '-' }}
                                    @if($p->driver?->phone)
                                        <span class="text-xs text-slate-500">• {{ $p->driver->phone }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <button type="button" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50" onclick="openAssign({{ $p->id }})">
                                        Assign
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-500 italic">No trips for this date.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 text-xs text-slate-600">
                <div class="font-semibold text-slate-700">JIG Calculation</div>
                <div>NR1 = JIG × 10, NR2 = JIG × 9 (jig count = ceil(Balance / ratio)).</div>
            </div>
            <div class="overflow-auto">
                <table class="min-w-max w-full text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th rowspan="2" class="px-2 py-3 text-left font-bold text-slate-700 border-r border-slate-200 w-10">No</th>
                            <th rowspan="2" class="px-2 py-3 text-left font-bold text-slate-700 border-r border-slate-200">Classification</th>
                            <th rowspan="2" class="px-2 py-3 text-left font-bold text-slate-700 border-r border-slate-200">Part Name</th>
                            <th rowspan="2" class="px-2 py-3 text-left font-bold text-slate-700 border-r border-slate-200">Part Number</th>
                            <th rowspan="2" class="px-2 py-3 text-left font-bold text-slate-700 border-r border-slate-200">LINE</th>
                            <th rowspan="2" class="px-2 py-3 text-right font-bold text-slate-700 border-r border-slate-200 w-20">Plan</th>
                            <th rowspan="2" class="px-2 py-3 text-right font-bold text-slate-700 border-r border-slate-200 w-28 col-stock">Stock at Customer</th>
                            <th rowspan="2" class="px-2 py-3 text-right font-bold text-slate-700 border-r border-slate-200 w-20">Balance</th>
                            <th rowspan="2" class="px-2 py-3 text-right font-bold text-slate-700 border-r border-slate-200 w-16 col-jig">JIG NR1</th>
                            <th rowspan="2" class="px-2 py-3 text-right font-bold text-slate-700 border-r border-slate-200 w-16 col-jig">JIG NR2</th>
                            <th rowspan="2" class="px-2 py-3 text-left font-bold text-slate-700 border-r border-slate-200 w-24">Duedate</th>
                            <th colspan="{{ count($sequences) }}" class="px-2 py-3 text-center font-bold text-slate-700 border-r border-slate-200">Sequence</th>
                            <th rowspan="2" class="px-2 py-3 text-right font-bold text-slate-700 w-20">Remain</th>
                        </tr>
                        <tr>
                            @foreach($sequences as $seq)
                                <th class="px-2 py-2 text-center font-bold text-slate-700 border-r border-slate-200 w-10 col-seq" data-seq="{{ $seq }}">{{ $seq }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @php($no = 0)
                        @forelse(($groups ?? []) as $class => $rows)
                            <tr class="bg-slate-100">
                                <td class="px-2 py-2 border-r border-slate-200"></td>
                                <td class="px-2 py-2 font-black text-slate-800 border-r border-slate-200" colspan="1">{{ $class }}</td>
                                <td class="px-2 py-2 border-r border-slate-200" colspan="4"></td>
                                <td class="px-2 py-2 border-r border-slate-200"></td>
                                <td class="px-2 py-2 border-r border-slate-200"></td>
                                <td class="px-2 py-2 border-r border-slate-200"></td>
                                <td class="px-2 py-2 border-r border-slate-200"></td>
                                <td class="px-2 py-2 border-r border-slate-200"></td>
                                <td class="px-2 py-2 border-r border-slate-200"></td>
                                @foreach($sequences as $seq)
                                    <td class="px-2 py-2 border-r border-slate-200"></td>
                                @endforeach
                                <td class="px-2 py-2"></td>
                            </tr>
                            @foreach($rows as $r)
                                @php($no++)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-2 py-2 border-r border-slate-200 text-slate-600 font-semibold">{{ $no }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-slate-700">{{ $r->delivery_class }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-slate-700">{{ $r->part_name }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 font-mono text-indigo-700 font-bold">{{ $r->part_no }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-slate-700">{{ $r->production_lines ?? '-' }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-right font-bold text-slate-900">{{ number_format((float) $r->plan_total, 0) }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-right text-slate-700 col-stock">{{ (float) $r->stock_at_customer > 0 ? number_format((float) $r->stock_at_customer, 0) : '-' }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-right font-bold text-slate-900">{{ number_format((float) $r->balance, 0) }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-right font-bold text-slate-900 col-jig">{{ (int) ($r->jig_nr1 ?? 0) > 0 ? number_format((int) $r->jig_nr1) : '-' }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-right font-bold text-slate-900 col-jig">{{ (int) ($r->jig_nr2 ?? 0) > 0 ? number_format((int) $r->jig_nr2) : '-' }}</td>
                                    <td class="px-2 py-2 border-r border-slate-200 text-slate-700">{{ $r->due_date?->format('Y-m-d') }}</td>
                                    @foreach($sequences as $seq)
                                        @php($v = (float) (($r->per_seq[$seq] ?? 0) ?: 0))
                                        <td class="px-2 py-2 border-r border-slate-200 text-center col-seq {{ $v > 0 ? 'font-bold text-slate-900' : 'text-slate-400' }}" data-seq="{{ $seq }}">
                                            {{ $v > 0 ? number_format($v, 0) : '' }}
                                        </td>
                                    @endforeach
                                    <td class="px-2 py-2 text-right font-bold text-slate-900">{{ number_format((float) $r->remain, 0) }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="{{ 12 + count($sequences) }}" class="px-6 py-12 text-center text-slate-500 italic">
                                    No data for selected date.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="assignModal" class="hidden fixed inset-0 z-50">
        <div class="absolute inset-0 bg-slate-900/40" onclick="closeAssign()"></div>
        <div class="absolute inset-x-0 top-20 mx-auto w-full max-w-lg px-4">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <div class="font-black text-slate-900">Assign Truck / Driver</div>
                    <button type="button" class="text-slate-500 hover:text-slate-900" onclick="closeAssign()">✕</button>
                </div>
                <form id="assignForm" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <div class="text-xs font-semibold text-slate-500 mb-1">Truck</div>
                        <select name="truck_id" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">-- none --</option>
                            @foreach(($trucks ?? []) as $t)
                                <option value="{{ $t->id }}">{{ $t->plate_no }}{{ $t->type ? ' • ' . $t->type : '' }}{{ $t->capacity ? ' • ' . $t->capacity : '' }}{{ $t->status ? ' • ' . $t->status : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 mb-1">Driver</div>
                        <select name="driver_id" class="w-full rounded-lg border-slate-300 text-sm">
                            <option value="">-- none --</option>
                            @foreach(($drivers ?? []) as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}{{ $d->phone ? ' • ' . $d->phone : '' }}{{ $d->license_type ? ' • ' . $d->license_type : '' }}{{ $d->status ? ' • ' . $d->status : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50" onclick="closeAssign()">Cancel</button>
                        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAssign(planId) {
            const form = document.getElementById('assignForm');
            form.action = `{{ url('/outgoing/delivery-plan') }}/${planId}/assign-resources`;
            document.getElementById('assignModal').classList.remove('hidden');
        }
        function closeAssign() {
            document.getElementById('assignModal').classList.add('hidden');
        }

        function toggleCustomize() {
            document.getElementById('customizePanel').classList.toggle('hidden');
        }

        function readCustomize() {
            try {
                return JSON.parse(localStorage.getItem('deliveryPlanCustomize') || '{}');
            } catch (e) {
                return {};
            }
        }

        function writeCustomize(cfg) {
            localStorage.setItem('deliveryPlanCustomize', JSON.stringify(cfg));
        }

        function applyCustomizeToDom(cfg) {
            const showStock = cfg.showStock !== false;
            const showJig = cfg.showJig !== false;
            const maxSeq = Number(cfg.maxSeq || {{ count($sequences) }});

            document.querySelectorAll('.col-stock').forEach(el => el.classList.toggle('hidden', !showStock));
            document.querySelectorAll('.col-jig').forEach(el => el.classList.toggle('hidden', !showJig));

            document.querySelectorAll('.col-seq').forEach(el => {
                const seq = Number(el.dataset.seq || 0);
                el.classList.toggle('hidden', seq > maxSeq);
            });
        }

        function applyCustomize() {
            const cfg = {
                showStock: document.getElementById('optShowStock').checked,
                showJig: document.getElementById('optShowJig').checked,
                maxSeq: Number(document.getElementById('optMaxSeq').value || {{ count($sequences) }}),
            };
            writeCustomize(cfg);
            applyCustomizeToDom(cfg);
        }

        function resetCustomize() {
            const cfg = { showStock: true, showJig: true, maxSeq: {{ count($sequences) }} };
            writeCustomize(cfg);
            hydrateCustomizeForm(cfg);
            applyCustomizeToDom(cfg);
        }

        function hydrateCustomizeForm(cfg) {
            document.getElementById('optShowStock').checked = cfg.showStock !== false;
            document.getElementById('optShowJig').checked = cfg.showJig !== false;
            document.getElementById('optMaxSeq').value = Number(cfg.maxSeq || {{ count($sequences) }});
        }

        (function initCustomize() {
            const cfg = Object.assign({ showStock: true, showJig: true, maxSeq: {{ count($sequences) }} }, readCustomize());
            hydrateCustomizeForm(cfg);
            applyCustomizeToDom(cfg);
        })();
    </script>
@endsection
