<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            if (!Schema::hasColumn('bom_items', 'consumption_uom_id')) {
                $table->unsignedBigInteger('consumption_uom_id')->nullable()->after('yield_factor');
                $table->foreign('consumption_uom_id')->references('id')->on('uoms')->nullOnDelete();
            }
            if (!Schema::hasColumn('bom_items', 'wip_uom_id')) {
                $table->unsignedBigInteger('wip_uom_id')->nullable()->after('consumption_uom_id');
                $table->foreign('wip_uom_id')->references('id')->on('uoms')->nullOnDelete();
            }
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
