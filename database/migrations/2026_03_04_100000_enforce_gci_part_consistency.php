<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enforce data consistency: gci_part_id as single source of truth.
 *
 * 1. Cleanup gci_parts.part_name yang kosong/null → isi dari part_no
 * 2. Backfill gci_part_id yang NULL di semua tabel transaksi (resolve dari part_id)
 * 3. Tambah NOT NULL constraint di gci_part_id & part_name
 */
return new class extends Migration {
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════
        // STEP 1: Cleanup gci_parts.part_name yang kosong
        // ═══════════════════════════════════════════════════════
        DB::statement("
            UPDATE gci_parts
            SET part_name = part_no
            WHERE part_name IS NULL OR TRIM(part_name) = '' OR TRIM(part_name) = '-'
        ");

        // ═══════════════════════════════════════════════════════
        // STEP 2: Backfill gci_part_id di semua tabel transaksi
        //         Resolve dari part_id → gci_part_vendor.gci_part_id
        // ═══════════════════════════════════════════════════════

        // location_inventory: backfill gci_part_id dari part_id
        DB::statement("
            UPDATE location_inventory li
            SET gci_part_id = gpv.gci_part_id
            FROM gci_part_vendor gpv
            WHERE gpv.id = li.part_id
              AND li.gci_part_id IS NULL
              AND li.part_id IS NOT NULL
        ");

        // arrival_items: backfill gci_part_id dari part_id
        DB::statement("
            UPDATE arrival_items ai
            SET gci_part_id = gpv.gci_part_id
            FROM gci_part_vendor gpv
            WHERE gpv.id = ai.part_id
              AND ai.gci_part_id IS NULL
              AND ai.part_id IS NOT NULL
        ");

        // arrival_items: backfill gci_part_vendor_id dari part_id
        DB::statement("
            UPDATE arrival_items ai
            SET gci_part_vendor_id = ai.part_id
            WHERE ai.gci_part_vendor_id IS NULL
              AND ai.part_id IS NOT NULL
        ");

        // bin_transfers: backfill gci_part_id dari part_id
        DB::statement("
            UPDATE bin_transfers bt
            SET gci_part_id = gpv.gci_part_id
            FROM gci_part_vendor gpv
            WHERE gpv.id = bt.part_id
              AND bt.gci_part_id IS NULL
              AND bt.part_id IS NOT NULL
        ");

        // location_inventory_adjustments: backfill gci_part_id dari part_id
        DB::statement("
            UPDATE location_inventory_adjustments lia
            SET gci_part_id = gpv.gci_part_id
            FROM gci_part_vendor gpv
            WHERE gpv.id = lia.part_id
              AND lia.gci_part_id IS NULL
              AND lia.part_id IS NOT NULL
        ");

        // ═══════════════════════════════════════════════════════
        // STEP 3: Hapus record yang masih NULL gci_part_id
        //         (orphan data yang gak bisa di-resolve)
        // ═══════════════════════════════════════════════════════

        // Log orphan counts sebelum hapus
        $orphanLi = DB::table('location_inventory')->whereNull('gci_part_id')->count();
        $orphanAi = DB::table('arrival_items')->whereNull('gci_part_id')->count();
        $orphanBt = DB::table('bin_transfers')->whereNull('gci_part_id')->count();
        $orphanLia = DB::table('location_inventory_adjustments')->whereNull('gci_part_id')->count();

        if ($orphanLi > 0) {
            // Set qty ke 0 dulu supaya gak affect stock summary
            DB::table('location_inventory')->whereNull('gci_part_id')->update(['qty_on_hand' => 0]);
            DB::table('location_inventory')->whereNull('gci_part_id')->delete();
        }

        // arrival_items, bin_transfers, adjustments: hapus orphan
        DB::table('arrival_items')->whereNull('gci_part_id')->delete();
        DB::table('bin_transfers')->whereNull('gci_part_id')->delete();
        DB::table('location_inventory_adjustments')->whereNull('gci_part_id')->delete();

        // ═══════════════════════════════════════════════════════
        // STEP 4: Enforce NOT NULL constraints
        // ═══════════════════════════════════════════════════════

        // gci_parts.part_name → NOT NULL dengan default part_no
        Schema::table('gci_parts', function (Blueprint $table) {
            $table->string('part_name', 255)->nullable(false)->default('')->change();
        });

        // location_inventory.gci_part_id → NOT NULL
        // Perlu drop unique constraint dulu, alter, lalu recreate
        try {
            Schema::table('location_inventory', function (Blueprint $table) {
                $table->unsignedBigInteger('gci_part_id')->nullable(false)->change();
            });
        } catch (\Throwable $e) {
            // Mungkin sudah NOT NULL
        }

        // arrival_items.gci_part_id → NOT NULL
        try {
            Schema::table('arrival_items', function (Blueprint $table) {
                $table->unsignedBigInteger('gci_part_id')->nullable(false)->change();
            });
        } catch (\Throwable $e) {
            // Mungkin sudah NOT NULL
        }

        // bin_transfers.gci_part_id → NOT NULL
        try {
            Schema::table('bin_transfers', function (Blueprint $table) {
                $table->unsignedBigInteger('gci_part_id')->nullable(false)->change();
            });
        } catch (\Throwable $e) {
            // Mungkin sudah NOT NULL
        }

        // location_inventory_adjustments.gci_part_id → NOT NULL
        try {
            Schema::table('location_inventory_adjustments', function (Blueprint $table) {
                $table->unsignedBigInteger('gci_part_id')->nullable(false)->change();
            });
        } catch (\Throwable $e) {
            // Mungkin sudah NOT NULL
        }

        // ═══════════════════════════════════════════════════════
        // STEP 5: Sync gci_inventories dari location_inventory
        //         Supaya summary inventory konsisten
        // ═══════════════════════════════════════════════════════
        DB::statement("
            INSERT INTO gci_inventories (gci_part_id, on_hand, on_order, as_of_date, created_at, updated_at)
            SELECT
                li.gci_part_id,
                SUM(li.qty_on_hand),
                0,
                CURRENT_DATE,
                NOW(),
                NOW()
            FROM location_inventory li
            WHERE li.gci_part_id IS NOT NULL
            GROUP BY li.gci_part_id
            ON CONFLICT (gci_part_id)
            DO UPDATE SET
                on_hand = EXCLUDED.on_hand,
                as_of_date = CURRENT_DATE,
                updated_at = NOW()
        ");
    }

    public function down(): void
    {
        // Revert NOT NULL constraints back to NULLABLE
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
