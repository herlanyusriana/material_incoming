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
        Schema::table('subcon_orders', function (Blueprint $table) {
            $table->decimal('weight_kgm', 12, 4)->nullable()->after('qty_sent');
        });

        Schema::table('subcon_order_receives', function (Blueprint $table) {
            $table->decimal('weight_kgm', 12, 4)->nullable()->after('qty_good');
            $table->decimal('weight_rejected_kgm', 12, 4)->nullable()->after('qty_rejected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subcon_orders', function (Blueprint $table) {
            $table->dropColumn('weight_kgm');
        });

        Schema::table('subcon_order_receives', function (Blueprint $table) {
            $table->dropColumn(['weight_kgm', 'weight_rejected_kgm']);
        });
    }
};
