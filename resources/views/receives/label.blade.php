@php
    $arrivalItem = $receive->arrivalItem;
    $arrival = $arrivalItem?->arrival;
    $part = $arrivalItem?->part;
    $resolvedTag = $receive->tag ?: ($receive->ensureSystemTag() ?? '-');
    $vendorName = $arrival?->vendor?->vendor_name ?? '-';
    $invoiceNo = $arrival?->invoice_no ?? '-';
    $deliveryNoteNo = $receive->delivery_note_no ?? $arrival?->sj_no ?? '-';
    $goodsUnit = strtoupper(trim((string) ($arrivalItem?->unit_goods ?? $receive->qty_unit ?? '')));
    $incomingPartNo = $arrivalItem?->display_part_no ?? $part?->part_no ?? $arrivalItem?->gciPartVendor?->vendor_part_no ?? '-';
    $partName = $arrivalItem?->display_part_name ?? $part?->part_name_gci ?? $part?->part_name_vendor ?? '-';
    $qtyGoodsText = number_format((float) ($receive->qty ?? 0), 0) . ' ' . $goodsUnit;
    $netWeight = (float) ($receive->net_weight ?? $receive->weight ?? 0);

    $showWeightInMain = $goodsUnit === 'COIL' || ($goodsUnit === 'SHEET' && $netWeight > 0);

    $qtyWeightText = $showWeightInMain
        ? number_format($netWeight, 2) . ' KGM'
        : $qtyGoodsText;

    $qtySecondaryLabel = match ($goodsUnit) {
        'SHEET' => 'Qty Sheet',
        default => 'Qty Coil',
    };

    $qtySecondaryText = match ($goodsUnit) {
        'COIL' => number_format((float) ($receive->qty ?? 0), 0) . ' COIL',
        'SHEET' => number_format((float) ($receive->qty ?? 0), 0) . ' SHEET',
        default => '-',
    };

    $monthBox = str_pad((string) ($monthNumber ?? (int) optional($receive->ata_date)->format('m')), 2, '0', STR_PAD_LEFT);
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label Material</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .label-container {
            background: white;
            width: 150mm;
            height: 100mm;
            border: 3px solid #000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: #fff;
            padding: 8mm 12mm;
            border-bottom: 2px solid #000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 22mm;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo {
            width: 42px;
            height: 42px;
            border: 2px solid #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            color: #000;
            background: white;
        }

        .header-title {
            font-size: 22px;
            font-weight: bold;
            color: #000;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .header-month {
            width: 42px;
            height: 42px;
            background: #000;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 24px;
            border-radius: 6px;
        }

        .content {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .left-section {
            width: 90mm;
            border-right: 2px solid #000;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .right-section {
            width: 60mm;
            display: flex;
            flex-direction: column;
        }

        .qr-box {
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 50mm;
            border-bottom: 2px solid #000;
            padding: 2mm;
            width: 100%;
        }

        .qr-box svg {
            width: 100% !important;
            height: 100% !important;
            display: block;
        }

        .form-row {
            border-bottom: 1px solid #000;
            min-height: 0;
            display: flex;
            align-items: center;
        }

        .form-row:last-child {
            border-bottom: none;
        }

        .field-value {
            width: 100%;
            padding: 2.4mm 5mm;
            font-size: 9pt;
            font-weight: 700;
            color: #000;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .field-value.multiline {
            white-space: normal;
            line-height: 1.2;
        }

        .field-value .sub {
            font-size: 8pt;
            color: #555;
            font-weight: 600;
        }

        .iqc-section {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .iqc-title {
            text-align: center;
            padding: 6px;
            font-weight: bold;
            background: #eee;
            border-bottom: 1px solid #000;
            font-size: 12px;
            text-transform: uppercase;
        }

        .stamp-section {
            display: flex;
            flex: 1;
        }

        .stamp-box {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
            color: #000;
        }

        .stamp-box:first-child {
            border-right: 2px solid #000;
        }

        .print-btn {
            margin-top: 12px;
            text-align: right;
        }

        .print-btn button {
            background: #000;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .print-btn button:hover {
            background: #333;
        }

        @page {
            size: 150mm 100mm;
            margin: 0;
        }

        @media print {
            body {
                background: white;
                padding: 0;
                min-height: auto;
            }

            .label-container {
                box-shadow: none;
                border: 2px solid #000;
            }

            .print-btn {
                display: none;
            }

            .iqc-title {
                background-color: #eee !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .header-month {
                background-color: #000 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div>
        <div class="label-container">
            <div class="header">
                <div class="header-left">
                    <div class="logo">GL</div>
                    <div class="header-title">LABEL MATERIAL</div>
                </div>
                <div class="header-month">{{ $monthBox }}</div>
            </div>

            <div class="content">
                <div class="left-section">
                    <div class="form-row">
                        <div class="field-value">{{ $incomingPartNo }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-value">{{ $partName }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-value">{{ $arrivalItem?->material_group ?? '-' }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-value">{{ $arrivalItem?->size ?? '-' }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-value">{{ $vendorName }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-value">{{ $invoiceNo }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-value">{{ $resolvedTag }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-value">{{ $qtyWeightText }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-value">{{ $qtySecondaryText }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-value">{{ $receive->ata_date?->format('Y-m-d H:i') ?? '-' }}</div>
                    </div>
                </div>

                <div class="right-section">
                    <div class="qr-box">{!! $qrSvg ?? '' !!}</div>
                    <div class="iqc-section">
                        <div class="iqc-title">IQC Check</div>
                        <div class="stamp-section">
                            <div class="stamp-box">Stamp</div>
                            <div class="stamp-box">TTD</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="print-btn">
            <button onclick="window.print()">Print Label</button>
        </div>
    </div>
</body>

</html>
