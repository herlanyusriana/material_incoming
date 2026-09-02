<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the lead-time offset to the MRP buying phase.
 *
 * `eta_week`   = when the goods are NEEDED (the planning week, YYYY-Www).
 * `order_week` = when the PO should be PLACED so goods arrive by `eta_week`
 *                (i.e. `eta_week` minus the vendor's `lead_time_days`).
 *
 * This lets purchasing see the "order now" date independently of the need date
 * so lead time is visible in the plan (and surfaced in the report view).
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('mrp_purchase_plans') && !Schema::hasColumn('mrp_purchase_plans', 'order_week')) {
            Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                $table->string('order_week', 8)->nullable()->after('eta_week');
                $table->index('order_week');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('mrp_purchase_plans', 'order_week')) {
            Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                $table->dropColumn('order_week');
            });
        }
    }
};
