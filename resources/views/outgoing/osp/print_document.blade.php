<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $docTitle }} - {{ $docNo }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            background: #e2e8f0;
            color: #0f172a;
        }

        .page {
            width: 190mm;
            margin: 0 auto;
            background: #fff;
            padding: 10mm;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
        }

        .toolbar {
            margin: 0 auto 16px;
            width: 190mm;
            text-align: right;
        }

        .toolbar button {
            padding: 10px 18px;
            font-weight: 700;
            border: 0;
            border-radius: 8px;
            background: #0f172a;
            color: #fff;
            cursor: pointer;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .brand {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.06em;
        }

        .doc-title {
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .muted {
            color: #475569;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 18px;
        }

        .card {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 14px;
        }

        .label {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            vertical-align: top;
        }

        th {
            background: #e2e8f0;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .text-right {
            text-align: right;
        }

        .summary {
            margin-top: 14px;
            margin-left: auto;
            width: 320px;
        }

        .summary td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
        }

        .summary .total {
            background: #dbeafe;
            font-weight: 800;
        }

        .footer-note {
            margin-top: 22px;
            font-size: 11px;
            color: #475569;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .toolbar {
                display: none;
            }

            .page {
                width: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <button onclick="window.print()">Print {{ $docShort }}</button>
    </div>

    <div class="page">
        <div class="header">
            <div>
                <div class="brand">PT GEUM CHEON INDO</div>
                <div class="muted">OSP Outgoing Document Flow</div>
            </div>
            <div style="text-align:right">
                <div class="doc-title">{{ $docTitle }}</div>
                <div><strong>{{ $docNo }}</strong></div>
                <div class="muted">{{ optional($ospOrder->shipped_date ?? $ospOrder->received_date)->format('d M Y') }}</div>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <div class="label">Customer</div>
                <div><strong>{{ $ospOrder->customer->name ?? '-' }}</strong></div>
                <div class="muted">{{ $ospOrder->customer->address ?? '-' }}</div>
            </div>
            <div class="card">
                <div class="label">OSP Reference</div>
                <div><strong>{{ $ospOrder->order_no }}</strong></div>
                <div class="muted">FG: {{ $ospOrder->gciPart->part_no ?? '-' }} - {{ $ospOrder->gciPart->part_name ?? '-' }}</div>
                <div class="muted">Doc Qty: {{ number_format($printQty, 4) }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th style="width: 140px;">Part No</th>
                    <th>Description</th>
                    <th style="width: 90px;">Type</th>
                    <th style="width: 70px;">UOM</th>
                    <th style="width: 110px;" class="text-right">Qty</th>
                    @if ($showPricing)
                        <th style="width: 120px;" class="text-right">Unit Price</th>
                        <th style="width: 140px;" class="text-right">Amount</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($lines as $line)
                    <tr>
                        <td>{{ $line['no'] }}</td>
                        <td style="font-family: monospace;">{{ $line['part_no'] }}</td>
                        <td>
                            <div><strong>{{ $line['part_name'] }}</strong></div>
                            <div class="muted">{{ $line['description'] ?: '-' }}</div>
                        </td>
                        <td>{{ $line['material_type'] }} / {{ $line['special'] }}</td>
                        <td>{{ $line['uom'] }}</td>
                        <td class="text-right">{{ number_format((float) $line['qty'], 4) }}</td>
                        @if ($showPricing)
                            <td class="text-right">{{ number_format((float) $line['unit_price'], 3) }}</td>
                            <td class="text-right">{{ number_format((float) $line['amount'], 2) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showPricing ? 8 : 6 }}" style="text-align:center; color:#64748b;">No OSP BOM material lines found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <td>Total Qty</td>
                <td class="text-right">{{ number_format($totalQty, 4) }}</td>
            </tr>
            @if ($showPricing)
                <tr class="total">
                    <td>Total Amount ({{ $currency }})</td>
                    <td class="text-right">{{ number_format($totalAmount, 2) }}</td>
                </tr>
            @endif
        </table>

        <div class="footer-note">
            Dokumen OSP ini dicetak berdasarkan item BOM bertipe <strong>FREE ISSUE</strong> atau material dengan tag <strong>OSP</strong> untuk FG terkait.
        </div>
    </div>
</body>

</html>
