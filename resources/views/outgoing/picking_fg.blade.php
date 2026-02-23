@extends('outgoing.layout')

@section('content')
    <style>
        .pick-row {
            transition: background 0.2s;
        }

        .pick-row:hover {
            background: #f8fafc;
        }

        .pick-row.status-completed {
            background: #f0fdf4;
        }

        .pick-row.status-picking {
            background: #fffbeb;
        }

        .progress-bar-bg {
            height: 6px;
            border-radius: 3px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 16px 20px;
            text-align: center;
        }

        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 900;
            line-height: 1;
        }

        .stat-card .stat-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #94a3b8;
            margin-top: 4px;
        }

        .pick-input {
            width: 80px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 8px;
            text-align: center;
            font-weight: 700;
            font-size: 13px;
            transition: border-color 0.15s, box-shadow 0.15s;
            -moz-appearance: textfield;
        }

        .pick-input::-webkit-outer-spin-button,
        .pick-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .pick-input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .status-badge.pending {
            background: #f1f5f9;
            color: #64748b;
        }

        .status-badge.picking {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.completed {
            background: #dcfce7;
            color: #166534;
        }

        .filter-btn {
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid #e2e8f0;
            background: white;
            color: #64748b;
            cursor: pointer;
            transition: all 0.15s;
        }
        .filter-btn:hover { background: #f8fafc; }
        .filter-btn.active { background: #6366f1; color: white; border-color: #6366f1; }

        .sync-pulse {
            animation: pulse 1s ease-in-out;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>

    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex items-start gap-3">
                    <div
                        class="h-12 w-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-black text-sm shadow-lg">
                        PK
                    </div>
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="text-2xl md:text-3xl font-black text-slate-900">Picking FG</div>
                            <span id="live-indicator" class="hidden items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-[10px] font-bold border border-green-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span> LIVE
                            </span>
                        </div>
                        <div class="mt-1 text-sm text-slate-600">
                            Delivery Date: <span
                                class="font-bold text-slate-900">{{ $selectedDate->format('d M Y') }}</span>
                            <span class="text-slate-400 mx-1">•</span>
                            <span class="text-xs text-slate-500">{{ $selectedDate->translatedFormat('l') }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-end gap-2">
                    <form method="GET" action="{{ route('outgoing.picking-fg') }}" class="flex items-end gap-2">
                        <div>
                            <div class="text-xs font-semibold text-slate-500 mb-1">Delivery Date</div>
                            <input type="date" name="date" value="{{ $selectedDate->toDateString() }}"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">
                        </div>
                        <button type="submit"
                            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white hover:bg-slate-800">View</button>
                    </form>
                    <form method="POST" action="{{ route('outgoing.picking-fg.clear') }}" onsubmit="return confirm('Are you sure you want to CLEAR all generated data for this date? This cannot be undone.');">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
                        <button type="submit" class="rounded-xl bg-red-100 px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-200 border border-red-200 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Clear Data
                        </button>
                    </form>
                    <a href="{{ route('outgoing.picking-fg', ['date' => $selectedDate->toDateString()]) }}"
                        class="rounded-xl bg-slate-600 px-4 py-2 text-sm font-bold text-white hover:bg-slate-700 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </a>
                </div>
            </div>

            {{-- Filter & Search Bar --}}
            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Filter:</span>
                    <a href="{{ route('outgoing.picking-fg', ['date' => $selectedDate->toDateString()]) }}"
                        class="filter-btn {{ !isset($filterStatus) || !$filterStatus ? 'active' : '' }}">All</a>
                    <a href="{{ route('outgoing.picking-fg', ['date' => $selectedDate->toDateString(), 'status' => 'pending', 'search' => $filterSearch ?? '']) }}"
                        class="filter-btn {{ ($filterStatus ?? '') === 'pending' ? 'active' : '' }}">Pending</a>
                    <a href="{{ route('outgoing.picking-fg', ['date' => $selectedDate->toDateString(), 'status' => 'picking', 'search' => $filterSearch ?? '']) }}"
                        class="filter-btn {{ ($filterStatus ?? '') === 'picking' ? 'active' : '' }}">Picking</a>
                    <a href="{{ route('outgoing.picking-fg', ['date' => $selectedDate->toDateString(), 'status' => 'completed', 'search' => $filterSearch ?? '']) }}"
                        class="filter-btn {{ ($filterStatus ?? '') === 'completed' ? 'active' : '' }}">Completed</a>
                </div>
                <form method="GET" action="{{ route('outgoing.picking-fg') }}" class="flex items-center gap-2 flex-1 max-w-sm">
                    <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
                    @if($filterStatus ?? '')
                        <input type="hidden" name="status" value="{{ $filterStatus }}">
                    @endif
                    <div class="relative flex-1">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" name="search" value="{{ $filterSearch ?? '' }}" placeholder="Search DO / Part..."
                            class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-sm font-medium focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                    </div>
                    <button type="submit" class="rounded-xl bg-slate-800 px-3 py-2 text-sm font-bold text-white hover:bg-slate-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
            <div class="stat-card">
                <div class="stat-value text-indigo-700" id="stat-total-do">{{ $stats->total_so }}</div>
                <div class="stat-label">Total DO</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-slate-900" id="stat-total-parts">{{ $stats->total_parts }}</div>
                <div class="stat-label">Total Parts</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-slate-400" id="stat-pending">{{ $stats->pending }}</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-amber-600" id="stat-picking">{{ $stats->picking }}</div>
                <div class="stat-label">Picking</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-green-700" id="stat-completed">{{ $stats->completed }}</div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-indigo-700 text-xl" id="stat-total-qty">{{ number_format($stats->total_qty) }}</div>
                <div class="stat-label">Plan Qty</div>
            </div>
            <div class="stat-card">
                <div class="stat-value text-emerald-700 text-xl" id="stat-total-picked">{{ number_format($stats->total_picked) }}</div>
                <div class="stat-label">Picked</div>
            </div>
        </div>

        {{-- DO Lists --}}
        @if($soList->isEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
                <div class="text-slate-400 text-sm italic">
                    @if($filterStatus || $filterSearch)
                        No items match your filter.
                        <a href="{{ route('outgoing.picking-fg', ['date' => $selectedDate->toDateString()]) }}" class="text-indigo-600 underline ml-1">Clear filters</a>
                    @else
                        No picking items for this date.
                    @endif
                </div>
                @if(!$filterStatus && !$filterSearch)
                    <div class="mt-2 text-xs text-slate-400">Generate DOs from the "Delivery Plan" page to start picking.</div>
                @endif
            </div>
        @else
            <div class="space-y-6">
                @foreach($soList as $so)
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden" data-so-id="{{ $so->so_id }}">
                        {{-- DO Header Card --}}
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-md">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-lg font-black text-slate-900">
                                                {{ $so->so_no ?? ($so->source === 'po' ? $so->po_no : 'DAILY PLAN') }}
                                            </span>
                                            @if($so->trip_no)
                                                <span class="px-2 py-0.5 rounded bg-orange-100 text-orange-700 text-[10px] font-black border border-orange-200 uppercase">
                                                    Trip {{ $so->trip_no }}
                                                </span>
                                            @endif
                                            <span class="status-badge {{ $so->status }} text-[10px]">
                                                {{ ucfirst($so->status) }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-slate-500 font-medium">
                                            @if($so->so_no)
                                                Delivery Order Identity <span class="text-slate-300 mx-1">•</span>
                                            @endif
                                            {{ $so->items_count }} Parts to pick
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-6">
                                    <div class="text-right">
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Plan Qty</div>
                                        <div class="text-lg font-black text-slate-800">{{ number_format($so->qty_plan_total) }}</div>
                                    </div>
                                    
                                    @if($so->status === 'completed')
                                        <a href="{{ route('outgoing.delivery-notes.create', ['sales_order_ids' => $so->so_id]) }}" 
                                           class="px-4 py-2 bg-indigo-600 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all active:scale-95 flex items-center gap-2">
                                           <span>Create Delivery</span>
                                           <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                        </a>
                                    @else
                                        <div class="w-32">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-[10px] font-bold text-slate-500 uppercase">Progress</span>
                                                <span class="text-[10px] font-black text-indigo-600" id="so-pct-{{ $so->so_id }}">{{ $so->progress_percent }}%</span>
                                            </div>
                                            <div class="progress-bar-bg">
                                                <div class="progress-bar-fill" id="so-progress-{{ $so->so_id }}" style="width: {{ $so->progress_percent }}%; background: {{ $so->progress_percent >= 100 ? '#16a34a' : ($so->progress_percent > 0 ? '#6366f1' : '#e2e8f0') }};"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Parts Table for this SO --}}
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-100">
                                        <th class="px-4 py-2 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400 w-10">No</th>
                                        <th class="px-4 py-2 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Status</th>
                                        <th class="px-4 py-2 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Part Info</th>
                                        <th class="px-4 py-2 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Plan</th>
                                        <th class="px-4 py-2 text-center text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50/50">Picked</th>
                                        <th class="px-4 py-2 text-right text-[10px] font-bold uppercase tracking-wider text-slate-400">Rem.</th>
                                        <th class="px-4 py-2 text-center text-[10px] font-bold uppercase tracking-wider text-slate-400 w-32">Progress</th>
                                        <th class="px-4 py-2 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Location</th>
                                        <th class="px-4 py-2 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">Picker</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($so->rows as $ridx => $row)
                                        @php $rowKey = $row->id; @endphp
                                        <tr class="pick-row status-{{ $row->status }} border-b border-slate-50" id="row-{{ $rowKey }}" data-qty-plan="{{ $row->qty_plan }}">
                                            <td class="px-4 py-3 text-slate-400 font-medium text-center">{{ $ridx + 1 }}</td>
                                            <td class="px-4 py-3">
                                                <span class="status-badge {{ $row->status }}" id="status-badge-{{ $rowKey }}" style="font-size: 9px; padding: 2px 8px;">
                                                    {{ ucfirst($row->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-mono font-bold text-indigo-700">{{ $row->part_no }}</div>
                                                <div class="text-[10px] font-bold text-slate-900 truncate max-w-[200px]">{{ $row->part_name }}</div>
                                                <div class="text-[9px] text-slate-500 uppercase font-bold">{{ $row->model }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-right font-bold text-slate-900">{{ number_format($row->qty_plan) }}</td>
                                            <td class="px-4 py-3 text-center bg-emerald-50/30">
                                                <input type="number" min="0" max="{{ $row->qty_plan }}" value="{{ $row->qty_picked }}"
                                                    class="pick-input" data-row-key="{{ $rowKey }}" data-part-id="{{ $row->gci_part_id }}"
                                                    data-source="{{ $row->source }}" data-sales-order-id="{{ $row->sales_order_id }}"
                                                    onchange="savePick(this)" id="pick-input-{{ $rowKey }}">
                                            </td>
                                            <td class="px-4 py-3 text-right font-bold" id="remaining-{{ $rowKey }}">
                                                <span class="{{ $row->qty_remaining > 0 ? 'text-amber-600' : 'text-green-700' }}">
                                                    {{ number_format($row->qty_remaining) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <div class="progress-bar-bg flex-1">
                                                        <div class="progress-bar-fill" id="progress-{{ $rowKey }}"
                                                            style="width: {{ $row->progress_percent }}%; background: {{ $row->progress_percent >= 100 ? '#16a34a' : ($row->progress_percent > 0 ? '#f59e0b' : '#e2e8f0') }};">
                                                        </div>
                                                    </div>
                                                    <span class="text-[9px] font-black text-slate-500 w-8 tabular-nums" id="pct-{{ $rowKey }}">{{ $row->progress_percent }}%</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="text" value="{{ $row->pick_location }}" placeholder="Loc..."
                                                    class="border border-slate-200 rounded-lg px-2 py-1 text-[11px] w-20 focus:outline-none focus:border-indigo-400"
                                                    onchange="savePick(document.getElementById('pick-input-{{ $rowKey }}'))"
                                                    id="loc-{{ $rowKey }}">
                                            </td>
                                            <td class="px-4 py-3 text-slate-500 text-[10px] font-medium">
                                                <span id="picker-{{ $rowKey }}">{{ $row->picked_by_name ?? '-' }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
                        {{-- Footer --}}
                <div class="border-t border-slate-200 p-4 bg-slate-50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="text-sm text-slate-500">
                            <span class="font-semibold">Tip:</span> Input qty picked langsung di kolom hijau.
                            Data tersimpan otomatis. Auto-refresh setiap 15 detik.
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold">
                                <span class="w-3 h-3 rounded-full bg-slate-200"></span> Pending
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold">
                                <span class="w-3 h-3 rounded-full bg-amber-300"></span> Picking
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold">
                                <span class="w-3 h-3 rounded-full bg-green-400"></span> Completed
                            </span>
                            <form method="POST" action="{{ route('outgoing.picking-fg.complete-all') }}" class="inline ml-2"
                                onsubmit="return confirm('Mark ALL items as completed?')">
                                @csrf
                                <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
                                <button type="submit"
                                    class="rounded-xl bg-green-600 px-4 py-2 text-xs font-bold text-white hover:bg-green-700">
                                    Complete All
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        const CSRF = '{{ csrf_token() }}';
        const UPDATE_URL = '{{ route("outgoing.picking-fg.update-pick") }}';
        const STATUS_URL = '{{ route("outgoing.picking-fg.status") }}';
        const DELIVERY_DATE = '{{ $selectedDate->toDateString() }}';
        let lastUpdated = null;
        let pollInterval = null;

        async function savePick(input) {
            const rowKey = input.dataset.rowKey;
            const partId = input.dataset.partId;
            const source = input.dataset.source || 'daily_plan';
            const soId = input.dataset.salesOrderId || null;
            const qtyPicked = parseInt(input.value) || 0;
            const qtyPlan = parseInt(document.getElementById(`row-${rowKey}`).dataset.qtyPlan) || 0;
            const location = document.getElementById(`loc-${rowKey}`)?.value || '';

            input.style.background = '#fef9c3';

            try {
                const res = await fetch(UPDATE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        delivery_date: DELIVERY_DATE,
                        gci_part_id: partId,
                        source: source,
                        sales_order_id: soId,
                        qty_picked: qtyPicked,
                        pick_location: location,
                    })
                });

                const data = await res.json();

                if (data.success) {
                    input.style.background = '#dcfce7';
                    updateRowUI(rowKey, data);
                    setTimeout(() => { input.style.background = ''; }, 800);
                } else {
                    input.style.background = '#fee2e2';
                    setTimeout(() => { input.style.background = ''; }, 1500);
                }
            } catch (e) {
                console.error('Save failed:', e);
                input.style.background = '#fee2e2';
                setTimeout(() => { input.style.background = ''; }, 1500);
            }
        }

        function updateRowUI(rowKey, data) {
            // Update remaining
            const remEl = document.getElementById(`remaining-${rowKey}`);
            if (remEl) {
                const span = remEl.querySelector('span');
                span.textContent = data.qty_remaining.toLocaleString();
                span.className = data.qty_remaining > 0 ? 'text-amber-600' : 'text-green-700';
            }

            // Update progress bar
            const progEl = document.getElementById(`progress-${rowKey}`);
            if (progEl) {
                progEl.style.width = data.progress_percent + '%';
                progEl.style.background = data.progress_percent >= 100 ? '#16a34a' : (data.progress_percent > 0 ? '#f59e0b' : '#e2e8f0');
            }
            const pctEl = document.getElementById(`pct-${rowKey}`);
            if (pctEl) pctEl.textContent = data.progress_percent + '%';

            // Update status badge
            const badgeEl = document.getElementById(`status-badge-${rowKey}`);
            if (badgeEl) {
                badgeEl.className = `status-badge ${data.status}`;
                badgeEl.innerHTML = data.status === 'completed'
                    ? '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg> Completed'
                    : data.status.charAt(0).toUpperCase() + data.status.slice(1);
            }

            // Update row class
            const rowEl = document.getElementById(`row-${rowKey}`);
            if (rowEl) {
                rowEl.classList.remove('status-pending', 'status-picking', 'status-completed');
                rowEl.classList.add(`status-${data.status}`);
            }
        }

        // Auto-refresh polling
        async function pollStatus() {
            try {
                const res = await fetch(`${STATUS_URL}?date=${DELIVERY_DATE}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (lastUpdated && data.last_updated && data.last_updated !== lastUpdated) {
                    // Data changed - update stats and rows
                    updateStats(data.stats);
                    updateRows(data.so_list);
                }
                lastUpdated = data.last_updated;

                // Show live indicator
                const liveEl = document.getElementById('live-indicator');
                if (liveEl) liveEl.classList.replace('hidden', 'inline-flex');
            } catch (e) {
                // Silently fail - will retry next poll
            }
        }

        function updateStats(stats) {
            const map = {
                'stat-pending': stats.pending,
                'stat-picking': stats.picking,
                'stat-completed': stats.completed,
                'stat-total-qty': stats.total_qty.toLocaleString(),
                'stat-total-picked': stats.total_picked.toLocaleString(),
            };
            for (const [id, val] of Object.entries(map)) {
                const el = document.getElementById(id);
                if (el && el.textContent != val) {
                    el.textContent = val;
                    el.classList.add('sync-pulse');
                    setTimeout(() => el.classList.remove('sync-pulse'), 1000);
                }
            }
        }

        function updateRows(soList) {
            if (!soList) return;
            soList.forEach(so => {
                // Update SO-level progress
                const soPctEl = document.getElementById(`so-pct-${so.so_id}`);
                if (soPctEl) soPctEl.textContent = so.progress_percent + '%';
                const soProgEl = document.getElementById(`so-progress-${so.so_id}`);
                if (soProgEl) {
                    soProgEl.style.width = so.progress_percent + '%';
                    soProgEl.style.background = so.progress_percent >= 100 ? '#16a34a' : (so.progress_percent > 0 ? '#6366f1' : '#e2e8f0');
                }

                // Update individual rows
                if (so.rows) {
                    so.rows.forEach(row => {
                        const rowKey = row.id;
                        const input = document.getElementById(`pick-input-${rowKey}`);
                        // Only update if input is not focused (user not editing)
                        if (input && document.activeElement !== input) {
                            if (parseInt(input.value) !== row.qty_picked) {
                                input.value = row.qty_picked;
                                updateRowUI(rowKey, row);
                            }
                        }
                        // Update picker name
                        const pickerEl = document.getElementById(`picker-${rowKey}`);
                        if (pickerEl && row.picked_by_name) pickerEl.textContent = row.picked_by_name;
                    });
                }
            });
        }

        // Start polling
        document.addEventListener('DOMContentLoaded', function() {
            pollInterval = setInterval(pollStatus, 15000);
            // Initial poll to set lastUpdated
            pollStatus();
        });
    </script>
@endsection
