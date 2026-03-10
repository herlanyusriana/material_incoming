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

        .summary-grid { display: flex; gap: 12px; margin-bottom: 16px; }
        .summary-box { border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; flex: 1; }
        .summary-box h3 { font-size: 9px; text-transform: uppercase; color: #64748b; letter-spacing: 1px; margin-bottom: 6px; }
        .summary-box .big { font-size: 20px; font-weight: 900; }
        .summary-box .big.red { color: #dc2626; }
        .summary-box .big.orange { color: #ea580c; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table th { background: #1e293b; color: white; padding: 6px 8px; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; }
        table td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; font-size: 9px; }
        table tr:nth-child(even) { background: #f8fafc; }
        table.summary-table { width: auto; min-width: 300px; }
        table.summary-table th { background: #334155; }
        table.summary-table td:last-child { text-align: right; font-weight: 700; }

        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 8px; font-weight: 700; }
        .badge-wo { background: #f1f5f9; color: #475569; }
        .badge-app { background: #eef2ff; color: #4338ca; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: monospace; }
        .font-bold { font-weight: 700; }
        .section-title { font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; color: #1e293b; margin: 16px 0 8px; border-bottom: 2px solid #e2e8f0; padding-bottom: 4px; }
        .two-col { width: 100%; }
        .two-col td { vertical-align: top; padding: 0; }
        .two-col td:first-child { padding-right: 12px; width: 50%; }
        .two-col td:last-child { padding-left: 12px; width: 50%; }
        .footer { margin-top: 20px; text-align: center; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>QDC Downtime Report</h1>
        <p>{{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}</p>
    </div>

    <div class="filters">
        <strong>Filters:</strong>
        @if($machineName) <span>Machine: {{ $machineName }}</span> @endif
        @if($category) <span>Category: {{ ucfirst($category) }}</span> @endif
        @if($source !== 'all') <span>Source: {{ $source === 'wo' ? 'Work Order' : 'Operator App' }}</span> @endif
        @if(!$machineName && !$category && $source === 'all') <span>All data</span> @endif
    </div>

    {{-- Summary boxes using table layout for dompdf compatibility --}}
    <table style="width: 100%; margin-bottom: 16px;">
        <tr>
            <td style="width: 50%; padding-right: 6px; vertical-align: top;">
                <table style="width: 100%; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <tr><td style="padding: 10px; border: none;">
                        <div style="font-size: 9px; text-transform: uppercase; color: #64748b; letter-spacing: 1px;">Total Downtime Events</div>
                        <div style="font-size: 22px; font-weight: 900; color: #ea580c;">{{ $totalCount }}x</div>
                    </td></tr>
                </table>
            </td>
            <td style="width: 50%; padding-left: 6px; vertical-align: top;">
                <table style="width: 100%; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <tr><td style="padding: 10px; border: none;">
                        <div style="font-size: 9px; text-transform: uppercase; color: #64748b; letter-spacing: 1px;">Total Downtime Duration</div>
                        <div style="font-size: 22px; font-weight: 900; color: #dc2626;">{{ number_format($totalMinutes) }} menit</div>
                        <div style="font-size: 9px; color: #94a3b8;">({{ number_format($totalMinutes / 60, 1) }} jam)</div>
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Summary tables --}}
    <table class="two-col" style="border: none;">
        <tr>
            <td style="border: none;">
                <div class="section-title">By Category</div>
                <table class="summary-table" style="width: 100%;">
                    <thead><tr><th>Category</th><th>Count</th><th>Duration (min)</th></tr></thead>
                    <tbody>
                        @foreach($byCategory as $row)
                            <tr>
                                <td>{{ strtoupper($row->category) }}</td>
                                <td class="text-center">{{ $row->count }}x</td>
                                <td class="text-right">{{ number_format($row->total_minutes) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
            <td style="border: none;">
                <div class="section-title">By Machine</div>
                <table class="summary-table" style="width: 100%;">
                    <thead><tr><th>Machine</th><th>Count</th><th>Duration (min)</th></tr></thead>
                    <tbody>
                        @foreach($byMachine as $row)
                            <tr>
                                <td>{{ $row->machine }}</td>
                                <td class="text-center">{{ $row->count }}x</td>
                                <td class="text-right">{{ number_format($row->total_minutes) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    {{-- Detail table --}}
    <div class="section-title">Detail Records</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Source</th>
                <th>Machine</th>
                <th>WO / Shift</th>
                <th>Category</th>
                <th>Start</th>
                <th>End</th>
                <th>Duration</th>
                <th>Notes</th>
                <th>Operator</th>
            </tr>
        </thead>
        <tbody>
            @forelse($downtimes as $dt)
                <tr>
                    <td class="font-mono">{{ $dt->date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $dt->source === 'app' ? 'badge-app' : 'badge-wo' }}">
                            {{ strtoupper($dt->source) }}
                        </span>
                    </td>
                    <td>{{ $dt->machine_name }}</td>
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
            @empty
                <tr><td colspan="10" class="text-center" style="padding: 20px;">No records found</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }} | QDC History Report
    </div>
</body>
</html>
