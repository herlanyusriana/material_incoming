<?php
// Load Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Vendor;

echo "Cleaning up Vendor Names (Enhanced)...\n";

$vendors = Vendor::all();
$count = 0;
$duplicates = 0;

foreach ($vendors as $v) {
    // Convert NBSP (0xA0) and other whitespace to normal space, then trim
    // unicode flag 'u' is important
    $clean = preg_replace('/[\p{Z}\s]+/u', ' ', $v->vendor_name);
    $clean = trim($clean);

    if ($v->vendor_name !== $clean) {
        $original = $v->vendor_name;
        // Hex dump to see what's hidden
        $hex = bin2hex($original);

        $exists = Vendor::where('vendor_name', $clean)
            ->where('id', '!=', $v->id)
            ->exists();

        if ($exists) {
            echo "CONFLICT: Vendor ID {$v->id} ('{$original}') -> '{$clean}'. skipped.\n";
            $duplicates++;
        } else {
            try {
                $v->vendor_name = $clean;
                $v->save();
                echo "Fixed: ID {$v->id} cleaned.\n";
                $count++;
            } catch (\Throwable $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "Done. Fixed $count vendors. Found $duplicates conflicts.\n";
