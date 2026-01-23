<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mrp_production_plans')) {
            Schema::table('mrp_production_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('mrp_production_plans', 'planned_qty')) {
                    $table->decimal('planned_qty', 20, 4)->default(0)->after('plan_date');
                }
            });
        }

        if (Schema::hasTable('mrp_purchase_plans')) {
            Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                if (!Schema::hasColumn('mrp_purchase_plans', 'required_qty')) {
                    $table->decimal('required_qty', 20, 4)->default(0)->after('plan_date');
                }
                if (!Schema::hasColumn('mrp_purchase_plans', 'on_hand')) {
                    $table->decimal('on_hand', 20, 4)->default(0)->after('required_qty');
                }
                if (!Schema::hasColumn('mrp_purchase_plans', 'on_order')) {
                    $table->decimal('on_order', 20, 4)->default(0)->after('on_hand');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mrp_purchase_plans')) {
            Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                if (Schema::hasColumn('mrp_purchase_plans', 'on_order')) {
                    $table->dropColumn('on_order');
                }
                if (Schema::hasColumn('mrp_purchase_plans', 'on_hand')) {
                    $table->dropColumn('on_hand');
                }
                if (Schema::hasColumn('mrp_purchase_plans', 'required_qty')) {
                    $table->dropColumn('required_qty');
                }
            });
        }

        if (Schema::hasTable('mrp_production_plans')) {
            Schema::table('mrp_production_plans', function (Blueprint $table) {
                if (Schema::hasColumn('mrp_production_plans', 'planned_qty')) {
                    $table->dropColumn('planned_qty');
                }
            });
        }
    }
};

