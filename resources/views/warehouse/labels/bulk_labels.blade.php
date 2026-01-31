<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Part Labels</title>
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
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: #fff;
        }

        .page {
            page-break-after: always;
            width: 100mm;
            height: 75mm;
            padding: 4mm;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .label {
            width: 100%;
            height: 100%;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 4mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 5mm 6mm;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        /* Header Section */
        .header {
            text-align: center;
        }

        .label-title {
            font-size: 7pt;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 2mm;
        }

        .part-no {
            font-size: 20pt;
            font-weight: 800;
            color: #1f2937;
            letter-spacing: -0.5px;
            line-height: 1;
        }

        .model {
            font-size: 10pt;
            font-weight: 600;
            color: #6366f1;
            margin-top: 1.5mm;
            letter-spacing: 0.5px;
        }

        /* QR Section */
        .qr-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2mm 0;
        }

        .qr-box {
            width: 42mm;
            height: 42mm;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e5e7eb;
            border-radius: 3mm;
            padding: 2mm;
            background: #fff;
        }

        .qr-box svg {
            width: 100%;
            height: 100%;
        }

        /* Footer Section */
        .footer {
            text-align: center;
        }

        .part-name-label {
            font-size: 8pt;
            color: #6b7280;
        }

        .part-name-value {
            font-size: 9pt;
            font-weight: 700;
            color: #374151;
        }

        @media print {

            html,
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .label {
                box-shadow: none;
                border: 1px solid #e5e5e5;
            }
        }
    </style>
</head>

<body>
    @foreach($labels as $label)
    @php($part = $label['part'])
    <div class="page">
        <div class="label">
            <!-- Header -->
            <div class="header">
                <div class="label-title">Part Number</div>
                <div class="part-no">{{ $part->part_no }}</div>
                <div class="model">{{ $part->model ?: strtoupper($part->classification ?? '') }}</div>
            </div>

            <!-- QR Code -->
            <div class="qr-section">
                <div class="qr-box">{!! $label['qrSvg'] ?? '' !!}</div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <span class="part-name-label">Part Name:</span>
                <span class="part-name-value">{{ Str::limit($part->part_name ?: '-', 35) }}</span>
            </div>
        </div>
    </div>
    @endforeach

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>

</html>