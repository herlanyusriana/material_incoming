<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Consolidated anti-drift migration for the planning history tables.
     *
     * Production servers (built from a legacy schema) diverge from the local
     * `erp_gci_new` schema: the audit columns the history models write are
     * either missing, or exist as a different (narrower / NOT NULL) type. Each
     * divergence surfaced as a distinct SQL error, previously fixed one at a time
     * by migrations 000001-000005:
     *
     *   - 000001: forecast_histories missing changed_by / qty_after / action / ...
     *             -> "Unknown column 'changed_by'" (42S22)
     *   - 000004: a legacy NOT NULL user_id survives and is never supplied
     *             -> "Field 'user_id' doesn't have a default value" (1364)
     *   - 000005: a legacy narrower action truncates 'commit_plan'
     *             -> "Data truncated for column 'action'" (1265)
     *
     * This single, idempotent migration re-applies every one of those fixes so a
     * fresh `php artisan migrate` on any environment brings all history tables to
     * the schema the models expect, in one shot. Every change is guarded by
     * hasColumn/hasTable and no ->after() is used, so it is order/schema
     * independent and a safe no-op where a column is already correct.
     */
    public function up(): void
    {
        $this->ensureForecastHistoryAudit();
        $this->ensureForecastUserColumnNullable();
        $this->ensureMrpHistory();
        $this->ensureActionWide();
    }

    public function down(): void
    {
        // No-op: reversing could drop audit columns, re-enforce NOT NULL, or
        // shrink columns and lose history rows. Keep the tables backward-compatible.
    }

    /**
     * forecast_histories: the audit columns ForecastController writes. Added only
     * when missing, and qty_before/qty_after relaxed to nullable (a 'clear' row
     * has no per-row quantities). Mirrors 000001 + 2026_08_31_000005.
     */
    private function ensureForecastHistoryAudit(): void
    {
        if (!Schema::hasTable('forecast_histories')) {
            return;
        }

        Schema::table('forecast_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('forecast_histories', 'qty_before')) {
                $table->decimal('qty_before', 20, 3)->nullable();
            }
            if (!Schema::hasColumn('forecast_histories', 'qty_after')) {
                $table->decimal('qty_after', 20, 3)->nullable();
            }
            if (!Schema::hasColumn('forecast_histories', 'changed_by')) {
                $table->string('changed_by')->nullable();
            }
            if (!Schema::hasColumn('forecast_histories', 'action')) {
                $table->string('action', 30)->nullable();
            }
            if (!Schema::hasColumn('forecast_histories', 'parts_count')) {
                $table->unsignedInteger('parts_count')->default(0);
            }
            if (!Schema::hasColumn('forecast_histories', 'weeks_generated')) {
                $table->unsignedInteger('weeks_generated')->default(0);
            }
        });

        $this->nullableChange('forecast_histories', ['qty_before', 'qty_after'], 'decimal', [20, 3]);
    }

    /**
     * A legacy NOT NULL user_id survives on some servers but is never supplied by
     * the current models (they use changed_by / uploaded_by). Relax it to nullable
     * on every forecast table. Mirrors 000004.
     */
    private function ensureForecastUserColumnNullable(): void
    {
        foreach (['forecast_histories', 'forecasts', 'forecast_documents', 'forecast_document_rows'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                if (Schema::hasColumn($table, 'user_id')) {
                    $t->unsignedBigInteger('user_id')->nullable()->change();
                } else {
                    $t->unsignedBigInteger('user_id')->nullable()->index();
                }
            });
        }
    }

    /**
     * mrp_histories: align with the MrpHistory model + MrpController usage — add
     * the audit columns, relax user_id and per-row plan columns to nullable (a
     * global 'clear' row has no per-row data). Mirrors 000002 + 000003.
     */
    private function ensureMrpHistory(): void
    {
        if (!Schema::hasTable('mrp_histories')) {
            return;
        }

        Schema::table('mrp_histories', function (Blueprint $table) {
            if (Schema::hasColumn('mrp_histories', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            } else {
                $table->unsignedBigInteger('user_id')->nullable()->index();
            }
            if (!Schema::hasColumn('mrp_histories', 'action')) {
                $table->string('action', 30)->nullable();
            }
            if (!Schema::hasColumn('mrp_histories', 'parts_count')) {
                $table->unsignedInteger('parts_count')->default(0);
            }
        });

        $this->nullableChange('mrp_histories', ['mrp_run_id', 'part_id'], 'unsignedBigInteger');
        $this->nullableChange('mrp_histories', ['plan_date'], 'date');
        $this->nullableChange('mrp_histories', ['plan_type'], 'string', [255]);
        $this->nullableChange('mrp_histories', ['qty_before', 'qty_after'], 'decimal', [20, 4]);
    }

    /**
     * action must be wide enough for 'commit_plan' (11 chars). A legacy narrower
     * column (varchar 8/10) would truncate it. Mirrors 000005.
     */
    private function ensureActionWide(): void
    {
        foreach (['forecast_histories', 'mrp_histories', 'mps_histories'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table) {
                if (Schema::hasColumn($table, 'action')) {
                    $t->string('action', 30)->nullable()->change();
                } else {
                    $t->string('action', 30)->nullable();
                }
            });
        }
    }

    /**
     * Relax an existing column to nullable, only when present. Kept as a helper so
     * every call stays compact; type/args route to the right Blueprint method.
     */
    private function nullableChange(string $table, array $columns, string $type, array $args = []): void
    {
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table, $column, $type, $args) {
                if ($type === 'date') {
                    $t->date($column)->nullable()->change();
                } elseif ($type === 'string') {
                    $t->string($column, $args[0] ?? 255)->nullable()->change();
                } elseif ($type === 'decimal') {
                    $t->decimal($column, $args[0] ?? 20, $args[1] ?? 3)->nullable()->change();
                } else {
                    $t->unsignedBigInteger($column)->nullable()->change();
                }
            });
        }
    }
};
