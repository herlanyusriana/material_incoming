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
            width: 42mm;
            height: 42mm;
            padding: 0;
            border: none;
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 2mm; /* Give some space from top header */
            margin-bottom: 2mm; /* Give space from bottom footer */
        }

        .qr-box svg {
            width: 100% !important;
            height: 100% !important;
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

            <!-- Footer / Extra Info -->
            <div style="position: absolute; bottom: 2mm; right: 6mm; text-align: right;">
                <p style="font-size: 7pt; font-weight: bold; color: #555; text-transform: uppercase; margin-bottom: 0.2mm;">Batch No</p>
                <p style="font-size: 11pt; font-weight: 900; line-height: 1;">{{ $batch ?: '---' }}</p>
            </div>

            <div style="position: absolute; bottom: 2mm; left: 6mm; text-align: left;">
                <p style="font-size: 7pt; font-weight: bold; color: #555; text-transform: uppercase; margin-bottom: 0.2mm;">Model</p>
                <p style="font-size: 9pt; font-weight: 900; line-height: 1;">{{ $part->model ?: '-' }}</p>
            </div>


        </div>
    </div>

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>

</html>