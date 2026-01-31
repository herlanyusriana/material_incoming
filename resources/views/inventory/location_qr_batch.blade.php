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

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

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
            body {
                background: white;
                padding: 0;
            }

            body>.stack {
                gap: 4px;
            }

            .card {
                box-shadow: none;
                border-color: rgba(17, 24, 39, 0.25);
            }
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
                if ($location->class)
                    $subtitleParts[] = 'Class: ' . $location->class;
                if ($location->zone)
                    $subtitleParts[] = 'Zone: ' . $location->zone;
                $subtitle = $subtitleParts ? implode(' • ', $subtitleParts) : '';
            @endphp
            <div class="card">
                @if(strtoupper($location->class ?? '') === 'TROLLY')
                    <div style="text-align: center; margin-bottom: 4px;">
                        <span
                            style="display: inline-flex; align-items: center; gap: 8px; background: #1e3a8a; color: white; padding: 4px 12px; border-radius: 99px; font-size: 10px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px;" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Mobile Storage Trolly
                        </span>
                    </div>
                @endif
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