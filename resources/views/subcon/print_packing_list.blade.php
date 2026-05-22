@php
    $line = $lines->first();
    $sentDate = $subconOrder->sent_date ?? now();
    $orderSeq = (int) substr((string) $subconOrder->order_no, -3);
    $plNo = sprintf('%03d/GCI/N/%s/%s', $orderSeq ?: (int) $subconOrder->id, $sentDate->format('n'), $sentDate->format('Y'));
    $shipperName = 'PT.GEUM CHEON INDO';
    $shipperNpwp = '02.006.378.0.415.000';
    $shipperAddress = ['Jl. Raya Serang KM. 12 DS. Sukadamai', 'Tangerang'];
    $shipperTelp = '(021) 59400240, 59402454';
    $shipperFax = '59401224';
    $consigneeName = strtoupper((string) ($subconOrder->vendor->vendor_name ?? '-'));
    $consigneeNpwp = '02.286.818.6-451.000';
    $consigneeAddress = preg_split('/\r\n|\r|\n/', trim((string) ($subconOrder->vendor->address ?? ''))) ?: [];
    $qty = (float) ($line['qty'] ?? 0);
    $boxQty = (int) ($line['box_qty'] ?? 0);
    $packingUom = $line['packing_uom'] ?? 'Box';
    $netWeight = (float) ($line['weight_kgm'] ?? 0);
    $grossWeight = (float) ($line['gross_weight_kgm'] ?? $netWeight);
    $partName = (string) ($line['part_name'] ?? '-');
    $partNo = (string) ($line['part_no'] ?? '-');
    $uom = $line['uom'] ?? 'Pcs';
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Packing List - {{ $plNo }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            margin: 0;
            background: #d1d5db;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }

        .toolbar {
            width: 210mm;
            margin: 12px auto;
            text-align: right;
        }

        .toolbar button {
            border: 0;
            border-radius: 8px;
            background: #111827;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
            padding: 9px 14px;
        }

        .page {
            width: 210mm;
            height: 297mm;
            margin: 0 auto 18px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.16);
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
        }

        .title {
            height: 24mm;
            border: 1px solid #000;
            border-bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td,
        th {
            border: 1px solid #000;
            vertical-align: top;
            padding: 5px 7px;
        }

        .top-table {
            height: 174mm;
        }

        .left {
            width: 49%;
        }

        .mid-no {
            width: 5%;
            text-align: center;
            font-size: 16px;
        }

        .right {
            width: 46%;
        }

        .section-title {
            font-size: 16px;
            font-weight: 800;
        }

        .section-body {
            margin-left: 20px;
            margin-top: 12px;
            font-size: 14px;
            line-height: 1.45;
        }

        .shipper-row {
            height: 54mm;
        }

        .consignee-row {
            height: 46mm;
        }

        .buyer-row {
            height: 20mm;
        }

        .port-row {
            height: 20mm;
        }

        .carrier-row {
            height: 19mm;
        }

        .pl-info {
            display: flex;
            justify-content: space-between;
            margin-top: 14px;
            font-size: 14px;
        }

        .remark {
            font-size: 16px;
        }

        .item-head th {
            height: 20mm;
            font-size: 15px;
            font-weight: 400;
            text-align: center;
            vertical-align: middle;
        }

        .items {
            height: 103mm;
        }

        .items td {
            border-top: 0;
            border-bottom: 0;
            font-size: 13px;
            padding-top: 7px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total td {
            height: 9mm;
            vertical-align: middle;
            font-size: 13px;
        }

        .signature {
            height: 47mm;
        }

        .signature td {
            border-top: 0;
        }

        .signed-by {
            position: absolute;
            right: 26mm;
            bottom: 11mm;
            width: 62mm;
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            height: 25mm;
            margin-bottom: 4px;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .page {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <button onclick="window.print()">Print Packing List</button>
    </div>

    <div class="page">
        <div class="title">PACKING LIST</div>

        <table class="top-table">
            <tr class="shipper-row">
                <td class="left">
                    <div class="section-title">1. &nbsp;Shipper</div>
                    <div class="section-body">
                        <div>{{ $shipperName }}</div>
                        <div>NPWP : {{ $shipperNpwp }}</div>
                        <br>
                        @foreach ($shipperAddress as $addressLine)
                            <div>{{ $addressLine }}</div>
                        @endforeach
                        <br>
                        <div>Telp: {{ $shipperTelp }}. Fax: {{ $shipperFax }}</div>
                    </div>
                </td>
                <td class="mid-no">8.</td>
                <td class="right">
                    <div class="remark">Packing List No. &amp; Date</div>
                    <div class="pl-info">
                        <span>{{ $plNo }}</span>
                        <span>{{ $sentDate->format('d M Y') }}</span>
                    </div>
                </td>
            </tr>
            <tr class="consignee-row">
                <td>
                    <div class="section-title">2. &nbsp;Consignee</div>
                    <div class="section-body">
                        <div>{{ $consigneeName }}</div>
                        <div>NPWP : {{ $consigneeNpwp }}</div>
                        @forelse ($consigneeAddress as $addressLine)
                            <div>{{ $addressLine }}</div>
                        @empty
                            <div>-</div>
                        @endforelse
                    </div>
                </td>
                <td class="mid-no" rowspan="4">9.</td>
                <td rowspan="4">
                    <div class="remark">Remark :</div>
                </td>
            </tr>
            <tr class="buyer-row">
                <td>
                    <div class="remark">3. &nbsp;Buyer</div>
                    <div class="section-body" style="margin-top: 8px;">Same As Consignee</div>
                </td>
            </tr>
            <tr class="port-row">
                <td style="padding:0;">
                    <table>
                        <tr>
                            <td style="width:38%; height:20mm;">
                                <div class="remark">4. &nbsp;Port of Loading</div>
                                <div class="section-body" style="margin-top:8px;">**********</div>
                            </td>
                            <td style="height:20mm;">
                                <div class="remark">5. &nbsp;Final Destination</div>
                                <div class="section-body" style="margin-top:8px;">{{ $consigneeName }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="carrier-row">
                <td style="padding:0;">
                    <table>
                        <tr>
                            <td style="width:38%; height:19mm;">
                                <div class="remark">6. &nbsp;Carrier</div>
                            </td>
                            <td style="height:19mm;">
                                <div class="remark">7. &nbsp;Sailing On Of About</div>
                                <div class="section-body" style="margin-top:8px;">{{ $sentDate->format('d M Y') }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table>
            <tr class="item-head">
                <th style="width:18%;">Marks &amp; No<br>Of Pkgs</th>
                <th style="width:31%;">11. Description Of Goods</th>
                <th style="width:7%;">12.</th>
                <th style="width:14%;">Qty</th>
                <th style="width:15%;">13. &nbsp;Net Weight</th>
                <th style="width:15%;">14. &nbsp;Gross Weight</th>
            </tr>
            <tr class="items">
                <td>{{ $partName }}</td>
                <td>{{ $partNo }}</td>
                <td class="text-right">{{ $boxQty ?: '' }}</td>
                <td>{{ $boxQty ? $packingUom : '' }} <span style="float:right;">{{ number_format($qty, 0, ',', '.') }} &nbsp;{{ ucfirst(strtolower($uom)) }}</span></td>
                <td class="text-right">{{ number_format($netWeight, 2, ',', '.') }} &nbsp; Kg</td>
                <td class="text-right">{{ number_format($grossWeight, 2, ',', '.') }} &nbsp; Kg</td>
            </tr>
            <tr class="total">
                <td class="text-center">Total</td>
                <td></td>
                <td class="text-right">{{ $boxQty ?: '' }}</td>
                <td>{{ $boxQty ? $packingUom : '' }} <span style="float:right;">{{ number_format($qty, 0, ',', '.') }} &nbsp;{{ ucfirst(strtolower($uom)) }}</span></td>
                <td class="text-right">{{ number_format($netWeight, 2, ',', '.') }} &nbsp; Kg</td>
                <td class="text-right">{{ number_format($grossWeight, 2, ',', '.') }} &nbsp; Kg</td>
            </tr>
            <tr class="signature">
                <td colspan="3"></td>
                <td colspan="3">
                    <div style="margin-top:28mm; font-weight:800;">15. &nbsp; Signed By</div>
                </td>
            </tr>
        </table>

        <div class="signed-by">
            <div class="signature-line"></div>
            <div style="font-weight:800;">HWANG MINHA</div>
        </div>
    </div>
</body>

</html>
