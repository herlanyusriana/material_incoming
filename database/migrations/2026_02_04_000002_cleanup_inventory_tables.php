<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop redundant inventory tables as we moved to unified LocationInventory
        Schema::dropIfExists('fg_inventory');
        Schema::dropIfExists('gci_inventory');
        Schema::dropIfExists('inventories'); // The old summary table, redundant now.

        // 2. Add 'type' to gci_parts if intended for distinction
        Schema::table('gci_parts', function (Blueprint $table) {
            if (!Schema::hasColumn('gci_parts', 'type')) {
                $table->string('type', 50)->default('FG')->after('part_name'); // 'RM', 'WIP', 'FG'
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We cannot easily recreate data, but we can recreate structure.
        Schema::table('gci_parts', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
