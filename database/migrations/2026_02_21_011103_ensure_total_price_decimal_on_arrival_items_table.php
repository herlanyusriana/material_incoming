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
        Schema::table('arrival_items', function (Blueprint $table) {
            if (Schema::hasColumn('arrival_items', 'total_price')) {
                $table->decimal('total_price', 20, 3)->nullable()->change();
            } else {
                $table->decimal('total_price', 20, 3)->nullable()->after('price');
            }

            if (Schema::hasColumn('arrival_items', 'price')) {
                $table->decimal('price', 20, 3)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed — this only ensures correct column types
    }
};
