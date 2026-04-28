<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = \App\Models\ProductionOrder::where('production_order_number', 'WO-260428-S1-0001')->first();
if (!$order) {
    echo "Order not found\n";
    exit;
}
$bom = \App\Models\Bom::activeVersion($order->gci_part_id, $order->plan_date);
$bom->loadMissing('items.machine', 'items.wipPart', 'items.componentPart', 'part');

foreach ($bom->items->sortBy('line_no') as $item) {
    echo 'Line: ' . $item->line_no . ' | Process: ' . $item->process_name . ' | Machine: ' . ($item->machine?->id ?? 'null') . "\n";
}
