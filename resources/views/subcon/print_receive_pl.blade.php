@php
    $items = [
        [
            'no' => 1,
            'part_no' => $subconOrderReceive->subconOrder->gciPart->part_no ?? '-',
            'part_name' => $subconOrderReceive->subconOrder->gciPart->part_name ?? '-',
            'description' => 'Received from Subcon. Order: ' . $subconOrderReceive->subconOrder->order_no,
            'uom' => $subconOrderReceive->subconOrder->gciPart->uom->uom_code ?? 'PCS',
            'qty' => $subconOrderReceive->qty_good,
            'unit_price' => 0,
            'amount' => 0,
        ]
    ];
    if ($subconOrderReceive->qty_rejected > 0) {
        $items[] = [
            'no' => 2,
            'part_no' => $subconOrderReceive->subconOrder->gciPart->part_no ?? '-',
            'part_name' => ($subconOrderReceive->subconOrder->gciPart->part_name ?? '-') . ' (REJECTED)',
            'description' => 'Rejected parts from Subcon.',
            'uom' => $subconOrderReceive->subconOrder->gciPart->uom->uom_code ?? 'PCS',
            'qty' => $subconOrderReceive->qty_rejected,
            'unit_price' => 0,
            'amount' => 0,
        ];
    }

    $docTitle = 'Packing List (Receive)';
    $docShort = 'PL';
    $docNo = 'RCV-' . str_pad($subconOrderReceive->id, 5, '0', STR_PAD_LEFT);
    $showPricing = false;
    $subconOrder = $subconOrderReceive->subconOrder;
    $lines = $items;
    $totalQty = $subconOrderReceive->qty_good + $subconOrderReceive->qty_rejected;
@endphp

@include('subcon.print_document')
