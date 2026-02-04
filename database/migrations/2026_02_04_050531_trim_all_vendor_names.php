<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $vendors = \App\Models\Vendor::all();
        foreach ($vendors as $vendor) {
            $cleanName = trim($vendor->vendor_name);
            if ($cleanName !== $vendor->vendor_name) {
                // Check for duplicate
                $exists = \App\Models\Vendor::where('vendor_name', $cleanName)
                    ->where('id', '!=', $vendor->id)
                    ->exists();

                if ($exists) {
                    // Conflict found. We cannot simply rename it.
                    // We append a suffix to identify it, but user must resolve manually.
                    // Or we skip.
                    // Let's force trim but append trimmed marker if duplicate
                    // Actually, if "Inno Rubber " (ID 2) and "Inno Rubber" (ID 1) exist.
                    // If we rename ID 2 to "Inno Rubber", it fails.
                    // We can rename it to "Inno Rubber (Duplicate)".
                    $vendor->vendor_name = $cleanName . ' (Duplicate ' . $vendor->id . ')';
                } else {
                    $vendor->vendor_name = $cleanName;
                }

                try {
                    $vendor->save();
                } catch (\Throwable $e) {
                    // Ignore error
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
