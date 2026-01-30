<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function dropForeignIfExists(string $table, string $constraintName): void
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
            // best-effort
        }
    }

    public function up(): void
    {
        if (!Schema::hasTable('boms') || !Schema::hasTable('bom_items') || !Schema::hasTable('gci_parts')) {
            return;
        }

        // SQLite can't reliably alter FKs in-place; app logic already uses gci_parts,
        // so we only enforce at DB level for mysql/pgsql.
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'pgsql'], true)) {
            return;
        }

        $this->dropForeignIfExists('boms', 'boms_part_id_foreign');
        $this->dropForeignIfExists('bom_items', 'bom_items_component_part_id_foreign');

        Schema::table('bom_items', function (Blueprint $table) {
            // Ensure component_part_id is nullable so we can keep string-only parts when needed.
            try {
                if (Schema::hasColumn('bom_items', 'component_part_id')) {
                    $table->unsignedBigInteger('component_part_id')->nullable()->change();
                }
            } catch (\Throwable $e) {
            }
        });

        Schema::table('boms', function (Blueprint $table) {
            try {
                $table->foreign('part_id')->references('id')->on('gci_parts')->cascadeOnDelete();
            } catch (\Throwable $e) {
            }
        });

        Schema::table('bom_items', function (Blueprint $table) {
            try {
                $table->foreign('component_part_id')->references('id')->on('gci_parts')->nullOnDelete();
            } catch (\Throwable $e) {
            }
        });
    }

    public function down(): void
    {
        // No-op: do not revert to legacy `parts` FKs.
    }
};

