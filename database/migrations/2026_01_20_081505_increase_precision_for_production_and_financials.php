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
        Schema::table('production_orders', function (Blueprint $table) {
            $table->decimal('qty_planned', 18, 4)->change();
            $table->decimal('qty_actual', 18, 4)->change();
            $table->decimal('qty_rejected', 18, 4)->change();
        });

        Schema::table('parts', function (Blueprint $table) {
            $table->decimal('price', 20, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->change();
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->decimal('qty_planned', 10, 2)->change();
            $table->decimal('qty_actual', 10, 2)->change();
            $table->decimal('qty_rejected', 10, 2)->change();
        });
    }
};
