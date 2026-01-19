<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mrp_production_plans', function (Blueprint $table) {
            $table->date('plan_date')->nullable()->after('part_id');
            $table->index('plan_date');
        });

        Schema::table('mrp_purchase_plans', function (Blueprint $table) {
            $table->date('plan_date')->nullable()->after('part_id');
            $table->index('plan_date');
        });
    }

    public function down(): void
    {
        Schema::table('mrp_production_plans', function (Blueprint $table) {
            $table->dropColumn('plan_date');
        });

        Schema::table('mrp_purchase_plans', function (Blueprint $table) {
            $table->dropColumn('plan_date');
        });
    }
};
