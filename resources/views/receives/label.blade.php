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
    
    $qtyWeightText = ($goodsUnit === 'COIL' || ($goodsUnit === 'SHEET' && (float)$receive->net_weight > 0))
        ? number_format((float)($receive->net_weight ?? $receive->weight ?? 0), 2) . ' KGM'
        : number_format((float)($receive->qty ?? 0), 0) . ' ' . $goodsUnit;

    $qtySecondaryText = in_array($goodsUnit, ['COIL', 'SHEET']) 
        ? number_format((float)($receive->qty ?? 0), 0) . ' ' . $goodsUnit
        : '-';

    $monthBox = str_pad((string)($monthNumber ?? (int)optional($receive->ata_date)->format('m')), 2, '0', STR_PAD_LEFT);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Material Label Card</title>
    <style>
        @page { size: 150mm 100mm; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-print-color-adjust: exact; }
        body { font-family: "Helvetica Neue", Arial, sans-serif; background: #eee; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        
        .label-outer { width: 148mm; height: 98mm; background: #fff; border: 3px solid #000; display: flex; flex-direction: column; overflow: hidden; }
        
        /* HEADER SECTION */
        header { height: 22mm; border-bottom: 3px solid #000; display: flex; align-items: center; justify-content: space-between; padding: 0 8mm; }
        .h-left { display: flex; align-items: center; gap: 5mm; }
        .gl-circle { width: 14mm; height: 14mm; border: 2.5px solid #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 20px; }
        .h-title { font-size: 24px; font-weight: 900; letter-spacing: 4px; }
        .month-chip { width: 14mm; height: 14mm; background: #000; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 900; border-radius: 2mm; }

        /* BODY SECTION */
        main { display: flex; flex: 1; min-height: 0; }
        .data-grid { width: 62%; border-right: 3px solid #000; display: flex; flex-direction: column; }
        .side-grid { width: 38%; display: flex; flex-direction: column; }

        /* DATA ROWS - Total 10 rows, shared height */
        .row { flex: 1; border-bottom: 1px solid #000; display: flex; align-items: center; padding: 0 5mm; }
        .row:last-child { border-bottom: none; }
        .val { font-size: 10pt; font-weight: 800; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* QR AREA */
        .qr-section { height: 50mm; display: flex; align-items: center; justify-content: center; border-bottom: 3px solid #000; padding: 3mm; }
        .qr-section svg { width: 44mm !important; height: 44mm !important; }

        /* IQC AREA */
        .iqc-section { flex: 1; display: flex; flex-direction: column; }
        .iqc-head { background: #e0e0e0; text-align: center; border-bottom: 1.5px solid #000; font-size: 11px; font-weight: 900; padding: 1.5mm 0; letter-spacing: 1px; }
        .iqc-boxes { flex: 1; display: flex; }
        .iqc-box { flex: 1; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; color: #333; }
        .iqc-box:first-child { border-right: 2px solid #000; }

        /* PRINT CONTROL */
        .no-print { position: fixed; bottom: 20px; right: 20px; background: #000; color: #fff; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .no-print:hover { transform: scale(1.05); background: #333; }

        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="label-outer">
        <header>
            <div class="h-left">
                <div class="gl-circle">GL</div>
                <div class="h-title">LABEL MATERIAL</div>
            </div>
            <div class="month-chip">{{ $monthBox }}</div>
        </header>
        
        <main>
            <section class="data-grid">
                <div class="row"><div class="val">{{ $incomingPartNo }}</div></div>
                <div class="row"><div class="val">{{ $partName }}</div></div>
                <div class="row"><div class="val">{{ $arrivalItem?->material_group ?? '-' }}</div></div>
                <div class="row"><div class="val">{{ $arrivalItem?->size ?? '-' }}</div></div>
                <div class="row"><div class="val">{{ $vendorName }}</div></div>
                <div class="row"><div class="val">{{ $invoiceNo }}</div></div>
                <div class="row"><div class="val">{{ $resolvedTag }}</div></div>
                <div class="row"><div class="val">{{ $qtyWeightText }}</div></div>
                <div class="row"><div class="val">{{ $qtySecondaryText }}</div></div>
                <div class="row"><div class="val">{{ $receive->ata_date?->format('Y-m-d H:i') ?? '-' }}</div></div>
            </section>
            
            <section class="side-grid">
                <div class="qr-section">
                    {!! $qrSvg ?? '' !!}
                </div>
                <div class="iqc-section">
                    <div class="iqc-head">IQC CHECK</div>
                    <div class="iqc-boxes">
                        <div class="iqc-box">STAMP</div>
                        <div class="iqc-box">TTD</div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <button class="no-print" onclick="window.print()">PRINT LABEL</button>
</body>
</html>
