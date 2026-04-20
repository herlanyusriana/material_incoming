<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bom_items')) {
            return;
        }

        Schema::table('bom_items', function (Blueprint $table) {
            if (!Schema::hasColumn('bom_items', 'make_or_buy')) {
                $table->string('make_or_buy', 20)->default('buy')->after('component_part_id');
            }

            if (!Schema::hasColumn('bom_items', 'special')) {
                $table->string('special', 50)->nullable()->after('make_or_buy');
            }
        });
    }

    public function down(): void
    {
        // Intentionally left blank. This migration repairs production schema drift
        // and should not remove BOM columns that may have existed before it ran.
    }
};
