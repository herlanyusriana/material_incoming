@php
    $docTitle = 'OSP Invoice';
    $docShort = 'Invoice';
    $docNo = $invoiceNo;
    $showPricing = true;
@endphp

@include('outgoing.osp.print_document')
