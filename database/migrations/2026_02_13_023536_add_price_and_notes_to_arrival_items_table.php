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
            $table->decimal('price', 20, 3)->nullable()->after('weight_gross');
            $table->decimal('total_price', 20, 3)->nullable()->after('price');
            $table->text('notes')->nullable()->after('total_price');
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
