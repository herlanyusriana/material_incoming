@php
    $line = $lines->first();
    $sentDate = $subconOrder->sent_date ?? now();
    $orderSeq = (int) substr((string) $subconOrder->order_no, -3);
    $invoiceDocNo = sprintf('%03d/GCI/N/%s/%s', $orderSeq ?: (int) $subconOrder->id, $sentDate->format('n'), $sentDate->format('Y'));
    $shipperName = 'PT.GEUM CHEON INDO';
    $shipperNpwp = '02.006.378.0.415.000';
    $shipperAddress = ['Jl. Raya Serang KM. 12 DS. Sukadamai', 'Tangerang'];
    $shipperTelp = '(021) 59400240, 59402454';
    $shipperFax = '59401224';
    $buyerName = strtoupper((string) ($subconOrder->vendor->vendor_name ?? '-'));
    $buyerNpwp = '02.286.818.6-451.000';
    $buyerAddress = preg_split('/\r\n|\r|\n/', trim((string) ($subconOrder->vendor->address ?? ''))) ?: [];
    $invoiceQty = (float) ($line['weight_kgm'] ?? 0);
    $unitPrice = (float) ($line['unit_price'] ?? 0);
    $amount = round($invoiceQty * $unitPrice, 0);
    $partName = (string) ($line['part_name'] ?? '-');
    $partNo = (string) ($line['part_no'] ?? '-');
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commercial Invoice - {{ $invoiceDocNo }}</title>
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
            height: 18mm;
            border: 1px solid #000;
            border-bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14mm;
            font-size: 27px;
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
            height: 172mm;
        }

        .left {
            width: 52%;
        }

        .mid-no {
            width: 4%;
            text-align: center;
            font-size: 14px;
        }

        .right {
            width: 44%;
        }

        .section-title {
            font-size: 16px;
            font-weight: 800;
        }

        .section-body {
            margin-left: 15px;
            margin-top: 9px;
            font-size: 14px;
            line-height: 1.45;
        }

        .shipper-row {
            height: 38mm;
        }

        .account-row {
            height: 43mm;
        }

        .notify-row {
            height: 18mm;
        }

        .port-row {
            height: 18mm;
        }

        .carrier-row {
            height: 18mm;
        }

        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 14px;
        }

        .right-title {
            font-size: 14px;
        }

        .item-head th {
            height: 16mm;
            font-size: 14px;
            font-weight: 400;
            text-align: center;
            vertical-align: middle;
        }

        .items {
            height: 97mm;
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
            height: 10mm;
            vertical-align: middle;
            font-size: 13px;
        }

        .double-line td {
            height: 12mm;
            border-top: 3px double #000;
            border-bottom: 3px double #000;
        }

        .signature {
            height: 44mm;
        }

        .signature td {
            border-top: 0;
        }

        .signed-by {
            position: absolute;
            right: 0;
            bottom: 6mm;
            width: 96mm;
        }

        .signature-box {
            margin-left: 28mm;
            margin-right: 0;
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            height: 28mm;
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
        <button onclick="window.print()">Print Commercial Invoice</button>
    </div>

    <div class="page">
        <div class="title">
            <span>COMMERCIAL</span>
            <span>INVOICE</span>
        </div>

        <table class="top-table">
            <tr class="shipper-row">
                <td class="left">
                    <div class="section-title">1. Shipper</div>
                    <div class="section-body">
                        <div>{{ $shipperName }}</div>
                        <div>NPWP : &nbsp; {{ $shipperNpwp }}</div>
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
                    <div class="right-title">No. &amp; Date of Invoice</div>
                    <div class="invoice-info">
                        <span>{{ $invoiceDocNo }}</span>
                        <span>{{ $sentDate->format('d M Y') }}</span>
                    </div>
                </td>
            </tr>
            <tr class="account-row">
                <td>
                    <div class="section-title">2. &nbsp;For Account &amp; Risk of Measure</div>
                    <div class="section-body">
                        <div>{{ $buyerName }}</div>
                        <div>NPWP : {{ $buyerNpwp }}</div>
                        @forelse ($buyerAddress as $addressLine)
                            <div>{{ $addressLine }}</div>
                        @empty
                            <div>-</div>
                        @endforelse
                    </div>
                </td>
                <td class="mid-no">9.</td>
                <td>
                    <div class="right-title">No. &amp; Date of L/C</div>
                </td>
            </tr>
            <tr class="notify-row">
                <td>
                    <div class="right-title">3. Notify Party</div>
                    <div class="section-body" style="margin-top:8px;">Same As Above</div>
                </td>
                <td class="mid-no">10.</td>
                <td>
                    <div class="right-title">L/C Issuing Bank</div>
                </td>
            </tr>
            <tr class="port-row">
                <td style="padding:0;">
                    <table>
                        <tr>
                            <td style="width:39%; height:18mm;">
                                <div class="right-title">4. Port of Loading</div>
                                <div class="section-body" style="margin-top:8px;">**********</div>
                            </td>
                            <td style="height:18mm;">
                                <div class="right-title">5. Final Destination</div>
                                <div class="section-body" style="margin-top:8px;">{{ $buyerName }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="mid-no" rowspan="2">11.</td>
                <td rowspan="2">
                    <div class="right-title" style="margin-top:22mm;">Remarks :</div>
                </td>
            </tr>
            <tr class="carrier-row">
                <td style="padding:0;">
                    <table>
                        <tr>
                            <td style="width:39%; height:18mm;">
                                <div class="right-title">6. Carrier</div>
                            </td>
                            <td style="height:18mm;">
                                <div class="right-title">7. Sailing On Or About</div>
                                <div class="section-body" style="margin-top:8px;">{{ $sentDate->format('d M Y') }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table>
            <tr class="item-head">
                <th style="width:20%;">12 &nbsp; Marks and No of Pkgs</th>
                <th style="width:33%;">13 Description of Goods</th>
                <th style="width:16%;">14 &nbsp; Quantity /Unit</th>
                <th style="width:14%;">15. &nbsp; Unit Price</th>
                <th style="width:17%;">16. &nbsp; Amount</th>
            </tr>
            <tr class="items">
                <td>1&nbsp;&nbsp;{{ $partName }}</td>
                <td class="text-right">{{ $partNo }}</td>
                <td class="text-right">{{ number_format($invoiceQty, 2, ',', '.') }} &nbsp; Kg</td>
                <td class="text-right">{{ number_format($unitPrice, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="total">
                <td class="text-center">Total</td>
                <td></td>
                <td class="text-right">{{ number_format($invoiceQty, 2, ',', '.') }} &nbsp; Kg</td>
                <td></td>
                <td class="text-right">{{ number_format($amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="double-line">
                <td colspan="5"></td>
            </tr>
            <tr class="signature">
                <td colspan="2"></td>
                <td colspan="3">
                    <div style="margin-top:36mm;">17 Signed By</div>
                </td>
            </tr>
        </table>

        <div class="signed-by">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div style="font-weight:800;">HWANG MINHA</div>
            </div>
        </div>
    </div>
</body>

</html>
