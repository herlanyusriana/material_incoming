<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mrp_purchase_plans')) {
            Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('mrp_purchase_plans', 'net_required')) {
                    $table->decimal('net_required', 20, 4)->default(0)->after('plan_date');
                }
                if (!Schema::hasColumn('mrp_purchase_plans', 'planned_order_rec')) {
                    $table->decimal('planned_order_rec', 20, 4)->default(0)->after('net_required');
                }
            });
        }

        if (Schema::hasTable('mrp_production_plans')) {
            Schema::table('mrp_production_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('mrp_production_plans', 'net_required')) {
                    $table->decimal('net_required', 20, 4)->default(0)->after('plan_date');
                }
                if (!Schema::hasColumn('mrp_production_plans', 'planned_order_rec')) {
                    $table->decimal('planned_order_rec', 20, 4)->default(0)->after('net_required');
                }
            });
        }
    }

    public function down(): void
    {
        // Best-effort down: keep columns (safe/no-op).
    }
};

