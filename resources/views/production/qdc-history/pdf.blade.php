<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>QDC History Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 10px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 3px solid #1e293b; padding-bottom: 12px; }
        .header h1 { font-size: 18px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; }
        .header p { font-size: 11px; color: #64748b; margin-top: 4px; }
        .filters { font-size: 9px; color: #64748b; margin-bottom: 12px; }
        .filters span { background: #f1f5f9; padding: 2px 6px; border-radius: 3px; margin-right: 6px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table th { background: #1e293b; color: white; padding: 5px 6px; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
        table td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
        table tr:nth-child(even) { background: #f8fafc; }
        table.summary-table th { background: #334155; }
        table.summary-table td:last-child { text-align: right; font-weight: 700; }

        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 8px; font-weight: 700; }
        .badge-wo { background: #f1f5f9; color: #475569; }
        .badge-app { background: #eef2ff; color: #4338ca; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: monospace; }
        .font-bold { font-weight: 700; }

        .machine-section { page-break-inside: avoid; margin-bottom: 24px; }
        .machine-header { background: #1e293b; color: white; padding: 10px 14px; margin-bottom: 8px; }
        .machine-header h2 { font-size: 14px; font-weight: 900; display: inline; }
        .machine-header .stats { float: right; font-size: 11px; }
        .machine-header .stats span { margin-left: 16px; }

        .footer { margin-top: 20px; text-align: center; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
        .page-break { page-break-before: always; }

        .overview-box { border: 1px solid #e2e8f0; padding: 10px; margin-bottom: 6px; }
        .overview-box .label { font-size: 9px; text-transform: uppercase; color: #64748b; letter-spacing: 1px; }
        .overview-box .value { font-size: 20px; font-weight: 900; }
        .overview-box .value.red { color: #dc2626; }
        .overview-box .value.orange { color: #ea580c; }
    </style>
</head>
<body>
    <div class="header">
        <h1>QDC Downtime Report</h1>
        <p>{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
    </div>

    <div class="filters">
        <strong>Filters:</strong>
        @if($machineName) <span>Machine: {{ $machineName }}</span> @endif
        @if($category) <span>Category: {{ ucfirst($category) }}</span> @endif
        @if($source !== 'all') <span>Source: {{ $source === 'wo' ? 'Work Order' : 'Operator App' }}</span> @endif
        @if(!$machineName && !$category && $source === 'all') <span>All data</span> @endif
    </div>

    {{-- Overall summary --}}
    <table style="width: 100%; margin-bottom: 16px;">
        <tr>
            <td style="width: 25%; padding-right: 6px; vertical-align: top; border: none;">
                <div class="overview-box">
                    <div class="label">Total Machines</div>
                    <div class="value">{{ $machineGroups->count() }}</div>
                </div>
            </td>
            <td style="width: 25%; padding: 0 3px; vertical-align: top; border: none;">
                <div class="overview-box">
                    <div class="label">Total Events</div>
                    <div class="value orange">{{ $totalCount }}x</div>
                </div>
            </td>
            <td style="width: 25%; padding: 0 3px; vertical-align: top; border: none;">
                <div class="overview-box">
                    <div class="label">Total Downtime</div>
                    <div class="value red">{{ number_format($totalMinutes) }} min</div>
                </div>
            </td>
            <td style="width: 25%; padding-left: 6px; vertical-align: top; border: none;">
                <div class="overview-box">
                    <div class="label">Total Hours</div>
                    <div class="value red">{{ number_format($totalMinutes / 60, 1) }} hrs</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Machine overview table --}}
    <table class="summary-table">
        <thead>
            <tr>
                <th style="width: 40%;">Machine</th>
                <th style="text-align: center;">Events</th>
                <th style="text-align: right;">Total (min)</th>
                <th style="text-align: right;">Total (hrs)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($machineGroups as $group)
                <tr>
                    <td style="font-weight: 700;">{{ $group->machine_name }}</td>
                    <td style="text-align: center;">{{ $group->count }}x</td>
                    <td style="text-align: right;">{{ number_format($group->total_minutes) }}</td>
                    <td style="text-align: right;">{{ number_format($group->total_minutes / 60, 1) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Per-machine sections --}}
    @foreach($machineGroups as $idx => $group)
        <div class="{{ $idx > 0 ? 'page-break' : '' }}">
            <div class="machine-header">
                <h2>{{ $group->machine_name }}</h2>
                <div class="stats">
                    <span>{{ $group->count }}x stop</span>
                    <span>{{ number_format($group->total_minutes) }} min downtime</span>
                </div>
            </div>

            {{-- Category breakdown for this machine --}}
            @if($group->by_category->count() > 1)
                <table class="summary-table" style="width: 50%; margin-bottom: 8px;">
                    <thead><tr><th>Category</th><th style="text-align: center;">Count</th><th>Duration (min)</th></tr></thead>
                    <tbody>
                        @foreach($group->by_category as $row)
                            <tr>
                                <td>{{ strtoupper($row->category) }}</td>
                                <td style="text-align: center;">{{ $row->count }}x</td>
                                <td class="text-right">{{ number_format($row->total_minutes) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Detail records for this machine --}}
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Source</th>
                        <th>WO / Shift</th>
                        <th>Category</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Duration (min)</th>
                        <th>Notes</th>
                        <th>Operator</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group->downtimes as $dt)
                        <tr>
                            <td class="font-mono">{{ $dt->date?->format('d/m/Y') ?? '-' }}</td>
                            <td class="text-center">
                                <span class="badge {{ $dt->source === 'app' ? 'badge-app' : 'badge-wo' }}">
                                    {{ strtoupper($dt->source) }}
                                </span>
                            </td>
                            <td>
                                @if($dt->wo_no) {{ $dt->wo_no }}
                                @elseif($dt->shift) {{ $dt->shift }}
                                @else - @endif
                            </td>
                            <td>{{ strtoupper($dt->category) }}</td>
                            <td class="font-mono text-center">
                                @if($dt->source === 'app' && $dt->start_time)
                                    {{ \Carbon\Carbon::parse($dt->start_time)->format('H:i') }}
                                @else
                                    {{ $dt->start_time ?? '-' }}
                                @endif
                            </td>
                            <td class="font-mono text-center">
                                @if($dt->source === 'app' && $dt->end_time)
                                    {{ \Carbon\Carbon::parse($dt->end_time)->format('H:i') }}
                                @else
                                    {{ $dt->end_time ?? '-' }}
                                @endif
                            </td>
                            <td class="text-right font-bold">
                                {{ $dt->duration_minutes !== null ? number_format($dt->duration_minutes) : '-' }}
                            </td>
                            <td style="max-width: 120px; overflow: hidden;">{{ Str::limit($dt->notes, 40) ?? '-' }}</td>
                            <td>{{ $dt->operator }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }} | QDC Downtime Report
    </div>
</body>
</html>
