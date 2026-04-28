<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orders = \App\Models\ProductionOrder::orderBy('id', 'desc')->take(10)->get();
foreach ($orders as $o) {
    echo 'WO: ' . $o->production_order_number . ' | Shift: ' . $o->shift . ' | Machine: ' . $o->machine_id . "\n";
}
