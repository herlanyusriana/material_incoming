@extends('layouts.app')

@section('title', 'WO Monitoring - Real-time')

@section('content')
    <div class="max-w-full mx-auto">
        <div class="mb-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="flex items-center gap-3 text-2xl font-bold text-slate-900">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-sm">
                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
                            </svg>
                        </div>
                        WO MONITORING
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Production monitoring real-time per mesin. QDC ditampilkan per WO dan per mesin supaya lebih mudah ditracking.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <input type="date" id="monitorDate" value="{{ $date }}"
                        class="rounded-lg border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
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

        <div id="machineCards" class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
            <div class="col-span-full py-12 text-center text-slate-400">
                <svg class="mx-auto mb-3 h-12 w-12 animate-spin text-slate-300" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0116 0H4z"></path>
                </svg>
                Loading data...
            </div>
        </div>
    </div>

    <script>
        let refreshTimer = null;
        let monitoringChannel = null;

        function formatMinutesFromSeconds(seconds) {
            return `${Math.round((seconds || 0) / 60)} mnt`;
        }

        function formatClock(dateTime) {
            if (!dateTime) {
                return '-';
            }

            const value = new Date(dateTime);
            if (Number.isNaN(value.getTime())) {
                return dateTime;
            }

            return value.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
            });
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function flashLiveBadge(message = 'Live via WebSocket') {
            const badge = document.getElementById('liveBadge');
            if (!badge) {
                return;
            }

            badge.dataset.baseLabel ??= badge.innerHTML;
            badge.innerHTML = `
                <span class="mr-1.5 flex h-2 w-2 relative">
                    <span class="absolute inline-flex h-2 w-2 animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                </span>
                ${message}
            `;

            clearTimeout(window.__liveBadgeTimer);
            window.__liveBadgeTimer = setTimeout(() => {
                badge.innerHTML = badge.dataset.baseLabel;
            }, 4000);
        }

        function fetchMonitoringData() {
            const date = document.getElementById('monitorDate').value;
            fetch(`/api/production-gci/wo-monitoring?date=${date}`)
                .then(r => r.json())
                .then(json => renderCards(json.data))
                .catch(() => {
                    document.getElementById('machineCards').innerHTML =
                        '<div class="col-span-full py-12 text-center text-rose-400">Error loading data. Retrying...</div>';
                });
        }

        function initMonitoringRealtime() {
            if (!window.initRealtimeEcho) {
                return;
            }

            const echo = window.initRealtimeEcho();
            if (!echo) {
                return;
            }

            monitoringChannel = echo.channel('production.monitoring')
                .listen('.production.monitoring.updated', (payload) => {
                    const selectedDate = document.getElementById('monitorDate').value;
                    const dates = Array.isArray(payload.dates) ? payload.dates : [];

                    if (dates.length > 0 && !dates.includes(selectedDate)) {
                        return;
                    }

                    flashLiveBadge(`Live update: ${payload.type || 'monitoring'}`);
                    fetchMonitoringData();
                });
        }

        function renderCards(machines) {
            const container = document.getElementById('machineCards');
            if (!machines || machines.length === 0) {
                container.innerHTML =
                    '<div class="col-span-full py-12 text-center text-slate-400">Tidak ada data untuk tanggal ini.</div>';
                return;
            }

            const activeMachines = machines.filter(m => m.orders.length > 0 || m.downtime_count > 0);

            if (activeMachines.length === 0) {
                container.innerHTML =
                    '<div class="col-span-full py-12 text-center text-slate-400">Tidak ada WO aktif untuk tanggal ini.</div>';
                return;
            }

            container.innerHTML = activeMachines.map(m => renderMachineCard(m)).join('');
        }

        function renderMachineCard(machine) {
            const orders = machine.orders;
            const qdcSessions = Array.isArray(machine.qdc_sessions) ? machine.qdc_sessions : [];
            const unassignedQdc = qdcSessions.filter(session => !session.production_order_id);
            const hasRunning = orders.some(o => (o.display_status || o.status) === 'in_production');
            const hasPaused = orders.some(o => (o.display_status || o.status) === 'paused');
            const summaryHtml = renderMachineSummary(machine);

            const statusDot = hasRunning
                ? '<span class="relative flex h-3 w-3"><span class="absolute inline-flex h-3 w-3 animate-ping rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex h-3 w-3 rounded-full bg-emerald-500"></span></span>'
                : (hasPaused
                    ? '<span class="relative flex h-3 w-3"><span class="absolute inline-flex h-3 w-3 animate-ping rounded-full bg-amber-300 opacity-75"></span><span class="relative inline-flex h-3 w-3 rounded-full bg-amber-500"></span></span>'
                    : '<span class="flex h-3 w-3"><span class="inline-flex h-3 w-3 rounded-full bg-slate-300"></span></span>');

            const ordersHtml = orders.map(o => {
                const pct = o.qty_planned > 0 ? Math.min(Math.round((o.qty_actual / o.qty_planned) * 100), 100) : 0;
                const displayStatus = o.display_status || o.status;
                const barColor = displayStatus === 'completed'
                    ? 'bg-blue-500'
                    : (displayStatus === 'paused'
                        ? 'bg-amber-400'
                        : (pct >= 80 ? 'bg-emerald-500' : (pct >= 50 ? 'bg-amber-500' : 'bg-rose-500')));
                const statusBadge = statusBadgeHtml(displayStatus);
                const processName = (o.process_name || '').trim();
                const hasHandover = (o.last_handover_from_process || '').trim() || (o.last_handover_from_machine_name || '').trim();
                const handoverHistory = Array.isArray(o.handover_history) ? o.handover_history : [];

                let hourlyHtml = '';
                if (o.hourly && o.hourly.length > 0) {
                    hourlyHtml = '<div class="mt-2 flex flex-wrap gap-1">' +
                        o.hourly.map(h => {
                            const hPct = h.target > 0 ? Math.round((h.actual / h.target) * 100) : 0;
                            const outputType = (h.output_type || 'fg').toLowerCase();
                            const hColor = outputType === 'wip'
                                ? 'bg-sky-100 text-sky-700'
                                : (hPct >= 90 ? 'bg-emerald-100 text-emerald-700' : (hPct >= 70 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700'));
                            return `<span class="rounded px-1.5 py-0.5 text-[10px] font-semibold ${hColor}">${h.time_range.split('-')[0]} ${outputType.toUpperCase()} ${h.actual}</span>`;
                        }).join('') +
                        '</div>';
                }

                const latestQdc = o.latest_qdc;
                const handoverHistoryHtml = handoverHistory.length > 0 ? `
                    <div class="mt-2 rounded-lg bg-slate-50 px-2.5 py-2 ring-1 ring-slate-200">
                        <div class="mb-1 text-[10px] font-bold uppercase tracking-wide text-slate-500">Timeline Handover</div>
                        <div class="space-y-1.5">
                            ${handoverHistory.map(item => `
                                <div class="flex items-start gap-2 text-[11px] text-slate-700">
                                    <span class="mt-1 inline-flex h-2 w-2 rounded-full bg-sky-500"></span>
                                    <div>
                                        <div class="font-semibold text-slate-800">
                                            ${escapeHtml(item.process_name || '-')} - ${escapeHtml(item.output_part_no || '-')}
                                        </div>
                                        <div class="text-slate-500">
                                            ${escapeHtml(item.time_range || '-')} - Qty ${Math.round(item.actual || 0)}${item.operator_name ? ` - ${escapeHtml(item.operator_name)}` : ''}${item.shift ? ` - ${escapeHtml(item.shift)}` : ''}
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : '';
                const qdcDetailHtml = latestQdc ? `
                    <div class="mt-2 rounded-lg bg-violet-50 px-2.5 py-2 ring-1 ring-violet-100">
                        <div class="flex flex-wrap items-center gap-2 text-[10px] font-semibold text-violet-700">
                            <span>Sesi terakhir ${formatClock(latestQdc.start_time)} - ${formatClock(latestQdc.end_time)}</span>
                            <span>${formatMinutesFromSeconds(latestQdc.duration_seconds)}</span>
                            ${latestQdc.operator_name ? `<span>Operator: ${escapeHtml(latestQdc.operator_name)}</span>` : ''}
                        </div>
                        <div class="mt-1 text-[11px] text-violet-900">
                            ${latestQdc.part_from || latestQdc.part_to
                                ? `${escapeHtml(latestQdc.part_from || '-')} -> ${escapeHtml(latestQdc.part_to || '-')}`
                                : `${escapeHtml(latestQdc.part_no || '-')} - ${escapeHtml(latestQdc.part_name || '-')}`
                            }
                        </div>
                        ${latestQdc.notes ? `<div class="mt-1 text-[10px] text-violet-700">Catatan: ${escapeHtml(latestQdc.notes)}</div>` : ''}
                    </div>
                ` : '';

                return `
                    <div class="border-b border-slate-100 py-3 last:border-0">
                        <div class="mb-1 flex items-center justify-between">
                            <div>
                                <span class="text-sm font-bold text-slate-900">${o.wo_number}</span>
                                ${statusBadge}
                            </div>
                            <span class="text-xs font-bold ${pct >= 80 ? 'text-emerald-600' : 'text-slate-500'}">${pct}%</span>
                        </div>
                        <div class="mb-1 text-xs text-slate-500">${o.part_no || '-'} - ${o.part_name || '-'}</div>
                        ${processName ? `
                            <div class="mb-2 inline-flex items-center rounded-lg bg-teal-50 px-2 py-1 text-[10px] font-bold text-teal-700 ring-1 ring-teal-200">
                                Proses aktif: ${escapeHtml(processName)}
                            </div>
                        ` : ''}
                        ${hasHandover ? `
                            <div class="mb-2 rounded-lg bg-amber-50 px-2.5 py-2 text-[11px] text-amber-800 ring-1 ring-amber-100">
                                Handover masuk${(o.last_handover_from_process || '').trim() ? ` dari <b>${escapeHtml(o.last_handover_from_process)}</b>` : ''}${(o.last_handover_from_machine_name || '').trim() ? ` - ${escapeHtml(o.last_handover_from_machine_name)}` : ''}
                            </div>
                        ` : ''}
                        <div class="mb-1 flex items-center gap-3">
                            <span class="text-xs text-slate-400">Target: <b class="text-slate-700">${Math.round(o.qty_planned)}</b></span>
                            <span class="text-xs text-slate-400">OK: <b class="text-emerald-600">${Math.round(o.qty_actual)}</b></span>
                            <span class="text-xs text-slate-400">NG: <b class="text-rose-600">${Math.round(o.qty_ng)}</b></span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full ${barColor} transition-all duration-500" style="width: ${pct}%"></div>
                        </div>
                        ${(o.qdc_count || 0) > 0 ? `
                            <div class="mt-2">
                                <span class="inline-flex items-center rounded-lg bg-violet-50 px-2 py-1 text-[10px] font-bold text-violet-700 ring-1 ring-violet-200">
                                    QDC ${o.qdc_count}x | ${formatMinutesFromSeconds(o.qdc_duration_seconds || 0)}
                                </span>
                            </div>
                        ` : ''}
                        ${qdcDetailHtml}
                        ${handoverHistoryHtml}
                        ${hourlyHtml}
                    </div>
                `;
            }).join('');

            const machineQdcHtml = machine.qdc_count > 0 ? `
                <div class="mx-5 mt-4 rounded-xl bg-violet-50/80 px-4 py-3 ring-1 ring-violet-100">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-violet-800">
                        <span>QDC mesin ${machine.qdc_count}x</span>
                        <span>| ${formatMinutesFromSeconds(machine.qdc_total_seconds || 0)}</span>
                        ${machine.qdc_unassigned_count > 0 ? `<span>| ${machine.qdc_unassigned_count} belum linked ke WO</span>` : ''}
                    </div>
                    <div class="mt-2 space-y-2">
                        ${qdcSessions.slice(0, 3).map(session => `
                            <div class="rounded-lg bg-white/80 px-3 py-2 text-[11px] text-slate-700 ring-1 ring-violet-100">
                                <div class="flex flex-wrap items-center gap-2 font-semibold text-slate-800">
                                    <span>${formatClock(session.start_time)} - ${formatClock(session.end_time)}</span>
                                    <span>${formatMinutesFromSeconds(session.duration_seconds)}</span>
                                    ${session.production_order_number ? `<span>WO ${escapeHtml(session.production_order_number)}</span>` : '<span>WO belum ter-link</span>'}
                                </div>
                                <div class="mt-1">
                                    ${session.part_from || session.part_to
                                        ? `${escapeHtml(session.part_from || '-')} -> ${escapeHtml(session.part_to || '-')}`
                                        : `${escapeHtml(session.part_no || '-')} - ${escapeHtml(session.part_name || '-')}`
                                    }
                                </div>
                                ${session.operator_name ? `<div class="mt-1 text-[10px] text-slate-500">Operator: ${escapeHtml(session.operator_name)}</div>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : '';

            const unassignedQdcHtml = orders.length === 0 && unassignedQdc.length > 0
                ? '<div class="px-5 pb-5 pt-3 text-xs text-violet-700">Mesin ini punya QDC, tapi belum ada WO yang terhubung untuk tanggal ini.</div>'
                : '';

            return `
                <div class="overflow-hidden rounded-2xl border bg-white shadow-sm ${hasRunning ? 'border-emerald-200 ring-1 ring-emerald-100' : (hasPaused ? 'border-amber-200 ring-1 ring-amber-100' : 'border-slate-200')}">
                    <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4 ${hasRunning ? 'bg-emerald-50/50' : (hasPaused ? 'bg-amber-50/50' : 'bg-slate-50/50')}">
                        ${statusDot}
                        <div class="flex-1">
                            <div class="text-sm font-bold text-slate-900">${machine.machine.name}</div>
                            <div class="text-[11px] text-slate-400">${machine.machine.code || ''}</div>
                        </div>
                        ${machine.total_downtime_minutes > 0 ?
                            `<span class="rounded-lg bg-rose-50 px-2 py-1 text-[10px] font-bold text-rose-600 ring-1 ring-rose-200">
                            ${machine.total_downtime_minutes} mnt downtime
                            </span>` : ''}
                    </div>
                    ${summaryHtml}
                    ${machineQdcHtml}
                    <div class="px-5 py-2">
                        ${orders.length > 0 ? ordersHtml : '<div class="py-6 text-center text-xs text-slate-400">Tidak ada WO</div>'}
                    </div>
                    ${unassignedQdcHtml}
                </div>
            `;
        }

        function addAggregate(bucket, key, actual, ng) {
            if (!key) {
                key = '-';
            }

            if (!bucket[key]) {
                bucket[key] = { actual: 0, ng: 0 };
            }

            bucket[key].actual += Number(actual || 0);
            bucket[key].ng += Number(ng || 0);
        }

        function renderAggregateList(title, bucket, toneClass) {
            const rows = Object.entries(bucket)
                .sort((a, b) => (b[1].actual + b[1].ng) - (a[1].actual + a[1].ng))
                .slice(0, 4);

            if (rows.length === 0) {
                return '';
            }

            return `
                <div class="rounded-xl bg-white/80 p-3 ring-1 ring-slate-200">
                    <div class="mb-2 text-[10px] font-black uppercase tracking-wide text-slate-400">${title}</div>
                    <div class="space-y-1.5">
                        ${rows.map(([key, value]) => `
                            <div class="flex items-center justify-between gap-3 text-[11px]">
                                <span class="min-w-0 truncate font-bold text-slate-700" title="${escapeHtml(key)}">${escapeHtml(key)}</span>
                                <span class="shrink-0 rounded-lg px-2 py-0.5 font-black ${toneClass}">
                                    OK ${Math.round(value.actual)}${value.ng > 0 ? ` / NG ${Math.round(value.ng)}` : ''}
                                </span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        function renderMachineSummary(machine) {
            const processBucket = {};
            const shiftBucket = {};
            const machineBucket = {};
            let totalOk = 0;
            let totalNg = 0;
            let fgOk = 0;
            let wipOk = 0;

            (machine.orders || []).forEach(order => {
                (order.hourly || []).forEach(report => {
                    const actual = Number(report.actual || 0);
                    const ng = Number(report.ng || 0);
                    const outputType = String(report.output_type || 'fg').toLowerCase();
                    const processName = String(report.process_name || order.process_name || '-').trim();
                    const shiftName = String(report.shift || order.shift || '-').trim();
                    const actualMachine = String(report.machine_name || machine.machine?.name || '-').trim();

                    totalOk += actual;
                    totalNg += ng;
                    if (outputType === 'fg') {
                        fgOk += actual;
                    } else {
                        wipOk += actual;
                    }

                    addAggregate(processBucket, processName, actual, ng);
                    addAggregate(shiftBucket, shiftName, actual, ng);
                    addAggregate(machineBucket, actualMachine, actual, ng);
                });
            });

            if (totalOk <= 0 && totalNg <= 0) {
                return '';
            }

            return `
                <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-4">
                    <div class="mb-3 grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
                        <div class="rounded-xl bg-emerald-50 px-3 py-2 ring-1 ring-emerald-100">
                            <div class="text-[10px] font-bold uppercase text-emerald-600">Total OK</div>
                            <div class="font-mono text-lg font-black text-emerald-700">${Math.round(totalOk)}</div>
                        </div>
                        <div class="rounded-xl bg-rose-50 px-3 py-2 ring-1 ring-rose-100">
                            <div class="text-[10px] font-bold uppercase text-rose-600">Total NG</div>
                            <div class="font-mono text-lg font-black text-rose-700">${Math.round(totalNg)}</div>
                        </div>
                        <div class="rounded-xl bg-sky-50 px-3 py-2 ring-1 ring-sky-100">
                            <div class="text-[10px] font-bold uppercase text-sky-600">WIP OK</div>
                            <div class="font-mono text-lg font-black text-sky-700">${Math.round(wipOk)}</div>
                        </div>
                        <div class="rounded-xl bg-blue-50 px-3 py-2 ring-1 ring-blue-100">
                            <div class="text-[10px] font-bold uppercase text-blue-600">FG OK</div>
                            <div class="font-mono text-lg font-black text-blue-700">${Math.round(fgOk)}</div>
                        </div>
                    </div>
                    <div class="grid gap-2 xl:grid-cols-3">
                        ${renderAggregateList('Per Proses', processBucket, 'bg-teal-50 text-teal-700')}
                        ${renderAggregateList('Per Shift', shiftBucket, 'bg-indigo-50 text-indigo-700')}
                        ${renderAggregateList('Mesin Aktual', machineBucket, 'bg-slate-100 text-slate-700')}
                    </div>
                </div>
            `;
        }

        function statusBadgeHtml(status) {
            const map = {
                in_production: ['bg-emerald-100 text-emerald-700', 'RUNNING'],
                paused: ['bg-amber-100 text-amber-700', 'PAUSED'],
                completed: ['bg-blue-100 text-blue-700', 'DONE'],
                planned: ['bg-amber-100 text-amber-700', 'PLANNED'],
                released: ['bg-purple-100 text-purple-700', 'RELEASED'],
                kanban_released: ['bg-purple-100 text-purple-700', 'RELEASED'],
            };

            const [cls, label] = map[status] || ['bg-slate-100 text-slate-500', String(status || '-').toUpperCase()];
            return `<span class="ml-2 rounded px-1.5 py-0.5 text-[10px] font-bold ${cls}">${label}</span>`;
        }

        document.getElementById('monitorDate').addEventListener('change', fetchMonitoringData);

        fetchMonitoringData();
        initMonitoringRealtime();
        refreshTimer = setInterval(fetchMonitoringData, 30000);
    </script>
@endsection
