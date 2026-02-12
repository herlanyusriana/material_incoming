<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add columns only if they don't exist (handles partial migration state)
        if (!Schema::hasColumn('outgoing_delivery_planning_lines', 'source')) {
            Schema::table('outgoing_delivery_planning_lines', function (Blueprint $table) {
                $table->string('source', 20)->default('daily_plan')->after('gci_part_id');
            });
        }

        if (!Schema::hasColumn('outgoing_delivery_planning_lines', 'outgoing_po_item_id')) {
            Schema::table('outgoing_delivery_planning_lines', function (Blueprint $table) {
                $table->foreignId('outgoing_po_item_id')->nullable()->after('source')
                    ->constrained('outgoing_po_items')->nullOnDelete();
            });
        }

        // Drop old unique index - handle different naming across MySQL/PG
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE outgoing_delivery_planning_lines DROP CONSTRAINT IF EXISTS odpl_date_part_unique');
            DB::statement('ALTER TABLE outgoing_delivery_planning_lines DROP CONSTRAINT IF EXISTS outgoing_delivery_planning_lines_delivery_date_gci_part_id_uniq');
        } else {
            // MySQL: check if old index still exists before dropping
            $indexes = collect(DB::select("SHOW INDEX FROM outgoing_delivery_planning_lines WHERE Key_name = 'odpl_date_part_unique'"));
            if ($indexes->isNotEmpty()) {
                Schema::table('outgoing_delivery_planning_lines', function (Blueprint $table) {
                    $table->dropUnique('odpl_date_part_unique');
                });
            }
        }

        // Add new unique index only if it doesn't exist
        $driver = DB::connection()->getDriverName();
        $hasNewIndex = false;
        if ($driver === 'pgsql') {
            $hasNewIndex = DB::selectOne("SELECT 1 FROM pg_indexes WHERE indexname = 'odpl_date_part_source_unique'") !== null;
        } else {
            $hasNewIndex = collect(DB::select("SHOW INDEX FROM outgoing_delivery_planning_lines WHERE Key_name = 'odpl_date_part_source_unique'"))->isNotEmpty();
        }

        if (!$hasNewIndex) {
            Schema::table('outgoing_delivery_planning_lines', function (Blueprint $table) {
                $table->unique(['delivery_date', 'gci_part_id', 'source'], 'odpl_date_part_source_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('outgoing_delivery_planning_lines', function (Blueprint $table) {
            $table->dropUnique('odpl_date_part_source_unique');
            $table->dropForeign(['outgoing_po_item_id']);
            $table->dropColumn(['source', 'outgoing_po_item_id']);
            $table->unique(['delivery_date', 'gci_part_id'], 'odpl_date_part_unique');
        });
    }
};
