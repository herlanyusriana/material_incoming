@php
    $arrivalItem = $receive->arrivalItem;
    $arrival = $arrivalItem?->arrival;
    $part = $arrivalItem?->part;
    $resolvedTag = $receive->tag ?: ($receive->ensureSystemTag() ?? '-');
    $goodsUnit = strtoupper(trim((string) ($arrivalItem?->unit_goods ?? $receive->qty_unit ?? '')));
    $incomingPartNo = $arrivalItem?->display_part_no ?? $part?->part_no ?? $arrivalItem?->gciPartVendor?->vendor_part_no ?? '-';
    $partName = $arrivalItem?->display_part_name ?? $part?->part_name_gci ?? $part?->part_name_vendor ?? '-';
    
    $qtyWeightText = number_format((float)($receive->net_weight ?? $receive->weight ?? 0), 2) . ' KGM';
    $qtySecondaryText = number_format((float)($receive->qty ?? 0), 0) . ' ' . $goodsUnit;
    $monthBox = str_pad((string)($monthNumber ?? (int)optional($receive->ata_date)->format('m')), 2, '0', STR_PAD_LEFT);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Minimalist Material Tag</title>
    <style>
        @page { size: 150mm 100mm; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-print-color-adjust: exact; }
        body { font-family: "Helvetica", Arial, sans-serif; background: #f0f0f0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        
        .label-outer { width: 148mm; height: 98mm; background: #fff; border: 4mm solid #000; display: flex; flex-direction: column; }
        
        /* HEADER */
        header { height: 22mm; border-bottom: 4mm solid #000; display: flex; align-items: center; justify-content: space-between; padding: 0 10mm; }
        .h-title { font-size: 32px; font-weight: 900; letter-spacing: 6px; }
        .month-box { width: 16mm; height: 16mm; background: #000; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 900; border-radius: 2mm; }

        /* CONTENT */
        main { display: flex; flex: 1; min-height: 0; }
        .left-col { width: 62%; border-right: 4mm solid #000; display: flex; flex-direction: column; }
        .right-col { width: 38%; display: flex; flex-direction: column; }

        /* DATA ROWS - 6 Pure Essential Rows */
        .data-row { flex: 1; border-bottom: 2px solid #000; display: flex; align-items: center; padding: 0 8mm; }
        .data-row:last-child { border-bottom: none; }
        
        .val-primary { font-size: 18pt; font-weight: 900; line-height: 1.1; }
        .val-secondary { font-size: 14pt; font-weight: 800; line-height: 1.1; }

        /* QR AREA */
        .qr-area { height: 48mm; display: flex; align-items: center; justify-content: center; border-bottom: 4mm solid #000; padding: 4mm; }
        .qr-area svg { width: 44mm !important; height: 44mm !important; }

        /* IQC AREA */
        .iqc-area { flex: 1; display: flex; flex-direction: column; background: #fff;}
        .iqc-label { background: #000; color: #fff; text-align: center; font-size: 11pt; font-weight: 900; padding: 1.5mm 0; border-bottom: 2px solid #000;}
        .iqc-grid { flex: 1; display: flex; }
        .iqc-pane { flex: 1; display: flex; align-items: flex-start; justify-content: center; font-size: 10pt; font-weight: 800; padding-top: 1mm; color: #555;}
        .iqc-pane:first-child { border-right: 3px solid #000; }

        /* PRINT */
        .btn-print { position: fixed; bottom: 20px; right: 20px; padding: 15px 40px; background: #000; color: #fff; border: none; border-radius: 50px; font-weight: 900; cursor: pointer; box-shadow: 0 5px 20px rgba(0,0,0,0.3); font-size: 16px; }
        @media print { body { background: #fff; padding: 0; } .btn-print { display: none; } }
    </style>
</head>
<body>
    <div class="label-outer">
        <header>
            <div class="h-title">MATERIAL TAG</div>
            <div class="month-box">{{ $monthBox }}</div>
        </header>
        
        <main>
            <section class="left-col">
                <div class="data-row"><div class="val-primary">{{ $incomingPartNo }}</div></div>
                <div class="data-row"><div class="val-secondary">{{ $partName }}</div></div>
                <div class="data-row"><div class="val-secondary">{{ $arrivalItem?->size ?? '-' }}</div></div>
                <div class="data-row"><div class="val-secondary">ID: {{ $resolvedTag }}</div></div>
                <div class="data-row"><div class="val-primary">{{ $qtyWeightText }}</div></div>
                <div class="data-row"><div class="val-primary">{{ $qtySecondaryText }}</div></div>
            </section>
            
            <section class="right-col">
                <div class="qr-area">
                    {!! $qrSvg ?? '' !!}
                </div>
                <div class="iqc-area">
                    <div class="iqc-label">IQC PASS</div>
                    <div class="iqc-grid">
                        <div class="iqc-pane">STAMP</div>
                        <div class="iqc-pane">TTD</div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <button class="btn-print" onclick="window.print()">PRINT LABEL</button>
</body>
</html>
