@extends('outgoing.layout')

@section('content')
    <style>
        .dp * { box-sizing: border-box; }
        .dp {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            padding: 20px;
            border-radius: 16px;
        }

        .dp .container {
            max-width: 1800px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .dp .header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #fff;
            padding: 24px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .dp .header h1 { font-size: 24px; font-weight: 700; }

        .dp .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .dp .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .dp .btn-primary { background: #fff; color: #2563eb; }
        .dp .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .dp .btn-success { background: #10b981; color: #fff; }
        .dp .btn-success:hover { background: #059669; }
        .dp .btn-secondary { background: #6b7280; color: #fff; }
        .dp .btn-secondary:hover { background: #4b5563; }

        .dp .controls-panel {
            padding: 18px 20px;
            background: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            align-items: end;
        }

        .dp .form-group { display: flex; flex-direction: column; gap: 8px; }
        .dp .form-group label { font-size: 12px; font-weight: 800; color: #374151; text-transform: uppercase; letter-spacing: .02em; }
        .dp .form-group input, .dp .form-group select {
            padding: 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.2s ease;
            background: #fff;
        }
        .dp .form-group input:focus, .dp .form-group select:focus { outline: none; border-color: #3b82f6; }

        .dp .main-content { padding: 22px; }

        .dp .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 2px solid #d1d5db;
            background: #fff;
        }
        .dp table { width: 100%; border-collapse: collapse; background: #fff; }
        .dp thead { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #fff; }
        .dp thead th {
            padding: 12px 10px;
            text-align: left;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .dp tbody tr { border-bottom: 2px solid #e5e7eb; transition: background 0.2s ease; background: #fff; }
        .dp tbody tr:hover { background: #f0f9ff; }
        .dp tbody td { padding: 10px; font-size: 13px; color: #1f2937; border: 1px solid #e5e7eb; vertical-align: top; }
        .dp .sequence-cell { background: #fef3c7; text-align: center; font-weight: 800; min-width: 50px; border: 1px solid #fbbf24; }

        .dp .row-select { width: 18px; height: 18px; vertical-align: middle; }

        .dp .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 800; }
        .dp .badge-success { background: #d1fae5; color: #065f46; }
        .dp .badge-warning { background: #fef3c7; color: #92400e; }
        .dp .badge-info { background: #dbeafe; color: #1e40af; }

        .dp .muted { color: #6b7280; }
        .dp .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }

        .dp .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .dp .modal.active { display: flex; }
        .dp .modal-content {
            background: #fff;
            border-radius: 16px;
            padding: 22px;
            max-width: 640px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .dp .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .dp .modal-header h2 { font-size: 20px; color: #111827; font-weight: 900; }
        .dp .close-btn { background: none; border: none; font-size: 28px; color: #9ca3af; cursor: pointer; line-height: 1; }
        .dp .close-btn:hover { color: #374151; }
        .dp .modal-body { display: flex; flex-direction: column; gap: 14px; }

        .dp .info-card {
            background: #f9fafb;
            padding: 16px;
            border-radius: 12px;
            border-left: 4px solid #3b82f6;
        }
        .dp .info-card h3 { font-size: 14px; color: #374151; margin-bottom: 10px; font-weight: 900; }
        .dp .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .dp .info-row:last-child { border-bottom: none; }
        .dp .info-label { font-weight: 800; color: #6b7280; }
        .dp .info-value { color: #111827; font-weight: 900; }

        @media screen and (max-width: 768px) {
            .dp { padding: 12px; }
            .dp .header { flex-direction: column; align-items: stretch; text-align: center; }
            .dp .header-actions { justify-content: center; }
        }
    </style>

    @php
        $classOptions = array_values(array_unique(array_map('strval', array_keys($groups ?? []))));
        sort($classOptions);
        $rowNo = 0;
    @endphp

    <div class="dp">
        <div class="container">
            <div class="header">
                <div>
                    <h1>Delivery Plan &amp; Arrangement</h1>
                    <div class="text-sm opacity-90">Plan date: <span class="mono">{{ $selectedDate }}</span></div>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" type="button" onclick="openAssignModal()">Assign Truck &amp; Driver</button>
                    <button class="btn btn-success" type="button" onclick="exportPlan()">Export Plan</button>
                </div>
            </div>

            <form method="GET" action="{{ route('outgoing.delivery-plan') }}" class="controls-panel">
                <div class="form-group">
                    <label>Plan Date</label>
                    <input type="date" name="date" value="{{ $selectedDate }}">
                </div>
                <div class="form-group">
                    <label>Classification</label>
                    <select id="classificationFilter">
                        <option value="">All</option>
                        @foreach($classOptions as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Part</label>
                    <input type="text" id="partNameFilter" placeholder="Search part name / part no...">
                </div>
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" id="dueDateFilter" value="{{ $selectedDate }}" disabled>
                </div>
                <div class="form-group">
                    <label>Warehouse</label>
                    <select id="warehouseFilter" disabled>
                        <option value="">Select Warehouse</option>
                    </select>
                </div>
                <div class="form-group">
                    <button class="btn btn-secondary" type="submit" style="justify-content:center;">View</button>
                </div>
            </form>

            <div class="main-content">
                <div class="table-container">
                    <table id="deliveryTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Classification</th>
                                <th>Part Name</th>
                                <th>Part Number</th>
                                <th style="text-align:right;">Plan</th>
                                <th style="text-align:right;">Stock at Customer</th>
                                <th style="text-align:right;">Balance</th>
                                <th>JIG</th>
                                <th>Duedate</th>
                                <th colspan="{{ count($sequences) }}" style="text-align:center;">Delivery Sequence</th>
                                <th style="text-align:right;">Remain</th>
                                <th>Truck</th>
                                <th>Driver</th>
                                <th>Status</th>
                            </tr>
                            <tr>
                                <th colspan="9"></th>
                                @foreach($sequences as $seq)
                                    <th class="sequence-cell">{{ $seq }}</th>
                                @endforeach
                                <th colspan="4"></th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            @forelse(($groups ?? []) as $class => $items)
                                @foreach(($items ?? []) as $r)
                                    @php
                                        $rowNo++;
                                        $assignment = $r->assignment ?? null;
                                        $assignedPlan = $assignment?->plan;
                                        $status = (string) ($assignment?->status ?? 'pending');
                                        $statusBadge = match ($status) {
                                            'assigned' => '<span class="badge badge-success">Assigned</span>',
                                            'picking' => '<span class="badge badge-info">Picking</span>',
                                            'shipped' => '<span class="badge badge-success">Shipped</span>',
                                            default => '<span class="badge badge-warning">Pending</span>',
                                        };
                                    @endphp
                                    <tr
                                        data-classification="{{ (string) $class }}"
                                        data-part="{{ strtolower((string) ($r->part_name ?? '') . ' ' . (string) ($r->part_no ?? '')) }}"
                                    >
                                        <td>
                                            @if((int) ($r->gci_part_id ?? 0) > 0)
                                                <input
                                                    type="checkbox"
                                                    class="row-select"
                                                    value="{{ (int) $r->gci_part_id }}"
                                                    data-balance="{{ (float) ($r->balance ?? 0) }}"
                                                >
                                            @endif
                                            <span class="muted">#{{ $rowNo }}</span>
                                        </td>
                                        <td><span class="badge badge-info">{{ $class }}</span></td>
                                        <td>{{ $r->part_name ?? '-' }}</td>
                                        <td class="mono">{{ $r->part_no ?? '-' }}</td>
                                        <td style="text-align:right; font-weight:900;">{{ number_format((float) ($r->plan_total ?? 0), 0) }}</td>
                                        <td style="text-align:right; font-weight:900;">{{ (float) ($r->stock_at_customer ?? 0) > 0 ? number_format((float) ($r->stock_at_customer ?? 0), 0) : '-' }}</td>
                                        <td style="text-align:right; font-weight:900;">{{ number_format((float) ($r->balance ?? 0), 0) }}</td>
                                        <td>
                                            <div class="mono" style="font-weight:800;">
                                                NR1: {{ (int) ($r->jig_nr1 ?? 0) > 0 ? (int) $r->jig_nr1 : '-' }}
                                                <span class="muted">|</span>
                                                NR2: {{ (int) ($r->jig_nr2 ?? 0) > 0 ? (int) $r->jig_nr2 : '-' }}
                                            </div>
                                            <div class="muted" style="font-size:12px;">NR1 = ceil(Balance/10), NR2 = ceil(Balance/9)</div>
                                        </td>
                                        <td class="mono">{{ $r->due_date?->format('Y-m-d') }}</td>
                                        @foreach($sequences as $seq)
                                            @php($v = (float) (($r->per_seq[$seq] ?? 0) ?: 0))
                                            <td class="sequence-cell">{{ $v > 0 ? number_format($v, 0) : '' }}</td>
                                        @endforeach
                                        <td style="text-align:right; font-weight:900;">{{ number_format((float) ($r->remain ?? 0), 0) }}</td>
                                        <td class="mono">
                                            {{ $assignedPlan?->truck?->plate_no ?? '-' }}
                                        </td>
                                        <td>
                                            {{ $assignedPlan?->driver?->name ?? '-' }}
                                        </td>
                                        <td>{!! $statusBadge !!}</td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="{{ 13 + count($sequences) }}" class="muted" style="text-align:center; padding:24px;">
                                        No data for selected date.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 14px;" class="muted">
                    Tips: pilih item dulu (checkbox), lalu klik <strong>Assign Truck &amp; Driver</strong>.
                </div>

                @if(!empty($plans) && count($plans) > 0)
                    <div style="margin-top: 18px;">
                        <div style="font-weight: 900; color:#111827; margin-bottom: 8px;">Trips ({{ $selectedDate }})</div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width:70px;">Seq</th>
                                        <th>Truck</th>
                                        <th>Driver</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($plans as $p)
                                        <tr>
                                            <td class="mono" style="font-weight:900;">#{{ $p->sequence }}</td>
                                            <td class="mono">{{ $p->truck?->plate_no ?? '-' }}</td>
                                            <td>{{ $p->driver?->name ?? '-' }}</td>
                                            <td class="muted">{{ $p->status ?? 'scheduled' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="assignModal" class="modal dp">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Assign Truck &amp; Driver</h2>
                <button class="close-btn" type="button" onclick="closeModal()">&times;</button>
            </div>

            <form id="assignForm" method="POST" action="{{ route('outgoing.delivery-plan.assign-items') }}" class="modal-body">
                @csrf
                <input type="hidden" name="plan_date" value="{{ $selectedDate }}">

                <div class="info-card">
                    <h3>Delivery Information</h3>
                    <div class="info-row">
                        <span class="info-label">Selected Items</span>
                        <span class="info-value" id="selectedCount">0</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Balance</span>
                        <span class="info-value" id="totalBalance">0</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Assign To Trip</label>
                    <select id="planSelect" name="delivery_plan_id">
                        <option value="">New Trip (auto seq)</option>
                        @foreach(($plans ?? []) as $p)
                            <option value="{{ $p->id }}">Trip #{{ $p->sequence }} — {{ $p->truck?->plate_no ?? 'no truck' }} / {{ $p->driver?->name ?? 'no driver' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Select Truck</label>
                    <select id="truckSelect" name="truck_id">
                        <option value="">-- none --</option>
                        @foreach(($trucks ?? []) as $t)
                            <option value="{{ $t->id }}">{{ $t->plate_no }}{{ $t->type ? ' • ' . $t->type : '' }}{{ $t->capacity ? ' • ' . $t->capacity : '' }}{{ $t->status ? ' • ' . $t->status : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Select Driver</label>
                    <select id="driverSelect" name="driver_id">
                        <option value="">-- none --</option>
                        @foreach(($drivers ?? []) as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}{{ $d->phone ? ' • ' . $d->phone : '' }}{{ $d->license_type ? ' • ' . $d->license_type : '' }}{{ $d->status ? ' • ' . $d->status : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Warehouse Picking Location</label>
                    <select id="pickingLocation" disabled>
                        <option value="">(coming soon)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Delivery Priority</label>
                    <select id="priority" disabled>
                        <option value="normal">Normal</option>
                        <option value="urgent">Urgent</option>
                        <option value="rush">Rush</option>
                    </select>
                </div>

                <div style="display:flex; gap:10px; margin-top:6px;">
                    <button class="btn btn-success" type="submit" style="flex:1; justify-content:center;">Assign</button>
                    <button class="btn btn-secondary" type="button" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function selectedCheckboxes() {
            return Array.from(document.querySelectorAll('.row-select:checked'));
        }

        function openAssignModal() {
            const checks = selectedCheckboxes();
            if (checks.length === 0) {
                alert('Pilih minimal 1 item.');
                return;
            }

            let totalBalance = 0;
            checks.forEach(cb => {
                totalBalance += Number(cb.dataset.balance || 0);
            });

            document.getElementById('selectedCount').textContent = String(checks.length);
            document.getElementById('totalBalance').textContent = String(Math.round(totalBalance));

            const form = document.getElementById('assignForm');
            form.querySelectorAll('input[name="gci_part_ids[]"]').forEach(n => n.remove());
            checks.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'gci_part_ids[]';
                input.value = cb.value;
                form.appendChild(input);
            });

            document.getElementById('assignModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('assignModal').classList.remove('active');
        }

        function exportPlan() {
            window.print();
        }

        function applyFilters() {
            const cls = (document.getElementById('classificationFilter').value || '').toLowerCase();
            const q = (document.getElementById('partNameFilter').value || '').trim().toLowerCase();
            const rows = document.querySelectorAll('#tableBody tr[data-classification]');
            rows.forEach(r => {
                const rCls = (r.dataset.classification || '').toLowerCase();
                const rPart = (r.dataset.part || '').toLowerCase();
                const okCls = !cls || rCls === cls;
                const okQ = !q || rPart.includes(q);
                r.style.display = (okCls && okQ) ? '' : 'none';
            });
        }

        document.getElementById('classificationFilter')?.addEventListener('change', applyFilters);
        document.getElementById('partNameFilter')?.addEventListener('input', applyFilters);
    </script>
@endsection

