<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receive Label {{ $receive->tag }}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 24px; color: #111827; }
        .label { border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; max-width: 520px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .title { font-size: 20px; font-weight: 700; }
        .meta { font-size: 12px; color: #6b7280; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; font-size: 14px; }
        .field { padding: 10px; border: 1px solid #e5e7eb; border-radius: 10px; background: #f9fafb; }
        .field span { display: block; color: #6b7280; font-size: 12px; margin-bottom: 4px; }
        .print-btn { margin-top: 16px; text-align: right; }
        .print-btn button { background: #2563eb; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; }
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
            .label { border: 1px solid #111827; }
        }
    </style>
</head>
<body>
    <div class="label">
        <div class="header">
            <div>
                <div class="title">Receive Tag {{ $receive->tag }}</div>
                <div class="meta">Departure {{ $receive->arrivalItem->arrival->arrival_no }} · {{ $receive->arrivalItem->arrival->vendor->vendor_name ?? 'Vendor' }}</div>
            </div>
            <div class="meta">Printed {{ now()->format('Y-m-d H:i') }}</div>
        </div>

        <div class="grid">
            <div class="field">
                <span>Part</span>
                <div>{{ $receive->arrivalItem->part->part_no }}</div>
                <div class="meta">{{ $receive->arrivalItem->part->part_name_vendor }}</div>
            </div>
            <div class="field">
                <span>Qty</span>
                <div>{{ number_format($receive->qty) }} {{ strtoupper($receive->qty_unit ?? '') }}</div>
            </div>
            <div class="field">
                <span>Bundle</span>
                <div>{{ number_format($receive->bundle_qty ?? 1) }} {{ strtoupper($receive->bundle_unit ?? '-') }}</div>
            </div>
            <div class="field">
                <span>QC Status</span>
                <div>{{ strtoupper($receive->qc_status) }}</div>
            </div>
            <div class="field">
                <span>ATA</span>
                <div>{{ $receive->ata_date?->format('Y-m-d H:i') }}</div>
            </div>
            <div class="field">
                <span>Weight</span>
                <div>
                    {{ number_format($receive->net_weight ?? $receive->weight ?? 0, 2) }} KGM
                </div>
            </div>
            <div class="field">
                <span>PO Number</span>
                <div>{{ $receive->jo_po_number ?? '-' }}</div>
            </div>
            <div class="field">
                <span>Location Code</span>
                <div>{{ $receive->location_code ?? '-' }}</div>
            </div>
        </div>

        <div class="print-btn">
            <button onclick="window.print()">Print Label</button>
        </div>
    </div>
</body>
</html>
