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
        // Check if trip_no column already exists before adding
        if (!Schema::hasColumn('sales_orders', 'trip_no')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->integer('trip_no')->default(1)->after('so_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('sales_orders', 'trip_no')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn('trip_no');
            });
        }
    }
};
