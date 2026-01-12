@php
    $arrivalItem = $receive->arrivalItem;
    $arrival = $arrivalItem?->arrival;
    $part = $arrivalItem?->part;
    $vendorName = $arrival?->vendor?->vendor_name ?? '-';
    $invoiceNo = $arrival?->invoice_no ?? '-';
    $goodsUnit = strtoupper(trim((string) ($arrivalItem?->unit_goods ?? $receive->qty_unit ?? '')));
    $qtyGoodsText = number_format((float) ($receive->qty ?? 0), 0) . ' ' . strtoupper((string) ($receive->qty_unit ?? ''));
    $netWeight = (float) ($receive->net_weight ?? $receive->weight ?? 0);

    $qtyWeightText = $goodsUnit === 'COIL'
        ? number_format($netWeight, 2) . ' KGM'
        : $qtyGoodsText;

    $qtyCoilText = $goodsUnit === 'COIL'
        ? number_format((float) ($receive->qty ?? 0), 0) . ' COIL'
        : '-';

    $monthBox = str_pad((string) ($monthNumber ?? (int) optional($receive->ata_date)->format('m')), 2, '0', STR_PAD_LEFT);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label Material</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            width: 600px;
            border: 3px solid #333;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .header {
            background: linear-gradient(to right, #e8f4f8, #ffffff);
            padding: 15px 20px;
            border-bottom: 3px solid #333;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-left { display: flex; align-items: center; gap: 15px; }

        .logo {
            width: 50px;
            height: 50px;
            border: 3px solid #4a90e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 24px;
            color: #4a90e2;
            background: white;
        }

        .header-title {
            font-size: 32px;
            font-weight: bold;
            color: #4a90e2;
            letter-spacing: 3px;
        }

        .header-qr {
            width: 100px;
            height: 100px;
            padding: 5px;
            background: white;
            border: 2px solid #ddd;
            border-radius: 4px;
        }

        .header-qr svg { width: 100%; height: 100%; display: block; }

        .content { display: flex; }
        .left-section { flex: 1; border-right: 2px solid #333; }

        .form-row { display: flex; border-bottom: 2px solid #333; }
        .form-row:last-child { border-bottom: none; }

        .field-name {
            width: 140px;
            padding: 12px 15px;
            font-weight: 600;
            background: #e8f4f8;
            border-right: 2px solid #333;
            font-size: 14px;
        }

        .colon {
            width: 20px;
            padding: 12px 5px;
            text-align: center;
            border-right: 2px solid #333;
            background: #f9f9f9;
        }

        .field-value { flex: 1; padding: 12px 15px; min-height: 45px; font-size: 14px; }

        .right-section { width: 200px; display: flex; flex-direction: column; }

        .number-box {
            background: linear-gradient(135deg, #e91e63, #f48fb1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 72px;
            font-weight: bold;
            height: 200px;
            border-bottom: 2px solid #333;
        }

        .iqc-section { flex: 1; display: flex; flex-direction: column; }
        .iqc-title { text-align: center; padding: 10px; font-weight: bold; background: #f0f0f0; border-bottom: 2px solid #333; }
        .stamp-section { display: flex; flex: 1; }
        .stamp-box { flex: 1; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 600; color: #666; }
        .stamp-box:first-child { border-right: 2px solid #333; }

        .print-btn { margin-top: 12px; text-align: right; }
        .print-btn button { background: #2563eb; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; }

        @media print {
            body { background: white; padding: 0; min-height: auto; }
            .label-container { box-shadow: none; }
            .print-btn { display: none; }
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
                <div class="header-qr">{!! $qrSvg ?? '' !!}</div>
            </div>

            <div class="content">
                <div class="left-section">
                    <div class="form-row">
                        <div class="field-name">Part Name</div><div class="colon">:</div>
                        <div class="field-value">{{ $part?->part_name_gci ?? ($part?->part_name_vendor ?? '-') }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-name">Model</div><div class="colon">:</div>
                        <div class="field-value">{{ $arrivalItem?->material_group ?? '-' }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-name">Size</div><div class="colon">:</div>
                        <div class="field-value">{{ $arrivalItem?->size ?? '-' }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-name">Vendor</div><div class="colon">:</div>
                        <div class="field-value">{{ $vendorName }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-name">No. Invoice</div><div class="colon">:</div>
                        <div class="field-value">{{ $invoiceNo }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-name">No. Tag</div><div class="colon">:</div>
                        <div class="field-value">{{ $receive->tag ?? '-' }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-name">No. JO</div><div class="colon">:</div>
                        <div class="field-value">{{ $receive->jo_po_number ?? '-' }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-name">Qty / Weight</div><div class="colon">:</div>
                        <div class="field-value">{{ $qtyWeightText }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-name">Qty Coil</div><div class="colon">:</div>
                        <div class="field-value">{{ $qtyCoilText }}</div>
                    </div>
                    <div class="form-row">
                        <div class="field-name">Incoming Date</div><div class="colon">:</div>
                        <div class="field-value">{{ $receive->ata_date?->format('Y-m-d H:i') ?? '-' }}</div>
                    </div>
                </div>

                <div class="right-section">
                    <div class="number-box">{{ $monthBox }}</div>
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
