<?php

/**
 * Script untuk men-sync data Receives ke Location Inventory
 * 
 * Jalankan dengan: php database/sync_receives_to_location_inventory.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Receive;
use App\Models\LocationInventory;
use Illuminate\Support\Facades\DB;

echo "Starting sync of Receives to Location Inventory...\n";

DB::transaction(function () {
    // Get all receives with location_code yang sudah QC PASS
    $receives = Receive::with('arrivalItem')
        ->where('qc_status', 'pass')
        ->whereNotNull('location_code')
        ->where('location_code', '!=', '')
        ->get();

    echo "Found " . $receives->count() . " receives to process\n\n";

    $processed = 0;
    $errors = 0;

    foreach ($receives as $receive) {
        try {
            $partId = $receive->arrivalItem->part_id ?? null;
            $locationCode = strtoupper(trim($receive->location_code));
            $qty = (float) ($receive->qty ?? 0);

            if (!$partId || !$locationCode || $qty <= 0) {
                echo "[SKIP] Receive ID {$receive->id}: Missing part_id, location_code, or invalid qty\n";
                continue;
            }

            // Check apakah sudah ada di location_inventory
            $existing = LocationInventory::where('part_id', $partId)
                ->where('location_code', $locationCode)
                ->whereNull('batch_no')
                ->first();

            if ($existing) {
                // Sudah ada, skip (assume already synced)
                echo "[EXISTS] Receive ID {$receive->id}: Part #{$partId} @ {$locationCode} already in inventory\n";
            } else {
                // Buat entry baru
                LocationInventory::updateStock($partId, $locationCode, $qty);
                echo "[SYNCED] Receive ID {$receive->id}: Added {$qty} units of Part #{$partId} to {$locationCode}\n";
                $processed++;
            }
        } catch (\Exception $e) {
            echo "[ERROR] Receive ID {$receive->id}: {$e->getMessage()}\n";
            $errors++;
        }
    }

    echo "\n=== SUMMARY ===\n";
    echo "Total Receives: " . $receives->count() . "\n";
    echo "Synced: {$processed}\n";
    echo "Skipped/Exists: " . ($receives->count() - $processed - $errors) . "\n";
    echo "Errors: {$errors}\n";
});

echo "\nSync completed!\n";
