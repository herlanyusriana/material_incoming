<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Make part_id nullable in location_inventory_adjustments.
 *
 * FG picking and other GCI-only operations use gci_part_id as the primary
 * reference and do not always have a vendor part_id. The original column was
 * NOT NULL with a FK to parts, but since gci_part_id is now the primary
 * reference and part_id is optional, it should be nullable.
 */
return new class extends Migration {
    public function up(): void
    {
        // Drop FK if exists, then make nullable
        try {
            DB::statement('ALTER TABLE location_inventory_adjustments DROP FOREIGN KEY location_inventory_adjustments_part_id_foreign');
        } catch (\Throwable $e) {
            // FK may already be dropped
        }

        DB::statement('ALTER TABLE location_inventory_adjustments MODIFY part_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::table('location_inventory_adjustments')
            ->whereNull('part_id')
            ->update(['part_id' => 0]);

        DB::statement('ALTER TABLE location_inventory_adjustments MODIFY part_id BIGINT UNSIGNED NOT NULL');
    }
};
