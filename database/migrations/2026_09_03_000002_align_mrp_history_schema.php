<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Align mrp_histories with the MrpHistory model + MrpController usage.
     *
     * MrpHistory fills user_id / action / parts_count / notes (global log rows from
     * MrpController@clear and run-level rows). The create migration only defined
     * mrp_run_id, part_id, plan_date, plan_type, qty_before, qty_after, notes —
     * all per-row NOT NULL. Two problems:
     *
     *   1. The audit columns (user_id, action, parts_count) the model writes are
     *      absent, so INSERT fails with "Unknown column".
     *   2. A global log row (clear) has NO per-row data (mrp_run_id, part_id,
     *      plan_date, plan_type, qty_before, qty_after), so those NOT NULL columns
     *      reject the row with "Field ... doesn't have a default value" (1354/1364).
     *
     * Every change is guarded by hasColumn so re-running is a safe no-op, and no
     * ->after() is used so it works on any existing schema.
     */
    public function up(): void
    {
        Schema::table('mrp_histories', function (Blueprint $table) {
            // Audit columns the MrpHistory model writes.
            if (!Schema::hasColumn('mrp_histories', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->index();
            }
            if (!Schema::hasColumn('mrp_histories', 'action')) {
                $table->string('action', 30)->nullable();
            }
            if (!Schema::hasColumn('mrp_histories', 'parts_count')) {
                $table->unsignedInteger('parts_count')->default(0);
            }

            // Per-row columns are only present for row-level history; a global log
            // row (clear) leaves them NULL, so they must be nullable.
            if (Schema::hasColumn('mrp_histories', 'mrp_run_id')) {
                $table->unsignedBigInteger('mrp_run_id')->nullable()->change();
            }
            if (Schema::hasColumn('mrp_histories', 'part_id')) {
                $table->unsignedBigInteger('part_id')->nullable()->change();
            }
            if (Schema::hasColumn('mrp_histories', 'plan_date')) {
                $table->date('plan_date')->nullable()->change();
            }
            if (Schema::hasColumn('mrp_histories', 'plan_type')) {
                $table->string('plan_type')->nullable()->change();
            }
            if (Schema::hasColumn('mrp_histories', 'qty_before')) {
                $table->decimal('qty_before', 20, 4)->nullable()->change();
            }
            if (Schema::hasColumn('mrp_histories', 'qty_after')) {
                $table->decimal('qty_after', 20, 4)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        // No-op: reversing could drop audit columns or re-enforce NOT NULL and
        // lose history rows. Keep the table backward-compatible.
    }
};
