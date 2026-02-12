<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('outgoing_picking_fgs', 'source')) {
            Schema::table('outgoing_picking_fgs', function (Blueprint $table) {
                $table->string('source', 20)->default('daily_plan')->after('gci_part_id');
            });
        }

        if (!Schema::hasColumn('outgoing_picking_fgs', 'outgoing_po_item_id')) {
            Schema::table('outgoing_picking_fgs', function (Blueprint $table) {
                $table->foreignId('outgoing_po_item_id')->nullable()->after('source')
                    ->constrained('outgoing_po_items')->nullOnDelete();
            });
        }

        // Drop old unique index
        $driver = DB::connection()->getDriverName();
        $oldIndexName = 'outgoing_picking_fgs_delivery_date_gci_part_id_unique';
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE outgoing_picking_fgs DROP CONSTRAINT IF EXISTS {$oldIndexName}");
        } else {
            $indexes = collect(DB::select("SHOW INDEX FROM outgoing_picking_fgs WHERE Key_name = '{$oldIndexName}'"));
            if ($indexes->isNotEmpty()) {
                Schema::table('outgoing_picking_fgs', function (Blueprint $table) {
                    $table->dropUnique(['delivery_date', 'gci_part_id']);
                });
            }
        }

        // Add new unique index only if it doesn't exist
        $hasNewIndex = false;
        if ($driver === 'pgsql') {
            $hasNewIndex = DB::selectOne("SELECT 1 FROM pg_indexes WHERE indexname = 'opf_date_part_source_unique'") !== null;
        } else {
            $hasNewIndex = collect(DB::select("SHOW INDEX FROM outgoing_picking_fgs WHERE Key_name = 'opf_date_part_source_unique'"))->isNotEmpty();
        }

        if (!$hasNewIndex) {
            Schema::table('outgoing_picking_fgs', function (Blueprint $table) {
                $table->unique(['delivery_date', 'gci_part_id', 'source'], 'opf_date_part_source_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('outgoing_picking_fgs', function (Blueprint $table) {
            $table->dropUnique('opf_date_part_source_unique');
            $table->dropForeign(['outgoing_po_item_id']);
            $table->dropColumn(['source', 'outgoing_po_item_id']);
            $table->unique(['delivery_date', 'gci_part_id']);
        });
    }
};
