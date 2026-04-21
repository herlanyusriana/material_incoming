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
        Schema::table('location_inventory_adjustments', function (Blueprint $table) {
            $table->decimal('weight_kgm', 12, 4)->nullable()->after('qty_change');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_inventory_adjustments', function (Blueprint $table) {
            $table->dropColumn('weight_kgm');
        });
    }
};
