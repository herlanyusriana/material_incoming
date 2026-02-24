<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $purchaseOrder->po_number }}</title>
    <style>
        @page { size: A4; margin: 20mm; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 11pt; color: #333; line-height: 1.4; margin: 0; padding: 0; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 3px solid #1a202c; padding-bottom: 20px; }
        .company-info h1 { margin: 0; font-size: 24pt; font-weight: 900; color: #1a202c; letter-spacing: -1px; }
        .company-info p { margin: 2px 0; font-size: 9pt; color: #718096; }
        .po-title { text-align: right; }
        .po-title h2 { margin: 0; font-size: 20pt; font-weight: 900; color: #4a5568; }
        .po-title p { margin: 2px 0; font-size: 11pt; font-weight: bold; color: #2d3748; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px; }
        .info-box h3 { margin: 0 0 10px 0; font-size: 10pt; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; color: #a0aec0; border-bottom: 1px solid #edf2f7; padding-bottom: 5px; }
        .info-box p { margin: 2px 0; font-weight: 600; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f7fafc; text-align: left; padding: 12px 10px; font-size: 9pt; font-weight: 900; text-transform: uppercase; color: #4a5568; border-bottom: 2px solid #e2e8f0; }
        td { padding: 12px 10px; border-bottom: 1px solid #edf2f7; font-size: 10pt; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        
        .totals { margin-left: auto; width: 300px; }
        .total-row { display: flex; justify-content: space-between; padding: 10px 0; }
        .total-row.grand { border-top: 2px solid #1a202c; font-weight: 900; font-size: 14pt; margin-top: 10px; padding-top: 15px; }
        
        .notes-section { margin-top: 40px; border-top: 1px solid #edf2f7; padding-top: 20px; }
        .notes-section h4 { margin: 0 0 10px 0; font-size: 10pt; font-weight: 900; color: #a0aec0; }
        .notes-section p { font-size: 9pt; color: #4a5568; font-style: italic; }
        
        .signatures { margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .sig-box { text-align: center; }
        .sig-line { border-top: 1px solid #2d3748; margin-top: 60px; width: 200px; margin-left: auto; margin-right: auto; }
        .sig-name { font-weight: 900; font-size: 10pt; margin-top: 5px; }
        .sig-label { font-size: 8pt; color: #a0aec0; text-transform: uppercase; font-weight: bold; }
        
        @media print {
            .no-print { display: none; }
            body { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="background: #fdf2f2; padding: 10px; text-align: center; border-bottom: 1px solid #feb2b2; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 8px 20px; background: #c53030; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Print Document</button>
    </div>

    <div class="header">
        <div class="company-info">
            <h1>GCI POWER</h1>
            <p>Manufacturing & Logistics Center</p>
            <p>Industrial Area Ste 100, Jakarta, Indonesia</p>
            <p>Tel: +62 21 555 1234 | Email: purchasing@gci-power.com</p>
        </div>
        <div class="po-title">
            <h2>PURCHASE ORDER</h2>
            <p>{{ $purchaseOrder->po_number }}</p>
            <p style="font-size: 9pt; font-weight: normal; margin-top: 5px;">Date: {{ $purchaseOrder->created_at->format('M d, Y') }}</p>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h3>Vendor Information</h3>
            <p>{{ $purchaseOrder->vendor?->vendor_name }}</p>
            <p style="font-size: 9pt; font-weight: normal; color: #718096;">Code: {{ $purchaseOrder->vendor?->vendor_code }}</p>
            <!-- Add more vendor details if available in Vendor model -->
        </div>
        <div class="info-box">
            <h3>Shipping / Terms</h3>
            <p>Standard Shipping</p>
            <p style="font-size: 9pt; font-weight: normal; color: #718096;">Payment: Net 30 Days</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="50">No</th>
                <th>Part Description</th>
                <th width="100" class="text-right">Quantity</th>
                <th width="120" class="text-right">Unit Price</th>
                <th width="120" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseOrder->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <div style="font-weight: 900;">{{ $item->part?->part_no }}</div>
                        <div style="font-size: 8pt; color: #718096;">{{ $item->part?->part_name }}</div>
                        @if ($item->vendorPart)
                            <div style="font-size: 7pt; color: #4c51bf; font-family: monospace;">Vendor Part: {{ $item->vendorPart->part_no }}</div>
                        @endif
                    </td>
                    <td class="text-right font-mono">{{ number_format($item->qty, 4) }}</td>
                    <td class="text-right font-mono">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right font-mono" style="font-weight: 900;">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="total-row">
            <span style="color: #a0aec0; font-weight: bold; text-transform: uppercase; font-size: 9pt;">Subtotal</span>
            <span class="font-mono">{{ number_format($purchaseOrder->total_amount, 2) }}</span>
        </div>
        <div class="total-row">
            <span style="color: #a0aec0; font-weight: bold; text-transform: uppercase; font-size: 9pt;">Tax (0%)</span>
            <span class="font-mono">0.00</span>
        </div>
        <div class="total-row grand">
            <span>TOTAL</span>
            <span>{{ number_format($purchaseOrder->total_amount, 2) }}</span>
        </div>
    </div>

    @if ($purchaseOrder->notes)
        <div class="notes-section">
            <h4>Important Notes</h4>
            <p>{{ $purchaseOrder->notes }}</p>
        </div>
    @endif

    <div class="signatures">
        <div class="sig-box">
            <div class="sig-label">Authorized By</div>
            <div class="sig-line"></div>
            <div class="sig-name">{{ $purchaseOrder->approvedBy?->name ?? '________________' }}</div>
            <div class="sig-label">Purchasing Manager</div>
        </div>
        <div class="sig-box">
            <div class="sig-label">Vendor Acknowledgement</div>
            <div class="sig-line"></div>
            <div class="sig-name">________________</div>
            <div class="sig-label">Name & Stamp</div>
        </div>
    </div>

    <div style="position: fixed; bottom: 0; width: 100%; border-top: 1px solid #edf2f7; padding-top: 10px; font-size: 8pt; color: #a0aec0; text-align: center;">
        Page 1 of 1 | Printed on {{ date('Y-m-d H:i:s') }} | System Generated PO
    </div>
</body>
</html>
