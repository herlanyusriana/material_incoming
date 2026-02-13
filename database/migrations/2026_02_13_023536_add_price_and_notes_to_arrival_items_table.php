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
            if (!Schema::hasColumn('arrival_items', 'price')) {
                $table->decimal('price', 20, 3)->nullable()->after('weight_gross');
            }
            if (!Schema::hasColumn('arrival_items', 'total_price')) {
                $table->decimal('total_price', 20, 3)->nullable()->after('price');
            }
            if (!Schema::hasColumn('arrival_items', 'notes')) {
                $table->text('notes')->nullable()->after('total_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_items', function (Blueprint $table) {
            $table->dropColumn(['price', 'total_price', 'notes']);
        });
    }
};
