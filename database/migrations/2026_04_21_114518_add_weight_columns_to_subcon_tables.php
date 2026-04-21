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
            if (!Schema::hasColumn('subcon_orders', 'weight_kgm')) {
                $table->decimal('weight_kgm', 12, 4)->nullable()->after('qty_sent');
            }
        });

        Schema::table('subcon_order_receives', function (Blueprint $table) {
            if (!Schema::hasColumn('subcon_order_receives', 'weight_kgm')) {
                $table->decimal('weight_kgm', 12, 4)->nullable()->after('qty_good');
            }
            if (!Schema::hasColumn('subcon_order_receives', 'weight_rejected_kgm')) {
                $table->decimal('weight_rejected_kgm', 12, 4)->nullable()->after('qty_rejected');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subcon_orders', function (Blueprint $table) {
            if (Schema::hasColumn('subcon_orders', 'weight_kgm')) {
                $table->dropColumn('weight_kgm');
            }
        });

        Schema::table('subcon_order_receives', function (Blueprint $table) {
            $drops = [];
            if (Schema::hasColumn('subcon_order_receives', 'weight_kgm')) {
                $drops[] = 'weight_kgm';
            }
            if (Schema::hasColumn('subcon_order_receives', 'weight_rejected_kgm')) {
                $drops[] = 'weight_rejected_kgm';
            }
            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};
