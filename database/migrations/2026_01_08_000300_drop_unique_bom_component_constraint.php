<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            // Allow multiple lines using the same RM part in one BOM (different process/line).
            $table->dropUnique('bom_items_bom_id_component_part_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $table->unique(['bom_id', 'component_part_id']);
        });
    }
};

