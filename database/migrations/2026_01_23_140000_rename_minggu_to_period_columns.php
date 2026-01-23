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

    private function buildCoalescePeriodExpr(string $table, array $candidates, string $format): string
    {
        $cols = [];
        foreach ($candidates as $col) {
            if (Schema::hasColumn($table, $col)) {
                $cols[] = "`{$col}`";
            }
        }

        // Always fallback to NOW() to avoid NULL.
        $cols[] = 'NOW()';

        $coalesce = 'COALESCE(' . implode(', ', $cols) . ')';
        return "DATE_FORMAT({$coalesce}, '{$format}')";
    }

    private function renameMingguToPeriod(string $table, string $format, array $dateCandidates): void
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

        $defaultPeriodSql = $this->buildCoalescePeriodExpr($table, $dateCandidates, $format);

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
        $this->renameMingguToPeriod('customer_pos', '%Y-%m', ['po_date', 'delivery_date', 'created_at', 'updated_at']);
        $this->renameMingguToPeriod('customer_planning_rows', '%Y-%m', ['created_at', 'updated_at']);
        $this->renameMingguToPeriod('forecasts', '%Y-%m', ['created_at', 'updated_at']);
        $this->renameMingguToPeriod('mps', '%Y-%m', ['approved_at', 'created_at', 'updated_at']);
        $this->renameMingguToPeriod('mrp_runs', '%x-W%v', ['run_at', 'created_at', 'updated_at']);
    }

    public function down(): void
    {
        // No-op: we don't want to reintroduce legacy column names.
    }
};
