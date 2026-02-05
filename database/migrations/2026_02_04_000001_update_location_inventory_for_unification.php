<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('location_inventory', function (Blueprint $table) {
            // 1. Add GCI Part ID (The Internal Master ID)
            // It allows storing FG/WIP which don't have Vendor Part ID
            $table->foreignId('gci_part_id')
                ->nullable()
                ->after('part_id')
                ->constrained('gci_parts')
                ->onDelete('cascade'); // If master part deleted, inventory gone? Or restrict? Cascade is risky but standard for dev.

            // 2. Make Vendor Part ID nullable
            // Because FG/WIP items won't have it
            $table->unsignedBigInteger('part_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't revert cleanly if data exists that violates not-null, but we try structure revert
        Schema::table('location_inventory', function (Blueprint $table) {
            $table->dropForeign(['gci_part_id']);
            $table->dropColumn('gci_part_id');

            // Reverting nullable is hard if nulls exist. We assume user won't revert in production easily.
            // $table->unsignedBigInteger('part_id')->nullable(false)->change(); 
        });
    }
};
