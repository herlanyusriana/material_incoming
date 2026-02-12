<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - {{ $delivery_note->dn_no }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .company-info h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 900;
        }

        .dn-info {
            text-align: right;
        }

        .dn-info h1 {
            margin: 0;
            font-size: 24px;
            color: #000;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }

        .detail-item {
            margin-bottom: 5px;
        }

        .detail-label {
            font-weight: bold;
            display: inline-block;
            width: 100px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 10px 5px;
            text-align: left;
        }

        td {
            border-bottom: 1px dashed #ccc;
            padding: 10px 5px;
        }

        .footer {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            text-align: center;
            margin-top: 50px;
        }

        .signature-box {
            height: 80px;
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="no-print"
        style="background: #f1f5f9; padding: 10px; margin-bottom: 20px; border-radius: 8px; text-align: center;">
        <button onclick="window.print()"
            style="padding: 10px 20px; background: #4f46e5; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
            PRINT SURAT JALAN
        </button>
    </div>

    <div class="header">
        <div class="company-info">
            <h2>PT. GCI INDONESIA</h2>
            <p>Kawasan Industri Bekasi Fajar, Blok A-1, MM2100<br>Cikarang Barat, Bekasi 17520</p>
        </div>
        <div class="dn-info">
            <h1>SURAT JALAN</h1>
            <p><strong>No:</strong> {{ $delivery_note->dn_no }}</p>
            <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($delivery_note->delivery_date)->format('d F Y') }}</p>
        </div>
    </div>

    <div class="details-grid">
        <div class="to-info">
            <p class="detail-label">SHIP TO:</p>
            <p><strong>{{ $delivery_note->customer?->name }}</strong></p>
            <p>{{ $delivery_note->customer?->address ?? 'N/A' }}</p>
        </div>
        <div class="transport-info">
            <div class="detail-item"><span class="detail-label">Truck:</span>
                {{ $delivery_note->truck?->plate_no ?? '-' }} ({{ $delivery_note->truck?->type ?? '-' }})</div>
            <div class="detail-item"><span class="detail-label">Driver:</span>
                {{ $delivery_note->driver?->name ?? '-' }}</div>
            <div class="detail-item"><span class="detail-label">Notes:</span> {{ $delivery_note->notes ?? '-' }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th>Part Number</th>
                <th>Description</th>
                <th style="text-align: right;">Quantity</th>
                <th style="width: 60px;">UoM</th>
            </tr>
        </thead>
        <tbody>
            @foreach($delivery_note->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->part?->part_no }}</td>
                    <td>{{ $item->part?->part_name }}</td>
                    <td style="text-align: right; font-weight: bold;">{{ number_format($item->qty, 2) }}</td>
                    <td>PCS</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div>
            <p>Prepared By,</p>
            <div class="signature-box"></div>
            <p>( Warehouse )</p>
        </div>
        <div>
            <p>Driver,</p>
            <div class="signature-box"></div>
            <p>( {{ $delivery_note->driver?->name ?? '.................' }} )</p>
        </div>
        <div>
            <p>Security,</p>
            <div class="signature-box"></div>
            <p>( ................. )</p>
        </div>
        <div>
            <p>Received By,</p>
            <div class="signature-box"></div>
            <p>( Customer )</p>
        </div>
    </div>
</body>

</html>