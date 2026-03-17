<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Operator KPI Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 10px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 3px solid #1e293b; padding-bottom: 12px; }
        .header h1 { font-size: 18px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; }
        .header p { font-size: 11px; color: #64748b; margin-top: 4px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table th { background: #1e293b; color: white; padding: 6px 8px; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
        table td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
        table tr:nth-child(even) { background: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }

        .overview-box { display: inline-block; width: 18%; border: 1px solid #e2e8f0; padding: 10px; margin-right: 1%; vertical-align: top; }
        .overview-box .label { font-size: 8px; text-transform: uppercase; color: #64748b; letter-spacing: 1px; }
        .overview-box .value { font-size: 18px; font-weight: 900; margin-top: 2px; }

        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 8px; font-weight: 700; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-yellow { background: #fef9c3; color: #854d0e; }
        .badge-red { background: #fee2e2; color: #991b1b; }

        .bar-container { width: 60px; height: 8px; background: #f1f5f9; border-radius: 4px; display: inline-block; vertical-align: middle; }
        .bar-fill { height: 100%; border-radius: 4px; }
        .bar-green { background: #22c55e; }
        .bar-yellow { background: #eab308; }
        .bar-red { background: #ef4444; }

        .footer { margin-top: 20px; text-align: center; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }

        .summary-row { margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Operator Performance Report</h1>
        <p>{{ \Carbon\Carbon::parse($from)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</p>
    </div>

    {{-- Summary --}}
    <table style="margin-bottom: 16px;">
        <tr>
            <td style="width: 20%; padding: 4px; border: none; vertical-align: top;">
                <div class="overview-box" style="width: 100%; margin: 0;">
                    <div class="label">Operators</div>
                    <div class="value">{{ $summary['total_operators'] ?? 0 }}</div>
                </div>
            </td>
            <td style="width: 20%; padding: 4px; border: none; vertical-align: top;">
                <div class="overview-box" style="width: 100%; margin: 0;">
                    <div class="label">Total Output</div>
                    <div class="value" style="color: #059669;">{{ number_format($summary['total_output'] ?? 0) }}</div>
                </div>
            </td>
            <td style="width: 20%; padding: 4px; border: none; vertical-align: top;">
                <div class="overview-box" style="width: 100%; margin: 0;">
                    <div class="label">Total NG</div>
                    <div class="value" style="color: #dc2626;">{{ number_format($summary['total_ng'] ?? 0) }}</div>
                </div>
            </td>
            <td style="width: 20%; padding: 4px; border: none; vertical-align: top;">
                <div class="overview-box" style="width: 100%; margin: 0;">
                    <div class="label">Total Downtime</div>
                    <div class="value" style="color: #d97706;">{{ number_format($summary['total_downtime'] ?? 0) }} min</div>
                </div>
            </td>
            <td style="width: 20%; padding: 4px; border: none; vertical-align: top;">
                <div class="overview-box" style="width: 100%; margin: 0;">
                    <div class="label">Avg Efficiency</div>
                    <div class="value" style="color: #7c3aed;">{{ $summary['avg_efficiency'] ?? 0 }}%</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Ranking Table --}}
    <table>
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 14%;">Operator</th>
                <th style="width: 8%;" class="text-center">Machines</th>
                <th style="width: 5%;" class="text-center">Days</th>
                <th style="width: 5%;" class="text-center">WO</th>
                <th style="width: 9%;" class="text-right">Output</th>
                <th style="width: 9%;" class="text-right">Target</th>
                <th style="width: 9%;" class="text-center">Efficiency</th>
                <th style="width: 8%;" class="text-center">NG Rate</th>
                <th style="width: 10%;" class="text-center">Downtime</th>
                <th style="width: 7%;" class="text-center">QDC</th>
                <th style="width: 12%;" class="text-center">Score</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $op)
                @php
                    $effClass = ($op['efficiency'] ?? 0) >= 90 ? 'badge-green' : (($op['efficiency'] ?? 0) >= 70 ? 'badge-yellow' : 'badge-red');
                    $ngColor = ($op['ng_rate'] ?? 0) <= 1 ? 'color: #166534' : (($op['ng_rate'] ?? 0) <= 3 ? 'color: #854d0e' : 'color: #991b1b');
                    $scoreBarClass = ($op['score'] ?? 0) >= 80 ? 'bar-green' : (($op['score'] ?? 0) >= 50 ? 'bar-yellow' : 'bar-red');
                    $avgQdc = ($op['avg_qdc_seconds'] ?? 0) > 0
                        ? intdiv($op['avg_qdc_seconds'], 60) . ':' . str_pad($op['avg_qdc_seconds'] % 60, 2, '0', STR_PAD_LEFT)
                        : '-';
                @endphp
                <tr>
                    <td class="font-bold text-center">{{ $op['rank'] }}</td>
                    <td class="font-bold">{{ $op['name'] }}</td>
                    <td class="text-center" style="font-size: 8px;">{{ implode(', ', $op['machines_used'] ?? []) ?: '-' }}</td>
                    <td class="text-center">{{ $op['days_worked'] }}</td>
                    <td class="text-center">{{ $op['wo_count'] }}</td>
                    <td class="text-right font-bold">{{ number_format($op['total_output']) }}</td>
                    <td class="text-right">{{ number_format($op['total_target']) }}</td>
                    <td class="text-center"><span class="badge {{ $effClass }}">{{ $op['efficiency'] }}%</span></td>
                    <td class="text-center" style="{{ $ngColor }}; font-weight: 700;">{{ $op['ng_rate'] }}%</td>
                    <td class="text-center">
                        {{ $op['total_downtime_minutes'] }} min
                        <span style="color: #94a3b8; font-size: 8px;">({{ $op['downtime_count'] }}x)</span>
                    </td>
                    <td class="text-center">
                        {{ $op['qdc_count'] }}x
                        <span style="color: #94a3b8; font-size: 8px;">({{ $avgQdc }})</span>
                    </td>
                    <td class="text-center">
                        <div class="bar-container">
                            <div class="bar-fill {{ $scoreBarClass }}" style="width: {{ min($op['score'], 100) }}%"></div>
                        </div>
                        <span class="font-bold" style="margin-left: 4px;">{{ $op['score'] }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center" style="padding: 20px; color: #94a3b8;">No data available</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Top 3 Detail --}}
    @foreach(array_slice($data, 0, 3) as $op)
        @if(!empty($op['downtime_reasons']))
        <div style="margin-bottom: 12px; page-break-inside: avoid;">
            <div style="background: #334155; color: white; padding: 6px 12px; font-weight: 700; font-size: 11px;">
                #{{ $op['rank'] }} {{ $op['name'] }} — Downtime Breakdown ({{ $op['total_downtime_minutes'] }} min total)
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="background: #475569;">Reason</th>
                        <th style="background: #475569;" class="text-center">Count</th>
                        <th style="background: #475569;" class="text-right">Duration (min)</th>
                        <th style="background: #475569;" class="text-right">% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $reasons = $op['downtime_reasons'];
                        arsort($reasons);
                    @endphp
                    @foreach($reasons as $reason => $minutes)
                        <tr>
                            <td>{{ $reason }}</td>
                            <td class="text-center">-</td>
                            <td class="text-right font-bold">{{ number_format($minutes) }}</td>
                            <td class="text-right">{{ $op['total_downtime_minutes'] > 0 ? round(($minutes / $op['total_downtime_minutes']) * 100, 1) : 0 }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    @endforeach

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }} | Operator Performance Report
    </div>
</body>
</html>