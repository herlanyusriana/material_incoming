<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills missing data in vendor_parts from legacy gci_part_vendor
 * and gci_parts tables. This fixes:
 *
 * 1. register_no that is NULL but exists in gci_part_vendor or gci_parts.size
 * 2. vendor_part_name that is empty but exists in gci_part_vendor or gci_parts.part_name
 * 3. vendor_part_no that is empty but exists in gci_part_vendor or gci_parts.part_no
 * 4. uom that is NULL but exists in gci_part_vendor
 * 5. hs_code that is NULL but exists in gci_part_vendor
 *
 * This is idempotent — safe to run multiple times.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only run if legacy table exists
        if (!DB::getSchemaBuilder()->hasTable('gci_part_vendor')) {
            return;
        }

        // 1. Backfill register_no from gci_part_vendor (match by gci_part_id + vendor_id)
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.register_no = gpv.register_no
            WHERE (vp.register_no IS NULL OR TRIM(vp.register_no) = '')
                AND gpv.register_no IS NOT NULL
                AND TRIM(gpv.register_no) <> ''
        ");

        // 2. Backfill register_no from gci_parts.size (fallback)
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_parts g ON vp.gci_part_id = g.id
            SET vp.register_no = g.size
            WHERE (vp.register_no IS NULL OR TRIM(vp.register_no) = '')
                AND g.size IS NOT NULL
                AND TRIM(g.size) <> ''
        ");

        // 3. Backfill vendor_part_name from gci_part_vendor
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.vendor_part_name = gpv.vendor_part_name
            WHERE (vp.vendor_part_name IS NULL OR TRIM(vp.vendor_part_name) = '')
                AND gpv.vendor_part_name IS NOT NULL
                AND TRIM(gpv.vendor_part_name) <> ''
        ");

        // 4. Backfill vendor_part_name from gci_parts.part_name (fallback)
        //    Only if vendor_part_name is still empty — use gci_parts.part_name
        //    prefixed with material group context (just use part_name directly)
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_parts g ON vp.gci_part_id = g.id
            SET vp.vendor_part_name = g.part_name
            WHERE (vp.vendor_part_name IS NULL OR TRIM(vp.vendor_part_name) = '')
                AND g.part_name IS NOT NULL
                AND TRIM(g.part_name) <> ''
        ");

        // 5. Backfill vendor_part_no from gci_part_vendor
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.vendor_part_no = gpv.vendor_part_no
            WHERE (vp.vendor_part_no IS NULL OR TRIM(vp.vendor_part_no) = '')
                AND gpv.vendor_part_no IS NOT NULL
                AND TRIM(gpv.vendor_part_no) <> ''
        ");

        // 6. Backfill vendor_part_no from gci_parts.part_no (fallback)
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_parts g ON vp.gci_part_id = g.id
            SET vp.vendor_part_no = g.part_no
            WHERE (vp.vendor_part_no IS NULL OR TRIM(vp.vendor_part_no) = '')
                AND g.part_no IS NOT NULL
                AND TRIM(g.part_no) <> ''
        ");

        // 7. Backfill uom from gci_part_vendor
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.uom = gpv.uom
            WHERE (vp.uom IS NULL OR TRIM(vp.uom) = '')
                AND gpv.uom IS NOT NULL
                AND TRIM(gpv.uom) <> ''
        ");

        // 8. Backfill hs_code from gci_part_vendor
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.hs_code = gpv.hs_code
            WHERE (vp.hs_code IS NULL OR TRIM(vp.hs_code) = '')
                AND gpv.hs_code IS NOT NULL
                AND TRIM(gpv.hs_code) <> ''
        ");

        // 9. Backfill price from gci_part_vendor
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.price = gpv.price
            WHERE vp.price IS NULL
                AND gpv.price IS NOT NULL
        ");

        // 10. Backfill status from gci_part_vendor
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.status = gpv.status
            WHERE (vp.status IS NULL OR TRIM(vp.status) = '')
                AND gpv.status IS NOT NULL
                AND TRIM(gpv.status) <> ''
        ");

        // 11. Backfill quality_inspection from gci_part_vendor
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.quality_inspection = gpv.quality_inspection
            WHERE vp.quality_inspection = 0
                AND gpv.quality_inspection = 1
        ");
    }

    public function down(): void
    {
        // Cannot undo backfills — data is enriched, not moved.
    }
};
