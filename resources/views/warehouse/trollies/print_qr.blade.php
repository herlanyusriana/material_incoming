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
        }

        .label {
            width: 100mm;
            height: 75mm;
            padding: 5mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .header {
            position: absolute;
            top: 4mm;
            width: 90mm;
            text-align: center;
        }

        .header h1 {
            font-size: 28pt;
            font-weight: 900;
            letter-spacing: 2px;
        }

        .header p {
            font-size: 10pt;
            font-weight: bold;
            color: #555;
            text-transform: uppercase;
            margin-top: 1mm;
        }

        .qr-container {
            width: 42mm;
            height: 42mm;
            margin-top: 10mm;
        }

        .qr-container svg {
            width: 100%;
            height: 100%;
        }

        .footer {
            position: absolute;
            bottom: 4mm;
            width: 90mm;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 0 4mm;
        }

        .info-box {
            text-align: left;
        }

        .info-box p {
            font-size: 7pt;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
        }

        .info-box h2 {
            font-size: 11pt;
            font-weight: 900;
        }

        .trolly-badge {
            background: #000;
            color: #fff;
            padding: 2mm 4mm;
            border-radius: 2mm;
            font-size: 12pt;
            font-weight: 900;
        }
    </style>
</head>

<body>
    <div class="label">
        <div class="header">
            <h1>{{ $trolly->code }}</h1>
            <p>Warehouse Transport Unit</p>
        </div>

        <div class="qr-container">
            {!! $qrSvg !!}
        </div>

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
    <script>window.onload = function () { window.print(); };</script>
</body>

</html>