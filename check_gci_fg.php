<?php

use App\Models\GciPart;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();




$part = GciPart::where('part_no', 'like', '%4114EAQ1141L%')->first();
if ($part) {
    echo "Found part: " . json_encode($part->toArray(), JSON_PRETTY_PRINT);
} else {
    echo "Part 4114EAQ1141L not found in GciPart table.\n";
    // Check all FG again just in case
    echo "All FG Parts:\n";
    $all = GciPart::where('classification', 'FG')->get();
    echo json_encode($all->toArray(), JSON_PRETTY_PRINT);
}



