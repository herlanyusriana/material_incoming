@php
    $arrivalItem = $receive->arrivalItem;
    $arrival = $arrivalItem?->arrival;
    $part = $arrivalItem?->part;
    $resolvedTag = $receive->tag ?: ($receive->ensureSystemTag() ?? '-');
    $vendorName = $arrival?->vendor?->vendor_name ?? '-';
    $vendorType = strtoupper((string) ($arrival?->vendor?->vendor_type ?? '-'));
    $invoiceNo = $arrival?->invoice_no ?? '-';
    $deliveryNoteNo = $receive->delivery_note_no ?? $arrival?->sj_no ?? '-';
    $locationCode = strtoupper(trim((string) ($receive->location_code ?? '-')));
    $goodsUnit = strtoupper(trim((string) ($arrivalItem?->unit_goods ?? $receive->qty_unit ?? '-')));
    $receiveDate = $receive->ata_date?->format('Y-m-d H:i') ?? '-';
    $gciPartNo = $arrivalItem?->gciPart?->part_no ?? $part?->gciPart?->part_no ?? '-';
    $incomingPartNo = $arrivalItem?->display_part_no ?? $part?->part_no ?? $arrivalItem?->gciPartVendor?->vendor_part_no ?? '-';
    $vendorPartNo = $arrivalItem?->gciPartVendor?->vendor_part_no ?? $arrivalItem?->vendorPart?->vendor_part_no ?? '-';
    $partName = $arrivalItem?->display_part_name ?? $part?->part_name_gci ?? $part?->part_name_vendor ?? '-';
    $partModel = $arrivalItem?->material_group ?? '-';
    $partSize = $arrivalItem?->size ?? '-';
    $bundleQty = (int) ($receive->bundle_qty ?? 0);
    $bundleUnit = strtoupper(trim((string) ($receive->bundle_unit ?? '')));
    $qtyText = number_format((float) ($receive->qty ?? 0), 0) . ' ' . $goodsUnit;
    $netWeight = (float) ($receive->net_weight ?? $receive->weight ?? 0);
    $grossWeight = (float) ($receive->gross_weight ?? 0);
    $weightText = $netWeight > 0 ? number_format($netWeight, 2) . ' KGM' : '-';
    $grossText = $grossWeight > 0 ? number_format($grossWeight, 2) . ' KGM' : '-';
    $bundleText = $bundleQty > 0 && $bundleUnit !== '' ? number_format($bundleQty) . ' ' . $bundleUnit : '-';
    $monthBox = str_pad((string) ($monthNumber ?? (int) optional($receive->ata_date)->format('m')), 2, '0', STR_PAD_LEFT);
    $warehouseMeta = [];
    if (isset($warehouseLocation) && $warehouseLocation) {
        if ($warehouseLocation->class) {
            $warehouseMeta[] = 'Class ' . $warehouseLocation->class;
        }
        if ($warehouseLocation->zone) {
            $warehouseMeta[] = 'Zone ' . $warehouseLocation->zone;
        }
    }
    $warehouseMetaText = $warehouseMeta ? implode(' | ', $warehouseMeta) : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label Material</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            padding: 18px;
            color: #0f172a;
        }

        .sheet {
            width: 150mm;
            min-height: 100mm;
            margin: 0 auto;
        }

        .label-card {
            background: #ffffff;
            border: 2px solid #0f172a;
            width: 100%;
            min-height: 100mm;
            display: flex;
            flex-direction: column;
        }

        .header {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            padding: 10mm 10mm 6mm;
            border-bottom: 2px solid #0f172a;
        }

        .brand-row {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo {
            width: 42px;
            height: 42px;
            border: 2px solid #0f172a;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
        }

        .title {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .subtitle {
            margin-top: 3px;
            font-size: 10px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .month-box {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            background: #0f172a;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
        }

        .body {
            display: grid;
            grid-template-columns: 1.55fr 0.85fr;
            flex: 1;
            min-height: 0;
        }

        .info-panel {
            border-right: 2px solid #0f172a;
            padding: 5mm 5mm 4mm;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .hero-block {
            border: 1.5px solid #0f172a;
            border-radius: 10px;
            padding: 4mm;
        }

        .hero-label {
            font-size: 8pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .hero-value {
            font-size: 16pt;
            font-weight: 800;
            line-height: 1.2;
            word-break: break-word;
        }

        .hero-sub {
            margin-top: 5px;
            font-size: 8pt;
            color: #475569;
            line-height: 1.35;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 7px;
        }

        .meta-card {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 3.2mm;
            min-height: 18mm;
        }

        .meta-card.wide {
            grid-column: span 2;
        }

        .meta-label {
            font-size: 7.5pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .meta-value {
            font-size: 10pt;
            font-weight: 700;
            line-height: 1.3;
            word-break: break-word;
        }

        .meta-sub {
            margin-top: 3px;
            font-size: 7.5pt;
            color: #64748b;
            line-height: 1.3;
        }

        .muted {
            color: #64748b;
            font-weight: 600;
            font-size: 8pt;
        }

        .qr-panel {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .qr-wrap {
            flex: 1;
            padding: 6mm 5mm 4mm;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 2px solid #0f172a;
        }

        .qr-wrap svg {
            width: 100% !important;
            height: auto !important;
            max-width: 44mm;
            max-height: 44mm;
            display: block;
        }

        .scan-note {
            padding: 4mm 5mm 3mm;
            border-bottom: 1px solid #0f172a;
            font-size: 8.5pt;
            line-height: 1.35;
        }

        .scan-note strong {
            display: block;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .iqc-title {
            padding: 3mm 5mm;
            text-align: center;
            font-size: 10pt;
            font-weight: 800;
            background: #e5e7eb;
            border-bottom: 1px solid #0f172a;
            text-transform: uppercase;
        }

        .iqc-boxes {
            display: grid;
            grid-template-columns: 1fr 1fr;
            flex: 1;
            min-height: 18mm;
        }

        .iqc-box {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 9pt;
        }

        .iqc-box + .iqc-box {
            border-left: 1px solid #0f172a;
        }

        .print-btn {
            margin-top: 12px;
            text-align: right;
        }

        .print-btn button {
            background: #0f172a;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 700;
            cursor: pointer;
        }

        @page {
            size: 150mm 100mm;
            margin: 0;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }

            .print-btn {
                display: none;
            }

            .sheet {
                margin: 0;
            }

            .label-name,
            .iqc-title {
                background: #e5e7eb !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .month-box {
                background: #0f172a !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="label-card">
            <div class="header">
                <div>
                    <div class="brand-row">
                        <div class="logo">GL</div>
                        <div>
                            <div class="title">Label Material</div>
                            <div class="subtitle">Incoming Standard Label</div>
                        </div>
                    </div>
                </div>
                <div class="month-box">{{ $monthBox }}</div>
            </div>

            <div class="body">
                <div class="info-panel">
                    <div class="hero-block">
                        <div class="hero-label">Part No</div>
                        <div class="hero-value">{{ $incomingPartNo }}</div>
                        <div class="hero-sub">
                            Vendor {{ $vendorPartNo }} | GCI {{ $gciPartNo }}
                        </div>
                    </div>

                    <div class="meta-grid">
                        <div class="meta-card wide">
                            <div class="meta-label">Part Name</div>
                            <div class="meta-value">{{ $partName }}</div>
                        </div>

                        <div class="meta-card">
                            <div class="meta-label">Model</div>
                            <div class="meta-value">{{ $partModel }}</div>
                            <div class="meta-sub">{{ $partSize }}</div>
                        </div>

                        <div class="meta-card">
                            <div class="meta-label">Tag</div>
                            <div class="meta-value">{{ $resolvedTag }}</div>
                        </div>

                        <div class="meta-card wide">
                            <div class="meta-label">Vendor</div>
                            <div class="meta-value">{{ $vendorName }}</div>
                            <div class="meta-sub">Type {{ $vendorType }}</div>
                        </div>

                        <div class="meta-card">
                            <div class="meta-label">Invoice</div>
                            <div class="meta-value">{{ $invoiceNo }}</div>
                            <div class="meta-sub">{{ $deliveryNoteNo }}</div>
                        </div>

                        <div class="meta-card">
                            <div class="meta-label">Location</div>
                            <div class="meta-value">{{ $locationCode }}</div>
                            <div class="meta-sub">{{ $warehouseMetaText ?? 'Belum ada meta lokasi' }}</div>
                        </div>

                        <div class="meta-card">
                            <div class="meta-label">Qty</div>
                            <div class="meta-value">{{ $qtyText }}</div>
                            <div class="meta-sub">Bundle {{ $bundleText }}</div>
                        </div>

                        <div class="meta-card">
                            <div class="meta-label">Weight</div>
                            <div class="meta-value">{{ $weightText }}</div>
                            <div class="meta-sub">Gross {{ $grossText }}</div>
                        </div>

                        <div class="meta-card wide">
                            <div class="meta-label">Receive Date</div>
                            <div class="meta-value">{{ $receiveDate }}</div>
                        </div>
                    </div>
                </div>

                <div class="qr-panel">
                    <div class="qr-wrap">{!! $qrSvg ?? '' !!}</div>
                    <div class="scan-note">
                        <strong>Scan Untuk:</strong>
                        Warehouse scan, WH Supply to Production, dan traceability tag material.
                    </div>
                    <div class="iqc-title">IQC Check</div>
                    <div class="iqc-boxes">
                        <div class="iqc-box">Stamp</div>
                        <div class="iqc-box">TTD</div>
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
