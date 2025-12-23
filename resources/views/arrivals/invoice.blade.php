<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Commercial Invoice</title>
    <style>
        @page {
            size: A4;
            margin: 12mm 12mm 12mm 12mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.35;
            padding: 10px 14px;
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
            padding: 4px 6px;
            vertical-align: top;
            font-size: 10px;
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
            font-size: 9px;
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
            font-size: 9px;
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
            font-size: 10px;
        }
        
        .items-table:not(.packing-items-table) th {
            background: none;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
            border-bottom: 1px solid #000;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        
        .container-box {
            padding: 6px 0;
            margin-top: 8px;
            font-size: 10px;
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
    @endphp
@php
    $totalBundles = $arrival->items->sum(fn($i) => (float)($i->qty_bundle ?? 0));
    $totalQtyGoods = $arrival->items->sum(fn($i) => (float)($i->qty_goods ?? 0));
    $totalNett = $arrival->items->sum(fn($i) => (float)($i->weight_nett ?? 0));
    $totalGross = $arrival->items->sum(fn($i) => (float)($i->weight_gross ?? 0));
    $totalAmount = $arrival->items->sum(fn($i) => (float)($i->total_price ?? 0));
    $hsCodes = $arrival->items->pluck('part.hs_code')->filter()->unique()->values();
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
            &nbsp;
        </td>
    </tr>
    
	    {{-- Row 3: Notify Party + LC Issuing Bank --}}
	    <tr>
	        <td class="col-left">
	            <span class="section-label">3.NOTIFY PARTY</span><br><br>
	            @if($arrival->trucking)
	                @php
	                    $notifyTel = trim((string) ($arrival->trucking->phone ?? ''));
	                    $notifyFax = trim((string) ($arrival->trucking->fax ?? ''));
	                @endphp
	                <span class="company-name">{{ strtoupper($arrival->trucking->company_name) }}</span><br>
	                {{ strtoupper($arrival->trucking->address) }}<br><br>
	                TEL: {{ $notifyTel !== '' ? $notifyTel : '-' }}
	                @if($notifyFax !== '')
	                    &nbsp; FAX: {{ $notifyFax }}
	                @endif
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
            {{ $arrival->vendor->bank_account ?? '' }}
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
<table class="items-table">
    <thead>
        <tr>
            <th style="width:120px; text-align:left; padding-left:6px;">12.MARKS & NO. OF<br>PKGS</th>
            <th style="width:160px; text-align:left; padding-left:10px;">13.DESCRIPTION<br>OF GOODS</th>
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
	            <td colspan="4" style="text-align:right">
	                <strong>BILL OF LADING : {{ strtoupper($arrival->bill_of_lading ?? 'HASLS21251102449') }}</strong>
	                <br>
	                <strong>PRICE TERM : {{ strtoupper($arrival->price_term ?? '-') }}</strong>
	            </td>
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
                    $pricePerWeight = $item->qty_goods > 0 && $item->weight_nett > 0
                        ? number_format($item->price / ($item->weight_nett / $item->qty_goods), 3)
                        : '0.000';
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
                                <td style="border:none; padding:0 12px 0 0; text-align:center; width:50%; white-space:nowrap;">USD {{ number_format($item->price, 3) }} /{{ $goodsUnitLabel }}</td>
                                <td style="border:none; padding:0 0 0 12px; text-align:center; width:50%; white-space:nowrap;">USD {{ $pricePerWeight }} /{{ $unitWeightLabel }}</td>
                            @else
                                <td style="border:none; padding:0; text-align:center; width:100%; white-space:nowrap;">USD {{ number_format($item->price, 3) }} /{{ $goodsUnitLabel }}</td>
                            @endif
                        </tr>
                    </table>
                </td>
                <td class="text-right">USD {{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        @endforeach
        
        {{-- Total row --}}
        <tr style="border-top:2px solid #000;">
            <td class="text-bold">TOTAL :</td>
            <td>&nbsp;</td>
            <td class="text-center text-bold">
                @if(in_array($goodsUnitDisplay, ['KGM', 'KG'], true))
                    {{ number_format($totalNett, 0) }} {{ $weightUnitDisplay }}
                @else
                    <table style="width:100%; border:none; margin:0; padding:0; font-weight:bold;">
                        <tr>
                            <td style="border:none; padding:0 12px 0 0; text-align:center; width:50%; white-space:nowrap;">
                                {{ number_format($totalQtyGoods, 0) }} {{ $goodsUnitDisplay }}
                            </td>
                            <td style="border:none; padding:0 0 0 12px; text-align:center; width:50%; white-space:nowrap;">
                                {{ number_format($totalNett, 0) }} {{ $weightUnitDisplay }}
                            </td>
                        </tr>
                    </table>
                @endif
            </td>
            <td>&nbsp;</td>
            <td class="text-right text-bold">USD {{ number_format($arrival->items->sum('total_price'), 2) }}</td>
        </tr>
    </tbody>
</table>

		{{-- Container + Seal Section --}}
		@php
		    $containerDetails = collect();
		    if ($arrival->containers?->count()) {
		        $containerDetails = $arrival->containers
		            ->map(function ($c) {
		                $containerNo = strtoupper(trim((string) ($c->container_no ?? '')));
		                $sealCode = strtoupper(trim((string) ($c->seal_code ?? '')));
		                return [
		                    'container_no' => $containerNo,
		                    'seal_code' => $sealCode !== '' ? $sealCode : null,
		                ];
		            })
		            ->filter(fn ($row) => $row['container_no'] !== '')
		            ->values();
		    }

		    $containerPattern = '/^[A-Z]{4}\\d{7}$/';
		    $tokens = collect(preg_split('/[\\s,;]+/', (string) ($arrival->container_numbers ?? '')) ?: [])
		        ->map(fn ($t) => strtoupper(trim((string) $t)))
		        ->filter()
		        ->values();

		    if ($tokens->count()) {
		        $defaultSeal = strtoupper(trim((string) ($arrival->seal_code ?? '')));
		        $rows = [];
		        $i = 0;
		        while ($i < $tokens->count()) {
		            $current = (string) $tokens[$i];
		            if (!preg_match($containerPattern, $current)) {
		                $i++;
		                continue;
		            }

		            $next = $tokens->get($i + 1);
		            $nextStr = $next !== null ? (string) $next : '';
		            $nextIsContainer = $nextStr !== '' && preg_match($containerPattern, $nextStr);

		            if ($nextIsContainer) {
		                $rows[] = ['container_no' => $current, 'seal_code' => null];
		                $i += 1;
		                continue;
		            }

		            $seal = $nextStr !== '' ? $nextStr : ($defaultSeal !== '' ? $defaultSeal : null);
		            $rows[] = ['container_no' => $current, 'seal_code' => $seal];
		            $i += ($nextStr !== '') ? 2 : 1;
		        }

		        $legacyDetails = collect($rows)->filter(fn ($row) => $row['container_no'] !== '');

		        $containerDetails = $containerDetails
		            ->merge($legacyDetails)
		            ->unique('container_no')
		            ->values();
		    }
		@endphp
		<div class="container-box">
		    <strong>CONTAINERS &amp; SEAL :</strong><br>
		    @if($containerDetails->count())
		        @foreach($containerDetails as $container)
		            {{ $loop->iteration }}. {{ $container['container_no'] }}
		            @if(!empty($container['seal_code']))
		                / {{ $container['seal_code'] }}
		            @endif
		            <br>
		        @endforeach
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
                    <div class="original-box">ORIGINAL</div>
                    <div class="sign-space">
                        @if($arrival->vendor->signature_path)
                            <img src="{{ public_path('storage/' . $arrival->vendor->signature_path) }}" style="max-height:45px;">
                        @endif
                    </div>
                    <div style="border-top:1px solid #000; padding-top:5px; margin-top:10px;">
                        <span class="section-label">SIGNED BY</span><br>
                        <strong>{{ strtoupper($arrival->vendor->contact_person ?? 'GENERAL DIRECTOR') }}</strong>
                    </div>
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
            {{ $arrival->vendor->bank_account ?? '' }}
        </td>
    </tr>
    
	    {{-- Row 3: Notify Party --}}
	    <tr>
	        <td class="col-left">
	            <span class="section-label">3.NOTIFY PARTY</span><br><br>
	            @if($arrival->trucking)
	                @php
	                    $notifyTel = trim((string) ($arrival->trucking->phone ?? ''));
	                    $notifyFax = trim((string) ($arrival->trucking->fax ?? ''));
	                @endphp
	                <span class="company-name">{{ strtoupper($arrival->trucking->company_name) }}</span><br>
	                {{ strtoupper($arrival->trucking->address) }}<br><br>
	                TEL: {{ $notifyTel !== '' ? $notifyTel : '-' }}
	                @if($notifyFax !== '')
	                    &nbsp; FAX: {{ $notifyFax }}
	                @endif
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
			                <td class="text-center packing-bundle" style="white-space:nowrap;">
			                    {{ number_format($item->qty_goods, 0) }} {{ strtoupper($item->unit_goods ?? 'PCS') }}
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
                    <div class="original-box">ORIGINAL</div>
                    <div class="sign-space">
                        @if($arrival->vendor->signature_path)
                            <img src="{{ public_path('storage/' . $arrival->vendor->signature_path) }}" style="max-height:45px;">
                        @endif
                    </div>
                    <div style="border-top:1px solid #000; padding-top:5px; margin-top:10px;">
                        <span class="section-label">SIGNED BY</span><br>
                        <strong>{{ strtoupper($arrival->vendor->contact_person ?? 'GENERAL DIRECTOR') }}</strong>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
