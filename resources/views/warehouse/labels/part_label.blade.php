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
            font-family: Arial, sans-serif;
            /* High compatibility font */
            background: #fff;
            color: #000;
            overflow: hidden;
        }

        .label-outer {
            width: 100mm;
            height: 75mm;
            padding: 3mm;
            /* Outer gap */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .label-inner {
            width: 94mm;
            height: 69mm;
            border: 2px solid #000;
            border-radius: 5mm;
            padding: 4mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }

        /* Top Section */
        .header {
            text-align: center;
            width: 100%;
        }

        .title-small {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #555;
            margin-bottom: 1mm;
        }

        .part-no {
            font-size: 26pt;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 1mm;
        }

        .model-name {
            font-size: 13pt;
            font-weight: bold;
            color: #333;
        }

        /* Middle Section: QR Code */
        .qr-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .qr-box {
            width: 35mm;
            height: 35mm;
            padding: 1mm;
            border: 1px solid #ccc;
            border-radius: 2mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-box svg {
            width: 100% !important;
            height: 100% !important;
        }

        /* Bottom Section */
        .footer {
            width: 100%;
            text-align: center;
            border-top: 1px dashed #000;
            padding-top: 2mm;
            margin-top: 1mm;
        }

        .part-name {
            font-size: 11pt;
            font-weight: bold;
            color: #000;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
                <p class="title-small">Part Number</p>
                <h1 class="part-no">{{ $part->part_no }}</h1>
                <p class="model-name">{{ $part->model ?: strtoupper($part->classification ?? '-') }}</p>
            </div>

            <!-- QR Code -->
            <div class="qr-container">
                <div class="qr-box">
                    {!! $qrSvg !!}
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <span class="part-name">Part Name: {{ $part->part_name ?: '-' }}</span>
            </div>
        </div>
    </div>

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>

</html>