<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Commercial Invoice</title>
    <style>
        @page {
            size: A4;
            margin: 15mm 15mm 15mm 15mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            line-height: 1.4;
            padding: 15px 20px;
        }
        
        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
            text-decoration: none;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .main-table td {
            border: 1px solid #000;
            padding: 5px 7px;
            vertical-align: top;
            font-size: 9px;
        }
        
        .main-table .num {
            font-weight: bold;
            width: 12px;
        }
        
        .col-left {
            width: 55%;
        }
        
        .col-right {
            width: 45%;
        }
        
        .inner-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .inner-table td {
            border: none;
            padding: 2px 0;
            vertical-align: top;
        }
        
        .section-label {
            font-weight: bold;
            font-size: 8px;
        }
        
        .company-name {
            font-weight: bold;
        }
        
        .items-table {
            margin-top: 8px;
        }

        .packing-items-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .packing-items-table td,
        .packing-items-table th {
            padding: 3px 8px;
            font-size: 8.5px;
            vertical-align: middle;
        }

        .packing-items-table th:first-child,
        .packing-items-table td:first-child {
            padding-left: 0;
            text-align: left;
        }

        .packing-items-table th:nth-child(2) {
            padding-left: 0;
            text-align: left;
        }

        .packing-items-table td:nth-child(2) {
            padding-left: 0;
        }

        .packing-items-table td:first-child,
        .packing-items-table td:nth-child(2) {
            vertical-align: top;
        }

        .packing-items-table th {
            border-bottom: 1px solid #000;
        }

        .packing-bundle {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .packing-desc {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .packing-desc td {
            border: none;
            padding: 0;
            vertical-align: middle;
            line-height: 1.2;
            font-size: inherit;
        }

        .items-table:not(.packing-items-table) td,
        .items-table:not(.packing-items-table) th {
            border: none;
            padding: 5px 6px;
            vertical-align: top;
            font-size: 9px;
        }
        
        .items-table:not(.packing-items-table) th {
            background: none;
            font-weight: bold;
            text-align: center;
            font-size: 8px;
            border-bottom: none;
        }

        .invoice-items-box {
            border: 1px solid #000;
            margin-top: 8px;
            padding: 0 6px 6px 6px;
        }

        .invoice-items-box .items-table {
            margin-top: 0;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        
        .container-box {
            padding: 6px 0;
            margin-top: 8px;
            font-size: 9px;
        }
        
        .footer-section {
            margin-top: 15px;
            position: relative;
        }
        
        .signature-table {
            width: 100%;
        }
        
        .signature-table td {
            vertical-align: top;
            padding: 0;
        }
        
        .original-box {
            border: 2px solid #cc0000;
            color: #cc0000;
            padding: 4px 15px;
            font-weight: bold;
            font-size: 12px;
            display: inline-block;
        }
        
        .sign-box {
            border: 1px solid #000;
            width: 180px;
            padding: 8px;
            text-align: center;
            float: right;
        }
        
        .sign-space {
            height: 50px;
        }
        
        .sign-name {
            border-top: 1px solid #000;
            padding-top: 4px;
            font-weight: bold;
            font-size: 8px;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    @php
        $portOrigin = strtoupper(trim($arrival->port_of_loading ?? ''));
        $countryOrigin = strtoupper(trim($arrival->country ?? ''));
        $countryInPort = $countryOrigin && stripos($portOrigin, $countryOrigin) !== false;
        $portDisplay = $portOrigin ?: $countryOrigin;
        if (!$countryInPort && $countryOrigin) {
            $portDisplay = trim($portDisplay . ($portDisplay ? ', ' : '') . $countryOrigin);
        }
        $madeInText = $portOrigin ?: ($countryOrigin ?: 'SOUTH KOREA');

        $notesText = trim((string) ($arrival->notes ?? ''));
        $bankAccountText = trim((string) ($arrival->vendor->bank_account ?? ''));

        $originalAngles = [-5, -4, -3, -2, -1, 1, 2, 3, 4, 5];
        $originalRotationInvoice = $originalAngles[random_int(0, count($originalAngles) - 1)];
        $originalRotationPacking = $originalAngles[random_int(0, count($originalAngles) - 1)];

        if (!function_exists('format3_no_round')) {
            function format3_no_round($value): string
            {
                $raw = is_string($value) ? trim($value) : (is_numeric($value) ? (string) $value : '0');
                if ($raw === '' || $raw === null) {
                    $raw = '0';
                }

                $raw = str_replace(',', '', $raw);

                $negative = false;
                if (str_starts_with($raw, '-')) {
                    $negative = true;
                    $raw = substr($raw, 1);
                }

                if (stripos($raw, 'e') !== false) {
                    $raw = (string) sprintf('%.15F', (float) $raw);
                    $raw = rtrim(rtrim($raw, '0'), '.');
                }

                $parts = explode('.', $raw, 2);
                $int = preg_replace('/\\D+/', '', $parts[0] ?? '') ?: '0';
                $frac = preg_replace('/\\D+/', '', $parts[1] ?? '');
                $frac = substr(str_pad($frac, 3, '0'), 0, 3);

                $rev = strrev($int);
                $chunks = str_split($rev, 3);
                $intFormatted = strrev(implode(',', $chunks));

                return ($negative ? '-' : '') . $intFormatted . '.' . $frac;
            }
        }
    @endphp
@php
    $totalBundles = $arrival->items->sum(fn($i) => (float)($i->qty_bundle ?? 0));
    $totalQtyGoods = $arrival->items->sum(fn($i) => (float)($i->qty_goods ?? 0));
    $totalNett = $arrival->items->sum(fn($i) => (float)($i->weight_nett ?? 0));
    $totalGross = $arrival->items->sum(fn($i) => (float)($i->weight_gross ?? 0));
    $totalAmount = $arrival->items->sum(fn($i) => (float)($i->total_price ?? 0));
    $hsCodes = $arrival->items->pluck('part.hs_code')->filter()->unique()->values();
    $hsCodesDisplayRaw = trim((string) ($arrival->hs_codes ?? $arrival->hs_code ?? $hsCodes->implode("\n")));
    $hsCodesDisplay = $hsCodesDisplayRaw !== '' ? strtoupper($hsCodesDisplayRaw) : '';
    $hasBundleData = $arrival->items->contains(function ($item) {
        return ($item->qty_bundle ?? 0) > 0;
    });
    $marksNoEnd = (int) $arrival->items->sum(fn($i) => (int) ($i->qty_bundle ?? 0));
    if ($marksNoEnd <= 0) {
        $marksNoEnd = $arrival->items->count();
    }
    $bundleTotalDisplay = $hasBundleData
        ? $arrival->items->sum(fn($i) => (float)($i->qty_bundle ?? 0))
        : 0;
    $bundleUnitDisplay = optional($arrival->items->first(function ($item) {
        return ($item->qty_bundle ?? 0) > 0 && $item->unit_bundle;
    }))->unit_bundle ?? 'BUNDLE';
    $goodsUnitDisplay = strtoupper(optional($arrival->items->first(function ($item) {
        return !empty($item->unit_goods);
    }))->unit_goods ?? 'PCS');
    $weightUnitDisplay = strtoupper(optional($arrival->items->first())->unit_weight ?? 'KGM');

    $weightOnlyGoodsUnits = ['KGM', 'KG'];
    $qtyTotalsByUnit = $arrival->items
        ->groupBy(fn ($i) => strtoupper(trim((string) ($i->unit_goods ?? 'PCS'))))
        ->map(fn ($items) => (float) $items->sum(fn ($i) => (float) ($i->qty_goods ?? 0)))
        ->filter(fn ($v) => $v > 0);
    $qtyTotalsNonWeight = $qtyTotalsByUnit->reject(fn ($v, $unit) => in_array($unit, $weightOnlyGoodsUnits, true));
    $nettTotalsByUnit = $arrival->items
        ->groupBy(fn ($i) => strtoupper(trim((string) ($i->unit_weight ?? 'KGM'))))
        ->map(fn ($items) => (float) $items->sum(fn ($i) => (float) ($i->weight_nett ?? 0)))
        ->filter(fn ($v) => $v > 0);
@endphp

<div class="title">COMMERCIAL INVOICE</div>

<table class="main-table">
    {{-- Row 1: Shipper + Invoice No & Date --}}
    <tr>
        <td class="col-left">
            <span class="section-label">1.SHIPPER</span><br><br>
            <span class="company-name">{{ strtoupper($arrival->vendor->vendor_name) }}</span><br>
            {{ strtoupper($arrival->vendor->address) }}<br>
            TEL:{{ $arrival->vendor->phone ?? '' }}
        </td>
        <td class="col-right">
            <span class="section-label">8.INVOICE NO. & DATE</span><br><br>
            <table style="width:100%; border:none;">
                <tr>
                    <td style="border:none; padding:0; font-weight:bold; font-size:14px;">{{ $arrival->invoice_no }}</td>
                    <td style="border:none; padding:0; text-align:right;">{{ $arrival->invoice_date ? $arrival->invoice_date->format('d.M.Y') : '' }}</td>
                </tr>
            </table>
        </td>
    </tr>
    
    {{-- Row 2: Consignee + Remittance --}}
    <tr>
        <td class="col-left">
            <span class="section-label">2.CONSIGNEE</span><br><br>
            <span class="company-name">PT.GEUM CHEON INDO</span><br>
            JL. RAYA SERANG KM.12 DESA SUKADAMAI RT 01/RW 05 CIKUPA -<br>
            TANGERANG 15710, INDONESIA<br><br>
            TEL:62-21-5940-0240 &nbsp; FAX:62-21-5940-1224<br>
            CNEE'S TAX ID: 002.006.378.0.415.000<br>
            EMAIL: MENTOSNICE@GMAIL.COM
        </td>
        <td class="col-right">
            <span class="section-label">9.REMITTANCE</span><br><br>
            @if($notesText !== '')
                <div style="white-space: pre-line;">{{ $notesText }}</div>
            @else
                &nbsp;
            @endif
        </td>
    </tr>
    
    {{-- Row 3: Notify Party + LC Issuing Bank --}}
    <tr>
        <td class="col-left">
            <span class="section-label">3.NOTIFY PARTY</span><br><br>
            @if($arrival->trucking)
                <span class="company-name">{{ strtoupper($arrival->trucking->company_name) }}</span><br>
                {{ strtoupper($arrival->trucking->address) }}<br><br>
                Tel: ( {{ substr($arrival->trucking->phone ?? '', 0, 2) }} - {{ substr($arrival->trucking->phone ?? '', 2, 2) }} ) {{ substr($arrival->trucking->phone ?? '', 4) }}
                @if($arrival->trucking->fax) Fax: {{ $arrival->trucking->fax }}@endif
            @else
                -
            @endif
        </td>
        <td class="col-right">
            <span class="section-label">10.LC ISSUING BANK</span><br><br><br>
            &nbsp;
        </td>
    </tr>
    
    {{-- Row 4: Port of Loading + Final Destination + Remark --}}
    <tr>
        <td class="col-left">
            <table style="width:100%; border:none;">
                <tr>
                    <td style="border:none; padding:0; width:50%;">
                        <span class="section-label">4.PORT OF LOADING</span><br><br>
                        &nbsp;&nbsp;{{ $portDisplay }}
                    </td>
                    <td style="border:none; padding:0; width:50%;">
                        <span class="section-label">5.FINAL DESTINATION</span><br><br>
                        &nbsp;&nbsp;JAKARTA, INDONESIA
                    </td>
                </tr>
            </table>
        </td>
        <td class="col-right" rowspan="2">
            <span class="section-label">11.REMARK:</span><br><br>
            @if($bankAccountText !== '')
                <div style="white-space: pre-line;">{{ $bankAccountText }}</div>
            @else
                &nbsp;
            @endif
        </td>
    </tr>
    
    {{-- Row 5: Vessel + Sailing Date --}}
    <tr>
        <td class="col-left">
            <table style="width:100%; border:none;">
                <tr>
                    <td style="border:none; padding:0; width:50%;">
                        <span class="section-label">6.VESSEL</span><br><br>
                        <strong>&nbsp;&nbsp;{{ strtoupper($arrival->vessel ?? '') }}</strong>
                    </td>
                    <td style="border:none; padding:0; width:50%;">
                        <span class="section-label">7.SAILING ON OR ABOUT</span><br><br>
                        <strong>&nbsp;&nbsp;{{ $arrival->ETD ? $arrival->ETD->format('d-M-y') : '' }}</strong>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

</table>

{{-- Items Table (Commercial Invoice) --}}
<div class="invoice-items-box">
<table class="items-table">
    <thead>
        <tr>
            <th style="width:120px; text-align:left; padding-left:6px;">12.MARKS &amp; NO. OF PKGS</th>
            <th style="width:160px; text-align:left; padding-left:10px;">13.DESCRIPTION OF GOODS</th>
            <th style="width:90px; text-align:center;">14.QUANTITY</th>
            <th style="width:100px; text-align:center;">15.UNIT PRICE</th>
            <th style="width:90px; text-align:right; padding-right:6px;">16.AMOUNT</th>
        </tr>
    </thead>
    <tbody>
        {{-- Marks & Made In row --}}
	        <tr>
	            <td style="vertical-align:top;">
	                NO: 1-{{ $marksNoEnd }}<br>
	                
	            </td>
	            <td colspan="4">&nbsp;</td>
	        </tr>
        
        @php
            $groupedItems = $arrival->items->groupBy(function($i) {
                $group = $i->material_group ?? '';
                if (trim($group) !== '') {
                    return strtoupper(trim($group));
                }
                return strtoupper(trim($i->part->part_name_vendor ?? ''));
            });
        @endphp
        
        {{-- Grouped item rows by Part Name Vendor --}}
        @foreach($groupedItems as $groupKey => $items)
            {{-- Vendor header row --}}
            <tr>
                <td>&nbsp;</td>
                <td colspan="4" style="text-align:left; font-weight:bold; padding-left:10px;">
                    {{ $items->first()->material_group ?: ($items->first()->part->part_name_vendor ?? 'HOT DIP GALVANIZED STEEL SHEETS') }}
                </td>
            </tr>
            
            {{-- Item rows --}}
            @foreach($items as $item)
            <tr>
                <td>{{ strtoupper($item->part->part_name_gci ?? '') }}</td>
                <td style="padding-left:10px;">{{ $item->size ?? '' }}</td>
                @php
                    $goodsUnitLabel = strtoupper($item->unit_goods ?? 'PCS');
                    $unitWeightLabel = strtoupper($item->unit_weight ?? 'KGM');
                    $pricePerWeightRaw = $item->qty_goods > 0 && $item->weight_nett > 0
                        ? ((float) $item->price * (float) $item->qty_goods) / (float) $item->weight_nett
                        : 0;
                    $pricePerWeight = format3_no_round($pricePerWeightRaw);
                @endphp
                <td class="text-center">
                    @php
                        $showWeightOnly = in_array($goodsUnitLabel, ['KGM', 'KG'], true);
                        $hasWeight = (float) ($item->weight_nett ?? 0) > 0;
                    @endphp

	                    @if($showWeightOnly)
	                        {{ number_format($item->weight_nett, 0) }} {{ $unitWeightLabel }}
	                    @elseif($hasWeight)
                        <table style="width:100%; border:none; margin:0; padding:0;">
                            <tr>
                                <td style="border:none; padding:0 12px 0 0; text-align:center; width:50%; white-space:nowrap;">
                                    {{ number_format($item->qty_goods, 0) }} {{ $goodsUnitLabel }}
                                </td>
                                <td style="border:none; padding:0 0 0 12px; text-align:center; width:50%; white-space:nowrap;">
                                    {{ number_format($item->weight_nett, 0) }} {{ $unitWeightLabel }}
                                </td>
                            </tr>
                        </table>
                    @else
                        {{ number_format($item->qty_goods, 0) }} {{ $goodsUnitLabel }}
                    @endif
                </td>
                <td class="text-center">
                    <table style="width:100%; border:none; margin:0; padding:0;">
                        <tr>
                            @if(!in_array($goodsUnitLabel, ['KGM', 'KG'], true) && (float) ($item->weight_nett ?? 0) > 0)
                                <td style="border:none; padding:0 12px 0 0; text-align:center; width:50%; white-space:nowrap;">USD {{ format3_no_round($item->price) }} /{{ $goodsUnitLabel }}</td>
                                <td style="border:none; padding:0 0 0 12px; text-align:center; width:50%; white-space:nowrap;">USD {{ $pricePerWeight }} /{{ $unitWeightLabel }}</td>
                            @else
                                <td style="border:none; padding:0; text-align:center; width:100%; white-space:nowrap;">USD {{ format3_no_round($item->price) }} /{{ $goodsUnitLabel }}</td>
                            @endif
                        </tr>
                    </table>
                </td>
                <td class="text-right">USD {{ format3_no_round($item->total_price) }}</td>
            </tr>
            @endforeach

            @php
                $groupQtyTotalsByUnit = $items
                    ->groupBy(fn ($i) => strtoupper(trim((string) ($i->unit_goods ?? 'PCS'))))
                    ->map(fn ($rows) => (float) $rows->sum(fn ($i) => (float) ($i->qty_goods ?? 0)))
                    ->filter(fn ($v) => $v > 0);
                $groupQtyTotalsNonWeight = $groupQtyTotalsByUnit->reject(fn ($v, $unit) => in_array($unit, $weightOnlyGoodsUnits, true));
                $groupNettTotalsByUnit = $items
                    ->groupBy(fn ($i) => strtoupper(trim((string) ($i->unit_weight ?? 'KGM'))))
                    ->map(fn ($rows) => (float) $rows->sum(fn ($i) => (float) ($i->weight_nett ?? 0)))
                    ->filter(fn ($v) => $v > 0);
                $groupAmount = (float) $items->sum(fn ($i) => (float) ($i->total_price ?? 0));
            @endphp
            <tr style="border-top:1px solid #000;">
                <td class="text-bold">SUBTOTAL :</td>
                <td>&nbsp;</td>
                <td class="text-center text-bold">
                    @if($groupQtyTotalsNonWeight->count() === 0)
                        @php
                            $unit = (string) ($groupNettTotalsByUnit->keys()->first() ?? $weightUnitDisplay);
                            $value = (float) ($groupNettTotalsByUnit->first() ?? 0);
                        @endphp
                        {{ number_format($value, 0) }} {{ $unit }}
                    @elseif($groupQtyTotalsNonWeight->count() === 1 && $groupNettTotalsByUnit->count() === 1)
                        @php
                            $qtyUnit = (string) $groupQtyTotalsNonWeight->keys()->first();
                            $qtyValue = (float) $groupQtyTotalsNonWeight->first();
                            $nettUnit = (string) $groupNettTotalsByUnit->keys()->first();
                            $nettValue = (float) $groupNettTotalsByUnit->first();
                        @endphp
                        <table style="width:100%; border:none; margin:0; padding:0; font-weight:bold;">
                            <tr>
                                <td style="border:none; padding:0 12px 0 0; text-align:center; width:50%; white-space:nowrap;">
                                    {{ number_format($qtyValue, 0) }} {{ $qtyUnit }}
                                </td>
                                <td style="border:none; padding:0 0 0 12px; text-align:center; width:50%; white-space:nowrap;">
                                    {{ number_format($nettValue, 0) }} {{ $nettUnit }}
                                </td>
                            </tr>
                        </table>
                    @else
                        <table style="width:100%; border:none; margin:0; padding:0; font-weight:bold;">
                            @foreach($groupQtyTotalsNonWeight as $unit => $value)
                                <tr>
                                    <td style="border:none; padding:0; text-align:center; white-space:nowrap;">
                                        {{ number_format($value, 0) }} {{ $unit }}
                                    </td>
                                </tr>
                            @endforeach
                            @foreach($groupNettTotalsByUnit as $unit => $value)
                                <tr>
                                    <td style="border:none; padding:0; text-align:center; white-space:nowrap;">
                                        {{ number_format($value, 0) }} {{ $unit }}
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                </td>
                <td>&nbsp;</td>
                <td class="text-right text-bold">USD {{ format3_no_round($groupAmount) }}</td>
            </tr>
        @endforeach
        
        {{-- Total row --}}
        <tr style="border-top:2px solid #000;">
            <td class="text-bold">TOTAL :</td>
            <td>&nbsp;</td>
            <td class="text-center text-bold">
                @if($qtyTotalsNonWeight->count() === 0)
                    @php
                        $unit = (string) ($nettTotalsByUnit->keys()->first() ?? $weightUnitDisplay);
                        $value = (float) ($nettTotalsByUnit->first() ?? $totalNett);
                    @endphp
                    {{ number_format($value, 0) }} {{ $unit }}
                @elseif($qtyTotalsNonWeight->count() === 1 && $nettTotalsByUnit->count() === 1)
                    @php
                        $qtyUnit = (string) $qtyTotalsNonWeight->keys()->first();
                        $qtyValue = (float) $qtyTotalsNonWeight->first();
                        $nettUnit = (string) $nettTotalsByUnit->keys()->first();
                        $nettValue = (float) $nettTotalsByUnit->first();
                    @endphp
                    <table style="width:100%; border:none; margin:0; padding:0; font-weight:bold;">
                        <tr>
                            <td style="border:none; padding:0 12px 0 0; text-align:center; width:50%; white-space:nowrap;">
                                {{ number_format($qtyValue, 0) }} {{ $qtyUnit }}
                            </td>
                            <td style="border:none; padding:0 0 0 12px; text-align:center; width:50%; white-space:nowrap;">
                                {{ number_format($nettValue, 0) }} {{ $nettUnit }}
                            </td>
                        </tr>
                    </table>
                @else
                    <table style="width:100%; border:none; margin:0; padding:0; font-weight:bold;">
                        @foreach($qtyTotalsNonWeight as $unit => $value)
                            <tr>
                                <td style="border:none; padding:0; text-align:center; white-space:nowrap;">
                                    {{ number_format($value, 0) }} {{ $unit }}
                                </td>
                            </tr>
                        @endforeach
                        @foreach($nettTotalsByUnit as $unit => $value)
                            <tr>
                                <td style="border:none; padding:0; text-align:center; white-space:nowrap;">
                                    {{ number_format($value, 0) }} {{ $unit }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </td>
            <td>&nbsp;</td>
            <td class="text-right text-bold">USD {{ format3_no_round($arrival->items->sum('total_price')) }}</td>
        </tr>
    </tbody>
</table>
</div>

	{{-- Container + Seal Section --}}
	<div class="container-box">
        <strong>HS CODE :</strong>
        @if($hsCodesDisplay !== '')
            <div style="white-space: pre-line;">{{ $hsCodesDisplay }}</div>
        @else
            -
        @endif
        <br>
        <strong>BILL OF LADING :</strong> {{ strtoupper($arrival->bill_of_lading ?? 'HASLS21251102449') }}<br>
        <strong>PRICE TERM :</strong> {{ strtoupper($arrival->price_term ?? '-') }}<br><br>
	    <strong>CONTAINERS &amp; SEAL :</strong><br>
	    @if($arrival->containers->count())
        @foreach($arrival->containers as $container)
            {{ $loop->iteration }}. {{ strtoupper(trim($container->container_no)) }}
            @if($container->seal_code)
                / {{ strtoupper(trim($container->seal_code)) }}
            @endif
            <br>
        @endforeach
    @elseif($arrival->container_numbers)
        @foreach(explode("\n", $arrival->container_numbers) as $container)
            @if(trim($container))
                {{ $loop->iteration }}. {{ strtoupper(trim($container)) }}<br>
            @endif
        @endforeach
        @if($arrival->seal_code)
            <strong>SEAL CODE :</strong> {{ strtoupper(trim($arrival->seal_code)) }}<br>
        @endif
    @else
        -
    @endif
</div>

{{-- Signature Section --}}
<div class="footer-section">
    <table class="signature-table">
        <tr>
            <td style="width:100%; text-align:right;">
                <div style="display:inline-block; text-align:center; padding:10px 30px;">
                    <div class="original-box" style="transform: rotate({{ $originalRotationInvoice }}deg);">ORIGINAL</div>
                    <table style="width:100%; border:none; margin-top:8px;">
                        <tr>
                            <td style="border:none; padding:0; width:45%; vertical-align:bottom; text-align:left;">
                                <span class="section-label">SIGNED BY</span><br>
                                @php $signedBy = strtoupper(trim((string) ($arrival->vendor->contact_person ?? ''))); @endphp
                                <strong>{{ $signedBy !== '' ? $signedBy : ' ' }}</strong>
                            </td>
                            <td style="border:none; padding:0; width:55%; vertical-align:bottom; text-align:right;">
                                <div class="sign-space" style="height:auto;">
                                    @if($arrival->vendor->signature_path)
                                        <img src="{{ public_path('storage/' . $arrival->vendor->signature_path) }}" style="max-height:45px;">
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </table>
                    <div style="border-top:1px solid #000; margin-top:10px;"></div>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="page-break"></div>

<div class="title">PACKING LIST</div>

<table class="main-table">
    {{-- Row 1: Shipper + Invoice No & Date --}}
    <tr>
        <td class="col-left">
            <span class="section-label">1.SHIPPER</span><br><br>
            <span class="company-name">{{ strtoupper($arrival->vendor->vendor_name) }}</span><br>
            {{ strtoupper($arrival->vendor->address) }}<br>
            TEL:{{ $arrival->vendor->phone ?? '' }}
        </td>
        <td class="col-right">
            <span class="section-label">8.INVOICE NO. & DATE</span><br><br>
            <table style="width:100%; border:none;">
                <tr>
                    <td style="border:none; padding:0; font-weight:bold; font-size:14px;">{{ $arrival->invoice_no }}</td>
                    <td style="border:none; padding:0; text-align:right;">{{ $arrival->invoice_date ? $arrival->invoice_date->format('d.M.Y') : '' }}</td>
                </tr>
            </table>
        </td>
    </tr>
    
    {{-- Row 2: Consignee + Remark --}}
    <tr>
        <td class="col-left">
            <span class="section-label">2.CONSIGNEE</span><br><br>
            <span class="company-name">PT.GEUM CHEON INDO</span><br>
            JL. RAYA SERANG KM.12 DESA SUKADAMAI RT 01/RW 05 CIKUPA -<br>
            TANGERANG 15710, INDONESIA<br><br>
            TEL:62-21-5940-0240 &nbsp; FAX:62-21-5940-1224<br>
            CNEE'S TAX ID: 002.006.378.0.415.000<br>
            EMAIL: MENTOSNICE@GMAIL.COM
        </td>
        <td class="col-right" rowspan="4" style="vertical-align: top;">
            <span class="section-label">9.REMARK:</span><br><br>
            @if($notesText !== '')
                <div style="white-space: pre-line;">{{ $notesText }}</div>
            @else
                &nbsp;
            @endif
        </td>
    </tr>
    
    {{-- Row 3: Notify Party --}}
    <tr>
        <td class="col-left">
            <span class="section-label">3.NOTIFY PARTY</span><br><br>
            @if($arrival->trucking)
                <span class="company-name">{{ strtoupper($arrival->trucking->company_name) }}</span><br>
                {{ strtoupper($arrival->trucking->address) }}<br><br>
                Tel: ( {{ substr($arrival->trucking->phone ?? '', 0, 2) }} - {{ substr($arrival->trucking->phone ?? '', 2, 2) }} ) {{ substr($arrival->trucking->phone ?? '', 4) }}
                @if($arrival->trucking->fax) Fax: {{ $arrival->trucking->fax }}@endif
            @else
                -
            @endif
        </td>
    </tr>
    
    {{-- Row 4: Port of Loading + Final Destination --}}
    <tr>
        <td class="col-left">
            <table style="width:100%; border:none;">
                <tr>
                    <td style="border:none; padding:0; width:50%;">
                        <span class="section-label">4.PORT OF LOADING</span><br><br>
                        &nbsp;&nbsp;{{ $portDisplay }}
                    </td>
                    <td style="border:none; padding:0; width:50%;">
                        <span class="section-label">5.FINAL DESTINATION</span><br><br>
                        &nbsp;&nbsp;JAKARTA, INDONESIA
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    {{-- Row 5: Vessel + Sailing Date --}}
    <tr>
        <td class="col-left">
            <table style="width:100%; border:none;">
                <tr>
                    <td style="border:none; padding:0; width:50%;">
                        <span class="section-label">6.VESSEL</span><br><br>
                        <strong>&nbsp;&nbsp;{{ strtoupper($arrival->vessel ?? '') }}</strong>
                    </td>
                    <td style="border:none; padding:0; width:50%;">
                        <span class="section-label">7.SAILING ON OR ABOUT</span><br><br>
                        <strong>&nbsp;&nbsp;{{ $arrival->ETD ? $arrival->ETD->format('d-M-y') : '' }}</strong>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

		{{-- Packing List Items Table --}}
		<table class="items-table packing-items-table">
		    <colgroup>
		        <col style="width:22%;">
		        <col style="width:40%;">
		        <col style="width:18%;">
		        <col style="width:10%;">
		        <col style="width:10%;">
		    </colgroup>
		    <thead>
		        <tr>
		            <th>10.MARKS & NO. OF<br>PKGS</th>
		            <th>11.DESCRIPTION<br>OF GOODS</th>
		            <th>12.QUANTITY</th>
		            <th>13.NET WEIGHT</th>
		            <th>14.GROSS WEIGHT</th>
		        </tr>
		    </thead>
		    <tbody>
		        {{-- Marks & Made In row --}}
		        <tr>
		            <td style="vertical-align:top;">
		                NO: 1-{{ $marksNoEnd }}<br>
		                
		            </td>
		            <td colspan="4">&nbsp;</td>
		        </tr>
	        
			        @foreach($groupedItems as $vendorName => $items)
			            {{-- Vendor header row --}}
			            <tr>
			                <td>&nbsp;</td>
			                <td style="text-align:left; font-weight:bold;">
			                    {{ $vendorName ?: 'HOT DIP GALVANIZED STEEL SHEETS' }}
			                </td>
			                <td>&nbsp;</td>
			                <td>&nbsp;</td>
			                <td>&nbsp;</td>
			            </tr>
		            
		            {{-- Item rows --}}
			            @foreach($items as $item)
				            <tr>
				                <td>{{ strtoupper($item->part->part_name_gci ?? '') }}</td>
				                <td>
				                    <table class="packing-desc">
				                        <colgroup>
				                            <col style="width:64%;">
				                            <col style="width:36%;">
				                        </colgroup>
				                        <tr>
				                            <td style="padding-right:8px; white-space:nowrap;">{{ $item->size ?? '' }}</td>
				                            <td class="packing-bundle text-center" style="white-space:nowrap;">
				                                @if(($item->qty_bundle ?? 0) > 0)
				                                    {{ number_format($item->qty_bundle, 0) }} {{ strtoupper($item->unit_bundle ?? 'PALLET') }}
				                                @else
			                                    -
			                                @endif
			                            </td>
			                        </tr>
			                    </table>
			                </td>
                            @php
                                $goodsUnitLabel = strtoupper($item->unit_goods ?? 'PCS');
                                $unitWeightLabel = strtoupper($item->unit_weight ?? 'KGM');
                                $showWeightOnly = in_array($goodsUnitLabel, ['KGM', 'KG'], true);
                                $hasWeight = (float) ($item->weight_nett ?? 0) > 0;
                            @endphp
			                <td class="text-center packing-bundle" style="white-space:nowrap;">
                                @if($showWeightOnly)
                                    {{ number_format($item->weight_nett, 0) }} {{ $unitWeightLabel }}
                                @elseif($hasWeight)
                                    <table style="width:100%; border:none; margin:0; padding:0;">
                                        <tr>
                                            <td style="border:none; padding:0 10px 0 0; text-align:center; width:50%; white-space:nowrap;">
                                                {{ number_format($item->qty_goods, 0) }} {{ $goodsUnitLabel }}
                                            </td>
                                            <td style="border:none; padding:0 0 0 10px; text-align:center; width:50%; white-space:nowrap;">
                                                {{ number_format($item->weight_nett, 0) }} {{ $unitWeightLabel }}
                                            </td>
                                        </tr>
                                    </table>
                                @else
			                        {{ number_format($item->qty_goods, 0) }} {{ $goodsUnitLabel }}
                                @endif
			                </td>
			                <td class="text-center" style="white-space:nowrap;">{{ number_format($item->weight_nett, 0) }} {{ strtoupper($item->unit_weight ?? 'KGM') }}</td>
			                <td class="text-center" style="white-space:nowrap;">{{ number_format($item->weight_gross, 0) }} {{ strtoupper($item->unit_weight ?? 'KGM') }}</td>
			            </tr>
		            @endforeach
		        @endforeach
	        
			        {{-- Total row --}}
				        <tr style="border-top:2px solid #000;">
				            <td class="text-bold">TOTAL :</td>
				            <td>
				                <table class="packing-desc">
				                    <colgroup>
				                        <col style="width:64%;">
				                        <col style="width:36%;">
			                    </colgroup>
			                    <tr>
			                        <td>&nbsp;</td>
			                        <td class="packing-bundle text-center text-bold" style="white-space:nowrap;">
			                            @if($hasBundleData && $bundleTotalDisplay > 0)
			                                {{ number_format($bundleTotalDisplay, 0) }} {{ strtoupper($bundleUnitDisplay) }}
			                            @else
			                                -
			                            @endif
			                        </td>
			                    </tr>
			                </table>
			            </td>
			            <td class="text-center text-bold packing-bundle" style="white-space:nowrap;">{{ number_format($totalQtyGoods, 0) }} {{ $goodsUnitDisplay }}</td>
			            <td class="text-center text-bold" style="white-space:nowrap;">{{ number_format($totalNett, 0) }} {{ $weightUnitDisplay }}</td>
			            <td class="text-center text-bold" style="white-space:nowrap;">{{ number_format($totalGross, 0) }} {{ $weightUnitDisplay }}</td>
			        </tr>
		    </tbody>
		</table>

	{{-- Signature Section --}}
	<div class="footer-section">
	    <table class="signature-table">
	        <tr>
            <td style="width:100%; text-align:right;">
                <div style="display:inline-block; text-align:center; padding:10px 30px;">
                    <div class="original-box" style="transform: rotate({{ $originalRotationPacking }}deg);">ORIGINAL</div>
                    <table style="width:100%; border:none; margin-top:8px;">
                        <tr>
                            <td style="border:none; padding:0; width:45%; vertical-align:bottom; text-align:left;">
                                <span class="section-label">SIGNED BY</span><br>
                                @php $signedBy = strtoupper(trim((string) ($arrival->vendor->contact_person ?? ''))); @endphp
                                <strong>{{ $signedBy !== '' ? $signedBy : ' ' }}</strong>
                            </td>
                            <td style="border:none; padding:0; width:55%; vertical-align:bottom; text-align:right;">
                                <div class="sign-space" style="height:auto;">
                                    @if($arrival->vendor->signature_path)
                                        <img src="{{ public_path('storage/' . $arrival->vendor->signature_path) }}" style="max-height:45px;">
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </table>
                    <div style="border-top:1px solid #000; margin-top:10px;"></div>
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
