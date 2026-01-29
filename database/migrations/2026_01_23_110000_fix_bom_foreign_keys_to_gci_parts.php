<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        try {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql') {
                $exists = DB::selectOne(
                    "SELECT CONSTRAINT_NAME
                     FROM information_schema.TABLE_CONSTRAINTS
                     WHERE CONSTRAINT_SCHEMA = DATABASE()
                       AND TABLE_NAME = ?
                       AND CONSTRAINT_NAME = ?
                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                     LIMIT 1",
                    [$table, $constraintName],
                );
                return (bool) $exists;
            }

            if ($driver === 'pgsql') {
                $exists = DB::selectOne(
                    "SELECT constraint_name
                     FROM information_schema.table_constraints
                     WHERE table_name = ?
                       AND constraint_name = ?
                       AND constraint_type = 'FOREIGN KEY'",
                    [$table, $constraintName]
                );
                return (bool) $exists;
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function dropForeignKeyIfExists(string $table, string $constraintName): void
    {
        try {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql') {
                $exists = DB::selectOne(
                    "SELECT CONSTRAINT_NAME
                     FROM information_schema.TABLE_CONSTRAINTS
                     WHERE CONSTRAINT_SCHEMA = DATABASE()
                       AND TABLE_NAME = ?
                       AND CONSTRAINT_NAME = ?
                       AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                     LIMIT 1",
                    [$table, $constraintName],
                );

                if ($exists) {
                    DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraintName}`");
                }
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE \"{$table}\" DROP CONSTRAINT IF EXISTS \"{$constraintName}\"");
            }
        } catch (\Throwable $e) {
            // Best-effort: different DBs / missing privileges shouldn't block deploy.
        }
    }

    public function up(): void
    {
        if (!Schema::hasTable('boms') || !Schema::hasTable('bom_items') || !Schema::hasTable('gci_parts')) {
            return;
        }

        // Old schema used `parts` as FK target; current app logic uses `gci_parts`.
        $this->dropForeignKeyIfExists('bom_items', 'bom_items_component_part_id_foreign');
        $this->dropForeignKeyIfExists('boms', 'boms_part_id_foreign');

        // Clean up rows that would violate the new FK.
        try {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement("
                    UPDATE bom_items bi
                    LEFT JOIN gci_parts gp ON gp.id = bi.component_part_id
                    SET bi.component_part_id = NULL
                    WHERE bi.component_part_id IS NOT NULL AND gp.id IS NULL
                ");

                DB::statement("
                    DELETE b
                    FROM boms b
                    LEFT JOIN gci_parts gp ON gp.id = b.part_id
                    WHERE gp.id IS NULL
                ");
            } elseif ($driver === 'pgsql') {
                DB::statement("
                    UPDATE bom_items
                    SET component_part_id = NULL
                    WHERE component_part_id IS NOT NULL
                    AND component_part_id NOT IN (SELECT id FROM gci_parts)
                ");

                DB::statement("
                    DELETE FROM boms
                    WHERE part_id IS NOT NULL
                    AND part_id NOT IN (SELECT id FROM gci_parts)
                ");
            }
        } catch (\Throwable $e) {
            // If cleanup fails, FK addition below will fail and surface the error.
        }

        Schema::table('boms', function (Blueprint $table) {
            if (!$this->foreignKeyExists('boms', 'boms_part_id_foreign')) {
                try {
                    $table->foreign('part_id')->references('id')->on('gci_parts')->cascadeOnDelete();
                } catch (\Throwable $e) {
                }
            }
        });

        Schema::table('bom_items', function (Blueprint $table) {
            if (!$this->foreignKeyExists('bom_items', 'bom_items_component_part_id_foreign')) {
                try {
                    $table->foreign('component_part_id')->references('id')->on('gci_parts')->nullOnDelete();
                } catch (\Throwable $e) {
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('boms') || !Schema::hasTable('bom_items') || !Schema::hasTable('parts')) {
            return;
        }

        $this->dropForeignKeyIfExists('bom_items', 'bom_items_component_part_id_foreign');
        $this->dropForeignKeyIfExists('boms', 'boms_part_id_foreign');

        Schema::table('boms', function (Blueprint $table) {
            if (!$this->foreignKeyExists('boms', 'boms_part_id_foreign')) {
                try {
                    $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
                } catch (\Throwable $e) {
                }
            }
        });

        Schema::table('bom_items', function (Blueprint $table) {
            if (!$this->foreignKeyExists('bom_items', 'bom_items_component_part_id_foreign')) {
                try {
                    $table->foreign('component_part_id')->references('id')->on('parts')->cascadeOnDelete();
                } catch (\Throwable $e) {
                }
            }
        });
    }
};
