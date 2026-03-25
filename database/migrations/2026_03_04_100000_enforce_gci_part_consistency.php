<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enforce data consistency: gci_part_id as single source of truth.
 *
 * 1. Cleanup empty/null gci_parts.part_name values
 * 2. Backfill missing gci_part_id values in transactional tables from part_id
 * 3. Remove orphan records that still cannot be resolved
 * 4. Tighten nullable constraints where possible
 * 5. Resync gci_inventories from location_inventory
 */
return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();

        DB::statement("
            UPDATE gci_parts
            SET part_name = part_no
            WHERE part_name IS NULL OR TRIM(part_name) = '' OR TRIM(part_name) = '-'
        ");

        if ($driver === 'pgsql') {
            DB::statement("
                UPDATE location_inventory AS li
                SET gci_part_id = gpv.gci_part_id
                FROM gci_part_vendor AS gpv
                WHERE gpv.id = li.part_id
                  AND li.gci_part_id IS NULL
                  AND li.part_id IS NOT NULL
            ");

            DB::statement("
                UPDATE arrival_items AS ai
                SET gci_part_id = gpv.gci_part_id
                FROM gci_part_vendor AS gpv
                WHERE gpv.id = ai.part_id
                  AND ai.gci_part_id IS NULL
                  AND ai.part_id IS NOT NULL
            ");

            DB::statement("
                UPDATE bin_transfers AS bt
                SET gci_part_id = gpv.gci_part_id
                FROM gci_part_vendor AS gpv
                WHERE gpv.id = bt.part_id
                  AND bt.gci_part_id IS NULL
                  AND bt.part_id IS NOT NULL
            ");

            DB::statement("
                UPDATE location_inventory_adjustments AS lia
                SET gci_part_id = gpv.gci_part_id
                FROM gci_part_vendor AS gpv
                WHERE gpv.id = lia.part_id
                  AND lia.gci_part_id IS NULL
                  AND lia.part_id IS NOT NULL
            ");
        } else {
            DB::statement("
                UPDATE location_inventory li
                INNER JOIN gci_part_vendor gpv ON gpv.id = li.part_id
                SET li.gci_part_id = gpv.gci_part_id
                WHERE li.gci_part_id IS NULL
                  AND li.part_id IS NOT NULL
            ");

            DB::statement("
                UPDATE arrival_items ai
                INNER JOIN gci_part_vendor gpv ON gpv.id = ai.part_id
                SET ai.gci_part_id = gpv.gci_part_id
                WHERE ai.gci_part_id IS NULL
                  AND ai.part_id IS NOT NULL
            ");

            DB::statement("
                UPDATE bin_transfers bt
                INNER JOIN gci_part_vendor gpv ON gpv.id = bt.part_id
                SET bt.gci_part_id = gpv.gci_part_id
                WHERE bt.gci_part_id IS NULL
                  AND bt.part_id IS NOT NULL
            ");

            DB::statement("
                UPDATE location_inventory_adjustments lia
                INNER JOIN gci_part_vendor gpv ON gpv.id = lia.part_id
                SET lia.gci_part_id = gpv.gci_part_id
                WHERE lia.gci_part_id IS NULL
                  AND lia.part_id IS NOT NULL
            ");
        }

        DB::statement("
            UPDATE arrival_items
            SET gci_part_vendor_id = part_id
            WHERE gci_part_vendor_id IS NULL
              AND part_id IS NOT NULL
        ");

        $orphanLi = DB::table('location_inventory')->whereNull('gci_part_id')->count();

        if ($orphanLi > 0) {
            DB::table('location_inventory')->whereNull('gci_part_id')->update(['qty_on_hand' => 0]);
            DB::table('location_inventory')->whereNull('gci_part_id')->delete();
        }

        DB::table('arrival_items')->whereNull('gci_part_id')->delete();
        DB::table('bin_transfers')->whereNull('gci_part_id')->delete();
        DB::table('location_inventory_adjustments')->whereNull('gci_part_id')->delete();

        try {
            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE gci_parts ALTER COLUMN part_name SET DEFAULT '', ALTER COLUMN part_name SET NOT NULL");
            } else {
                DB::statement("ALTER TABLE gci_parts MODIFY part_name VARCHAR(255) NOT NULL DEFAULT ''");
            }
        } catch (\Throwable $e) {
            // Ignore if the column is already aligned or the engine rejects the change.
        }

        try {
            Schema::table('location_inventory', function (Blueprint $table) {
                $table->unsignedBigInteger('gci_part_id')->nullable(false)->change();
            });
        } catch (\Throwable $e) {
            // Ignore if already not-null or cannot be changed safely on this environment.
        }

        try {
            Schema::table('arrival_items', function (Blueprint $table) {
                $table->unsignedBigInteger('gci_part_id')->nullable(false)->change();
            });
        } catch (\Throwable $e) {
            // Ignore if already not-null or cannot be changed safely on this environment.
        }

        try {
            Schema::table('bin_transfers', function (Blueprint $table) {
                $table->unsignedBigInteger('gci_part_id')->nullable(false)->change();
            });
        } catch (\Throwable $e) {
            // Ignore if already not-null or cannot be changed safely on this environment.
        }

        try {
            Schema::table('location_inventory_adjustments', function (Blueprint $table) {
                $table->unsignedBigInteger('gci_part_id')->nullable(false)->change();
            });
        } catch (\Throwable $e) {
            // Ignore if already not-null or cannot be changed safely on this environment.
        }

        $inventoryRows = DB::table('location_inventory as li')
            ->selectRaw('li.gci_part_id, SUM(li.qty_on_hand) as on_hand')
            ->whereNotNull('li.gci_part_id')
            ->groupBy('li.gci_part_id')
            ->get();

        $now = now();
        $today = $now->toDateString();

        foreach ($inventoryRows as $row) {
            DB::table('gci_inventories')->updateOrInsert(
                ['gci_part_id' => $row->gci_part_id],
                [
                    'on_hand' => $row->on_hand,
                    'on_order' => 0,
                    'as_of_date' => $today,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::table('gci_parts', function (Blueprint $table) {
            $table->string('part_name', 255)->nullable()->default(null)->change();
        });

        Schema::table('location_inventory', function (Blueprint $table) {
            $table->unsignedBigInteger('gci_part_id')->nullable()->change();
        });

        Schema::table('arrival_items', function (Blueprint $table) {
            $table->unsignedBigInteger('gci_part_id')->nullable()->change();
        });

        Schema::table('bin_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('gci_part_id')->nullable()->change();
        });

        Schema::table('location_inventory_adjustments', function (Blueprint $table) {
            $table->unsignedBigInteger('gci_part_id')->nullable()->change();
        });
    }
};
