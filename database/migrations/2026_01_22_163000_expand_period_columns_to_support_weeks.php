<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // We support both YYYY-MM (7 chars) and YYYY-Www (8 chars).
        // Several tables were created with varchar(7), which truncates week periods like 2026-W02.
        $tables = [
            'customer_planning_rows' => "ALTER TABLE `customer_planning_rows` MODIFY `period` VARCHAR(8) NOT NULL",
            'customer_pos' => "ALTER TABLE `customer_pos` MODIFY `period` VARCHAR(8) NOT NULL",
            'forecasts' => "ALTER TABLE `forecasts` MODIFY `period` VARCHAR(8) NOT NULL",
            'mrp_runs' => "ALTER TABLE `mrp_runs` MODIFY `period` VARCHAR(8) NOT NULL",
            // MPS is monthly by design, but widening doesn't hurt and keeps formats consistent.
            'mps' => "ALTER TABLE `mps` MODIFY `period` VARCHAR(8) NOT NULL",
        ];

        foreach ($tables as $table => $sql) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'period')) {
                DB::statement($sql);
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'customer_planning_rows' => "ALTER TABLE `customer_planning_rows` MODIFY `period` VARCHAR(7) NOT NULL",
            'customer_pos' => "ALTER TABLE `customer_pos` MODIFY `period` VARCHAR(7) NOT NULL",
            'forecasts' => "ALTER TABLE `forecasts` MODIFY `period` VARCHAR(7) NOT NULL",
            'mrp_runs' => "ALTER TABLE `mrp_runs` MODIFY `period` VARCHAR(7) NOT NULL",
            'mps' => "ALTER TABLE `mps` MODIFY `period` VARCHAR(7) NOT NULL",
        ];

        foreach ($tables as $table => $sql) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'period')) {
                DB::statement($sql);
            }
        }
    }
};

