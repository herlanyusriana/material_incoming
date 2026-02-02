<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('location_inventory_adjustments')) {
            return;
        }

        Schema::table('location_inventory_adjustments', function (Blueprint $table) {
            if (!Schema::hasColumn('location_inventory_adjustments', 'from_location_code')) {
                $table->string('from_location_code', 50)->nullable()->after('location_code');
            }
            if (!Schema::hasColumn('location_inventory_adjustments', 'to_location_code')) {
                $table->string('to_location_code', 50)->nullable()->after('from_location_code');
            }
            if (!Schema::hasColumn('location_inventory_adjustments', 'from_batch_no')) {
                $table->string('from_batch_no', 255)->nullable()->after('batch_no');
            }
            if (!Schema::hasColumn('location_inventory_adjustments', 'to_batch_no')) {
                $table->string('to_batch_no', 255)->nullable()->after('from_batch_no');
            }
            if (!Schema::hasColumn('location_inventory_adjustments', 'action_type')) {
                $table->string('action_type', 32)->default('adjustment')->after('to_batch_no');
                $table->index('action_type');
            }
        });

        // Add index in a separate Schema::table call so columns exist.
        if (Schema::hasColumn('location_inventory_adjustments', 'from_location_code') && Schema::hasColumn('location_inventory_adjustments', 'to_location_code')) {
            Schema::table('location_inventory_adjustments', function (Blueprint $table) {
                try {
                    $table->index(['from_location_code', 'to_location_code'], 'lia_from_to_loc_idx');
                } catch (\Throwable) {
                }
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('location_inventory_adjustments')) {
            return;
        }

        Schema::table('location_inventory_adjustments', function (Blueprint $table) {
            foreach (['action_type', 'to_batch_no', 'from_batch_no', 'to_location_code', 'from_location_code'] as $col) {
                if (Schema::hasColumn('location_inventory_adjustments', $col)) {
                    $table->dropColumn($col);
                }
            }
            // Best effort
            try {
                $table->dropIndex('lia_from_to_loc_idx');
            } catch (\Throwable) {
            }
        });
    }
};
