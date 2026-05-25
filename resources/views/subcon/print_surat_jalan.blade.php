@php
    $sentDate = $subconOrder->sent_date ?? now();
    $orderSeq = (int) substr((string) $subconOrder->order_no, -3);
    $preprintSjNo = sprintf('%03d/PM/SJ GCI/%d/%s', $orderSeq ?: (int) $subconOrder->id, (int) $sentDate->format('n'), $sentDate->format('Y'));
    $vendorName = strtoupper((string) ($subconOrder->vendor->vendor_name ?? '-'));
    $vendorAddress = preg_split('/\r\n|\r|\n/', trim((string) ($subconOrder->vendor->address ?? ''))) ?: [];
    $contractNo = (string) ($subconOrder->contract_no ?? '-');
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - {{ $preprintSjNo }}</title>
    <style>
        @page {
            size: 9.5in 5.5in;
            margin: 0;
        }

        :root {
            --offset-x: 0mm;
            --offset-y: 0mm;
        }

        body {
            margin: 0;
            background: #e5e7eb;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12pt;
        }

        .toolbar {
            width: 9.5in;
            margin: 12px auto;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
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

        .sheet {
            position: relative;
            width: 9.5in;
            height: 5.5in;
            margin: 0 auto 18px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.16);
        }

        .field {
            position: absolute;
            transform: translate(var(--offset-x), var(--offset-y));
            white-space: nowrap;
            line-height: 1.25;
        }

        .date {
            top: 11mm;
            left: 170mm;
            width: 38mm;
            text-align: left;
        }

        .sj-no {
            top: 31mm;
            left: 16mm;
            letter-spacing: 0.02em;
        }

        .vendor {
            top: 22mm;
            left: 159mm;
            width: 58mm;
            text-align: center;
            white-space: normal;
            font-weight: 700;
        }

        .address {
            top: 31mm;
            left: 129mm;
            width: 88mm;
            text-align: right;
            white-space: normal;
            line-height: 1.35;
        }

        .line-field {
            font-size: 10.5pt;
        }

        .qty {
            left: 19mm;
            width: 20mm;
            text-align: right;
        }

        .uom {
            left: 43mm;
        }

        .part-name {
            left: 55mm;
            width: 80mm;
            overflow: hidden;
            text-overflow: clip;
        }

        .part-no {
            left: 124mm;
            width: 38mm;
            text-align: left;
        }

        .weight {
            left: 163mm;
            width: 25mm;
            text-align: right;
        }

        .net-weight {
            left: 194mm;
            width: 34mm;
            text-align: left;
        }

        .contract {
            top: 116mm;
            left: 170mm;
            width: 58mm;
            text-align: center;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <button onclick="window.print()">Print SJ LX-300</button>
    </div>

    <div class="sheet">
        <div class="field date">{{ $sentDate->format('d M Y') }}</div>
        <div class="field sj-no">{{ $preprintSjNo }}</div>

        <div class="field vendor">{{ $vendorName }}</div>
        <div class="field address">
            @forelse ($vendorAddress as $addressLine)
                <div>{{ $addressLine }}</div>
            @empty
                <div>-</div>
            @endforelse
        </div>

        @foreach ($lines->take(6) as $idx => $line)
            @php
                $top = 68 + ($idx * 8);
                $qty = (float) ($line['qty'] ?? 0);
                $weight = (float) ($line['weight_kgm'] ?? 0);
                $netWeight = (float) ($line['net_weight'] ?? 0);
                $boxQty = $line['box_qty'] ?? null;
                $uom = $line['uom'] ?? 'Pcs';
                $partText = trim((string) ($line['part_name'] ?? '-'));
                $partNo = (string) ($line['part_no'] ?? '-');
            @endphp
            <div class="field line-field qty" style="top: {{ $top }}mm">{{ number_format($qty, 0) }}</div>
            <div class="field line-field uom" style="top: {{ $top }}mm">{{ ucfirst(strtolower($uom)) }}</div>
            <div class="field line-field part-name" style="top: {{ $top }}mm">{{ $partText }}</div>
            <div class="field line-field part-no" style="top: {{ $top }}mm">{{ $partNo }}</div>
            <div class="field line-field weight" style="top: {{ $top }}mm">{{ number_format($weight, 3) }} Kg</div>
            <div class="field line-field net-weight" style="top: {{ $top }}mm">
                {{ number_format($netWeight, 3) }} kg/pcs
                @if ($boxQty)
                    ({{ number_format($boxQty, 0) }} Box)
                @endif
            </div>
        @endforeach
        <div class="field contract">{{ $contractNo }}</div>
    </div>
</body>

</html>
