<?php

use App\Models\GciPart;
use App\Models\CustomerPart;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check for overlaps
$gciFgs = GciPart::where('classification', 'FG')->pluck('part_no')->toArray();
$custParts = CustomerPart::pluck('customer_part_no')->toArray();

$overlaps = array_intersect($gciFgs, $custParts);

echo "Overlaps found (GCI FG vs Customer Parts): " . count($overlaps) . "\n";
print_r(array_slice($overlaps, 0, 20));

// Also check lengths
echo "\nGCI FG Part Lengths:\n";
foreach ($gciFgs as $p) {
    if (strlen($p) > 12) {
        echo "$p (Len: " . strlen($p) . ")\n";
    }
}
