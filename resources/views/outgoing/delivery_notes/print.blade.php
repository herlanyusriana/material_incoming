<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - {{ $delivery_note->dn_no }}</title>
    <style>
        @page {
            margin: 0;
            size: auto;
            /* auto is the initial value */
        }

        body {
            /* font-family: 'Courier New', Courier, monospace; */
            /* Dot matrix style */
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            color: #000;
            /* Black for data */
            margin: 0;
            padding: 0;
            background: transparent;
        }

        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: #f1f5f9;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        @media print {
            .no-print {
                display: none;
            }
        }

        /* Container to match the paper size. Adjust width/height as needed */
        .page-container {
            position: relative;
            width: 210mm;
            /* A4 Width/Letter Width approx */
            height: 148mm;
            /* Half A4 Height approx */
            /* border: 1px solid red; */
            /* Debug border */
            overflow: hidden;
            margin: 0 auto;
        }

        .data-absolute {
            position: absolute;
            white-space: nowrap;
            /* background: rgba(0, 255, 0, 0.1); */
            /* Debug background */
        }

        /* Coordinates (Estimates in mm) */

        /* NO. (Top Right) - e.g. 0771 */
        .pos-dn-no-top {
            top: 18mm;
            left: 170mm;
            font-weight: bold;
            font-size: 16px;
        }

        /* Date (Top Right) - e.g. 13 Feb 2026 */
        .pos-date {
            top: 25mm;
            left: 170mm;
        }

        /* Customer Name (Kepada Yth) */
        .pos-customer-name {
            top: 42mm;
            left: 140mm;
            width: 60mm;
            font-weight: bold;
        }

        /* Customer Address */
        .pos-customer-addr {
            top: 48mm;
            left: 140mm;
            width: 60mm;
            font-size: 12px;
            line-height: 1.2;
            white-space: normal;
            /* Allow wrapping */
        }

        /* DN No / Ref (Left side) */
        /* "NO. : .... / SJ GCI / .... " */
        .pos-dn-ref-left {
            top: 48mm;
            left: 45mm;
        }

        /* PO Ref (Blue text in image) */
        .pos-po-ref {
            top: 55mm;
            left: 160mm;
            color: blue;
            /* Match image style? Or keep black */
            font-weight: bold;
        }

        /* ITEMS TABLE */
        /* The table grid starts around 70mm from top */
        .items-container {
            position: absolute;
            top: 75mm;
            /* Moved up from 82mm */
            /* Adjust based on header height */
            left: 10mm;
            width: 190mm;
        }

        .item-row {
            display: flex;
            height: 8mm;
            /* Row height fitting the pre-printed lines */
            align-items: center;
            /* border-bottom: 1px solid blue; */
            /* Debug alignment */
        }

        .col-no {
            width: 10mm;
            text-align: center;
        }

        .col-qty {
            width: 30mm;
            text-align: center;
            font-weight: bold;
            color: #0066cc;
        }

        /* Blueish qty */
        .col-name {
            width: 70mm;
            padding-left: 5mm;
        }

        .col-size {
            width: 40mm;
            padding-left: 5mm;
        }

        /* Part No */
        .col-remarks {
            width: 40mm;
            padding-left: 5mm;
        }

        /* Remarks/Footer specific data? */
    </style>
</head>

<body>

    <div class="no-print">
        <button onclick="window.print()" style="cursor: pointer; padding: 5px 10px; font-weight: bold;">PRINT
            DATA</button>
        <div style="font-size: 10px; margin-top: 5px; color: #666;">
            * This prints ONLY data.<br>
            * Ensure page size is correct.<br>
            * Adjust printer margins to 0.
        </div>
    </div>

    @php
        // Prepare PO refs for display
        $poRefs = $delivery_note->items->map(fn($i) => $i->outgoing_po_item_id ? ($i->outgoingPoItem?->outgoingPo?->po_no) : ($i->customerPo?->po_no))->filter()->unique()->implode(', ');
    @endphp

    <div class="page-container">
        <!-- Top Right No -->
        <div class="data-absolute pos-dn-no-top">{{ substr($delivery_note->dn_no, -4) }}</div> {{-- Taking last 4 digits
        mainly? Or full? --}}

        <!-- Date -->
        <div class="data-absolute pos-date">{{ $delivery_note->delivery_date->format('d M Y') }}</div>

        <!-- Customer -->
        <div class="data-absolute pos-customer-name">{{ $delivery_note->customer?->name }}</div>
        <div class="data-absolute pos-customer-addr">{{ Str::limit($delivery_note->customer?->address, 100) }}</div>

        <!-- Left Ref (DN No Full) -->
        <div class="data-absolute pos-dn-ref-left">{{ $delivery_note->dn_no }}</div>

        <!-- PO Ref (Right side) -->
        <div class="data-absolute pos-po-ref">{{ $poRefs ?? '' }}</div>

        <!-- Items -->
        <div class="items-container">
            @foreach($delivery_note->items as $index => $item)
                <div class="item-row">
                    <!-- <div class="col-no">{{ $index + 1 }}.</div> -->
                    <div class="col-no"></div>
                    <div class="col-qty">{{ number_format($item->qty) }} {{ $item->part?->uom ?? 'Pcs' }}</div>
                    <div class="col-name">{{ $item->part?->part_name }}</div>
                    <div class="col-size">{{ $item->part?->part_no }}</div> <!-- "Ukuran" mapped to Part No -->
                    <div class="col-remarks">{{ $item->remarks }}</div>
                </div>
            @endforeach
        </div>

    </div>

</body>

</html>