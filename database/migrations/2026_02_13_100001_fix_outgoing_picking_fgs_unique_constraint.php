<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // Drop old unique index [delivery_date, gci_part_id, source]
        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE outgoing_picking_fgs DROP CONSTRAINT IF EXISTS opf_date_part_source_unique");
        } else {
            $indexes = collect(DB::select("SHOW INDEX FROM outgoing_picking_fgs WHERE Key_name = 'opf_date_part_source_unique'"));
            if ($indexes->isNotEmpty()) {
                Schema::table('outgoing_picking_fgs', function (Blueprint $table) {
                    $table->dropUnique('opf_date_part_source_unique');
                });
            }
        }

        // Add new unique index including sales_order_id
        $newIndexName = 'opf_date_part_so_unique';
        $hasNewIndex = false;
        if ($driver === 'pgsql') {
            $hasNewIndex = DB::selectOne("SELECT 1 FROM pg_indexes WHERE indexname = '{$newIndexName}'") !== null;
        } else {
            $hasNewIndex = collect(DB::select("SHOW INDEX FROM outgoing_picking_fgs WHERE Key_name = '{$newIndexName}'"))->isNotEmpty();
        }

        if (!$hasNewIndex) {
            Schema::table('outgoing_picking_fgs', function (Blueprint $table) {
                $table->unique(['delivery_date', 'gci_part_id', 'sales_order_id'], 'opf_date_part_so_unique');
            });
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE outgoing_picking_fgs DROP CONSTRAINT IF EXISTS opf_date_part_so_unique");
        } else {
            Schema::table('outgoing_picking_fgs', function (Blueprint $table) {
                $table->dropUnique('opf_date_part_so_unique');
            });
        }

        Schema::table('outgoing_picking_fgs', function (Blueprint $table) {
            $table->unique(['delivery_date', 'gci_part_id', 'source'], 'opf_date_part_source_unique');
        });
    }
};
