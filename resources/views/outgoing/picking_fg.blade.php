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
                        <div class="text-2xl md:text-3xl font-black text-slate-900">Picking FG</div>
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
                    <form method="POST" action="{{ route('outgoing.picking-fg.generate') }}" class="inline">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
                        <button type="submit"
                            class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Sync from Delivery Plan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Stats --}}
        @if($rows->isNotEmpty())
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="stat-card">
                    <div class="stat-value text-slate-900">{{ $stats->total }}</div>
                    <div class="stat-label">Total Parts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value text-slate-400">{{ $stats->pending }}</div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value text-amber-600">{{ $stats->picking }}</div>
                    <div class="stat-label">Picking</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value text-green-700">{{ $stats->completed }}</div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value text-indigo-700">{{ number_format($stats->total_qty) }}</div>
                    <div class="stat-label">Total Qty Plan</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value text-emerald-700">{{ number_format($stats->total_picked) }}</div>
                    <div class="stat-label">Total Picked</div>
                </div>
            </div>
        @endif

        {{-- Pick Table --}}
        @if($rows->isEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
                <div class="text-slate-400 text-sm italic">No picking items for this date.</div>
                <div class="mt-2 text-xs text-slate-400">Use "Sync from Delivery Plan" to generate picking list, or select a
                    different date.</div>
            </div>
        @else
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 w-12">
                                    No</th>
                                <th class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Status</th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 min-w-[120px]">
                                    Sales Order</th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 min-w-[120px]">
                                    Part No.</th>
                                <th
                                    class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 min-w-[180px]">
                                    Part Name</th>
                                <th class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Model</th>
                                <th class="px-3 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Stock<br>On Hand</th>
                                <th class="px-3 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Qty<br>Plan</th>
                                <th
                                    class="px-3 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50">
                                    Qty<br>Picked</th>
                                <th class="px-3 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Remaining</th>
                                <th
                                    class="px-3 py-3 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500 min-w-[120px]">
                                    Progress</th>
                                <th class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Location</th>
                                <th class="px-3 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Picker</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $idx => $row)
                                @php
                                    $rowKey = $row->id;
                                @endphp
                                <tr class="pick-row status-{{ $row->status }} border-b border-slate-100"
                                    id="row-{{ $rowKey }}" data-part-id="{{ $row->gci_part_id }}" data-source="{{ $row->source }}"
                                    data-sales-order-id="{{ $row->sales_order_id }}"
                                    data-qty-plan="{{ $row->qty_plan }}">
                                    <td class="px-3 py-3 text-slate-500 font-semibold">{{ $idx + 1 }}</td>
                                    <td class="px-3 py-3 flex items-center gap-2">
                                        <span class="status-badge {{ $row->status }}" id="status-badge-{{ $rowKey }}">
                                            @if($row->status === 'completed')
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                            {{ ucfirst($row->status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3">
                                        @if($row->so_no)
                                            <div class="font-bold text-slate-900">{{ $row->so_no }}</div>
                                            @if($row->trip_no)
                                                <div class="text-[10px] font-black text-orange-600 uppercase">Trip {{ $row->trip_no }}</div>
                                            @endif
                                        @elseif($row->source === 'po')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700">
                                                PO: {{ $row->po_no ?? 'N/A' }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 italic">Daily Plan</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-indigo-700 font-mono font-bold whitespace-nowrap">{{ $row->part_no }}
                                    </td>
                                    <td class="px-3 py-3 text-slate-900 font-bold">{{ $row->part_name }}</td>
                                    <td class="px-3 py-3 text-slate-600">{{ $row->model }}</td>
                                    <td
                                        class="px-3 py-3 text-right font-semibold {{ $row->stock_on_hand < $row->qty_plan ? 'text-red-600' : 'text-slate-700' }}">
                                        {{ number_format($row->stock_on_hand) }}
                                    </td>
                                    <td class="px-3 py-3 text-right font-bold text-slate-900">{{ number_format($row->qty_plan) }}
                                    </td>
                                    <td class="px-3 py-3 text-center bg-emerald-50/50">
                                        <input type="number" min="0" max="{{ $row->qty_plan }}" value="{{ $row->qty_picked }}"
                                            class="pick-input" data-row-key="{{ $rowKey }}" data-part-id="{{ $row->gci_part_id }}" 
                                            data-source="{{ $row->source }}" data-sales-order-id="{{ $row->sales_order_id }}" 
                                            onchange="savePick(this)" id="pick-input-{{ $rowKey }}">
                                    </td>
                                    <td class="px-3 py-3 text-right font-bold" id="remaining-{{ $rowKey }}">
                                        <span class="{{ $row->qty_remaining > 0 ? 'text-amber-600' : 'text-green-700' }}">
                                            {{ number_format($row->qty_remaining) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="progress-bar-bg flex-1 min-w-[60px]">
                                                <div class="progress-bar-fill" id="progress-{{ $rowKey }}"
                                                    style="width: {{ $row->progress_percent }}%; background: {{ $row->progress_percent >= 100 ? '#16a34a' : ($row->progress_percent > 0 ? '#f59e0b' : '#e2e8f0') }};">
                                                </div>
                                            </div>
                                            <span class="text-[10px] font-bold text-slate-500 w-10 tabular-nums"
                                                id="pct-{{ $rowKey }}">{{ $row->progress_percent }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <input type="text" value="{{ $row->pick_location }}" placeholder="Loc..."
                                            class="border border-slate-200 rounded-lg px-2 py-1 text-xs w-20 focus:outline-none focus:border-indigo-400"
                                            data-part-id="{{ $row->gci_part_id }}" data-source="{{ $rowSource }}"
                                            onchange="savePick(document.getElementById('pick-input-{{ $rowKey }}'))"
                                            id="loc-{{ $rowKey }}">
                                    </td>
                                    <td class="px-3 py-3 text-slate-500 text-[10px]">
                                        <span id="picker-{{ $rowKey }}">{{ $row->picked_by_name ?? '-' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer --}}
                <div class="border-t border-slate-200 p-4 bg-slate-50">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="text-sm text-slate-500">
                            <span class="font-semibold">Tip:</span> Input qty picked langsung di kolom hijau.
                            Data tersimpan otomatis. Status berubah otomatis berdasarkan qty.
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
                                    ✓ Complete All
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
        const DELIVERY_DATE = '{{ $selectedDate->toDateString() }}';

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
    </script>
@endsection