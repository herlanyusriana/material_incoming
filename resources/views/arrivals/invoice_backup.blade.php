<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commercial Invoice - {{ $arrival->invoice_no }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 8pt;
            line-height: 1.2;
            color: #000;
            padding: 10px;
        }
        
        .title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 2px solid #000;
            border-top: 2px solid #000;
            padding: 5px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .header-table {
            margin-bottom: 5px;
        }
        
        .header-table td {
            padding: 2px 4px;
            vertical-align: top;
            font-size: 7pt;
            border: 1px solid #000;
        }
        
        .header-table .label {
            font-weight: bold;
            width: 80px;
            background-color: #f0f0f0;
        }
        
        .items-table {
            border: 2px solid #000;
            margin-top: 5px;
        }
        
        .items-table th {
            border: 1px solid #000;
            padding: 5px 3px;
            font-size: 7pt;
            text-align: center;
            font-weight: bold;
            background-color: #f0f0f0;
        }
        
        .items-table td {
            border: 1px solid #000;
            padding: 4px 3px;
            font-size: 7pt;
            vertical-align: top;
        }
        
        .items-table td.center {
            text-align: center;
        }
        
        .items-table td.right {
            text-align: right;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        
        .container-section {
            margin-top: 10px;
            font-size: 7pt;
            font-weight: bold;
        }
        
        .signature-section {
            margin-top: 40px;
            text-align: right;
        }
        
        .original-stamp {
            border: 3px solid red;
            color: red;
            padding: 10px 30px;
            font-size: 18pt;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .signature-box {
            display: inline-block;
            text-align: center;
            margin-right: 50px;
        }
        
        .stamp-img {
            width: 120px;
            height: 80px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="title">COMMERCIAL INVOICE</div>
    
    <table class="header-table">
        <tr>
            <td class="label">1.SHIPPER</td>
            <td colspan="3" rowspan="3" style="width: 50%;">
                <strong>{{ strtoupper($arrival->vendor->vendor_name) }}</strong><br>
                {{ strtoupper($arrival->vendor->address) }}<br>
                @if($arrival->vendor->phone)TEL: {{ $arrival->vendor->phone }} @endif
                @if($arrival->vendor->phone && $arrival->vendor->email) &nbsp; @endif
                @if($arrival->vendor->email)FAX:{{ $arrival->vendor->phone ? str_replace('-5940-0240', '-5940-1224', $arrival->vendor->phone) : '' }}@endif<br>
                @if($arrival->vendor->email)EMAIL: {{ strtoupper($arrival->vendor->email) }}@endif
            </td>
            <td class="label" style="width: 100px;">INVOICE NO. & DATE</td>
            <td style="width: 120px;">
                <strong>{{ $arrival->invoice_no }}</strong><br>
                {{ $arrival->invoice_date ? $arrival->invoice_date->format('d-M-Y') : '' }}
            </td>
        </tr>
        <tr>
            <td class="label">2.CONSIGNEE</td>
            <td class="label">3.REMITTANCE</td>
            <td rowspan="2" style="width: 120px;">
                {{ $arrival->vendor->bank_account ?? '' }}<br>
                <strong>DHL'C ISSUING BANK</strong>
            </td>
        </tr>
        <tr>
            <td class="label">3.NOTIFY PARTY</td>
        </tr>
        <tr>
            <td colspan="4" style="height: 70px;">
                <strong>PT.GEUM CHEON INDO</strong><br>
                JL. RAYA SERANG KM.12 DESA SUKADAMAI RT. 01/RW. 05 CIKUPA -<br>
                TANGERANG 15710, INDONESIA<br>
                TEL:+62-21-5940-0240 &nbsp; FAX:+62-21-5940-1224<br>
                CNIES TAX ID: 02.000.006.3-014.000
            </td>
            <td class="label">1.REMARK</td>
            <td rowspan="2" style="vertical-align: top;">
                {{ $arrival->notes ?? '' }}
            </td>
        </tr>
        <tr>
            <td colspan="4" style="height: 60px;">
                @if($arrival->trucking)
                <strong>{{ strtoupper($arrival->trucking->company_name) }}</strong><br>
                {{ $arrival->trucking->address }}<br>
                @if($arrival->trucking->phone)Tel: {{ $arrival->trucking->phone }}@endif
                @endif
            </td>
            <td class="label">HR CODE</td>
        </tr>
        <tr>
            <td colspan="6" style="padding: 0;">
                <table style="width: 100%; border: none;">
                    <tr>
                        <td class="label" style="width: 80px; border-right: 1px solid #000; border-bottom: none;">4.PORT OF LOADING</td>
                        <td colspan="2" style="border-right: 1px solid #000; border-bottom: none;"><strong>{{ strtoupper($arrival->port_of_loading ?? '') }}</strong></td>
                        <td class="label" style="width: 100px; border-right: 1px solid #000; border-bottom: none;">5.FINAL DESTINATION</td>
                        <td colspan="2" style="border-bottom: none;"><strong>JAKARTA, INDONESIA</strong></td>
                    </tr>
                    <tr>
                        <td class="label" style="border-right: 1px solid #000; border-top: 1px solid #000;">6.VESSEL</td>
                        <td style="border-right: 1px solid #000; border-top: 1px solid #000;" colspan="2">
                            <strong>{{ strtoupper($arrival->vessel ?? '') }}</strong>
                        </td>
                        <td class="label" style="border-right: 1px solid #000; border-top: 1px solid #000;">7.SAILING ON OR ABOUT</td>
                        <td style="border-top: 1px solid #000;" colspan="2">
                            <strong>{{ $arrival->ETD ? $arrival->ETD->format('d-M-Y') : '' }}</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 30px;">NO</th>
                <th style="width: 70px;">HS CODE</th>
                <th style="width: 70px;">HR CODE</th>
                <th>DESCRIPTION OF GOODS</th>
                <th style="width: 90px;">SIZE<br>(T*W*L)</th>
                <th style="width: 45px;">PALLETS</th>
                <th style="width: 65px;">QUANTITY</th>
                <th style="width: 40px;">UNIT</th>
                <th style="width: 75px;">UNIT PRICE<br>({{ $arrival->currency }})</th>
                <th style="width: 90px;">AMOUNT<br>({{ $arrival->currency }})</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalAmount = 0;
                $totalQtyGoods = 0;
                $totalBundles = 0;
            @endphp
            @foreach($arrival->items as $index => $item)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td class="center" style="font-size: 9pt;">{{ $item->part->hs_code ?? '' }}</td>
                <td class="center" style="font-size: 9pt;">{{ $item->part->hs_code ?? '' }}</td>
                <td style="padding-left: 5px;">{{ strtoupper($item->part->part_name_vendor) }}</td>
                <td class="center" style="font-size: 9pt;">{{ $item->size ?? '' }}</td>
                <td class="center">{{ number_format($item->qty_bundle, 0) }}</td>
                <td class="right">{{ number_format($item->qty_goods, 0) }}</td>
                <td class="center">{{ strtoupper($item->unit_bundle ?? 'PCS') }}</td>
                <td class="right">{{ number_format($item->price, 2) }}</td>
                <td class="right">{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @php
                $totalAmount += $item->total_price;
                $totalQtyGoods += $item->qty_goods;
                $totalBundles += $item->qty_bundle;
            @endphp
            @endforeach
            
            <!-- Totals -->
            <tr class="total-row">
                <td colspan="5" class="center"><strong>TOTAL:</strong></td>
                <td class="center"><strong>{{ number_format($totalBundles, 0) }}</strong></td>
                <td class="right"><strong>{{ number_format($totalQtyGoods, 0) }}</strong></td>
                <td class="center"></td>
                <td class="right"></td>
                <td class="right"><strong>{{ number_format($totalAmount, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>
    
    <div class="container-section">
        NO CONTAINER :<br>
        @if($arrival->container_numbers)
            @foreach(explode("\n", $arrival->container_numbers) as $container)
                @if(trim($container))
                    {{ $loop->iteration }}. {{ strtoupper(trim($container)) }}<br>
                @endif
            @endforeach
        @endif
    </div>
    
    <div style="text-align: right; margin-top: 30px;">
        <div class="original-stamp">ORIGINAL</div>
    </div>
    
    <div class="signature-section">
        <div class="signature-box">
            <div style="margin-bottom: 5px; font-size: 8pt;">SIGNED BY</div>
            @if($arrival->vendor->signature_path)
                <img src="{{ public_path('storage/' . $arrival->vendor->signature_path) }}" class="stamp-img" alt="Signature">
            @else
                <div style="width: 120px; height: 80px; border: 1px dashed #999; margin: 10px 0;"></div>
            @endif
            <div style="font-weight: bold; font-size: 8pt; margin-top: 5px;">
                {{ strtoupper($arrival->vendor->contact_person ?? 'GENERAL DIRECTOR') }}
            </div>
        </div>
    </div>
</body>
</html>    
    <table class="items-table">
        <thead>
            <tr>
                <th class="center" style="width: 40px;">12.MARKS & NO. OF</th>
                <th class="center" style="width: 140px;">13.DESCRIPTION</th>
                <th class="center" style="width: 70px;">14.QUANTITY</th>
                <th class="center" style="width: 80px;">15.UNIT PRICE</th>
                <th class="center" style="width: 80px;">16.AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="center">PKGS<br><br>NO: &nbsp; 31<br><br>MADE IN VIETNAM</td>
                <td>
                    <strong>GOODS</strong><br>
                    <strong>{{ $arrival->bill_of_lading ?? '-' }}</strong><br>
                    <strong>CIF JAKARTA</strong><br>
                    <br>
                    @php
                        $totalSheets = 0;
                        $totalKgs = 0;
                    @endphp
                    @foreach($arrival->items as $item)
                        <strong>{{ $item->part->part_name_vendor }}</strong><br>
                        @if($item->size)
                            {{ $item->size }}<br>
                        @endif
                        @php
                            $qty = $item->qty_goods;
                            $unit = $item->unit_weight ?? 'Sheet';
                            if (strtoupper($unit) === 'SHEET') {
                                $totalSheets += $qty;
                            } elseif (strtoupper($unit) === 'KGM' || strtoupper($unit) === 'KG') {
                                $totalKgs += $item->weight_nett;
                            }
                        @endphp
                    @endforeach
                </td>
                <td class="right">
                    <br><br><br><br>
                    @foreach($arrival->items as $item)
                        @php
                            $qty = $item->qty_goods;
                            $unit = $item->unit_weight ?? 'Sheet';
                        @endphp
                        {{ number_format($qty, 0) }} {{ ucfirst(strtolower($unit)) }}<br>
                        @if($item->size)
                            <br>
                        @endif
                        @if($item->weight_nett > 0)
                            {{ number_format($item->weight_nett, 3) }} KGM<br>
                        @endif
                    @endforeach
                </td>
                <td class="right">
                    <br><br><br><br>
                    @php $currencySymbol = $arrival->currency === 'USD' ? 'USD' : $arrival->currency; @endphp
                    @foreach($arrival->items as $item)
                        @php
                            $unit = $item->unit_weight ?? 'Sheet';
                            $pricePerUnit = $item->price;
                            $agmSuffix = ' /Agm';
                        @endphp
                        {{ $currencySymbol }} {{ number_format($pricePerUnit, 3) }} /{{ ucfirst(strtolower($unit)) }}<br>
                        @if($item->size)
                            <br>
                        @endif
                        @if($item->weight_nett > 0)
                            {{ $currencySymbol }} {{ number_format($pricePerUnit, 3) }}{{ $agmSuffix }}<br>
                        @endif
                    @endforeach
                </td>
                <td class="right">
                    <br><br><br><br>
                    @foreach($arrival->items as $item)
                        {{ $currencySymbol }} {{ number_format($item->total_price, 2) }}<br>
                        @if($item->size)
                            <br>
                        @endif
                        @if($item->weight_nett > 0)
                            {{ $currencySymbol }} {{ number_format($item->total_price, 2) }}<br>
                        @endif
                    @endforeach
                </td>
            </tr>
            <tr class="total-row">
                <td colspan="2" style="text-align: center;">
                    TOTAL :
                </td>
                <td class="right">
                    @if($totalSheets > 0)
                        {{ number_format($totalSheets, 0) }} Sheet<br>
                    @endif
                    @if($totalKgs > 0)
                        {{ number_format($totalKgs, 3) }} KGM
                    @endif
                </td>
                <td></td>
                <td class="right">{{ $currencySymbol }} {{ number_format($arrival->items->sum('total_price'), 2) }}</td>
            </tr>
        </tbody>
    </table>
    
    <div class="container-section">
        <strong>NO CONTAINER :</strong><br>
        @if($arrival->container_numbers)
            @foreach(explode("\n", $arrival->container_numbers) as $container)
                @if(trim($container))
                    {{ $loop->iteration }}. {{ trim($container) }}<br>
                @endif
            @endforeach
        @else
            -
        @endif
    </div>
    
    <div style="text-align: right; margin-top: 50px;">
        <div class="original-stamp">ORIGINAL</div>
    </div>
    
    <div class="signature-section">
        <div class="signature-box">
            <div style="margin-bottom: 10px;">SIGNED BY</div>
            @if($arrival->vendor->signature_path)
                <div class="stamp-area">
                    <img src="{{ public_path('storage/' . $arrival->vendor->signature_path) }}" alt="Signature">
                </div>
            @else
                <div class="stamp-area" style="border: 1px dashed #ccc;"></div>
            @endif
            <div class="signatory-name">{{ $arrival->vendor->contact_person ?? 'General Director' }}</div>
        </div>
    </div>
    
</body>
</html>
                    Jakarta, Indonesia 12345<br>
                    Phone: +62 21 1234 5678 | Email: info@geumcheonindo.com
                </div>
            </div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <div class="invoice-number">{{ $arrival->arrival_no }}</div>
            </div>
            <div class="clear"></div>
        </div>
    </div>
    
    <div class="info-section">
        <div class="info-box">
            <h3>Vendor Information</h3>
            <div class="info-row">
                <span class="info-label">Vendor Name:</span>
                <span class="info-value">{{ $arrival->vendor->vendor_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Contact Person:</span>
                <span class="info-value">{{ $arrival->vendor->contact_person ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value">{{ $arrival->vendor->phone ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ $arrival->vendor->email ?? '-' }}</span>
            </div>
        </div>
        
        <div class="info-box">
            <h3>Departure Details</h3>
            <div class="info-row">
                <span class="info-label">Invoice No:</span>
                <span class="info-value">{{ $arrival->invoice_no }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Invoice Date:</span>
                <span class="info-value">{{ $arrival->invoice_date ? $arrival->invoice_date->format('d M Y') : '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">ETD:</span>
                <span class="info-value">{{ $arrival->ETD ? $arrival->ETD->format('d M Y') : '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Vessel:</span>
                <span class="info-value">{{ $arrival->vessel ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">B/L Number:</span>
                <span class="info-value">{{ $arrival->bill_of_lading ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Port of Loading:</span>
                <span class="info-value">{{ $arrival->port_of_loading ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Currency:</span>
                <span class="info-value">{{ $arrival->currency }}</span>
            </div>
        </div>
        <div class="clear"></div>
    </div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 15%;">Part Number</th>
                <th style="width: 30%;">Description</th>
                <th style="width: 12%;">Size</th>
                <th class="text-center" style="width: 10%;">Qty Bundle</th>
                <th class="text-center" style="width: 10%;">Qty Goods</th>
                <th class="text-right" style="width: 18%;">Unit Price ({{ $arrival->currency }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($arrival->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <div class="part-number">{{ $item->part->part_no }}</div>
                </td>
                <td>
                    <div>{{ $item->part->part_name_vendor }}</div>
                    @if($item->part->part_name_gci && $item->part->part_name_gci !== $item->part->part_name_vendor)
                        <div class="part-name">{{ $item->part->part_name_gci }}</div>
                    @endif
                </td>
                <td class="text-center">{{ $item->size ?? '-' }}</td>
                <td class="text-center">{{ number_format($item->qty_bundle) }}</td>
                <td class="text-center">{{ number_format($item->qty_goods) }}</td>
                <td class="text-right">{{ number_format($item->unit_price ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="summary-section">
        <div class="summary-row">
            <span class="summary-label">Total Items:</span>
            <span class="summary-value">{{ $arrival->items->count() }} items</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Total Quantity:</span>
            <span class="summary-value">{{ number_format($arrival->items->sum('qty_goods')) }} pcs</span>
        </div>
        <div class="summary-row total">
            <span class="summary-label">Total Amount ({{ $arrival->currency }}):</span>
            <span class="summary-value">{{ number_format($arrival->items->sum(function($item) { return $item->qty_goods * ($item->unit_price ?? 0); }), 2) }}</span>
        </div>
        <div class="clear"></div>
    </div>
    
    <div class="clear"></div>
    
    @if($arrival->notes)
    <div class="notes">
        <h4>Notes:</h4>
        <p>{{ $arrival->notes }}</p>
    </div>
    @endif
    
    <div class="footer">
        <p>This is a computer-generated invoice and does not require a signature.</p>
        <p>Printed on {{ now()->format('d M Y H:i') }}</p>
    </div>
</body>
</html>
