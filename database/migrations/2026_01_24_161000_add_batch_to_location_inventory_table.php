<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('location_inventory')) {
            return;
        }

        Schema::table('location_inventory', function (Blueprint $table) {
            if (!Schema::hasColumn('location_inventory', 'batch_no')) {
                $table->string('batch_no', 50)->nullable()->after('location_code');
                $table->index('batch_no');
            }
            if (!Schema::hasColumn('location_inventory', 'production_date')) {
                $table->date('production_date')->nullable()->after('batch_no');
                $table->index('production_date');
            }
        });

        Schema::table('location_inventory', function (Blueprint $table) {
            // Replace unique(part_id, location_code) with unique(part_id, location_code, batch_no)
            // to allow multiple batches per location.
            //
            // Note: MySQL may use the old unique index to support the FK on `part_id`,
            // so ensure a standalone index exists before dropping it.
            try {
                $table->index('part_id', 'location_inventory_part_id_idx');
            } catch (\Throwable) {
                // ignore if already exists
            }

            try {
                $table->index('location_code', 'location_inventory_location_code_idx');
            } catch (\Throwable) {
                // ignore if already exists
            }

            try {
                $table->dropUnique(['part_id', 'location_code']);
            } catch (\Throwable) {
                // ignore if not present / different name
            }

            try {
                $table->unique(['part_id', 'location_code', 'batch_no']);
            } catch (\Throwable) {
                // ignore if already exists
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('location_inventory')) {
            return;
        }

        Schema::table('location_inventory', function (Blueprint $table) {
            try {
                $table->dropUnique(['part_id', 'location_code', 'batch_no']);
            } catch (\Throwable) {
                // ignore
            }

            try {
                $table->unique(['part_id', 'location_code']);
            } catch (\Throwable) {
                // ignore
            }
        });

        Schema::table('location_inventory', function (Blueprint $table) {
            if (Schema::hasColumn('location_inventory', 'production_date')) {
                $table->dropIndex(['production_date']);
                $table->dropColumn('production_date');
            }
            if (Schema::hasColumn('location_inventory', 'batch_no')) {
                $table->dropIndex(['batch_no']);
                $table->dropColumn('batch_no');
            }
        });
    }
};
