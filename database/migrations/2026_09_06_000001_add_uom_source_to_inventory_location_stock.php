<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_location_stock', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_location_stock', 'uom')) {
                $table->string('uom', 20)->nullable()->after('qty_on_hand');
            }
            if (! Schema::hasColumn('inventory_location_stock', 'source_type')) {
                $table->string('source_type', 30)->nullable()->after('uom');
            }
            if (! Schema::hasColumn('inventory_location_stock', 'source_ref')) {
                $table->string('source_ref', 255)->nullable()->after('source_type');
            }
        });

        // Backfill UOM dari master part
        DB::statement(
            'UPDATE inventory_location_stock s
             INNER JOIN gci_parts p ON p.id = s.gci_part_id
             SET s.uom = p.subcount_uom
             WHERE s.uom IS NULL AND p.subcount_uom IS NOT NULL'
        );

        // Backfill source dari movement terakhir per (part, location, batch)
        DB::statement(
            "UPDATE inventory_location_stock s
             INNER JOIN (
                 SELECT m.gci_part_id, COALESCE(m.to_location_code, m.from_location_code) AS location_code,
                        COALESCE(m.batch_no, '') AS batch_no,
                        m.transaction_type, m.source_reference
                 FROM inventory_stock_movements m
                 INNER JOIN (
                     SELECT gci_part_id, COALESCE(to_location_code, from_location_code) AS location_code,
                            COALESCE(batch_no, '') AS batch_no, MAX(id) AS max_id
                     FROM inventory_stock_movements
                     GROUP BY gci_part_id, COALESCE(to_location_code, from_location_code), COALESCE(batch_no, '')
                 ) latest ON latest.max_id = m.id
             ) src ON src.gci_part_id = s.gci_part_id
                  AND src.location_code = s.location_code
                  AND src.batch_no = s.batch_no
             SET s.source_type = src.transaction_type,
                 s.source_ref = src.source_reference"
        );
    }

    public function down(): void
    {
        Schema::table('inventory_location_stock', function (Blueprint $table) {
            $table->dropColumn(['uom', 'source_type', 'source_ref']);
        });
    }
};
