<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Vendor;

$vendors = Vendor::all(); // Excludes soft deleted
echo "Total Active Vendors: " . $vendors->count() . "\n";

foreach ($vendors as $v) {
    echo "[ID: {$v->id}] '{$v->vendor_name}'\n";
}
