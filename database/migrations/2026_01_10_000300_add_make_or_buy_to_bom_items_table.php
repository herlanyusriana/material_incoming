<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            if (!Schema::hasColumn('bom_items', 'make_or_buy')) {
                $table->string('make_or_buy', 10)->default('buy')->after('component_part_id');
                $table->index(['bom_id', 'make_or_buy']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            if (Schema::hasColumn('bom_items', 'make_or_buy')) {
                $table->dropIndex(['bom_id', 'make_or_buy']);
                $table->dropColumn('make_or_buy');
            }
        });
    }
};

