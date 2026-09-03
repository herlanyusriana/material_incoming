<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Ensure the original user_id audit column (present in older schemas) is
     * nullable across the forecast tables.
     *
     * Background: older forecast_histories / forecasts schemas recorded the
     * acting user in a `user_id` column (NOT NULL), while the current models and
     * ForecastController write an audit trail via `ForecastHistory` using
     * `changed_by` (a display name) — they never insert `user_id`. After the
     * anti-drift migration (2026_09_03_000001) added `changed_by`, the previous
     * "Unknown column 'changed_by'" (42S22) became "Field 'user_id' doesn't have
     * a default value" (1364) because a NOT NULL `user_id` survives on servers
     * that were built from the legacy schema and is never populated by INSERT.
     *
     * This mirrors 2026_09_03_000003 (mrp_histories.user_id): relax any pre-existing
     * user_id to nullable. Each table is handled in its own closure with its own
     * name captured via a marked parameter (no $this->{{$table}} string mixups),
     * the column is added only when missing, and no ->after() is used so it works
     * regardless of the current column layout. Re-running is a safe no-op.
     */
    public function up(): void
    {
        foreach (['forecast_histories', 'forecasts', 'forecast_documents', 'forecast_document_rows'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                if (Schema::hasColumn($table, 'user_id')) {
                    $t->unsignedBigInteger('user_id')->nullable()->change();
                } else {
                    $t->unsignedBigInteger('user_id')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        // No-op: re-enforcing NOT NULL would break inserts that never supply user_id.
    }
};
