<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * forecast_histories is also used for global actions (clear, commit_plan)
     * that don't target a single forecast row, so forecast_id must be nullable.
     */
    public function up(): void
    {
        Schema::table('forecast_histories', function (Blueprint $table) {
            if (Schema::hasColumn('forecast_histories', 'forecast_id')) {
                $table->unsignedBigInteger('forecast_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('forecast_histories', function (Blueprint $table) {
            if (Schema::hasColumn('forecast_histories', 'forecast_id')) {
                $table->unsignedBigInteger('forecast_id')->nullable(false)->change();
            }
        });
    }
};
