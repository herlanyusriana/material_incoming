<!DOCTYPE html>
@php
    $subconUom = $subconOrderReceive->subconOrder->bomItem?->consumptionUom?->code
        ?? $subconOrderReceive->subconOrder->bomItem?->consumption_uom
        ?? $subconOrderReceive->subconOrder->rmPart?->uom
        ?? $subconOrderReceive->subconOrder->bomItem?->wipUom?->code
        ?? $subconOrderReceive->subconOrder->bomItem?->wip_uom
        ?? $subconOrderReceive->subconOrder->gciPart?->uom
        ?? 'PCS';
@endphp
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label Receive - {{ $subconOrderReceive->id }}</title>
    <style>
        @page {
            size: 100mm 150mm;
            margin: 0;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
        }

        .label-container {
            width: 100mm;
            height: 150mm;
            box-sizing: border-box;
            padding: 8mm;
            display: flex;
            flex-direction: column;
            border: 1px dashed #ccc; /* For preview, hide on print */
        }

        @media print {
            .label-container {
                border: none;
            }
            .no-print {
                display: none;
            }
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 5mm;
            margin-bottom: 5mm;
        }

        .title {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 2mm;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 10pt;
        }

        .part-info {
            border: 2px solid #000;
            padding: 4mm;
            margin-bottom: 5mm;
            text-align: center;
            flex: 1;
        }

        .part-no {
            font-size: 20pt;
            font-weight: bold;
            margin-bottom: 2mm;
        }

        .part-name {
            font-size: 14pt;
        }

        .details {
            font-size: 12pt;
            line-height: 1.5;
            margin-bottom: 5mm;
        }

        .detail-row {
            display: flex;
            border-bottom: 1px solid #000;
            padding: 2mm 0;
        }

        .detail-label {
            width: 35%;
            font-weight: bold;
        }

        .detail-val {
            width: 65%;
            font-family: monospace;
            font-size: 14pt;
        }

        .footer {
            text-align: center;
            font-size: 9pt;
            margin-top: auto;
            border-top: 1px solid #000;
            padding-top: 2mm;
        }

        .toolbar {
            text-align: center;
            padding: 10px;
            background: #f1f5f9;
        }
        
        .toolbar button {
            padding: 10px 20px;
            font-size: 14px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button onclick="window.print()">Print Label (100x150mm)</button>
    </div>
    
    <div class="label-container">
        <div class="header">
            <div class="title">PT GEUM CHEON INDO</div>
            <div class="subtitle">Subcon Receive Label</div>
        </div>

        <div class="part-info">
            <div class="part-no">{{ $subconOrderReceive->subconOrder->gciPart->part_no ?? '-' }}</div>
            <div class="part-name">{{ $subconOrderReceive->subconOrder->gciPart->part_name ?? '-' }}</div>
        </div>

        <div class="details">
            <div class="detail-row">
                <div class="detail-label">Order No:</div>
                <div class="detail-val">{{ $subconOrderReceive->subconOrder->order_no }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Qty Good:</div>
                <div class="detail-val">{{ number_format($subconOrderReceive->qty_good) }} {{ $subconUom }}</div>
            </div>
            @if($subconOrderReceive->qty_rejected > 0)
            <div class="detail-row">
                <div class="detail-label">Qty Reject:</div>
                <div class="detail-val">{{ number_format($subconOrderReceive->qty_rejected) }} {{ $subconUom }}</div>
            </div>
            @endif
            <div class="detail-row">
                <div class="detail-label">Recv Date:</div>
                <div class="detail-val">{{ optional($subconOrderReceive->received_date)->format('d/m/Y') }}</div>
            </div>
        </div>

        <div class="footer">
            Printed on {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
