<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Vendor;
use App\Models\Part;

echo "Diagnosing Vendors...\n";

// Check duplicates by name (case insensitive)
// Use simple logic to avoid SQL dialect issues
$vendors = Vendor::all();
$names = [];
foreach ($vendors as $v) {
    $name = strtolower(trim($v->vendor_name));
    if (!isset($names[$name])) {
        $names[$name] = [];
    }
    $names[$name][] = $v;
}

$foundDupes = false;
foreach ($names as $name => $list) {
    if (count($list) > 1) {
        $foundDupes = true;
        echo "Duplicate Name: '$name'\n";
        foreach ($list as $v) {
            echo "  - ID {$v->id}: '{$v->vendor_name}'\n";
        }
    }
}
if (!$foundDupes) {
    echo "No duplicate vendor names found.\n";
}

// Check Inno Rubber specifically
$inno = Vendor::where('vendor_name', 'like', '%Inno%')->get();
echo "\nInno Rubber matches:\n";
foreach ($inno as $v) {
    echo "ID: {$v->id}, Name: '{$v->vendor_name}' (Len: " . strlen($v->vendor_name) . ")\n";
    $partsCount = Part::where('vendor_id', $v->id)->count();
    echo "  -> Has $partsCount active/inactive Parts.\n";
}
