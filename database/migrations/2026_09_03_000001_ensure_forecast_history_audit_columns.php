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
     * from an older schema) the `changed_by` column is missing. This makes the
     * table match the create migration regardless of its current state. Every
     * change is guarded by hasColumn so re-running is a safe no-op.
     */
    public function up(): void
    {
        Schema::table('forecast_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('forecast_histories', 'changed_by')) {
                $table->string('changed_by')->nullable()->after('qty_after');
            }
            if (!Schema::hasColumn('forecast_histories', 'action')) {
                $table->string('action', 30)->nullable()->after('changed_by');
            }
            if (!Schema::hasColumn('forecast_histories', 'parts_count')) {
                $table->unsignedInteger('parts_count')->default(0)->after('action');
            }
            if (!Schema::hasColumn('forecast_histories', 'weeks_generated')) {
                $table->unsignedInteger('weeks_generated')->default(0)->after('parts_count');
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
