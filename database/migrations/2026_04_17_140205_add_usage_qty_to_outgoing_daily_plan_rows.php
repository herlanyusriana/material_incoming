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
        Schema::table('outgoing_daily_plan_rows', function (Blueprint $table) {
            $table->decimal('usage_qty', 10, 4)->nullable()->after('customer_part_id')->default(1.0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outgoing_daily_plan_rows', function (Blueprint $table) {
            $table->dropColumn('usage_qty');
        });
    }
};
