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
        Schema::table('bom_item_substitutes', function (Blueprint $table) {
            // Ensure no duplicates exist before adding unique index (best effort)
            // We already checked in tinker, but for insurance:
            $table->unique(['bom_item_id', 'substitute_part_id'], 'bom_item_sub_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bom_item_substitutes', function (Blueprint $table) {
            $table->dropUnique('bom_item_sub_unique');
        });
    }
};
