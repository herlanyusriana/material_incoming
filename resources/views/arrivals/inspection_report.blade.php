<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Inspection Report</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
        .container { width: 100%; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; }
        .meta { font-size: 11px; line-height: 1.4; text-align: right; }
        .meta b { font-weight: bold; }
        .grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
        .photo-card { border: 1px solid #333; padding: 6px; height: 225px; position: relative; }
        .label { position: absolute; top: 6px; left: 6px; background: rgba(255,255,255,0.9); padding: 2px 6px; font-weight: bold; font-size: 12px; }
        .photo { width: 100%; height: 100%; object-fit: cover; }
        .empty { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #777; font-size: 12px; }
        .notes { margin-top: 10px; border: 1px solid #333; padding: 8px; }
        .badges { margin-top: 6px; }
        .badge { display: inline-block; border: 1px solid #333; padding: 2px 6px; margin-right: 6px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <div class="title">CONTAINER INSPECTION REPORT</div>
                <div style="margin-top:4px; font-size:12px;">
                    <b>Invoice</b>: {{ $arrival->invoice_no }} &nbsp; • &nbsp;
                    <b>Arrival</b>: {{ $arrival->arrival_no }} &nbsp; • &nbsp;
                    <b>Vendor</b>: {{ $arrival->vendor->vendor_name ?? '-' }}
                </div>
            </div>
            <div class="meta">
                <div><b>Status</b>: {{ strtoupper($inspection->status) }}</div>
                <div><b>Inspector</b>: {{ $inspection->inspector->name ?? '-' }}</div>
                <div><b>Tanggal</b>: {{ $inspection->updated_at?->format('Y-m-d H:i') ?? '-' }}</div>
                <div><b>No. Container</b>: {{ $arrival->container_numbers ?? '-' }}</div>
            </div>
        </div>

        <div class="grid">
            @php
                $cards = [
                    'left' => 'Kiri (Left)',
                    'right' => 'Kanan (Right)',
                    'front' => 'Depan (Front)',
                    'back' => 'Belakang (Back)',
                ];
            @endphp

            @foreach ($cards as $key => $label)
                <div class="photo-card">
                    <div class="label">{{ $label }}</div>
                    @if (!empty($photos[$key]))
                        <img class="photo" src="{{ $photos[$key] }}" alt="{{ $label }}">
                    @else
                        <div class="empty">Foto belum tersedia</div>
                    @endif
                </div>
            @endforeach

            <div class="photo-card">
                <div class="label">Catatan</div>
                <div style="padding-top: 26px; font-size: 11px; line-height: 1.4;">
                    {{ $inspection->notes ?: '-' }}
                    <div class="badges">
                        @foreach (['issues_left' => 'Left', 'issues_right' => 'Right', 'issues_front' => 'Front', 'issues_back' => 'Back'] as $field => $side)
                            @php $issues = $inspection->{$field} ?? []; @endphp
                            @if (!empty($issues))
                                <div style="margin-top:6px;">
                                    <b>{{ $side }}</b>:
                                    @foreach ($issues as $issue)
                                        <span class="badge">{{ strtoupper($issue) }}</span>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

