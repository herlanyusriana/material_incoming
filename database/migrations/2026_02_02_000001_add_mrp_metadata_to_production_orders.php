<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('production_orders', 'mrp_run_id')) {
                $table->foreignId('mrp_run_id')->nullable()->constrained('mrp_runs')->nullOnDelete();
            }
            if (!Schema::hasColumn('production_orders', 'mrp_period')) {
                $table->string('mrp_period')->nullable()->after('mrp_run_id');
            }
            if (!Schema::hasColumn('production_orders', 'mrp_generated')) {
                $table->boolean('mrp_generated')->default(false)->after('mrp_period');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            if (Schema::hasColumn('production_orders', 'mrp_generated')) {
                $table->dropColumn('mrp_generated');
            }
            if (Schema::hasColumn('production_orders', 'mrp_period')) {
                $table->dropColumn('mrp_period');
            }
            if (Schema::hasColumn('production_orders', 'mrp_run_id')) {
                $table->dropConstrainedForeignId('mrp_run_id');
            }
        });
    }
};
