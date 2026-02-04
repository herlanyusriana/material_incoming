<?php
// Load Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

echo "Cleaning up Vendor Names...\n";

$vendors = Vendor::all();
$count = 0;
$duplicates = 0;

foreach ($vendors as $v) {
    $trimmed = trim($v->vendor_name);
    if ($v->vendor_name !== $trimmed) {
        $original = $v->vendor_name;
        // Check for duplicate conflict
        $exists = Vendor::where('vendor_name', $trimmed)
            ->where('id', '!=', $v->id)
            ->exists();

        if ($exists) {
            echo "CONFLICT: Vendor ID {$v->id} ({$original}) becomes '{$trimmed}' which already exists.\n";
            // Merge logic? Or just suffix?
            // User said "semua vendor kek nya ada akhiran spasi".
            // Maybe the "existing" one is also dirty?
            // Actually, if I iterate, I might hit the 'clean' one later or earlier.

            // If duplicate exists, it implies we have TWO records.
            // One might be used by Parts, other might be empty.
            // Or both used.
            // Merge is dangerous without user confirmation.
            // I'll skip and report.
            $duplicates++;
        } else {
            try {
                $v->vendor_name = $trimmed;
                $v->save();
                echo "Fixed: Vendor ID {$v->id} ('{$original}' -> '{$trimmed}')\n";
                $count++;
            } catch (\Throwable $e) {
                echo "Error updating Vendor ID {$v->id}: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "Done. Fixed $count vendors. Found $duplicates conflicts.\n";
