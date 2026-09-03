<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Anti-drift migration: ensure the audit columns that ForecastController +
     * forecast history view assume are present.
     *
     * On some environments (e.g. a deploy where forecast_histories was created
     * from an older schema, or the create migration was skipped because the
     * table already existed) columns like `changed_by` / `qty_after` may be
     * missing. Each column is added independently and guarded by hasColumn, so
     * re-running is a safe no-op and no assumption is made about a column's
     * position/order (no `->after(...)` — that would fail when the anchor
     * column is absent).
     */
    public function up(): void
    {
        Schema::table('forecast_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('forecast_histories', 'qty_before')) {
                $table->decimal('qty_before', 20, 3)->nullable();
            }
            if (!Schema::hasColumn('forecast_histories', 'qty_after')) {
                $table->decimal('qty_after', 20, 3)->nullable();
            }
            if (!Schema::hasColumn('forecast_histories', 'changed_by')) {
                $table->string('changed_by')->nullable();
            }
            if (!Schema::hasColumn('forecast_histories', 'action')) {
                $table->string('action', 30)->nullable();
            }
            if (!Schema::hasColumn('forecast_histories', 'parts_count')) {
                $table->unsignedInteger('parts_count')->default(0);
            }
            if (!Schema::hasColumn('forecast_histories', 'weeks_generated')) {
                $table->unsignedInteger('weeks_generated')->default(0);
            }
        });
    }

    public function down(): void
    {
        // Intentionally left as a no-op: dropping columns could lose history
        // records on environments where these were the only source of data.
        // Keep the table backward-compatible.
    }
};
