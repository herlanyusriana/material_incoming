<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy columns from the previous (product/month) planning need to be nullable
        // because current planning uses Part GCI + ISO week (minggu).
        if (Schema::hasTable('forecasts')) {
            if (Schema::hasColumn('forecasts', 'product_id')) {
                DB::statement('ALTER TABLE `forecasts` MODIFY `product_id` BIGINT UNSIGNED NULL');
            }
            if (Schema::hasColumn('forecasts', 'period')) {
                DB::statement('ALTER TABLE `forecasts` MODIFY `period` VARCHAR(7) NULL');
            }
        }

        if (Schema::hasTable('mps')) {
            if (Schema::hasColumn('mps', 'product_id')) {
                DB::statement('ALTER TABLE `mps` MODIFY `product_id` BIGINT UNSIGNED NULL');
            }
            if (Schema::hasColumn('mps', 'period')) {
                DB::statement('ALTER TABLE `mps` MODIFY `period` VARCHAR(7) NULL');
            }
        }
    }

    public function down(): void
    {
        // Intentionally left blank (making them NOT NULL again is unsafe).
    }
};

