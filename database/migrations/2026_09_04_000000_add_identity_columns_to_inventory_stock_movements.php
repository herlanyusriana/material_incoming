<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add the label/batch identity + source-reference columns that the
     * warehouse/production flow expects on inventory_stock_movements.
     *
     * Background: `InventoryLocationStock::updateStock()` records every stock
     * change as an `inventory_stock_movements` row, but the columns production
     * and warehouse code query on — `batch_no`, `transaction_type` and
     * `source_reference` — never existed on this table. As a result:
     *
     *   - the production backflush query (ProductionOrderController) that resolves
     *     an `inventory_stock_movement_id` could never match history rows and
     *     silently left every backflush unmapped to a source receive; and
     *   - movement rows carried only `tag_number`, losing the batch identity that
     *     production FEFO/traceability keys on.
     *
     * This migration adds the three columns (all nullable, so existing rows stay
     * valid) and the model populates them going forward — `batch_no` (falling back
     * to the tag), `transaction_type` and `source_reference`. Every column is
     * guarded by hasColumn so the migration is idempotent across environments.
     */
    public function up(): void
    {
        if (!Schema::hasTable('inventory_stock_movements')) {
            return;
        }

        Schema::table('inventory_stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_stock_movements', 'batch_no')) {
                $table->string('batch_no', 100)->nullable()->index();
            }
            if (!Schema::hasColumn('inventory_stock_movements', 'transaction_type')) {
                $table->string('transaction_type', 80)->nullable()->index();
            }
            if (!Schema::hasColumn('inventory_stock_movements', 'source_reference')) {
                $table->string('source_reference', 255)->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        foreach (['batch_no', 'transaction_type', 'source_reference'] as $column) {
            if (!Schema::hasTable('inventory_stock_movements') || !Schema::hasColumn('inventory_stock_movements', $column)) {
                continue;
            }

            Schema::table('inventory_stock_movements', function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        }
    }
};