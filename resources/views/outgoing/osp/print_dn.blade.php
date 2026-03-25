@php
    $docTitle = 'OSP Delivery Note';
    $docShort = 'DN';
    $docNo = $deliveryNoteNo;
    $showPricing = false;
@endphp

@include('outgoing.osp.print_document')
