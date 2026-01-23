<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function isMysql(): bool
    {
        try {
            return DB::connection()->getDriverName() === 'mysql';
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function renameMingguToPeriod(string $table, string $defaultPeriodSql): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasColumn($table, 'period')) {
            return;
        }

        if (!Schema::hasColumn($table, 'minggu')) {
            return;
        }

        if (!$this->isMysql()) {
            return;
        }

        // Backfill minggu first so NOT NULL changes won't fail.
        DB::statement("
            UPDATE `{$table}`
            SET `minggu` = {$defaultPeriodSql}
            WHERE `minggu` IS NULL OR `minggu` = ''
        ");

        // Rename minggu -> period and widen to support YYYY-Www (8 chars).
        DB::statement("ALTER TABLE `{$table}` CHANGE `minggu` `period` VARCHAR(8) NOT NULL");
    }

    public function up(): void
    {
        // Legacy schemas used `minggu` as the period column. Current code expects `period`.
        // Backfill is best-effort using available dates.
        $this->renameMingguToPeriod('customer_pos', "DATE_FORMAT(COALESCE(`po_date`, `delivery_date`, `created_at`, `updated_at`, NOW()), '%Y-%m')");
        $this->renameMingguToPeriod('customer_planning_rows', "DATE_FORMAT(COALESCE(`created_at`, `updated_at`, NOW()), '%Y-%m')");
        $this->renameMingguToPeriod('forecasts', "DATE_FORMAT(COALESCE(`created_at`, `updated_at`, NOW()), '%Y-%m')");
        $this->renameMingguToPeriod('mps', "DATE_FORMAT(COALESCE(`approved_at`, `created_at`, `updated_at`, NOW()), '%Y-%m')");
        $this->renameMingguToPeriod('mrp_runs', "DATE_FORMAT(COALESCE(`run_at`, `created_at`, `updated_at`, NOW()), '%x-W%v')");
    }

    public function down(): void
    {
        // No-op: we don't want to reintroduce legacy column names.
    }
};

