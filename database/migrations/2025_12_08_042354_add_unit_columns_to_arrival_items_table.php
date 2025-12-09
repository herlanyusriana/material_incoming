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
            $table->string('unit_bundle', 20)->nullable()->after('qty_bundle');
            $table->string('unit_weight', 20)->nullable()->after('weight_nett');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arrival_items', function (Blueprint $table) {
            $table->dropColumn(['unit_bundle', 'unit_weight']);
        });
    }
};
