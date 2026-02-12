<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if column exists before adding it
        if (!Schema::hasColumn('mrp_purchase_plans', 'incoming_stock')) {
            Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                $table->decimal('incoming_stock', 15, 2)->default(0)->after('on_order');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mrp_purchase_plans', 'incoming_stock')) {
            Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                $table->dropColumn('incoming_stock');
            });
        }
    }
};