<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Part Labels</title>
    <style>
        @page { size: 80mm 60mm; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: #fff; }
        .page { 
            page-break-after: always; 
            width: 80mm;
            height: 60mm;
            padding: 3mm;
        }
        .page:last-child { page-break-after: auto; }
        
        .label {
            width: 100%;
            height: 100%;
            border: 2px solid #000;
            border-radius: 4px;
            padding: 3mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 2mm;
            margin-bottom: 2mm;
        }
        .part-no {
            font-size: 16pt;
            font-weight: 900;
            letter-spacing: -0.5px;
        }
        .class-badge {
            font-size: 10pt;
            font-weight: 800;
            color: #000;
            border: 2px solid #000;
            padding: 1mm 3mm;
            border-radius: 4mm;
            min-width: 12mm;
            text-align: center;
        }

        /* Middle Section: Meta + QR */
        .middle {
            display: flex;
            flex: 1;
            gap: 2mm;
            overflow: hidden;
        }
        .meta {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2mm;
            padding-top: 1mm;
        }
        .field {
            display: flex;
            flex-direction: column;
        }
        .field-label {
            font-size: 7pt;
            color: #555;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 0.5mm;
        }
        .field-value {
            font-size: 9pt;
            font-weight: 700;
            line-height: 1.1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .qr-section {
            width: 28mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }
        .qr-box {
            width: 28mm;
            height: 28mm;
        }
        .qr-box svg {
            width: 100%;
            height: 100%;
        }

        /* Footer: Barcode 1D */
        .footer {
            margin-top: 2mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            border-top: 1px dotted #ccc;
            padding-top: 2mm;
        }
        .barcode-img {
            width: 100%;
            max-width: 60mm; 
            height: 10mm;
            object-fit: fill; 
        }
        .barcode-text {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8pt;
            margin-top: 1mm;
            font-weight: 600;
            letter-spacing: 1px;
        }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
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
                    <div class="part-no">{{ $part->part_no }}</div>
                    <div class="class-badge">{{ strtoupper((string) ($part->classification ?? '')) }}</div>
                </div>

                <!-- Content -->
                <div class="middle">
                    <div class="meta">
                        <div class="field">
                            <div class="field-label">Part Name</div>
                            <div class="field-value">{{ $part->part_name ?: '-' }}</div>
                        </div>
                        <div class="field">
                            <div class="field-label">Model</div>
                            <div class="field-value">{{ $part->model ?: '-' }}</div>
                        </div>
                        @if($part->customer)
                        <div class="field">
                            <div class="field-label">Customer</div>
                            <div class="field-value" style="font-size: 8pt;">{{ Str::limit($part->customer->name, 15) }}</div>
                        </div>
                        @endif
                    </div>

                    <div class="qr-section">
                        <div class="qr-box">{!! $label['qrSvg'] ?? '' !!}</div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="footer">
                    <img class="barcode-img" src="data:image/png;base64,{{ $label['barcodeImage'] }}" alt="Barcode">
                    <div class="barcode-text">{{ $label['barcode'] }}</div>
                </div>
            </div>
        </div>
    @endforeach

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>
</html>
