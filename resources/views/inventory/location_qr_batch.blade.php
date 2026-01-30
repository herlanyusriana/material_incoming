<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Location Labels</title>
    <style>
        @page {
            size: 100mm 75mm;
            margin: 0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            background: #f8fafc;
            padding: 8px 0 12px;
        }
        .stack {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
        }
        .card {
            width: min(100%, 100mm);
            background: white;
            border: 1px solid rgba(15, 23, 42, 0.2);
            border-radius: 14px;
            padding: 12px 12px 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            page-break-inside: avoid;
        }
        .title {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 1px;
            text-align: center;
        }
        .subtitle {
            margin-top: 6px;
            font-size: 12px;
            text-align: center;
            color: #334155;
        }
        .qr {
            margin: 16px auto 10px;
            width: 280px;
            height: 280px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr svg {
            width: 100%;
            height: 100%;
            display: block;
        }
        @media print {
            body { background: white; padding: 0; }
            body > .stack { gap: 4px; }
            .card { box-shadow: none; border-color: rgba(17, 24, 39, 0.25); }
        }
    </style>
</head>
<body>
    <div class="stack">
        @forelse ($cards as $card)
            @php
                $location = $card['location'];
                $title = trim((string) ($location->location_code ?? 'WAREHOUSE LOCATION'));
                $subtitleParts = [];
                if ($location->class) $subtitleParts[] = 'Class: ' . $location->class;
                if ($location->zone) $subtitleParts[] = 'Zone: ' . $location->zone;
                $subtitle = $subtitleParts ? implode(' • ', $subtitleParts) : '';
            @endphp
            <div class="card">
                <div class="title">{{ $title }}</div>
                @if ($subtitle !== '')
                    <div class="subtitle">{{ $subtitle }}</div>
                @endif
                <div class="qr">{!! $card['qrSvg'] ?? '' !!}</div>
            </div>
        @empty
            <div class="card">
                <div class="title">No Locations</div>
            </div>
        @endforelse
    </div>

    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
