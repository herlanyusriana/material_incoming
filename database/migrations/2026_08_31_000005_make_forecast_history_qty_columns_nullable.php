<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * qty_before / qty_after only apply to per-forecast edits. Global actions
     * (clear, commit_plan) don't have them, so they must be nullable.
     */
    public function up(): void
    {
        Schema::table('forecast_histories', function (Blueprint $table) {
            if (Schema::hasColumn('forecast_histories', 'qty_before')) {
                $table->decimal('qty_before', 20, 3)->nullable()->change();
            }
            if (Schema::hasColumn('forecast_histories', 'qty_after')) {
                $table->decimal('qty_after', 20, 3)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('forecast_histories', function (Blueprint $table) {
            if (Schema::hasColumn('forecast_histories', 'qty_before')) {
                $table->decimal('qty_before', 20, 3)->nullable(false)->change();
            }
            if (Schema::hasColumn('forecast_histories', 'qty_after')) {
                $table->decimal('qty_after', 20, 3)->nullable(false)->change();
            }
        });
    }
};
