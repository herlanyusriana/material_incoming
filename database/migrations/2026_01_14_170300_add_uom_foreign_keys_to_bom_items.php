<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            // Add foreign key columns for UOM
            $table->unsignedBigInteger('consumption_uom_id')->nullable()->after('consumption_uom');
            $table->unsignedBigInteger('wip_uom_id')->nullable()->after('wip_uom');
            
            // Add foreign key constraints
            $table->foreign('consumption_uom_id')->references('id')->on('uoms')->nullOnDelete();
            $table->foreign('wip_uom_id')->references('id')->on('uoms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            $table->dropForeign(['consumption_uom_id']);
            $table->dropForeign(['wip_uom_id']);
            $table->dropColumn(['consumption_uom_id', 'wip_uom_id']);
        });
    }
};
