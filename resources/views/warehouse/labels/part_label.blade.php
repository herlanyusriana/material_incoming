<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Part Label - {{ $part->part_no }}</title>
    <style>
        @page {
            size: 100mm 75mm;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: 100mm;
            height: 75mm;
            margin: 0;
            padding: 0;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #fff;
            overflow: hidden;
        }

        .label-container {
            width: 100mm;
            height: 75mm;
            padding: 3mm;
        }

        .label {
            width: 100%;
            height: 100%;
            border: 2px solid #000;
            border-radius: 3px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Header Section */
        .header {
            flex-shrink: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding: 3mm 4mm;
            background: #fff;
        }

        .part-no {
            font-size: 20pt;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #000;
        }

        .class-badge {
            font-size: 12pt;
            font-weight: 800;
            color: #000;
            border: 2px solid #000;
            padding: 1.5mm 4mm;
            border-radius: 4mm;
            min-width: 16mm;
            text-align: center;
        }

        /* QR Section */
        .qr-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2mm;
            background: #fff;
        }

        .qr-box {
            width: 38mm;
            height: 38mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-box svg {
            width: 100%;
            height: 100%;
        }

        /* Metadata Section */
        .meta {
            flex-shrink: 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2mm 4mm;
            border-top: 1px solid #ccc;
            padding: 3mm 4mm;
            background: #f8f8f8;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 0.3mm;
            min-width: 0;
        }

        .field-label {
            font-size: 7pt;
            color: #666;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .field-value {
            font-size: 9pt;
            font-weight: 600;
            line-height: 1.2;
            color: #000;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media print {

            html,
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .label {
                border: 2px solid #000 !important;
            }

            .meta {
                background: #f8f8f8 !important;
            }
        }
    </style>
</head>

<body>
    <div class="label-container">
        <div class="label">
            <!-- Header -->
            <div class="header">
                <div class="part-no">{{ $part->part_no }}</div>
                <div class="class-badge">{{ strtoupper((string) ($part->classification ?? '')) }}</div>
            </div>

            <!-- QR Code -->
            <div class="qr-section">
                <div class="qr-box">{!! $qrSvg !!}</div>
            </div>

            <!-- Metadata -->
            <div class="meta">
                <div class="field">
                    <div class="field-label">Part Name</div>
                    <div class="field-value">{{ Str::limit($part->part_name ?: '-', 30) }}</div>
                </div>
                <div class="field">
                    <div class="field-label">Model</div>
                    <div class="field-value">{{ Str::limit($part->model ?: '-', 25) }}</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>

</html>