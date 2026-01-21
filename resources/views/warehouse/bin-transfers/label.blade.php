<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bin Transfer Label - {{ $binTransfer->id }}</title>
    <style>
        @page {
            size: 100mm 60mm;
            margin: 0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            width: 100mm;
            height: 60mm;
            padding: 3mm;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 2mm;
            margin-bottom: 2mm;
        }
        
        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }
        
        .header .transfer-id {
            font-size: 10pt;
            color: #666;
        }
        
        .content {
            display: flex;
            gap: 3mm;
            flex: 1;
        }
        
        .info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .info-row {
            margin-bottom: 1.5mm;
        }
        
        .info-label {
            font-size: 7pt;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 0.5mm;
        }
        
        .info-value {
            font-size: 10pt;
            font-weight: bold;
            word-wrap: break-word;
        }
        
        .movement {
            display: flex;
            align-items: center;
            gap: 2mm;
            padding: 2mm 0;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }
        
        .location-box {
            flex: 1;
            text-align: center;
            padding: 2mm;
            border-radius: 2mm;
        }
        
        .from-box {
            background: #fee;
            border: 1px solid #f88;
        }
        
        .to-box {
            background: #efe;
            border: 1px solid #8f8;
        }
        
        .location-label {
            font-size: 6pt;
            color: #666;
        }
        
        .location-code {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 1mm;
        }
        
        .arrow {
            font-size: 16pt;
            color: #666;
        }
        
        .qr-code {
            width: 40mm;
            height: 40mm;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
            border-radius: 2mm;
        }
        
        .qr-code svg {
            width: 38mm;
            height: 38mm;
        }
        
        .footer {
            text-align: center;
            font-size: 6pt;
            color: #999;
            margin-top: 2mm;
            padding-top: 1mm;
            border-top: 1px solid #ddd;
        }
        
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>BIN TRANSFER</h1>
        <div class="transfer-id">Transfer #{{ $binTransfer->id }}</div>
    </div>
    
    <div class="content">
        <div class="info">
            <div class="info-row">
                <div class="info-label">Part Number</div>
                <div class="info-value">{{ $binTransfer->part->part_no }}</div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Part Name</div>
                <div class="info-value" style="font-size: 8pt;">{{ Str::limit($binTransfer->part->part_name_gci, 30) }}</div>
            </div>
            
            <div class="movement">
                <div class="location-box from-box">
                    <div class="location-label">FROM</div>
                    <div class="location-code">{{ $binTransfer->from_location_code }}</div>
                </div>
                <div class="arrow">→</div>
                <div class="location-box to-box">
                    <div class="location-label">TO</div>
                    <div class="location-code">{{ $binTransfer->to_location_code }}</div>
                </div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Quantity</div>
                <div class="info-value" style="font-size: 14pt; color: #2563eb;">{{ formatNumber($binTransfer->qty) }}</div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Transfer Date</div>
                <div class="info-value">{{ $binTransfer->transfer_date->format('Y-m-d') }}</div>
            </div>
        </div>
        
        <div class="qr-code">
            {!! $qrSvg !!}
        </div>
    </div>
    
    <div class="footer">
        Transferred by: {{ $binTransfer->creator->name ?? '-' }} | Printed: {{ now()->format('Y-m-d H:i') }}
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
