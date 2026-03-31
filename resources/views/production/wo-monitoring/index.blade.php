@extends('layouts.app')

@section('title', 'WO Monitoring — Real-time')

@section('content')
    <div class="max-w-full mx-auto">
        {{-- Header --}}
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
                        <div
                            class="h-10 w-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-sm">
                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
                            </svg>
                        </div>
                        WO MONITORING
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">Production monitoring real-time per mesin — data dari Android App.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <input type="date" id="monitorDate" value="{{ $date }}"
                        class="rounded-lg border-slate-300 text-sm px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500" />
                    <div id="liveBadge"
                        class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                        <span class="mr-1.5 flex h-2 w-2 relative">
                            <span
                                class="absolute inline-flex h-2 w-2 animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        </span>
                        Auto-refresh 30s
                    </div>
                </div>
            </div>
        </div>

        {{-- Machine Cards Container --}}
        <div id="machineCards" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            <div class="col-span-full text-center py-12 text-slate-400">
                <svg class="mx-auto h-12 w-12 animate-spin text-slate-300 mb-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0116 0H4z"></path>
                </svg>
                Loading data...
            </div>
        </div>
    </div>

    <script>
        let refreshTimer = null;

        function fetchMonitoringData() {
            const date = document.getElementById('monitorDate').value;
            fetch(`/api/production-gci/wo-monitoring?date=${date}`)
                .then(r => r.json())
                .then(json => renderCards(json.data))
                .catch(() => {
                    document.getElementById('machineCards').innerHTML =
                        '<div class="col-span-full text-center py-12 text-rose-400">Error loading data. Retrying...</div>';
                });
        }

        function renderCards(machines) {
            const container = document.getElementById('machineCards');
            if (!machines || machines.length === 0) {
                container.innerHTML =
                    '<div class="col-span-full text-center py-12 text-slate-400">Tidak ada data untuk tanggal ini.</div>';
                return;
            }

            // Filter: only show machines that have orders OR downtime
            const activeMachines = machines.filter(m => m.orders.length > 0 || m.downtime_count > 0);

            if (activeMachines.length === 0) {
                container.innerHTML =
                    '<div class="col-span-full text-center py-12 text-slate-400">Tidak ada WO aktif untuk tanggal ini.</div>';
                return;
            }

            container.innerHTML = activeMachines.map(m => renderMachineCard(m)).join('');
        }

        function renderMachineCard(machine) {
            const orders = machine.orders;
            const hasRunning = orders.some(o => (o.display_status || o.status) === 'in_production');

            const statusDot = hasRunning ?
                '<span class="flex h-3 w-3 relative"><span class="absolute inline-flex h-3 w-3 animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"></span></span>' :
                '<span class="flex h-3 w-3"><span class="inline-flex h-3 w-3 rounded-full bg-slate-300"></span></span>';

            const ordersHtml = orders.map(o => {
                const pct = o.qty_planned > 0 ? Math.min(Math.round((o.qty_actual / o.qty_planned) * 100), 100) : 0;
                const displayStatus = o.display_status || o.status;
                const barColor = displayStatus === 'completed' ? 'bg-blue-500' : (pct >= 80 ? 'bg-emerald-500' : (pct >= 50 ? 'bg-amber-500' : 'bg-rose-500'));
                const statusBadge = statusBadgeHtml(displayStatus);

                // Hourly data
                let hourlyHtml = '';
                if (o.hourly && o.hourly.length > 0) {
                    hourlyHtml = '<div class="mt-2 flex flex-wrap gap-1">' +
                        o.hourly.map(h => {
                            const hPct = h.target > 0 ? Math.round((h.actual / h.target) * 100) : 0;
                            const hColor = hPct >= 90 ? 'bg-emerald-100 text-emerald-700' : (hPct >= 70 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700');
                            return `<span class="text-[10px] font-semibold px-1.5 py-0.5 rounded ${hColor}">${h.time_range.split('-')[0]} ${h.actual}</span>`;
                        }).join('') +
                        '</div>';
                }

                return `
                    <div class="py-3 border-b border-slate-100 last:border-0">
                        <div class="flex items-center justify-between mb-1">
                            <div>
                                <span class="text-sm font-bold text-slate-900">${o.wo_number}</span>
                                ${statusBadge}
                            </div>
                            <span class="text-xs font-bold ${pct >= 80 ? 'text-emerald-600' : 'text-slate-500'}">${pct}%</span>
                        </div>
                        <div class="text-xs text-slate-500 mb-1">${o.part_no || '-'} — ${o.part_name || '-'}</div>
                        <div class="flex items-center gap-3 mb-1">
                            <span class="text-xs text-slate-400">Target: <b class="text-slate-700">${Math.round(o.qty_planned)}</b></span>
                            <span class="text-xs text-slate-400">OK: <b class="text-emerald-600">${Math.round(o.qty_actual)}</b></span>
                            <span class="text-xs text-slate-400">NG: <b class="text-rose-600">${Math.round(o.qty_ng)}</b></span>
                        </div>
                        <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full ${barColor} transition-all duration-500" style="width: ${pct}%"></div>
                        </div>
                        ${hourlyHtml}
                    </div>
                `;
            }).join('');

            return `
                <div class="bg-white rounded-2xl shadow-sm border ${hasRunning ? 'border-emerald-200 ring-1 ring-emerald-100' : 'border-slate-200'} overflow-hidden">
                    <div class="px-5 py-4 ${hasRunning ? 'bg-emerald-50/50' : 'bg-slate-50/50'} border-b border-slate-100 flex items-center gap-3">
                        ${statusDot}
                        <div class="flex-1">
                            <div class="text-sm font-bold text-slate-900">${machine.machine.name}</div>
                            <div class="text-[11px] text-slate-400">${machine.machine.code || ''}</div>
                        </div>
                        ${machine.total_downtime_minutes > 0 ?
                            `<span class="text-[10px] font-bold px-2 py-1 rounded-lg bg-rose-50 text-rose-600 ring-1 ring-rose-200">
                                ⚠ ${machine.total_downtime_minutes} mnt downtime
                            </span>` : ''}
                    </div>
                    <div class="px-5 py-2">
                        ${orders.length > 0 ? ordersHtml : '<div class="py-6 text-center text-xs text-slate-400">Tidak ada WO</div>'}
                    </div>
                </div>
            `;
        }

        function statusBadgeHtml(status) {
            const map = {
                'in_production': ['bg-emerald-100 text-emerald-700', 'RUNNING'],
                'completed': ['bg-blue-100 text-blue-700', 'DONE'],
                'planned': ['bg-amber-100 text-amber-700', 'PLANNED'],
                'released': ['bg-purple-100 text-purple-700', 'RELEASED'],
                'kanban_released': ['bg-purple-100 text-purple-700', 'RELEASED'],
            };
            const [cls, label] = map[status] || ['bg-slate-100 text-slate-500', status.toUpperCase()];
            return `<span class="ml-2 text-[10px] font-bold px-1.5 py-0.5 rounded ${cls}">${label}</span>`;
        }

        document.getElementById('monitorDate').addEventListener('change', fetchMonitoringData);

        // Auto-refresh every 30 seconds
        fetchMonitoringData();
        refreshTimer = setInterval(fetchMonitoringData, 30000);
    </script>
@endsection
