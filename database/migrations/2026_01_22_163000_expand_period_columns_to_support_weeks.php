<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            // This migration uses MySQL-specific ALTER statements.
            return;
        }

        // Backfill NULL/empty periods first (otherwise NOT NULL alters can fail).
        if (Schema::hasTable('customer_planning_rows') && Schema::hasColumn('customer_planning_rows', 'period')) {
            DB::statement("
                UPDATE `customer_planning_rows`
                SET `period` = DATE_FORMAT(COALESCE(`created_at`, `updated_at`, NOW()), '%Y-%m')
                WHERE `period` IS NULL OR `period` = ''
            ");
        }

        if (Schema::hasTable('customer_pos') && Schema::hasColumn('customer_pos', 'period')) {
            DB::statement("
                UPDATE `customer_pos`
                SET `period` = DATE_FORMAT(COALESCE(`po_date`, `delivery_date`, `created_at`, `updated_at`, NOW()), '%Y-%m')
                WHERE `period` IS NULL OR `period` = ''
            ");
        }

        if (Schema::hasTable('forecasts') && Schema::hasColumn('forecasts', 'period')) {
            DB::statement("
                UPDATE `forecasts`
                SET `period` = DATE_FORMAT(COALESCE(`created_at`, `updated_at`, NOW()), '%Y-%m')
                WHERE `period` IS NULL OR `period` = ''
            ");
        }

        if (Schema::hasTable('mrp_runs') && Schema::hasColumn('mrp_runs', 'period')) {
            DB::statement("
                UPDATE `mrp_runs`
                SET `period` = DATE_FORMAT(COALESCE(`run_at`, `created_at`, `updated_at`, NOW()), '%x-W%v')
                WHERE `period` IS NULL OR `period` = ''
            ");
        }

        if (Schema::hasTable('mps') && Schema::hasColumn('mps', 'period')) {
            DB::statement("
                UPDATE `mps`
                SET `period` = DATE_FORMAT(COALESCE(`approved_at`, `created_at`, `updated_at`, NOW()), '%Y-%m')
                WHERE `period` IS NULL OR `period` = ''
            ");
        }

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
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

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
