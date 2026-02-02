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
        if (!Schema::hasColumn('bom_item_substitutes', 'substitute_part_no')) {
            Schema::table('bom_item_substitutes', function (Blueprint $table) {
                $table->string('substitute_part_no')->nullable()->after('substitute_part_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op to avoid dropping data if it was already there
    }
};
