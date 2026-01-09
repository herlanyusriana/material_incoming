<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('bom_items', 'component_gci_part_id')) {
            Schema::table('bom_items', function (Blueprint $table) {
                $table->foreignId('component_gci_part_id')->nullable()->after('bom_id');
            });
        }

        // Ensure RM parts used in BOM also exist in gci_parts (no dependency on incoming master data).
        if (Schema::hasTable('parts') && Schema::hasTable('gci_parts') && Schema::hasColumn('bom_items', 'component_part_id')) {
            DB::statement("
                INSERT INTO gci_parts (part_no, part_name, model, status, created_at, updated_at)
                SELECT DISTINCT
                    p.part_no,
                    COALESCE(NULLIF(p.part_name_gci, ''), NULLIF(p.part_name_vendor, ''), p.part_no) AS part_name,
                    NULL AS model,
                    'active' AS status,
                    NOW() AS created_at,
                    NOW() AS updated_at
                FROM bom_items bi
                INNER JOIN parts p ON p.id = bi.component_part_id
                LEFT JOIN gci_parts gp ON gp.part_no = p.part_no
                WHERE gp.id IS NULL AND p.part_no IS NOT NULL AND p.part_no <> ''
            ");

            DB::statement("
                UPDATE bom_items bi
                INNER JOIN parts p ON p.id = bi.component_part_id
                INNER JOIN gci_parts gp ON gp.part_no = p.part_no
                SET bi.component_gci_part_id = gp.id
                WHERE bi.component_gci_part_id IS NULL
            ");
        }

        // Swap column to be the canonical component_part_id (FK -> gci_parts).
        if (Schema::hasColumn('bom_items', 'component_part_id')) {
            Schema::table('bom_items', function (Blueprint $table) {
                $table->dropForeign(['component_part_id']);
            });

            Schema::table('bom_items', function (Blueprint $table) {
                $table->dropColumn('component_part_id');
            });
        }

        if (Schema::hasColumn('bom_items', 'component_gci_part_id') && !Schema::hasColumn('bom_items', 'component_part_id')) {
            DB::statement('ALTER TABLE bom_items CHANGE component_gci_part_id component_part_id BIGINT UNSIGNED NOT NULL');

            Schema::table('bom_items', function (Blueprint $table) {
                $table->foreign('component_part_id')->references('id')->on('gci_parts')->cascadeOnUpdate()->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Best-effort rollback: keep data, but remove FK to gci_parts and restore nullable column for legacy.
        if (Schema::hasColumn('bom_items', 'component_part_id')) {
            Schema::table('bom_items', function (Blueprint $table) {
                $table->dropForeign(['component_part_id']);
            });

            DB::statement('ALTER TABLE bom_items CHANGE component_part_id component_gci_part_id BIGINT UNSIGNED NULL');

            Schema::table('bom_items', function (Blueprint $table) {
                $table->foreignId('component_part_id')->nullable()->after('bom_id');
            });
        }
    }
};

