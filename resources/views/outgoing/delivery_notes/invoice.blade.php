<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commercial Invoice - {{ $delivery_note->dn_no }}</title>
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
            background: #ccc;
        }

        .page-container {
            width: 190mm;
            margin: 0 auto;
            background: white;
            padding: 10mm;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .page-container {
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            .no-print {
                display: none;
            }
        }

        .header {
            text-align: center;
            font-weight: bold;
            font-size: 28px;
            letter-spacing: 6px;
            margin-bottom: 15px;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        th, td {
            border: 1px solid #000;
            padding: 5px 8px;
            vertical-align: top;
        }

        .bold { font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .no-border-bottom { border-bottom: none; }
        .no-border-top { border-top: none; }

        .item-row td {
            border-top: none;
            border-bottom: none;
            padding-top: 3px;
            padding-bottom: 3px;
        }

        .footer-signature {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }

        .signature-box {
            width: 200px;
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            margin-top: 50px;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; font-weight: bold;">PRINT INVOICE</button>
    </div>

    @php
        $poRefs = $delivery_note->items
            ->map(fn($i) => $i->outgoing_po_item_id ? ($i->outgoingPoItem?->outgoingPo?->po_no) : ($i->customerPo?->po_no))
            ->filter()->unique()->implode(', ');

        $totalQty = 0;
        $totalAmount = 0;
    @endphp

    <div class="page-container">
        <div class="header">COMMERCIAL &nbsp; INVOICE</div>

        <table>
            {{-- Row 1: Shipper + Invoice No --}}
            <tr>
                <td colspan="3" width="55%" rowspan="2" style="border-bottom: none;">
                    <div class="bold">Shipper Exporter</div>
                    <div>PT.GEUM CHEON INDO</div>
                    <div>NPWP 02.006.378.0.415.000</div>
                    <div style="margin-top: 5px;">Jl. Raya Serang KM. 12 DS. Sukadamai</div>
                    <div>Tangerang</div>
                    <div style="margin-top: 5px;">Telp: (021) 59400240, 59402454. Fax: 59401224</div>
                </td>
                <td colspan="3" width="45%">
                    <div class="bold">8. &nbsp; No. & Date of Invoice</div>
                    <div style="display: flex; justify-content: space-between; margin-top: 5px; padding: 0 10px;">
                        <span>{{ $invoiceNo }}</span>
                        <span>{{ $delivery_note->delivery_date->format('d M Y') }}</span>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <div class="bold">9. &nbsp; No. & Date of L/C</div>
                    <div style="height: 20px;"></div>
                </td>
            </tr>

            {{-- Row 2: For Account & Risk + L/C Issuing Bank --}}
            <tr>
                <td colspan="3" style="border-top: none;">
                    <div class="bold">For Account & Risk of Messers.</div>
                    <div>{{ $delivery_note->customer->name }}</div>
                    <div style="margin-top: 5px;">{{ $delivery_note->customer->address }}</div>
                </td>
                <td colspan="3">
                    <div class="bold">10. L/C Issuing Bank</div>
                    <div style="height: 20px;"></div>
                </td>
            </tr>

            {{-- Row 3: Notify Party + Remarks --}}
            <tr>
                <td colspan="3">
                    <div class="bold">Notify Party</div>
                    <div>Same As Above</div>
                </td>
                <td colspan="3" rowspan="3">
                    <div class="bold">11. Remarks :</div>
                    <div style="height: 60px;"></div>
                </td>
            </tr>

            {{-- Row 4: Port of Loading + Final Destination --}}
            <tr>
                <td width="27%">
                    <div>Port of Loading</div>
                    <div>**********</div>
                </td>
                <td colspan="2" width="28%">
                    <div class="bold">5. &nbsp; Final Destination</div>
                    <div style="margin-left: 10px;">{{ $delivery_note->customer->name }}</div>
                </td>
            </tr>

            {{-- Row 5: Carrier + Sailing --}}
            <tr>
                <td>
                    <div>Carrier</div>
                </td>
                <td colspan="2">
                    <div class="bold">7. &nbsp; Sailing On Or About</div>
                    <div style="margin-left: 10px; color: #333;">{{ $delivery_note->delivery_date->format('d F Y') }}</div>
                </td>
            </tr>

            {{-- Items Header --}}
            <tr>
                <td colspan="2" class="bold text-center" style="border-bottom: 1px solid #000;">
                    <span>Marks and No of Pkgs</span>
                </td>
                <td class="bold text-center" style="border-bottom: 1px solid #000;">
                    13 Description of Goods
                </td>
                <td class="bold text-center" style="border-bottom: 1px solid #000;">
                    14. &nbsp; Quantity /Unit
                </td>
                <td class="bold text-center" style="border-bottom: 1px solid #000;">
                    15. &nbsp; Unit Price
                </td>
                <td class="bold text-center" style="border-bottom: 1px solid #000;">
                    16. &nbsp; Amount
                </td>
            </tr>

            {{-- Items --}}
            @foreach($delivery_note->items as $index => $item)
                @php
                    $price = $item->outgoingPoItem?->price ?? $item->customerPo?->price ?? 0;
                    $price = (float) $price;
                    $qty = (float) $item->qty;
                    $amount = $qty * $price;
                    $totalQty += $qty;
                    $totalAmount += $amount;
                    $uom = $item->part?->standardPacking?->uom ?? 'Pcs';
                @endphp
                <tr class="item-row">
                    <td colspan="2">
                        {{ $index + 1 }} &nbsp; {{ $item->part?->part_name }}
                    </td>
                    <td>
                        {{ $item->part?->part_no }}
                    </td>
                    <td class="text-right">
                        {{ number_format($qty, 0, ',', '.') }} &nbsp; {{ $uom }}
                    </td>
                    <td class="text-right">
                        IDR &nbsp; {{ number_format($price, 0, ',', '.') }}
                    </td>
                    <td class="text-right">
                        IDR &nbsp; {{ number_format($amount, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach

            {{-- Empty space to fill --}}
            <tr>
                <td colspan="2" style="border-top: none; border-bottom: none; height: 100px;"></td>
                <td style="border-top: none; border-bottom: none;"></td>
                <td style="border-top: none; border-bottom: none;"></td>
                <td style="border-top: none; border-bottom: none;"></td>
                <td style="border-top: none; border-bottom: none;"></td>
            </tr>

            {{-- Total Row --}}
            <tr style="border-top: 1px solid #000;">
                <td colspan="2" class="bold" style="border-top: 2px solid #000;">
                    Total
                </td>
                <td style="border-top: 2px solid #000;"></td>
                <td class="text-right bold" style="border-top: 2px solid #000;">
                    {{ number_format($totalQty, 0, ',', '.') }} &nbsp; Pcs
                </td>
                <td style="border-top: 2px solid #000;"></td>
                <td class="text-right bold" style="border-top: 2px solid #000;">
                    IDR &nbsp; {{ number_format($totalAmount, 0, ',', '.') }}
                </td>
            </tr>
        </table>

        {{-- PPN + Grand Total --}}
        <table style="border: none; margin-top: 0;">
            @php
                $ppnRate = 11;
                $ppn = round($totalAmount * $ppnRate / 100);
                $grandTotal = $totalAmount + $ppn;
            @endphp
            <tr>
                <td colspan="4" style="border: none;"></td>
                <td class="text-right" style="border: none;">PPN {{ $ppnRate }}%</td>
                <td class="text-right" style="border: none;">Rp</td>
                <td class="text-right" style="border: none; width: 120px;">{{ number_format($ppn, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="4" style="border: none;"></td>
                <td class="text-right bold" style="border: none;">Grand Total</td>
                <td class="text-right" style="border: none; border-top: 1px solid #000;">Rp</td>
                <td class="text-right bold" style="border: none; border-top: 1px solid #000; width: 120px;">{{ number_format($grandTotal, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div class="footer-signature">
            <div class="signature-box">
                <div style="height: 60px;"></div>
                <div class="bold" style="font-family: 'Times New Roman', serif; font-size: 14px;">HWANG &nbsp; MINHA</div>
                <div class="signature-line"></div>
                <div>17. Signed By</div>
            </div>
        </div>
    </div>
</body>

</html>