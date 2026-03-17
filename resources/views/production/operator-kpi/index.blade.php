<x-app-layout>
    <x-slot name="header">
        Operator Performance
    </x-slot>

    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900">Operator Performance</h1>
                    <p class="mt-1 text-sm text-slate-500">Performa operator berdasarkan data produksi dari Android App.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 bg-slate-50 rounded-xl border border-slate-200 px-4 py-2.5">
                        <label class="text-xs font-bold text-slate-500 uppercase">Dari</label>
                        <input type="date" id="dateFrom" value="{{ $from }}"
                            class="border-0 bg-transparent text-sm p-0 focus:ring-0 font-semibold" />
                        <label class="text-xs font-bold text-slate-500 uppercase ml-2">Sampai</label>
                        <input type="date" id="dateTo" value="{{ $to }}"
                            class="border-0 bg-transparent text-sm p-0 focus:ring-0 font-semibold" />
                        <button onclick="fetchKpiData()"
                            class="ml-3 px-4 py-1.5 bg-indigo-600 text-white text-xs font-bold rounded-lg hover:bg-indigo-700 transition">
                            Tampilkan
                        </button>
                    </div>
                    <button onclick="downloadPdf()"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-900 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        PDF
                    </button>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div id="summaryCards" class="grid grid-cols-2 md:grid-cols-5 gap-4">
            @for($i = 0; $i < 5; $i++)
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 animate-pulse">
                <div class="h-3 bg-slate-100 rounded w-20 mb-3"></div>
                <div class="h-8 bg-slate-100 rounded w-16"></div>
            </div>
            @endfor
        </div>

        {{-- Operator Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/30 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Performance Ranking</h2>
                <div class="text-xs text-slate-400" id="periodLabel">Loading...</div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="kpiTable">
                    <thead>
                        <tr class="bg-slate-50 border-b-2 border-slate-900">
                            <th class="px-4 py-3 text-xs font-bold text-slate-900 uppercase tracking-wider w-12">#</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-900 uppercase tracking-wider">Operator</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-900 uppercase tracking-wider text-center">Hari</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-900 uppercase tracking-wider text-center">WO</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-900 uppercase tracking-wider text-right">Output</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-900 uppercase tracking-wider text-right">Target</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-900 uppercase tracking-wider text-center">Efisiensi</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-900 uppercase tracking-wider text-center">NG Rate</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-900 uppercase tracking-wider text-center">Downtime</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-900 uppercase tracking-wider text-center">QDC</th>
                            <th class="px-4 py-3 text-xs font-bold text-slate-900 uppercase tracking-wider text-center">Score</th>
                        </tr>
                    </thead>
                    <tbody id="kpiBody" class="divide-y divide-slate-100">
                        <tr>
                            <td colspan="11" class="px-6 py-12 text-center text-slate-400">
                                <svg class="mx-auto h-8 w-8 animate-spin text-slate-300 mb-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0116 0H4z"></path>
                                </svg>
                                Loading...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Detail Panel --}}
        <div id="detailPanel" class="hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/30 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-700" id="detailTitle">Detail Operator</h2>
                    <button onclick="document.getElementById('detailPanel').classList.add('hidden')"
                        class="text-xs text-slate-400 hover:text-slate-600 font-bold">Tutup</button>
                </div>
                <div class="p-6" id="detailContent"></div>
            </div>
        </div>
    </div>

    <script>
        function fetchKpiData() {
            const from = document.getElementById('dateFrom').value;
            const to = document.getElementById('dateTo').value;
            document.getElementById('periodLabel').textContent = `${from} s/d ${to}`;

            fetch(`/production/operator-kpi/data?from=${from}&to=${to}`)
                .then(r => r.json())
                .then(json => {
                    renderSummary(json.summary);
                    renderTable(json.data);
                })
                .catch(err => {
                    document.getElementById('kpiBody').innerHTML =
                        '<tr><td colspan="11" class="px-6 py-12 text-center text-rose-400">Gagal memuat data</td></tr>';
                });
        }

        function downloadPdf() {
            const from = document.getElementById('dateFrom').value;
            const to = document.getElementById('dateTo').value;
            window.open(`/production/operator-kpi/pdf?from=${from}&to=${to}`, '_blank');
        }

        function renderSummary(s) {
            const container = document.getElementById('summaryCards');
            container.innerHTML = `
                ${summaryCard('Operator', s.total_operators, 'indigo')}
                ${summaryCard('Total Output', s.total_output.toLocaleString(), 'emerald')}
                ${summaryCard('Total NG', s.total_ng.toLocaleString(), 'red')}
                ${summaryCard('Total Downtime', s.total_downtime + ' mnt', 'amber')}
                ${summaryCard('Avg Efisiensi', s.avg_efficiency + '%', 'violet')}
            `;
        }

        function summaryCard(label, value, color) {
            return `
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">${label}</div>
                    <div class="text-3xl font-black text-${color}-600">${value}</div>
                </div>
            `;
        }

        function renderTable(data) {
            const body = document.getElementById('kpiBody');

            if (!data || data.length === 0) {
                body.innerHTML = '<tr><td colspan="11" class="px-6 py-12 text-center text-slate-400 italic">Belum ada data operator di periode ini</td></tr>';
                return;
            }

            body.innerHTML = data.map(op => {
                const medal = op.rank <= 3 ? ['<span class="text-2xl">1</span>','<span class="text-xl text-slate-500">2</span>','<span class="text-lg text-amber-700">3</span>'][op.rank-1] : op.rank;
                const effColor = op.efficiency >= 90 ? 'text-emerald-600 bg-emerald-50' :
                    (op.efficiency >= 70 ? 'text-amber-600 bg-amber-50' : 'text-rose-600 bg-rose-50');
                const ngColor = op.ng_rate <= 1 ? 'text-emerald-600' :
                    (op.ng_rate <= 3 ? 'text-amber-600' : 'text-rose-600');
                const scoreColor = op.score >= 80 ? 'bg-emerald-500' :
                    (op.score >= 50 ? 'bg-amber-500' : 'bg-rose-500');

                const avgQdcFormatted = op.avg_qdc_seconds > 0
                    ? Math.floor(op.avg_qdc_seconds/60) + ':' + String(op.avg_qdc_seconds%60).padStart(2,'0')
                    : '-';

                return `
                    <tr class="hover:bg-slate-50/50 transition-colors cursor-pointer" onclick="showDetail(${JSON.stringify(op).replace(/"/g, '&quot;')})">
                        <td class="px-4 py-3 text-center font-black">${medal}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-xs font-bold text-white shadow-sm">
                                    ${op.name.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-slate-900">${op.name}</div>
                                    <div class="text-[10px] text-slate-400">${(op.machines_used || []).join(', ') || '-'}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-slate-600 font-semibold">${op.days_worked}</td>
                        <td class="px-4 py-3 text-center text-sm text-slate-600 font-semibold">${op.wo_count}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="text-sm font-bold text-slate-900">${op.total_output.toLocaleString()}</div>
                            <div class="text-[10px] text-slate-400">${op.avg_output_per_day}/hari</div>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-slate-500">${op.total_target.toLocaleString()}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-1 text-xs font-bold rounded-lg ${effColor}">${op.efficiency}%</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-sm font-bold ${ngColor}">${op.ng_rate}%</span>
                            <div class="text-[10px] text-slate-400">${op.total_ng} pcs</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="text-sm font-semibold text-slate-700">${op.total_downtime_minutes} mnt</div>
                            <div class="text-[10px] text-slate-400">${op.downtime_count}x (avg ${op.avg_downtime_per_day}/hari)</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="text-sm font-semibold text-slate-700">${op.qdc_count}x</div>
                            <div class="text-[10px] text-slate-400">avg ${avgQdcFormatted}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="inline-flex items-center gap-1.5">
                                <div class="h-2.5 w-20 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full ${scoreColor} rounded-full transition-all" style="width: ${Math.min(op.score, 100)}%"></div>
                                </div>
                                <span class="text-xs font-black text-slate-700">${op.score}</span>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function showDetail(op) {
            const panel = document.getElementById('detailPanel');
            const title = document.getElementById('detailTitle');
            const content = document.getElementById('detailContent');

            panel.classList.remove('hidden');
            title.textContent = `Detail: ${op.name}`;

            const reasons = op.downtime_reasons || {};
            const reasonRows = Object.entries(reasons).sort((a,b) => b[1] - a[1]).map(([reason, minutes]) => {
                const pct = op.total_downtime_minutes > 0 ? Math.round((minutes / op.total_downtime_minutes) * 100) : 0;
                return `
                    <div class="py-2">
                        <div class="flex justify-between mb-1">
                            <span class="text-sm text-slate-700 font-medium">${reason}</span>
                            <span class="text-xs text-slate-500 font-bold">${minutes} mnt (${pct}%)</span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-amber-500 rounded-full" style="width: ${pct}%"></div>
                        </div>
                    </div>
                `;
            }).join('');

            content.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Ringkasan</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between bg-slate-50 rounded-lg px-3 py-2">
                                <span class="text-sm text-slate-500">Hari Kerja</span>
                                <span class="text-sm font-bold text-slate-900">${op.days_worked} hari</span>
                            </div>
                            <div class="flex justify-between bg-slate-50 rounded-lg px-3 py-2">
                                <span class="text-sm text-slate-500">Total WO</span>
                                <span class="text-sm font-bold text-slate-900">${op.wo_count}</span>
                            </div>
                            <div class="flex justify-between bg-slate-50 rounded-lg px-3 py-2">
                                <span class="text-sm text-slate-500">Output/Hari</span>
                                <span class="text-sm font-bold text-emerald-600">${op.avg_output_per_day}</span>
                            </div>
                            <div class="flex justify-between bg-slate-50 rounded-lg px-3 py-2">
                                <span class="text-sm text-slate-500">Mesin</span>
                                <span class="text-sm font-bold text-slate-900">${(op.machines_used || []).join(', ') || '-'}</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Produksi</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between bg-emerald-50 rounded-lg px-3 py-2">
                                <span class="text-sm text-slate-500">Total OK</span>
                                <span class="text-sm font-bold text-emerald-600">${op.total_output.toLocaleString()}</span>
                            </div>
                            <div class="flex justify-between bg-rose-50 rounded-lg px-3 py-2">
                                <span class="text-sm text-slate-500">Total NG</span>
                                <span class="text-sm font-bold text-rose-600">${op.total_ng.toLocaleString()}</span>
                            </div>
                            <div class="flex justify-between bg-blue-50 rounded-lg px-3 py-2">
                                <span class="text-sm text-slate-500">Efisiensi</span>
                                <span class="text-sm font-bold text-blue-600">${op.efficiency}%</span>
                            </div>
                            <div class="flex justify-between bg-amber-50 rounded-lg px-3 py-2">
                                <span class="text-sm text-slate-500">NG Rate</span>
                                <span class="text-sm font-bold text-amber-600">${op.ng_rate}%</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Downtime Breakdown</h3>
                        ${reasonRows || '<p class="text-sm text-slate-400 italic">Tidak ada data downtime</p>'}
                    </div>
                </div>
            `;

            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        fetchKpiData();
    </script>
</x-app-layout>