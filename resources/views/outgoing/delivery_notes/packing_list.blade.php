<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packing List - {{ $delivery_note->dn_no }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            /* Slightly smaller to fit better */
            margin: 0;
            padding: 20px;
            background: #ccc;
            /* Gray background on screen */
        }

        .page-container {
            width: 190mm;
            /* A4 width */
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
        }

        .header {
            text-align: center;
            font-weight: bold;
            font-size: 24px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }

        .section-header {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .no-border-bottom {
            border-bottom: none;
        }

        .no-border-top {
            border-top: none;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
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

        @media print {
            .no-print {
                display: none;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">PRINT PACKING LIST</button>
    </div>

    <div class="page-container">
        <div class="header">PACKING LIST</div>

        <table>
            <!-- Sections 1-8 -->
            <tr>
                <td colspan="2" width="50%">
                    <div class="bold">1. Shipper</div>
                    <div style="margin-left: 10px;">
                        <div class="bold">PT. GCI INDONESIA</div>
                        <div>Kawasan Industri Bekasi Fajar, Blok A-1, MM2100</div>
                        <div>Cikarang Barat, Bekasi 17520</div>
                        <div>Telp: (021) 8998 ... Fax: (021) ...</div>
                    </div>
                </td>
                <td colspan="3" width="50%">
                    <div class="bold">8. Packing List No. & Date</div>
                    <div style="display: flex; justify-content: space-between; margin-top: 10px; padding: 0 10px;">
                        <span>{{ $delivery_note->dn_no }}</span>
                        <span>{{ $delivery_note->delivery_date->format('d M Y') }}</span>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="bold">2. Consignee</div>
                    <div style="margin-left: 10px;">
                        <div class="bold">{{ $delivery_note->customer->name }}</div>
                        <div>{{ $delivery_note->customer->address }}</div>
                    </div>
                </td>
                <td colspan="3" rowspan="2">
                    <div class="bold">9. Remark :</div>
                    <div style="height: 100px;"></div>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <div class="bold">3. Buyer</div>
                    <div style="margin-left: 10px;">Same As Consignee</div>
                </td>
            </tr>
            <tr>
                <td width="25%">
                    <div class="bold">4. Port of Loading</div>
                    <div style="margin-left: 10px;">**********</div>
                </td>
                <td width="25%">
                    <div class="bold">5. Final Destination</div>
                    <div style="margin-left: 10px;">{{ $delivery_note->customer->name }}</div>
                </td>
                <td colspan="3" rowspan="2">
                    <!-- Empty space beside Carrier/Sailing -->
                </td>
            </tr>
            <tr>
                <td>
                    <div class="bold">6. Carrier</div>
                    <div>&nbsp;</div>
                </td>
                <td>
                    <div class="bold">7. Sailing On Of About</div>
                    <div style="margin-left: 10px;">{{ $delivery_note->delivery_date->format('d F Y') }}</div>
                </td>
            </tr>

            <!-- Items Header -->
            <tr class="header-row">
                <td colspan="2" class="bold" style="border-bottom: 1px solid #000;">
                    <span style="margin-right: 15px;">10.</span>Marks & No Of Pkgs<br>
                    <span style="margin-right: 15px;">11.</span>Description Of Goods
                </td>
                <td class="text-center bold" width="10%" style="border-bottom: 1px solid #000;">12. Qty</td>
                <td class="text-center bold" width="15%" style="border-bottom: 1px solid #000;">13. Net Weight</td>
                <td class="text-center bold" width="15%" style="border-bottom: 1px solid #000;">14. Gross Weight</td>
            </tr>

            @php
                $totalPkgs = 0;
                $totalNet = 0;
                $totalGross = 0;
                $totalQty = 0;
            @endphp

            @foreach($delivery_note->items as $item)
                @php
                    $stdPack = $item->part->standardPacking->packing_qty ?? 1; // Avoid division by zero
                    $pkgs = ceil($item->qty / $stdPack);
                    $totalPkgs += $pkgs;

                    $net = $item->qty * ($item->part->net_weight ?? 0);
                    $gross = $item->qty * ($item->part->gross_weight ?? 0);

                    $totalNet += $net;
                    $totalGross += $gross;
                    $totalQty += $item->qty;
                @endphp
                <tr class="item-row">
                    <td colspan="2" style="border-bottom: none; border-top: none; padding-top: 2px; padding-bottom: 2px;">
                        <div style="display: grid; grid-template-columns: 2fr 1fr 2fr; align-items: start;">
                            <span>{{ $item->part->part_name }}</span>
                            <span class="text-center"><b>{{ $pkgs }}</b> Bx</span>
                            <span>{{ $item->part->part_no }}</span>
                        </div>
                    </td>
                    <td class="text-right" style="border-bottom: none; border-top: none; vertical-align: top;">
                        {{ number_format($item->qty, 0, ',', '.') }} {{ $item->part->uom ?? 'Pcs' }}
                    </td>
                    <td class="text-right" style="border-bottom: none; border-top: none; vertical-align: top;">
                        {{ number_format($net, 3, ',', '.') }} Kg
                    </td>
                    <td class="text-right" style="border-bottom: none; border-top: none; vertical-align: top;">
                        {{ number_format($gross, 3, ',', '.') }} Kg
                    </td>
                </tr>
            @endforeach

            <!-- Spacer Row to fill height if needed or just close the table bottom -->
            <tr style="height: 20px;">
                <td colspan="2" style="border-top: none;"></td>
                <td style="border-top: none;"></td>
                <td style="border-top: none;"></td>
                <td style="border-top: none;"></td>
            </tr>

            <!-- Total Row -->
            <tr style="border-top: 1px solid #000; border-bottom: 1px solid #000;">
                <td colspan="2" class="bold">
                    <div style="display: grid; grid-template-columns: 2fr 1fr 2fr; align-items: center;">
                        <span>Total</span>
                        <span class="text-center">{{ $totalPkgs }} PKGS</span>
                        <span></span>
                    </div>
                </td>
                <td class="text-right bold">{{ number_format($totalQty, 0, ',', '.') }} Pcs</td>
                <td class="text-right bold">{{ number_format($totalNet, 3, ',', '.') }} Kg</td>
                <td class="text-right bold">{{ number_format($totalGross, 3, ',', '.') }} Kg</td>
            </tr>
        </table>

        <div class="footer-signature">
            <div class="signature-box">
                <div style="height: 40px;"></div> <!-- Signature space -->
                <div class="signature-line"></div>
                <div>15. Signed By</div>
                <div class="bold" style="margin-top: 5px;">( HWANG MINHA )</div>
            </div>
        </div>
    </div>

</body>

</html>