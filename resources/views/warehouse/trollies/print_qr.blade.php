<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trolly QR - {{ $trolly->code }}</title>
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

        body {
            font-family: Arial, sans-serif;
            background: #fff;
            width: 100mm;
            height: 75mm;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .label-outer {
            width: 100mm;
            height: 75mm;
            padding: 3mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .label-inner {
            width: 94mm;
            height: 69mm;
            border: 2px solid #000;
            border-radius: 4mm;
            padding: 4mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }

        .header {
            text-align: center;
            width: 100%;
        }

        .header h1 {
            font-size: 32pt;
            font-weight: 900;
            letter-spacing: 2px;
            line-height: 1;
            margin-bottom: 2mm;
        }

        .header p {
            font-size: 9pt;
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .qr-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .qr-box {
            width: 38mm;
            height: 38mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Ensure SVG takes full space */
        .qr-box svg {
            width: 100% !important;
            height: 100% !important;
        }

        .footer {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 2mm;
        }

        .info-box {
            text-align: left;
        }

        .info-box p {
            font-size: 7pt;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 0.5mm;
        }

        .info-box h2 {
            font-size: 11pt;
            font-weight: 900;
            line-height: 1;
        }

        .trolly-badge {
            background: #000;
            color: #fff;
            padding: 1.5mm 4mm;
            border-radius: 1.5mm;
            font-size: 11pt;
            font-weight: 900;
            letter-spacing: 1px;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
            }
            .label-inner {
                border: 2px solid #000 !important;
            }
        }
    </style>
</head>

<body>
    <div class="label-outer">
        <div class="label-inner">
            <!-- Header -->
            <div class="header">
                <h1>{{ $trolly->code }}</h1>
                <p>Warehouse Transport Unit</p>
            </div>

            <!-- QR Code Container -->
            <div class="qr-container">
                <div class="qr-box">
                    {!! $qrSvg !!}
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <div class="info-box">
                    <p>Type / Kind</p>
                    <h2>{{ $trolly->type ?: 'GEN' }} / {{ $trolly->kind ?: 'MAT' }}</h2>
                </div>
                <div class="trolly-badge">
                    TROLLY
                </div>
            </div>
        </div>
    </div>
    <script>window.onload = function () { window.print(); };</script>
</body>

</html>