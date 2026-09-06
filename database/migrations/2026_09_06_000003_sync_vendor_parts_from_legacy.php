<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Syncs vendor_parts from legacy gci_part_vendor, overwriting mismatched
 * values (not just filling empty ones like the previous backfill migration).
 *
 * This fixes record id=4 which had vendor_part_name = "BACK PLATE VT 7"
 * (copied from gci_parts.part_name) instead of "STEEL IN SHEET" (the correct
 * value from gci_part_vendor).
 *
 * Idempotent — safe to run multiple times.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('gci_part_vendor')) {
            return;
        }

        // Sync vendor_part_name — always overwrite from gci_part_vendor when different
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.vendor_part_name = gpv.vendor_part_name
            WHERE gpv.vendor_part_name IS NOT NULL
                AND TRIM(gpv.vendor_part_name) <> ''
                AND (
                    vp.vendor_part_name IS NULL
                    OR TRIM(vp.vendor_part_name) = ''
                    OR vp.vendor_part_name != gpv.vendor_part_name
                )
        ");

        // Sync register_no — overwrite from gci_part_vendor when different
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.register_no = gpv.register_no
            WHERE gpv.register_no IS NOT NULL
                AND TRIM(gpv.register_no) <> ''
                AND (
                    vp.register_no IS NULL
                    OR TRIM(vp.register_no) = ''
                    OR vp.register_no != gpv.register_no
                )
        ");

        // Sync vendor_part_no — overwrite from gci_part_vendor when different
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.vendor_part_no = gpv.vendor_part_no
            WHERE gpv.vendor_part_no IS NOT NULL
                AND TRIM(gpv.vendor_part_no) <> ''
                AND (
                    vp.vendor_part_no IS NULL
                    OR TRIM(vp.vendor_part_no) = ''
                    OR vp.vendor_part_no != gpv.vendor_part_no
                )
        ");

        // Sync uom — overwrite from gci_part_vendor when different
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.uom = gpv.uom
            WHERE gpv.uom IS NOT NULL
                AND TRIM(gpv.uom) <> ''
                AND (
                    vp.uom IS NULL
                    OR TRIM(vp.uom) = ''
                    OR vp.uom != gpv.uom
                )
        ");

        // Sync hs_code — overwrite from gci_part_vendor when different
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.hs_code = gpv.hs_code
            WHERE gpv.hs_code IS NOT NULL
                AND TRIM(gpv.hs_code) <> ''
                AND (
                    vp.hs_code IS NULL
                    OR TRIM(vp.hs_code) = ''
                    OR vp.hs_code != gpv.hs_code
                )
        ");

        // Sync price — overwrite from gci_part_vendor when different
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.price = gpv.price
            WHERE gpv.price IS NOT NULL
                AND (
                    vp.price IS NULL
                    OR vp.price != gpv.price
                )
        ");

        // Sync status — overwrite from gci_part_vendor when different
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.status = gpv.status
            WHERE gpv.status IS NOT NULL
                AND TRIM(gpv.status) <> ''
                AND (
                    vp.status IS NULL
                    OR TRIM(vp.status) = ''
                    OR vp.status != gpv.status
                )
        ");

        // Sync quality_inspection — overwrite from gci_part_vendor
        DB::statement("
            UPDATE vendor_parts vp
            INNER JOIN gci_part_vendor gpv
                ON vp.gci_part_id = gpv.gci_part_id
                AND vp.vendor_id = gpv.vendor_id
            SET vp.quality_inspection = gpv.quality_inspection
            WHERE gpv.quality_inspection IS NOT NULL
                AND vp.quality_inspection != gpv.quality_inspection
        ");
    }

    public function down(): void
    {
        // Cannot undo syncs — data is corrected, not moved.
    }
};
