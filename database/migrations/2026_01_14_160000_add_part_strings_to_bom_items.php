<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            if (!Schema::hasColumn('bom_items', 'component_part_no')) {
                $table->string('component_part_no', 100)->nullable()->after('component_part_id');
            }
            if (!Schema::hasColumn('bom_items', 'wip_part_no')) {
                $table->string('wip_part_no', 100)->nullable()->after('wip_part_id');
            }
            
            // Make component_part_id nullable if we want to support string-only parts
            $table->unsignedBigInteger('component_part_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $table->dropColumn(['component_part_no', 'wip_part_no']);
            $table->unsignedBigInteger('component_part_id')->nullable(false)->change();
        });
    }
};
