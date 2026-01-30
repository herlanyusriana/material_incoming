@php
    $arrivalItem = $receive->arrivalItem;
    $arrival = $arrivalItem?->arrival;
    $part = $arrivalItem?->part;
    $vendorName = $arrival?->vendor?->vendor_name ?? '-';
    $invoiceNo = $arrival?->invoice_no ?? '-';
    $storageLocation = strtoupper(trim((string) ($receive->location_code ?? '')));
    $warehouseMeta = [];
    if (isset($warehouseLocation) && $warehouseLocation) {
        if ($warehouseLocation->class) $warehouseMeta[] = 'CLASS ' . $warehouseLocation->class;
        if ($warehouseLocation->zone) $warehouseMeta[] = 'ZONE ' . $warehouseLocation->zone;
    }
    $warehouseMetaText = $warehouseMeta ? implode(' • ', $warehouseMeta) : null;
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
            width: 150mm;
            height: 100mm;
            border: 2px solid #333;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(to right, #e8f4f8, #ffffff);
            padding: 8mm 12mm;
            border-bottom: 2px solid #333;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 22mm;
        }

        .header-left { display: flex; align-items: center; gap: 12px; }

        .logo {
            width: 42px;
            height: 42px;
            border: 2px solid #4a90e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            color: #4a90e2;
            background: white;
        }

        .header-title {
            font-size: 26px;
            font-weight: bold;
            color: #4a90e2;
            letter-spacing: 2px;
        }

        .header-month {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #e91e63, #f48fb1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 28px;
            border-radius: 6px;
        }

        .content { display: flex; flex: 1; overflow: hidden; }
        .left-section { width: 90mm; border-right: 2px solid #333; display: flex; flex-direction: column; overflow: hidden; }
        .right-section { width: 60mm; border-left: none; display: flex; flex-direction: column; }

        .qr-box {
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 50mm;
            border-bottom: 2px solid #333;
            padding: 5mm;
            width: 100%;
        }

        .qr-box svg { 
            width: 100% !important; 
            height: 100% !important; 
            display: block; 
        }

        .form-row { display: flex; border-bottom: 1px solid #333; flex: 1; min-height: 0; }
        .form-row:last-child { border-bottom: none; }

        .field-name {
            width: 35mm;
            padding: 2mm 5mm;
            font-weight: 600;
            background: #e8f4f8;
            border-right: 1px solid #333;
            font-size: 11pt;
            display: flex;
            align-items: center;
        }

        .colon {
            width: 6mm;
            padding: 2mm 0;
            text-align: center;
            border-right: 1px solid #333;
            background: #f9f9f9;
            font-size: 11pt;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .field-value { 
            flex: 1; 
            padding: 2mm 5mm; 
            font-size: 12pt; 
            font-weight: 600;
            display: flex; 
            flex-direction: column; 
            justify-content: center;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .iqc-section { flex: 1; display: flex; flex-direction: column; }
        .iqc-title { text-align: center; padding: 6px; font-weight: bold; background: #f0f0f0; border-bottom: 1px solid #333; font-size: 12px; }
        .stamp-section { display: flex; flex: 1; }
        .stamp-box { flex: 1; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; color: #666; }
        .stamp-box:first-child { border-right: 2px solid #333; }

        .print-btn { margin-top: 12px; text-align: right; }
        .print-btn button { background: #2563eb; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; }

        @page {
            size: 150mm 100mm;
            margin: 0;
        }

        @media print {
            body { background: white; padding: 0; min-height: auto; }
            .label-container { box-shadow: none; border: 2px solid #333; }
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
                <div class="header-month">{{ $monthBox }}</div>
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
