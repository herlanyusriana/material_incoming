<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3: Replace `parts` table with a database VIEW.
 *
 * Supports both MySQL and PostgreSQL.
 */
return new class extends Migration {
    private array $foreignKeys = [
        'arrival_items' => 'part_id',
        'inventories' => 'part_id',
        'location_inventory' => 'part_id',
        'location_inventory_adjustments' => 'part_id',
        'bin_transfers' => 'part_id',
        'inventory_transfers' => 'part_id',
        'bom_items' => 'incoming_part_id',
        'bom_item_substitutes' => 'incoming_part_id',
        'purchase_order_items' => 'vendor_part_id',
    ];

    public function up(): void
    {
        $driver = DB::getDriverName();

        // ─── Step 1: Re-seed gci_part_vendor preserving original parts.id ───
        DB::table('gci_part_vendor')->truncate();

        $parts = DB::table('parts')
            ->whereNotNull('gci_part_id')
            ->whereNotNull('vendor_id')
            ->orderBy('id')
            ->get();

        $seen = [];
        foreach ($parts as $p) {
            $key = $p->gci_part_id . '-' . $p->vendor_id;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $qi = false;
            if ($p->quality_inspection !== null) {
                $qiVal = strtoupper(trim((string) $p->quality_inspection));
                $qi = in_array($qiVal, ['YES', '1', 'TRUE']);
            }

            DB::table('gci_part_vendor')->insert([
                'id' => $p->id,
                'gci_part_id' => $p->gci_part_id,
                'vendor_id' => $p->vendor_id,
                'vendor_part_no' => $p->part_no,
                'vendor_part_name' => $p->part_name_vendor,
                'register_no' => $p->register_no,
                'price' => $p->price ?? 0,
                'uom' => $p->uom,
                'hs_code' => $p->hs_code,
                'quality_inspection' => $qi,
                'status' => $p->status ?? 'active',
                'created_at' => $p->created_at ?? now(),
                'updated_at' => $p->updated_at ?? now(),
            ]);
        }

        // Reset auto-increment sequence
        $maxId = DB::table('gci_part_vendor')->max('id') ?? 0;
        if ($maxId > 0) {
            if ($driver === 'pgsql') {
                DB::statement("SELECT setval(pg_get_serial_sequence('gci_part_vendor', 'id'), {$maxId})");
            } else {
                DB::statement("ALTER TABLE gci_part_vendor AUTO_INCREMENT = " . ($maxId + 1));
            }
        }

        // ─── Step 2: Drop all FK constraints pointing to `parts` ───
        foreach ($this->foreignKeys as $table => $column) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            Schema::table($table, function ($blueprint) use ($table, $column) {
                try {
                    $blueprint->dropForeign("{$table}_{$column}_foreign");
                } catch (\Throwable $e) {
                    // FK might not exist
                }
            });
        }

        // ─── Step 3: Rename `parts` → `parts_legacy` ───
        Schema::rename('parts', 'parts_legacy');

        // ─── Step 4: Create VIEW `parts` ───
        // Boolean comparison differs: PG uses `= true`, MySQL uses `= 1`
        $boolCheck = ($driver === 'pgsql') ? 'gpv.quality_inspection = true' : 'gpv.quality_inspection = 1';

        DB::statement("
            CREATE VIEW parts AS
            SELECT
                gpv.id,
                gpv.gci_part_id,
                gpv.vendor_id,
                gpv.vendor_part_no AS part_no,
                gpv.vendor_part_name AS part_name_vendor,
                gp.part_name AS part_name_gci,
                gpv.register_no,
                gpv.price,
                gpv.uom,
                gpv.hs_code,
                CASE WHEN {$boolCheck} THEN 'YES' ELSE NULL END AS quality_inspection,
                gpv.status,
                gpv.created_at,
                gpv.updated_at
            FROM gci_part_vendor gpv
            JOIN gci_parts gp ON gp.id = gpv.gci_part_id
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS parts");

        Schema::rename('parts_legacy', 'parts');

        foreach ($this->foreignKeys as $table => $column) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            try {
                Schema::table($table, function ($blueprint) use ($column) {
                    $blueprint->foreign($column)->references('id')->on('parts')->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // Best effort
            }
        }
    }
};
