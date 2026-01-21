<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('mrp_purchase_plans', 'part_gci_id')) {
            Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                $table->foreignId('part_gci_id')->nullable()->after('mrp_run_id');
            });
        }

        // Migrate existing rows from incoming `parts` to `gci_parts` by matching part_no.
        if (Schema::hasTable('parts') && Schema::hasTable('gci_parts') && Schema::hasColumn('mrp_purchase_plans', 'part_id')) {
            DB::statement("
                INSERT INTO gci_parts (part_no, part_name, model, status, created_at, updated_at)
                SELECT DISTINCT
                    p.part_no,
                    COALESCE(NULLIF(p.part_name_gci, ''), NULLIF(p.part_name_vendor, ''), p.part_no) AS part_name,
                    NULL AS model,
                    'active' AS status,
                    NOW() AS created_at,
                    NOW() AS updated_at
                FROM mrp_purchase_plans mpp
                INNER JOIN parts p ON p.id = mpp.part_id
                LEFT JOIN gci_parts gp ON gp.part_no = p.part_no
                WHERE gp.id IS NULL AND p.part_no IS NOT NULL AND p.part_no <> ''
            ");

            DB::statement("
                UPDATE mrp_purchase_plans mpp
                INNER JOIN parts p ON p.id = mpp.part_id
                INNER JOIN gci_parts gp ON gp.part_no = p.part_no
                SET mpp.part_gci_id = gp.id
                WHERE mpp.part_gci_id IS NULL
            ");
        }

        // Swap column names to keep code consistent (part_id -> gci_parts).
        if (Schema::hasColumn('mrp_purchase_plans', 'part_id')) {
            Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                $table->dropForeign(['part_id']);
            });
            Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                $table->dropColumn('part_id');
            });
        }

        if (Schema::hasColumn('mrp_purchase_plans', 'part_gci_id') && !Schema::hasColumn('mrp_purchase_plans', 'part_id')) {
            DB::statement('ALTER TABLE mrp_purchase_plans CHANGE part_gci_id part_id BIGINT UNSIGNED NOT NULL');
            Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                $table->foreign('part_id')->references('id')->on('gci_parts')->cascadeOnUpdate()->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Best-effort rollback: keep data, but restore legacy column for parts.
        if (Schema::hasColumn('mrp_purchase_plans', 'part_id')) {
            Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                $table->dropForeign(['part_id']);
            });

            DB::statement('ALTER TABLE mrp_purchase_plans CHANGE part_id part_gci_id BIGINT UNSIGNED NULL');

            Schema::table('mrp_purchase_plans', function (Blueprint $table) {
                $table->foreignId('part_id')->nullable()->after('mrp_run_id');
            });
        }
    }
};

