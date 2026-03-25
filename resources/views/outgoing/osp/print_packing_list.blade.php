@php
    $docTitle = 'OSP Packing List';
    $docShort = 'PL';
    $docNo = $packingListNo;
    $showPricing = false;
@endphp

@include('outgoing.osp.print_document')
