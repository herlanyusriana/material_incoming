<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Widen the audit `action` column so stored values are never truncated.
     *
     * Background: ForecastController and MrpController record trailing audit rows
     * with action values such as `clear` (5) and `commit_plan` (11). The add-column
     * migrations (2026_08_31_000003 and the anti-drift 2026_09_03_000001 / 000002)
     * only create `action` when it is absent (guarded by !hasColumn). On servers
     * where `action` already exists from a legacy schema — but as a shorter type
     * (e.g. varchar(8) / varchar(10)) — the guard skips it, so `commit_plan` no
     * longer fits and MySQL raises "Data truncated for column 'action'" (1265).
     *
     * This migration UNCONDITIONALLY widens an existing `action` (and adds it when
     * missing) to varchar(30). No ->after() is used so it is order/schema
     * independent. Re-running is a safe no-op (change() to the same length is a
     * no-op).
     */
    public function up(): void
    {
        foreach (['forecast_histories', 'mrp_histories', 'mps_histories'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                if (Schema::hasColumn($table, 'action')) {
                    $t->string('action', 30)->nullable()->change();
                } else {
                    $t->string('action', 30)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // No-op: shrinking the column could truncate existing longer values.
    }
};
