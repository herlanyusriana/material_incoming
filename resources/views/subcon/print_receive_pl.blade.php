@php
    $subconUom = $subconOrderReceive->subconOrder->bomItem?->consumptionUom?->code
        ?? $subconOrderReceive->subconOrder->bomItem?->consumption_uom
        ?? $subconOrderReceive->subconOrder->rmPart?->uom
        ?? $subconOrderReceive->subconOrder->bomItem?->wipUom?->code
        ?? $subconOrderReceive->subconOrder->bomItem?->wip_uom
        ?? $subconOrderReceive->subconOrder->gciPart?->uom
        ?? 'PCS';

    $items = [
        [
            'no' => 1,
            'part_no' => $subconOrderReceive->subconOrder->gciPart->part_no ?? '-',
            'part_name' => $subconOrderReceive->subconOrder->gciPart->part_name ?? '-',
            'description' => __('subcon.prints.subcon_reference') . ': ' . $subconOrderReceive->subconOrder->order_no,
            'uom' => $subconUom,
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
            'description' => __('subcon.orders.show.reject_history'),
            'uom' => $subconUom,
            'qty' => $subconOrderReceive->qty_rejected,
            'unit_price' => 0,
            'amount' => 0,
        ];
    }

    $docTitle = __('subcon.prints.packing_list') . ' (Receive)';
    $docShort = 'PL';
    $docNo = 'RCV-' . str_pad($subconOrderReceive->id, 5, '0', STR_PAD_LEFT);
    $showPricing = false;
    $subconOrder = $subconOrderReceive->subconOrder;
    $lines = $items;
    $totalQty = $subconOrderReceive->qty_good + $subconOrderReceive->qty_rejected;
@endphp

@include('subcon.print_document')
