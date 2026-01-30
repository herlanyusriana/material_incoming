<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('location_inventory_adjustments', function (Blueprint $table) {
            if (!Schema::hasColumn('location_inventory_adjustments', 'batch_no')) {
                $table->string('batch_no', 255)->nullable()->after('location_code');
                $table->index(['location_code', 'batch_no', 'part_id'], 'loc_inv_adj_loc_batch_part_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('location_inventory_adjustments', function (Blueprint $table) {
            if (Schema::hasColumn('location_inventory_adjustments', 'batch_no')) {
                $table->dropIndex('loc_inv_adj_loc_batch_part_idx');
                $table->dropColumn('batch_no');
            }
        });
    }
};
