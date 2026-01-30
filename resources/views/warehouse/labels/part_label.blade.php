<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Part Label - {{ $part->part_no }}</title>
    <style>
        @page { size: 100mm 80mm; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            width: 100mm;
            height: 80mm;
            padding: 3mm;
            font-family: Arial, sans-serif;
        }
        .label {
            width: 100%;
            height: 100%;
            border: 2px solid #000;
            padding: 3mm;
            display: grid;
            grid-template-columns: 1fr 44mm;
            grid-template-rows: auto 1fr auto;
            gap: 2.5mm;
        }
        .title {
            grid-column: 1 / -1;
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 3mm;
            border-bottom: 1px solid #000;
            padding-bottom: 2mm;
        }
        .part-no { font-size: 16pt; font-weight: 900; letter-spacing: 0.2mm; }
        .class {
            font-size: 9pt;
            font-weight: 800;
            padding: 1mm 2mm;
            border: 1px solid #000;
        }
        .meta {
            grid-column: 1 / 2;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            gap: 1.5mm;
            overflow: hidden;
        }
        .name { font-size: 10pt; font-weight: 700; line-height: 1.2; }
        .model { font-size: 9pt; color: #444; }
        .barcode-wrap {
            grid-column: 1 / 2;
            align-self: end;
        }
        .barcode-img {
            width: 100%;
            height: 18mm;
            object-fit: contain;
            display: block;
        }
        .barcode-text {
            margin-top: 1mm;
            font-size: 9pt;
            font-weight: 800;
            text-align: center;
            letter-spacing: 0.3mm;
        }
        .qr {
            grid-column: 2 / 3;
            grid-row: 2 / 4;
            border-left: 1px solid #000;
            padding-left: 2.5mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.5mm;
        }
        .qr-box {
            width: 40mm;
            height: 40mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-box svg { width: 40mm; height: 40mm; display: block; }
        .qr-caption { font-size: 7pt; color: #555; text-align: center; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="label">
        <div class="title">
            <div class="part-no">{{ $part->part_no }}</div>
            <div class="class">{{ strtoupper((string) ($part->classification ?? '')) }}</div>
        </div>

        <div class="meta">
            <div class="name">{{ $part->part_name ?: '-' }}</div>
            <div class="model">{{ $part->model ?: '-' }}</div>
        </div>

        <div class="barcode-wrap">
            <img class="barcode-img" src="data:image/png;base64,{{ $barcodeImage }}" alt="Barcode">
            <div class="barcode-text">{{ $barcode }}</div>
        </div>

        <div class="qr">
            <div class="qr-box">{!! $qrSvg !!}</div>
            <div class="qr-caption">Scan for part info</div>
        </div>
    </div>

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>
</html>

