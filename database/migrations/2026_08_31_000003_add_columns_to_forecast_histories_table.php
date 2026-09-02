<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add the log columns that ForecastController + history view already assume.
     */
    public function up(): void
    {
        Schema::table('forecast_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('forecast_histories', 'action')) {
                $table->string('action', 30)->nullable()->after('changed_by'); // generate | clear | commit_plan
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
        Schema::table('forecast_histories', function (Blueprint $table) {
            if (Schema::hasColumn('forecast_histories', 'action')) {
                $table->dropColumn('action');
            }
            if (Schema::hasColumn('forecast_histories', 'parts_count')) {
                $table->dropColumn('parts_count');
            }
            if (Schema::hasColumn('forecast_histories', 'weeks_generated')) {
                $table->dropColumn('weeks_generated');
            }
        });
    }
};
