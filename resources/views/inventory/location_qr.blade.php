@php
    $title = trim((string) ($location->location_code ?? 'WAREHOUSE LOCATION'));
    $subtitleParts = [];
    if ($location->class) $subtitleParts[] = 'Class: ' . $location->class;
    if ($location->zone) $subtitleParts[] = 'Zone: ' . $location->zone;
    $subtitle = $subtitleParts ? implode(' • ', $subtitleParts) : '';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Location QR</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; padding: 18px; background: #f8fafc; }
        .card { background: white; border: none; border-radius: 12px; padding: 18px; max-width: 520px; margin: 0 auto; }
        .title { font-size: 28px; font-weight: 800; letter-spacing: 1px; text-align: center; }
        .subtitle { margin-top: 6px; font-size: 12px; text-align: center; color: #334155; }
        .qr { margin: 16px auto 10px; width: 280px; height: 280px; padding: 10px; border: none; border-radius: 10px; }
        .qr svg { width: 100%; height: 100%; display: block; }
        .btn { margin-top: 12px; text-align: right; }
        .btn button { background: #2563eb; color: white; border: none; padding: 8px 12px; border-radius: 8px; cursor: pointer; font-weight: 700; }
        @media print {
            body { background: white; padding: 0; }
            .btn { display: none; }
            .card { border: none; border-radius: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="title">{{ $title }}</div>
        @if ($subtitle !== '')
            <div class="subtitle">{{ $subtitle }}</div>
        @endif
        <div class="qr">{!! $qrSvg ?? '' !!}</div>
        <div class="btn"><button onclick="window.print()">Print</button></div>
    </div>
</body>
</html>
