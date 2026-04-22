<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Line Stock QR - {{ $part->part_no }}</title>
    <style>
        @page { size: 100mm 75mm; margin: 0; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            width: 100mm;
            height: 75mm;
            font-family: Arial, sans-serif;
            color: #000;
            background: #fff;
            overflow: hidden;
        }
        .outer {
            width: 100mm;
            height: 75mm;
            padding: 4mm;
        }
        .label {
            width: 92mm;
            height: 67mm;
            border: 2px solid #000;
            border-radius: 4mm;
            display: grid;
            grid-template-columns: 52mm 1fr;
            overflow: hidden;
        }
        .info {
            padding: 5mm 4mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-right: 2px solid #000;
        }
        .eyebrow {
            font-size: 9pt;
            font-weight: 900;
            letter-spacing: 1.8px;
        }
        .part-no {
            font-size: 22pt;
            line-height: 1;
            font-weight: 900;
            word-break: break-word;
        }
        .name {
            margin-top: 2mm;
            font-size: 11pt;
            font-weight: 800;
            line-height: 1.15;
        }
        .location {
            display: inline-block;
            margin-top: 4mm;
            padding: 2mm 3mm;
            border: 2px solid #000;
            border-radius: 999px;
            font-size: 12pt;
            font-weight: 900;
        }
        .note {
            font-size: 8pt;
            line-height: 1.25;
            font-weight: 700;
        }
        .qr-area {
            padding: 4mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2mm;
        }
        .qr {
            width: 35mm;
            height: 35mm;
        }
        .qr svg {
            width: 100% !important;
            height: 100% !important;
        }
        .scan-text {
            font-size: 9pt;
            font-weight: 900;
            text-align: center;
        }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>

<body>
    <div class="outer">
        <div class="label">
            <section class="info">
                <div>
                    <div class="eyebrow">LINE STOCK</div>
                    <div class="part-no">{{ $part->part_no }}</div>
                    <div class="name">{{ $part->part_name }}</div>
                    <div class="location">{{ $location }}</div>
                </div>
                <div class="note">
                    Untuk material policy Simpan di Line. Scan QR ini saat supply produksi, sistem ambil stok FIFO otomatis.
                </div>
            </section>
            <section class="qr-area">
                <div class="qr">{!! $qrSvg !!}</div>
                <div class="scan-text">SCAN UNTUK<br>SUPPLY LINE</div>
            </section>
        </div>
    </div>
    <script>
        window.onload = function () { window.print(); };
    </script>
</body>

</html>
